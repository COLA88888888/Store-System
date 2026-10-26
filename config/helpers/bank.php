<?php
/**
 * ສ່ວນ: ຟັງຊັນຊ່ວຍ: ທະນາຄານ
 * resolveBankLogo / resolveBankQr (ຫາໄຟລ໌ໂລໂກ້ ແລະ QR ຂອງທະນາຄານ) ແລະ getBankTransferTotals
 * (ຍອດເງິນໂອນແຍກຕາມບັນຊີທະນາຄານ).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// Global Helper Functions for Bank Assets
if (!function_exists('resolveBankLogo')) {
    function resolveBankLogo($logoFile, $bankCode = '') {
        if (empty($logoFile) || trim($logoFile) === '') {
            return '';
        }
        $filename = basename(trim($logoFile));
        $root = dirname(dirname(__DIR__));
        $htdocsRoot = 'D:/xampp/htdocs/MiniPos';
        if (file_exists($root . '/assets/img/banks/' . $filename) || file_exists($htdocsRoot . '/assets/img/banks/' . $filename)) {
            return '../../assets/img/banks/' . $filename;
        }
        return '../../assets/img/banks/' . $filename;
    }
}

if (!function_exists('resolveBankQr')) {
    function resolveBankQr($qrFile, $bankCode = '') {
        if (empty($qrFile) || trim($qrFile) === '') {
            return '';
        }
        $filename = basename(trim($qrFile));
        $root = dirname(dirname(__DIR__));
        $htdocsRoot = 'D:/xampp/htdocs/MiniPos';
        if (file_exists($root . '/assets/img/qr/' . $filename) || file_exists($htdocsRoot . '/assets/img/qr/' . $filename)) {
            return '../../assets/img/qr/' . $filename;
        }
        // File gone: no QR at all — never a broken path that ends up on a placeholder image
        return '';
    }
}

// Transfer received per bank account between two dates — [bank_id => ['total_tx', 'total_received']].
// The single source for both the Bank page and the daily report's per-bank breakdown, so the two
// always show the same numbers. A bill counts toward the account its bank_account_id points at;
// a bill with no account, or whose account was since deleted (bank removed and re-added), is
// matched to the current account by bank name — so old BCEL bills still add up under BCEL.
// $storeId 0 = every branch. $shiftId > 0 = ສະເພາະບິນຂອງກະນັ້ນ (ບັອກເລືອກກະ ໜ້າລາຍງານປະຈຳວັນ) —
// ບິນເກົ່າໃນ st_sales ບໍ່ມີກະ ຈຶ່ງບໍ່ນັບ.
if (!function_exists('getBankTransferTotals')) {
    function getBankTransferTotals($pdo, $startDate, $endDate, $storeId = 0, $shiftId = 0) {
        $saveStore = '';
        $salesStore = '';
        $storeId = intval($storeId);
        $shiftId = intval($shiftId);
        if ($storeId > 0) {
            try {
                $c = $pdo->query("SHOW COLUMNS FROM st_tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
                $col = in_array('store_id', $c) ? 'store_id' : (in_array('branch_id', $c) ? 'branch_id' : null);
                if ($col) $saveStore = " AND {$col} = {$storeId}";
                $c = $pdo->query("SHOW COLUMNS FROM st_sales")->fetchAll(PDO::FETCH_COLUMN);
                $col = in_array('store_id', $c) ? 'store_id' : (in_array('branch_id', $c) ? 'branch_id' : null);
                if ($col) $salesStore = " AND {$col} = {$storeId}";
            } catch (Throwable $e) {}
        }
        if ($shiftId > 0) {
            $saveStore .= " AND shift_session_id IN (SELECT id FROM st_shift_sessions WHERE shift_id = {$shiftId})";
            $salesStore .= " AND 1 = 0";
        }
        $sql = "
            SELECT b.id AS bank_acc_id, COUNT(s.sale_id) AS total_tx, COALESCE(SUM(s.transfer_net_amount), 0) AS total_received
            FROM st_bank_accounts b
            LEFT JOIN (
                SELECT Id AS sale_id,
                    CASE
                        WHEN sale_transfer > 0 THEN sale_transfer
                        WHEN (type_pay LIKE '%ໂອນ%' OR type_pay LIKE '%QR%' OR bank_account_id > 0) AND type_pay NOT LIKE '%ເງິນສົດ%' THEN sale_barlance
                        ELSE 0
                    END AS transfer_net_amount,
                    bank_account_id, bank_name, sale_date AS created_at
                FROM st_tbsale_save
                WHERE (sale_status IS NULL OR sale_status != 'CANCEL'){$saveStore}
                UNION ALL
                SELECT sale_id,
                    CASE
                        WHEN (payment_type LIKE '%ໂອນ%' OR payment_type LIKE '%QR%' OR bank_account_id > 0) AND payment_type NOT LIKE '%ເງິນສົດ%' THEN total_amount
                        ELSE 0
                    END AS transfer_net_amount,
                    bank_account_id, bank_name, DATE(created_at) AS created_at
                FROM st_sales
                WHERE (status IS NULL OR status != 'CANCEL')
                  AND invoice_number NOT IN (SELECT sale_save_bill FROM st_tbsale_save WHERE sale_save_bill IS NOT NULL){$salesStore}
            ) s ON (
                (
                    (s.bank_account_id > 0 AND s.bank_account_id = b.id)
                    OR (
                        (s.bank_account_id IS NULL OR s.bank_account_id = 0
                         OR NOT EXISTS (SELECT 1 FROM st_bank_accounts b2 WHERE b2.id = s.bank_account_id))
                        AND (
                            (s.bank_name IS NOT NULL AND s.bank_name != '' AND (
                                LOWER(s.bank_name) = LOWER(b.bank_name)
                                OR LOWER(s.bank_name) = LOWER(b.bank_code)
                                OR LOWER(s.bank_name) LIKE CONCAT('%', LOWER(b.bank_name), '%')
                                OR LOWER(s.bank_name) LIKE CONCAT('%', LOWER(b.bank_code), '%')
                                OR LOWER(b.bank_name) LIKE CONCAT('%', LOWER(s.bank_name), '%')
                                OR LOWER(b.bank_code) LIKE CONCAT('%', LOWER(s.bank_name), '%')
                            ))
                            OR (
                                (s.bank_name IS NULL OR s.bank_name = '' OR LOWER(s.bank_name) LIKE '%bcel%' OR LOWER(s.bank_name) LIKE '%onepay%')
                                AND (LOWER(b.bank_code) = 'bcel' OR LOWER(b.bank_name) LIKE '%bcel%')
                            )
                        )
                    )
                )
                AND s.transfer_net_amount > 0
                AND s.created_at BETWEEN ? AND ?
            )
            GROUP BY b.id
        ";
        $out = [];
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$startDate, $endDate]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $out[intval($r['bank_acc_id'])] = ['total_tx' => intval($r['total_tx']), 'total_received' => floatval($r['total_received'])];
            }
        } catch (Throwable $e) {}
        return $out;
    }
}

