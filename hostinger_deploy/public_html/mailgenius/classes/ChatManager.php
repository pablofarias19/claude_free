<?php

class ChatManager {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    // ── Sessions ─────────────────────────────────────────────

    public function createSession(array $data): array {
        $token = bin2hex(random_bytes(32));
        $id    = Database::insert('chat_sessions', [
            'session_token'  => $token,
            'visitor_name'   => $data['name']  ?? 'Visitante',
            'visitor_email'  => $data['email'] ?? null,
            'visitor_ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
            'page_url'       => $data['page_url'] ?? null,
            'referrer'       => $data['referrer'] ?? null,
            'status'         => 'waiting',
            'started_at'     => date('Y-m-d H:i:s'),
        ]);

        // Send welcome bot message
        $this->sendBotWelcome($id);
        $this->notifyAgents($id);

        return ['session_id' => $id, 'token' => $token];
    }

    public function getSession(int $id): ?array {
        $session = Database::fetch(
            "SELECT cs.*, u.name AS agent_name, u.avatar AS agent_avatar
             FROM chat_sessions cs
             LEFT JOIN users u ON u.id = cs.agent_id
             WHERE cs.id = ?", [$id]
        );
        if (!$session) return null;

        $session['messages'] = $this->getMessages($id);
        return $session;
    }

    public function getSessionByToken(string $token): ?array {
        return Database::fetch(
            "SELECT cs.*, u.name AS agent_name, u.avatar AS agent_avatar
             FROM chat_sessions cs
             LEFT JOIN users u ON u.id = cs.agent_id
             WHERE cs.session_token = ?", [$token]
        );
    }

    public function getSessions(string $status = '', int $page = 1): array {
        $per    = 30;
        $offset = ($page - 1) * $per;
        $where  = $status ? "WHERE cs.status = ?" : "WHERE 1=1";
        $params = $status ? [$status] : [];

        $total = Database::query(
            "SELECT COUNT(*) FROM chat_sessions cs $where", $params
        )->fetchColumn();

        $rows = Database::fetchAll(
            "SELECT cs.*, u.name AS agent_name,
                    (SELECT COUNT(*) FROM chat_messages cm WHERE cm.session_id = cs.id) AS msg_count,
                    (SELECT COUNT(*) FROM chat_messages cm WHERE cm.session_id = cs.id AND cm.is_read = 0 AND cm.sender_type = 'visitor') AS unread_count,
                    (SELECT cm2.content FROM chat_messages cm2 WHERE cm2.session_id = cs.id ORDER BY cm2.created_at DESC LIMIT 1) AS last_message
             FROM chat_sessions cs
             LEFT JOIN users u ON u.id = cs.agent_id
             $where
             ORDER BY cs.started_at DESC
             LIMIT $per OFFSET $offset",
            $params
        );

        return ['data' => $rows, 'total' => (int)$total];
    }

    public function assignAgent(int $sessionId, int $agentId): bool {
        $rows = Database::update('chat_sessions', [
            'agent_id' => $agentId,
            'status'   => 'active',
        ], ['id' => $sessionId]);

        if ($rows > 0) {
            $this->addSystemMessage($sessionId, 'Agente conectado. Ya puedes continuar la conversación.');
        }
        return $rows > 0;
    }

    public function closeSession(int $sessionId): bool {
        Database::update('chat_sessions', [
            'status'    => 'closed',
            'closed_at' => date('Y-m-d H:i:s'),
        ], ['id' => $sessionId]);
        $this->addSystemMessage($sessionId, 'Conversación finalizada. ¡Gracias por contactarnos!');
        return true;
    }

    public function transferToEmail(int $sessionId, int $userId): ?int {
        $session  = $this->getSession($sessionId);
        if (!$session) return null;

        $messages = $session['messages'];
        $bodyHtml = $this->buildEmailFromChat($session, $messages);
        $subject  = 'Conversación de chat — ' . ($session['visitor_name'] ?? 'Visitante');

        $emailManager = new EmailManager();
        $result = $emailManager->send([
            'user_id'    => $userId,
            'to'         => $session['visitor_email'] ?? MAIL_FROM_EMAIL,
            'subject'    => $subject,
            'body_html'  => $bodyHtml,
            'metadata'   => ['source' => 'chat', 'session_id' => $sessionId],
        ]);

        if ($result['success']) {
            Database::update('chat_sessions', [
                'converted_to_email' => 1,
                'email_id'           => $result['email_id'],
            ], ['id' => $sessionId]);
        }

        return $result['email_id'] ?? null;
    }

    // ── Messages ─────────────────────────────────────────────

