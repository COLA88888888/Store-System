<?php
/**
 * ຟັງຊັນຊ່ວຍ: ກະເຮັດວຽກ — ເວລາປິດກະຕາມຕາຕະລາງ, ການໃຊ້ງານຂອງພະນັກງານ ແລະ ປິດກະອໍໂຕ້.
 * ຖືກ require ຈາກ config/db.php (ທຸກ request).
 *
 * ກົດ:
 *  - ຍັງບໍ່ຮອດໂມງປິດກະ → ພະນັກງານປິດບໍ່ໄດ້ (api/shifts_backend.php close_shift ໃຊ້ shiftScheduledEnd)
 *  - ຮອດໂມງປິດແລ້ວ ແຕ່ພະນັກງານຍັງໃຊ້ງານຢູ່ → ບໍ່ປິດອໍໂຕ້, ໃຫ້ພະນັກງານປິດເອງ
 *  - ຮອດໂມງປິດແລ້ວ ແລະ ບໍ່ມີໃຜໃຊ້ງານໜ້າຂາຍ SHIFT_IDLE_MINUTES ນາທີ (ເປີດປະໄວ້ / ອອກໄປແລ້ວ) → ປິດອໍໂຕ້
 *  - "ໃຊ້ງານ" = st_shift_sessions.last_active_at ທີ່ໜ້າ POS ສົ່ງມາ (heartbeat) ເມື່ອມີການແຕະ/ພິມ/ຄລິກ
 *    (pages/pos/partials/js/pos_shift_js.php)
 */

if (!defined('SHIFT_IDLE_MINUTES')) {
    define('SHIFT_IDLE_MINUTES', 15);   // ເທົ່າກັບເວລາອອກຈາກລະບົບອໍໂຕ້ເມື່ອບໍ່ໃຊ້ງານ (config/bootstrap/session_auth.php)
}

if (!function_exists('shiftScheduledEnd')) {
    // ເວລາປິດກະຕາມຕາຕະລາງ (Y-m-d H:i:s) ຂອງ session ທີ່ເປີດເມື່ອ $openTime.
    // ກະປົກກະຕິ (07:00-15:00): ມື້ທີ່ເປີດ ເວລາ end. ກະຂ້າມຄືນ (22:00-06:00): ຖ້າເວລາ end ຂອງມື້ນັ້ນຜ່ານໄປແລ້ວ → ມື້ຖັດໄປ.
    function shiftScheduledEnd($openTime, $startTime, $endTime) {
        $openTs = strtotime($openTime);
        $endTs  = strtotime(date('Y-m-d', $openTs) . ' ' . $endTime);
        if ($endTime <= $startTime && $endTs <= $openTs) {
            $endTs = strtotime('+1 day', $endTs);
        }
        return date('Y-m-d H:i:s', $endTs);
    }
}

if (!function_exists('shiftSessionEnd')) {
    // ເວລາປິດກະຂອງ session (ອ່ານເວລາຈາກ st_shifts) — ກະຖືກລຶບໄປແລ້ວ → null (ບໍ່ມີກຳນົດ)
    function shiftSessionEnd($pdo, array $session) {
        $st = $pdo->prepare("SELECT start_time, end_time FROM st_shifts WHERE id = ? LIMIT 1");
        $st->execute([(int)$session['shift_id']]);
        $shift = $st->fetch(PDO::FETCH_ASSOC);
        return $shift ? shiftScheduledEnd($session['open_time'], $shift['start_time'], $shift['end_time']) : null;
    }
}

if (!function_exists('shiftTouchActivity')) {
    // ພະນັກງານກຳລັງໃຊ້ງານໜ້າຂາຍ → ບັນທຶກເວລາໃສ່ກະທີ່ເປີດຢູ່ຂອງສາຂາ (ກັນບໍ່ໃຫ້ປິດອໍໂຕ້)
    function shiftTouchActivity($pdo, $storeId) {
        try {
            $pdo->prepare("UPDATE st_shift_sessions SET last_active_at = NOW() WHERE store_id = ? AND status = 'open'")
                ->execute([(int)$storeId]);
        } catch (Throwable $e) {}
    }
}

