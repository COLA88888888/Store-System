<?php
// ຮັບປະກັນໂຄງສ້າງຕາຕະລາງ price_adjustments ມີຟີວຄົບຖ້ວນ
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `price_adjustments` (
        `adjust_id` INT AUTO_INCREMENT PRIMARY KEY,
        `adjust_date` DATE DEFAULT NULL,
        `adjust_time` TIME DEFAULT NULL,
        `target_mode` VARCHAR(20) DEFAULT 'product',
        `category_id` INT DEFAULT NULL,
        `product_id` INT DEFAULT NULL,
        `product_name` VARCHAR(255) DEFAULT NULL,
        `target_price_type` VARCHAR(20) DEFAULT 'price',
        `calc_type` VARCHAR(20) DEFAULT 'set',
        `adjust_value` DECIMAL(12,2) DEFAULT 0.00,
        `old_bprice` DECIMAL(12,2) DEFAULT 0.00,
        `old_price` DECIMAL(12,2) DEFAULT 0.00,
        `new_bprice` DECIMAL(12,2) DEFAULT 0.00,
        `new_price` DECIMAL(12,2) DEFAULT 0.00,
        `diff_amount` DECIMAL(12,2) DEFAULT 0.00,
        `remark` TEXT DEFAULT NULL,
        `username` VARCHAR(100) DEFAULT NULL,
        `user_id` INT DEFAULT NULL,
        `branch_id` INT DEFAULT 1,
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Add missing columns if table existed previously with old columns
    $cols = $pdo->query("SHOW COLUMNS FROM `price_adjustments`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('target_mode', $cols)) $pdo->exec("ALTER TABLE `price_adjustments` ADD COLUMN `target_mode` VARCHAR(20) DEFAULT 'product' AFTER `adjust_time`");
    if (!in_array('category_id', $cols)) $pdo->exec("ALTER TABLE `price_adjustments` ADD COLUMN `category_id` INT DEFAULT NULL AFTER `target_mode`");
    if (!in_array('product_id', $cols)) $pdo->exec("ALTER TABLE `price_adjustments` ADD COLUMN `product_id` INT DEFAULT NULL AFTER `category_id`");
    if (!in_array('product_name', $cols)) $pdo->exec("ALTER TABLE `price_adjustments` ADD COLUMN `product_name` VARCHAR(255) DEFAULT NULL AFTER `product_id`");
    if (!in_array('target_price_type', $cols)) $pdo->exec("ALTER TABLE `price_adjustments` ADD COLUMN `target_price_type` VARCHAR(20) DEFAULT 'price' AFTER `product_name`");
    if (!in_array('calc_type', $cols)) $pdo->exec("ALTER TABLE `price_adjustments` ADD COLUMN `calc_type` VARCHAR(20) DEFAULT 'set' AFTER `target_price_type`");
    if (!in_array('adjust_value', $cols)) $pdo->exec("ALTER TABLE `price_adjustments` ADD COLUMN `adjust_value` DECIMAL(12,2) DEFAULT 0.00 AFTER `calc_type`");
    if (!in_array('old_bprice', $cols)) $pdo->exec("ALTER TABLE `price_adjustments` ADD COLUMN `old_bprice` DECIMAL(12,2) DEFAULT 0.00 AFTER `adjust_value`");
    if (!in_array('old_price', $cols)) $pdo->exec("ALTER TABLE `price_adjustments` ADD COLUMN `old_price` DECIMAL(12,2) DEFAULT 0.00 AFTER `old_bprice`");
    if (!in_array('new_bprice', $cols)) $pdo->exec("ALTER TABLE `price_adjustments` ADD COLUMN `new_bprice` DECIMAL(12,2) DEFAULT 0.00 AFTER `old_price`");
    if (!in_array('new_price', $cols)) $pdo->exec("ALTER TABLE `price_adjustments` ADD COLUMN `new_price` DECIMAL(12,2) DEFAULT 0.00 AFTER `new_bprice`");
    if (!in_array('diff_amount', $cols)) $pdo->exec("ALTER TABLE `price_adjustments` ADD COLUMN `diff_amount` DECIMAL(12,2) DEFAULT 0.00 AFTER `new_price`");
} catch (Exception $e) {}

$userStoreId = intval($_SESSION['store_id'] ?? 1);
$isAdmin = ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' || strtolower($_SESSION['status'] ?? '') === 'admin' || ($_SESSION['user_id'] ?? 0) == 1;
$isMain = isMainBranch($pdo, $userStoreId);
$canAllBranches = canViewAllBranches($pdo);

// Fetch stores list for branch selection/filter
$stores = $pdo->query("SELECT * FROM tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// GET filter_store parameter
$filter_store = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : 0;
if (!$canAllBranches) {
    $filter_store = $userStoreId;
}

$message = '';
$message_type = '';

// Handle POST Actions (Add Adjustment & Delete Log)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'adjust_price') {
        $target_mode       = $_POST['target_mode'] ?? 'product'; // 'product' ຫຼື 'category'
        $category_id       = intval($_POST['category_id'] ?? 0);
        $product_id        = intval($_POST['product_id'] ?? 0);
        $target_price_type = $_POST['target_price_type'] ?? 'price'; // 'price', 'bprice', 'both'
        $calc_type         = $_POST['calc_type'] ?? 'set'; // 'increase', 'decrease', 'set'
        $adjust_value      = floatval(str_replace(',', '', $_POST['adjust_value'] ?? '0'));
        $remark            = trim($_POST['remark'] ?? '');
        $username          = $_SESSION['username'] ?? 'admin';
        $user_id           = $_SESSION['user_id'] ?? 1;

        $targetStoreId     = isset($_POST['store_id']) && intval($_POST['store_id']) > 0 ? intval($_POST['store_id']) : ($filter_store > 0 ? $filter_store : $userStoreId);
        if (!$canAllBranches) {
            $targetStoreId = $userStoreId;
        }

        if ($target_mode === 'product' && $product_id <= 0) {
            $message = 'ກະລຸນາເລືອກສິນຄ້າທີ່ຕ້ອງການປັບລາຄາ!';
            $message_type = 'danger';
        } elseif ($target_mode === 'category' && $category_id <= 0) {
            $message = 'ກະລຸນາເລືອກໝວດໝູ່ສິນຄ້າ!';
            $message_type = 'danger';
        } else {
            try {
                $pdo->beginTransaction();

                // ດຶງລາຍການສິນຄ້າທີ່ຈະປັບ
                if ($target_mode === 'product') {
                    $stmt = $pdo->prepare("SELECT product_id, product_name, bprice, price FROM products WHERE product_id = ? AND store_id = ?");
                    $stmt->execute([$product_id, $targetStoreId]);
                    $targetProducts = $stmt->fetchAll();
                } else {
                    $stmt = $pdo->prepare("SELECT product_id, product_name, bprice, price FROM products WHERE category_id = ? AND store_id = ?");
                    $stmt->execute([$category_id, $targetStoreId]);
                    $targetProducts = $stmt->fetchAll();
                }

                if (empty($targetProducts)) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $message = 'ບໍ່ພົບສິນຄ້າໃນເງື່ອນໄຂທີ່ເລືອກ!';
                    $message_type = 'warning';
                } else {
                    $count = 0;
                    $updStmt = $pdo->prepare("UPDATE products SET bprice = ?, price = ? WHERE product_id = ? AND store_id = ?");
                    $logStmt = $pdo->prepare("INSERT INTO price_adjustments (adjust_date, adjust_time, target_mode, category_id, product_id, product_name, target_price_type, calc_type, adjust_value, old_bprice, old_price, new_bprice, new_price, diff_amount, remark, username, user_id, branch_id) VALUES (CURDATE(), CURTIME(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    foreach ($targetProducts as $prod) {
                        $pid       = $prod['product_id'];
                        $pname     = $prod['product_name'];
                        $oldBprice = floatval($prod['bprice']);
                        $oldPrice  = floatval($prod['price']);

                        $newBprice = $oldBprice;
                        $newPrice  = $oldPrice;

                        // ຄິດໄລ່ລາຄາຂາຍໃໝ່
                        if ($target_price_type === 'price' || $target_price_type === 'both') {
                            if ($calc_type === 'increase') {
                                $newPrice = $oldPrice + $adjust_value;
                            } elseif ($calc_type === 'decrease') {
                                $newPrice = max(0, $oldPrice - $adjust_value);
                            } else { // 'set'
                                $newPrice = max(0, $adjust_value);
                            }
                        }

                        // ຄິດໄລ່ລາຄາຊື້ໃໝ່
                        if ($target_price_type === 'bprice' || $target_price_type === 'both') {
                            if ($calc_type === 'increase') {
                                $newBprice = $oldBprice + $adjust_value;
                            } elseif ($calc_type === 'decrease') {
                                $newBprice = max(0, $oldBprice - $adjust_value);
                            } else { // 'set'
                                $newBprice = max(0, $adjust_value);
                            }
                        }

                        // ຜົນຕ່າງ (Diff amount relative to sales price or bprice)
                        $diffAmount = ($target_price_type === 'bprice') ? ($newBprice - $oldBprice) : ($newPrice - $oldPrice);

                        // ອັບເດດລາຄາໃນຕາຕະລາງ products (ຕອງຕາມ store_id)
                        $updStmt->execute([$newBprice, $newPrice, $pid, $targetStoreId]);

                        // ບັນທຶກປະຫວັດລາຍລະອຽດ
                        $logStmt->execute([
                            $target_mode,
                            ($target_mode === 'category' ? $category_id : null),
                            $pid,
                            $pname,
                            $target_price_type,
                            $calc_type,
                            $adjust_value,
                            $oldBprice,
                            $oldPrice,
                            $newBprice,
                            $newPrice,
                            $diffAmount,
                            $remark,
                            $username,
                            $user_id,
                            $targetStoreId
                        ]);

                        $count++;
                    }

                    $pdo->commit();
                    $message = "ປັບລາຄາສິນຄ້າສຳເລັດແລ້ວ ທັງໝົດ {$count} ລາຍການ!";
                    $message_type = 'success';
                    logActivity($pdo, "ປັບລາຄາສິນຄ້າ", "ໂໝດ: {$target_mode}, ປັບທັງໝົດ: {$count} ລາຍການ (ສາຂາ #$targetStoreId)");
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message = 'ຜິດພາດ: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    } elseif ($action === 'delete_log') {
        $adjust_id = intval($_POST['adjust_id'] ?? 0);
        if ($adjust_id > 0) {
            try {
                $pdo->beginTransaction();

                $sel = ($canAllBranches)
                    ? $pdo->prepare("SELECT * FROM price_adjustments WHERE adjust_id = ?")
                    : $pdo->prepare("SELECT * FROM price_adjustments WHERE adjust_id = ? AND branch_id = ?");
                ($canAllBranches) ? $sel->execute([$adjust_id]) : $sel->execute([$adjust_id, $userStoreId]);
                $row = $sel->fetch(PDO::FETCH_ASSOC);

                if (!$row) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $message = 'ບໍ່ພົບປະຫວັດການປັບລາຄານີ້!';
                    $message_type = 'warning';
                } else {
                    // Put the product's price(s) back to what they were before this adjustment
                    if (!empty($row['product_id'])) {
                        $tpt = $row['target_price_type'] ?? 'price';
                        if ($tpt === 'price') {
                            $pdo->prepare("UPDATE products SET price = ? WHERE product_id = ? AND store_id = ?")
                                ->execute([$row['old_price'], $row['product_id'], $row['branch_id']]);
                        } elseif ($tpt === 'bprice') {
                            $pdo->prepare("UPDATE products SET bprice = ? WHERE product_id = ? AND store_id = ?")
                                ->execute([$row['old_bprice'], $row['product_id'], $row['branch_id']]);
                        } else { // both
                            $pdo->prepare("UPDATE products SET price = ?, bprice = ? WHERE product_id = ? AND store_id = ?")
                                ->execute([$row['old_price'], $row['old_bprice'], $row['product_id'], $row['branch_id']]);
                        }
                    }
                    $pdo->prepare("DELETE FROM price_adjustments WHERE adjust_id = ?")->execute([$adjust_id]);
                    $pdo->commit();
                    logActivity($pdo, "ລຶບການປັບລາຄາ", "ຄືນລາຄາເດີມ ສິນຄ້າ ID: " . ($row['product_id'] ?? '-'));
                    $message = 'ລຶບປະຫວັດ ແລະ ຄືນລາຄາເດີມສຳເລັດ!';
                    $message_type = 'success';
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message = 'ຜິດພາດ: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    } elseif ($action === 'update_log') {
        // Edit an existing adjustment: recompute new price(s) from the row's ORIGINAL old_* baseline
        $adjust_id         = intval($_POST['adjust_id'] ?? 0);
        $target_price_type = $_POST['target_price_type'] ?? 'price';
        $calc_type         = $_POST['calc_type'] ?? 'set';
        $adjust_value      = floatval(str_replace(',', '', $_POST['adjust_value'] ?? '0'));
        $remark            = trim($_POST['remark'] ?? '');

        if ($adjust_id <= 0) {
            $message = 'ຂໍ້ມູນບໍ່ຖືກຕ້ອງ!';
            $message_type = 'danger';
        } else {
            try {
                $pdo->beginTransaction();
                $sel = ($canAllBranches)
                    ? $pdo->prepare("SELECT * FROM price_adjustments WHERE adjust_id = ?")
                    : $pdo->prepare("SELECT * FROM price_adjustments WHERE adjust_id = ? AND branch_id = ?");
                ($canAllBranches) ? $sel->execute([$adjust_id]) : $sel->execute([$adjust_id, $userStoreId]);
                $row = $sel->fetch(PDO::FETCH_ASSOC);

                if (!$row || empty($row['product_id'])) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $message = 'ບໍ່ພົບປະຫວັດ ຫຼື ບໍ່ແມ່ນລາຍການສິນຄ້າ!';
                    $message_type = 'warning';
                } else {
                    $oldB = floatval($row['old_bprice']);
                    $oldP = floatval($row['old_price']);
                    $newB = $oldB;
                    $newP = $oldP;

                    if ($target_price_type === 'price' || $target_price_type === 'both') {
                        $newP = ($calc_type === 'increase') ? $oldP + $adjust_value
                              : (($calc_type === 'decrease') ? max(0, $oldP - $adjust_value) : max(0, $adjust_value));
                    }
                    if ($target_price_type === 'bprice' || $target_price_type === 'both') {
                        $newB = ($calc_type === 'increase') ? $oldB + $adjust_value
                              : (($calc_type === 'decrease') ? max(0, $oldB - $adjust_value) : max(0, $adjust_value));
                    }
                    $diff = ($target_price_type === 'bprice') ? ($newB - $oldB) : ($newP - $oldP);

                    $pdo->prepare("UPDATE products SET bprice = ?, price = ? WHERE product_id = ? AND store_id = ?")
                        ->execute([$newB, $newP, $row['product_id'], $row['branch_id']]);
                    $pdo->prepare("UPDATE price_adjustments SET target_price_type = ?, calc_type = ?, adjust_value = ?, new_bprice = ?, new_price = ?, diff_amount = ?, remark = ? WHERE adjust_id = ?")
                        ->execute([$target_price_type, $calc_type, $adjust_value, $newB, $newP, $diff, $remark, $adjust_id]);

                    $pdo->commit();
                    logActivity($pdo, "ແກ້ໄຂການປັບລາຄາ", "ສິນຄ້າ ID: " . $row['product_id']);
                    $message = 'ແກ້ໄຂການປັບລາຄາສຳເລັດ!';
                    $message_type = 'success';
                }
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message = 'ຜິດພາດ: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

if (!empty($_POST['is_ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => ($message_type === 'success'),
        'message' => $message
    ]);
    exit();
}

// Fetch categories, products, and price adjustments history
$targetProdStore = ($filter_store > 0) ? $filter_store : $userStoreId;
$catListStmt = $pdo->prepare("SELECT category_id, category_name FROM categories WHERE store_id = ? ORDER BY category_name ASC");
$catListStmt->execute([$targetProdStore]);
$categoriesList = $catListStmt->fetchAll();

$prodStmt = $pdo->prepare("SELECT p.product_id, p.product_name, p.barcode, p.category_id, p.bprice, p.price, p.unit, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id WHERE p.store_id = ? ORDER BY p.product_name ASC");
$prodStmt->execute([$targetProdStore]);
$productsList = $prodStmt->fetchAll();

$where = ["1=1"];
$params = [];
if ($filter_store > 0) {
    $where[] = "pa.branch_id = :filter_store";
    $params[':filter_store'] = $filter_store;
}
$whereSql = implode(' AND ', $where);

$adjStmt = $pdo->prepare("
    SELECT pa.*, p.barcode as prod_barcode, c.category_name, s.store_name 
    FROM price_adjustments pa
    LEFT JOIN products p ON pa.product_id = p.product_id AND p.store_id = pa.branch_id
    LEFT JOIN categories c ON pa.category_id = c.category_id
    LEFT JOIN tbstore s ON pa.branch_id = s.store_id
    WHERE {$whereSql}
    ORDER BY pa.adjust_id DESC 
    LIMIT 100
");
$adjStmt->execute($params);
$adjustments = $adjStmt->fetchAll();

if (!empty($_GET['fetch_table'])) {
    require_once __DIR__ . '/../pages/settings/price_adjustment/partials/price_adj_table.php';
    exit();
}
