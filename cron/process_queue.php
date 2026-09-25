<?php
/**
 * Cron job: process scheduled email queue
 * Schedule: * * * * * php /path/to/cron/process_queue.php >> /var/log/mailgenius_cron.log 2>&1
 */
define('CLI_MODE', true);
require_once dirname(__DIR__) . '/config/config.php';

$lockFile = sys_get_temp_dir() . '/mg_queue.lock';
if (file_exists($lockFile) && (time() - filemtime($lockFile)) < 300) {
    echo "[" . date('Y-m-d H:i:s') . "] Queue processor already running.\n";
    exit;
}
file_put_contents($lockFile, getmypid());

register_shutdown_function(function() use ($lockFile) {
    @unlink($lockFile);
});

echo "[" . date('Y-m-d H:i:s') . "] Starting queue processor...\n";

try {
    $mgr  = new EmailManager();
    $due  = Database::fetchAll(
        "SELECT * FROM emails WHERE type = 'scheduled' AND scheduled_at <= NOW() ORDER BY scheduled_at ASC LIMIT 50",
        []
    );
    $count = 0;
    foreach ($due as $email) {
        $data = array_merge($email, [
            'to'       => json_decode($email['to_emails'], true),
            'cc'       => json_decode($email['cc_emails']  ?? '[]', true),
            'bcc'      => json_decode($email['bcc_emails'] ?? '[]', true),
        ]);
        $result = $mgr->send($data);
        if ($result['success']) {
            Database::query("UPDATE emails SET type='sent', sent_at=NOW() WHERE id=?", [$email['id']]);
            $count++;
        }
    }
    echo "[" . date('Y-m-d H:i:s') . "] Processed {$count} scheduled email(s).\n";
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
}

// Also process campaign batches
try {
    $camp = new CampaignManager();
    $activeCampaigns = Database::fetchAll(
        "SELECT id FROM campaigns WHERE status = 'running'",
        []
    );
    foreach ($activeCampaigns as $c) {
        $sent = $camp->processBatch($c['id']);
        if ($sent > 0) {
            echo "[" . date('Y-m-d H:i:s') . "] Campaign #{$c['id']}: sent {$sent} batch.\n";
        }
    }
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Campaign ERROR: " . $e->getMessage() . "\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Done.\n";
