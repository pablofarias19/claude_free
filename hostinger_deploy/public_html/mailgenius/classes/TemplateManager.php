<?php

class TemplateManager {

    public function getAll(int $categoryId = 0, string $search = ''): array {
        $where  = [];
        $params = [];
        if ($categoryId) { $where[] = 't.category_id = ?'; $params[] = $categoryId; }
        if ($search)     { $where[] = '(t.name LIKE ? OR t.subject LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        return Database::fetchAll(
            "SELECT t.*, tc.name AS category_name, tc.color AS category_color
             FROM templates t LEFT JOIN template_categories tc ON tc.id = t.category_id
             $whereStr ORDER BY t.usage_count DESC, t.name ASC",
            $params
        );
    }

    public function get(int $id): ?array {
        return Database::fetch(
            "SELECT t.*, tc.name AS category_name FROM templates t
             LEFT JOIN template_categories tc ON tc.id = t.category_id
             WHERE t.id = ?", [$id]
        );
    }

    public function save(array $data, ?int $id = null): int {
        $vars = $this->extractVariables($data['body_html'] ?? '');
        $payload = [
            'category_id' => $data['category_id'] ?? null,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'subject'     => $data['subject'],
            'body_html'   => $data['body_html'],
            'body_text'   => $data['body_text'] ?? strip_tags($data['body_html']),
            'variables'   => json_encode($vars),
            'is_active'   => $data['is_active'] ?? 1,
        ];
        if ($id) {
            Database::update('templates', $payload, ['id' => $id]);
            return $id;
        }
        return Database::insert('templates', $payload);
    }

    public function delete(int $id): bool {
        return Database::delete('templates', ['id' => $id]) > 0;
    }

    public function applyVars(int $id, array $vars): ?array {
        $tpl = $this->get($id);
        if (!$tpl) return null;

        $html    = $tpl['body_html'];
        $subject = $tpl['subject'];
        foreach ($vars as $k => $v) {
            $html    = str_replace("{{$k}}", htmlspecialchars($v), $html);
            $subject = str_replace("{{$k}}", $v, $subject);
        }
        return ['subject' => $subject, 'body_html' => $html];
    }

    public function getCategories(): array {
        return Database::fetchAll("SELECT * FROM template_categories ORDER BY name ASC");
    }

    public function incrementUsage(int $id): void {
        Database::get()->prepare(
            "UPDATE templates SET usage_count = usage_count + 1 WHERE id = ?"
        )->execute([$id]);
    }

    private function extractVariables(string $html): array {
        preg_match_all('/\{\{(\w+)\}\}/', $html, $m);
        return array_unique($m[1] ?? []);
    }
}
