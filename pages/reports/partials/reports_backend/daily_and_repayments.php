<?php
/**
 * ສ່ວນ: ຕົວເລກລາຍວັນ ແລະ ການຊຳລະໜີ້
 * ຕົວເລກ 10 ບັດຂອງລາຍງານປະຈຳວັນ ແລະ ຍອດຊຳລະໜີ້ຕໍ່ບິນ (ເງິນສົດ/ໂອນ)
 * ທີ່ຈະບວກໃສ່ຖັນເງິນຂອງບິນຕິດໜີ້.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/partials/reports_backend.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

if ($type === 'daily') {
    try {
        $cashSql = $hasCashCol ? "WHEN s.cash_received > 0 THEN s.cash_received" : "";
        $qrSql   = $hasQrCol   ? "WHEN s.qr_received > 0 THEN s.qr_received" : "";

        $stmtDaily = $pdo->prepare("
            SELECT 
                COUNT(s.Id) AS total_bills,
                COALESCE(SUM(s.sale_qty), 0) AS total_items_qty,
                COALESCE(SUM(s.sale_amount), 0) AS total_gross_sales,
                COALESCE(SUM(s.sale_discount_bill), 0) AS total_bill_discounts,
                COALESCE(SUM(s.sale_barlance), 0) AS total_net_sales,
                COALESCE(SUM(CASE 
                    {$cashSql}
                    WHEN s.type_pay LIKE '%ເງິນສົດ%' AND (s.type_pay NOT LIKE '%ໂອນ%' AND s.type_pay NOT LIKE '%QR%') THEN s.sale_barlance
                    WHEN s.type_pay LIKE '%ເງິນສົດ%' THEN GREATEST(0, s.sale_pay - s.sale_return)
                    ELSE 0 
                END), 0) AS total_cash_received,
                COALESCE(SUM(CASE 
                    {$qrSql}
                    WHEN (s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%') AND s.type_pay NOT LIKE '%ເງິນສົດ%' THEN s.sale_barlance
                    WHEN s.sale_transfer > 0 THEN s.sale_transfer
                    ELSE 0 
                END), 0) AS total_qr_received,
                COALESCE(SUM(GREATEST(0, (s.sale_pay - s.sale_return) - s.sale_barlance)), 0) AS total_tips_sum
            FROM st_tbsale_save s
            WHERE {$whereClause}
        ");
        $stmtDaily->execute($params);
        $dailyStats = $stmtDaily->fetch(PDO::FETCH_ASSOC);

        $hasItemDiscCol = false;
        try {
            $colsD = $pdo->query("SHOW COLUMNS FROM st_tbsale_save_detail")->fetchAll(PDO::FETCH_COLUMN);
            $hasItemDiscCol = in_array('save_discount_item', $colsD) ? 'save_discount_item' : (in_array('save_discount', $colsD) ? 'save_discount' : '');
        } catch (Exception $e) {}

        if ($hasItemDiscCol) {
            $stmtItemDisc = $pdo->prepare("
                SELECT COALESCE(SUM(d.{$hasItemDiscCol}), 0) AS total_item_disc
                FROM st_tbsale_save_detail d
                INNER JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
                WHERE {$whereClause}
            ");
            $stmtItemDisc->execute($params);
            $itemDiscRes = $stmtItemDisc->fetch(PDO::FETCH_ASSOC);
            $daily_item_discounts = (float)($itemDiscRes['total_item_disc'] ?? 0);
        } else {
            $daily_item_discounts = 0;
        }

        $daily_bills_count     = (int)($dailyStats['total_bills'] ?? 0);
        $daily_items_qty       = (int)($dailyStats['total_items_qty'] ?? 0);
        $daily_gross_sales     = (float)($dailyStats['total_gross_sales'] ?? 0);
        $daily_bill_discounts  = (float)($dailyStats['total_bill_discounts'] ?? 0);
        $daily_total_discounts = $daily_item_discounts + $daily_bill_discounts;
        $daily_net_sales       = (float)($dailyStats['total_net_sales'] ?? 0);
        $daily_cash_payments   = (float)($dailyStats['total_cash_received'] ?? 0);
        $daily_qr_payments     = (float)($dailyStats['total_qr_received'] ?? 0);
        $daily_tips_sum        = (float)($dailyStats['total_tips_sum'] ?? 0);

        // Promotion & Free Gift Statistics
        $daily_promo_discounts = $daily_item_discounts;
        $daily_promo_gifts_count = 0;
        try {
            $stmtGifts = $pdo->prepare("
                SELECT COALESCE(SUM(d.save_qty), 0) AS total_gifts
                FROM st_tbsale_save_detail d
                INNER JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
                WHERE {$whereClause} AND (d.save_name LIKE '%(ແຖມ)%' OR d.save_name LIKE '%🎁%' OR d.save_price = 0)
            ");
            $stmtGifts->execute($params);
            $daily_promo_gifts_count = (int)($stmtGifts->fetchColumn() ?: 0);
        } catch (Exception $e) {}
    } catch (Exception $e) {}
}

// ໂມດູນລູກຄ້າຕິດໜີ້ ຖືກລຶບອອກແລ້ວ — ບໍ່ມີການຊຳລະໜີ້ຍ້ອນຫຼັງ ($debtRepaidByBill ຫວ່າງ,
// sales_rows.php ຍັງອ່ານຕົວແປນີ້ ຈຶ່ງເກັບໄວ້).
$debtRepaidByBill = [];

