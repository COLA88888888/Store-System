<?php
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../../config/db.php';

// Check authorization
if (empty($_SESSION['user_id']) || (!hasPermission('import_list') && !hasPermission('import_stock') && !hasPermission('stock') && ($_SESSION['status'] ?? '') !== 'ຜູ້ບໍລິຫານ')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

// ====== HANDLE POST ACTIONS ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- 1. ACTION: DELETE SINGLE IMPORT DETAIL ---
    if ($_POST['action'] === 'delete_import_detail') {
        $import_detail_id = intval($_POST['import_detail_id'] ?? 0);
        if ($import_detail_id > 0) {
            try {
                $pdo->beginTransaction();

                $detStmt = $pdo->prepare("
                    SELECT id.*, p.qty AS current_product_qty, pb.initial_qty AS batch_initial_qty, pb.quantity AS batch_rem_qty
                    FROM import_details id
                    JOIN products p ON id.product_id = p.product_id
                    LEFT JOIN product_batches pb ON id.import_detail_id = pb.import_detail_id
                    WHERE id.import_detail_id = ? FOR UPDATE
                ");
                $detStmt->execute([$import_detail_id]);
                $det = $detStmt->fetch();

                if ($det) {
                    $bRem = isset($det['batch_rem_qty']) ? intval($det['batch_rem_qty']) : null;
                    $bInit = isset($det['batch_initial_qty']) ? intval($det['batch_initial_qty']) : null;
                    $pQty = intval($det['current_product_qty']);
                    $baseQty = intval($det['total_base_qty']);

                    if (($bRem !== null && $bInit !== null && $bRem < $bInit) || ($pQty < $baseQty)) {
                        throw new Exception(t('import_list.err_has_movement', 'ບໍ່ສາມາດລົບ/ຍົກເລີກໃບບິນນີ້ໄດ້ ເນື່ອງຈາກສິນຄ້ານີ້ມີການເຄື່ອນໄຫວ ຫຼື ຖືກຂາຍອອກໄປແລ້ວ!'));
                    }

                    // Revert stock quantity in products
                    $revStock = $pdo->prepare("UPDATE products SET qty = GREATEST(0, qty - ?) WHERE product_id = ?");
                    $revStock->execute([$det['total_base_qty'], $det['product_id']]);

                    // Update total cost in imports master
                    $updImp = $pdo->prepare("UPDATE imports SET total_cost = GREATEST(0, total_cost - ?) WHERE import_id = ?");
                    $updImp->execute([$det['total_cost'], $det['import_id']]);

                    // Delete batches
                    $delBatch = $pdo->prepare("DELETE FROM product_batches WHERE import_detail_id = ?");
                    $delBatch->execute([$import_detail_id]);

                    // Delete detail
                    $delDet = $pdo->prepare("DELETE FROM import_details WHERE import_detail_id = ?");
                    $delDet->execute([$import_detail_id]);

                    $message = t('import_list.msg_cancel_success', 'ຍົກເລີກການຮັບສິນຄ້າເຂົ້າສຳເລັດ (ປັບສະຕັອກຄືນແລ້ວ)!');
                    $message_type = 'success';
                    logActivity($pdo, "ຍົກເລີກການຮັບສິນຄ້າເຂົ້າ", "Detail ID: $import_detail_id");
                }

                $pdo->commit();
            } catch (Exception $e) {
                // MySQL can auto-rollback the whole transaction itself (deadlock, lock-wait
                // timeout, etc.) before we get here — calling rollBack() on an already-gone
                // transaction throws "There is no active transaction" and crashes the page
                // with an uncaught fatal, masking the real error. Guard it.
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $message = $e->getMessage();
                $message_type = 'danger';
            }
        }
    }

    // --- 2. ACTION: DELETE ENTIRE MASTER IMPORT BILL ---
    elseif ($_POST['action'] === 'delete_master_import') {
        $import_id = intval($_POST['import_id'] ?? 0);
        if ($import_id > 0) {
            try {
                $pdo->beginTransaction();

                $detStmt = $pdo->prepare("
                    SELECT id.*, p.qty AS current_product_qty, pb.initial_qty AS batch_initial_qty, pb.quantity AS batch_rem_qty
                    FROM import_details id
                    JOIN products p ON id.product_id = p.product_id
                    LEFT JOIN product_batches pb ON id.import_detail_id = pb.import_detail_id
                    WHERE id.import_id = ? FOR UPDATE
                ");
                $detStmt->execute([$import_id]);
                $details = $detStmt->fetchAll();

                foreach ($details as $det) {
                    $bRem = isset($det['batch_rem_qty']) ? intval($det['batch_rem_qty']) : null;
                    $bInit = isset($det['batch_initial_qty']) ? intval($det['batch_initial_qty']) : null;
                    $pQty = intval($det['current_product_qty']);
                    $baseQty = intval($det['total_base_qty']);

                    if (($bRem !== null && $bInit !== null && $bRem < $bInit) || ($pQty < $baseQty)) {
                        throw new Exception(t('import_list.err_bill_has_movement', 'ບໍ່ສາມາດລົບໃບບິນນີ້ໄດ້ ເນື່ອງຈາກມີສິນຄ້າໃນໃບບິນນີ້ຖືກຂາຍ ຫຼື ເຄື່ອນໄຫວແລ້ວ!'));
                    }
                }

                foreach ($details as $det) {
                    $pdo->prepare("UPDATE products SET qty = GREATEST(0, qty - ?) WHERE product_id = ?")
                        ->execute([$det['total_base_qty'], $det['product_id']]);
                    $pdo->prepare("DELETE FROM product_batches WHERE import_detail_id = ?")
                        ->execute([$det['import_detail_id']]);
                }

                $pdo->prepare("DELETE FROM import_details WHERE import_id = ?")->execute([$import_id]);
                $pdo->prepare("DELETE FROM imports WHERE import_id = ?")->execute([$import_id]);

                $message = t('import_list.msg_delete_bill_success', 'ລົບໃບບິນຮັບເຂົ້າສິນຄ້າສຳເລັດ (ປັບສະຕັອກຄືນແລ້ວ)!');
                $message_type = 'success';
                logActivity($pdo, "ລົບໃບບິນຮັບເຂົ້າສິນຄ້າ", "Import ID: $import_id");

                $pdo->commit();
            } catch (Exception $e) {
                // MySQL can auto-rollback the whole transaction itself (deadlock, lock-wait
                // timeout, etc.) before we get here — calling rollBack() on an already-gone
                // transaction throws "There is no active transaction" and crashes the page
                // with an uncaught fatal, masking the real error. Guard it.
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $message = $e->getMessage();
                $message_type = 'danger';
            }
        }
    }

    // --- 3. ACTION: EDIT IMPORT DETAIL ---
    elseif ($_POST['action'] === 'edit_import_detail') {
        $import_detail_id = intval($_POST['import_detail_id'] ?? 0);
        $supplier_name    = trim($_POST['supplier_name'] ?? '');
        $quantity         = intval($_POST['quantity'] ?? 0);
        $cost_price       = floatval($_POST['cost_price'] ?? 0);
        $unit_key         = trim($_POST['unit_key'] ?? 'base');
        $expiry_date      = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $notes            = trim($_POST['notes'] ?? '');

        if ($import_detail_id > 0 && $quantity > 0) {
            try {
                $pdo->beginTransaction();

                $detStmt = $pdo->prepare("SELECT * FROM import_details WHERE import_detail_id = ? FOR UPDATE");
                $detStmt->execute([$import_detail_id]);
                $oldDet = $detStmt->fetch();

                if ($oldDet) {
                    $product_id = $oldDet['product_id'];

                    // Fetch Product
                    $pStmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? FOR UPDATE");
                    $pStmt->execute([$product_id]);
                    $product = $pStmt->fetch();

                    $unit_name = !empty($product['unit']) ? $product['unit'] : t('import_list.default_unit', 'ອັນ');
                    $multiplier = 1;

                    if ($unit_key !== 'base') {
                        $uStmt = $pdo->prepare("SELECT unit_name, multiplier FROM product_units WHERE id = ? AND product_id = ?");
                        $uStmt->execute([intval($unit_key), $product_id]);
                        $puRow = $uStmt->fetch();
                        if ($puRow) {
                            $unit_name  = $puRow['unit_name'];
                            $multiplier = max(1, intval($puRow['multiplier']));
                        }
                    }

                    $new_total_base_qty = $quantity * $multiplier;
                    $new_total_cost     = $quantity * $cost_price;

                    $diff_base_qty = $new_total_base_qty - $oldDet['total_base_qty'];
                    $diff_cost     = $new_total_cost - $oldDet['total_cost'];

                    // Update products stock
                    $updStock = $pdo->prepare("UPDATE products SET qty = GREATEST(0, qty + ?), bprice = ? WHERE product_id = ?");
                    $updStock->execute([$diff_base_qty, $cost_price, $product_id]);

                    // Update master import
                    $updImp = $pdo->prepare("UPDATE imports SET supplier_name = ?, total_cost = GREATEST(0, total_cost + ?), notes = IF(? != '', ?, notes) WHERE import_id = ?");
                    $updImp->execute([$supplier_name, $diff_cost, $notes, $notes, $oldDet['import_id']]);

                    // Update import_details
                    $updDet = $pdo->prepare("UPDATE import_details SET unit_name = ?, multiplier = ?, quantity = ?, total_base_qty = ?, cost_price = ?, total_cost = ?, expiry_date = ? WHERE import_detail_id = ?");
                    $updDet->execute([$unit_name, $multiplier, $quantity, $new_total_base_qty, $cost_price, $new_total_cost, $expiry_date, $import_detail_id]);

                    // Update product_batches
                    $updBatch = $pdo->prepare("UPDATE product_batches SET expiry_date = ?, initial_qty = ?, quantity = GREATEST(0, quantity + ?) WHERE import_detail_id = ?");
                    $updBatch->execute([$expiry_date, $new_total_base_qty, $diff_base_qty, $import_detail_id]);

                    $message = t('import_list.msg_edit_detail_success', 'ດັດແກ້ຂໍ້ມູນການຮັບສິນຄ້າເຂົ້າສຳເລັດ!');
                    $message_type = 'success';
                    logActivity($pdo, "ແກ້ໄຂການຮັບສິນຄ້າເຂົ້າ", "Detail ID: $import_detail_id");
                }

                $pdo->commit();
            } catch (Exception $e) {
                // MySQL can auto-rollback the whole transaction itself (deadlock, lock-wait
                // timeout, etc.) before we get here — calling rollBack() on an already-gone
                // transaction throws "There is no active transaction" and crashes the page
                // with an uncaught fatal, masking the real error. Guard it.
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $message = t('import_list.err_prefix', 'ຜິດພາດ: ') . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }

    // --- 4. ACTION: EDIT MASTER IMPORT BILL (SUPPLIER, DATE, NOTES) ---
    elseif ($_POST['action'] === 'edit_master_import') {
        $import_id     = intval($_POST['import_id'] ?? 0);
        $supplier_name = trim($_POST['supplier_name'] ?? '');
        $raw_date      = trim($_POST['import_date'] ?? '');
        $import_date   = !empty($raw_date) ? $raw_date . ' ' . date('H:i:s') : date('Y-m-d H:i:s');
        $notes         = trim($_POST['notes'] ?? '');

        if ($import_id > 0) {
            try {
                $updMaster = $pdo->prepare("UPDATE imports SET supplier_name = ?, import_date = ?, notes = ? WHERE import_id = ?");
                $updMaster->execute([$supplier_name, $import_date, $notes, $import_id]);

                $message = t('import_list.msg_edit_master_success', 'ດັດແກ້ຂໍ້ມູນໃບບິນຮັບເຂົ້າສຳເລັດ!');
                $message_type = 'success';
                logActivity($pdo, "ແກ້ໄຂຂໍ້ມູນໃບບິນຮັບເຂົ້າ", "Import ID: $import_id");
            } catch (Exception $e) {
                $message = t('import_list.err_prefix', 'ຜິດພາດ: ') . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// ====== GET FILTER PARAMETERS ======
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date   = $_GET['to_date']   ?? date('Y-m-d');
$view_type = $_GET['view_type'] ?? 'bill';

// ====== FETCH IMPORT RECORDS BASED ON VIEW TYPE & DATE RANGE ======
$activeStoreId = getActiveStoreId($pdo);
$userStoreId = intval($_SESSION['store_id'] ?? 1);
$isAdmin = ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' || strtolower($_SESSION['status'] ?? '') === 'admin' || ($_SESSION['user_id'] ?? 0) == 1;
$isMain = isMainBranch($pdo, $userStoreId);

// Fetch stores list for branch filter dropdown
$stores = $pdo->query("SELECT * FROM tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// GET filter_store parameter
$filter_store = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : 0;
if (!$isAdmin && !$isMain) {
    $filter_store = $userStoreId;
}

// Fetch categories for modal filter
$targetProdStore = ($filter_store > 0) ? $filter_store : $userStoreId;
$catStmt = $pdo->prepare("SELECT category_id, category_name FROM categories WHERE store_id = ? ORDER BY category_name ASC");
$catStmt->execute([$targetProdStore]);
$categories = $catStmt->fetchAll();

// Fetch products for modal filter (filtered by active/selected store)
$pStmt = $pdo->prepare("
    SELECT p.product_id, p.product_name, p.barcode, p.unit, p.bprice, p.price, p.qty, p.category_id, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.store_id = ?
    ORDER BY p.product_name ASC
");
$pStmt->execute([$targetProdStore]);
$products = $pStmt->fetchAll();

$product_units_map = [];
$uRows = $pdo->query("SELECT * FROM product_units ORDER BY multiplier ASC")->fetchAll();
foreach ($uRows as $u) {
    $product_units_map[$u['product_id']][] = $u;
}

// Build store filter WHERE condition for bill queries
$whereClause = "WHERE DATE(i.import_date) BETWEEN :from_date AND :to_date";
$params = [
    ':from_date' => $from_date,
    ':to_date'   => $to_date
];

if ($filter_store > 0) {
    $whereClause .= " AND (CASE WHEN i.store_id > 0 THEN i.store_id ELSE p.store_id END) = :filter_store";
    $params[':filter_store'] = $filter_store;
}

// Pre-fetch items per bill for the View Bill Items Modal (strictly branch scoped)
$allBillWhere = "";
$allBillParams = [];
if ($filter_store > 0) {
    $allBillWhere = "WHERE (CASE WHEN i.store_id > 0 THEN i.store_id ELSE p.store_id END) = :filter_store";
    $allBillParams[':filter_store'] = $filter_store;
}

$allBillStmt = $pdo->prepare("
    SELECT id.*, p.product_name, p.barcode, p.unit AS base_unit,
    (
        CASE 
            WHEN (pb.quantity IS NOT NULL AND pb.initial_qty IS NOT NULL AND pb.quantity < pb.initial_qty)
                 OR (p.qty < id.total_base_qty) THEN 1 
            ELSE 0 
        END
    ) AS has_movement
    FROM import_details id
    JOIN imports i ON id.import_id = i.import_id
    LEFT JOIN products p ON (id.product_id = p.product_id AND p.store_id = (CASE WHEN i.store_id > 0 THEN i.store_id ELSE 1 END))
    LEFT JOIN product_batches pb ON id.import_detail_id = pb.import_detail_id
    {$allBillWhere}
    GROUP BY id.import_detail_id, p.product_name, p.barcode, p.unit
    ORDER BY id.import_detail_id ASC
");
$allBillStmt->execute($allBillParams);
$allBillItems = $allBillStmt->fetchAll();

$billItemsMap = [];
foreach ($allBillItems as $bi) {
    $bi['invoice_number'] = '';
    $billItemsMap[$bi['import_id']][] = $bi;
}

$importList = [];
if ($view_type === 'bill') {
    $billQuery = "
        SELECT 
            i.import_id,
            i.invoice_number,
            i.supplier_name,
            i.import_date,
            i.total_cost,
            i.notes,
            u.username,
            u.fname,
            u.lname,
            s.store_name,
            s.store_id,
            COUNT(DISTINCT id.import_detail_id) AS item_count,
            SUM(id.total_base_qty) AS sum_base_qty,
            MAX(
                CASE 
                    WHEN (pb.quantity IS NOT NULL AND pb.initial_qty IS NOT NULL AND pb.quantity < pb.initial_qty)
                         OR (p.qty < id.total_base_qty) THEN 1 
                    ELSE 0 
                END
            ) AS has_movement
        FROM imports i
        LEFT JOIN import_details id ON i.import_id = id.import_id
        LEFT JOIN products p ON (id.product_id = p.product_id AND p.store_id = (CASE WHEN i.store_id > 0 THEN i.store_id ELSE 1 END))
        LEFT JOIN tbstore s ON (CASE WHEN i.store_id > 0 THEN i.store_id ELSE p.store_id END) = s.store_id
        LEFT JOIN product_batches pb ON id.import_detail_id = pb.import_detail_id
        LEFT JOIN tbuser u ON i.created_by = u.Id
        {$whereClause}
        GROUP BY i.import_id
        ORDER BY i.import_date DESC, i.import_id DESC
    ";
    $stmt = $pdo->prepare($billQuery);
    $stmt->execute($params);
    $importList = $stmt->fetchAll();
} else {
    $detailQuery = "
        SELECT 
            id.import_detail_id,
            id.import_id,
            id.product_id,
            id.quantity,
            id.unit_name,
            id.multiplier,
            id.total_base_qty,
            id.cost_price,
            id.total_cost,
            id.expiry_date,
            i.invoice_number,
            i.supplier_name,
            i.import_date,
            p.product_name,
            p.qty AS current_product_qty,
            p.unit AS base_unit,
            s.store_name,
            s.store_id,
            u.username,
            u.fname,
            u.lname,
            pb.initial_qty AS batch_initial_qty,
            pb.quantity AS batch_rem_qty
        FROM import_details id
        JOIN imports i ON id.import_id = i.import_id
        LEFT JOIN products p ON (id.product_id = p.product_id AND p.store_id = (CASE WHEN i.store_id > 0 THEN i.store_id ELSE 1 END))
        LEFT JOIN tbstore s ON (CASE WHEN i.store_id > 0 THEN i.store_id ELSE p.store_id END) = s.store_id
        LEFT JOIN tbuser u ON i.created_by = u.Id
        LEFT JOIN product_batches pb ON id.import_detail_id = pb.import_detail_id
        {$whereClause}
        GROUP BY id.import_detail_id
        ORDER BY i.import_date DESC, id.import_detail_id DESC
    ";
    $stmt = $pdo->prepare($detailQuery);
    $stmt->execute($params);
    $importList = $stmt->fetchAll();
}

$total_records = count($importList);
$grandTotalCost = 0;
foreach ($importList as $gRow) { $grandTotalCost += (float)($gRow['total_cost'] ?? 0); }

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/import_stock.css?v=<?php echo filemtime(__DIR__ . '/../../themes/import_stock.css'); ?>">

<div class="content-wrapper bg-light">
  
  <!-- Page Header -->
  <section class="content-header py-3">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h4 class="m-0 font-weight-bold text-dark" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-list-alt text-success mr-2"></i> <?php echo htmlspecialchars(t('import_list.page_title', 'ລາຍການສິນຄ້າຮັບເຂົ້າ')); ?>
          </h4>
        </div>
        <div class="col-sm-6 text-right">
          <a href="import_stock.php" class="btn btn-primary font-weight-bold shadow-sm" style="border-radius: 8px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('import_list.import_stock_btn', 'ນຳເຂົ້າສິນຄ້າ')); ?>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Main Content -->
  <section class="content pb-5">
    <div class="container-fluid">

      <!-- Alert Notification & Success Popup Modal -->
      <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show shadow-sm mb-4" role="alert" style="border-radius: 10px;">
          <i class="fas <?php echo ($message_type === 'success') ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-2"></i>
          <?php echo htmlspecialchars($message); ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <?php if ($message_type === 'success'): ?>
          <script>
            $(document).ready(function() {
              Swal.fire({
                title: '<?php echo htmlspecialchars(t('import_list.swal_success_title', 'ດຳເນີນການສຳເລັດ!'), ENT_QUOTES); ?>',
                text: '<?php echo addslashes($message); ?>',
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: '<?php echo htmlspecialchars(t('import_list.ok_button', 'ຕົກລົງ'), ENT_QUOTES); ?>',
                timer: 3500,
                timerProgressBar: true
              });
            });
          </script>
        <?php endif; ?>
      <?php endif; ?>

      <!-- DATE RANGE & VIEW TYPE FILTER CARD -->
      <?php require_once __DIR__ . '/components/filter_bar.php'; ?>

      <!-- MAIN TABLE CARD -->
      <div class="card shadow-sm border-0" style="border-radius: 16px;">
        <div class="card-header bg-white border-bottom py-3 px-4">
          <div class="row align-items-center">
            <div class="col-md-6">
              <h5 class="card-title font-weight-bold text-dark mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
                <i class="fas fa-table text-primary mr-2"></i> <?php echo htmlspecialchars(t('import_list.table_card_title', 'ຕາຕະລາງລາຍການສິນຄ້າຮັບເຂົ້າ')); ?>
              </h5>
            </div>
            <div class="col-md-6 text-right d-flex align-items-center justify-content-end">
              <span class="badge badge-success px-3 py-2 font-weight-bold" style="font-size: 0.9rem; height: 34px; line-height: 18px;">
                <?php echo htmlspecialchars(t('import_list.total_prefix', 'ລວມ')); ?> <?php echo number_format($total_records); ?> <?php echo ($view_type === 'bill') ? htmlspecialchars(t('import_list.unit_bills', 'ໃບບິນ')) : htmlspecialchars(t('import_list.unit_items', 'ລາຍການ')); ?>
              </span>
            </div>
          </div>
        </div>

        <div class="card-body p-4">
          <!-- Search & Page Size Bar -->
          <div class="row align-items-center mb-3">
            <div class="col-md-4 mb-2 mb-md-0">
              <div class="d-flex align-items-center">
                <!-- <span class="mr-2 font-weight-bold text-muted" style="font-size: 0.88rem;">ສະແດງ:</span> -->
                <select id="pageSizeSelect" class="form-control form-control-sm" style="width: 100px; height: 38px; border-radius: 6px;" onchange="changePageSize(this.value)">
                  <option value="10" selected>10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>
            </div>

            <div class="col-md-8 text-md-right">
              <div class="input-group input-group-sm ml-md-auto" style="max-width: 340px;">
                <div class="input-group-prepend">
                  <span class="input-group-text bg-white border-right-0" style="border-radius: 6px 0 0 6px;"><i class="fas fa-search text-muted"></i></span>
                </div>
                <input type="text" id="import_search" class="form-control border-left-0" placeholder="<?php echo htmlspecialchars(t('import_list.search_placeholder', 'ຄົ້ນຫາ ໃບບິນ, ຊື່ສິນຄ້າ, ຜູ້ສະໜອງ...')); ?>" onkeyup="filterImportHistory()" style="border-radius: 0 6px 6px 0; height: 36px;">
              </div>
            </div>
          </div>

          <!-- TABLE RENDER: VIEW TYPE == 'bill' OR 'detail' -->
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle text-nowrap">
              
              <?php if ($view_type === 'bill'): ?>
                <!-- HEADER FOR MODE 1: ຕາມບິນ (Blue Header) -->
                <thead class=" font-weight-bold">
                  <tr>
                    <th class="text-center" style="width: 50px;"><?php echo htmlspecialchars(t('import_list.col_no', 'ລຳດັບ')); ?></th>
                    <th class="text-center"><?php echo htmlspecialchars(t('import_list.col_import_date', 'ວັນທີຮັບເຂົ້າ')); ?></th>
                    <th class="text-center"><?php echo htmlspecialchars(t('import_list.col_invoice_no', 'ເລກທີໃບບິນ')); ?></th>
                    <th><?php echo htmlspecialchars(t('import_list.col_store', 'ສາຂາ')); ?></th>
                    <th><?php echo htmlspecialchars(t('import_list.col_supplier', 'ຜູ້ສະໜອງ')); ?></th>
                    <th class="text-center"><?php echo htmlspecialchars(t('import_list.col_item_count', 'ຈຳນວນລາຍການ')); ?></th>
                    <th class="text-right"><?php echo htmlspecialchars(t('import_list.col_total_cost', 'ມູນຄ່າທຶນລວມ')); ?></th>
                    <th><?php echo htmlspecialchars(t('import_list.col_creator', 'ຜູ້ບັນທຶກ')); ?></th>
                    <th><?php echo htmlspecialchars(t('import_list.col_notes', 'ໝາຍເຫດ')); ?></th>
                    <th class="text-center" style="width: 120px;"><?php echo htmlspecialchars(t('import_list.col_actions', 'ຈັດການ')); ?></th>
                  </tr>
                </thead>
                <tbody id="historyTableBody">
                  <?php if (empty($importList)): ?>
                    <tr>
                      <td colspan="10" class="text-center text-muted py-5">
                        <i class="fas fa-box-open fa-3x d-block mb-2 text-muted" style="opacity: 0.4;"></i>
                        <?php echo htmlspecialchars(t('import_list.empty_bills', 'ບໍ່ມີລາຍການໃບບິນຮັບເຂົ້າໃນຊ່ວງວັນທີນີ້')); ?>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php $idx = 1; foreach ($importList as $row): ?>
                      <?php 
                        $importDate = !empty($row['import_date']) ? date('d/m/Y', strtotime($row['import_date'])) : '-';
                        $creatorName = trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
                        if (empty($creatorName)) $creatorName = $row['username'] ?? 'Admin';

                        $searchStr = strtolower(($row['invoice_number'] ?? '') . ' ' . ($row['supplier_name'] ?? '') . ' ' . ($row['store_name'] ?? '') . ' ' . ($row['notes'] ?? '') . ' ' . $creatorName);
                        $hasMov = !empty($row['has_movement']);
                      ?>
                      <tr class="import-row" data-search="<?php echo htmlspecialchars($searchStr); ?>" data-cost="<?php echo (float)($row['total_cost'] ?? 0); ?>">
                        <td class="align-middle text-center text-muted font-weight-bold row-index"><?php echo $idx++; ?></td>
                        
                        <td class="align-middle text-center text-secondary font-weight-bold" style="font-size: 0.9rem;">
                          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo $importDate; ?>
                        </td>

                        <td class="align-middle text-center font-weight-bold">
                          <span class="invoice-badge"><?php echo htmlspecialchars($row['invoice_number']); ?></span>
                        </td>

                        <td class="align-middle font-weight-bold">
                          <span class="badge badge-light border text-primary px-2 py-1" style="font-size: 0.82rem;">
                            <i class="fas fa-store mr-1 text-primary"></i><?php echo htmlspecialchars($row['store_name'] ?: t('import_list.main_store_default', 'ສາຂາຫຼັກ')); ?>
                          </span>
                        </td>

                        <td class="align-middle font-weight-bold text-dark">
                          <?php if (!empty($row['supplier_name'])): ?>
                            <i class="fas fa-building text-secondary mr-1"></i> <?php echo htmlspecialchars($row['supplier_name']); ?>
                          <?php else: ?>
                            <span class="text-muted font-weight-normal">-</span>
                          <?php endif; ?>
                        </td>

                        <td class="align-middle text-center font-weight-bold text-info">
                          <span class="badge badge-info px-2 py-1" style="font-size: 0.88rem;">
                            <?php echo number_format($row['item_count']); ?> <?php echo htmlspecialchars(t('import_list.unit_items', 'ລາຍການ')); ?>
                          </span>
                        </td>

                        <td class="align-middle text-right font-weight-bold text-success" style="font-size: 1rem;">
                          <?php echo displayMoney($pdo, $row['total_cost']); ?>
                        </td>

                        <td class="align-middle text-secondary font-weight-bold" style="font-size: 0.88rem;">
                          <i class="fas fa-user-circle mr-1 text-muted"></i> <?php echo htmlspecialchars($creatorName); ?>
                        </td>

                        <td class="align-middle text-muted" style="font-size: 0.88rem;">
                          <?php echo !empty($row['notes']) ? htmlspecialchars($row['notes']) : '-'; ?>
                        </td>

                        <td class="text-center align-middle">
                          <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-info btn-view-bill" title="<?php echo htmlspecialchars(t('import_list.view_bill_title', 'ເບິ່ງລາຍລະອຽດສິນຄ້າໃນບິນ')); ?>" data-id="<?php echo $row['import_id']; ?>" data-invoice="<?php echo htmlspecialchars($row['invoice_number']); ?>" data-supplier="<?php echo htmlspecialchars($row['supplier_name'] ?? ''); ?>" data-date="<?php echo $importDate; ?>" data-cost="<?php echo floatval($row['total_cost']); ?>">
                              <i class="fas fa-eye"></i>
                            </button>

                            <a href="print_import.php?import_id=<?php echo $row['import_id']; ?>" target="_blank" class="btn btn-outline-primary" title="<?php echo htmlspecialchars(t('import_list.print_bill_title', 'ພິມໃບບິນ')); ?>">
                              <i class="fas fa-print"></i>
                            </a>

                            <?php if (hasPermission('import_list', 'del') || hasPermission('import_stock', 'del')): ?>
                              <?php if (!$hasMov): ?>
                                <button type="button" class="btn btn-outline-danger btn-delete-master-bill" title="<?php echo htmlspecialchars(t('import_list.delete_bill_title', 'ລົບບິນນີ້')); ?>" data-id="<?php echo $row['import_id']; ?>" data-invoice="<?php echo htmlspecialchars($row['invoice_number']); ?>">
                                  <i class="fas fa-trash-alt"></i>
                                </button>
                              <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary disabled" title="<?php echo htmlspecialchars(t('import_list.cannot_delete_movement_title', 'ບໍ່ສາມາດລົບໄດ້ ເນື່ອງຈາກສິນຄ້າມີການເຄື່ອນໄຫວແລ້ວ')); ?>">
                                  <i class="fas fa-lock"></i>
                                </button>
                              <?php endif; ?>
                            <?php endif; ?>
                          </div>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
                <?php if (!empty($importList)): ?>
                <tfoot>
                  <tr style="background:#f8fafc;font-weight:800;border-top:2px solid #cbd5e1;">
                    <td colspan="6" class="text-right"><?php echo htmlspecialchars(t('import_list.grand_total', 'ລວມທັງໝົດ:')); ?></td>
                    <td class="text-right text-success" style="font-size:1rem;" id="importGrandTotal"><?php echo displayMoney($pdo, $grandTotalCost); ?></td>
                    <td colspan="3"></td>
                  </tr>
                </tfoot>
                <?php endif; ?>

              <?php else: ?>
                <!-- HEADER FOR MODE 2: ລາຍລະອຽດ (Blue Header) -->
                <thead class="font-weight-bold">
                  <tr>
                    <th class="text-center" style="width: 50px;"><?php echo htmlspecialchars(t('import_list.col_no', 'ລຳດັບ')); ?></th>
                    <th class="text-center"><?php echo htmlspecialchars(t('import_list.col_import_date', 'ວັນທີຮັບເຂົ້າ')); ?></th>
                    <th class="text-center"><?php echo htmlspecialchars(t('import_list.col_invoice_no', 'ເລກທີໃບບິນ')); ?></th>
                    <th><?php echo htmlspecialchars(t('import_list.col_store', 'ສາຂາ')); ?></th>
                    <th><?php echo htmlspecialchars(t('import_list.col_product_name', 'ຊື່ສິນຄ້າ')); ?></th>
                    <th class="text-center"><?php echo htmlspecialchars(t('import_list.col_received_qty', 'ຈຳນວນຮັບເຂົ້າ')); ?></th>
                    <th class="text-center"><?php echo htmlspecialchars(t('import_list.col_total_base_qty', 'ຈຳນວນຍ່ອຍລວມ')); ?></th>
                    <th class="text-right"><?php echo htmlspecialchars(t('import_list.col_total_cost', 'ມູນຄ່າທຶນລວມ')); ?></th>
                    <th class="text-center"><?php echo htmlspecialchars(t('import_list.col_expiry_date', 'ວັນໝົດອາຍຸ')); ?></th>
                    <th><?php echo htmlspecialchars(t('import_list.col_creator', 'ຜູ້ບັນທຶກ')); ?></th>
                    <th class="text-center" style="width: 110px;"><?php echo htmlspecialchars(t('import_list.col_actions', 'ຈັດການ')); ?></th>
                  </tr>
                </thead>
                <tbody id="historyTableBody">
                  <?php if (empty($importList)): ?>
                    <tr>
                      <td colspan="11" class="text-center text-muted py-5">
                        <i class="fas fa-box-open fa-3x d-block mb-2 text-muted" style="opacity: 0.4;"></i>
                        <?php echo htmlspecialchars(t('import_list.empty_details', 'ບໍ່ມີລາຍການສິນຄ້າຮັບເຂົ້າໃນຊ່ວງວັນທີນີ້')); ?>
                      </td>
                    </tr>
                  <?php else: ?>
                    <?php $idx = 1; foreach ($importList as $row): ?>
                      <?php 
                        $importDate = !empty($row['import_date']) ? date('d/m/Y', strtotime($row['import_date'])) : '-';
                        
                        // Expiry badge evaluation
                        $expBadge = '<span class="text-muted">-</span>';
                        if (!empty($row['expiry_date'])) {
                            $expTime = strtotime($row['expiry_date']);
                            $nowTime = time();
                            $daysDiff = floor(($expTime - $nowTime) / (60 * 60 * 24));
                            $formattedExp = date('d/m/Y', $expTime);

                            if ($daysDiff < 0) {
                                $expBadge = '<span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i> ' . htmlspecialchars(t('import_list.expired_label', 'ໝົດອາຍຸ')) . ' (' . $formattedExp . ')</span>';
                            } elseif ($daysDiff <= 30) {
                                $expBadge = '<span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-exclamation-triangle mr-1"></i> ' . htmlspecialchars(t('import_list.near_expiry_label', 'ໃກ້ໝົດອາຍຸ')) . ' (' . $formattedExp . ')</span>';
                            } else {
                                $expBadge = '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> ' . $formattedExp . '</span>';
                            }
                        }

                        $creatorName = trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
                        if (empty($creatorName)) $creatorName = $row['username'] ?? 'Admin';

                        $searchStr = strtolower(($row['invoice_number'] ?? '') . ' ' . ($row['product_name'] ?? '') . ' ' . ($row['supplier_name'] ?? '') . ' ' . ($row['store_name'] ?? '') . ' ' . $creatorName);

                        // Stock movement check
                        $batchRem     = isset($row['batch_rem_qty']) ? intval($row['batch_rem_qty']) : null;
                        $batchInit    = isset($row['batch_initial_qty']) ? intval($row['batch_initial_qty']) : null;
                        $productQty   = intval($row['current_product_qty']);
                        $totalBaseQty = intval($row['total_base_qty']);

                        $hasMovement = false;
                        if ($batchRem !== null && $batchInit !== null && $batchRem < $batchInit) {
                            $hasMovement = true;
                        } elseif ($productQty < $totalBaseQty) {
                            $hasMovement = true;
                        }
                      ?>
                      <tr class="import-row" data-search="<?php echo htmlspecialchars($searchStr); ?>" data-cost="<?php echo (float)($row['total_cost'] ?? 0); ?>">
                        <td class="align-middle text-center text-muted font-weight-bold row-index"><?php echo $idx++; ?></td>
                        
                        <td class="align-middle text-center text-secondary font-weight-bold" style="font-size: 0.88rem;">
                          <?php echo $importDate; ?>
                        </td>

                        <td class="align-middle text-center font-weight-bold">
                          <span class="invoice-badge"><?php echo htmlspecialchars($row['invoice_number']); ?></span>
                        </td>

                        <td class="align-middle font-weight-bold">
                          <span class="badge badge-light border text-primary px-2 py-1" style="font-size: 0.82rem;">
                            <i class="fas fa-store mr-1 text-primary"></i><?php echo htmlspecialchars($row['store_name'] ?: t('import_list.main_store_default', 'ສາຂາຫຼັກ')); ?>
                          </span>
                        </td>

                        <td class="align-middle font-weight-bold text-dark">
                          <?php echo htmlspecialchars($row['product_name']); ?>
                          <?php if (!empty($row['supplier_name'])): ?>
                            <small class="text-muted d-block font-weight-normal"><i class="fas fa-store mr-1"></i> <?php echo htmlspecialchars($row['supplier_name']); ?></small>
                          <?php endif; ?>
                        </td>

                        <td class="align-middle text-center font-weight-bold text-dark">
                          <?php echo number_format($row['quantity']); ?> <?php echo htmlspecialchars($row['unit_name']); ?>
                        </td>

                        <td class="align-middle text-center font-weight-bold text-info">
                          <?php echo number_format($row['total_base_qty']); ?> <?php echo htmlspecialchars($row['base_unit'] ?: t('import_list.default_unit', 'ອັນ')); ?>
                        </td>

                        <td class="align-middle text-right font-weight-bold text-primary">
                          <?php echo displayMoney($pdo, $row['total_cost']); ?>
                        </td>

                        <td class="align-middle text-center">
                          <?php echo $expBadge; ?>
                        </td>

                        <td class="align-middle text-secondary font-weight-bold" style="font-size: 0.88rem;">
                          <i class="fas fa-user-circle mr-1 text-muted"></i> <?php echo htmlspecialchars($creatorName); ?>
                        </td>

                        <td class="text-center align-middle">
                          <?php $rowJson = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8'); ?>
                          <?php if (hasPermission('import_list', 'edit') || hasPermission('import_stock', 'edit') || hasPermission('import_list', 'del') || hasPermission('import_stock', 'del')): ?>
                            <div class="btn-group btn-group-sm">
                              <?php if (hasPermission('import_list', 'edit') || hasPermission('import_stock', 'edit')): ?>
                                <button type="button" class="btn btn-outline-warning btn-edit-import" title="<?php echo htmlspecialchars(t('import_list.edit_title', 'ແກ້ໄຂ')); ?>" data-id="<?php echo $row['import_detail_id']; ?>" data-json="<?php echo $rowJson; ?>">
                                  <i class="fas fa-edit"></i>
                                </button>
                              <?php endif; ?>
                              <?php if (hasPermission('import_list', 'del') || hasPermission('import_stock', 'del')): ?>
                                <?php if (!$hasMovement): ?>
                                  <button type="button" class="btn btn-outline-danger btn-delete-import" title="<?php echo htmlspecialchars(t('import_list.cancel_delete_title', 'ຍົກເລີກ/ລົບບິນນີ້')); ?>" data-id="<?php echo $row['import_detail_id']; ?>" data-invoice="<?php echo htmlspecialchars($row['invoice_number']); ?>" data-name="<?php echo htmlspecialchars($row['product_name']); ?>">
                                    <i class="fas fa-trash-alt"></i>
                                  </button>
                                <?php else: ?>
                                  <button type="button" class="btn btn-outline-secondary disabled" title="<?php echo htmlspecialchars(t('import_list.cannot_cancel_movement_title', 'ບໍ່ສາມາດຍົກເລີກໄດ້ ເນື່ອງຈາກສິນຄ້າມີການເຄື່ອນໄຫວແລ້ວ')); ?>">
                                    <i class="fas fa-lock"></i>
                                  </button>
                                <?php endif; ?>
                              <?php endif; ?>
                            </div>
                          <?php else: ?>
                            <span class="badge badge-light text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('import_list.view_only', 'ເບິ່ງຢ່າງດຽວ')); ?></span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
                <?php if (!empty($importList)): ?>
                <tfoot>
                  <tr style="background:#f8fafc;font-weight:800;border-top:2px solid #cbd5e1;">
                    <td colspan="7" class="text-right"><?php echo htmlspecialchars(t('import_list.grand_total', 'ລວມທັງໝົດ:')); ?></td>
                    <td class="text-right text-primary" style="font-size:1rem;" id="importGrandTotal"><?php echo displayMoney($pdo, $grandTotalCost); ?></td>
                    <td colspan="3"></td>
                  </tr>
                </tfoot>
                <?php endif; ?>
              <?php endif; ?>

            </table>
          </div>

          <!-- Pagination Bar -->
          <div class="d-flex flex-column flex-md-row align-items-center justify-content-between pt-4 px-2">
            <div class="d-flex justify-content-end ml-md-auto">
              <ul class="pagination pagination-circle mb-0" id="importPagination">
              </ul>
            </div>
          </div>

        </div>
      </div>

    </div>
  </section>
</div>

<!-- MODALS & COMPONENTS -->
<?php require_once __DIR__ . '/components/modal_edit_import.php'; ?>
<?php require_once __DIR__ . '/components/modal_bill_details.php'; ?>
<?php require_once __DIR__ . '/components/modal_edit_master_bill.php'; ?>
<?php require_once __DIR__ . '/components/modal_select_product.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<!-- JAVASCRIPT LOGIC -->
<?php require_once __DIR__ . '/components/import_list_js.php'; ?>
