<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../config/db.php';

// Units now has its own permission module (wired the same way as categories in
// pages/permissions), separate from hasPermission('categories', ...).
if (empty($_SESSION['user_id']) || (!hasPermission('units'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

// User store and permissions
$userStoreId = intval($_SESSION['store_id'] ?? 1);
$currentStoreId = intval($_SESSION['active_store_id'] ?? $userStoreId);
$canAllBranches = (
    ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' ||
    strtolower($_SESSION['status'] ?? '') === 'admin' ||
    ($_SESSION['user_id'] ?? 0) == 1 ||
    !empty($_SESSION['can_all_branches'])
);
$stores = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);

if (!$canAllBranches) {
    $selectedStoreId = $userStoreId;
} else {
    $selectedStoreId = isset($_REQUEST['store_id']) ? intval($_REQUEST['store_id']) : $currentStoreId;
}
$activeStoreIdForUnit = $selectedStoreId;

$unit_message = '';
$unit_message_type = '';

// Handle Unit Form Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $unitActiveStoreId = !empty($_POST['store_id']) ? intval($_POST['store_id']) : $selectedStoreId;

        if ($_POST['action'] === 'add_unit') {
            $name = trim($_POST['unit_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $unit_id = isset($_POST['unit_id']) && $_POST['unit_id'] !== '' ? intval($_POST['unit_id']) : null;
            if ($name !== '' && $unit_id !== null && $unit_id > 0) {
                try {
                    // Check if unit_id already exists to prevent duplicate key error
                    $checkExists = $pdo->prepare("SELECT COUNT(*) FROM st_units WHERE unit_id = ?");
                    $checkExists->execute([$unit_id]);
                    if ((int)$checkExists->fetchColumn() > 0) {
                        $unit_message = (tl('ຜິດພາດ: ລະຫັດຫົວໜ່ວຍ') . ' ') . $unit_id . (' ' . tl('ນີ້ມີໃນລະບົບແລ້ວ! ກະລຸນາປ້ອນລະຫັດອື່ນ.'));
                        $unit_message_type = 'danger';
                    } else {
                        // branch_no = ລຳດັບໃນສາຂານີ້ → ລະຫັດທີ່ສະແດງ "<ລະຫັດສາຂາ>-<branch_no>"
                        $stmt = $pdo->prepare("INSERT INTO st_units (unit_id, unit_name, description, store_id, branch_no) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$unit_id, $name, $desc, $unitActiveStoreId, nextBranchNo($pdo, 'st_units', $unitActiveStoreId)]);

                        $unit_message = tl('ເພີ່ມຫົວໜ່ວຍສຳເລັດ!');
                        $unit_message_type = 'success';
                        logActivity($pdo, "ເພີ່ມຫົວໜ່ວຍ", "ລະຫັດ: $unit_id, ຊື່: $name");
                    }
                } catch (Exception $e) {
                    $unit_message = (tl('ຜິດພາດ: ລະຫັດນີ້ອາດຊ້ຳກັນ ຫຼື') . ' ') . $e->getMessage();
                    $unit_message_type = 'danger';
                }
            } else {
                $unit_message = tl('ກະລຸນາປ້ອນລະຫັດ ແລະ ຊື່ຫົວໜ່ວຍໃຫ້ຄົບ!');
                $unit_message_type = 'danger';
            }
        }
        elseif ($_POST['action'] === 'edit_unit') {
            $id = intval($_POST['unit_id'] ?? 0);
            $new_id = isset($_POST['new_unit_id']) && $_POST['new_unit_id'] !== '' ? intval($_POST['new_unit_id']) : $id;
            $name = trim($_POST['unit_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            if ($id > 0 && $new_id > 0 && $name !== '') {
                try {
                    // If changing unit_id, ensure new_id is not already taken
                    if ($new_id !== $id) {
                        $chk = $pdo->prepare("SELECT COUNT(*) FROM st_units WHERE unit_id = ?");
                        $chk->execute([$new_id]);
                        if ((int)$chk->fetchColumn() > 0) {
                            throw new Exception(("" . tl('ລະຫັດຫົວໜ່ວຍ') . " $new_id " . tl('ນີ້ມີໃນລະບົບແລ້ວ!') . ""));
                        }
                    }

                    $pdo->beginTransaction();
                    if ($new_id !== $id) {
                        $pdo->prepare("UPDATE st_products SET unit_id = ?, unit = ? WHERE unit_id = ?")->execute([$new_id, $name, $id]);
                        $stmt = $pdo->prepare("UPDATE st_units SET unit_id = ?, unit_name = ?, description = ? WHERE unit_id = ?");
                        $stmt->execute([$new_id, $name, $desc, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE st_units SET unit_name = ?, description = ? WHERE unit_id = ?");
                        $stmt->execute([$name, $desc, $id]);
                        $pdo->prepare("UPDATE st_products SET unit = ? WHERE unit_id = ?")->execute([$name, $id]);
                    }
                    $pdo->commit();

                    $unit_message = tl('ແກ້ໄຂຫົວໜ່ວຍສຳເລັດ!');
                    $unit_message_type = 'success';
                    logActivity($pdo, "ແກ້ໄຂຫົວໜ່ວຍ", "ID: $new_id, ຊື່ໃໝ່: $name");
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $unit_message = (tl('ຜິດພາດ:') . ' ') . $e->getMessage();
                    $unit_message_type = 'danger';
                }
            }
        }
        elseif ($_POST['action'] === 'delete_unit') {
            $id = intval($_POST['unit_id'] ?? 0);
            if ($id > 0) {
                // products.unit_id is the real FK link, so "in use" is a direct ID match
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM st_products WHERE unit_id = ?");
                $checkStmt->execute([$id]);
                $count = (int)$checkStmt->fetchColumn();

                if ($count > 0) {
                    $unit_message = (tl('ບໍ່ສາມາດລົບຫົວໜ່ວຍນີ້ໄດ້! ເພາະມີລາຍການສິນຄ້າໃຊ້ຫົວໜ່ວຍນີ້') . ' ') . $count . (' ' . tl('ລາຍການ.'));
                    $unit_message_type = 'danger';
                } else {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM st_units WHERE unit_id = ?");
                        $stmt->execute([$id]);
                        $unit_message = tl('ລົບຫົວໜ່ວຍສຳເລັດ!');
                        $unit_message_type = 'success';
                        logActivity($pdo, "ລົບຫົວໜ່ວຍ", "ID: $id");
                    } catch (Exception $e) {
                        $unit_message = (tl('ຜິດພາດ:') . ' ') . $e->getMessage();
                        $unit_message_type = 'danger';
                    }
                }
            }
        }
    }
}

// Fetch Units of the selected branch only (ສາຂາໃດສາຂານັ້ນ — ບໍ່ລວມກັບສາຂາອື່ນ),
// ordered by its per-branch number (1-1, 1-2 …)
$activeStoreIdForUnit = $selectedStoreId;
ensureBranchNos($pdo, 'st_units');
$stmtUnit = $pdo->prepare("SELECT u.*, (SELECT COUNT(*) FROM st_products p WHERE p.unit_id = u.unit_id) AS product_count FROM st_units u WHERE u.store_id = ? ORDER BY u.branch_no ASC, u.unit_id ASC");
$stmtUnit->execute([$activeStoreIdForUnit]);
$allUnits = $stmtUnit->fetchAll();
foreach ($allUnits as &$__u) {
    $__u['display_code'] = branchCodeLabel($pdo, $__u['store_id'] ?? 0, $__u['branch_no'] ?? null, $__u['unit_id']);
}
unset($__u);
// ລະຫັດທີ່ຫົວໜ່ວຍໃໝ່ຈະໄດ້ ໃນສາຂາທີ່ກຳລັງເຮັດວຽກ (ສະແດງໃນຟອມເພີ່ມ)
$next_unit_code = branchCodeLabel($pdo, $activeStoreIdForUnit, nextBranchNo($pdo, 'st_units', $activeStoreIdForUnit));

// Sequential next available unit_id (fill lowest unused integer starting from 1)
$usedUnitIds = $pdo->query("SELECT unit_id FROM st_units ORDER BY unit_id ASC")->fetchAll(PDO::FETCH_COLUMN);
$next_unit_id = 1;
foreach ($usedUnitIds as $uid) {
    if ((int)$uid === $next_unit_id) {
        $next_unit_id++;
    } elseif ((int)$uid > $next_unit_id) {
        break;
    }
}
