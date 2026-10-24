<?php
/**
 * ເຊັກສະຕັອກ — controller ຂອງ pages/stock_check/stock_check.php ແລະ ເປົ້າໝາຍ AJAX ໃນໄຟລ໌ດຽວກັນ.
 *
 * ຂັ້ນຕອນ: ເລີ່ມນັບ (create_check) → ລະບົບຖ່າຍຈຳນວນໃນລະບົບ (system_qty) ຂອງທຸກສິນຄ້າທີ່ຕັດສະຕັອກໄວ້
 * → ພະນັກງານປ້ອນ/ສະແກນຈຳນວນທີ່ນັບໄດ້ ແລະ ບັນທຶກ (save_counts) ໄດ້ຫຼາຍເທື່ອ            [draft]
 * → ສົ່ງໃຫ້ຫົວໜ້າ/ຜູ້ຈັດການກວດສອບ (submit_for_approval) — ພະນັກງານແກ້ຕົວເລກບໍ່ໄດ້ອີກ   [pending]
 * → ຫົວໜ້າກົດ "ອະນຸມັດ ແລະ ປັບສະຕັອກ" (approve_adjustment) — ອະນຸມັດ + ປັບສະຕັອກໃນຂັ້ນດຽວ [completed]
 *   ຫຼື ສົ່ງກັບໃຫ້ນັບໃໝ່ (reject_adjustment → draft).
 *   ຫົວໜ້າແກ້ຕົວເລກໄດ້ໃນ pending, ແລະ ອະນຸມັດໃບທີ່ຕົນເອງນັບ (draft) ໄດ້ເລີຍ ບໍ່ຕ້ອງສົ່ງ.
 *   ປັບ st_products.qty ດ້ວຍສ່ວນຕ່າງ (counted - system) ຂອງລາຍການທີ່ນັບແລ້ວ (scAdjustStock).
 *   complete_check ຍັງຮັບໄວ້ສຳລັບໃບເກົ່າທີ່ຄ້າງຢູ່ສະຖານະ 'approved' (ກ່ອນລວມເປັນຂັ້ນດຽວ).
 *   ໃຊ້ສ່ວນຕ່າງແທນການຕັ້ງຄ່າທັບ ເພື່ອບໍ່ໃຫ້ລົບລ້າງການຂາຍ/ນຳເຂົ້າທີ່ເກີດຂຶ້ນລະຫວ່າງນັບ.
 *   ລາຍການທີ່ບໍ່ໄດ້ນັບ (counted_qty = NULL) ບໍ່ຖືກແຕະ.
 * ໃບທີ່ຍັງບໍ່ຢືນຢັນ ລຶບໄດ້ (delete_check); ໃບທີ່ຢືນຢັນແລ້ວ ເບິ່ງ/ພິມໄດ້ຢ່າງດຽວ.
 * ສາຂາ: ໃຊ້ getActiveStoreId() ສະເໝີ.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../config/db.php';

$scIsAjax = ($_SERVER['REQUEST_METHOD'] === 'POST')
    && in_array($_POST['action'] ?? '', ['save_counts', 'submit_for_approval', 'approve_adjustment', 'reject_adjustment', 'complete_check', 'delete_check', 'check_item_stock'], true);

// ສິດ:
//   ພະນັກງານທົ່ວໄປ: ບໍ່ໃຫ້ເຫັນຍອດໃນລະບົບ (Blind count) ໃຫ້ເຫັນແຕ່ຈຳນວນຕົວຈິງ, ແລະ ບໍ່ຕ້ອງສົ່ງຂໍອະນຸມັດ — ກົດບັນທຶກປັບສະຕັອກໄດ້ເລີຍ
//   ຫົວໜ້າ/ຜູ້ບໍລິຫານ: ເຫັນຍອດໃນລະບົບ, ສ່ວນຕ່າງ (ຄົບ/ບໍ່ພໍ/ເກີນ), ແລະ ຈັດການໃບເຊັກສະຕັອກໄດ້
$scLoggedIn   = !empty($_SESSION['user_id']);
$userStatus   = trim($_SESSION['status'] ?? '');
$isBossRole   = in_array($userStatus, ['admin', 'Admin', 'super admin', 'Super Admin', 'ຜູ້ບໍລິຫານ']);
$isMgrRole    = in_array($userStatus, ['manager', 'Manager', 'ຜູ້ຈັດການ']);
$scIsBoss     = $scLoggedIn && (isOwnerUser() || $isBossRole || isAdminAccount($pdo, $_SESSION['user_id']));
$scIsManager  = $scLoggedIn && ($scIsBoss || $isMgrRole || isManagerRole());
$scIsStaff    = ($scLoggedIn && !$scIsBoss && !$scIsManager); // ພະນັກງານທົ່ວໄປ

$scIsCounter  = $scLoggedIn && ($scIsManager || hasPermission('stock_check') || hasPermission('stock_count'));
$scCanStart   = $scIsCounter;
$scCanConfirm = $scIsCounter;
$scCanApprove = $scIsCounter; // ບໍ່ຕ້ອງສົ່ງໃຫ້ຫົວໜ້າອະນຸມັດ — ພະນັກງານກໍສາມາດກົດບັນທຶກ ແລະ ປັບສະຕັອກໄດ້ເລີຍ
$scCanDelete  = $scIsManager;
$scCanCount   = $scIsCounter;

// ພະນັກງານບໍ່ໃຫ້ເຫັນໃນລະບົບ ໃຫ້ເຫັນແຕ່ຈຳນວນຕົວຈິງ (Blind stock take)
// ສະເພາະຫົວໜ້າ/ຜູ້ບໍລິຫານຈຶ່ງເຫັນຍອດໃນລະບົບ ແລະ ສ່ວນຕ່າງ
$scShowSystem = !$scIsStaff;
$scShowDiff   = !$scIsStaff;
$scSeeResults = !$scIsStaff;

if (empty($_SESSION['user_id']) || (!$scIsManager && !$scIsCounter)) {
    if ($scIsAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => t('sc.s203')]);
        exit();
    }
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$scStoreId = getActiveStoreId($pdo);
$scUserId  = intval($_SESSION['user_id']);

// ສ້າງໃບເຊັກສະຕັອກໃໝ່ ແລະ ດຶງສິນຄ້າທັງໝົດທີ່ຕັດສະຕັອກເຂົ້າມາພ້ອມ
function scCreateCheckInternal($pdo, $storeId, $userId, $note = '') {
    $sc = $pdo->prepare("SELECT store_code FROM st_tbstore WHERE store_id = ?");
    $sc->execute([$storeId]);
    $storeCode = preg_replace('/[^0-9A-Za-z]/', '', (string)$sc->fetchColumn());
    if ($storeCode === '') $storeCode = (string)$storeId;
    $prefix = 'SC-' . date('Ymd') . '-' . $storeCode;

    $mx = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(check_no, ?) AS UNSIGNED)) FROM st_stock_checks
                         WHERE store_id = ? AND check_no LIKE ? AND CHAR_LENGTH(check_no) = ?");
    $mx->execute([strlen($prefix) + 1, $storeId, $prefix . '%', strlen($prefix) + 4]);
    $checkNo = $prefix . str_pad((string)((int)$mx->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);

    $pdo->prepare("INSERT INTO st_stock_checks (check_no, store_id, status, note, user_id, created_at) VALUES (?, ?, 'draft', ?, ?, NOW())")
        ->execute([$checkNo, $storeId, mb_substr(trim($note), 0, 255) ?: null, $userId]);
    $draftId = (int)$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO st_stock_check_items (check_id, product_id, barcode, product_name, unit, cost_price, system_qty, zone, shelf)
                   SELECT ?, product_id, barcode, product_name, unit, IFNULL(bprice, 0), IFNULL(qty, 0), zone, shelf
                   FROM st_products WHERE store_id = ? AND IFNULL(cut_qty, 1) <> 0
                   ORDER BY IFNULL(zone, ''), IFNULL(shelf, ''), product_name")
        ->execute([$draftId, $storeId]);

    // ບໍ່ເອີ້ນ logActivity() ຢູ່ນີ້: ມັນ CREATE TABLE IF NOT EXISTS (DDL) ເຊິ່ງ MySQL commit transaction ທີ່ເປີດຢູ່ໃຫ້ເອງ
    // ແລ້ວ $pdo->commit() ຂອງຜູ້ເອີ້ນຈະ throw "There is no active transaction". ຜູ້ເອີ້ນ log ຫຼັງ commit ດ້ວຍ scLogCreated().
    return $draftId;
}

function scLogCreated($pdo, $checkId) {
    $no = $pdo->prepare("SELECT check_no FROM st_stock_checks WHERE id = ?");
    $no->execute([intval($checkId)]);
    logActivity($pdo, 'ເລີ່ມເຊັກສະຕັອກ', 'ເລກທີ: ' . $no->fetchColumn());
}

// ໂຫຼດໃບເຊັກສະຕັອກຂອງສາຂານີ້ (ບໍ່ໃຫ້ເປີດໃບຂອງສາຂາອື່ນຜ່ານ id)
function scLoadCheck($pdo, $checkId, $storeId, $forUpdate = false) {
    $stmt = $pdo->prepare("SELECT * FROM st_stock_checks WHERE id = ? AND store_id = ?" . ($forUpdate ? " FOR UPDATE" : ""));
    $stmt->execute([intval($checkId), intval($storeId)]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// ບັນທຶກຈຳນວນທີ່ນັບ: items = [{id, add?: int, set?: int|""|null, note?}]
//   add = ຈຳນວນທີ່ນັບໄດ້ຮອບນີ້ → ບວກເພີ່ມໃສ່ຍອດລວມໃນຖານຂໍ້ມູນ (ນັບຫຼາຍມື້/ຫຼາຍບ່ອນ/ຫຼາຍຄົນ ລວມກັນ;
//         ບວກຢູ່ຝັ່ງ DB ສະນັ້ນ ຫຼາຍຄົນບັນທຶກພ້ອມກັນ ຍອດບໍ່ທັບກັນ)
//   set = ແກ້ຍອດລວມໂດຍກົງ (ປຸ່ມ ✏️), "" / null = ກັບເປັນ "ຍັງບໍ່ນັບ". ເຮັດ set ກ່ອນ ແລ້ວຈຶ່ງ add.
//   counted_qty (ຮູບແບບເກົ່າ) = ຄືກັບ set.
// counted_by / counted_at ປ່ຽນສະເພາະເມື່ອຈຳນວນປ່ຽນແທ້ (ແກ້ແຕ່ໝາຍເຫດ ບໍ່ນັບວ່າເປັນການນັບໃໝ່).
// ລຳດັບ SET ສຳຄັນ: MySQL ໃຊ້ຄ່າໃໝ່ທີ່ SET ກ່ອນໜ້າ, ຈຶ່ງໃຫ້ counted_qty ຢູ່ທ້າຍ.
// ສົ່ງຄືນຍອດລວມລ່າສຸດຂອງແຖວທີ່ບັນທຶກ (ລວມທັງທີ່ຄົນອື່ນນັບເພີ່ມ) ໃຫ້ໜ້າຈໍອັບເດດ.
function scSaveItems($pdo, $checkId, array $items, $userId) {
    $set = $pdo->prepare("UPDATE st_stock_check_items SET
            counted_by  = IF(counted_qty <=> ?, counted_by, IF(? IS NULL, NULL, ?)),
            counted_at  = IF(counted_qty <=> ?, counted_at, IF(? IS NULL, NULL, NOW())),
            counted_qty = ?
        WHERE id = ? AND check_id = ?");
    $add = $pdo->prepare("UPDATE st_stock_check_items SET
            counted_by = ?, counted_at = NOW(), counted_qty = IFNULL(counted_qty, 0) + ?
        WHERE id = ? AND check_id = ?");
    $note = $pdo->prepare("UPDATE st_stock_check_items SET note = ? WHERE id = ? AND check_id = ?");
    $ids = [];
    foreach ($items as $it) {
        if (!is_array($it) || empty($it['id'])) continue;
        $id = intval($it['id']);
        $ids[] = $id;
        $hasSet = array_key_exists('set', $it) || array_key_exists('counted_qty', $it);
        if ($hasSet) {
            $raw = array_key_exists('set', $it) ? $it['set'] : $it['counted_qty'];
            $v = ($raw === null || $raw === '') ? null : max(0, intval($raw));
            $set->execute([$v, $v, $userId, $v, $v, $v, $id, intval($checkId)]);
        }
        $n = intval($it['add'] ?? 0);
        if ($n > 0) {
            $add->execute([$userId, $n, $id, intval($checkId)]);
        }
        if (array_key_exists('note', $it)) {
            $t = mb_substr(trim((string)$it['note']), 0, 255);
            $note->execute([$t !== '' ? $t : null, $id, intval($checkId)]);
        }
    }
    if (!$ids) return [];
    $in = implode(',', array_map('intval', array_unique($ids)));
    $rows = $pdo->query("SELECT i.id, i.system_qty, i.counted_qty, (i.counted_qty - i.system_qty) AS diff, i.counted_at,
                                COALESCE(NULLIF(TRIM(CONCAT(IFNULL(u.fname,''), ' ', IFNULL(u.lname,''))), ''), u.username) AS counted_by_name
                         FROM st_stock_check_items i LEFT JOIN st_tbuser u ON u.Id = i.counted_by
                         WHERE i.check_id = " . intval($checkId) . " AND i.id IN ($in)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['counted_at'] = $r['counted_at'] ? date('d/m H:i', strtotime($r['counted_at'])) : null;
        $r['system_qty'] = (int)$r['system_qty'];
        $r['counted_qty'] = ($r['counted_qty'] === null) ? null : (int)$r['counted_qty'];
        $r['diff'] = ($r['counted_qty'] === null) ? null : ((int)$r['counted_qty'] - (int)$r['system_qty']);
    }
    return $rows;
}

// ປັບສະຕັອກໃຫ້ຕົງກັບຈຳນວນຕົວຈິງ ແລະ ປິດໃບ (ຕ້ອງຢູ່ໃນ transaction ຂອງຜູ້ເອີ້ນ). ສົ່ງຄືນຈຳນວນລາຍການທີ່ປັບ.
function scAdjustStock($pdo, $checkId, $storeId, $userId) {
    $rows = $pdo->prepare("SELECT product_id, system_qty, counted_qty FROM st_stock_check_items
                           WHERE check_id = ? AND counted_qty IS NOT NULL AND counted_qty <> system_qty");
    $rows->execute([$checkId]);
    $adj = $pdo->prepare("UPDATE st_products SET qty = qty + ? WHERE product_id = ? AND store_id = ?");
    $adjusted = 0;
    foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $adj->execute([intval($r['counted_qty']) - intval($r['system_qty']), $r['product_id'], $storeId]);
        $adjusted++;
    }
    $pdo->prepare("UPDATE st_stock_checks SET status = 'completed', completed_by = ?, completed_at = NOW() WHERE id = ?")
        ->execute([$userId, $checkId]);
    return $adjusted;
}

// =====================================================================
// AJAX
// =====================================================================
if ($scIsAjax) {
    header('Content-Type: application/json; charset=utf-8');
    $action  = $_POST['action'];
    $checkId = intval($_POST['check_id'] ?? 0);
    $items   = json_decode($_POST['items'] ?? '[]', true);
    if (!is_array($items)) $items = [];

    $allowed = [
        'save_counts'         => $scCanCount,
        'submit_for_approval' => $scCanCount,
        'approve_adjustment'  => $scCanApprove,
        'reject_adjustment'   => $scCanApprove,
        'complete_check'      => $scCanCount || $scCanConfirm,
        'delete_check'        => $scCanDelete,
        'check_item_stock'    => $scCanCount,
    ][$action] ?? false;
    if (!$allowed) {
        echo json_encode(['success' => false, 'message' => t('sc.s204')]);
        exit();
    }

    try {
        $pdo->beginTransaction();
        $check = scLoadCheck($pdo, $checkId, $scStoreId, true);
        if (!$check) {
            throw new Exception(t('sc.s205'));
        }
        if ($check['status'] === 'completed') {
            throw new Exception(t('sc.s206'));
        }

        // ກວດສອບສະຕັອກລ່າສຸດຂອງສິນຄ້າ (ກວດວ່າມີການຂາຍ/ຕັດສະຕັອກເກີດຂຶ້ນ ຫຼື ບໍ່)
        if ($action === 'check_item_stock') {
            $code = trim($_POST['barcode'] ?? '');
            $stmt = $pdo->prepare("SELECT i.id, i.product_id, i.barcode, i.product_name, i.unit, i.system_qty, i.counted_qty,
                                          p.qty AS live_qty
                                   FROM st_stock_check_items i
                                   JOIN st_products p ON p.product_id = i.product_id AND p.store_id = ?
                                   WHERE i.check_id = ? AND (LOWER(i.barcode) = LOWER(?) OR i.product_id = ?)
                                   LIMIT 1");
            $stmt->execute([$scStoreId, $checkId, $code, $code]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$item) {
                echo json_encode(['success' => false, 'message' => t('sc.s207')]);
                exit();
            }

            $liveQty = intval($item['live_qty']);
            $sysQty  = intval($item['system_qty']);
            $stockCut = ($liveQty < $sysQty); // ມີການຂາຍ / ຕັດສະຕັອກ
            $stockChanged = ($liveQty !== $sysQty);

            if ($stockChanged) {
                // ອັບເດດ system_qty ໃນໃບເຊັກສະຕັອກໃຫ້ກົງກັບສະຕັອກລ່າສຸດທີ່ຖືກຕັດ
                $pdo->prepare("UPDATE st_stock_check_items SET system_qty = ? WHERE id = ?")
                    ->execute([$liveQty, $item['id']]);
                $item['system_qty'] = $liveQty;
            }
            $pdo->commit();

            $counted = ($item['counted_qty'] === null) ? null : intval($item['counted_qty']);
            // ຖ້ານັບຄົບແລ້ວ ແລະ ບໍ່ມີການຕັດສະຕັອກ
            $isComplete = ($counted !== null && $counted === $liveQty && !$stockCut);

            echo json_encode([
                'success'       => true,
                'item'          => $item,
                'live_qty'      => $liveQty,
                'old_sys_qty'   => $sysQty,
                'stock_cut'     => $stockCut,
                'stock_changed' => $stockChanged,
                'is_complete'   => $isComplete
            ]);
            exit();
        }

        if ($action === 'delete_check') {
            $pdo->prepare("DELETE FROM st_stock_check_items WHERE check_id = ?")->execute([$checkId]);
            $pdo->prepare("DELETE FROM st_stock_checks WHERE id = ?")->execute([$checkId]);
            $pdo->commit();
            logActivity($pdo, 'ລຶບໃບເຊັກສະຕັອກ', 'ເລກທີ: ' . $check['check_no']);
            echo json_encode(['success' => true, 'message' => t('sc.s208')]);
            exit();
        }

        // ໃຜແກ້ຕົວເລກໄດ້ຕອນນີ້: draft = ຜູ້ນັບ, pending/approved = ຫົວໜ້າເທົ່ານັ້ນ (ສົ່ງແລ້ວ ພະນັກງານແກ້ບໍ່ໄດ້)
        $st = $check['status'];
        $canEditNow = ($st === 'draft' && $scCanCount) || (in_array($st, ['pending', 'approved'], true) && $scCanApprove);

        // ລຳດັບຂັ້ນຕອນ: ກວດສະຖານະກ່ອນບັນທຶກຫຍັງ
        if ($action === 'save_counts' && !$canEditNow) {
            throw new Exception(t('sc.s209'));
        }
        if ($action === 'submit_for_approval' && $st !== 'draft') {
            throw new Exception(t('sc.s210'));
        }
        if ($action === 'approve_adjustment' && !in_array($st, ['draft', 'pending', 'approved'], true)) {
            throw new Exception(t('sc.s211'));
        }
        if ($action === 'reject_adjustment' && !in_array($st, ['pending', 'approved'], true)) {
            throw new Exception(t('sc.s212'));
        }
        if ($action === 'complete_check' && $st !== 'approved') {
            throw new Exception(t('sc.s213'));
        }
        if ($action === 'approve_adjustment') {
            $cnt = (int)$pdo->query("SELECT COUNT(*) FROM st_stock_check_items WHERE check_id = " . intval($checkId) . " AND counted_qty IS NOT NULL")->fetchColumn();
            if ($cnt <= 0 && empty($items)) {
                throw new Exception(t('sc.s214'));
            }
        }

        // ລາຍການທີ່ສົ່ງມາພ້ອມ: ບັນທຶກສະເພາະຜູ້ທີ່ແກ້ໄດ້ໃນສະຖານະນີ້ (ຄົນອື່ນ — ບໍ່ສົນ ບໍ່ປ່ຽນຕົວເລກ)
        $savedRows = (!empty($items) && $canEditNow) ? scSaveItems($pdo, $checkId, $items, $scUserId) : [];
        if (isset($_POST['note']) && $canEditNow) {
            $pdo->prepare("UPDATE st_stock_checks SET note = ? WHERE id = ?")
                ->execute([mb_substr(trim($_POST['note']), 0, 255), $checkId]);
        }

        if ($action === 'save_counts') {
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => t('sc.s095'), 'saved_at' => date('H:i:s'), 'rows' => $savedRows]);
            exit();
        }

        // ສົ່ງໃຫ້ຫົວໜ້າກວດສອບ ແລະ ອະນຸຍາດປັບສະຕັອກ
        if ($action === 'submit_for_approval') {
            $cnt = (int)$pdo->query("SELECT COUNT(*) FROM st_stock_check_items WHERE check_id = " . intval($checkId) . " AND counted_qty IS NOT NULL")->fetchColumn();
            if ($cnt <= 0) {
                throw new Exception(t('sc.s215'));
            }
            // submitted_by/at: ຫົວໜ້າໄດ້ແຈ້ງເຕືອນວ່າໃຜສົ່ງ, ແລະ ອະນຸມັດແລ້ວແຈ້ງກັບຫາຜູ້ສົ່ງ (config/helpers/stock_check_alerts.php)
            $pdo->prepare("UPDATE st_stock_checks SET status = 'pending', submitted_by = ?, submitted_at = NOW() WHERE id = ?")->execute([$scUserId, $checkId]);
            $pdo->commit();
            logActivity($pdo, 'ສົ່ງໃບເຊັກສະຕັອກຂໍອະນຸມັດ', 'ເລກທີ: ' . $check['check_no']);
            echo json_encode(['success' => true, 'message' => t('sc.s216')]);
            exit();
        }

        // ຫົວໜ້າ "ອະນຸມັດ ແລະ ປັບສະຕັອກ" — ຂັ້ນດຽວ, transaction ດຽວ
        if ($action === 'approve_adjustment') {
            $pdo->prepare("UPDATE st_stock_checks SET approved_by = ?, approved_at = NOW() WHERE id = ?")
                ->execute([$scUserId, $checkId]);
            $adjusted = scAdjustStock($pdo, $checkId, $scStoreId, $scUserId);
            $pdo->commit();
            logActivity($pdo, 'ປັບສະຕັອກໃຫ້ຕົງ', 'ເລກທີ: ' . $check['check_no'] . ', ປັບສະຕັອກ ' . $adjusted . ' ລາຍການ');
            echo json_encode(['success' => true, 'adjusted' => $adjusted,
                'message' => t('sc.s217') . $adjusted . ' ' . t('sc.s075') . ')']);
            exit();
        }

        // ຫົວໜ້າຕີກັບໃຫ້ນັບໃໝ່ / ແກ້ໄຂຄືນ
        if ($action === 'reject_adjustment') {
            $pdo->prepare("UPDATE st_stock_checks SET status = 'draft', approved_by = NULL, approved_at = NULL WHERE id = ?")
                ->execute([$checkId]);
            $pdo->commit();
            logActivity($pdo, 'ສົ່ງກັບໃຫ້ນັບສະຕັອກໃໝ່', 'ເລກທີ: ' . $check['check_no']);
            echo json_encode(['success' => true, 'message' => t('sc.s218')]);
            exit();
        }

        // complete_check (ໃບເກົ່າທີ່ຄ້າງ 'approved' — ກວດຢູ່ຂ້າງເທິງ): ປັບສະຕັອກໃຫ້ຕົງກັບລະບົບ
        $adjusted = scAdjustStock($pdo, $checkId, $scStoreId, $scUserId);
        $pdo->commit();

        logActivity($pdo, 'ຢືນຢັນເຊັກສະຕັອກ', 'ເລກທີ: ' . $check['check_no'] . ', ປັບສະຕັອກ ' . $adjusted . ' ລາຍການ');
        echo json_encode(['success' => true, 'adjusted' => $adjusted,
            'message' => t('sc.s219') . $adjusted . ' ' . t('sc.s075') . ')']);
        exit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// =====================================================================
// ເລີ່ມນັບໃໝ່ (form POST) — ຖ້າມີໃບທີ່ກຳລັງນັບຢູ່ແລ້ວ ເປີດໃບນັ້ນແທນການສ້າງຊ້ຳ
// =====================================================================
$sc_message = '';
$sc_message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_check') {
    if (!$scCanStart) {
        $sc_message = t('sc.s220');
        $sc_message_type = 'danger';
    } else {
        try {
            $forceNew = !empty($_POST['force_new']);
            $draftId = null;
            if (!$forceNew) {
                $draft = $pdo->prepare("SELECT id FROM st_stock_checks WHERE store_id = ? AND status IN ('draft', 'pending', 'approved') ORDER BY id DESC LIMIT 1");
                $draft->execute([$scStoreId]);
                $draftId = $draft->fetchColumn();
            }
            if (!$draftId) {
                $pdo->beginTransaction();
                $draftId = scCreateCheckInternal($pdo, $scStoreId, $scUserId, $_POST['note'] ?? '');
                $pdo->commit();
                scLogCreated($pdo, $draftId);
            }
            header('Location: stock_check.php?id=' . intval($draftId));
            exit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $sc_message = t('sc.s221') . ' ' . $e->getMessage();
            $sc_message_type = 'danger';
        }
    }
}

// =====================================================================
// ຂໍ້ມູນສຳລັບໜ້າ: ?id=N → ໜ້ານັບ/ເບິ່ງໃບ, ບໍ່ມີ id → ລາຍການໃບເຊັກສະຕັອກ
// =====================================================================
$scCheck = null;
$scItems = [];
$scChecks = [];
$scDateFrom = $_GET['from'] ?? date('Y-m-01');
$scDateTo   = $_GET['to'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scDateFrom)) $scDateFrom = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scDateTo))   $scDateTo   = date('Y-m-d');

$scUserNameSql = "COALESCE(NULLIF(TRIM(CONCAT(IFNULL(u.fname,''), ' ', IFNULL(u.lname,''))), ''), u.username)";

// ໃບທີ່ກຳລັງດຳເນີນຢູ່ (draft / pending / approved)
$od = $pdo->prepare("SELECT id, check_no, status, created_at FROM st_stock_checks WHERE store_id = ? AND status IN ('draft', 'pending', 'approved') ORDER BY id DESC LIMIT 1");
$od->execute([$scStoreId]);
$scOpenDraft = $od->fetch(PDO::FETCH_ASSOC) ?: null;

// ຖ້າບໍ່ມີ id ແລະ ບໍ່ໄດ້ກົດເບິ່ງປະຫວັດ (?view=checks ຫຼື ?view=history)
// ໃຫ້ເປີດໜ້ານັບສິນຄ້າທັນທີ (ເຂົ້າເຖິງງ່າຍ ເຫັນສິນຄ້າໂລດ ບໍ່ຕ້ອງກົດຫຼາຍຂັ້ນຕອນ)
if (empty($_GET['id']) && empty($_GET['view'])) {
    if ($scOpenDraft) {
        $_GET['id'] = $scOpenDraft['id'];
    } else {
        try {
            $pdo->beginTransaction();
            $newDraftId = scCreateCheckInternal($pdo, $scStoreId, $scUserId);
            $pdo->commit();
            scLogCreated($pdo, $newDraftId);
            $_GET['id'] = $newDraftId;
            $od->execute([$scStoreId]);
            $scOpenDraft = $od->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
        }
    }
}

if (!empty($_GET['id']) && empty($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT c.*, $scUserNameSql AS created_by_name,
                                  (SELECT COALESCE(NULLIF(TRIM(CONCAT(IFNULL(au.fname,''), ' ', IFNULL(au.lname,''))), ''), au.username)
                                     FROM st_tbuser au WHERE au.Id = c.approved_by) AS approved_by_name,
                                  (SELECT COALESCE(NULLIF(TRIM(CONCAT(IFNULL(cu.fname,''), ' ', IFNULL(cu.lname,''))), ''), cu.username)
                                     FROM st_tbuser cu WHERE cu.Id = c.completed_by) AS completed_by_name
                           FROM st_stock_checks c LEFT JOIN st_tbuser u ON u.Id = c.user_id
                           WHERE c.id = ? AND c.store_id = ?");
    $stmt->execute([intval($_GET['id']), $scStoreId]);
    $scCheck = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($scCheck) {
        if (in_array($scCheck['status'], ['draft', 'pending'], true)) {
            try {
                $pdo->prepare("UPDATE st_stock_check_items i
                               JOIN st_products p ON p.product_id = i.product_id AND p.store_id = ?
                               SET i.zone = p.zone, i.shelf = p.shelf
                               WHERE i.check_id = ? AND (NOT (i.zone <=> p.zone) OR NOT (i.shelf <=> p.shelf))")
                    ->execute([$scStoreId, $scCheck['id']]);
            } catch (Throwable $e) {}
        }
        $it = $pdo->prepare("SELECT i.*, $scUserNameSql AS counted_by_name
                             FROM st_stock_check_items i LEFT JOIN st_tbuser u ON u.Id = i.counted_by
                             WHERE i.check_id = ?
                             ORDER BY (i.counted_qty IS NOT NULL) DESC, i.counted_at DESC, IFNULL(i.zone, '') ASC, IFNULL(i.shelf, '') ASC, i.product_name ASC, i.id ASC");
        $it->execute([$scCheck['id']]);
        $scItems = $it->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sc_message = t('sc.s222');
        $sc_message_type = 'danger';
    }
}

// ແຖບ: 'checks' = ລາຍການໃບ, 'history' = ປະຫວັດການນັບລາຍສິນຄ້າ (ທຸກໃບ)
$scView = (($_GET['view'] ?? '') === 'history') ? 'history' : 'checks';
$scHistory = [];
$scSearch = trim($_GET['q'] ?? '');
$scHistorySearch = $scSearch;
$scHistoryLimit = 5000;   // ເພດານຂໍ້ມູນທີ່ດຶງ (ສະແດງເປັນໜ້າ, Excel/PDF/ພິມ ໃຊ້ທັງໝົດ)

if (!$scCheck && $scView === 'history') {
    $sql = "SELECT i.*, c.id AS check_id, c.check_no, c.status, $scUserNameSql AS counted_by_name
            FROM st_stock_check_items i
            JOIN st_stock_checks c ON c.id = i.check_id
            LEFT JOIN st_tbuser u ON u.Id = i.counted_by
            WHERE c.store_id = ? AND i.counted_qty IS NOT NULL
              AND DATE(COALESCE(i.counted_at, c.created_at)) BETWEEN ? AND ?";
    $params = [$scStoreId, $scDateFrom, $scDateTo];
    if ($scSearch !== '') {
        if ($scSearch === 'ບໍ່ລະບຸ') {
            $sql .= " AND (i.zone IS NULL OR i.zone = '' OR i.shelf IS NULL OR i.shelf = '' OR i.product_name LIKE ? OR i.barcode LIKE ?)";
            $params[] = '%' . $scSearch . '%';
            $params[] = '%' . $scSearch . '%';
        } else {
            $sql .= " AND (i.product_name LIKE ? OR i.barcode LIKE ? OR i.zone LIKE ? OR i.shelf LIKE ? OR i.product_id = ?)";
            $params[] = '%' . $scSearch . '%';
            $params[] = '%' . $scSearch . '%';
            $params[] = '%' . $scSearch . '%';
            $params[] = '%' . $scSearch . '%';
            $params[] = $scSearch;
        }
    }
    $sql .= " ORDER BY COALESCE(i.counted_at, c.created_at) DESC, i.id DESC LIMIT " . ($scHistoryLimit + 1);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $scHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!$scCheck) {
    if ($scSearch !== '') {
        $kw = '%' . $scSearch . '%';
        $sqlChecks = "SELECT c.*, $scUserNameSql AS created_by_name,
                             COUNT(i.id) AS item_count,
                             SUM(i.counted_qty IS NOT NULL) AS counted_count,
                             SUM(CASE WHEN i.counted_qty < i.system_qty THEN i.system_qty - i.counted_qty ELSE 0 END) AS short_qty,
                             SUM(CASE WHEN i.counted_qty > i.system_qty THEN i.counted_qty - i.system_qty ELSE 0 END) AS over_qty,
                             SUM(CASE WHEN i.counted_qty IS NOT NULL THEN i.counted_qty - i.system_qty ELSE 0 END) AS diff_qty,
                             (SELECT i2.product_name FROM st_stock_check_items i2 WHERE i2.check_id = c.id AND i2.counted_qty IS NOT NULL ORDER BY i2.counted_at DESC, i2.id DESC LIMIT 1) AS last_counted_name,
                             (SELECT i2.barcode FROM st_stock_check_items i2 WHERE i2.check_id = c.id AND i2.counted_qty IS NOT NULL ORDER BY i2.counted_at DESC, i2.id DESC LIMIT 1) AS last_counted_barcode,
                             (SELECT i2.counted_qty FROM st_stock_check_items i2 WHERE i2.check_id = c.id AND i2.counted_qty IS NOT NULL ORDER BY i2.counted_at DESC, i2.id DESC LIMIT 1) AS last_counted_qty
                      FROM st_stock_checks c
                      LEFT JOIN st_tbuser u ON u.Id = c.user_id
                      LEFT JOIN st_stock_check_items i ON i.check_id = c.id
                      WHERE c.store_id = ?
                        AND (
                            (DATE(c.created_at) BETWEEN ? AND ?)
                            OR c.check_no LIKE ?
                            OR EXISTS (SELECT 1 FROM st_stock_check_items si WHERE si.check_id = c.id AND (si.barcode LIKE ? OR si.product_name LIKE ? OR si.product_id = ?))
                        )
                        AND (
                            c.check_no LIKE ?
                            OR c.note LIKE ?
                            OR u.username LIKE ?
                            OR u.fname LIKE ?
                            OR u.lname LIKE ?
                            OR EXISTS (SELECT 1 FROM st_stock_check_items si2 WHERE si2.check_id = c.id AND (si2.barcode LIKE ? OR si2.product_name LIKE ? OR si2.product_id = ?))
                        )
                      GROUP BY c.id
                      ORDER BY c.id DESC";
        $stmt = $pdo->prepare($sqlChecks);
        $stmt->execute([
            $scStoreId,
            $scDateFrom,
            $scDateTo,
            $kw,
            $kw,
            $kw,
            $scSearch,
            $kw,
            $kw,
            $kw,
            $kw,
            $kw,
            $kw,
            $kw,
            $scSearch,
        ]);
        $scChecks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ດຶງສິນຄ້າທີ່ກົງກັບຄຳຄົ້ນຫາ ເພື່ອສະແດງໃນຕາຕະລາງໃຫ້ເຫັນທັນທີ (ສະແກນ ຫຼື ຄົ້ນຫາຊື່ສິນຄ້າຕ້ອງເຫັນ)
        if (!empty($scChecks)) {
            $checkIds = array_column($scChecks, 'id');
            $inClause = implode(',', array_map('intval', $checkIds));
            $stmtItems = $pdo->prepare("SELECT check_id, product_id, barcode, product_name, unit, system_qty, counted_qty
                                        FROM st_stock_check_items
                                        WHERE check_id IN ($inClause)
                                          AND (product_name LIKE ? OR barcode LIKE ? OR product_id = ?)
                                        ORDER BY id ASC");
            $stmtItems->execute([$kw, $kw, $scSearch]);
            $matchedItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            $scMatchedByCheck = [];
            foreach ($matchedItems as $mi) {
                $scMatchedByCheck[$mi['check_id']][] = $mi;
            }
            foreach ($scChecks as &$c) {
                $c['matched_items'] = $scMatchedByCheck[$c['id']] ?? [];
            }
            unset($c);
        }
    } else {
        $stmt = $pdo->prepare("SELECT c.*, $scUserNameSql AS created_by_name,
                                      COUNT(i.id) AS item_count,
                                      SUM(i.counted_qty IS NOT NULL) AS counted_count,
                                      SUM(CASE WHEN i.counted_qty < i.system_qty THEN i.system_qty - i.counted_qty ELSE 0 END) AS short_qty,
                                      SUM(CASE WHEN i.counted_qty > i.system_qty THEN i.counted_qty - i.system_qty ELSE 0 END) AS over_qty,
                                      SUM(CASE WHEN i.counted_qty IS NOT NULL THEN i.counted_qty - i.system_qty ELSE 0 END) AS diff_qty,
                                      (SELECT i2.product_name FROM st_stock_check_items i2 WHERE i2.check_id = c.id AND i2.counted_qty IS NOT NULL ORDER BY i2.counted_at DESC, i2.id DESC LIMIT 1) AS last_counted_name,
                                      (SELECT i2.barcode FROM st_stock_check_items i2 WHERE i2.check_id = c.id AND i2.counted_qty IS NOT NULL ORDER BY i2.counted_at DESC, i2.id DESC LIMIT 1) AS last_counted_barcode,
                                      (SELECT i2.counted_qty FROM st_stock_check_items i2 WHERE i2.check_id = c.id AND i2.counted_qty IS NOT NULL ORDER BY i2.counted_at DESC, i2.id DESC LIMIT 1) AS last_counted_qty
                               FROM st_stock_checks c
                               LEFT JOIN st_tbuser u ON u.Id = c.user_id
                               LEFT JOIN st_stock_check_items i ON i.check_id = c.id
                               WHERE c.store_id = ? AND DATE(c.created_at) BETWEEN ? AND ?
                               GROUP BY c.id
                               ORDER BY c.id DESC");
        $stmt->execute([$scStoreId, $scDateFrom, $scDateTo]);
        $scChecks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// ໃບທີ່ກຳລັງດຳເນີນຢູ່ (draft / pending / approved) — ໃຊ້ທັງແຖບແຈ້ງເຕືອນ ແລະ ແຖບເມນູ "ນັບສະຕັອກ" (tabs.php) ໃນທຸກໜ້າ
$od = $pdo->prepare("SELECT id, check_no, status, created_at FROM st_stock_checks WHERE store_id = ? AND status IN ('draft', 'pending', 'approved') ORDER BY id DESC LIMIT 1");
$od->execute([$scStoreId]);
$scOpenDraft = $od->fetch(PDO::FETCH_ASSOC) ?: null;

// ແບ່ງໜ້າ (ລາຍການໃບ ແລະ ປະຫວັດ) — ດຶງທັງໝົດມາແລ້ວຕັດເປັນໜ້າ, ເພື່ອໃຫ້ Excel/PDF/ພິມ ໄດ້ຂໍ້ມູນຄົບທຸກໜ້າ
// ຈຳນວນແຖວຕໍ່ໜ້າ: ເລືອກໄດ້ຈາກບັອກ "ສະແດງ N ລາຍການ" (?per_page=10|20|50|100|all)
$scPerPageOptions = [10, 20, 50, 100];
$scPerPageParam = $_GET['per_page'] ?? '';
$scAllRows = ($scView === 'history') ? array_slice($scHistory, 0, $scHistoryLimit) : $scChecks;
$scTotalRows = count($scAllRows);
if ($scPerPageParam === 'all') {
    $scPerPage = max(1, $scTotalRows);
} elseif (in_array((int)$scPerPageParam, $scPerPageOptions, true)) {
    $scPerPage = (int)$scPerPageParam;
} else {
    $scPerPage = 20;
    $scPerPageParam = '20';
}
$scTotalPages = max(1, (int)ceil($scTotalRows / $scPerPage));
$scPage = min($scTotalPages, max(1, intval($_GET['page'] ?? 1)));
$scPageOffset = ($scPage - 1) * $scPerPage;
$scPageRows = array_slice($scAllRows, $scPageOffset, $scPerPage);

// ຊື່ສາຂາ ສຳລັບຫົວລາຍງານທີ່ພິມ/Excel/PDF
$scStoreName = '';
try {
    $sn = $pdo->prepare("SELECT store_name FROM st_tbstore WHERE store_id = ?");
    $sn->execute([$scStoreId]);
    $scStoreName = (string)$sn->fetchColumn();
} catch (Throwable $e) {}
