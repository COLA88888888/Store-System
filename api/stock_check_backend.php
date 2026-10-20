<?php
/**
 * ເຊັກສະຕັອກ — controller ຂອງ pages/stock_check/stock_check.php ແລະ ເປົ້າໝາຍ AJAX ໃນໄຟລ໌ດຽວກັນ.
 *
 * ຂັ້ນຕອນ: ເລີ່ມນັບ (create_check) → ລະບົບຖ່າຍຈຳນວນໃນລະບົບ (system_qty) ຂອງທຸກສິນຄ້າທີ່ຕັດສະຕັອກໄວ້
 * → ພະນັກງານປ້ອນ/ສະແກນຈຳນວນທີ່ນັບໄດ້ ແລະ ບັນທຶກ (save_counts) ໄດ້ຫຼາຍເທື່ອ
 * → ຢືນຢັນ (complete_check): ປັບ st_products.qty ດ້ວຍສ່ວນຕ່າງ (counted - system) ຂອງລາຍການທີ່ນັບແລ້ວ.
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
    && in_array($_POST['action'] ?? '', ['save_counts', 'submit_for_approval', 'approve_adjustment', 'reject_adjustment', 'complete_check', 'delete_check'], true);

// ສິດ:
//   ຜູ້ບໍລິຫານ / ຫົວໜ້າ ($scIsBoss): ອະນຸມັດໃຫ້ປັບສະຕັອກ (approve_adjustment) ຫຼື ຕີກັບໃຫ້ນັບໃໝ່
//   stock_check (ຜູ້ຈັດການ/ພະນັກງານເຊັກສະຕັອກ): ເລີ່ມນັບໃໝ່, ນັບ, ສົ່ງຂໍອະນຸມັດ, ແລະ ກົດປັບສະຕັອກຫຼັງຈາກຫົວໜ້າອະນຸມັດແລ້ວ
//   stock_count (ພະນັກງານນັບ): ປ້ອນ/ບັນທຶກຈຳນວນຕົວຈິງ, ສົ່ງຂໍອະນຸມັດ, ແລະ ກົດປັບສະຕັອກຫຼັງຈາກຫົວໜ້າອະນຸມັດແລ້ວ
$scIsBoss     = !empty($_SESSION['user_id']) && (isOwnerUser() || isAdminAccount($pdo, $_SESSION['user_id']));
$scIsManager  = !empty($_SESSION['user_id']) && ($scIsBoss || hasPermission('stock_check'));
$scIsCounter  = !empty($_SESSION['user_id']) && hasPermission('stock_count');
$scCanStart   = $scIsBoss || ($scIsManager && hasPermission('stock_check', 'add'));
$scCanConfirm = $scIsBoss || ($scIsManager && hasPermission('stock_check', 'edit'));
$scCanApprove = $scIsBoss || $scCanConfirm;   // ຫົວໜ້າ/ຜູ້ຈັດການ ທີ່ມີສິດອະນຸມັດໃບເຊັກສະຕັອກ
$scCanDelete  = $scIsBoss || ($scIsManager && hasPermission('stock_check', 'del'));
$scCanCount   = $scCanConfirm || ($scIsCounter && hasPermission('stock_count', 'edit'));
$scSeeResults = $scIsManager;   // ເຫັນຜົນການນັບຂອງໃບທີ່ຍັງນັບຢູ່ (ຫຼື ເມື່ອຫົວໜ້າອະນຸມັດແລ້ວ)

if (empty($_SESSION['user_id']) || (!$scIsManager && !$scIsCounter)) {
    if ($scIsAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'ທ່ານບໍ່ມີສິດເຂົ້າໃຊ້ເມນູນີ້']);
        exit();
    }
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$scStoreId = getActiveStoreId($pdo);
$scUserId  = intval($_SESSION['user_id']);

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
    $rows = $pdo->query("SELECT i.id, i.counted_qty, i.counted_at,
                                COALESCE(NULLIF(TRIM(CONCAT(IFNULL(u.fname,''), ' ', IFNULL(u.lname,''))), ''), u.username) AS counted_by_name
                         FROM st_stock_check_items i LEFT JOIN st_tbuser u ON u.Id = i.counted_by
                         WHERE i.check_id = " . intval($checkId) . " AND i.id IN ($in)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['counted_at'] = $r['counted_at'] ? date('d/m H:i', strtotime($r['counted_at'])) : null;
    }
    return $rows;
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
    ][$action] ?? false;
    if (!$allowed) {
        echo json_encode(['success' => false, 'message' => 'ທ່ານບໍ່ມີສິດເຮັດລາຍການນີ້']);
        exit();
    }

    try {
        $pdo->beginTransaction();
        $check = scLoadCheck($pdo, $checkId, $scStoreId, true);
        if (!$check) {
            throw new Exception('ບໍ່ພົບໃບເຊັກສະຕັອກນີ້');
        }
        if ($check['status'] === 'completed') {
            throw new Exception('ໃບເຊັກສະຕັອກນີ້ນັບແລ້ວ (ປັບສະຕັອກແລ້ວ) ບໍ່ສາມາດແກ້ໄຂໄດ້');
        }

        if ($action === 'delete_check') {
            $pdo->prepare("DELETE FROM st_stock_check_items WHERE check_id = ?")->execute([$checkId]);
            $pdo->prepare("DELETE FROM st_stock_checks WHERE id = ?")->execute([$checkId]);
            $pdo->commit();
            logActivity($pdo, 'ລຶບໃບເຊັກສະຕັອກ', 'ເລກທີ: ' . $check['check_no']);
            echo json_encode(['success' => true, 'message' => 'ລຶບໃບເຊັກສະຕັອກສຳເລັດ']);
            exit();
        }

        $savedRows = !empty($items) ? scSaveItems($pdo, $checkId, $items, $scUserId) : [];
        if (isset($_POST['note'])) {
            $pdo->prepare("UPDATE st_stock_checks SET note = ? WHERE id = ?")
                ->execute([mb_substr(trim($_POST['note']), 0, 255), $checkId]);
        }

        if ($action === 'save_counts') {
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'ບັນທຶກສຳເລັດ', 'saved_at' => date('H:i:s'), 'rows' => $savedRows]);
            exit();
        }

        // ສົ່ງໃຫ້ຫົວໜ້າກວດສອບ ແລະ ອະນຸຍາດປັບສະຕັອກ
        if ($action === 'submit_for_approval') {
            $cnt = (int)$pdo->query("SELECT COUNT(*) FROM st_stock_check_items WHERE check_id = " . intval($checkId) . " AND counted_qty IS NOT NULL")->fetchColumn();
            if ($cnt <= 0) {
                throw new Exception('ກະລຸນານັບ ແລະ ບັນທຶກສິນຄ້າຢ່າງໜ້ອຍ 1 ລາຍການກ່ອນສົ່ງຂໍອະນຸມັດ');
            }
            // submitted_by/at: ຫົວໜ້າໄດ້ແຈ້ງເຕືອນວ່າໃຜສົ່ງ, ແລະ ອະນຸມັດແລ້ວແຈ້ງກັບຫາຜູ້ສົ່ງ (config/helpers/stock_check_alerts.php)
            $pdo->prepare("UPDATE st_stock_checks SET status = 'pending', submitted_by = ?, submitted_at = NOW() WHERE id = ?")->execute([$scUserId, $checkId]);
            $pdo->commit();
            logActivity($pdo, 'ສົ່ງໃບເຊັກສະຕັອກຂໍອະນຸມັດ', 'ເລກທີ: ' . $check['check_no']);
            echo json_encode(['success' => true, 'message' => 'ສົ່ງໃຫ້ຫົວໜ້າກວດສອບແລ້ວ! ກະລຸນາລໍຖ້າຫົວໜ້າອະນຸຍາດກ່ອນປັບສະຕັອກ']);
            exit();
        }

        // ຫົວໜ້າອະນຸຍາດໃຫ້ພະນັກງານປັບສະຕັອກໃຫ້ຕົງກັນ
        if ($action === 'approve_adjustment') {
            $pdo->prepare("UPDATE st_stock_checks SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?")
                ->execute([$scUserId, $checkId]);
            $pdo->commit();
            logActivity($pdo, 'ຫົວໜ້າອະນຸມັດໃບເຊັກສະຕັອກ', 'ເລກທີ: ' . $check['check_no']);
            echo json_encode(['success' => true, 'message' => 'ອະນຸຍາດແລ້ວ! ຕອນນີ້ສາມາດກົດປັບສະຕັອກໃຫ້ຕົງກັນໄດ້ເລີຍ']);
            exit();
        }

        // ຫົວໜ້າຕີກັບໃຫ້ນັບໃໝ່ / ແກ້ໄຂຄືນ
        if ($action === 'reject_adjustment') {
            $pdo->prepare("UPDATE st_stock_checks SET status = 'draft', approved_by = NULL, approved_at = NULL WHERE id = ?")
                ->execute([$checkId]);
            $pdo->commit();
            logActivity($pdo, 'ສົ່ງກັບໃຫ້ນັບສະຕັອກໃໝ່', 'ເລກທີ: ' . $check['check_no']);
            echo json_encode(['success' => true, 'message' => 'ສົ່ງກັບໃຫ້ພະນັກງານນັບ/ແກ້ໄຂໃໝ່ແລ້ວ']);
            exit();
        }

        // complete_check: ຕ້ອງໄດ້ຮັບອະນຸຍາດຈາກຫົວໜ້າກ່ອນ (status = 'approved') ຈຶ່ງສາມາດປັບສະຕັອກໄດ້
        if ($check['status'] !== 'approved') {
            throw new Exception('ຕ້ອງໄດ້ຮັບອະນຸຍາດຈາກຫົວໜ້າກ່ອນ ຈຶ່ງສາມາດປັບສະຕັອກໄດ້!');
        }
        $rows = $pdo->prepare("SELECT product_id, product_name, system_qty, counted_qty FROM st_stock_check_items
                               WHERE check_id = ? AND counted_qty IS NOT NULL AND counted_qty <> system_qty");
        $rows->execute([$checkId]);
        $adj = $pdo->prepare("UPDATE st_products SET qty = qty + ? WHERE product_id = ? AND store_id = ?");
        $adjusted = 0;
        foreach ($rows->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $adj->execute([intval($r['counted_qty']) - intval($r['system_qty']), $r['product_id'], $scStoreId]);
            $adjusted++;
        }
        $pdo->prepare("UPDATE st_stock_checks SET status = 'completed', completed_by = ?, completed_at = NOW() WHERE id = ?")
            ->execute([$scUserId, $checkId]);
        $pdo->commit();

        logActivity($pdo, 'ຢືນຢັນເຊັກສະຕັອກ', 'ເລກທີ: ' . $check['check_no'] . ', ປັບສະຕັອກ ' . $adjusted . ' ລາຍການ');
        echo json_encode(['success' => true, 'adjusted' => $adjusted,
            'message' => 'ນັບແລ້ວ! ປັບສະຕັອກແລ້ວ ' . $adjusted . ' ລາຍການ']);
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
        $sc_message = 'ທ່ານບໍ່ມີສິດເລີ່ມເຊັກສະຕັອກ';
        $sc_message_type = 'danger';
    } else {
        try {
            $draft = $pdo->prepare("SELECT id FROM st_stock_checks WHERE store_id = ? AND status IN ('draft', 'pending', 'approved') ORDER BY id DESC LIMIT 1");
            $draft->execute([$scStoreId]);
            $draftId = $draft->fetchColumn();
            if (!$draftId) {
                $pdo->beginTransaction();
                // ເລກທີ SC-ປີເດືອນວັນ-<ລະຫັດສາຂາ><ລຳດັບ 4 ຫຼັກຂອງມື້ນັ້ນ>
                // ເຊັ່ນ ສາຂາ 1 → SC-20261020-10001, ສາຂາ 2 → SC-20261020-20001
                $sc = $pdo->prepare("SELECT store_code FROM st_tbstore WHERE store_id = ?");
                $sc->execute([$scStoreId]);
                $storeCode = preg_replace('/[^0-9A-Za-z]/', '', (string)$sc->fetchColumn());
                if ($storeCode === '') $storeCode = (string)$scStoreId;
                $prefix = 'SC-' . date('Ymd') . '-' . $storeCode;
                // ລຳດັບຖັດໄປ = ເລກສູງສຸດຂອງມື້ນີ້ + 1 (ບໍ່ໃຊ້ COUNT ເພາະໃບທີ່ລຶບອອກຈະເຮັດໃຫ້ເລກຊ້ຳ)
                $mx = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(check_no, ?) AS UNSIGNED)) FROM st_stock_checks
                                     WHERE store_id = ? AND check_no LIKE ? AND CHAR_LENGTH(check_no) = ?");
                $mx->execute([strlen($prefix) + 1, $scStoreId, $prefix . '%', strlen($prefix) + 4]);
                $checkNo = $prefix . str_pad((string)((int)$mx->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);

                $pdo->prepare("INSERT INTO st_stock_checks (check_no, store_id, status, note, user_id, created_at) VALUES (?, ?, 'draft', ?, ?, NOW())")
                    ->execute([$checkNo, $scStoreId, mb_substr(trim($_POST['note'] ?? ''), 0, 255) ?: null, $scUserId]);
                $draftId = (int)$pdo->lastInsertId();

                // ສິນຄ້າບໍລິການ (cut_qty = 0) ບໍ່ມີສະຕັອກ — ບໍ່ຕ້ອງນັບ
                $pdo->prepare("INSERT INTO st_stock_check_items (check_id, product_id, barcode, product_name, unit, cost_price, system_qty)
                               SELECT ?, product_id, barcode, product_name, unit, IFNULL(bprice, 0), IFNULL(qty, 0)
                               FROM st_products WHERE store_id = ? AND IFNULL(cut_qty, 1) <> 0
                               ORDER BY product_name")
                    ->execute([$draftId, $scStoreId]);
                $pdo->commit();
                logActivity($pdo, 'ເລີ່ມເຊັກສະຕັອກ', 'ເລກທີ: ' . $checkNo);
            }
            header('Location: stock_check.php?id=' . intval($draftId));
            exit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $sc_message = 'ຜິດພາດ: ' . $e->getMessage();
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

if (!empty($_GET['id'])) {
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
        $it = $pdo->prepare("SELECT i.*, $scUserNameSql AS counted_by_name
                             FROM st_stock_check_items i LEFT JOIN st_tbuser u ON u.Id = i.counted_by
                             WHERE i.check_id = ? ORDER BY i.product_name, i.id");
        $it->execute([$scCheck['id']]);
        $scItems = $it->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $sc_message = 'ບໍ່ພົບໃບເຊັກສະຕັອກນີ້ໃນສາຂາທີ່ເລືອກ';
        $sc_message_type = 'danger';
    }
}

// ແຖບ: 'checks' = ລາຍການໃບ, 'history' = ປະຫວັດການນັບລາຍສິນຄ້າ (ທຸກໃບ)
$scView = (($_GET['view'] ?? '') === 'history') ? 'history' : 'checks';
$scHistory = [];
$scHistorySearch = trim($_GET['q'] ?? '');
$scHistoryLimit = 5000;   // ເພດານຂໍ້ມູນທີ່ດຶງ (ສະແດງເປັນໜ້າ, Excel/PDF/ພິມ ໃຊ້ທັງໝົດ)

if (!$scCheck && $scView === 'history') {
    $sql = "SELECT i.*, c.id AS check_id, c.check_no, c.status, $scUserNameSql AS counted_by_name
            FROM st_stock_check_items i
            JOIN st_stock_checks c ON c.id = i.check_id
            LEFT JOIN st_tbuser u ON u.Id = i.counted_by
            WHERE c.store_id = ? AND i.counted_qty IS NOT NULL
              AND DATE(COALESCE(i.counted_at, c.created_at)) BETWEEN ? AND ?";
    $params = [$scStoreId, $scDateFrom, $scDateTo];
    if ($scHistorySearch !== '') {
        $sql .= " AND (i.product_name LIKE ? OR i.barcode LIKE ? OR i.product_id = ?)";
        $params[] = '%' . $scHistorySearch . '%';
        $params[] = '%' . $scHistorySearch . '%';
        $params[] = $scHistorySearch;
    }
    $sql .= " ORDER BY COALESCE(i.counted_at, c.created_at) DESC, i.id DESC LIMIT " . ($scHistoryLimit + 1);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $scHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (!$scCheck) {
    $stmt = $pdo->prepare("SELECT c.*, $scUserNameSql AS created_by_name,
                                  COUNT(i.id) AS item_count,
                                  SUM(i.counted_qty IS NOT NULL) AS counted_count,
                                  SUM(CASE WHEN i.counted_qty < i.system_qty THEN i.system_qty - i.counted_qty ELSE 0 END) AS short_qty,
                                  SUM(CASE WHEN i.counted_qty > i.system_qty THEN i.counted_qty - i.system_qty ELSE 0 END) AS over_qty,
                                  SUM(CASE WHEN i.counted_qty IS NOT NULL THEN i.counted_qty - i.system_qty ELSE 0 END) AS diff_qty
                           FROM st_stock_checks c
                           LEFT JOIN st_tbuser u ON u.Id = c.user_id
                           LEFT JOIN st_stock_check_items i ON i.check_id = c.id
                           WHERE c.store_id = ? AND DATE(c.created_at) BETWEEN ? AND ?
                           GROUP BY c.id
                           ORDER BY c.id DESC");
    $stmt->execute([$scStoreId, $scDateFrom, $scDateTo]);
    $scChecks = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
