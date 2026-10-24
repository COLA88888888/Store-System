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

if (!function_exists('shiftCloseSession')) {
    // ປິດ session: ສະຫຼຸບຍອດຂາຍຈາກລະບົບຊ່ວງ open_time → $closeTime ແລ້ວບັນທຶກ. ໃຊ້ທັງປິດເອງ ແລະ ປິດອໍໂຕ້.
    // $counted = null (ປິດອໍໂຕ້ — ບໍ່ມີຄົນນັບເງິນ) → ສ່ວນຕ່າງເປັນ null. ສົ່ງຄືນຍອດທີ່ສະຫຼຸບໄດ້.
    function shiftCloseSession($pdo, array $session, $closeTime, $closedById, $closedByName, $counted, $notes) {
        $sid = (int)$session['id'];
        $storeId = (int)$session['store_id'];
        $st = $pdo->prepare("
            SELECT COUNT(*) AS total_bills,
                   COALESCE(SUM(CASE WHEN cash_received > 0 THEN cash_received WHEN type_pay = 'ເງິນສົດ' THEN sale_barlance ELSE 0 END), 0) AS cash_sales,
                   COALESCE(SUM(CASE WHEN qr_received > 0 THEN qr_received WHEN type_pay IN ('ໂອນເງິນ / QR', 'ໂອນຈ່າຍ') THEN sale_barlance ELSE 0 END), 0) AS transfer_sales,
                   COALESCE(SUM(CASE WHEN type_pay = 'ຕິດໜີ້' THEN sale_barlance ELSE 0 END), 0) AS debt_sales,
                   COALESCE(SUM(sale_barlance), 0) AS total_sales
            FROM st_tbsale_save
            WHERE (shift_session_id = :sid OR (store_id = :store_id AND CONCAT(sale_date, ' ', sale_time) >= :open_time AND CONCAT(sale_date, ' ', sale_time) <= :close_time))
              AND sale_status = 'SUCCESS'");
        $st->execute([':sid' => $sid, ':store_id' => $storeId, ':open_time' => $session['open_time'], ':close_time' => $closeTime]);
        $s = $st->fetch(PDO::FETCH_ASSOC);
        $sum = [
            'bills'    => (int)($s['total_bills'] ?? 0),
            'cash'     => (float)($s['cash_sales'] ?? 0),
            'transfer' => (float)($s['transfer_sales'] ?? 0),
            'debt'     => (float)($s['debt_sales'] ?? 0),
            'total'    => (float)($s['total_sales'] ?? 0),
        ];
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

if (!function_exists('shiftAutoCloseStale')) {
    // ປິດອໍໂຕ້ທຸກກະ (ທຸກສາຂາ) ທີ່ຮອດໂມງປິດແລ້ວ ແລະ ບໍ່ມີໃຜໃຊ້ງານໜ້າຂາຍ SHIFT_IDLE_MINUTES ນາທີ.
    // ເວລາປິດ = ຊ້າກວ່າລະຫວ່າງ ໂມງປິດກະ ແລະ ເວລາໃຊ້ງານຫຼ້າສຸດ. ສົ່ງຄືນຈຳນວນກະທີ່ປິດ.
    function shiftAutoCloseStale($pdo) {
        $closedCount = 0;
        try {
            $rows = $pdo->query("
                SELECT ss.*, sh.start_time AS sh_start, sh.end_time AS sh_end
                FROM st_shift_sessions ss
                JOIN st_shifts sh ON sh.id = ss.shift_id
                WHERE ss.status = 'open'")->fetchAll(PDO::FETCH_ASSOC);
            $now = time();
            foreach ($rows as $ss) {
                $endTs = strtotime(shiftScheduledEnd($ss['open_time'], $ss['sh_start'], $ss['sh_end']));
                $lastTs = strtotime($ss['last_active_at'] ?: $ss['open_time']);
                if ($now < $endTs || $now - $lastTs < SHIFT_IDLE_MINUTES * 60) continue;
                $closeTime = date('Y-m-d H:i:s', max($endTs, $lastTs));
                $res = shiftCloseSession($pdo, $ss, $closeTime, null, 'ລະບົບ (ປິດອໍໂຕ້)', null,
                    'ປິດອໍໂຕ້: ຮອດໂມງປິດກະແລ້ວ ແລະ ບໍ່ມີພະນັກງານໃຊ້ງານ ' . SHIFT_IDLE_MINUTES . ' ນາທີ');
                if ($res['closed']) {
                    $closedCount++;
                    try { logActivity($pdo, "ປິດກະອໍໂຕ້", "ກະ: {$ss['shift_name']} (ສາຂາ {$ss['store_id']}), ຍອດຂາຍ: " . number_format($res['total'])); } catch (Throwable $e) {}
                }
            }
        } catch (Throwable $e) {
            error_log('shiftAutoCloseStale: ' . $e->getMessage());
        }
        return $closedCount;
    }
}

// ກວດປິດກະອໍໂຕ້ ຢ່າງຫຼາຍນາທີລະເທື່ອ ຕໍ່ຜູ້ໃຊ້ທີ່ເຂົ້າລະບົບ — ບໍ່ມີໃຜເປີດໜ້າ POS ກໍຍັງປິດໄດ້ ເມື່ອມີຄົນໃຊ້ລະບົບໜ້າອື່ນ
if (isset($pdo) && $pdo instanceof PDO && !empty($_SESSION['user_id'])
    && (time() - (int)($_SESSION['shift_autoclose_at'] ?? 0)) >= 60) {
    $_SESSION['shift_autoclose_at'] = time();
    shiftAutoCloseStale($pdo);
}
