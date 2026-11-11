<?php
/**
 * ສົ່ງອີເມວແຈ້ງເຕືອນ (ບໍ່ໃຊ້ Composer/PHPMailer) — ໃຊ້ຮ່ວມ frontend + backend (backend/config/helpers/web_mail.php require ໄຟລ໌ນີ້).
 *   mailQueue()        ເພີ່ມອີເມວລົງແຖວລໍຖ້າ w_web_email_queue (migration v41) — ໄວ ບໍ່ຕ້ອງລໍຖ້າ SMTP
 *   mailKick()         ສັ່ງ frontend/api/mail_queue.php ສົ່ງແຖວໃນເບື້ອງຫຼັງ (ລໍຖ້າສູງສຸດ 0.4 ວິ)
 *   mailProcessQueue() ສົ່ງອີເມວທີ່ລໍຖ້າ (ເອີ້ນຈາກ api/mail_queue.php) — ຜິດ 5 ເທື່ອ = failed
 *   mailTemplate()     HTML ອີເມວແບບ WlaoDev (ຫົວສີຟ້າ, ປຸ່ມ)
 *   smtpSend()         SMTP ດິບ: ssl (465) / tls STARTTLS (587) / ບໍ່ເຂົ້າລະຫັດ (Mailpit 1025), AUTH LOGIN
 * ຕັ້ງຄ່າ: frontend/config/mail.php (ສຳເນົາຈາກ mail.example.php) — ບໍ່ມີໄຟລ໌ = ສົ່ງເຂົ້າ Mailpit ຂອງ Laragon (127.0.0.1:1025, ທົດລອງ)
 */

function mailConfig(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $file = dirname(__DIR__) . '/config/mail.php';
        $user = is_file($file) ? (require $file) : [];
        $cfg = (is_array($user) ? $user : []) + [
            'enabled'   => true,
            'host'      => '127.0.0.1',
            'port'      => 1025,
            'secure'    => '',          // '' | 'tls' | 'ssl'
            'user'      => '',
            'pass'      => '',
            'from'      => 'no-reply@wlaodev.local',
            'from_name' => 'WlaoDev',
            'site_url'  => '',          // URL ເຕັມຂອງເວັບໄຊ ສຳລັບລິ້ງໃນອີເມວ (ວ່າງ = ຄິດຈາກ request)
        ];
    }
    return $cfg;
}

/** URL ເຕັມຂອງເວັບໄຊ (ລິ້ງໃນອີເມວ): 'pages/my_orders' → 'http://localhost/websizewlaodev/pages/my_orders' */
function mailSiteUrl(string $path = ''): string
{
    $base = rtrim((string)mailConfig()['site_url'], '/');
    if ($base === '') {
        $https = ($_SERVER['HTTPS'] ?? '') === 'on';
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $root = preg_match('#^(.*?)/(frontend|backend)/#', $script, $m) ? $m[1] : '';
        $base = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $root;
    }
    return $base . '/' . ltrim($path, '/');
}

function mailQueue(PDO $pdo, string $to, string $subject, string $html, string $kind = '', ?int $memberId = null): bool
{
    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL) || empty(mailConfig()['enabled'])) {
        return false;
    }
    try {
        $pdo->prepare("INSERT INTO w_web_email_queue (member_id, to_email, kind, subject, body_html) VALUES (?, ?, ?, ?, ?)")
            ->execute([$memberId, $to, $kind, mb_substr($subject, 0, 255), $html]);
        $GLOBALS['mailQueued'] = true;
        return true;
    } catch (Throwable $e) {
        error_log('mailQueue: ' . $e->getMessage());
        return false;
    }
}

