<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../config/db.php';

// Check permissions
if (empty($_SESSION['user_id']) || (!hasPermission('categories'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

// Handle Category Form Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $catActiveStoreId = getActiveStoreId($pdo);

        if ($_POST['action'] === 'add_category') {
            $name = trim($_POST['category_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $cat_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null;
            // Kitchen-ticket printer for this category's items (per branch). Empty = none.
            $printer_id = !empty($_POST['printer_id']) ? intval($_POST['printer_id']) : null;
            if ($name !== '' && $cat_id !== null && $cat_id > 0) {
                try {
                    // Check if category_id already exists to prevent duplicate entry error
                    $checkExists = $pdo->prepare("SELECT COUNT(*) FROM st_categories WHERE category_id = ?");
                    $checkExists->execute([$cat_id]);
                    if ((int)$checkExists->fetchColumn() > 0) {
                        $message = (tl('ຜິດພາດ: ລະຫັດປະເພດສິນຄ້າ') . ' ') . $cat_id . (' ' . tl('ນີ້ມີໃນລະບົບແລ້ວ! ກະລຸນາປ້ອນລະຫັດອື່ນ.'));
                        $message_type = 'danger';
                    } else {
                        // branch_no = ລຳດັບໃນສາຂານີ້ → ລະຫັດທີ່ສະແດງ "<ລະຫັດສາຂາ>-<branch_no>"
                        $stmt = $pdo->prepare("INSERT INTO st_categories (category_id, category_name, description, store_id, branch_no, printer_id) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$cat_id, $name, $desc, $catActiveStoreId, nextBranchNo($pdo, 'st_categories', $catActiveStoreId), $printer_id]);

                        $message = tl('ເພີ່ມປະເພດສິນຄ້າສຳເລັດ!');
                        $message_type = 'success';
                        logActivity($pdo, "ເພີ່ມປະເພດສິນຄ້າ", "ລະຫັດ: $cat_id, ຊື່: $name");
                    }
                } catch (Exception $e) {
                    $message = (tl('ຜິດພາດ: ລະຫັດນີ້ອາດຊ້ຳກັນ ຫຼື') . ' ') . $e->getMessage();
                    $message_type = 'danger';
                }
            } else {
                $message = tl('ກະລຸນາປ້ອນລະຫັດ ແລະ ຊື່ປະເພດສິນຄ້າໃຫ້ຄົບ!');
                $message_type = 'danger';
            }
        }
        elseif ($_POST['action'] === 'edit_category') {
            $id = intval($_POST['category_id'] ?? 0);
            $new_id = isset($_POST['new_category_id']) && $_POST['new_category_id'] !== '' ? intval($_POST['new_category_id']) : $id;
            $name = trim($_POST['category_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $printer_id = !empty($_POST['printer_id']) ? intval($_POST['printer_id']) : null;
            if ($id > 0 && $new_id > 0 && $name !== '') {
                try {
                    // If changing category_id, ensure new_id is not already taken
                    if ($new_id !== $id) {
                        $chk = $pdo->prepare("SELECT COUNT(*) FROM st_categories WHERE category_id = ?");
                        $chk->execute([$new_id]);
                        if ((int)$chk->fetchColumn() > 0) {
                            throw new Exception(("" . tl('ລະຫັດປະເພດສິນຄ້າ') . " $new_id " . tl('ນີ້ມີໃນລະບົບແລ້ວ!') . ""));
                        }
                    }

                    $pdo->beginTransaction();
                    if ($new_id !== $id) {
                        $pdo->prepare("UPDATE st_products SET category_id = ? WHERE category_id = ?")->execute([$new_id, $id]);
                        $stmt = $pdo->prepare("UPDATE st_categories SET category_id = ?, category_name = ?, description = ?, printer_id = ? WHERE category_id = ?");
                        $stmt->execute([$new_id, $name, $desc, $printer_id, $id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE st_categories SET category_name = ?, description = ?, printer_id = ? WHERE category_id = ?");
                        $stmt->execute([$name, $desc, $printer_id, $id]);
                    }
                    $pdo->commit();

                    $message = tl('ແກ້ໄຂປະເພດສິນຄ້າສຳເລັດ!');
                    $message_type = 'success';
                    logActivity($pdo, "ແກ້ໄຂປະເພດສິນຄ້າ", "ID: $new_id, ຊື່ໃໝ່: $name");
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $message = (tl('ຜິດພາດ:') . ' ') . $e->getMessage();
                    $message_type = 'danger';
                }
            }
        }
        elseif ($_POST['action'] === 'bulk_set_printer') {
            // Route every category in this branch to one printer in a single click, instead of
            // opening each category's edit form when a shop has many categories but wants them
            // all going to the same kitchen printer.
            if (hasPermission('categories', 'edit')) {
                $printer_id = !empty($_POST['printer_id']) ? intval($_POST['printer_id']) : null;
                try {
                    $stmt = $pdo->prepare("UPDATE st_categories SET printer_id = ? WHERE store_id = ?");
                    $stmt->execute([$printer_id, $catActiveStoreId]);
                    $message = (tl('ຕັ້ງເຄື່ອງພິມໃຫ້ທຸກໝວດໝູ່ສຳເລັດ!') . ' (') . $stmt->rowCount() . (' ' . tl('ໝວດ)'));
                    $message_type = 'success';
                    logActivity($pdo, "ຕັ້ງເຄື່ອງພິມໃຫ້ທຸກໝວດໝູ່", $printer_id ? "printer_id: $printer_id" : "ຍົກເລີກເຄື່ອງພິມ");
                } catch (Exception $e) {
                    $message = (tl('ຜິດພາດ:') . ' ') . $e->getMessage();
                    $message_type = 'danger';
                }
            }
        }
        elseif ($_POST['action'] === 'delete_category') {
            $id = intval($_POST['category_id'] ?? 0);
            if ($id > 0) {
                // Check if category has products inside
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM st_products WHERE category_id = ?");
                $checkStmt->execute([$id]);
                $count = (int)$checkStmt->fetchColumn();

                if ($count > 0) {
                    $message = (tl('ບໍ່ສາມາດລົບປະເພດສິນຄ້ານີ້ໄດ້! ເພາະມີລາຍການສິນຄ້າໃນໝວດໝູ່ນີ້') . ' ') . $count . (' ' . tl('ລາຍການ.'));
                    $message_type = 'danger';
                } else {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM st_categories WHERE category_id = ?");
                        $stmt->execute([$id]);
                        $message = tl('ລົບປະເພດສິນຄ້າສຳເລັດ!');
                        $message_type = 'success';
                        logActivity($pdo, "ລົບປະເພດສິນຄ້າ", "ID: $id");
                    } catch (Exception $e) {
                        $message = (tl('ຜິດພາດ:') . ' ') . $e->getMessage();
                        $message_type = 'danger';
                    }
                }
            }
        }
    }
}

// Fetch Categories of the active branch only (ສາຂາໃດສາຂານັ້ນ — ບໍ່ລວມກັບສາຂາອື່ນ),
// ordered by its per-branch number (1-1, 1-2 …)
$activeStoreIdForCat = getActiveStoreId($pdo);
ensureBranchNos($pdo, 'st_categories');
try {
    $stmtCat = $pdo->prepare("SELECT c.*, (SELECT COUNT(*) FROM st_products p WHERE p.category_id = c.category_id) AS product_count,
            pr.name AS printer_name, pr.status AS printer_status
        FROM st_categories c
        LEFT JOIN st_tbl_printer pr ON pr.id = c.printer_id AND pr.branch_id = ?
        WHERE c.store_id = ?
        ORDER BY c.branch_no ASC, c.category_id ASC");
    $stmtCat->execute([$activeStoreIdForCat, $activeStoreIdForCat]);
} catch (Throwable $e) {
    $stmtCat = $pdo->prepare("SELECT c.*, (SELECT COUNT(*) FROM st_products p WHERE p.category_id = c.category_id) AS product_count FROM st_categories c WHERE c.store_id = ? ORDER BY c.category_id ASC");
    $stmtCat->execute([$activeStoreIdForCat]);
}
$allCategories = $stmtCat->fetchAll();
foreach ($allCategories as &$__c) {
    $__c['display_code'] = branchCodeLabel($pdo, $__c['store_id'] ?? 0, $__c['branch_no'] ?? null, $__c['category_id']);
}
unset($__c);
$total_records = count($allCategories);
// ລະຫັດທີ່ໝວດໃໝ່ຈະໄດ້ ໃນສາຂາທີ່ກຳລັງເຮັດວຽກ (ສະແດງໃນຟອມເພີ່ມ)
$next_cat_code = branchCodeLabel($pdo, $activeStoreIdForCat, nextBranchNo($pdo, 'st_categories', $activeStoreIdForCat));

// Sequential next available ID (fill lowest unused integer starting from 1)
$usedCatIds = $pdo->query("SELECT category_id FROM st_categories ORDER BY category_id ASC")->fetchAll(PDO::FETCH_COLUMN);
$next_cat_id = 1;
foreach ($usedCatIds as $cid) {
    if ((int)$cid === $next_cat_id) {
        $next_cat_id++;
    } elseif ((int)$cid > $next_cat_id) {
        break;
    }
}

// Active printers — for the "kitchen ticket printer" dropdown on the add/edit category form.
$catPrinters = [];
try {
    $catPrintersStmt = $pdo->prepare("SELECT id, name FROM st_tbl_printer WHERE status = 1 AND branch_id = ? ORDER BY name ASC");
    $catPrintersStmt->execute([$activeStoreIdForCat]);
    $catPrinters = $catPrintersStmt->fetchAll();
} catch (Throwable $e) {}