    public function sendMessage(int $sessionId, array $data): array {
        $msgId = Database::insert('chat_messages', [
            'session_id'      => $sessionId,
            'sender_type'     => $data['sender_type'],
            'sender_id'       => $data['sender_id'] ?? null,
            'sender_name'     => $data['sender_name'] ?? null,
            'message_type'    => $data['message_type'] ?? 'text',
            'content'         => $data['content'],
            'attachment_path' => $data['attachment_path'] ?? null,
            'attachment_name' => $data['attachment_name'] ?? null,
            'attachment_size' => $data['attachment_size'] ?? null,
            'attachment_mime' => $data['attachment_mime'] ?? null,
            'metadata'        => json_encode($data['metadata'] ?? []),
            'created_at'      => date('Y-m-d H:i:s'),
        ]);

        // Mark first agent response time
        if ($data['sender_type'] === 'agent') {
            $session = Database::fetch("SELECT first_response_at FROM chat_sessions WHERE id = ?", [$sessionId]);
            if ($session && empty($session['first_response_at'])) {
                Database::update('chat_sessions', ['first_response_at' => date('Y-m-d H:i:s')], ['id' => $sessionId]);
            }
        }

        // Check bot rules if visitor message
        $botReply = null;
        if ($data['sender_type'] === 'visitor') {
            $botReply = $this->processBotResponse($sessionId, $data['content']);
        }

        return [
            'id'        => $msgId,
            'bot_reply' => $botReply,
            'created_at'=> date('Y-m-d H:i:s'),
        ];
    }

    public function getMessages(int $sessionId, int $since = 0): array {
        $where  = $since ? "AND cm.id > ?" : "";
        $params = $since ? [$sessionId, $since] : [$sessionId];
        return Database::fetchAll(
            "SELECT cm.*, u.name AS agent_name, u.avatar AS agent_avatar
             FROM chat_messages cm
             LEFT JOIN users u ON u.id = cm.sender_id AND cm.sender_type = 'agent'
             WHERE cm.session_id = ? $where
             ORDER BY cm.created_at ASC",
            $params
        );
    }

    public function markRead(int $sessionId, string $readerType): void {
        $this->db->prepare(
            "UPDATE chat_messages SET is_read = 1, read_at = NOW()
             WHERE session_id = ? AND sender_type != ? AND is_read = 0"
        )->execute([$sessionId, $readerType]);
    }

    public function uploadChatFile(int $sessionId, array $file): array {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Error de subida'];
        }

