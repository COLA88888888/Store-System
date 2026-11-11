<?php
/**
 * ສ່ວນ: ອີເມວແຈ້ງເຕືອນສະມາຊິກເວັບໄຊ ຈາກຫຼັງບ້ານ — ໃສ່ແຖວ w_web_email_queue (migration v41) ແລ້ວ mailKick()
 * ໃຫ້ frontend/api/mail_queue.php ສົ່ງໃນເບື້ອງຫຼັງ (ຫຼັງບ້ານບໍ່ຊ້າລົງ). ໃຊ້ SMTP ຂອງ frontend/config/mail.php.
 *   webMailOrderStatus() ອໍເດີປ່ຽນສະຖານະ / ໃສ່ເລກໃບຝາກເຄື່ອງ — pages/web_orders/index.php
 *   webMailPriceDrop()   ສິນຄ້າລົດລາຄາ (ລາຄາຂາຍຫຼຸດລົງ ຫຼື ຕັ້ງລາຄາກ່ອນຫຼຸດ) → ສະມາຊິກທຸກຄົນທີ່ເປີດຮັບອີເມວ — pages/products/actions/edit_product.php
 * ສົ່ງສະເພາະສະມາຊິກທີ່ມີອີເມວ ແລະ email_notify = 1. ໄຟລ໌ນີ້ຖືກ require ຈາກ config/db.php.
 */
require_once dirname(__DIR__, 3) . '/frontend/includes/mailer.php';
require_once dirname(__DIR__, 3) . '/frontend/includes/member_notify.php';   // ກະດິ່ງແຈ້ງເຕືອນສະມາຊິກ (ທຸກສະມາຊິກ ບໍ່ສະເພາະຄົນທີ່ມີອີເມວ)

