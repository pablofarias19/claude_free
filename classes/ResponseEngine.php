<?php

class ResponseEngine {
    private PDO $db;

    public function __construct() {
        $this->db = Database::get();
    }

    // ── Rule evaluation ───────────────────────────────────────

    public function classify(array $email): ?array {
        $rules = Database::fetchAll(
            "SELECT * FROM response_rules WHERE is_active = 1 ORDER BY priority DESC"
        );

        foreach ($rules as $rule) {
            $conditions = json_decode($rule['conditions'], true) ?? [];
            if ($this->evaluateConditions($conditions, $rule['condition_logic'], $email)) {
                $this->db->prepare(
                    "UPDATE response_rules SET trigger_count = trigger_count + 1, last_triggered = NOW() WHERE id = ?"
                )->execute([$rule['id']]);
                return $rule;
            }
        }
        return null;
    }

    public function execute(array $rule, array $email): array {
        $actionData = json_decode($rule['action_data'] ?? '{}', true) ?? [];

        return match($rule['action_type']) {
            'auto_reply'       => $this->executeAutoReply($rule, $email, $actionData),
            'forward'          => $this->executeForward($email, $actionData),
            'apply_template'   => $this->executeTemplate($email, $actionData),
            'route_to_diagram' => $this->executeDiagram($email, $actionData),
            'tag'              => $this->executeTag($email, $actionData),
            'ignore'           => ['action' => 'ignore', 'success' => true],
            default            => ['action' => 'unknown', 'success' => false],
        };
    }

    public function processIncoming(array $email): array {
        $rule = $this->classify($email);
        if (!$rule) {
            return ['classified' => false, 'action' => 'none'];
        }

        $result = $this->execute($rule, $email);
        return array_merge($result, ['classified' => true, 'rule' => $rule['name']]);
    }

    // ── CRUD rules ────────────────────────────────────────────

    public function getRules(int $page = 1): array {
        $per    = 20;
        $offset = ($page - 1) * $per;
        $total  = Database::query("SELECT COUNT(*) FROM response_rules")->fetchColumn();
        $rows   = Database::fetchAll(
            "SELECT rr.*, rc.name AS category_name, rc.color AS category_color
             FROM response_rules rr
             LEFT JOIN response_categories rc ON rc.id = rr.category_id
             ORDER BY rr.priority DESC, rr.created_at DESC
             LIMIT $per OFFSET $offset"
        );
        return ['data' => $rows, 'total' => (int)$total];
    }

    public function saveRule(array $data, ?int $id = null): int {
        $payload = [
            'name'            => $data['name'],
            'category_id'     => $data['category_id'] ?? null,
            'conditions'      => json_encode($data['conditions'] ?? []),
            'condition_logic' => $data['condition_logic'] ?? 'AND',
            'action_type'     => $data['action_type'],
            'action_data'     => json_encode($data['action_data'] ?? []),
            'is_active'       => $data['is_active'] ?? 1,
            'priority'        => $data['priority'] ?? 0,
        ];

        if ($id) {
            Database::update('response_rules', $payload, ['id' => $id]);
            return $id;
        }
        return Database::insert('response_rules', $payload);
    }

    public function deleteRule(int $id): bool {
        return Database::delete('response_rules', ['id' => $id]) > 0;
    }

    public function getCategories(): array {
        return Database::fetchAll("SELECT * FROM response_categories ORDER BY priority ASC");
    }

    // ── Diagram walker ────────────────────────────────────────

    public function walkDiagram(int $diagramId, string $input): array {
        $diagram = Database::fetch("SELECT * FROM diagrams WHERE id = ?", [$diagramId]);
        if (!$diagram) return ['error' => 'Diagrama no encontrado'];

        $nodes       = json_decode($diagram['nodes'], true) ?? [];
        $connections = json_decode($diagram['connections'], true) ?? [];

        $startNode = $this->findNodeByType($nodes, 'start');
        if (!$startNode) return ['error' => 'Diagrama sin nodo inicial'];

        $path        = [];
        $currentId   = $startNode['id'];
        $maxSteps    = 20;
        $finalAnswer = null;

        while ($maxSteps-- > 0) {
            $node = $this->findNodeById($nodes, $currentId);
            if (!$node) break;

            $path[] = ['id' => $node['id'], 'type' => $node['type'], 'label' => $node['label']];

            if (in_array($node['type'], ['response', 'end'])) {
                $finalAnswer = $node['data']['response'] ?? $node['label'];
                break;
            }

            if ($node['type'] === 'decision') {
                $currentId = $this->evaluateDecision($node, $connections, $input);
            } else {
                $nextConn  = $this->findConnectionFrom($connections, $currentId);
                $currentId = $nextConn ? $nextConn['target'] : null;
            }

            if (!$currentId) break;
        }

        return ['path' => $path, 'answer' => $finalAnswer, 'diagram' => $diagram['name']];
    }

    // ── Internal ─────────────────────────────────────────────