        $mime = mime_content_type($file['tmp_name']);
        $dir  = UPLOADS_DIR . '/chat/' . $sessionId . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $ext    = pathinfo($file['name'], PATHINFO_EXTENSION);
        $stored = bin2hex(random_bytes(12)) . '.' . $ext;
        $path   = $dir . $stored;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            return ['success' => false, 'error' => 'Error al guardar'];
        }

        $url = UPLOADS_URL . '/chat/' . $sessionId . '/' . $stored;
        return ['success' => true, 'url' => $url, 'name' => $file['name'], 'size' => $file['size'], 'mime' => $mime, 'path' => $path];
    }

    // ── Canned responses ─────────────────────────────────────

    public function getCanned(string $search = ''): array {
        if ($search) {
            return Database::fetchAll(
                "SELECT * FROM chat_canned WHERE is_active = 1 AND (shortcut LIKE ? OR title LIKE ? OR content LIKE ?) ORDER BY usage_count DESC",
                ["%$search%", "%$search%", "%$search%"]
            );
        }
        return Database::fetchAll("SELECT * FROM chat_canned WHERE is_active = 1 ORDER BY usage_count DESC");
    }

    public function useCanned(int $id, array $vars = []): string {
        $canned = Database::fetch("SELECT * FROM chat_canned WHERE id = ?", [$id]);
        if (!$canned) return '';

        Database::update('chat_canned', ['usage_count' => $canned['usage_count'] + 1], ['id' => $id]);

        $content = $canned['content'];
        foreach ($vars as $k => $v) {
            $content = str_replace("{{$k}}", $v, $content);
        }
        return $content;
    }

    // ── Stats ─────────────────────────────────────────────────

    public function getStats(): array {
        return [
            'active'          => Database::count('chat_sessions', ['status' => 'active']),
            'waiting'         => Database::count('chat_sessions', ['status' => 'waiting']),
            'closed_today'    => (int) $this->db->query("SELECT COUNT(*) FROM chat_sessions WHERE status='closed' AND DATE(closed_at)=CURDATE()")->fetchColumn(),
            'total_today'     => (int) $this->db->query("SELECT COUNT(*) FROM chat_sessions WHERE DATE(started_at)=CURDATE()")->fetchColumn(),
            'avg_response_s'  => $this->avgResponseTime(),
            'unread_messages' => (int) $this->db->query("SELECT COUNT(*) FROM chat_messages WHERE sender_type='visitor' AND is_read=0")->fetchColumn(),
        ];
    }

    // ── Internal ─────────────────────────────────────────────

    private function sendBotWelcome(int $sessionId): void {
        $welcome = Database::fetch(
            "SELECT * FROM chat_bot_responses WHERE is_active = 1 ORDER BY priority DESC LIMIT 1"
        );
        if (!$welcome) {
            $this->sendMessage($sessionId, [
                'sender_type' => 'bot',
                'sender_name' => 'Asistente',
                'content'     => '¡Hola! ¿En qué puedo ayudarte?',
                'message_type'=> 'text',
            ]);
            return;
        }

        $this->sendMessage($sessionId, [
            'sender_type' => 'bot',
            'sender_name' => 'Asistente',
            'content'     => $welcome['response_text'],
            'message_type'=> $welcome['response_type'],
            'metadata'    => ['quick_replies' => json_decode($welcome['quick_replies'] ?? '[]', true)],
        ]);
    }

    private function processBotResponse(int $sessionId, string $message): ?array {
        $session = Database::fetch("SELECT agent_id, status FROM chat_sessions WHERE id = ?", [$sessionId]);
        if ($session && $session['agent_id']) return null; // Agent is handling

        $rules = Database::fetchAll("SELECT * FROM chat_bot_responses WHERE is_active = 1 ORDER BY priority DESC");
        $lower = strtolower($message);

        foreach ($rules as $rule) {
            $keywords = json_decode($rule['trigger_keywords'], true) ?? [];
            foreach ($keywords as $kw) {
                if (str_contains($lower, strtolower($kw))) {
                    // Increment trigger count
                    $this->db->prepare("UPDATE chat_bot_responses SET trigger_count = trigger_count + 1 WHERE id = ?")->execute([$rule['id']]);

                    $reply = [
                        'sender_type' => 'bot',
                        'sender_name' => 'Asistente',
                        'content'     => $rule['response_text'],
                        'message_type'=> $rule['response_type'],
                        'metadata'    => ['quick_replies' => json_decode($rule['quick_replies'] ?? '[]', true)],
                    ];
                    $msgId = Database::insert('chat_messages', [
                        'session_id'  => $sessionId,
                        'sender_type' => 'bot',
                        'sender_name' => 'Asistente',
                        'message_type'=> $rule['response_type'],
                        'content'     => $rule['response_text'],
                        'metadata'    => json_encode(['quick_replies' => json_decode($rule['quick_replies'] ?? '[]', true)]),
                        'created_at'  => date('Y-m-d H:i:s'),
                    ]);

                    if ($rule['next_action'] === 'transfer_to_agent') {
                        Database::update('chat_sessions', ['status' => 'waiting'], ['id' => $sessionId]);
                        $this->notifyAgents($sessionId);
                    } elseif ($rule['next_action'] === 'close') {
                        $this->closeSession($sessionId);
                    }

                    return array_merge($reply, ['id' => $msgId]);
                }
            }
        }
        return null;
    }

    private function addSystemMessage(int $sessionId, string $text): void {
        Database::insert('chat_messages', [
            'session_id'  => $sessionId,
            'sender_type' => 'system',
            'sender_name' => 'Sistema',
            'message_type'=> 'text',
            'content'     => $text,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    private function notifyAgents(int $sessionId): void {
        $session = Database::fetch("SELECT * FROM chat_sessions WHERE id = ?", [$sessionId]);
        $agents  = Database::fetchAll("SELECT id FROM users WHERE role IN ('admin','user') AND is_active = 1");
        foreach ($agents as $agent) {
            Database::insert('notifications', [
                'user_id'    => $agent['id'],
                'type'       => 'new_chat',
                'title'      => 'Nueva conversación de chat',
                'body'       => 'Nuevo visitante en espera: ' . ($session['visitor_name'] ?? 'Visitante'),
                'link'       => '/chat.php?session=' . $sessionId,
                'source_type'=> 'chat',
                'source_id'  => $sessionId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function buildEmailFromChat(array $session, array $messages): string {
        $rows = '';
        foreach ($messages as $m) {
            $bg    = $m['sender_type'] === 'agent' ? '#e8f4fd' : '#f0f0f0';
            $name  = $m['sender_type'] === 'agent'
                ? ($m['agent_name'] ?? 'Agente')
                : ($session['visitor_name'] ?? 'Visitante');
            $rows .= "<tr><td style='padding:8px 12px;background:$bg;border-radius:6px;margin:4px 0'>
                        <strong>$name</strong> <small style='color:#888'>{$m['created_at']}</small><br>
                        " . nl2br(e($m['content'])) . "
                      </td></tr>";
        }

        return "<html><body style='font-family:Arial,sans-serif'>
            <h2 style='color:#4f46e5'>Historial de Chat — " . e($session['visitor_name'] ?? 'Visitante') . "</h2>
            <p><strong>Email:</strong> " . e($session['visitor_email'] ?? 'N/A') . " &nbsp;
               <strong>Inicio:</strong> " . e($session['started_at']) . "</p>
            <table cellspacing='4' style='width:100%;max-width:600px'>$rows</table>
        </body></html>";
    }

    private function avgResponseTime(): int {
        $row = $this->db->query(
            "SELECT AVG(TIMESTAMPDIFF(SECOND, started_at, first_response_at)) AS avg_s
             FROM chat_sessions WHERE first_response_at IS NOT NULL AND DATE(started_at) = CURDATE()"
        )->fetch();
        return (int)($row['avg_s'] ?? 0);
    }
}
