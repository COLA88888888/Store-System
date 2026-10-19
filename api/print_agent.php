<?php
/**
 * Endpoint for the shop's print agent (tools/print-agent.php).
 *
 * When MiniPos is hosted off-site it cannot reach a printer's 192.168.x.x address, so LAN
 * print jobs are parked in print_jobs (already rendered to ESC/POS bytes server-side — see
 * the print_lan_ticket handler in api/pos_backend.php) and the agent, which DOES sit on that
 * LAN, pulls them down and sends them straight to the socket. No image decoding needed on the
 * agent side; the bytes are print-ready.
 *
 * The agent has no login, so it authenticates with a shared token from Settings > Printers.
 *
 *   ?action=status -> health check for tools/print-agent-check.php
 *   ?action=fetch  -> pending jobs (id, ip_address, payload)
 *   ?action=ack    -> mark one job done or failed
 */

session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$token = trim((string)getSetting($pdo, 'print_agent_token', ''));
$given = trim((string)($_POST['token'] ?? $_GET['token'] ?? ''));

// hash_equals so a wrong token can't be discovered by timing the response.
if ($token === '' || $given === '' || !hash_equals($token, $given)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid agent token']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Health check for tools/print-agent-check.php — reports the server half of the setup so the
// shop can see which link in the chain is broken without digging through the database.
if ($action === 'status') {
    $tableExists = false;
    $pending = 0;
    $printers = [];
    try {
        $tableExists = (bool)$pdo->query("SHOW TABLES LIKE 'st_print_jobs'")->fetch();
        if ($tableExists) {
            $pending = (int)$pdo->query("SELECT COUNT(*) FROM st_print_jobs WHERE status = 'pending'")->fetchColumn();
        }
        foreach ($pdo->query("SELECT id, name, ip_address, status FROM st_tbl_printer WHERE type = 'ip'") as $p) {
            $printers[] = [
                'id'         => (int)$p['id'],
                'name'       => $p['name'],
                'ip_address' => $p['ip_address'],
                'active'     => (int)$p['status'] === 1,
            ];
        }
    } catch (Throwable $e) {}

    echo json_encode([
        'success'          => true,
        'print_mode'       => getSetting($pdo, 'print_mode', 'direct'),
        'print_jobs_table' => $tableExists,
        'pending'          => $pending,
        'printers'         => $printers,
        'server_time'      => date('Y-m-d H:i:s'),
    ]);
    exit;
}

if ($action === 'fetch') {
    $limit = min(20, max(1, intval($_GET['limit'] ?? 5)));
    try {
        $stmt = $pdo->prepare("
            SELECT j.id, j.payload, p.ip_address, p.name AS printer_name, p.paper_width
            FROM st_print_jobs j
            JOIN st_tbl_printer p ON p.id = j.printer_id
            WHERE j.status = 'pending'
            ORDER BY j.id ASC
            LIMIT $limit
        ");
        $stmt->execute();
        $jobs = [];
        foreach ($stmt->fetchAll() as $row) {
            $jobs[] = [
                'id'           => (int)$row['id'],
                'printer_name' => $row['printer_name'],
                'ip_address'   => $row['ip_address'],
                // Lets the agent size the inter-ticket print-gap correctly (58mm vs 80mm).
                'paper_width'  => (int)$row['paper_width'],
                // Base64 of the finished ESC/POS bytes — write it straight to the socket.
                'payload'      => $row['payload'],
            ];
        }
        echo json_encode(['success' => true, 'jobs' => $jobs]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'ack') {
    $id = intval($_POST['id'] ?? 0);
    $ok = !empty($_POST['ok']);
    $error = substr(trim((string)($_POST['error'] ?? '')), 0, 500);
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing id']);
        exit;
    }
    try {
        $pdo->prepare("UPDATE st_print_jobs SET status = ?, error = ?, printed_at = NOW() WHERE id = ?")
            ->execute([$ok ? 'done' : 'failed', $ok ? null : $error, $id]);
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action']);
