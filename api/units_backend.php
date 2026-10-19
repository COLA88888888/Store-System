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

$unit_message = '';
$unit_message_type = '';

// Handle Unit Form Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $unitActiveStoreId = getActiveStoreId($pdo);

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
                        $unit_message = 'ຜິດພາດ: ລະຫັດຫົວໜ່ວຍ ' . $unit_id . ' ນີ້ມີໃນລະບົບແລ້ວ! ກະລຸນາປ້ອນລະຫັດອື່ນ.';
                        $unit_message_type = 'danger';
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO st_units (unit_id, unit_name, description, store_id) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$unit_id, $name, $desc, $unitActiveStoreId]);

                        $unit_message = 'ເພີ່ມຫົວໜ່ວຍສຳເລັດ!';
                        $unit_message_type = 'success';
                        logActivity($pdo, "ເພີ່ມຫົວໜ່ວຍ", "ລະຫັດ: $unit_id, ຊື່: $name");
                    }
                } catch (Exception $e) {
                    $unit_message = 'ຜິດພາດ: ລະຫັດນີ້ອາດຊ້ຳກັນ ຫຼື ' . $e->getMessage();
                    $unit_message_type = 'danger';
                }
            } else {
                $unit_message = 'ກະລຸນາປ້ອນລະຫັດ ແລະ ຊື່ຫົວໜ່ວຍໃຫ້ຄົບ!';
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
                            throw new Exception("ລະຫັດຫົວໜ່ວຍ $new_id ນີ້ມີໃນລະບົບແລ້ວ!");
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

                    $unit_message = 'ແກ້ໄຂຫົວໜ່ວຍສຳເລັດ!';
                    $unit_message_type = 'success';
                    logActivity($pdo, "ແກ້ໄຂຫົວໜ່ວຍ", "ID: $new_id, ຊື່ໃໝ່: $name");
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $unit_message = 'ຜິດພາດ: ' . $e->getMessage();
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
                    $unit_message = 'ບໍ່ສາມາດລົບຫົວໜ່ວຍນີ້ໄດ້! ເພາະມີລາຍການສິນຄ້າໃຊ້ຫົວໜ່ວຍນີ້ ' . $count . ' ລາຍການ.';
                    $unit_message_type = 'danger';
                } else {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM st_units WHERE unit_id = ?");
                        $stmt->execute([$id]);
                        $unit_message = 'ລົບຫົວໜ່ວຍສຳເລັດ!';
                        $unit_message_type = 'success';
                        logActivity($pdo, "ລົບຫົວໜ່ວຍ", "ID: $id");
                    } catch (Exception $e) {
                        $unit_message = 'ຜິດພາດ: ' . $e->getMessage();
                        $unit_message_type = 'danger';
                    }
                }
            }
        }
    }
}

// Fetch Units shared across branches, ordered sequentially by unit_id ASC
$activeStoreIdForUnit = getActiveStoreId($pdo);
$stmtUnit = $pdo->query("SELECT u.*, (SELECT COUNT(*) FROM st_products p WHERE p.unit_id = u.unit_id) AS product_count FROM st_units u ORDER BY u.unit_id ASC");
$allUnits = $stmtUnit->fetchAll();

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
