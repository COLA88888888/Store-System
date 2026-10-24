<?php
/**
 * API Backend: ຈັດການກະເຮັດວຽກ (Work Shifts & Cash Drawer)
 * ຈັດການຕາຕະລາງກະ, ເປີດ-ປິດກະ, ກວດສອບໂມງເປີດກະ, ຄິດໄລ່ຍອດຂາຍ ແລະ ສ່ວນຕ່າງເງິນສົດ
 */

ob_start();
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

function respond($data) {
    if (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

if (empty($_SESSION['checked']) || empty($_SESSION['user_id'])) {
    respond(['success' => false, 'unauthenticated' => true, 'message' => 'ກະລຸນາເຂົ້າສູ່ລະບົບ']);
}

$userId = intval($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['fname'] ?? $_SESSION['username'] ?? 'User';
$userStatus = $_SESSION['status'] ?? '';
$isAdmin = ($userId === 1 || in_array($userStatus, ['ຜູ້ບໍລິຫານ', 'admin', 'Admin', 'super admin', 'Super Admin']));
$isManager = in_array($userStatus, ['ຜູ້ຈັດການ', 'manager', 'Manager']);
$isAdminOrManager = ($isAdmin || $isManager);
$isCashier = ($userStatus === 'ພະນັກງານ' && !$isAdminOrManager);
$activeStoreId = intval($_SESSION['store_id'] ?? 1);

$action = trim($_POST['action'] ?? $_GET['action'] ?? '');

// ທຸກຄຳຂໍ: ປິດອໍໂຕ້ກະທີ່ຮອດໂມງປິດ ແລະ ບໍ່ມີໃຜໃຊ້ງານ (config/helpers/shift_auto.php) — ສະຖານະທີ່ສົ່ງຄືນຈຶ່ງເປັນປັດຈຸບັນສະເໝີ
shiftAutoCloseStale($pdo);

// ພະນັກງານປະຈຳກະ: ສະແດງສະເພາະຕຳແໜ່ງ "ພະນັກງານ" ຂອງສາຂານີ້ (ຜູ້ບໍລິຫານ ແລະ ຜູ້ຈັດການບໍ່ໃຫ້ໂຊ)
function shiftStaffList($pdo, $idsCsv, $storeId) {
    $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string)$idsCsv)))));
    if (!$ids) return [];
    $in = implode(',', $ids);
    $st = $pdo->prepare("SELECT Id, COALESCE(NULLIF(TRIM(CONCAT(IFNULL(fname,''), ' ', IFNULL(lname,''))), ''), username) AS name
                         FROM st_tbuser 
                         WHERE Id IN ($in) 
                           AND store_id = ? 
                           AND status = 'ພະນັກງານ'
                           AND status NOT IN ('ຜູ້ບໍລິຫານ', 'ຜູ້ຈັດການ', 'admin', 'Admin', 'super admin', 'Super Admin')
                           AND Id != 1
                         ORDER BY name");
    $st->execute([$storeId]);
    return array_map(function ($r) { return ['id' => (int)$r['Id'], 'name' => $r['name']]; }, $st->fetchAll(PDO::FETCH_ASSOC));
}
function shiftStaffNames($pdo, $idsCsv, $storeId) {
    return implode(', ', array_column(shiftStaffList($pdo, $idsCsv, $storeId), 'name'));
}
// ບິນທີ່ພັກໄວ້ (ຍັງບໍ່ຊຳລະ) ຂອງສາຂາ — ຍັງມີ = ປິດກະບໍ່ໄດ້
function shiftHeldCount($pdo, $storeId) {
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM st_held_orders WHERE store_id = ? AND status = 'held'");
        $st->execute([(int)$storeId]);
        return (int)$st->fetchColumn();
    } catch (Throwable $e) { return 0; }
}
// ພະນັກງານ: ບໍ່ເຫັນສ່ວນຕ່າງເງິນສົດ (ສະເພາະຜູ້ຈັດການ/ຜູ້ບໍລິຫານ) ແລະ ເຫັນສະເພາະກະທີ່ຕົນເອງເປີດ ຫຼື ປິດ
function shiftIsOwn(array $s, $userId) {
    return (int)($s['opened_by_user_id'] ?? 0) === (int)$userId || (int)($s['closed_by_user_id'] ?? 0) === (int)$userId;
}
function shiftHideDiff(array $s) {
    $s['difference_cash'] = null;
    $s['diff_hidden'] = true;
    return $s;
}

