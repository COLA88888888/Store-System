<?php
/**
 * ສ່ວນ: ຟັງຊັນຊ່ວຍ: ເລກສະມາຊິກເວັບໄຊ (w_web_members.member_no) ສຳລັບສະແດງ — 1 → "WL-0001".
 * ຄືກັບ memberCode() ໃນ frontend/models/members.php. ໃຊ້ໃນ pages/web_members/, pages/web_orders/, pages/web_chats/.
 * ໄຟລ໌ນີ້ຖືກ require ຈາກ config/db.php.
 */
if (!function_exists('memberCode')) {
    function memberCode($no): string
    {
        return 'WL-' . str_pad((string)(int)$no, 4, '0', STR_PAD_LEFT);
    }
}

/**
 * ຕົວເລກແຈ້ງເຕືອນເມນູຂ້າງ (layouts/sidebar.php + api/web_badges.php): ອໍເດີເວັບໃໝ່, ສະລິບລໍຖ້າກວດ, ແຊັດທີ່ຍັງບໍ່ອ່ານ.
 * ນັບສະເພາະເມນູທີ່ຜູ້ໃຊ້ມີສິດ (ຄືກັບ permMenuAttr ໃນ sidebar). ຕາຕະລາງຍັງບໍ່ມີ → 0
 */
if (!function_exists('webBadgeCounts')) {
    function webBadgeCounts(PDO $pdo): array
    {
        $out = ['orders' => 0, 'slips' => 0, 'chats' => 0];
        try {
            if (hasPermission('sale') || hasPermission('customers')) {
                $r = $pdo->query("SELECT COUNT(*) n, SUM(slip_img IS NOT NULL AND slip_img <> '') s FROM w_web_orders WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC);
                $out['orders'] = (int)$r['n'];
                $out['slips'] = (int)$r['s'];
            }
            if (hasPermission('customers')) {
                $out['chats'] = (int)$pdo->query("SELECT COALESCE(SUM(unread_staff), 0) FROM w_web_chats WHERE status = 'open'")->fetchColumn();
            }
        } catch (Throwable $e) {
        }
        return $out;
    }
}
