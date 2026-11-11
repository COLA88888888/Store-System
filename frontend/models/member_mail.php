<?php
/**
 * ອີເມວແຈ້ງເຕືອນສະມາຊິກ ຈາກ frontend (ສົ່ງຜ່ານແຖວ w_web_email_queue — includes/mailer.php):
 *   memberMailWelcome() ລົງທະບຽນສຳເລັດ (ເບີໂທ/ອີເມວ ຫຼື Google) — api/member.php
 *   memberMailOrder()   ໄດ້ຮັບອໍເດີແລ້ວ + ຍອດ + ລິ້ງຕິດຕາມ — pages/product.php, pages/checkout.php
 * ອີເມວອື່ນ (ປ່ຽນສະຖານະອໍເດີ, ລົດລາຄາ) ສ້າງຢູ່ backend/config/helpers/web_mail.php.
 * ສົ່ງສະເພາະສະມາຊິກທີ່ມີອີເມວ ແລະ email_notify = 1 (ປິດໄດ້ໃນ pages/account.php).
 */

/** ສະມາຊິກ (ຕາມ id) ທີ່ຮັບອີເມວໄດ້: ['id','name','email','member_no'] ຫຼື null */
function memberMailTarget(PDO $pdo, int $memberId): ?array
{
    try {
        $st = $pdo->prepare("SELECT id, name, email, member_no FROM w_web_members WHERE id = ? AND status = 'active' AND email_notify = 1 AND email IS NOT NULL AND email <> ''");
        $st->execute([$memberId]);
        return $st->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        return null;
    }
}

function memberMailWelcome(PDO $pdo, int $memberId): void
{
    $m = memberMailTarget($pdo, $memberId);
    if (!$m) {
        return;
    }
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $body = '<p>ສະບາຍດີ ' . $h($m['name']) . ' 🙏</p>'
        . '<p>ຂອບໃຈທີ່ລົງທະບຽນເປັນສະມາຊິກ WlaoDev — ເລກສະມາຊິກຂອງທ່ານແມ່ນ <b style="color:#1d4ed8">' . $h(memberCode($m['member_no'])) . '</b></p>'
        . '<ul style="padding-left:18px;margin:12px 0">'
        . '<li>ເຫັນລາຄາສິນຄ້າທຸກລາຍການ + ສ່ວນຫຼຸດສະມາຊິກ 5%</li>'
        . '<li>ສັ່ງຊື້ອອນລາຍ, ແນບສະລິບ ແລະ ຕິດຕາມສະຖານະເຄື່ອງ</li>'
        . '<li>ຮັບແຈ້ງເຕືອນທາງອີເມວນີ້ ເມື່ອສິນຄ້າລົດລາຄາ ຫຼື ອໍເດີມີການອັບເດດ</li>'
        . '</ul>';
    mailQueue($pdo, $m['email'], 'ຍິນດີຕ້ອນຮັບສູ່ WlaoDev — ສະມາຊິກ ' . memberCode($m['member_no']),
        mailTemplate('ລົງທະບຽນສຳເລັດ!', $body, 'ເບິ່ງສິນຄ້າ ແລະ ລາຄາ', mailSiteUrl('pages/equipment_list')), 'welcome', (int)$m['id']);
    register_shutdown_function('mailKick');
}

function memberMailOrder(PDO $pdo, int $memberId, string $orderNo): void
{
    $m = memberMailTarget($pdo, $memberId);
    $o = $m ? memberOrder($pdo, $m + ['tel' => ''], $orderNo) : null;
    if (!$o) {
        return;
    }
    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    $rows = '';
    foreach ($o['items'] as $it) {
        $rows .= '<tr><td style="padding:6px 0;border-bottom:1px solid #e5e7eb">' . $h($it['product_name']) . ' × ' . (int)$it['qty'] . '</td>'
            . '<td style="padding:6px 0;border-bottom:1px solid #e5e7eb;text-align:right">' . ((float)$it['subtotal'] > 0 ? $h(money((float)$it['subtotal'])) : '-') . '</td></tr>';
    }
    $total = orderDisplayTotal($o);
    $body = '<p>ສະບາຍດີ ' . $h($m['name']) . ', ພວກເຮົາໄດ້ຮັບອໍເດີ <b>' . $h($orderNo) . '</b> ຂອງທ່ານແລ້ວ.</p>'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin:10px 0">' . $rows
        . '<tr><td style="padding:8px 0;font-weight:bold">ຍອດລວມ</td><td style="padding:8px 0;text-align:right;font-weight:bold;color:#dc2626">' . ($total > 0 ? $h(money($total)) : '-') . '</td></tr></table>'
        . (empty($o['slip_img'])
            ? '<p style="padding:10px 12px;background:#fffbeb;border-radius:6px;color:#92400e">ກະລຸນາໂອນເງິນ ແລະ ແນບສະລິບໃນໜ້າອໍເດີ ເພື່ອໃຫ້ທີມງານຢືນຢັນ ແລະ ຈັດສົ່ງໄວ.</p>'
            : '<p>ທີມງານກຳລັງກວດສະລິບ — ຈະແຈ້ງທາງອີເມວນີ້ເມື່ອອໍເດີມີການອັບເດດ.</p>');
    mailQueue($pdo, $m['email'], 'ໄດ້ຮັບອໍເດີ ' . $orderNo . ' ແລ້ວ — WlaoDev',
        mailTemplate('ໄດ້ຮັບອໍເດີແລ້ວ', $body, 'ຕິດຕາມອໍເດີ', mailSiteUrl('pages/my_order?no=' . rawurlencode($orderNo))), 'order_new', (int)$m['id']);
    register_shutdown_function('mailKick');
}
