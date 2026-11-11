<?php
/**
 * Shop-side print agent.
 *
 * Run this on a PC inside the shop when MiniPos itself is hosted off-site. The server cannot
 * reach a printer at 192.168.x.x, but this machine can — so it polls the server for queued
 * jobs and prints them on the local network. Each job's payload is already the finished
 * ESC/POS bytes (the server rasters the ticket image itself — see print_lan_ticket in
 * api/pos_backend.php), so this agent only needs curl: no GD, no image decoding.
 *
 * Thermal heads can't accept a second job while still printing/cutting the previous one — send
 * too fast and that ticket silently vanishes. So between two prints the agent waits an amount
 * estimated from the ticket's own byte size (a taller ticket has more raster rows, so it takes
 * longer) instead of a fixed delay, and it re-polls immediately after printing rather than
 * sleeping — the approach an existing, reliably-running shop print agent already uses.
 *
 * Usage (see print-agent.bat):
 *   php print-agent.php --url=https://your-pos-site.com --token=XXXX [--interval=3]
 *
 * The token comes from Settings > Printers on the server.
 */

if (!extension_loaded('curl')) {
    fwrite(STDERR, "PHP is missing the 'curl' extension — enable it in php.ini on this PC.\n");
    exit(1);
}

$opts = getopt('', ['url:', 'token:', 'interval::', 'once::']);
$baseUrl  = rtrim($opts['url'] ?? '', '/');
$token    = $opts['token'] ?? '';
$interval = max(1, (int)($opts['interval'] ?? 3)); // idle poll interval, seconds
$once     = isset($opts['once']);

if ($baseUrl === '' || $token === '') {
    fwrite(STDERR, "Usage: php print-agent.php --url=https://your-minipos-site.com --token=YOUR_TOKEN [--interval=3]\n");
    exit(1);
}

$endpoint = $baseUrl . '/api/print_agent.php';

// The printer can't take the next job until it's done printing + cutting the current one. Sending
// the next job too early doesn't queue behind the first — the printer's tiny receive buffer just
// drops the rest, so the first ticket comes out cut off partway through (a half-printed bill).
// These defaults are deliberately conservative (slower than most printers' real speed) because a
// truncated bill is a real money/audit problem; a print job finishing a bit early to wait is not.
// Lower printSpeedMmS further (or raise cutTimeMs) if tickets are still coming out incomplete.
$printSpeedMmS = 45;   // mm/s
$cutTimeMs     = 700;  // paper cut + settle
$minGapMs      = 600;  // floor, for very short tickets

function agentLog($msg) {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
}

// Reused across requests (keep-alive) so polling every few seconds doesn't pay a fresh TLS
// handshake each time.
function sharedCurl() {
    static $ch = null;
    if ($ch === null) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TCP_KEEPALIVE  => 1,
            CURLOPT_TIMEOUT        => 30,
        ]);
    }
    return $ch;
}

function httpPost($url, array $fields) {
    $ch = sharedCurl();
    curl_setopt_array($ch, [
        CURLOPT_URL        => $url,
        CURLOPT_POST       => true,
        CURLOPT_POSTFIELDS => http_build_query($fields),
    ]);
    $body  = curl_exec($ch);
    $err   = curl_error($ch);
    $errNo = curl_errno($ch);
    $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($body === false) {
        // Windows PHP often ships without a CA bundle, so every https call fails here until
        // curl.cainfo is set. Say so plainly rather than repeating a bare "SSL error" forever.
        if (in_array($errNo, [60, 77, 35], true)) {
            throw new Exception("SSL error: $err — set curl.cainfo (cacert.pem) in php.ini, or run print-agent-check.php");
        }
        throw new Exception("HTTP error: $err");
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new Exception("Bad response (HTTP $code): " . substr(strip_tags((string)$body), 0, 200));
    }
    if ($code === 403) {
        throw new Exception('Server refused the token — copy it again from Settings > Printers');
    }
    return $data;
}

