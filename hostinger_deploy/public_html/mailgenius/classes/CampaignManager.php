<?php

class CampaignManager {

    public function getAll(int $page = 1): array {
        $per    = 15;
        $offset = ($page - 1) * $per;
        $total  = Database::query("SELECT COUNT(*) FROM campaigns")->fetchColumn();
        $rows   = Database::fetchAll(
            "SELECT c.*, cg.name AS group_name,
                    ROUND(c.open_count / NULLIF(c.sent_count,0) * 100, 1) AS open_rate,
                    ROUND(c.click_count / NULLIF(c.sent_count,0) * 100, 1) AS click_rate
             FROM campaigns c LEFT JOIN contact_groups cg ON cg.id = c.group_id
             ORDER BY c.created_at DESC LIMIT $per OFFSET $offset"
        );
        return ['data' => $rows, 'total' => (int)$total];
    }

    public function get(int $id): ?array {
        $c = Database::fetch(
            "SELECT c.*, cg.name AS group_name FROM campaigns c
             LEFT JOIN contact_groups cg ON cg.id = c.group_id
             WHERE c.id = ?", [$id]
        );
        if (!$c) return null;

        $c['recipient_stats'] = Database::fetchAll(
            "SELECT status, COUNT(*) AS cnt FROM campaign_contacts WHERE campaign_id = ? GROUP BY status",
            [$id]
        );
        return $c;
    }

    public function save(array $data, ?int $id = null): int {
        $payload = [
            'user_id'     => $data['user_id']    ?? null,
            'name'        => $data['name'],
            'description' => $data['description']?? null,
            'from_name'   => $data['from_name']  ?? MAIL_FROM_NAME,
            'from_email'  => $data['from_email'] ?? MAIL_FROM_EMAIL,
            'reply_to'    => $data['reply_to']   ?? null,
            'subject'     => $data['subject'],
            'body_html'   => $data['body_html'],
            'body_text'   => $data['body_text']  ?? strip_tags($data['body_html']),
            'template_id' => $data['template_id']?? null,
            'group_id'    => $data['group_id']   ?? null,
            'status'      => $data['status']     ?? 'draft',
            'scheduled_at'=> $data['scheduled_at']?? null,
            'batch_size'  => $data['batch_size'] ?? 50,
        ];

        if ($id) {
            Database::update('campaigns', $payload, ['id' => $id]);
            return $id;
        }
        return Database::insert('campaigns', $payload);
    }

    public function schedule(int $id, string $scheduledAt): bool {
        Database::update('campaigns', [
            'status'       => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ], ['id' => $id]);
        return true;
    }

    public function start(int $id): bool {
        $campaign = $this->get($id);
        if (!$campaign) return false;

        // Load recipients
        if ($campaign['group_id']) {
            $contacts = Database::fetchAll(
                "SELECT * FROM contacts WHERE group_id = ? AND is_subscribed = 1",
                [$campaign['group_id']]
            );

            Database::get()->prepare(
                "DELETE FROM campaign_contacts WHERE campaign_id = ? AND status = 'pending'"
            )->execute([$id]);

            foreach ($contacts as $contact) {
                Database::insert('campaign_contacts', [
                    'campaign_id' => $id,
                    'contact_id'  => $contact['id'],
                    'email'       => $contact['email'],
                    'status'      => 'pending',
                ]);
            }

            Database::update('campaigns', [
                'status'          => 'running',
                'started_at'      => date('Y-m-d H:i:s'),
                'total_recipients'=> count($contacts),
                'sent_count'      => 0,
            ], ['id' => $id]);
        }

        return true;
    }

    public function pause(int $id): bool {
        Database::update('campaigns', ['status' => 'paused'], ['id' => $id]);
        return true;
    }

    public function processBatch(int $id, int $batchSize = 50): array {
        $campaign = $this->get($id);
        if (!$campaign || $campaign['status'] !== 'running') {
            return ['processed' => 0, 'done' => true];
        }

        $pending = Database::fetchAll(
            "SELECT * FROM campaign_contacts WHERE campaign_id = ? AND status = 'pending' LIMIT ?",
            [$id, $batchSize]
        );

        if (empty($pending)) {
            Database::update('campaigns', [
                'status'       => 'completed',
                'completed_at' => date('Y-m-d H:i:s'),
            ], ['id' => $id]);
            return ['processed' => 0, 'done' => true];
        }

        $emailManager = new EmailManager();
        $sent         = 0;

        foreach ($pending as $recipient) {
            $bodyHtml = $this->personalizeBody($campaign['body_html'], $recipient);

            $result = $emailManager->send([
                'from_name'  => $campaign['from_name'],
                'from_email' => $campaign['from_email'],
                'to'         => $recipient['email'],
                'subject'    => $campaign['subject'],
                'body_html'  => $bodyHtml,
                'campaign_id'=> $id,
            ]);

            $status = $result['success'] ? 'sent' : 'failed';
            Database::update('campaign_contacts', [
                'status'       => $status,
                'sent_at'      => date('Y-m-d H:i:s'),
                'error_message'=> $result['error'] ?? null,
            ], ['id' => $recipient['id']]);

            if ($result['success']) $sent++;
        }

        Database::get()->prepare(
            "UPDATE campaigns SET sent_count = sent_count + ? WHERE id = ?"
        )->execute([$sent, $id]);

        $remaining = Database::query(
            "SELECT COUNT(*) FROM campaign_contacts WHERE campaign_id = ? AND status = 'pending'",
            [$id]
        )->fetchColumn();

        return ['processed' => $sent, 'remaining' => (int)$remaining, 'done' => $remaining === 0];
    }

    public function delete(int $id): bool {
        return Database::delete('campaigns', ['id' => $id]) > 0;
    }

    public function getStats(): array {
        $db = Database::get();
        return [
            'total'     => (int) $db->query("SELECT COUNT(*) FROM campaigns")->fetchColumn(),
            'running'   => (int) $db->query("SELECT COUNT(*) FROM campaigns WHERE status='running'")->fetchColumn(),
            'scheduled' => (int) $db->query("SELECT COUNT(*) FROM campaigns WHERE status='scheduled'")->fetchColumn(),
            'completed' => (int) $db->query("SELECT COUNT(*) FROM campaigns WHERE status='completed'")->fetchColumn(),
            'total_sent'=> (int) $db->query("SELECT COALESCE(SUM(sent_count),0) FROM campaigns")->fetchColumn(),
        ];
    }

    private function personalizeBody(string $html, array $contact): string {
        return str_replace(
            ['{{nombre}}',       '{{email}}',          '{{empresa}}',          '{{fecha}}'],
            [e($contact['email']), e($contact['email']), e($contact['email'] ?? ''), date('d/m/Y')],
            $html
        );
    }
}