try {
    switch ($action) {

        // ====================================================================
        // 0. Heartbeat ຈາກໜ້າ POS: ພະນັກງານກຳລັງໃຊ້ງານ (ແຕະ/ພິມ/ຄລິກ ພາຍໃນ 1-2 ນາທີ) → ບໍ່ປິດກະອໍໂຕ້
        // ====================================================================
        case 'heartbeat': {
            if (!empty($_POST['active'])) {
                shiftTouchActivity($pdo, $activeStoreId);
            }
            $hb = $pdo->prepare("SELECT id FROM st_shift_sessions WHERE store_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1");
            $hb->execute([$activeStoreId]);
            $hbId = (int)$hb->fetchColumn();
            respond(['success' => true, 'has_active_shift' => $hbId > 0, 'session_id' => $hbId]);
            break;
        }

        // ====================================================================
        // 1. ສະຖານະກະປະຈຸບັນຂອງຮ້ານ/ສາຂາ (Current Shift Status)
        // ====================================================================
        case 'get_current_status': {
            // ກວດສອບວ່າມີກະທີ່ກຳລັງເປີດຢູ່ຫຼືບໍ່
            $stmtActive = $pdo->prepare("
                SELECT * FROM st_shift_sessions 
                WHERE store_id = ? AND status = 'open' 
                ORDER BY id DESC LIMIT 1
            ");
            $stmtActive->execute([$activeStoreId]);
            $activeSession = $stmtActive->fetch(PDO::FETCH_ASSOC);

            // ດຶງລາຍການກະທັງໝົດທີ່ເປີດໃຊ້ງານ
            $stmtShifts = $pdo->prepare("
                SELECT * FROM st_shifts 
                WHERE store_id = ? AND status = 1 
                ORDER BY start_time ASC
            ");
            $stmtShifts->execute([$activeStoreId]);
            $shifts = $stmtShifts->fetchAll(PDO::FETCH_ASSOC);

            $nowTime = date('H:i:s');
            $nowDate = date('Y-m-d');
            $nowFull = date('Y-m-d H:i:s');

            if ($activeSession) {
                // ຄິດໄລ່ຍອດຂາຍສົດໆລະຫວ່າງທີ່ເປີດກະ
                $sessionId = intval($activeSession['id']);
                $openTime = $activeSession['open_time'];

                $stmtSales = $pdo->prepare("
                    SELECT 
                        COUNT(*) as total_bills,
                        COALESCE(SUM(CASE WHEN cash_received > 0 THEN cash_received WHEN type_pay = 'ເງິນສົດ' THEN sale_barlance ELSE 0 END), 0) as cash_sales,
                        COALESCE(SUM(CASE WHEN qr_received > 0 THEN qr_received WHEN type_pay IN ('ໂອນເງິນ / QR', 'ໂອນຈ່າຍ') THEN sale_barlance ELSE 0 END), 0) as transfer_sales,
                        COALESCE(SUM(CASE WHEN type_pay = 'ຕິດໜີ້' THEN sale_barlance ELSE 0 END), 0) as debt_sales,
                        COALESCE(SUM(sale_barlance), 0) as total_sales
                    FROM st_tbsale_save
                    WHERE (shift_session_id = :sid OR (store_id = :store_id AND CONCAT(sale_date, ' ', sale_time) >= :open_time))
                      AND sale_status = 'SUCCESS'
                ");
                $stmtSales->execute([
                    ':sid' => $sessionId,
                    ':store_id' => $activeStoreId,
                    ':open_time' => $openTime
                ]);
                $salesSummary = $stmtSales->fetch(PDO::FETCH_ASSOC);
                $scheduledEnd = shiftSessionEnd($pdo, $activeSession);

                respond([
                    'success' => true,
                    'has_active_shift' => true,
                    'is_cashier' => $isCashier,
                    'is_admin' => $isAdmin,
                    'current_time' => $nowFull,
                    'session' => [
                        'id' => $activeSession['id'],
                        'shift_id' => $activeSession['shift_id'],
                        'shift_name' => $activeSession['shift_name'],
                        'open_time' => $activeSession['open_time'],
                        'opened_by_user_id' => $activeSession['opened_by_user_id'],
                        'opened_by_name' => $activeSession['opened_by_name'],
                        'staff_names' => $activeSession['staff_names'] ?? '',
                        'initial_cash' => floatval($activeSession['initial_cash']),
                        // ໂມງປິດກະຕາມຕາຕະລາງ: ກ່ອນເວລານີ້ພະນັກງານປິດບໍ່ໄດ້ (close_shift)
                        'scheduled_end' => $scheduledEnd,
                        'can_close_now' => $scheduledEnd === null || time() >= strtotime($scheduledEnd),
                        'held_count' => shiftHeldCount($pdo, $activeStoreId),   // ບິນພັກ > 0 → ປິດກະບໍ່ໄດ້
                        'system_total_bills' => intval($salesSummary['total_bills'] ?? 0),
                        'system_sales_cash' => floatval($salesSummary['cash_sales'] ?? 0),
                        'system_sales_transfer' => floatval($salesSummary['transfer_sales'] ?? 0),
                        'system_sales_debt' => floatval($salesSummary['debt_sales'] ?? 0),
                        'system_total_sales' => floatval($salesSummary['total_sales'] ?? 0)
                    ]
                ]);
            }

            // ຖ້າບໍ່ມີກະທີ່ເປີດຢູ່: ກວດສອບກະທີ່ສາມາດເປີດໄດ້ ຫຼື ກະຖັດໄປ
            $canOpenAny = false;
            $nextShift = null;
            $shiftOptions = [];

            foreach ($shifts as $s) {
                $start = $s['start_time'];
                $end = $s['end_time'];
                $canOpen = false;

                if ($start <= $end) {
                    // ກະປົກກະຕິ (ເຊັ່ນ: 07:00 ຫາ 15:00)
                    if ($nowTime >= $start) {
                        $canOpen = true;
                    }
                } else {
                    // ກະຂ້າມຄືນ (ເຊັ່ນ: 22:00 ຫາ 06:00)
                    if ($nowTime >= $start || $nowTime <= $end) {
                        $canOpen = true;
                    }
                }

                if ($canOpen) {
                    $canOpenAny = true;
                }

                $shiftOptions[] = [
                    'id' => $s['id'],
                    'shift_name' => $s['shift_name'],
                    'start_time' => substr($s['start_time'], 0, 5),
                    'end_time' => substr($s['end_time'], 0, 5),
                    'can_open' => $canOpen,
                    'staff_names' => shiftStaffNames($pdo, $s['staff_user_ids'] ?? '', $activeStoreId)
                ];
            }

            // ຊອກຫາກະຖັດໄປທີ່ໃກ້ທີ່ສຸດຖ້າຍັງບໍ່ຮອດໂມງ
            if (!$canOpenAny && !empty($shifts)) {
                foreach ($shifts as $s) {
                    if ($s['start_time'] > $nowTime) {
                        $nextShift = $s;
                        break;
                    }
                }
                if (!$nextShift) {
                    $nextShift = $shifts[0]; // ກະທຳອິດຂອງມື້ອື່ນ
                }
            }

            respond([
                'success' => true,
                'has_active_shift' => false,
                // ສາຂານີ້ບໍ່ໄດ້ຕັ້ງກະໄວ້ເລີຍ → ໜ້າຂາຍເຊື່ອງປຸ່ມກະ ແລະ ບໍ່ບັງຄັບເປີດກະ
                'has_shifts' => !empty($shifts),
                'can_open_any' => $canOpenAny,
                'is_cashier' => $isCashier,
                'is_admin' => $isAdmin,
                'current_time' => $nowFull,
                'current_time_clock' => substr($nowTime, 0, 5),
                'shifts' => $shiftOptions,
                'next_shift' => $nextShift ? [
                    'id' => $nextShift['id'],
                    'shift_name' => $nextShift['shift_name'],
                    'start_time' => substr($nextShift['start_time'], 0, 5),
                    'end_time' => substr($nextShift['end_time'], 0, 5)
                ] : null
            ]);
            break;
        }

        // ====================================================================
        // 2. ເປີດກະເຮັດວຽກ (Open Shift)
        // ====================================================================
        case 'open_shift': {
            // ກວດສອບວ່າມີກະທີ່ເປີດຢູ່ແລ້ວຫຼືບໍ່
            $stmtCheck = $pdo->prepare("
                SELECT id, shift_name FROM st_shift_sessions 
                WHERE store_id = ? AND status = 'open' LIMIT 1
            ");
            $stmtCheck->execute([$activeStoreId]);
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                respond([
                    'success' => false,
                    'message' => "ມີກະ \"{$existing['shift_name']}\" ກຳລັງເປີດໃຊ້ງານຢູ່ແລ້ວ! ບໍ່ສາມາດເປີດກະຊ້ອນກັນໄດ້"
                ]);
            }

            $shiftId = intval($_POST['shift_id'] ?? 0);
            if ($shiftId <= 0) {
                respond(['success' => false, 'message' => 'ກະລຸນາເລືອກກະເຮັດວຽກ']);
            }

            // ດຶງຂໍ້ມູນກະ
            $stmtS = $pdo->prepare("SELECT * FROM st_shifts WHERE id = ? AND store_id = ? AND status = 1 LIMIT 1");
            $stmtS->execute([$shiftId, $activeStoreId]);
            $shift = $stmtS->fetch(PDO::FETCH_ASSOC);
            if (!$shift) {
                respond(['success' => false, 'message' => 'ບໍ່ພົບຂໍ້ມູນກະເຮັດວຽກ ຫຼື ກະຖືກປິດໃຊ້ງານແລ້ວ']);
            }

            // ກວດສອບໂມງເປີດກະ: ຖ້າຍັງບໍ່ຮອດໂມງ ຫ້າມເປີດ (ຕາມເງື່ອນໄຂຜູ້ໃຊ້)
            $nowTime = date('H:i:s');
            $start = $shift['start_time'];
            $end = $shift['end_time'];
            $canOpen = false;

            if ($start <= $end) {
                if ($nowTime >= $start) {
                    $canOpen = true;
                }
            } else {
                if ($nowTime >= $start || $nowTime <= $end) {
                    $canOpen = true;
                }
            }

            // ຖ້າເປັນພະນັກງານ ແລະ ຍັງບໍ່ຮອດໂມງເປີດກະ -> ບລັອກພ້ອມເຕືອນ
            if (!$canOpen && $isCashier) {
                $startFormatted = substr($start, 0, 5);
                $nowFormatted = substr($nowTime, 0, 5);
                respond([
                    'success' => false,
                    'too_early' => true,
                    'message' => "ຍັງບໍ່ຮອດໂມງເປີດກະ! ກະ \"{$shift['shift_name']}\" ເລີ່ມເວລາ {$startFormatted} (ເວລາປະຈຸບັນ: {$nowFormatted})"
                ]);
            }

            // ປ້ອນເງິນສົດໃນລິ້ນຊັກ: ຕ້ອງປ້ອນສະເໝີ (ບໍ່ມີເງິນໃຫ້ປ້ອນ 0) — ວ່າງ = ບໍ່ເປີດກະ
            $initialCashRaw = trim($_POST['initial_cash'] ?? '');
            if ($initialCashRaw === '' || !is_numeric(str_replace(',', '', $initialCashRaw))) {
                respond(['success' => false, 'message' => 'ກະລຸນາປ້ອນເງິນສົດໃນລິ້ນຊັກ (ຖ້າບໍ່ມີເງິນທອນ ໃຫ້ປ້ອນ 0)']);
            }
            $initialCash = floatval(str_replace(',', '', $initialCashRaw));
            if ($initialCash < 0) {
                $initialCash = 0;
            }

            $pdo->beginTransaction();

            // ພະນັກງານປະຈຳກະ: ບັນທຶກຊື່ໄວ້ກັບ session (ລາຍງານປິດກະບໍ່ປ່ຽນ ເຖິງແກ້ກະພາຍຫຼັງ)
            $staffNames = mb_substr(shiftStaffNames($pdo, $shift['staff_user_ids'] ?? '', $activeStoreId), 0, 500);
            $stmtInsert = $pdo->prepare("
                INSERT INTO st_shift_sessions
                (shift_id, shift_name, store_id, branch_id, opened_by_user_id, opened_by_name, staff_names, open_time, last_active_at, initial_cash, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?, 'open')
            ");
            $stmtInsert->execute([
                $shift['id'],
                $shift['shift_name'],
                $activeStoreId,
                $activeStoreId,
                $userId,
                $userName,
                $staffNames !== '' ? $staffNames : null,
                $initialCash
            ]);
            $newSessionId = $pdo->lastInsertId();

            $pdo->commit();

            logActivity($pdo, "ເປີດກະເຮັດວຽກ", "ກະ: {$shift['shift_name']}, ເງິນລິ້ນຊັກ: " . number_format($initialCash) . " ₭ ໂດຍ: {$userName}");

            respond([
                'success' => true,
                'message' => "ເປີດກະ \"{$shift['shift_name']}\" ສຳເລັດແລ້ວ",
                'session_id' => $newSessionId,
                'shift_name' => $shift['shift_name'],
                'initial_cash' => $initialCash
            ]);
            break;
        }

        // ====================================================================
        // 3. ດຶງສະຫຼຸບກະກ່ອນປິດ (Get Shift Summary Before Closing)
        // ====================================================================
        case 'get_shift_summary': {
            $sessionId = intval($_GET['session_id'] ?? $_POST['session_id'] ?? 0);
            if ($sessionId <= 0) {
                // ດຶງ active session
                $stmtA = $pdo->prepare("SELECT id FROM st_shift_sessions WHERE store_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1");
                $stmtA->execute([$activeStoreId]);
                $sessionId = intval($stmtA->fetchColumn() ?? 0);
            }

            if ($sessionId <= 0) {
                respond(['success' => false, 'message' => 'ບໍ່ພົບກະທີ່ກຳລັງເປີດຢູ່']);
            }

            $stmtS = $pdo->prepare("SELECT * FROM st_shift_sessions WHERE id = ? AND store_id = ? LIMIT 1");
            $stmtS->execute([$sessionId, $activeStoreId]);
            $session = $stmtS->fetch(PDO::FETCH_ASSOC);
            if (!$session) {
                respond(['success' => false, 'message' => 'ບໍ່ພົບຂໍ້ມູນກະນີ້']);
            }

            $openTime = $session['open_time'];
            $endTime = ($session['status'] === 'closed' && !empty($session['close_time'])) ? $session['close_time'] : date('Y-m-d H:i:s');

            // ຄິດໄລ່ຍອດຂາຍ
            $stmtSales = $pdo->prepare("
                SELECT 
                    COUNT(*) as total_bills,
                    COALESCE(SUM(CASE WHEN cash_received > 0 THEN cash_received WHEN type_pay = 'ເງິນສົດ' THEN sale_barlance ELSE 0 END), 0) as cash_sales,
                    COALESCE(SUM(CASE WHEN qr_received > 0 THEN qr_received WHEN type_pay IN ('ໂອນເງິນ / QR', 'ໂອນຈ່າຍ') THEN sale_barlance ELSE 0 END), 0) as transfer_sales,
                    COALESCE(SUM(CASE WHEN type_pay = 'ຕິດໜີ້' THEN sale_barlance ELSE 0 END), 0) as debt_sales,
                    COALESCE(SUM(sale_barlance), 0) as total_sales
                FROM st_tbsale_save
                WHERE (shift_session_id = :sid OR (store_id = :store_id AND CONCAT(sale_date, ' ', sale_time) >= :open_time AND CONCAT(sale_date, ' ', sale_time) <= :end_time))
                  AND sale_status = 'SUCCESS'
            ");
            $stmtSales->execute([
                ':sid' => $sessionId,
                ':store_id' => $activeStoreId,
                ':open_time' => $openTime,
                ':end_time' => $endTime
            ]);
            $salesSummary = $stmtSales->fetch(PDO::FETCH_ASSOC);

            // ດຶງລາຍຊື່ຜູ້ຂາຍທີ່ເຄື່ອນໄຫວໃນກະນີ້
            $stmtCashiers = $pdo->prepare("
                SELECT DISTINCT user_receive 
                FROM st_tbsale_save 
                WHERE (shift_session_id = :sid OR (store_id = :store_id AND CONCAT(sale_date, ' ', sale_time) >= :open_time AND CONCAT(sale_date, ' ', sale_time) <= :end_time))
                  AND sale_status = 'SUCCESS'
            ");
            $stmtCashiers->execute([
                ':sid' => $sessionId,
                ':store_id' => $activeStoreId,
                ':open_time' => $openTime,
                ':end_time' => $endTime
            ]);
            $cashiers = $stmtCashiers->fetchAll(PDO::FETCH_COLUMN);

            respond([
                'success' => true,
                'session' => [
                    'id' => $session['id'],
                    'shift_name' => $session['shift_name'],
                    'opened_by_name' => $session['opened_by_name'],
                    'staff_names' => $session['staff_names'] ?? '',
                    'open_time' => $session['open_time'],
                    'close_time' => $endTime,
                    'is_closed' => ($session['status'] === 'closed'),
                    'initial_cash' => floatval($session['initial_cash']),
                    'system_total_bills' => intval($salesSummary['total_bills'] ?? 0),
                    'system_sales_cash' => floatval($salesSummary['cash_sales'] ?? 0),
                    'system_sales_transfer' => floatval($salesSummary['transfer_sales'] ?? 0),
                    'system_sales_debt' => floatval($salesSummary['debt_sales'] ?? 0),
                    'system_total_sales' => floatval($salesSummary['total_sales'] ?? 0),
                    'counted_sales_cash' => $session['counted_sales_cash'] !== null ? floatval($session['counted_sales_cash']) : null,
                    // ພະນັກງານບໍ່ເຫັນສ່ວນຕ່າງ (ສະເພາະຜູ້ຈັດການ/ຜູ້ບໍລິຫານ)
                    'difference_cash' => ($isCashier || $session['difference_cash'] === null) ? null : floatval($session['difference_cash']),
                    'diff_hidden' => $isCashier,
                    'notes' => $session['notes'] ?? '',
                    'active_cashiers' => $cashiers
                ]
            ]);
            break;
        }

        // ====================================================================
        // 4. ປິດກະເຮັດວຽກ (Close Shift)
        // ====================================================================
        case 'close_shift': {
            $sessionId = intval($_POST['session_id'] ?? 0);
            if ($sessionId <= 0) {
                // ດຶງ active session
                $stmtA = $pdo->prepare("SELECT id FROM st_shift_sessions WHERE store_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1");
                $stmtA->execute([$activeStoreId]);
                $sessionId = intval($stmtA->fetchColumn() ?? 0);
            }

            if ($sessionId <= 0) {
                respond(['success' => false, 'message' => 'ບໍ່ພົບກະທີ່ກຳລັງເປີດຢູ່']);
            }

            $stmtS = $pdo->prepare("SELECT * FROM st_shift_sessions WHERE id = ? AND store_id = ? AND status = 'open' LIMIT 1");
            $stmtS->execute([$sessionId, $activeStoreId]);
            $session = $stmtS->fetch(PDO::FETCH_ASSOC);
            if (!$session) {
                respond(['success' => false, 'message' => 'ບໍ່ພົບກະທີ່ເປີດຢູ່ ຫຼື ກະນີ້ຖືກປິດໄປແລ້ວ']);
            }

            $openTime = $session['open_time'];
            $now = date('Y-m-d H:i:s');

            // ຍັງບໍ່ຮອດໂມງປິດກະ: ພະນັກງານປິດບໍ່ໄດ້; ຜູ້ບໍລິຫານ/ຜູ້ຈັດການ ປິດກ່ອນເວລາໄດ້ ເມື່ອຢືນຢັນ (force_early=1)
            $scheduledEnd = shiftSessionEnd($pdo, $session);
            if ($scheduledEnd !== null && time() < strtotime($scheduledEnd)) {
                $endClock = date('H:i', strtotime($scheduledEnd));
                if ($isCashier || empty($_POST['force_early'])) {
                    respond([
                        'success' => false,
                        'too_early_close' => true,
                        'can_force' => !$isCashier,
                        'scheduled_end' => $scheduledEnd,
                        'message' => "ຍັງບໍ່ທັນຮອດໂມງປິດກະ! ກະ \"{$session['shift_name']}\" ປິດເວລາ {$endClock} (ເວລາປະຈຸບັນ: " . date('H:i') . ")"
                    ]);
                }
            }

            // ຍັງມີບິນພັກໄວ້ → ຕ້ອງຊຳລະໃຫ້ໝົດກ່ອນຈຶ່ງປິດກະໄດ້ (ທຸກຕຳແໜ່ງ)
            $heldCount = shiftHeldCount($pdo, $activeStoreId);
            if ($heldCount > 0) {
                respond([
                    'success' => false,
                    'pending_held' => $heldCount,
                    'message' => "ຍັງມີບິນພັກໄວ້ {$heldCount} ບິນ — ກະລຸນາຊຳລະບິນທີ່ພັກໃຫ້ໝົດກ່ອນ ຈຶ່ງປິດກະໄດ້"
                ]);
            }

            // ປ້ອນຈຳນວນເງິນທີ່ນັບໄດ້ສະເພາະຂາຍ ບໍ່ລວມກັບເງິນໃນລິ້ນຊັກຕອນເປີດກະ
            // ຕ້ອງປ້ອນເງິນທີ່ນັບໄດ້ສະເໝີ (ບໍ່ມີໃຫ້ປ້ອນ 0) — ວ່າງ = ບໍ່ປິດກະ
            $countedRaw = str_replace(',', '', trim($_POST['counted_sales_cash'] ?? ''));
            if ($countedRaw === '' || !is_numeric($countedRaw)) {
                respond(['success' => false, 'message' => 'ກະລຸນາປ້ອນເງິນສົດທີ່ນັບໄດ້ຕົວຈິງ (ບໍ່ມີໃຫ້ປ້ອນ 0)']);
            }
            $countedSalesCash = floatval($countedRaw);
            if ($countedSalesCash < 0) {
                $countedSalesCash = 0;
            }
            $notes = trim($_POST['notes'] ?? '');

            // ສະຫຼຸບຍອດຂາຍ + ບັນທຶກປິດກະ (config/helpers/shift_auto.php — ໃຊ້ຮ່ວມກັບປິດອໍໂຕ້)
            $sum = shiftCloseSession($pdo, $session, $now, $userId, $userName, $countedSalesCash, $notes);
            if (!$sum['closed']) {
                respond(['success' => false, 'message' => 'ກະນີ້ຖືກປິດໄປແລ້ວ']);
            }
            $sysBills = $sum['bills'];
            $sysCash = $sum['cash'];
            $sysTransfer = $sum['transfer'];
            $sysDebt = $sum['debt'];
            $sysTotal = $sum['total'];
            $diffCash = $sum['diff'];

            logActivity($pdo, "ປິດກະເຮັດວຽກ", "ກະ: {$session['shift_name']}, ຂາຍເງິນສົດລະບົບ: " . number_format($sysCash) . ", ນັບໄດ້: " . number_format($countedSalesCash) . ", ສ່ວນຕ່າງ: " . number_format($diffCash) . " ໂດຍ: {$userName}");

            respond([
                'success' => true,
                'message' => 'ປິດກະເຮັດວຽກສຳເລັດແລ້ວ',
                'session' => [
                    'id' => $sessionId,
                    'shift_name' => $session['shift_name'],
                    'open_time' => $openTime,
                    'close_time' => $now,
                    'opened_by_name' => $session['opened_by_name'],
                    'closed_by_name' => $userName,
                    'staff_names' => $session['staff_names'] ?? '',
                    'initial_cash' => floatval($session['initial_cash']),
                    'system_sales_cash' => $sysCash,
                    'system_sales_transfer' => $sysTransfer,
                    'system_sales_debt' => $sysDebt,
                    'system_total_sales' => $sysTotal,
                    'system_total_bills' => $sysBills,
                    'counted_sales_cash' => $countedSalesCash,
                    'difference_cash' => $isCashier ? null : $diffCash,
                    'diff_hidden' => $isCashier,
                    'notes' => $notes
                ]
            ]);
            break;
        }

        // ====================================================================
        // 5. ລາຍການຕັ້ງຄ່າກະ (List Shift Definitions for Settings)
        // ====================================================================
        case 'list_shifts': {
            $stmt = $pdo->prepare("
                SELECT * FROM st_shifts 
                WHERE store_id = ? 
                ORDER BY start_time ASC
            ");
            $stmt->execute([$activeStoreId]);
            $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // ຈັດຮູບແບບເວລາໃຫ້ອ່ານງ່າຍ + ພະນັກງານປະຈຳກະ
            foreach ($shifts as &$s) {
                $s['start_time_fmt'] = substr($s['start_time'], 0, 5);
                $s['end_time_fmt'] = substr($s['end_time'], 0, 5);
                $s['staff'] = shiftStaffList($pdo, $s['staff_user_ids'] ?? '', $activeStoreId);
            }
            unset($s);

            respond([
                'success' => true,
                'shifts' => $shifts
            ]);
            break;
        }

        // ====================================================================
        // 6. ບັນທຶກ/ແກ້ໄຂກະ (Save / Update Shift Definition)
        // ====================================================================
        case 'save_shift': {
            if (!$isAdmin && !hasPermission('setup')) {
                respond(['success' => false, 'message' => 'ທ່ານບໍ່ມີສິດໃນການຈັດການຕັ້ງຄ່າກະ']);
            }

            $id = intval($_POST['id'] ?? 0);
            $shiftName = trim($_POST['shift_name'] ?? '');
            $startTime = trim($_POST['start_time'] ?? '');
            $endTime = trim($_POST['end_time'] ?? '');
            // ຟອມບໍ່ມີສະວິດ "ເປີດໃຊ້ງານກະນີ້" ແລ້ວ: ກະໃໝ່ = ເປີດໃຊ້ງານ (1); ແກ້ໄຂ = ຮັກສາສະຖານະເດີມ (null → COALESCE).
            // ຖ້າມີການສົ່ງ status ມາ (API ເກົ່າ) ຍັງໃຊ້ຄ່ານັ້ນ.
            $status = isset($_POST['status']) ? (!empty($_POST['status']) ? 1 : 0) : null;
            // ພະນັກງານປະຈຳກະ: ສະເພາະຜູ້ໃຊ້ຂອງສາຂານີ້
            $staffIn = $_POST['staff_user_ids'] ?? [];
            if (!is_array($staffIn)) $staffIn = explode(',', (string)$staffIn);
            $staffCsv = implode(',', array_column(shiftStaffList($pdo, implode(',', $staffIn), $activeStoreId), 'id'));

            if (empty($shiftName)) {
                respond(['success' => false, 'message' => 'ກະລຸນາປ້ອນຊື່ກະ']);
            }
            if (empty($startTime) || empty($endTime)) {
                respond(['success' => false, 'message' => 'ກະລຸນາເລືອກເວລາເລີ່ມ ແລະ ເວລາສິ້ນສຸດ']);
            }
            // ຕ້ອງມີພະນັກງານໃນກະຢ່າງໜ້ອຍ 1 ຄົນ (ພະນັກງານຂອງສາຂານີ້ທີ່ຖືກຕ້ອງ)
            if ($staffCsv === '') {
                respond(['success' => false, 'message' => 'ກະລຸນາຕິກເລືອກພະນັກງານໃນກະນີ້ຢ່າງໜ້ອຍ 1 ຄົນ']);
            }

            // Format HH:MM:00
            if (strlen($startTime) === 5) $startTime .= ':00';
            if (strlen($endTime) === 5) $endTime .= ':00';

            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE st_shifts 
                    SET shift_name = ?, start_time = ?, end_time = ?, staff_user_ids = ?, status = COALESCE(?, status)
                    WHERE id = ? AND store_id = ?
                ");
                $stmt->execute([$shiftName, $startTime, $endTime, $staffCsv !== '' ? $staffCsv : null, $status, $id, $activeStoreId]);
                logActivity($pdo, "ແກ້ໄຂກະເຮັດວຽກ", "ລະຫັດ: {$id}, ຊື່ກະ: {$shiftName}");
                respond(['success' => true, 'message' => 'ບັນທຶກການແກ້ໄຂກະສຳເລັດ']);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO st_shifts (shift_name, start_time, end_time, staff_user_ids, store_id, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$shiftName, $startTime, $endTime, $staffCsv !== '' ? $staffCsv : null, $activeStoreId, $status ?? 1]);
                $newId = $pdo->lastInsertId();
                logActivity($pdo, "ເພີ່ມກະເຮັດວຽກໃໝ່", "ຊື່ກະ: {$shiftName}, ເວລາ: {$startTime} - {$endTime}");
                respond(['success' => true, 'message' => 'ເພີ່ມກະເຮັດວຽກໃໝ່ສຳເລັດ', 'id' => $newId]);
            }
            break;
        }

        // ====================================================================
        // 7. ລຶບກະເຮັດວຽກ (Delete Shift)
        // ====================================================================
        case 'delete_shift': {
            if (!$isAdmin && !hasPermission('setup')) {
                respond(['success' => false, 'message' => 'ທ່ານບໍ່ມີສິດໃນການລຶບກະ']);
            }

            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                respond(['success' => false, 'message' => 'ລະຫັດກະບໍ່ຖືກຕ້ອງ']);
            }

            // ກວດສອບວ່າມີ session ທີ່ໃຊ້ກະນີ້ຢູ່ຫຼືບໍ່
            $stmtC = $pdo->prepare("SELECT COUNT(*) FROM st_shift_sessions WHERE shift_id = ?");
            $stmtC->execute([$id]);
            $countSessions = intval($stmtC->fetchColumn() ?? 0);

            if ($countSessions > 0) {
                // ຖ້າມີປະຫວັດແລ້ວ ໃຫ້ປິດໃຊ້ງານແທນການລຶບຖິ້ມ ເພື່ອຮັກສາປະຫວັດ
                $stmt = $pdo->prepare("UPDATE st_shifts SET status = 0 WHERE id = ? AND store_id = ?");
                $stmt->execute([$id, $activeStoreId]);
                respond(['success' => true, 'message' => 'ປິດການໃຊ້ງານກະນີ້ສຳເລັດ (ເນື່ອງຈາກມີປະຫວັດການເປີດ-ປິດກະແລ້ວ)']);
            } else {
                $stmt = $pdo->prepare("DELETE FROM st_shifts WHERE id = ? AND store_id = ?");
                $stmt->execute([$id, $activeStoreId]);
                respond(['success' => true, 'message' => 'ລຶບກະເຮັດວຽກສຳເລັດແລ້ວ']);
            }
            break;
        }

        // ====================================================================
        // 7b. ລາຍຊື່ພະນັກງານຂອງສາຂານີ້ (ສະເພາະຕຳແໜ່ງ ພະນັກງານ, ຜູ້ບໍລິຫານ ແລະ ຜູ້ຈັດການບໍ່ໃຫ້ໂຊ)
        // ====================================================================
        case 'list_staff': {
            $st = $pdo->prepare("SELECT Id, COALESCE(NULLIF(TRIM(CONCAT(IFNULL(fname,''), ' ', IFNULL(lname,''))), ''), username) AS name, status
                                 FROM st_tbuser 
                                 WHERE store_id = ? 
                                   AND status = 'ພະນັກງານ'
                                   AND status NOT IN ('ຜູ້ບໍລິຫານ', 'ຜູ້ຈັດການ', 'admin', 'Admin', 'super admin', 'Super Admin')
                                   AND Id != 1
                                 ORDER BY name");
            $st->execute([$activeStoreId]);
            $staff = array_map(function ($r) {
                return ['id' => (int)$r['Id'], 'name' => $r['name'], 'role' => $r['status']];
            }, $st->fetchAll(PDO::FETCH_ASSOC));
            respond(['success' => true, 'staff' => $staff]);
            break;
        }

        // ====================================================================
        // 8. ປະຫວັດການເປີດ-ປິດກະ (Shift Sessions History)
        // ====================================================================
        case 'list_sessions': {
            $dateFrom = trim($_GET['date_from'] ?? date('Y-m-01'));
            $dateTo = trim($_GET['date_to'] ?? date('Y-m-d'));
            $shiftId = intval($_GET['shift_id'] ?? 0);

            $sql = "
                SELECT s.*, 
                       TIMESTAMPDIFF(MINUTE, s.open_time, COALESCE(s.close_time, NOW())) as duration_minutes
                FROM st_shift_sessions s
                WHERE s.store_id = :store_id
                  AND DATE(s.open_time) >= :dfrom 
                  AND DATE(s.open_time) <= :dto
            ";
            $params = [
                ':store_id' => $activeStoreId,
                ':dfrom' => $dateFrom,
                ':dto' => $dateTo
            ];

            if ($shiftId > 0) {
                $sql .= " AND s.shift_id = :shift_id";
                $params[':shift_id'] = $shiftId;
            }

            $sql .= " ORDER BY s.id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($isCashier) {
                // ພະນັກງານ: ເຫັນສະເພາະກະຂອງຕົນເອງ ແລະ ບໍ່ເຫັນສ່ວນຕ່າງ
                $sessions = array_values(array_map('shiftHideDiff', array_filter($sessions, function ($r) use ($userId) { return shiftIsOwn($r, $userId); })));
            }

            // ຍອດລວມ KPI
            $totalSalesAll = 0;
            $totalBillsAll = 0;
            $totalDiffAll = 0;

            foreach ($sessions as &$row) {
                $row['initial_cash'] = floatval($row['initial_cash']);
                $row['system_sales_cash'] = floatval($row['system_sales_cash']);
                $row['system_sales_transfer'] = floatval($row['system_sales_transfer']);
                $row['system_sales_debt'] = floatval($row['system_sales_debt']);
                $row['system_total_sales'] = floatval($row['system_total_sales']);
                $row['system_total_bills'] = intval($row['system_total_bills']);
                $row['counted_sales_cash'] = $row['counted_sales_cash'] !== null ? floatval($row['counted_sales_cash']) : null;
                $row['difference_cash'] = $row['difference_cash'] !== null ? floatval($row['difference_cash']) : null;

                $totalSalesAll += $row['system_total_sales'];
                $totalBillsAll += $row['system_total_bills'];
                if ($row['difference_cash'] !== null) {
                    $totalDiffAll += $row['difference_cash'];
                }
            }

            respond([
                'success' => true,
                'sessions' => $sessions,
                'kpi' => [
                    'total_shifts' => count($sessions),
                    'total_sales' => $totalSalesAll,
                    'total_bills' => $totalBillsAll,
                    'total_diff' => $totalDiffAll
                ]
            ]);
            break;
        }

        // ====================================================================
        // 9. ລາຍລະອຽດກະ ແລະ ບິນຂາຍໃນກະ (Session Details & Bill List)
        // ====================================================================
        case 'get_session_details': {
            $sessionId = intval($_GET['session_id'] ?? 0);
            if ($sessionId <= 0) {
                respond(['success' => false, 'message' => 'ລະຫັດກະບໍ່ຖືກຕ້ອງ']);
            }

            $stmtS = $pdo->prepare("SELECT * FROM st_shift_sessions WHERE id = ? AND store_id = ? LIMIT 1");
            $stmtS->execute([$sessionId, $activeStoreId]);
            $session = $stmtS->fetch(PDO::FETCH_ASSOC);
            if (!$session) {
                respond(['success' => false, 'message' => 'ບໍ່ພົບຂໍ້ມູນກະ']);
            }
            // ພະນັກງານ: ເບິ່ງໄດ້ສະເພາະກະຂອງຕົນເອງ (ລາຍການຂາຍຂອງກະນັ້ນ) ແລະ ບໍ່ເຫັນສ່ວນຕ່າງ
            if ($isCashier) {
                if (!shiftIsOwn($session, $userId)) {
                    respond(['success' => false, 'message' => 'ເບິ່ງໄດ້ສະເພາະກະຂອງທ່ານເອງ']);
                }
                $session = shiftHideDiff($session);
            }

            $openTime = $session['open_time'];
            $endTime = !empty($session['close_time']) ? $session['close_time'] : date('Y-m-d H:i:s');

            // ດຶງບິນທີ່ຂາຍໃນກະນີ້
            $stmtBills = $pdo->prepare("
                SELECT sale_save_bill, sale_date, sale_time, user_receive, customer_name,
                       type_pay, sale_barlance, cash_received, qr_received, sale_qty
                FROM st_tbsale_save
                WHERE (shift_session_id = :sid OR (store_id = :store_id AND CONCAT(sale_date, ' ', sale_time) >= :open_time AND CONCAT(sale_date, ' ', sale_time) <= :end_time))
                  AND sale_status = 'SUCCESS'
                ORDER BY CONCAT(sale_date, ' ', sale_time) ASC
            ");
            $stmtBills->execute([
                ':sid' => $sessionId,
                ':store_id' => $activeStoreId,
                ':open_time' => $openTime,
                ':end_time' => $endTime
            ]);
            $bills = $stmtBills->fetchAll(PDO::FETCH_ASSOC);
            // ພະນັກງານ: ລາຍການຂາຍສະເພາະບິນທີ່ຕົນເອງຂາຍໃນກະນີ້ (ສະຫຼຸບຍອດຂອງກະຍັງເປັນຂອງທັງກະ)
            if ($isCashier) {
                $me = (string)($_SESSION['username'] ?? '');
                $bills = array_values(array_filter($bills, function ($b) use ($me) { return (string)$b['user_receive'] === $me; }));
            }

            respond([
                'success' => true,
                'session' => $session,
                'bills' => $bills
            ]);
            break;
        }

        default:
            respond(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Shift backend error: " . $e->getMessage());
    respond(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດ: ' . $e->getMessage()]);
}