    private function evaluateConditions(array $conditions, string $logic, array $email): bool {
        if (empty($conditions)) return false;
        $results = [];

        foreach ($conditions as $cond) {
            $field    = $cond['field']    ?? '';
            $operator = $cond['operator'] ?? 'contains';
            $value    = strtolower($cond['value'] ?? '');

            $haystack = strtolower(match($field) {
                'subject'      => $email['subject']    ?? '',
                'from'         => $email['from_email'] ?? '',
                'body'         => strip_tags($email['body_html'] ?? ''),
                'to'           => implode(',', (array)($email['to'] ?? [])),
                default        => '',
            });

            $results[] = match($operator) {
                'contains'     => str_contains($haystack, $value),
                'not_contains' => !str_contains($haystack, $value),
                'equals'       => $haystack === $value,
                'starts_with'  => str_starts_with($haystack, $value),
                'ends_with'    => str_ends_with($haystack, $value),
                'regex'        => (bool) preg_match("/$value/i", $haystack),
                default        => false,
            };
        }

        return $logic === 'OR'
            ? in_array(true, $results, true)
            : !in_array(false, $results, true);
    }

    private function executeAutoReply(array $rule, array $email, array $actionData): array {
        $templateId  = $actionData['template_id'] ?? null;
        $customReply = $actionData['reply_text']   ?? null;

        $bodyHtml = $customReply ?? 'Gracias por su mensaje. Hemos recibido su consulta y le responderemos a la brevedad.';

        if ($templateId) {
            $tpl = Database::fetch("SELECT * FROM templates WHERE id = ?", [$templateId]);
            if ($tpl) {
                $bodyHtml = $this->replaceVars($tpl['body_html'], $email);
            }
        }

        $emailManager = new EmailManager();
        return $emailManager->send([
            'to'         => $email['from_email'],
            'subject'    => 'Re: ' . $email['subject'],
            'body_html'  => $bodyHtml,
            'metadata'   => ['auto_reply' => true, 'rule_id' => $rule['id']],
        ]);
    }

    private function executeForward(array $email, array $actionData): array {
        $to = $actionData['forward_to'] ?? null;
        if (!$to) return ['success' => false, 'error' => 'Sin destino de reenvío'];

        $emailManager = new EmailManager();
        return $emailManager->send([
            'to'        => $to,
            'subject'   => 'FWD: ' . $email['subject'],
            'body_html' => "<p><em>Reenviado de:</em> {$email['from_email']}</p><hr>" . ($email['body_html'] ?? ''),
        ]);
    }

    private function executeTemplate(array $email, array $actionData): array {
        $templateId = $actionData['template_id'] ?? null;
        if (!$templateId) return ['success' => false, 'error' => 'Sin plantilla'];

        $tpl = Database::fetch("SELECT * FROM templates WHERE id = ?", [$templateId]);
        if (!$tpl) return ['success' => false, 'error' => 'Plantilla no encontrada'];

        $emailManager = new EmailManager();
        return $emailManager->send([
            'to'          => $email['from_email'],
            'subject'     => $this->replaceVars($tpl['subject'], $email),
            'body_html'   => $this->replaceVars($tpl['body_html'], $email),
            'template_id' => $templateId,
        ]);
    }

    private function executeDiagram(array $email, array $actionData): array {
        $diagramId = $actionData['diagram_id'] ?? null;
        if (!$diagramId) return ['success' => false, 'error' => 'Sin diagrama'];

        $subject = $email['subject'] ?? '';
        $body    = strip_tags($email['body_html'] ?? '');
        $result  = $this->walkDiagram($diagramId, $subject . ' ' . $body);

        if ($result['answer']) {
            $emailManager = new EmailManager();
            return $emailManager->send([
                'to'       => $email['from_email'],
                'subject'  => 'Re: ' . $email['subject'],
                'body_html'=> nl2br(e($result['answer'])),
                'metadata' => ['diagram_id' => $diagramId, 'path' => $result['path']],
            ]);
        }
        return ['success' => false, 'error' => 'Diagrama no generó respuesta'];
    }

    private function executeTag(array $email, array $actionData): array {
        $tags = $actionData['tags'] ?? [];
        if (!empty($email['contact_id'])) {
            $contact = Database::fetch("SELECT tags FROM contacts WHERE id = ?", [$email['contact_id']]);
            $existing = json_decode($contact['tags'] ?? '[]', true);
            $merged   = array_unique(array_merge($existing, $tags));
            Database::update('contacts', ['tags' => json_encode($merged)], ['id' => $email['contact_id']]);
        }
        return ['success' => true, 'action' => 'tagged', 'tags' => $tags];
    }

    private function replaceVars(string $text, array $email): string {
        return str_replace(
            ['{{nombre}}', '{{email}}', '{{asunto}}', '{{fecha}}'],
            [$email['from_name'] ?? '', $email['from_email'] ?? '', $email['subject'] ?? '', date('d/m/Y')],
            $text
        );
    }

    private function findNodeByType(array $nodes, string $type): ?array {
        foreach ($nodes as $n) { if (($n['type'] ?? '') === $type) return $n; }
        return null;
    }

    private function findNodeById(array $nodes, string $id): ?array {
        foreach ($nodes as $n) { if ($n['id'] === $id) return $n; }
        return null;
    }

    private function findConnectionFrom(array $connections, string $sourceId): ?array {
        foreach ($connections as $c) { if ($c['source'] === $sourceId) return $c; }
        return null;
    }

    private function evaluateDecision(array $node, array $connections, string $input): ?string {
        $conditions = $node['data']['conditions'] ?? [];
        $lower      = strtolower($input);

        foreach ($connections as $conn) {
            if ($conn['source'] !== $node['id']) continue;
            $label = strtolower($conn['label'] ?? '');
            if (empty($label) || str_contains($lower, $label)) {
                return $conn['target'];
            }
        }

        // Default: first connection
        foreach ($connections as $conn) {
            if ($conn['source'] === $node['id']) return $conn['target'];
        }
        return null;
    }
}