if (!function_exists('shiftSalesTotals')) {
    // ຍອດຂາຍຂອງກະ ຄິດສົດຈາກບິນ (ບິນ SUCCESS ຂອງກະ + ບິນບໍ່ມີກະໃນຊ່ວງເວລາກະ) — ກົດດຽວໃຊ້ທຸກບ່ອນ
    // (ປິດກະ, ສະຫຼຸບກ່ອນປິດ, ປະຫວັດກະ, ໃບສະຫຼຸບ). ຕໍ່ບິນ:
    //   ໂອນ  = qr_received ແຕ່ບໍ່ເກີນຍອດບິນ (ບໍ່ມີ qr ແຕ່ເປັນບິນໂອນ → ທັງບິນ)
    //   ສົດ   = ຍອດບິນ − ໂອນ  (ບໍ່ໃຊ້ cash_received ກົງໆ — ມັນແມ່ນເງິນທີ່ລູກຄ້າຍື່ນ ລວມເງິນທອນ)
    //   ຕິດໜີ້ = ຍອດບິນ − ມັດຈຳສົດ − ໂອນ
    // $endTime = ເວລາປິດກະ (ກະທີ່ຍັງເປີດ → ຕອນນີ້). ບິນທີ່ຖືກຍົກເລີກພາຍຫຼັງ ບໍ່ນັບ (ເພາະຄິດສົດ).
    function shiftSalesTotals($pdo, array $session, $endTime = null) {
        $endTime = $endTime ?: (!empty($session['close_time']) ? $session['close_time'] : date('Y-m-d H:i:s'));
        $st = $pdo->prepare("
            SELECT COUNT(*) AS bills, COALESCE(SUM(total), 0) AS total, COALESCE(SUM(trf), 0) AS transfer,
                   COALESCE(SUM(CASE WHEN is_debt THEN LEAST(GREATEST(cash_in, 0), total - trf) ELSE total - trf END), 0) AS cash,
                   COALESCE(SUM(CASE WHEN is_debt THEN total - LEAST(GREATEST(cash_in, 0), total - trf) - trf ELSE 0 END), 0) AS debt
            FROM (
                SELECT sale_barlance AS total, cash_received AS cash_in, (type_pay LIKE '%ຕິດໜີ້%') AS is_debt,
                       CASE WHEN qr_received > 0 THEN LEAST(qr_received, sale_barlance)
                            WHEN type_pay LIKE '%ໂອນ%' OR type_pay LIKE '%QR%' THEN sale_barlance ELSE 0 END AS trf
                FROM st_tbsale_save
                WHERE (shift_session_id = :sid OR (store_id = :store_id AND shift_session_id IS NULL
                       AND CONCAT(sale_date, ' ', sale_time) >= :open_time AND CONCAT(sale_date, ' ', sale_time) <= :end_time))
                  AND sale_status = 'SUCCESS'
            ) b");
        $st->execute([':sid' => (int)$session['id'], ':store_id' => (int)$session['store_id'],
                      ':open_time' => $session['open_time'], ':end_time' => $endTime]);
        $s = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'bills'    => (int)($s['bills'] ?? 0),
            'cash'     => (float)($s['cash'] ?? 0),
            'transfer' => (float)($s['transfer'] ?? 0),
            'debt'     => (float)($s['debt'] ?? 0),
            'total'    => (float)($s['total'] ?? 0),
        ];
    }
}

if (!function_exists('shiftApplyLiveTotals')) {
    // ໃສ່ຍອດສົດ (shiftSalesTotals) ທັບຄ່າ system_* ທີ່ບັນທຶກໄວ້ຕອນປິດກະ ແລະ ຄິດສ່ວນຕ່າງເງິນສົດໃໝ່
    // (ເງິນນັບໄດ້ − ເງິນສົດທີ່ຖືກ). ຄ່າເກົ່າທີ່ບັນທຶກໄວ້ອາດຜິດ: ສູດເກົ່ານັບເງິນທອນເປັນຍອດຂາຍ, ບໍ່ຫັກບິນທີ່ຍົກເລີກພາຍຫຼັງ.
    function shiftApplyLiveTotals($pdo, array $session) {
        $t = shiftSalesTotals($pdo, $session);
        $session['system_total_bills']    = $t['bills'];
        $session['system_sales_cash']     = $t['cash'];
        $session['system_sales_transfer'] = $t['transfer'];
        $session['system_sales_debt']     = $t['debt'];
        $session['system_total_sales']    = $t['total'];
        if (array_key_exists('counted_sales_cash', $session)) {
            $session['difference_cash'] = $session['counted_sales_cash'] === null ? null
                : (float)$session['counted_sales_cash'] - $t['cash'];
        }
        return $session;
    }
}

