<?php
/**
 * ສ່ວນ: ຟັງຊັນຊ່ວຍ: ສິນຄ້າໝົດອາຍຸ
 * ຄ່າຄົງທີ່ ແລະ ຟັງຊັນຂອງສິນຄ້າໝົດອາຍຸ: ຈຳນວນວັນແຈ້ງເຕືອນ, ຂໍ້ຄວາມເວລາທີ່ເຫຼືອ (ປີ/ເດືອນ/ວັນ),
 * ແປງວັນທີຈາກຟອມ ແລະ ລາຍການສິນຄ້າໃກ້ໝົດອາຍຸ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// ແຈ້ງເຕືອນສິນຄ້າເມື່ອເຫຼືອ 5 ມື້ ຫຼື ໜ້ອຍກວ່າກ່ອນໝົດອາຍຸ — ກຳນົດຕາຍຕົວໃນໂຄດ, ບໍ່ມີຊ່ອງໃຫ້ຕັ້ງຄ່າ
if (!defined('EXPIRY_ALERT_DAYS')) define('EXPIRY_ALERT_DAYS', 5);
// ພິມບິນຕອນຂາຍ: ບິນຫຼັກອອກສະເພາະເຄື່ອງພິມບິນຫຼັກ, ບິນຍ່ອຍແຍກຕາມໝວດສິນຄ້າອອກເຄື່ອງພິມທີ່ຕັ້ງໄວ້ໃນໝວດນັ້ນ
// (ໝວດທີ່ຕັ້ງເປັນເຄື່ອງພິມບິນຫຼັກ ຈະບໍ່ອອກບິນຍ່ອຍຊ້ຳ). ປ່ຽນເປັນ false ເພື່ອປິດບິນຍ່ອຍທັງໝົດ.
if (!defined('POS_PRINT_KITCHEN_TICKETS')) define('POS_PRINT_KITCHEN_TICKETS', true);
if (!function_exists("getExpiryAlertDays")) {
    function getExpiryAlertDays($pdo) {
        return EXPIRY_ALERT_DAYS;
    }
}
// ເວລາທີ່ເຫຼືອກ່ອນໝົດອາຍຸ ເປັນ ປີ/ເດືອນ/ວັນ ຕາມປະຕິທິນແທ້ ("1 ເດືອນ 1 ວັນ"), ຫຼື "ກາຍມາ ..." ຖ້າໝົດອາຍຸແລ້ວ,
// "ມື້ນີ້" ຖ້າໝົດອາຍຸມື້ນີ້. ໃຊ້ຢູ່ໜ້າ ຈັດການສິນຄ້າໝົດອາຍຸ ແລະ ລາຍງານສິນຄ້າໝົດອາຍຸ.
if (!function_exists("formatExpiryRemaining")) {
    function formatExpiryRemaining($expiryDate) {
        try {
            $today = new DateTime('today');
            $exp   = new DateTime(substr((string)$expiryDate, 0, 10));
        } catch (Throwable $e) {
            return '-';
        }
        $diff = $today->diff($exp);
        if ($diff->days === 0) return t('expiry.days_today', 'ມື້ນີ້');
        $parts = [];
        if ($diff->y > 0) $parts[] = sprintf(t('expiry.dur_year', '%s ປີ'), $diff->y);
        if ($diff->m > 0) $parts[] = sprintf(t('expiry.dur_month', '%s ເດືອນ'), $diff->m);
        if ($diff->d > 0) $parts[] = sprintf(t('expiry.dur_day', '%s ວັນ'), $diff->d);
        $text = implode(' ', $parts);
        return $diff->invert ? sprintf(t('expiry.overdue_by', 'ກາຍມາ %s'), $text) : $text;
    }
}
// Same text as HTML with every piece ("1 ເດືອນ", "9 ວັນ", "ກາຍມາ") in its own <span>, so the
// dashboard's instant language switch can translate each piece — it matches one phrase per text node.
if (!function_exists("formatExpiryRemainingHtml")) {
    function formatExpiryRemainingHtml($expiryDate) {
        try {
            $today = new DateTime('today');
            $exp   = new DateTime(substr((string)$expiryDate, 0, 10));
        } catch (Throwable $e) {
            return '-';
        }
        $diff = $today->diff($exp);
        $esc = function ($s) { return '<span>' . htmlspecialchars($s) . '</span>'; };
        if ($diff->days === 0) return $esc(t('expiry.days_today', 'ມື້ນີ້'));
        $parts = [];
        if ($diff->y > 0) $parts[] = $esc(sprintf(t('expiry.dur_year', '%s ປີ'), $diff->y));
        if ($diff->m > 0) $parts[] = $esc(sprintf(t('expiry.dur_month', '%s ເດືອນ'), $diff->m));
        if ($diff->d > 0) $parts[] = $esc(sprintf(t('expiry.dur_day', '%s ວັນ'), $diff->d));
        $html = implode(' ', $parts);
        if (!$diff->invert) return $html;
        // "ກາຍມາ %s" / "%s overdue": keep the language's word order, the word itself in its own span
        $pattern = t('expiry.overdue_by', 'ກາຍມາ %s');
        $pos = strpos($pattern, '%s');
        $before = trim(substr($pattern, 0, $pos === false ? 0 : $pos));
        $after  = trim($pos === false ? '' : substr($pattern, $pos + 2));
        return ($before !== '' ? $esc($before) . ' ' : '') . $html . ($after !== '' ? ' ' . $esc($after) : '');
    }
}
// ແປງວັນໝົດອາຍຸທີ່ຮັບມາຈາກຟອມ (Y-m-d ຫຼື d/m/Y) ເປັນ Y-m-d; ວ່າງ ຫຼື ບໍ່ຖືກຕ້ອງ → null (= ບໍ່ຕິດຕາມ)
if (!function_exists("normalizeExpiryDate")) {
    function normalizeExpiryDate($value) {
        $value = trim((string)$value);
        if ($value === '') return null;
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $fmt) {
            $dt = DateTime::createFromFormat('!' . $fmt, $value);
            if ($dt && $dt->format($fmt) === $value) {
                $y = (int)$dt->format('Y');
                return ($y >= 2000 && $y <= 2100) ? $dt->format('Y-m-d') : null;
            }
        }
        return null;
    }
}
if (!function_exists("getExpiryAlerts")) {
    function getExpiryAlerts($pdo, $store_id = null) {
        if (!$pdo) return [];
        $days = function_exists("getExpiryAlertDays") ? getExpiryAlertDays($pdo) : 5;
        try {
            $sql = "SELECT p.*, s.store_name, s.is_main, DATEDIFF(p.expiry_date, CURDATE()) as days_left FROM st_products p JOIN st_tbstore s ON p.store_id = s.store_id WHERE p.expiry_date IS NOT NULL AND DATEDIFF(p.expiry_date, CURDATE()) <= ? AND s.status = \"active\"";
            $params = [$days];
            if ($store_id !== null && intval($store_id) > 0) {
                $sql .= " AND p.store_id = ?";
                $params[] = intval($store_id);
            }
            $sql .= " ORDER BY p.expiry_date ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}

