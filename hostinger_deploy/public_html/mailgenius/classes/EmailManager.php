<?php

class EmailManager {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    // ── Send ─────────────────────────────────────────────────

    public function send(array $data): array {
        // Validate
        if (empty($data['to']) || empty($data['subject']) || empty($data['body_html'])) {
            return ['success' => false, 'error' => 'Faltan campos obligatorios'];
        }

        $toList = is_array($data['to']) ? $data['to'] : [$data['to']];

        $emailId = Database::insert('emails', [
            'user_id'    => $data['user_id'] ?? null,
            'type'       => 'sent',
            'from_name'  => $data['from_name']  ?? MAIL_FROM_NAME,
            'from_email' => $data['from_email'] ?? MAIL_FROM_EMAIL,
            'to_emails'  => json_encode($toList),
            'cc_emails'  => json_encode($data['cc'] ?? []),
            'bcc_emails' => json_encode($data['bcc'] ?? []),
            'subject'    => $data['subject'],
            'body_html'  => $data['body_html'],
            'body_text'  => $data['body_text'] ?? strip_tags($data['body_html']),
            'priority'   => $data['priority'] ?? 'normal',
            'campaign_id'=> $data['campaign_id'] ?? null,
            'thread_id'  => $data['thread_id'] ?? generate_uuid(),
            'sent_at'    => date('Y-m-d H:i:s'),
            'metadata'   => json_encode($data['metadata'] ?? []),
        ]);

        // Handle attachments
        if (!empty($data['attachments'])) {
            $this->saveAttachments($emailId, $data['attachments']);
        }

        // Actually send via PHPMailer / SMTP
        $result = $this->dispatchMail($emailId, $data);

        // Log event
        foreach ($toList as $recipient) {
            Database::insert('email_logs', [
                'email_id'        => $emailId,
                'recipient_email' => $recipient,
                'event_type'      => $result['success'] ? 'sent' : 'failed',
                'metadata'        => json_encode($result),
                'created_at'      => date('Y-m-d H:i:s'),
            ]);
        }

        return array_merge($result, ['email_id' => $emailId]);
    }