if (!function_exists('shiftCloseSession')) {
    // ປິດ session: ສະຫຼຸບຍອດຂາຍຈາກລະບົບຊ່ວງ open_time → $closeTime ແລ້ວບັນທຶກ. ໃຊ້ທັງປິດເອງ ແລະ ປິດອໍໂຕ້.
    // $counted = null (ປິດອໍໂຕ້ — ບໍ່ມີຄົນນັບເງິນ) → ສ່ວນຕ່າງເປັນ null. ສົ່ງຄືນຍອດທີ່ສະຫຼຸບໄດ້.
    function shiftCloseSession($pdo, array $session, $closeTime, $closedById, $closedByName, $counted, $notes) {
        $sid = (int)$session['id'];
        $storeId = (int)$session['store_id'];
        $sum = shiftSalesTotals($pdo, $session, $closeTime);   // ກົດດຽວກັບລາຍງານ (ບໍ່ນັບເງິນທອນເປັນຍອດຂາຍ)
        $diff = $counted === null ? null : $counted - $sum['cash'];

        $ownTx = !$pdo->inTransaction();
        if ($ownTx) $pdo->beginTransaction();
        try {
            // AND status = 'open': ສອງຄຳຂໍພ້ອມກັນ (ປິດເອງ + ປິດອໍໂຕ້) ປິດໄດ້ຄັ້ງດຽວ
            $up = $pdo->prepare("
                UPDATE st_shift_sessions SET closed_by_user_id = ?, closed_by_name = ?, close_time = ?,
                       system_sales_cash = ?, system_sales_transfer = ?, system_sales_debt = ?, system_total_sales = ?, system_total_bills = ?,
                       counted_sales_cash = ?, difference_cash = ?, status = 'closed', notes = ?
                WHERE id = ? AND status = 'open'");
            $up->execute([$closedById, $closedByName, $closeTime, $sum['cash'], $sum['transfer'], $sum['debt'], $sum['total'], $sum['bills'],
                          $counted, $diff, $notes, $sid]);
            $closed = $up->rowCount() > 0;
            if ($closed) {
                $pdo->prepare("UPDATE st_tbsale_save SET shift_session_id = ?
                               WHERE store_id = ? AND shift_session_id IS NULL
                                 AND CONCAT(sale_date, ' ', sale_time) >= ? AND CONCAT(sale_date, ' ', sale_time) <= ?")
                    ->execute([$sid, $storeId, $session['open_time'], $closeTime]);
            }
            if ($ownTx) $pdo->commit();
        } catch (Throwable $e) {
            if ($ownTx && $pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        $sum['closed'] = $closed;
        $sum['diff'] = $diff;
        return $sum;
    }
}

if (!defined('SHIFT_LOGOUT_NOTE')) {
    // ໝາຍເຫດຂອງກະທີ່ປິດເພາະຜູ້ໃຊ້ກົດອອກຈາກລະບົບ — ແຈ້ງເຕືອນ (config/helpers/shift_alerts.php) ໃຊ້ແຍກເຫດຜົນການປິດ
    define('SHIFT_LOGOUT_NOTE', 'ປິດກະອັດຕະໂນມັດ: ອອກຈາກລະບົບ');
}

if (!function_exists('shiftCloseOnLogout')) {
    // ກົດອອກຈາກລະບົບ (auth/logout.php) → ປິດກະທີ່ເປີດຢູ່ຂອງສາຂາຜູ້ໃຊ້ ຖ້າຜູ້ໃຊ້ເປັນຄົນເປີດກະ ຫຼື ເປັນພະນັກງານປະຈຳກະນັ້ນ
    // (ຜູ້ຈັດການ/ຜູ້ບໍລິຫານທີ່ບໍ່ກ່ຽວກັບກະ ອອກຈາກລະບົບ ບໍ່ປິດກະຂອງພະນັກງານ). ບໍ່ມີຄົນນັບເງິນ → counted/ສ່ວນຕ່າງ = null.
    // ບໍ່ກວດໂມງປິດກະ / ບິນພັກໄວ້ ຄືການປິດເອງ: ອອກຈາກລະບົບແລ້ວ ກະກໍສິ້ນສຸດ. ສົ່ງຄືນ id ຂອງກະທີ່ປິດ ຫຼື 0.
    function shiftCloseOnLogout($pdo, $userId, $userName, $storeId) {
        $userId = (int)$userId;
        if ($userId <= 0 || !$pdo) return 0;
        try {
            $st = $pdo->prepare("SELECT ss.*, sh.staff_user_ids FROM st_shift_sessions ss
                                 LEFT JOIN st_shifts sh ON sh.id = ss.shift_id
                                 WHERE ss.store_id = ? AND ss.status = 'open' ORDER BY ss.id DESC LIMIT 1");
            $st->execute([(int)$storeId]);
            $session = $st->fetch(PDO::FETCH_ASSOC);
            if (!$session) return 0;
            $staffIds = array_map('intval', explode(',', (string)($session['staff_user_ids'] ?? '')));
            if ((int)$session['opened_by_user_id'] !== $userId && !in_array($userId, $staffIds, true)) return 0;

            $sum = shiftCloseSession($pdo, $session, date('Y-m-d H:i:s'), $userId, $userName, null, SHIFT_LOGOUT_NOTE);
            if (!$sum['closed']) return 0;
            if (function_exists('logActivity')) {
                logActivity($pdo, 'ປິດກະເຮັດວຽກ (ອອກຈາກລະບົບ)', "ກະ: {$session['shift_name']}, ຍອດຂາຍ: " . number_format($sum['total'])
                    . ", {$sum['bills']} ບິນ, ໂດຍ: {$userName}");
            }
            return (int)$session['id'];
        } catch (Throwable $e) {
            return 0;   // ປິດກະບໍ່ໄດ້ ບໍ່ຄວນຂັດການອອກຈາກລະບົບ
        }
    }
}

if (!function_exists('shiftAutoCloseStale')) {
    // ຍົກເລີກການປິດກະອໍໂຕ້ເມື່ອກາຍໂມງ:
    // ກາຍໂມງກະຢ່າ ປິດກາຍໂມງບໍ່ມີບັນຫາ ບໍ່ໃຫ້ເດັ້ງຟອມເປີດກະມາ ຖ້າແຕ່ພະນັກງານປິດເອງ
    function shiftAutoCloseStale($pdo) {
        return 0;
    }
}
