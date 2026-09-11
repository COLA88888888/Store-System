<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../config/db.php';

// Check permissions
if (empty($_SESSION['user_id']) || (!hasPermission('categories') && ($_SESSION['status'] ?? '') !== 'ຜູ້ບໍລິຫານ')) {
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
                    $stmt = $pdo->prepare("INSERT INTO categories (category_id, category_name, description, store_id, printer_id) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$cat_id, $name, $desc, $catActiveStoreId, $printer_id]);

                    // A category added from the main branch is also created in every sub-branch
                    // (own auto-generated category_id per branch, since category_id is a single
                    // shared PRIMARY KEY across the whole table, not composite with store_id),
                    // so it's immediately available for products there without a manual re-add.
                    if (isMainBranch($pdo, $catActiveStoreId)) {
                        $subStoresStmt = $pdo->prepare("SELECT store_id FROM tbstore WHERE store_id != ?");
                        $subStoresStmt->execute([$catActiveStoreId]);
                        $subInsertStmt = $pdo->prepare("INSERT INTO categories (category_name, description, store_id) VALUES (?, ?, ?)");
                        foreach ($subStoresStmt->fetchAll(PDO::FETCH_COLUMN) as $subStoreId) {
                            $subInsertStmt->execute([$name, $desc, $subStoreId]);
                        }
                    }

                    $message = 'ເພີ່ມປະເພດສິນຄ້າສຳເລັດ!';
                    $message_type = 'success';
                    logActivity($pdo, "ເພີ່ມປະເພດສິນຄ້າ", "ຊື່: $name");
                } catch (Exception $e) {
                    $message = 'ຜິດພາດ: ລະຫັດນີ້ອາດຊ້ຳກັນ ຫຼື ' . $e->getMessage();
                    $message_type = 'danger';
                }
            } else {
                $message = 'ກະລຸນາປ້ອນລະຫັດ ແລະ ຊື່ປະເພດສິນຄ້າໃຫ້ຄົບ!';
                $message_type = 'danger';
            }
        }
        elseif ($_POST['action'] === 'edit_category') {
            $id = intval($_POST['category_id'] ?? 0);
            $name = trim($_POST['category_name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $printer_id = !empty($_POST['printer_id']) ? intval($_POST['printer_id']) : null;
            if ($id > 0 && $name !== '') {
                try {
                    // Grab the pre-edit name so a main-branch edit can find the matching
                    // rows in sub-branches (each branch holds its own category_id, so the
                    // only thing linking "the same" category across branches is its name).
                    $oldNameStmt = $pdo->prepare("SELECT category_name FROM categories WHERE category_id = ? AND store_id = ?");
                    $oldNameStmt->execute([$id, $catActiveStoreId]);
                    $oldName = $oldNameStmt->fetchColumn();

                    // Scoped to this branch's own category — a sub-branch user can't edit another branch's row.
                    // printer_id stays branch-local (each branch has its own printers), so it's not propagated below.
                    $stmt = $pdo->prepare("UPDATE categories SET category_name = ?, description = ?, printer_id = ? WHERE category_id = ? AND store_id = ?");
                    $stmt->execute([$name, $desc, $printer_id, $id, $catActiveStoreId]);

                    // A category edited from the main branch also renames the matching
                    // category in every sub-branch, so products there keep showing the
                    // updated name/description without a manual re-edit in each branch.
                    if ($oldName !== false && $oldName !== '' && isMainBranch($pdo, $catActiveStoreId)) {
                        $subUpdateStmt = $pdo->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_name = ? AND store_id != ?");
                        $subUpdateStmt->execute([$name, $desc, $oldName, $catActiveStoreId]);
                    }

                    $message = 'ແກ້ໄຂປະເພດສິນຄ້າສຳເລັດ!';
                    $message_type = 'success';
                    logActivity($pdo, "ແກ້ໄຂປະເພດສິນຄ້າ", "ID: $id, ຊື່ໃໝ່: $name");
                } catch (Exception $e) {
                    $message = 'ຜິດພາດ: ' . $e->getMessage();
                    $message_type = 'danger';
                }
            }
        }
        elseif ($_POST['action'] === 'delete_category') {
            $id = intval($_POST['category_id'] ?? 0);
            if ($id > 0) {
                // Check if category has products inside — across ALL branches, since some
                // branches' products intentionally still point at another branch's category
                // (left in place by design) and deleting it out from under them isn't safe.
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                $checkStmt->execute([$id]);
                $count = (int)$checkStmt->fetchColumn();

                if ($count > 0) {
                    $message = 'ບໍ່ສາມາດລົບປະເພດສິນຄ້ານີ້ໄດ້! ເພາະມີລາຍການສິນຄ້າໃນໝວດໝູ່ນີ້ ' . $count . ' ລາຍການ.';
                    $message_type = 'danger';
                } else {
                    try {
                        $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ? AND store_id = ?");
                        $stmt->execute([$id, $catActiveStoreId]);
                        $message = 'ລົບປະເພດສິນຄ້າສຳເລັດ!';
                        $message_type = 'success';
                        logActivity($pdo, "ລົບປະເພດສິນຄ້າ", "ID: $id");
                    } catch (Exception $e) {
                        $message = 'ຜິດພາດ: ' . $e->getMessage();
                        $message_type = 'danger';
                    }
                }
            }
        }
    }
}

// Fetch Categories scoped to the active branch only — don't mix categories across branches.
// product_count intentionally still matches by category_id alone (not store_id) — some
// branches already have products pointing at another branch's category by design and those
// links were left in place, so the count must keep including them.
$activeStoreIdForCat = getActiveStoreId($pdo);
$stmtCat = $pdo->prepare("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.category_id) AS product_count FROM categories c WHERE c.store_id = ? ORDER BY c.category_id DESC");
$stmtCat->execute([$activeStoreIdForCat]);
$allCategories = $stmtCat->fetchAll();
$total_records = count($allCategories);

// Next available ID (MAX + 1 across all branches, or 1 if empty) — category_id is a shared
// primary key, so this must stay global to avoid two branches generating the same new ID.
$next_cat_id = (int)$pdo->query("SELECT IFNULL(MAX(category_id), 0) + 1 FROM categories")->fetchColumn();

// Active printers — for the "kitchen ticket printer" dropdown on the add/edit category form.
$catPrinters = [];
try {
    $catPrinters = $pdo->query("SELECT id, name FROM tbl_printer WHERE status = 1 ORDER BY name ASC")->fetchAll();
} catch (Throwable $e) {}
