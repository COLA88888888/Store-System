<?php
/**
 * ແຈ້ງເຕືອນພະນັກງານ ເມື່ອມີອໍເດີເວັບໃໝ່ ຫຼື ລູກຄ້າແນບສະລິບ — ທາງ Telegram ແລະ/ຫຼື ອີເມວ.
 * ຕັ້ງຄ່າໃນ config/notify.php (gitignored; ສຳເນົາຈາກ config/notify.example.php). ບໍ່ມີໄຟລ໌ / ວ່າງ = ບໍ່ແຈ້ງຫຍັງ.
 * ເອີ້ນຈາກ: createDirectProductOrder() + createWebOrder() (models/orders.php), memberAttachSlip() (models/member_orders.php).
 * ບໍ່ເຄີຍ throw — ການແຈ້ງເຕືອນລົ້ມເຫຼວ ບໍ່ກະທົບອໍເດີ (ບັນທຶກລົງ error log).
 */

function staffNotifyConfig(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $file = dirname(__DIR__) . '/config/notify.php';
        $user = is_file($file) ? (require $file) : [];
        $cfg = (is_array($user) ? $user : []) + ['telegram_bot_token' => '', 'telegram_chat_id' => '', 'emails' => []];
    }
    return $cfg;
}

/** ສົ່ງຂໍ້ຄວາມ (ຂໍ້ຄວາມທຳມະດາ) ໄປທຸກຊ່ອງທາງທີ່ຕັ້ງໄວ້ */
function staffNotify(PDO $pdo, string $subject, string $text): void
{
    try {
        $cfg = staffNotifyConfig();
        $token = trim((string)$cfg['telegram_bot_token']);
        $chat = trim((string)$cfg['telegram_chat_id']);
        if ($token !== '' && $chat !== '' && function_exists('curl_init')) {
            $ch = curl_init('https://api.telegram.org/bot' . $token . '/sendMessage');
            curl_setopt_array($ch, [
                CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 4, CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_POSTFIELDS => ['chat_id' => $chat, 'text' => $subject . "\n" . $text, 'disable_web_page_preview' => 'true'],
            ]);
            $out = curl_exec($ch);
            if ($out === false || !str_contains((string)$out, '"ok":true')) {
                error_log('staffNotify telegram: ' . ($out === false ? curl_error($ch) : mb_substr((string)$out, 0, 200)));
            }
            curl_close($ch);
        }
        $emails = array_filter(array_map('trim', (array)$cfg['emails']), fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL));
        if ($emails && function_exists('mailQueue')) {
            $html = mailTemplate($subject, nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8')));
            foreach ($emails as $to) {
                mailQueue($pdo, $to, $subject, $html, 'staff_notify');
            }
            register_shutdown_function('mailKick');
        }
    } catch (Throwable $e) {
        error_log('staffNotify: ' . $e->getMessage());
    }
}

/** ອໍເດີໃໝ່ ($event = 'new') ຫຼື ສະລິບໃໝ່ ($event = 'slip') */
function staffNotifyOrder(PDO $pdo, string $orderNo, string $event = 'new'): void
{
    $cfg = staffNotifyConfig();
    if (trim((string)$cfg['telegram_bot_token']) === '' && !array_filter((array)$cfg['emails'])) {
        return;   // ຍັງບໍ່ໄດ້ຕັ້ງຄ່າ — ບໍ່ຕ້ອງອ່ານ DB
    }
    try {
        $st = $pdo->prepare("SELECT id, order_no, customer_name, customer_tel, total_amount, total_qty, province, district, bank_name FROM w_web_orders WHERE order_no = ? LIMIT 1");
        $st->execute([$orderNo]);
        $o = $st->fetch(PDO::FETCH_ASSOC);
        if (!$o) {
            return;
        }
        $items = $pdo->prepare("SELECT product_name, qty FROM w_web_order_items WHERE order_id = ? LIMIT 10");
        $items->execute([(int)$o['id']]);
        $lines = array_map(fn($r) => '• ' . $r['product_name'] . ' × ' . (int)$r['qty'], $items->fetchAll(PDO::FETCH_ASSOC));
        $subject = $event === 'slip' ? '💳 ລູກຄ້າແນບສະລິບ — ' . $o['order_no'] : '🛒 ອໍເດີເວັບໃໝ່ — ' . $o['order_no'];
        $text = 'ລູກຄ້າ: ' . $o['customer_name'] . ' (' . $o['customer_tel'] . ")\n"
            . ($lines ? implode("\n", $lines) . "\n" : '')
            . 'ຍອດ: ' . ((float)$o['total_amount'] > 0 ? number_format((float)$o['total_amount']) . ' ₭' : 'ຕາມໃບສະເໜີລາຄາ')
            . ($event === 'slip' && $o['bank_name'] ? ' · ໂອນຜ່ານ ' . $o['bank_name'] : '') . "\n"
            . (trim($o['province'] . ' ' . $o['district']) !== '' ? 'ສົ່ງໄປ: ' . trim($o['district'] . ', ' . $o['province'], ' ,') . "\n" : '')
            . 'ເປີດລະບົບ: ' . mailSiteUrl('backend/');
        staffNotify($pdo, $subject, $text);
    } catch (Throwable $e) {
        error_log('staffNotifyOrder: ' . $e->getMessage());
    }
}
