<?php

class ContactManager {

    public function getAll(int $groupId = 0, string $search = '', int $page = 1): array {
        $per    = 25;
        $offset = ($page - 1) * $per;
        $where  = [];
        $params = [];

        if ($groupId) { $where[] = 'c.group_id = ?';         $params[] = $groupId; }
        if ($search)  { $where[] = '(c.name LIKE ? OR c.email LIKE ? OR c.company LIKE ?)';
                        $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $total    = Database::query("SELECT COUNT(*) FROM contacts c $whereStr", $params)->fetchColumn();
        $rows     = Database::fetchAll(
            "SELECT c.*, cg.name AS group_name, cg.color AS group_color
             FROM contacts c LEFT JOIN contact_groups cg ON cg.id = c.group_id
             $whereStr ORDER BY c.name ASC LIMIT $per OFFSET $offset",
            $params
        );

        return ['data' => $rows, 'total' => (int)$total, 'page' => $page];
    }

    public function get(int $id): ?array {
        $c = Database::fetch(
            "SELECT c.*, cg.name AS group_name FROM contacts c
             LEFT JOIN contact_groups cg ON cg.id = c.group_id
             WHERE c.id = ?", [$id]
        );
        if (!$c) return null;
        $c['email_history'] = Database::fetchAll(
            "SELECT e.id, e.subject, e.type, e.sent_at, e.created_at
             FROM emails e
             WHERE JSON_CONTAINS(e.to_emails, JSON_QUOTE(?))
             ORDER BY e.created_at DESC LIMIT 10", [$c['email']]
        );
        return $c;
    }

    public function save(array $data, ?int $id = null): int|false {
        // Validate email
        if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $payload = [
            'name'         => $data['name'],
            'email'        => strtolower($data['email']),
            'phone'        => $data['phone']    ?? null,
            'company'      => $data['company']  ?? null,
            'position'     => $data['position'] ?? null,
            'group_id'     => $data['group_id'] ?? null,
            'tags'         => json_encode($data['tags'] ?? []),
            'custom_fields'=> json_encode($data['custom_fields'] ?? []),
            'is_subscribed'=> $data['is_subscribed'] ?? 1,
        ];

        if ($id) {
            Database::update('contacts', $payload, ['id' => $id]);
            return $id;
        }
        return Database::insert('contacts', $payload);
    }

    public function delete(int $id): bool {
        return Database::delete('contacts', ['id' => $id]) > 0;
    }

    public function importCsv(string $filePath): array {
        $imported = 0;
        $errors   = [];

        if (($handle = fopen($filePath, 'r')) === false) {
            return ['imported' => 0, 'errors' => ['No se puede abrir el archivo']];
        }

        $headers = fgetcsv($handle);
        $headers = array_map('strtolower', array_map('trim', $headers));

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, $row);
            $result = $this->save([
                'name'    => $data['name']    ?? $data['nombre'] ?? 'Sin nombre',
                'email'   => $data['email']   ?? $data['correo'] ?? '',
                'phone'   => $data['phone']   ?? $data['telefono'] ?? null,
                'company' => $data['company'] ?? $data['empresa'] ?? null,
            ]);

            if ($result === false) {
                $errors[] = 'Email inválido: ' . ($data['email'] ?? '');
            } else {
                $imported++;
            }
        }
        fclose($handle);

        return ['imported' => $imported, 'errors' => $errors];
    }

    public function getGroups(): array {
        return Database::fetchAll(
            "SELECT cg.*, COUNT(c.id) AS actual_count
             FROM contact_groups cg
             LEFT JOIN contacts c ON c.group_id = cg.id
             GROUP BY cg.id ORDER BY cg.name ASC"
        );
    }

    public function saveGroup(array $data, ?int $id = null): int {
        $payload = [
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'color'       => $data['color'] ?? '#4f46e5',
        ];
        if ($id) {
            Database::update('contact_groups', $payload, ['id' => $id]);
            return $id;
        }
        return Database::insert('contact_groups', $payload);
    }

    public function getStats(): array {
        $db = Database::get();
        return [
            'total'        => (int) $db->query("SELECT COUNT(*) FROM contacts")->fetchColumn(),
            'subscribed'   => (int) $db->query("SELECT COUNT(*) FROM contacts WHERE is_subscribed = 1")->fetchColumn(),
            'unsubscribed' => (int) $db->query("SELECT COUNT(*) FROM contacts WHERE is_subscribed = 0")->fetchColumn(),
            'bounced'      => (int) $db->query("SELECT COUNT(*) FROM contacts WHERE bounce_count > 0")->fetchColumn(),
            'groups'       => (int) $db->query("SELECT COUNT(*) FROM contact_groups")->fetchColumn(),
        ];
    }
}