// Raw TCP ("JetDirect", port 9100) — same protocol api/lan_printer.php uses server-side, just
// run from a machine that can actually reach the printer's LAN address.
function sendToPrinter($ip, $bytes) {
    $port = 9100;
    if (preg_match('/^([0-9a-zA-Z.-]+):([0-9]{1,5})$/', $ip, $m)) {
        $ip = $m[1];
        $port = (int)$m[2];
    }

    $errno = 0;
    $errstr = '';
    $sock = @fsockopen($ip, $port, $errno, $errstr, 4);
    if (!$sock) {
        throw new Exception("Cannot connect to $ip:$port - $errstr ($errno)");
    }
    stream_set_timeout($sock, 4);
    // Paced to the printer's own speed — same scheme as api/lan_printer.php::lanPrinterWritePaced().
    // Too slow (the old 1 KB / 15 ms ≈ 68 KB/s) and the printer runs dry mid-ticket, printing in
    // stop-start jerks; one big burst overruns a cheap printer's small receive buffer (fwrite()
    // still reports success, the printer drops the rest, and the leftover raster prints as garbled
    // text on the next job). So: a small head start, then a clock-based rate a little above
    // print speed.
    $bytesPerSec = 300000;  // same as LAN_PRINT_BYTES_PER_SEC in api/lan_printer.php
    $headStart   = 65536;   // same as LAN_PRINT_HEAD_START
    $len = strlen($bytes);
    $sent = 0;
    $start = microtime(true);
    while ($sent < $len) {
        $allowed = $headStart + (int)((microtime(true) - $start) * $bytesPerSec);
        if ($sent >= $allowed) {
            usleep(1000);
            continue;
        }
        $written = @fwrite($sock, substr($bytes, $sent, min(8192, $len - $sent, $allowed - $sent)));
        if ($written === false || $written === 0) {
            fclose($sock);
            throw new Exception("Write failed after $sent bytes");
        }
        $sent += $written;
    }
    fclose($sock);
}

// Estimate how long the printer will be busy with this ticket (ms) from its own byte size —
// a fixed delay either drops long tickets or wastes time after short ones.
function estimateBusyMs($payloadBytes, $printerDots, $speedMmS, $cutMs, $minMs) {
    $byteWidth = max(1, (int)(($printerDots + 7) / 8));
    $rows      = $payloadBytes / $byteWidth;
    $paperMm   = $rows / 8; // 203 dpi head = 8 dots/mm
    $printMs   = ($paperMm / max(1, $speedMmS)) * 1000;
    return (int)max($minMs, $printMs + $cutMs);
}

function waitForPrinter() {
    static $readyAt = 0.0; // unix time (seconds) the printer is expected to be free again
    $args = func_get_args();
    if (isset($args[0])) { // record mode: just sent a job, note when it'll be done
        $readyAt = microtime(true) + ($args[0] / 1000);
        return;
    }
    $waitMs = ($readyAt - microtime(true)) * 1000;
    if ($waitMs > 0) usleep((int)($waitMs * 1000));
}

agentLog("Print agent started — polling $endpoint every {$interval}s (idle)");

while (true) {
    $didWork = false;

    try {
        $res = httpPost($endpoint . '?action=fetch', ['token' => $token]);

        if (empty($res['success'])) {
            agentLog('Fetch refused: ' . ($res['message'] ?? 'unknown'));
        } else {
            foreach ($res['jobs'] ?? [] as $job) {
                $id    = $job['id'];
                $ip    = trim((string)($job['ip_address'] ?? ''));
                $bytes = base64_decode((string)($job['payload'] ?? ''), true);
                $dots  = (int)($job['paper_width'] ?? 80) === 58 ? 384 : 576;

                try {
                    if ($ip === '') throw new Exception('Job has no printer IP address');
                    if ($bytes === false || $bytes === '') throw new Exception('Job payload is not valid base64 ESC/POS data');

                    waitForPrinter(); // let the previous ticket finish before sending this one
                    sendToPrinter($ip, $bytes);
                    waitForPrinter(estimateBusyMs(strlen($bytes), $dots, $printSpeedMmS, $cutTimeMs, $minGapMs));

                    httpPost($endpoint . '?action=ack', ['token' => $token, 'id' => $id, 'ok' => 1]);
                    agentLog("Job #$id printed on " . ($job['printer_name'] ?? '?'));
                } catch (Exception $e) {
                    // Report the failure so the job stops being retried forever and the shop
                    // can see why it never came out.
                    httpPost($endpoint . '?action=ack', ['token' => $token, 'id' => $id, 'ok' => 0, 'error' => $e->getMessage()]);
                    agentLog("Job #$id FAILED: " . $e->getMessage());
                }
                $didWork = true;
            }
        }
    } catch (Exception $e) {
        agentLog('Poll error: ' . $e->getMessage());
    }

    if ($once) break;
    // Just printed something — a second bill may already be queued, so check again right away.
    if (!$didWork) sleep($interval);
}