/** ສັ່ງສົ່ງແຖວອີເມວໃນເບື້ອງຫຼັງ (ບໍ່ລໍຖ້າ). ຄວນເອີ້ນຫຼັງ mailQueue() — ຫຼື register_shutdown_function('mailKick') */
function mailKick(): void
{
    if (!function_exists('curl_init')) {
        return;
    }
    $ch = curl_init(mailSiteUrl('api/mail_queue.php'));
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => '', CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT_MS => 400, CURLOPT_CONNECTTIMEOUT_MS => 300, CURLOPT_NOSIGNAL => true, CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

/** ສົ່ງອີເມວທີ່ລໍຖ້າ. ['sent' => n, 'failed' => n] */
function mailProcessQueue(PDO $pdo, int $limit = 30): array
{
    $stats = ['sent' => 0, 'failed' => 0];
    $rows = $pdo->query("SELECT id, to_email, subject, body_html, attempts FROM w_web_email_queue WHERE status = 'pending' ORDER BY id LIMIT " . max(1, $limit))->fetchAll(PDO::FETCH_ASSOC);
    $ok = $pdo->prepare("UPDATE w_web_email_queue SET status = 'sent', sent_at = NOW(), attempts = attempts + 1, error = NULL WHERE id = ?");
    $bad = $pdo->prepare("UPDATE w_web_email_queue SET attempts = attempts + 1, error = ?, status = IF(attempts + 1 >= 5, 'failed', 'pending') WHERE id = ?");
    foreach ($rows as $r) {
        $err = '';
        if (smtpSend($r['to_email'], $r['subject'], $r['body_html'], $err)) {
            $ok->execute([$r['id']]);
            $stats['sent']++;
        } else {
            $bad->execute([mb_substr($err, 0, 255), $r['id']]);
            $stats['failed']++;
            if (str_contains($err, 'connect')) {
                break;   // ເຊີເວີ SMTP ບໍ່ຕອບ → ຢຸດຮອບນີ້ ລອງໃໝ່ຄັ້ງໜ້າ
            }
        }
    }
    return $stats;
}

/** ສົ່ງ 1 ອີເມວ HTML ຜ່ານ SMTP. true = ສຳເລັດ, false = ຜິດ ($err) */
function smtpSend(string $to, string $subject, string $html, string &$err = ''): bool
{
    $c = mailConfig();
    $remote = ($c['secure'] === 'ssl' ? 'ssl://' : 'tcp://') . $c['host'] . ':' . (int)$c['port'];
    $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'SNI_enabled' => true]]);
    $fp = @stream_socket_client($remote, $eno, $estr, 10, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        $err = "connect failed: $estr";
        return false;
    }
    stream_set_timeout($fp, 15);
    $read = function () use ($fp) {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (string $line, array $expect) use ($fp, $read, &$err) {
        if ($line !== '') {
            fwrite($fp, $line . "\r\n");
        }
        $res = $read();
        if (!in_array((int)substr($res, 0, 3), $expect, true)) {
            $err = trim(($line !== '' ? strtok($line, ' ') . ': ' : '') . $res);
            return false;
        }
        return true;
    };
    $host = gethostname() ?: 'localhost';
    $ok = $cmd('', [220]) && $cmd("EHLO $host", [250]);
    if ($ok && $c['secure'] === 'tls') {
        $ok = $cmd('STARTTLS', [220])
            && stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)
            && $cmd("EHLO $host", [250]);
    }
    if ($ok && $c['user'] !== '') {
        $ok = $cmd('AUTH LOGIN', [334]) && $cmd(base64_encode($c['user']), [334]) && $cmd(base64_encode($c['pass']), [235]);
    }
    $from = $c['from'] ?: $c['user'];
    $ok = $ok && $cmd('MAIL FROM:<' . $from . '>', [250]) && $cmd('RCPT TO:<' . $to . '>', [250, 251]) && $cmd('DATA', [354]);
    if ($ok) {
        $enc = fn(string $s) => '=?UTF-8?B?' . base64_encode($s) . '?=';
        $headers = [
            'Date: ' . date('r'),
            'From: ' . $enc((string)$c['from_name']) . ' <' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . $enc($subject),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (explode('@', $from)[1] ?? 'wlaodev') . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
        ];
        fwrite($fp, implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($html)) . "\r\n.\r\n");
        $ok = $cmd('', [250]);
    }
    @fwrite($fp, "QUIT\r\n");
    fclose($fp);
    return $ok;
}

/**
 * HTML ອີເມວ: ຫົວສີຟ້າ WlaoDev, ຫົວຂໍ້, ເນື້ອຫາ ($bodyHtml — escape ແລ້ວ), ປຸ່ມ (ທາງເລືອກ), ທ້າຍ = ວິທີປິດແຈ້ງເຕືອນ.
 * ໃຊ້ inline style ເທົ່ານັ້ນ (Gmail ບໍ່ອ່ານ <style>).
 */
function mailTemplate(string $title, string $bodyHtml, string $btnText = '', string $btnUrl = ''): string
{
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $btn = $btnText !== '' && $btnUrl !== ''
        ? '<p style="margin:24px 0 8px;text-align:center"><a href="' . $h($btnUrl) . '" style="display:inline-block;padding:12px 28px;border-radius:8px;background:#1d4ed8;color:#ffffff;font-weight:bold;text-decoration:none">' . $h($btnText) . '</a></p>'
        : '';
    return '<!doctype html><html><body style="margin:0;padding:0;background:#f1f5f9;font-family:\'Noto Sans Lao\',\'Phetsarath OT\',Arial,sans-serif;color:#1f2937">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px"><tr><td align="center">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:10px;overflow:hidden">'
        . '<tr><td style="background:#1d4ed8;padding:18px 24px;color:#ffffff;font-size:20px;font-weight:bold">WlaoDev <span style="color:#fde68a;font-size:14px;font-weight:normal">POS &amp; ອຸປະກອນ</span></td></tr>'
        . '<tr><td style="padding:24px">'
        . '<h1 style="margin:0 0 14px;font-size:19px;color:#1e3a8a;line-height:1.5">' . $h($title) . '</h1>'
        . '<div style="font-size:15px;line-height:1.7">' . $bodyHtml . '</div>' . $btn
        . '</td></tr>'
        . '<tr><td style="padding:14px 24px;background:#f8fafc;color:#64748b;font-size:12px;line-height:1.6">'
        . 'ທ່ານໄດ້ຮັບອີເມວນີ້ ເພາະເປັນສະມາຊິກ WlaoDev. ປິດການແຈ້ງເຕືອນທາງອີເມວໄດ້ທີ່ <a href="' . $h(mailSiteUrl('pages/account')) . '" style="color:#1d4ed8">ບັນຊີຂອງຂ້ອຍ</a>. ໂທ 020 29 777 302'
        . '</td></tr></table></td></tr></table></body></html>';
}