if (!function_exists('webMailOrderStatus')) {
    function webMailOrderStatus(PDO $pdo, int $orderId, string $status, ?string $trackingNo = null): void
    {
        $labels = [
            'pending'   => ['ໄດ້ຮັບອໍເດີແລ້ວ', 'ກະລຸນາໂອນເງິນ ແລະ ແນບສະລິບ ເພື່ອໃຫ້ທີມງານຢືນຢັນ.'],
            'confirmed' => ['ຢືນຢັນອໍເດີ ແລະ ຮັບເງິນແລ້ວ', 'ທີມງານກວດຍອດເງິນແລ້ວ ກຳລັງກຽມສິນຄ້າໃຫ້ທ່ານ.'],
            'packing'   => ['ກຳລັງແພັກສິນຄ້າ', 'ທີມງານກຳລັງກວດ ແລະ ຫຸ້ມຫໍ່ສິນຄ້າຂອງທ່ານ.'],
            'shipped'   => ['ສິນຄ້າກຳລັງເດີນທາງໄປຫາທ່ານ', 'ຝາກເຄື່ອງກັບບໍລິສັດຂົນສົ່ງແລ້ວ.'],
            'arrived'   => ['ເຄື່ອງຮອດສາຂາປາຍທາງແລ້ວ — ໄປຮັບໄດ້', 'ກະລຸນາໄປຮັບເຄື່ອງທີ່ສາຂາຂົນສົ່ງ ພ້ອມເລກໃບຝາກເຄື່ອງ.'],
            'completed' => ['ຮັບເຄື່ອງຮຽບຮ້ອຍ — ສຳເລັດ', 'ຂອບໃຈທີ່ໃຊ້ບໍລິການ WlaoDev.'],
            'cancelled' => ['ອໍເດີຖືກຍົກເລີກ', 'ຖ້າມີຂໍ້ສົງໄສ ກະລຸນາຕິດຕໍ່ທີມງານ.'],
        ];
        // ກະດິ່ງແຈ້ງເຕືອນໃນເວັບໄຊ — ສະມາຊິກທຸກຄົນທີ່ເປັນເຈົ້າຂອງອໍເດີ (ມີອີເມວ ຫຼື ບໍ່ກໍໄດ້)
        try {
            $nq = $pdo->prepare("SELECT member_id, order_no FROM w_web_orders WHERE id = ?");
            $nq->execute([$orderId]);
            if (($no = $nq->fetch(PDO::FETCH_ASSOC)) && (int)$no['member_id'] > 0 && isset($labels[$status])) {
                memberNotify($pdo, (int)$no['member_id'], $trackingNo === 'receipt' ? 'receipt' : ($trackingNo !== null ? 'tracking' : 'order_status'),
                    ['order_no' => $no['order_no'], 'status' => $status, 'tracking' => $trackingNo], 'pages/my_order.php?no=' . rawurlencode($no['order_no']));
            }
        } catch (Throwable $e) {
        }
        try {
            $st = $pdo->prepare("SELECT o.order_no, o.courier_name, o.courier_branch, o.tracking_no, m.id AS member_id, m.name, m.email
                                 FROM w_web_orders o JOIN w_web_members m ON m.id = o.member_id
                                 WHERE o.id = ? AND m.status = 'active' AND m.email_notify = 1 AND m.email IS NOT NULL AND m.email <> ''");
            $st->execute([$orderId]);
            $o = $st->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return;
        }
        if (!$o || !isset($labels[$status])) {
            return;
        }
        $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        [$title, $desc] = $labels[$status];
        $isReceipt = $trackingNo === 'receipt';   // ພະນັກງານແນບບິນຮັບເຄື່ອງ (ບໍ່ມີເລກ)
        $trk = $isReceipt ? '' : ($trackingNo ?? (string)$o['tracking_no']);
        $body = '<p>ສະບາຍດີ ' . $h($o['name']) . ',</p>'
            . '<p>ອໍເດີ <b>' . $h($o['order_no']) . '</b>: <b style="color:#1d4ed8">' . $h($title) . '</b><br>' . $h($desc) . '</p>';
        if (!empty($o['courier_name'])) {
            $body .= '<p>ຂົນສົ່ງ: ' . $h($o['courier_name']) . (!empty($o['courier_branch']) ? ' — ສາຂາ ' . $h($o['courier_branch']) : '') . '</p>';
        }
        if ($trk !== '') {
            $body .= '<p style="padding:10px 12px;background:#fffbeb;border-radius:6px">ເລກໃບຝາກເຄື່ອງ: <b style="font-size:17px;color:#1e3a8a">' . $h($trk) . '</b></p>';
        }
        if ($isReceipt) {
            $body .= '<p style="padding:10px 12px;background:#fffbeb;border-radius:6px">ທີມງານແນບ <b>ບິນຮັບເຄື່ອງ</b> ຂອງບໍລິສັດຂົນສົ່ງແລ້ວ — ເບິ່ງບິນໄດ້ໃນໜ້າຕິດຕາມອໍເດີ</p>';
        }
        $subject = ($isReceipt ? 'ບິນຮັບເຄື່ອງ' : ($trackingNo !== null ? 'ເລກໃບຝາກເຄື່ອງ' : $title)) . ' — ອໍເດີ ' . $o['order_no'];
        mailQueue($pdo, $o['email'], $subject, mailTemplate($title, $body, 'ຕິດຕາມອໍເດີ', mailSiteUrl('pages/my_order?no=' . rawurlencode($o['order_no']))), 'order_status', (int)$o['member_id']);
        register_shutdown_function('mailKick');
    }

    function webMailPriceDrop(PDO $pdo, int $productId, string $name, float $oldPrice, float $newPrice, string $imgUrl = ''): void
    {
        if ($newPrice <= 0 || $oldPrice <= $newPrice) {
            return;
        }
        // ກະດິ່ງແຈ້ງເຕືອນ: ສະມາຊິກທຸກຄົນ (ສິນຄ້າດຽວກັນ 1 ຄັ້ງ / 12 ຊົ່ວໂມງ)
        memberNotifyAll($pdo, 'price_drop', ['name' => $name, 'pct' => (int)round(100 - $newPrice / $oldPrice * 100)], 'pages/product.php?id=' . $productId);
        // ສົ່ງສິນຄ້າດຽວກັນບໍ່ເກີນ 1 ຄັ້ງ / 12 ຊົ່ວໂມງ (ແກ້ລາຄາຫຼາຍເທື່ອຕິດກັນ ບໍ່ spam)
        try {
            $dup = $pdo->prepare("SELECT 1 FROM w_web_email_queue WHERE kind = 'price_drop' AND subject LIKE ? AND created_at > NOW() - INTERVAL 12 HOUR LIMIT 1");
            $dup->execute(['%#' . $productId . ')%']);
            if ($dup->fetch()) {
                return;
            }
            $members = $pdo->query("SELECT id, name, email FROM w_web_members WHERE status = 'active' AND email_notify = 1 AND email IS NOT NULL AND email <> ''")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return;
        }
        if (!$members) {
            return;
        }
        $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        $fmt = fn($n) => number_format($n, 0, '.', ',') . ' ₭';
        $pct = (int)round(100 - $newPrice / $oldPrice * 100);
        $link = mailSiteUrl('pages/product?id=' . $productId);
        $img = $imgUrl !== '' ? '<p style="text-align:center"><img src="' . $h(mailSiteUrl('backend/assets/product_img/' . rawurlencode(basename($imgUrl)))) . '" alt="" width="220" style="max-width:100%;border-radius:8px"></p>' : '';
        foreach ($members as $m) {
            $body = '<p>ສະບາຍດີ ' . $h($m['name']) . ' — ສິນຄ້າລົດລາຄາສຳລັບສະມາຊິກ!</p>' . $img
                . '<p style="font-size:17px;font-weight:bold;color:#1e3a8a;margin:8px 0">' . $h($name) . '</p>'
                . '<p style="margin:4px 0"><s style="color:#94a3b8">' . $h($fmt($oldPrice)) . '</s> &nbsp; <b style="font-size:20px;color:#dc2626">' . $h($fmt($newPrice)) . '</b>'
                . ' <span style="padding:2px 8px;border-radius:4px;background:#fee2e2;color:#dc2626;font-weight:bold">-' . $pct . '%</span></p>';
            mailQueue($pdo, $m['email'], 'ລົດລາຄາ -' . $pct . '%: ' . $name . ' (#' . $productId . ')', mailTemplate('ສິນຄ້າລົດລາຄາ', $body, 'ເບິ່ງສິນຄ້າ ແລະ ສັ່ງຊື້', $link), 'price_drop', (int)$m['id']);
        }
        register_shutdown_function('mailKick');
    }
}
