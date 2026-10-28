<?php
// ==========================================
// ໂມດູນຈັດການໂປຣໂມຊັ່ນ (Promotions Management Module)
// Modular Architecture - Partial Loader
// ==========================================
session_start();
$base_path = '../../../';
require_once __DIR__ . '/../../../config/db.php';

if (empty($_SESSION['user_id']) || (!hasPermission('promotions') && !hasPermission('setup'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

$userStoreId = intval($_SESSION['store_id'] ?? 1);
$isAdmin = ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' || strtolower($_SESSION['status'] ?? '') === 'admin' || ($_SESSION['user_id'] ?? 0) == 1;
$isMain = isMainBranch($pdo, $userStoreId);
$canAllBranches = canViewAllBranches($pdo);

// Fetch stores list for branch selection/filter
$stores = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// GET filter_branch parameter
$filter_branch = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? intval($_GET['branch_id']) : intval($_SESSION['store_id'] ?? 1);
if (!$canAllBranches) {
    $filter_branch = $userStoreId;
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add_promo') {
        $promo_name        = trim($_POST['promo_name'] ?? '');
        $promo_type        = $_POST['promo_type'] ?? 'discount';
        $discount_type     = $_POST['discount_type'] ?? 'percentage';
        $discount_value    = floatval(str_replace(',', '', $_POST['discount_value'] ?? 0));
        $gift_product_name = trim($_POST['gift_product_name'] ?? '');
        $gift_qty          = intval($_POST['gift_qty'] ?? 1);
        $start_date        = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
        $end_date          = !empty($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime('+30 days'));
        $min_qty           = intval(str_replace(',', '', $_POST['min_qty'] ?? 0));
        $min_amount        = floatval(str_replace(',', '', $_POST['min_amount'] ?? 0));
        $target_type       = $_POST['target_type'] ?? 'all';
        $target_name       = trim($_POST['target_name'] ?? 'ທຸກສິນຄ້າ');
        $target_unit_name  = trim($_POST['target_unit_name'] ?? 'all');
        if (empty($target_unit_name)) $target_unit_name = 'all';
        $status            = intval($_POST['status'] ?? 1);

        $branch_id         = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : $userStoreId;
        if (!$canAllBranches) {
            $branch_id = $userStoreId;
        }

        if ($target_type === 'all') {
            $target_name = 'ທຸກສິນຄ້າ';
        }

        if ($promo_name !== '') {
            try {
                $stmt = $pdo->prepare("INSERT INTO st_promotions (promo_name, promo_type, discount_type, discount_value, gift_product_name, gift_qty, start_date, end_date, min_qty, min_amount, target_type, target_name, target_unit_name, status, branch_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$promo_name, $promo_type, $discount_type, $discount_value, $gift_product_name, $gift_qty, $start_date, $end_date, $min_qty, $min_amount, $target_type, $target_name, $target_unit_name, $status, $branch_id]);
                $message = t('promotions.msg_added', 'ເພີ່ມໂປຣໂມຊັ່ນໃໝ່ສຳເລັດ!');
                $message_type = 'success';
                logActivity($pdo, "ເພີ່ມໂປຣໂມຊັ່ນ", $promo_name);
            } catch (Exception $e) {
                $message = t('promotions.err_prefix', 'ຜິດພາດ:') . ' ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    } elseif ($action === 'edit_promo') {
        $id                = intval($_POST['id'] ?? 0);
        $promo_name        = trim($_POST['promo_name'] ?? '');
        $promo_type        = $_POST['promo_type'] ?? 'discount';
        $discount_type     = $_POST['discount_type'] ?? 'percentage';
        $discount_value    = floatval(str_replace(',', '', $_POST['discount_value'] ?? 0));
        $gift_product_name = trim($_POST['gift_product_name'] ?? '');
        $gift_qty          = intval($_POST['gift_qty'] ?? 1);
        $start_date        = !empty($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
        $end_date          = !empty($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime('+30 days'));
        $min_qty           = intval(str_replace(',', '', $_POST['min_qty'] ?? 0));
        $min_amount        = floatval(str_replace(',', '', $_POST['min_amount'] ?? 0));
        $target_type       = $_POST['target_type'] ?? 'all';
        $target_name       = trim($_POST['target_name'] ?? 'ທຸກສິນຄ້າ');
        $target_unit_name  = trim($_POST['target_unit_name'] ?? 'all');
        if (empty($target_unit_name)) $target_unit_name = 'all';

        $branch_id         = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : $userStoreId;
        if (!$canAllBranches) {
            $branch_id = $userStoreId;
        }

        if ($target_type === 'all') {
            $target_name = 'ທຸກສິນຄ້າ';
        }

        if ($id > 0 && $promo_name !== '') {
            try {
                $sql = "UPDATE st_promotions SET promo_name = ?, promo_type = ?, discount_type = ?, discount_value = ?, gift_product_name = ?, gift_qty = ?, start_date = ?, end_date = ?, min_qty = ?, min_amount = ?, target_type = ?, target_name = ?, target_unit_name = ?, branch_id = ? WHERE id = ?";
                $params = [$promo_name, $promo_type, $discount_type, $discount_value, $gift_product_name, $gift_qty, $start_date, $end_date, $min_qty, $min_amount, $target_type, $target_name, $target_unit_name, $branch_id, $id];
                if (!$canAllBranches) {
                    $sql .= " AND branch_id = ?";
                    $params[] = $userStoreId;
                }
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $message = t('promotions.msg_updated', 'ອັບເດດໂປຣໂມຊັ່ນສຳເລັດ!');
                $message_type = 'success';
                logActivity($pdo, "ແກ້ໄຂໂປຣໂມຊັ່ນ", "$promo_name (ID: $id)");
            } catch (Exception $e) {
                $message = t('promotions.err_prefix', 'ຜິດພາດ:') . ' ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    } elseif ($action === 'toggle_status') {
        $id = intval($_POST['id'] ?? 0);
        $status = intval($_POST['status'] ?? 0);
        if ($id > 0) {
            $chkSql = "SELECT end_date, branch_id FROM st_promotions WHERE id = ?";
            $chkParams = [$id];
            if (!$canAllBranches) {
                $chkSql .= " AND branch_id = ?";
                $chkParams[] = $userStoreId;
            }
            $chk = $pdo->prepare($chkSql);
            $chk->execute($chkParams);
            $promoRow = $chk->fetch(PDO::FETCH_ASSOC);

            if (!$promoRow) {
                $message = t('promotions.msg_not_found', 'ບໍ່ພົບໂປຣໂມຊັ່ນນີ້!');
                $message_type = 'danger';
            } elseif ($status == 1) {
                $promoDate = $promoRow['end_date'];
                if ($promoDate && $promoDate < date('Y-m-d')) {
                    $message = t('promotions.msg_expired_pre', 'ໂປຣໂມຊັ່ນນີ້ໝົດອາຍຸແລ້ວ (') . date('d/m/Y', strtotime($promoDate)) . t('promotions.msg_expired_post', ')! ກະລຸນາກົດ "ແກ້ໄຂ" ເພື່ອປ່ຽນວັນທີສິ້ນສຸດກ່ອນເປີດໃຊ້ງານ.');
                    $message_type = 'warning';
                } else {
                    $stmt = $pdo->prepare("UPDATE st_promotions SET status = 1 WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = t('promotions.msg_enabled', 'ເປີດໃຊ້ງານໂປຣໂມຊັ່ນສຳເລັດ!');
                    $message_type = 'success';
                }
            } else {
                $stmt = $pdo->prepare("UPDATE st_promotions SET status = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $message = t('promotions.msg_disabled', 'ປິດໃຊ້ງານໂປຣໂມຊັ່ນສຳເລັດ!');
                $message_type = 'success';
            }
        }
    } elseif ($action === 'delete_promo') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $sql = "DELETE FROM st_promotions WHERE id = ?";
            $params = [$id];
            if (!$canAllBranches) {
                $sql .= " AND branch_id = ?";
                $params[] = $userStoreId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $message = t('promotions.msg_deleted', 'ລົບໂປຣໂມຊັ່ນສຳເລັດ!');
            $message_type = 'success';
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

try {
    $pdo->exec("UPDATE st_promotions SET status = 0 WHERE status = 1 AND end_date < CURDATE()");
} catch (Exception $e) {}

$where = ["1=1"];
$params = [];
if ($filter_branch > 0) {
    $where[] = "(p.branch_id = :filter_branch OR p.branch_id = 0)";
    $params[':filter_branch'] = $filter_branch;
}
$whereSql = implode(' AND ', $where);

ensureBranchNos($pdo, 'st_promotions');
$stmtP = $pdo->prepare("
    SELECT p.*, s.store_name
    FROM st_promotions p 
    LEFT JOIN st_tbstore s ON p.branch_id = s.store_id 
    WHERE {$whereSql} 
    ORDER BY p.id DESC
");
$stmtP->execute($params);
$promos = $stmtP->fetchAll(PDO::FETCH_ASSOC);
attachBranchCodes($pdo, $promos, 'st_promotions'); // ລະຫັດ "1-1" ແຍກຕາມສາຂາ

// Map barcode / code1 -> product_name so the table can show a real name even when a promo's
// target was entered as a code. Built before the fetch_table exit so the AJAX reload has it too.
$productNameByCode = [];
try {
    foreach ($pdo->query("SELECT product_name, barcode, code1 FROM st_products")->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $nm = trim((string)$row['product_name']);
        if ($nm === '') continue;
        foreach ([(string)($row['barcode'] ?? ''), (string)($row['code1'] ?? '')] as $code) {
            $code = trim($code);
            if ($code !== '') $productNameByCode[$code] = $nm;
        }
    }
} catch (Exception $e) {}

// Active display currency, so the table shows ₭ amounts in whatever currency the shop uses
$promoDispCur = function_exists('getDisplayCurrency') ? getDisplayCurrency($pdo) : ['code' => 'KIP', 'symbol' => kipSymbol(), 'decimals' => 0, 'rates' => null];
$promoFmtMoney = function ($amountKip) use ($promoDispCur) {
    $code = strtoupper((string)($promoDispCur['code'] ?? 'KIP'));
    if ($code === 'KIP' || !function_exists('convertFromKip')) {
        return number_format((float)$amountKip, 0) . ' ' . kipSymbol();
    }
    $val = convertFromKip($amountKip, $code, $promoDispCur['rates'] ?? null);
    $dec = (int)($promoDispCur['decimals'] ?? 2);
    return number_format((float)$val, $dec, '.', ',') . ' ' . (string)($promoDispCur['symbol'] ?? $code);
};

if (!empty($_GET['fetch_table'])) {
    require_once __DIR__ . '/partials/promotions_table.php';
    exit();
}

// Fetch categories & products for target selection (filtered by store)
$targetProdStore = ($filter_branch > 0) ? $filter_branch : $userStoreId;

$categories = [];
try {
    $cStmt = $pdo->prepare("SELECT category_name FROM st_categories WHERE store_id = ? OR store_id = 1 ORDER BY category_name ASC");
    $cStmt->execute([$targetProdStore]);
    $categories = $cStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {}

$productsList = [];   // names only — feeds the <datalist>
$productsLookup = [];  // {code, barcode, name, units[]} — lets the modal resolve a typed code
                      // to a name AND offer that product's real unit names
try {
    $pStmt = $pdo->prepare("SELECT product_id, product_name, barcode, code1, unit FROM st_products WHERE store_id = ? OR store_id = 1 ORDER BY product_name ASC");
    $pStmt->execute([$targetProdStore]);
    $prodRows = $pStmt->fetchAll(PDO::FETCH_ASSOC);

    // Extra units per product
    $unitsByProduct = [];
    try {
        foreach ($pdo->query("SELECT product_id, unit_name FROM st_product_units")->fetchAll(PDO::FETCH_ASSOC) as $u) {
            $un = trim((string)$u['unit_name']);
            if ($un !== '') $unitsByProduct[$u['product_id']][] = $un;
        }
    } catch (Exception $e) {}

    foreach ($prodRows as $row) {
        $productsList[] = $row['product_name'];
        $units = [];
        $baseU = trim((string)($row['unit'] ?? ''));
        if ($baseU !== '') $units[] = $baseU;
        foreach ($unitsByProduct[$row['product_id']] ?? [] as $eu) $units[] = $eu;
        $productsLookup[] = [
            'name'    => $row['product_name'],
            'barcode' => (string)($row['barcode'] ?? ''),
            'code'    => (string)($row['code1'] ?? ''),
            'units'   => array_values(array_unique($units)),
        ];
    }
    $productsList = array_values(array_unique($productsList));
} catch (Exception $e) {}

require_once __DIR__ . '/../../../layouts/header.php';
?>

<?php echo themeCssLink('settings-promotions.css'); /* CSS ຍ້າຍໄປ themes/settings-promotions.css */ ?>

<div class="container-fluid p-4">
  <?php require_once __DIR__ . '/partials/promotions_header.php'; ?>
  <?php require_once __DIR__ . '/partials/promotions_table.php'; ?>
</div>

<?php require_once __DIR__ . '/partials/promotions_modal.php'; ?>
<?php require_once __DIR__ . '/partials/promotions_js.php'; ?>
<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
