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

// ບໍ່ປິດກະອໍໂຕ້ເມື່ອກາຍໂມງ: ໃຫ້ພະນັກງານປິດເອງສະເໝີ ("ກາຍໂມງກະຢ່າ ປິດກາຍໂມງບໍ່ມີບັນຫາ ບໍ່ໃຫ້ເດັ້ງຟອມເປີດກະມາ ຖ້າແຕ່ປິດ")

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
// ສາຂາທີ່ໜ້າລາຍງານຕາມກະຂໍເບິ່ງ (?branch=N, 0 = ທຸກສາຂາ): ສະເພາະຜູ້ທີ່ເບິ່ງທຸກສາຂາໄດ້ (canViewAllBranches);
// ຄົນອື່ນ ຫຼື ບໍ່ສົ່ງມາ = ສາຂາຕົນເອງ. ໃຊ້ ?branch ບໍ່ແມ່ນ ?store_id — store_id ຈະປ່ຽນສາຂາທີ່ເບິ່ງທົ່ວລະບົບ
// (config/bootstrap/branch_switcher.php).
function shiftRequestedStore($pdo, $ownStoreId) {
    if (!isset($_GET['branch']) || $_GET['branch'] === '' || !canViewAllBranches($pdo)) return (int)$ownStoreId;
    return max(0, intval($_GET['branch']));
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

                // ກົດດຽວກັບປິດກະ ແລະ ລາຍງານ (shiftSalesTotals — ບໍ່ນັບເງິນທອນເປັນຍອດຂາຍ)
                $t = shiftSalesTotals($pdo, $activeSession, date('Y-m-d H:i:s'));
                $salesSummary = ['total_bills' => $t['bills'], 'cash_sales' => $t['cash'], 'transfer_sales' => $t['transfer'],
                                 'debt_sales' => $t['debt'], 'total_sales' => $t['total']];
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

            // ຄິດໄລ່ຍອດຂາຍ — ກົດດຽວກັບປິດກະ ແລະ ລາຍງານ (config/helpers/shift_auto.php shiftSalesTotals)
            $t = shiftSalesTotals($pdo, $session, $endTime);
            $salesSummary = ['total_bills' => $t['bills'], 'cash_sales' => $t['cash'], 'transfer_sales' => $t['transfer'],
                             'debt_sales' => $t['debt'], 'total_sales' => $t['total']];

            // ດຶງລາຍຊື່ຜູ້ຂາຍທີ່ເຄື່ອນໄຫວໃນກະນີ້
            $stmtCashiers = $pdo->prepare("
                SELECT DISTINCT user_receive 
                FROM st_tbsale_save 
                WHERE (shift_session_id = :sid OR (store_id = :store_id AND shift_session_id IS NULL AND CONCAT(sale_date, ' ', sale_time) >= :open_time AND CONCAT(sale_date, ' ', sale_time) <= :end_time))
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
            // ?branch (ບັອກສາຂາ ໜ້າລາຍງານຕາມກະ) — 0 = ກະຂອງທຸກສາຂາ (ພ້ອມຊື່ສາຂາ)
            $listStore = shiftRequestedStore($pdo, $activeStoreId);
            $stmt = $pdo->prepare("
                SELECT sh.*, st.store_name FROM st_shifts sh
                LEFT JOIN st_tbstore st ON st.store_id = sh.store_id
                WHERE ((? = 0 AND st.store_id IS NOT NULL) OR sh.store_id = ?)   -- ທຸກສາຂາ: ບໍ່ເອົາກະຂອງສາຂາທີ່ບໍ່ມີຢູ່ແລ້ວ
                ORDER BY sh.store_id ASC, sh.start_time ASC
            ");
            $stmt->execute([$listStore, $listStore]);
            $shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // ກະທີ່ມີພະນັກງານເປີດຢູ່ຕອນນີ້ (session status = 'open') — ຖັນສະຖານະອອນໄລນ໌ສະເພາະກະນັ້ນ
            $openStmt = $pdo->prepare("SELECT shift_id, opened_by_name, open_time FROM st_shift_sessions
                                       WHERE (? = 0 OR store_id = ?) AND status = 'open' ORDER BY id DESC");
            $openStmt->execute([$listStore, $listStore]);
            $openByShift = [];
            foreach ($openStmt->fetchAll(PDO::FETCH_ASSOC) as $o) {
                if (!isset($openByShift[$o['shift_id']])) $openByShift[$o['shift_id']] = $o;
            }

            // ຈັດຮູບແບບເວລາໃຫ້ອ່ານງ່າຍ + ພະນັກງານປະຈຳກະ
            foreach ($shifts as &$s) {
                $s['start_time_fmt'] = substr($s['start_time'], 0, 5);
                $s['end_time_fmt'] = substr($s['end_time'], 0, 5);
                $s['staff'] = shiftStaffList($pdo, $s['staff_user_ids'] ?? '', (int)$s['store_id']);
                $o = $openByShift[$s['id']] ?? null;
                $s['open_session'] = $o ? ['opened_by_name' => $o['opened_by_name'], 'open_time_fmt' => date('H:i', strtotime($o['open_time']))] : null;
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
                respond(['success' => false, 'message' => 'ກະລຸນາເລືອກພະນັກງານກະນີ້ກ່ອນ']);
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

            $listStore = shiftRequestedStore($pdo, $activeStoreId);   // 0 = ທຸກສາຂາ
            $sql = "
                SELECT s.*, st.store_name,
                       TIMESTAMPDIFF(MINUTE, s.open_time, COALESCE(s.close_time, NOW())) as duration_minutes
                FROM st_shift_sessions s
                LEFT JOIN st_tbstore st ON st.store_id = s.store_id
                WHERE DATE(s.open_time) >= :dfrom
                  AND DATE(s.open_time) <= :dto
            ";
            $params = [
                ':dfrom' => $dateFrom,
                ':dto' => $dateTo
            ];
            if ($listStore > 0) {
                $sql .= " AND s.store_id = :store_id";
                $params[':store_id'] = $listStore;
            }

            if ($shiftId > 0) {
                $sql .= " AND s.shift_id = :shift_id";
                $params[':shift_id'] = $shiftId;
            }

            $sql .= " ORDER BY s.id DESC";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            // ຍອດຂອງແຕ່ລະກະ ຄິດສົດຈາກບິນ (ກະທີ່ເປີດຢູ່ກໍມີຍອດ; ບິນທີ່ຍົກເລີກພາຍຫຼັງບໍ່ນັບ; ສ່ວນຕ່າງຖືກຕ້ອງ)
            foreach ($sessions as $k => $row) {
                $sessions[$k] = shiftApplyLiveTotals($pdo, $row);
            }
            if ($isCashier) {
                // ພະນັກງານ: ເຫັນສະເພາະກະຂອງຕົນເອງ ແລະ ບໍ່ເຫັນສ່ວນຕ່າງ
                $sessions = array_values(array_map('shiftHideDiff', array_filter($sessions, function ($r) use ($userId) { return shiftIsOwn($r, $userId); })));
            }

            // ດຶງຂໍ້ມູນທະນາຄານທີ່ມີການໂອນເງິນໃນແຕ່ລະກະ (ສຳລັບສະແດງໂລໂກ້ທະນາຄານໃນຖັນ ຂາຍໂອນ/QR)
            $sessionIds = array_filter(array_column($sessions, 'id'));
            $sessionBanks = [];
            if (!empty($sessionIds)) {
                $ph = implode(',', array_fill(0, count($sessionIds), '?'));
                $stmtBank = $pdo->prepare("
                    SELECT s.shift_session_id, s.bank_name, b.bank_logo, b.bank_code, b.bank_name AS account_bank_name,
                           SUM(s.sale_barlance) as bank_total
                    FROM st_tbsale_save s
                    LEFT JOIN st_bank_accounts b ON (b.id = s.bank_account_id OR (s.bank_account_id IS NULL AND b.bank_name = s.bank_name))
                    WHERE s.shift_session_id IN ($ph)
                      AND (s.type_pay LIKE '%ໂອນ%' OR s.type_pay LIKE '%QR%' OR s.qr_received > 0)
                      AND s.sale_status = 'SUCCESS'
                    GROUP BY s.shift_session_id, s.bank_name, b.bank_logo, b.bank_code, b.bank_name
                ");
                $stmtBank->execute($sessionIds);
                foreach ($stmtBank->fetchAll(PDO::FETCH_ASSOC) as $bk) {
                    $sid = $bk['shift_session_id'];
                    $bName = trim((string)($bk['bank_name'] ?: ($bk['account_bank_name'] ?? '')));
                    $logo = !empty($bk['bank_logo']) ? $bk['bank_logo'] : '';
                    $logoUrl = $logo ? ('../../assets/img/banks/' . basename($logo)) : '';
                    $sessionBanks[$sid][] = [
                        'bank_name' => $bName ?: 'ໂອນ',
                        'bank_logo' => $logo,
                        'bank_logo_url' => $logoUrl,
                        'total' => floatval($bk['bank_total'])
                    ];
                }
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

                $row['transfer_banks'] = $sessionBanks[$row['id']] ?? [];
                $row['transfer_banks_text'] = implode(', ', array_unique(array_filter(array_column($row['transfer_banks'], 'bank_name'))));

                $totalSalesAll += $row['system_total_sales'];
                $totalBillsAll += $row['system_total_bills'];
                if ($row['difference_cash'] !== null) {
                    $totalDiffAll += $row['difference_cash'];
                }
            }
            unset($row);

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

            // ຜູ້ທີ່ເບິ່ງທຸກສາຂາໄດ້ ເປີດໃບສະຫຼຸບກະຂອງສາຂາໃດກໍໄດ້ (ບັອກສາຂາ ໜ້າລາຍງານຕາມກະ); ຄົນອື່ນ = ສາຂາຕົນເອງ
            if (canViewAllBranches($pdo)) {
                $stmtS = $pdo->prepare("SELECT * FROM st_shift_sessions WHERE id = ? LIMIT 1");
                $stmtS->execute([$sessionId]);
            } else {
                $stmtS = $pdo->prepare("SELECT * FROM st_shift_sessions WHERE id = ? AND store_id = ? LIMIT 1");
                $stmtS->execute([$sessionId, $activeStoreId]);
            }
            $session = $stmtS->fetch(PDO::FETCH_ASSOC);
            if (!$session) {
                respond(['success' => false, 'message' => 'ບໍ່ພົບຂໍ້ມູນກະ']);
            }
            $session = shiftApplyLiveTotals($pdo, $session);   // ຍອດສົດຈາກບິນ (ກົດດຽວກັບລາຍງານ / ປິດກະ)
            // ພະນັກງານ: ເບິ່ງໄດ້ສະເພາະກະຂອງຕົນເອງ (ລາຍການຂາຍຂອງກະນັ້ນ) ແລະ ບໍ່ເຫັນສ່ວນຕ່າງ
            if ($isCashier) {
                if (!shiftIsOwn($session, $userId)) {
                    respond(['success' => false, 'message' => 'ເບິ່ງໄດ້ສະເພາະກະຂອງທ່ານເອງ']);
                }
                $session = shiftHideDiff($session);
            }

            $openTime = $session['open_time'];
            $endTime = !empty($session['close_time']) ? $session['close_time'] : date('Y-m-d H:i:s');

            // ຊື່ສາຂາຂອງກະ — ສະແດງໃນໃບສະຫຼຸບ / ພິມ / Excel / PDF
            $stStore = $pdo->prepare("SELECT store_name FROM st_tbstore WHERE store_id = ? LIMIT 1");
            $stStore->execute([(int)$session['store_id']]);
            $session['store_name'] = (string)($stStore->fetchColumn() ?: '');

            // ດຶງບິນທີ່ຂາຍໃນກະນີ້ ພ້ອມຂໍ້ມູນທະນາຄານໂອນ
            $stmtBills = $pdo->prepare("
                SELECT s.sale_save_bill, s.sale_date, s.sale_time, s.user_receive, s.customer_name,
                       s.type_pay, s.sale_barlance, s.cash_received, s.qr_received, s.sale_qty,
                       s.bank_account_id, s.bank_name,
                       b.bank_logo, b.bank_code, b.bank_name AS account_bank_name
                FROM st_tbsale_save s
                LEFT JOIN st_bank_accounts b ON (b.id = s.bank_account_id OR (s.bank_account_id IS NULL AND b.bank_name = s.bank_name))
                WHERE (s.shift_session_id = :sid OR (s.store_id = :store_id AND s.shift_session_id IS NULL AND CONCAT(s.sale_date, ' ', s.sale_time) >= :open_time AND CONCAT(s.sale_date, ' ', s.sale_time) <= :end_time))
                  AND s.sale_status = 'SUCCESS'
                ORDER BY CONCAT(s.sale_date, ' ', s.sale_time) ASC
            ");
            $sessStore = (int)$session['store_id'];   // ບິນ/ສິນຄ້າ ຂອງສາຂາທີ່ກະນີ້ເປັນ
            $stmtBills->execute([
                ':sid' => $sessionId,
                ':store_id' => $sessStore,
                ':open_time' => $openTime,
                ':end_time' => $endTime
            ]);
            $bills = $stmtBills->fetchAll(PDO::FETCH_ASSOC);
            foreach ($bills as &$b) {
                $bName = trim((string)($b['bank_name'] ?: ($b['account_bank_name'] ?? '')));
                $b['bank_display_name'] = $bName;
                $logo = !empty($b['bank_logo']) ? $b['bank_logo'] : '';
                $b['bank_logo_url'] = $logo ? ('../../assets/img/banks/' . basename($logo)) : '';
            }
            unset($b);
            // ພະນັກງານ: ລາຍການຂາຍສະເພາະບິນທີ່ຕົນເອງຂາຍໃນກະນີ້ (ສະຫຼຸບຍອດຂອງກະຍັງເປັນຂອງທັງກະ)
            if ($isCashier) {
                $me = (string)($_SESSION['username'] ?? '');
                $bills = array_values(array_filter($bills, function ($b) use ($me) { return (string)$b['user_receive'] === $me; }));
            }

            // ສິນຄ້າທີ່ຂາຍໃນກະ (ລວມຕາມສິນຄ້າ ຈາກບິນຂ້າງເທິງ): ຈຳນວນ, ລາຄາ, ສ່ວນຫຼຸດລາຍການ, ຍອດສຸດທິ
            $items = [];
            $billNos = array_values(array_unique(array_column($bills, 'sale_save_bill')));
            if ($billNos) {
                $ph = implode(',', array_fill(0, count($billNos), '?'));
                $stmtItems = $pdo->prepare("
                    SELECT d.save_proid AS product_id, MAX(d.save_proname) AS product_name,
                           SUM(d.save_qty) AS qty, SUM(d.save_money) AS gross,
                           SUM(COALESCE(d.save_discount_item, 0)) AS discount,
                           MAX(d.save_price) AS price, COUNT(DISTINCT d.save_bill) AS bill_count
                    FROM st_tbsale_save_detail d
                    INNER JOIN st_tbsale_save s ON s.sale_save_bill = d.save_bill AND s.store_id = ?
                    WHERE d.save_bill IN ($ph)
                    GROUP BY d.save_proid, BINARY d.save_proname
                    ORDER BY qty DESC, product_name ASC
                ");
                $stmtItems->execute(array_merge([$sessStore], $billNos));
                foreach ($stmtItems->fetchAll(PDO::FETCH_ASSOC) as $it) {
                    $it['qty'] = (float)$it['qty'];
                    $it['gross'] = (float)$it['gross'];
                    $it['discount'] = (float)$it['discount'];
                    $it['net'] = $it['gross'] - $it['discount'];
                    $items[] = $it;
                }
            }

            // ສິນຄ້າແຍກຕາມການຊຳລະຂອງບິນ (ລາຍງານລາຍຮັບຕາມກະ): ເງິນສົດ / ເງິນໂອນ ແຍກແຕ່ລະທະນາຄານ / ສົດ+ໂອນ / ຕິດໜີ້
            $itemsByChannel = [];
            if ($billNos) {
                $billChannel = [];
                foreach ($bills as $b) {
                    $tp = (string)$b['type_pay'];
                    $cash = (float)($b['cash_received'] ?? 0);
                    $qr = (float)($b['qr_received'] ?? 0);
                    $isTransfer = $qr > 0 || strpos($tp, 'ໂອນ') !== false || strpos($tp, 'QR') !== false;
                    if (strpos($tp, 'ຕິດໜີ້') !== false) {
                        $ch = ['key' => 'debt', 'type' => 'debt', 'label' => 'ຕິດໜີ້'];
                    } elseif ($isTransfer && $cash > 0 && $qr > 0) {
                        $ch = ['key' => 'mixed', 'type' => 'mixed', 'label' => 'ເງິນສົດ + ໂອນ'];
                    } elseif ($isTransfer) {
                        $bank = $b['bank_display_name'] !== '' ? $b['bank_display_name'] : 'ບໍ່ລະບຸທະນາຄານ';
                        $ch = ['key' => 'transfer|' . $bank, 'type' => 'transfer', 'label' => 'ເງິນໂອນ · ' . $bank,
                               'bank_name' => $bank, 'bank_logo_url' => $b['bank_logo_url']];
                    } else {
                        $ch = ['key' => 'cash', 'type' => 'cash', 'label' => 'ເງິນສົດ'];
                    }
                    $billChannel[$b['sale_save_bill']] = $ch;
                    if (!isset($itemsByChannel[$ch['key']])) {
                        $itemsByChannel[$ch['key']] = $ch + ['bill_count' => 0, 'total' => 0.0, 'items' => []];
                    }
                    $itemsByChannel[$ch['key']]['bill_count']++;
                    $itemsByChannel[$ch['key']]['total'] += (float)$b['sale_barlance'];
                }
                $stmtLines = $pdo->prepare("
                    SELECT d.save_bill, d.save_proid, d.save_proname, d.save_qty, d.save_price, d.save_money,
                           COALESCE(d.save_discount_item, 0) AS discount
                    FROM st_tbsale_save_detail d
                    INNER JOIN st_tbsale_save s ON s.sale_save_bill = d.save_bill AND s.store_id = ?
                    WHERE d.save_bill IN ($ph)
                ");
                $stmtLines->execute(array_merge([$sessStore], $billNos));
                $linesByBill = [];
                foreach ($stmtLines->fetchAll(PDO::FETCH_ASSOC) as $ln) {
                    $linesByBill[$ln['save_bill']][] = [
                        'product_name' => $ln['save_proname'], 'qty' => (float)$ln['save_qty'], 'price' => (float)$ln['save_price'],
                        'discount' => (float)$ln['discount'], 'net' => (float)$ln['save_money'] - (float)$ln['discount'],
                    ];
                    $ch = $billChannel[$ln['save_bill']] ?? null;
                    if (!$ch) continue;
                    $pKey = $ln['save_proid'] . '|' . $ln['save_proname'];
                    $row = &$itemsByChannel[$ch['key']]['items'][$pKey];
                    if (!isset($row)) {
                        $row = ['product_name' => $ln['save_proname'], 'qty' => 0.0, 'price' => (float)$ln['save_price'], 'discount' => 0.0, 'net' => 0.0];
                    }
                    $row['qty'] += (float)$ln['save_qty'];
                    $row['discount'] += (float)$ln['discount'];
                    $row['net'] += (float)$ln['save_money'] - (float)$ln['discount'];
                    unset($row);
                }
                // ລຳດັບ: ເງິນສົດ → ເງິນໂອນ (ຕາມທະນາຄານ) → ສົດ+ໂອນ → ຕິດໜີ້; ສິນຄ້າໃນກຸ່ມ: ຂາຍຫຼາຍສຸດກ່ອນ
                $order = ['cash' => 0, 'transfer' => 1, 'mixed' => 2, 'debt' => 3];
                uasort($itemsByChannel, function ($a, $b) use ($order) {
                    return [$order[$a['type']], $a['label']] <=> [$order[$b['type']], $b['label']];
                });
                foreach ($itemsByChannel as &$grp) {
                    $grp['items'] = array_values($grp['items']);
                    usort($grp['items'], function ($a, $b) { return $b['qty'] <=> $a['qty']; });
                }
                unset($grp);
                $itemsByChannel = array_values($itemsByChannel);

                // ແຕ່ລະບິນ: ລາຍການສິນຄ້າ + ຮັບເງິນແຕ່ລະທາງເທົ່າໃດ (ລາຍງານລາຍຮັບຕາມກະ — ຕາຕະລາງ "ສິນຄ້າແຍກຕາມການຊຳລະ").
                // cash_received = ເງິນສົດທີ່ລູກຄ້າຍື່ນ (ລວມເງິນທອນ) → ເງິນສົດທີ່ຮັບແທ້ = ຍອດບິນ - ເງິນໂອນ
                foreach ($bills as &$b) {
                    $ch = $billChannel[$b['sale_save_bill']];
                    $total = (float)$b['sale_barlance'];
                    $qr = (float)($b['qr_received'] ?? 0);
                    if ($ch['type'] === 'debt') {
                        $b['transfer_part'] = min($qr, $total);
                        $b['cash_part'] = min((float)($b['cash_received'] ?? 0), $total - $b['transfer_part']);
                        $b['debt_part'] = max(0, $total - $b['cash_part'] - $b['transfer_part']);
                    } else {
                        $b['transfer_part'] = $ch['type'] === 'cash' ? 0 : ($qr > 0 ? min($qr, $total) : $total);
                        $b['cash_part'] = max(0, $total - $b['transfer_part']);
                        $b['debt_part'] = 0;
                    }
                    $b['channel_type'] = $ch['type'];
                    $b['lines'] = $linesByBill[$b['sale_save_bill']] ?? [];
                }
                unset($b);
            }

            respond([
                'success' => true,
                'session' => $session,
                'bills' => $bills,
                'items' => $items,
                'items_by_channel' => $itemsByChannel
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