    public function sendScheduled(array $data, string $scheduledAt): array {
        $toList  = is_array($data['to']) ? $data['to'] : [$data['to']];
        $emailId = Database::insert('emails', [
            'user_id'     => $data['user_id'] ?? null,
            'type'        => 'scheduled',
            'from_name'   => $data['from_name']  ?? MAIL_FROM_NAME,
            'from_email'  => $data['from_email'] ?? MAIL_FROM_EMAIL,
            'to_emails'   => json_encode($toList),
            'cc_emails'   => json_encode($data['cc']  ?? []),
            'bcc_emails'  => json_encode($data['bcc'] ?? []),
            'subject'     => $data['subject'],
            'body_html'   => $data['body_html'],
            'body_text'   => $data['body_text'] ?? strip_tags($data['body_html']),
            'priority'    => $data['priority'] ?? 'normal',
            'scheduled_at'=> $scheduledAt,
            'metadata'    => json_encode($data['metadata'] ?? []),
        ]);

        if (!empty($data['attachments'])) {
            $this->saveAttachments($emailId, $data['attachments']);
        }

        Database::insert('scheduled_queue', [
            'email_id'     => $emailId,
            'scheduled_at' => $scheduledAt,
            'status'       => 'pending',
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return ['success' => true, 'email_id' => $emailId, 'scheduled_at' => $scheduledAt];
    }

    public function saveDraft(array $data): int {
        $toList  = is_array($data['to'] ?? []) ? ($data['to'] ?? []) : [$data['to']];
        return Database::insert('emails', [
            'user_id'    => $data['user_id'] ?? null,
            'type'       => 'draft',
            'from_name'  => $data['from_name']  ?? MAIL_FROM_NAME,
            'from_email' => $data['from_email'] ?? MAIL_FROM_EMAIL,
            'to_emails'  => json_encode($toList),
            'cc_emails'  => json_encode($data['cc']  ?? []),
            'bcc_emails' => json_encode($data['bcc'] ?? []),
            'subject'    => $data['subject']   ?? '',
            'body_html'  => $data['body_html'] ?? '',
            'body_text'  => $data['body_text'] ?? '',
            'priority'   => $data['priority']  ?? 'normal',
            'metadata'   => json_encode($data['metadata'] ?? []),
        ]);
    }

    // ── Inbox / List ─────────────────────────────────────────

    public function getEmails(string $type = 'sent', int $page = 1, int $per = 20, string $search = ''): array {
        $offset = ($page - 1) * $per;
        $like   = "%$search%";

        $where  = $search
            ? "WHERE e.type = ? AND (e.subject LIKE ? OR e.from_email LIKE ?)"
            : "WHERE e.type = ?";
        $params = $search ? [$type, $like, $like] : [$type];

        $total  = Database::query(
            "SELECT COUNT(*) FROM emails e $where", $params
        )->fetchColumn();

        $rows = Database::fetchAll(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM email_attachments WHERE email_id = e.id) AS attach_count,
                    (SELECT COUNT(*) FROM email_logs WHERE email_id = e.id AND event_type = 'opened') AS open_count
             FROM emails e $where
             ORDER BY e.created_at DESC
             LIMIT $per OFFSET $offset",
            $params
        );

        return ['data' => $rows, 'total' => (int)$total, 'page' => $page, 'per' => $per];
    }

    public function getEmail(int $id): ?array {
        $email = Database::fetch(
            "SELECT e.*, u.name AS user_name FROM emails e
             LEFT JOIN users u ON u.id = e.user_id
             WHERE e.id = ?", [$id]
        );
        if (!$email) return null;

        $email['attachments'] = Database::fetchAll(
            "SELECT * FROM email_attachments WHERE email_id = ?", [$id]
        );
        $email['logs'] = Database::fetchAll(
            "SELECT * FROM email_logs WHERE email_id = ? ORDER BY created_at DESC LIMIT 50", [$id]
        );
        return $email;
    }

    public function getScheduled(): array {
        return Database::fetchAll(
            "SELECT e.*, sq.scheduled_at AS queue_at, sq.status AS queue_status, sq.attempts
             FROM emails e
             JOIN scheduled_queue sq ON sq.email_id = e.id
             WHERE sq.status IN ('pending','failed')
             ORDER BY sq.scheduled_at ASC"
        );
    }

    public function cancelScheduled(int $emailId): bool {
        Database::update('scheduled_queue', ['status' => 'cancelled'], ['email_id' => $emailId]);
        Database::update('emails', ['type' => 'draft'], ['id' => $emailId]);
        return true;
    }

    // ── Stats ────────────────────────────────────────────────

    public function getStats(): array {
        $db = $this->db;
        return [
            'sent_today'      => (int) $db->query("SELECT COUNT(*) FROM emails WHERE type='sent' AND DATE(sent_at)=CURDATE()")->fetchColumn(),
            'sent_week'       => (int) $db->query("SELECT COUNT(*) FROM emails WHERE type='sent' AND sent_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetchColumn(),
            'scheduled'       => (int) $db->query("SELECT COUNT(*) FROM scheduled_queue WHERE status='pending'")->fetchColumn(),
            'drafts'          => (int) $db->query("SELECT COUNT(*) FROM emails WHERE type='draft'")->fetchColumn(),
            'open_rate_today' => $this->calcOpenRate('today'),
            'bounced_today'   => (int) $db->query("SELECT COUNT(*) FROM email_logs WHERE event_type='bounced' AND DATE(created_at)=CURDATE()")->fetchColumn(),
        ];
    }

    public function getDailySent(int $days = 14): array {
        return Database::fetchAll(
            "SELECT DATE(sent_at) AS day, COUNT(*) AS cnt
             FROM emails WHERE type='sent' AND sent_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(sent_at) ORDER BY day ASC",
            [$days]
        );
    }

    // ── Internal ─────────────────────────────────────────────

    private function dispatchMail(int $emailId, array $data): array {
        // PHPMailer integration point
        // Requires: composer require phpmailer/phpmailer
        $phpmailerPath = APP_ROOT . '/vendor/autoload.php';

        if (!file_exists($phpmailerPath)) {
            // Fallback: native mail()
            return $this->dispatchNativeMail($data);
        }

        require_once $phpmailerPath;
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = !empty(SMTP_USER);
            $mail->Username   = SMTP_USER;
            $mail->Password   = SMTP_PASS;
            $mail->SMTPSecure = SMTP_ENCRYPTION === 'ssl'
                ? PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(
                $data['from_email'] ?? MAIL_FROM_EMAIL,
                $data['from_name']  ?? MAIL_FROM_NAME
            );

            $toList = is_array($data['to']) ? $data['to'] : [$data['to']];
            foreach ($toList as $to) {
                $mail->addAddress($to);
            }
            foreach ($data['cc']  ?? [] as $cc)  { $mail->addCC($cc); }
            foreach ($data['bcc'] ?? [] as $bcc) { $mail->addBCC($bcc); }

            $mail->isHTML(true);
            $mail->Subject = $data['subject'];
            $mail->Body    = $data['body_html'];
            $mail->AltBody = $data['body_text'] ?? strip_tags($data['body_html']);

            // Attachments from saved files
            $attachments = Database::fetchAll(
                "SELECT * FROM email_attachments WHERE email_id = ?", [$emailId]
            );
            foreach ($attachments as $att) {
                if ($att['is_inline']) {
                    $mail->addEmbeddedImage($att['file_path'], $att['cid'], $att['original_name']);
                } else {
                    $mail->addAttachment($att['file_path'], $att['original_name']);
                }
            }

            $mail->send();
            return ['success' => true, 'message' => 'Email enviado correctamente'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $mail->ErrorInfo];
        }
    }

    private function dispatchNativeMail(array $data): array {
        $toList  = is_array($data['to']) ? $data['to'] : [$data['to']];
        $to      = implode(', ', $toList);
        $subject = mb_encode_mimeheader($data['subject'], 'UTF-8', 'B');
        $from    = $data['from_email'] ?? MAIL_FROM_EMAIL;
        $headers = "MIME-Version: 1.0\r\n"
                 . "Content-Type: text/html; charset=UTF-8\r\n"
                 . "From: {$data['from_name']} <$from>\r\n"
                 . "X-Mailer: MailGenius Pro\r\n";

        $sent = mail($to, $subject, $data['body_html'], $headers);
        return $sent
            ? ['success' => true, 'message' => 'Enviado (mail nativo)']
            : ['success' => false, 'error' => 'Error con mail() nativo'];
    }

    private function saveAttachments(int $emailId, array $files): void {
        $uploadDir = UPLOADS_DIR . '/attachments/' . $emailId . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        foreach ($files as $file) {
            if (empty($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) continue;
            if ($file['size'] > MAX_UPLOAD_SIZE) continue;

            $mime = mime_content_type($file['tmp_name']);
            if (!in_array($mime, ALLOWED_MIME_TYPES)) continue;

            $ext        = pathinfo($file['name'], PATHINFO_EXTENSION);
            $stored     = bin2hex(random_bytes(16)) . '.' . $ext;
            $destPath   = $uploadDir . $stored;

            if (!move_uploaded_file($file['tmp_name'], $destPath)) continue;

            Database::insert('email_attachments', [
                'email_id'      => $emailId,
                'original_name' => $file['name'],
                'stored_name'   => $stored,
                'file_path'     => $destPath,
                'size'          => $file['size'],
                'mime_type'     => $mime,
                'is_inline'     => 0,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }

    private function calcOpenRate(string $period): float {
        $dateFilter = $period === 'today'
            ? "AND DATE(e.sent_at)=CURDATE()"
            : "AND e.sent_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)";

        $total  = (int) $this->db->query("SELECT COUNT(*) FROM emails e WHERE e.type='sent' $dateFilter")->fetchColumn();
        $opened = (int) $this->db->query("SELECT COUNT(DISTINCT el.email_id) FROM email_logs el JOIN emails e ON e.id=el.email_id WHERE el.event_type='opened' $dateFilter")->fetchColumn();

        return $total > 0 ? round($opened / $total * 100, 1) : 0.0;
    }
}
