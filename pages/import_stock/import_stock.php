<?php
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../../config/db.php';

// Check authorization
if (empty($_SESSION['user_id']) || (!hasPermission('import_stock') && ($_SESSION['status'] ?? '') !== 'ຜູ້ບໍລິຫານ' && intval($_SESSION['user_id'] ?? 0) !== 1)) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

// Let a receipt line record the sell price applied at receipt time (nullable, additive migration)
try { $pdo->exec("ALTER TABLE import_details ADD COLUMN sell_price DECIMAL(15,2) NULL AFTER cost_price"); } catch (Throwable $e) {}

// ====== HANDLE POST ACTIONS ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- 1. ACTION: IMPORT STOCK BILL (MULTI-ITEM OR SINGLE ITEM) ---
    if ($_POST['action'] === 'import_stock_bill') {
        if (!hasPermission('import_stock', 'add')) {
            $message = t('import_stock.err_no_permission', 'ທ່ານບໍ່ມີສິດໃນການເພີ່ມ ຫຼື ບັນທຶກຮັບເຂົ້າສິນຄ້າ!');
            $message_type = 'warning';
        } else {
        $invoice_number = trim($_POST['invoice_number'] ?? '');
        $supplier_name  = trim($_POST['supplier_name'] ?? '');
        $raw_date       = trim($_POST['import_date'] ?? '');
        $import_date    = !empty($raw_date) ? $raw_date . ' ' . date('H:i:s') : date('Y-m-d H:i:s');
        $notes          = trim($_POST['notes'] ?? '');
        $cart_json      = $_POST['cart_json'] ?? '[]';
        $items          = json_decode($cart_json, true);

        if ($invoice_number === '') {
            $maxImpId = (int)$pdo->query("SELECT IFNULL(MAX(import_id), 0) + 1 FROM imports")->fetchColumn();
            $invoice_number = str_pad($maxImpId, 6, '0', STR_PAD_LEFT);
        }

        if (!empty($items) && is_array($items)) {
            try {
                $pdo->beginTransaction();

                // Calculate bill total cost
                $total_bill_cost = 0;
                foreach ($items as $it) {
                    $q = intval($it['quantity'] ?? 0);
                    $c = floatval($it['cost_price'] ?? 0);
                    $total_bill_cost += ($q * $c);
                }

                $activeStoreId = getActiveStoreId($pdo);

                // Insert master `imports` record with selected import_date and store_id
                $insImp = $pdo->prepare("INSERT INTO imports (invoice_number, supplier_name, import_date, total_cost, created_by, notes, store_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insImp->execute([$invoice_number, $supplier_name, $import_date, $total_bill_cost, $_SESSION['user_id'], $notes, $activeStoreId]);
                $import_id = $pdo->lastInsertId();

                $insertedCount = 0;
                foreach ($items as $item) {
                    $product_id   = intval($item['product_id'] ?? 0);
                    $unit_key     = trim($item['unit_key'] ?? 'base');
                    $quantity     = intval($item['quantity'] ?? 0);
                    $cost_price   = floatval($item['cost_price'] ?? 0);
                    $sell_price   = floatval($item['sell_price'] ?? 0);
                    $expiry_date  = !empty($item['expiry_date']) ? $item['expiry_date'] : null;

                    if ($product_id <= 0 || $quantity <= 0) continue;

                    // Fetch Product
                    $activeStoreId = getActiveStoreId($pdo);
                    $pStmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ? AND store_id = ? FOR UPDATE");
                    $pStmt->execute([$product_id, $activeStoreId]);
                    $product = $pStmt->fetch();

                    if (!$product) continue;

                    $unit_name = !empty($product['unit']) ? $product['unit'] : t('import_stock.default_unit', 'ອັນ');
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

                    $total_base_qty = $quantity * $multiplier;
                    $total_cost     = $quantity * $cost_price;

                    // Insert into import_details
                    $insDet = $pdo->prepare("INSERT INTO import_details (import_id, product_id, unit_name, multiplier, quantity, total_base_qty, cost_price, sell_price, total_cost, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insDet->execute([
                        $import_id, $product_id, $unit_name, $multiplier,
                        $quantity, $total_base_qty, $cost_price, ($sell_price > 0 ? $sell_price : null), $total_cost, $expiry_date
                    ]);
                    $import_detail_id = $pdo->lastInsertId();

                    // Insert into product_batches
                    $insBatch = $pdo->prepare("INSERT INTO product_batches (product_id, import_detail_id, expiry_date, initial_qty, quantity) VALUES (?, ?, ?, ?, ?)");
                    $insBatch->execute([$product_id, $import_detail_id, $expiry_date, $total_base_qty, $total_base_qty]);

                    // Update product stock + buy price, and the sell price too when one was entered
                    if ($sell_price > 0) {
                        $updProd = $pdo->prepare("UPDATE products SET qty = qty + ?, bprice = ?, price = ? WHERE product_id = ? AND store_id = ?");
                        $updProd->execute([$total_base_qty, $cost_price, $sell_price, $product_id, $activeStoreId]);
                    } else {
                        $updProd = $pdo->prepare("UPDATE products SET qty = qty + ?, bprice = ? WHERE product_id = ? AND store_id = ?");
                        $updProd->execute([$total_base_qty, $cost_price, $product_id, $activeStoreId]);
                    }

                    $insertedCount++;
                }

                $pdo->commit();
                $message = sprintf(t('import_stock.msg_bill_success', 'ບັນທຶກໃບບິນຮັບສິນຄ້າເຂົ້າເລກທີ %s ສຳເລັດ (ລວມ %s ລາຍການ)!'), $invoice_number, $insertedCount);
                $message_type = 'success';
                logActivity($pdo, "ຮັບສິນຄ້າເຂົ້າ (ໃບບິນ)", "ໃບບິນ: $invoice_number, $insertedCount ລາຍການ, ລວມ: " . number_format($total_bill_cost) . " ₭");
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message = t('import_stock.err_prefix', 'ຜິດພາດ: ') . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            $message = t('import_stock.err_no_items', 'ກະລຸນາເພີ່ມສິນຄ້າໃສ່ລາຍການຮັບເຂົ້າຢ່າງນ້ອຍ 1 ລາຍການ!');
            $message_type = 'danger';
        }
        }
    }

    // --- 2. ACTION: DELETE/CANCEL IMPORT DETAIL ---
    elseif ($_POST['action'] === 'delete_import_detail') {
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
                        throw new Exception(t('import_stock.err_has_movement', 'ບໍ່ສາມາດລົບ/ຍົກເລີກໃບບິນນີ້ໄດ້ ເນື່ອງຈາກສິນຄ້ານີ້ມີການເຄື່ອນໄຫວ ຫຼື ຖືກຂາຍອອກໄປແລ້ວ!'));
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

                    $message = t('import_stock.msg_cancel_success', 'ຍົກເລີກການຮັບສິນຄ້າເຂົ້າສຳເລັດ (ປັບສະຕັອກຄືນແລ້ວ)!');
                    $message_type = 'success';
                    logActivity($pdo, "ຍົກເລີກການຮັບສິນຄ້າເຂົ້າ", "Detail ID: $import_detail_id");
                }

                $pdo->commit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
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
        $sell_price       = floatval($_POST['sell_price'] ?? 0);
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

                    $unit_name = !empty($product['unit']) ? $product['unit'] : t('import_stock.default_unit', 'ອັນ');
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

                    // Update products stock + buy price, and the sell price when one was entered
                    if ($sell_price > 0) {
                        $updStock = $pdo->prepare("UPDATE products SET qty = GREATEST(0, qty + ?), bprice = ?, price = ? WHERE product_id = ?");
                        $updStock->execute([$diff_base_qty, $cost_price, $sell_price, $product_id]);
                    } else {
                        $updStock = $pdo->prepare("UPDATE products SET qty = GREATEST(0, qty + ?), bprice = ? WHERE product_id = ?");
                        $updStock->execute([$diff_base_qty, $cost_price, $product_id]);
                    }

                    // Update master import
                    $updImp = $pdo->prepare("UPDATE imports SET supplier_name = ?, total_cost = GREATEST(0, total_cost + ?), notes = IF(? != '', ?, notes) WHERE import_id = ?");
                    $updImp->execute([$supplier_name, $diff_cost, $notes, $notes, $oldDet['import_id']]);

                    // Update import_details
                    $updDet = $pdo->prepare("UPDATE import_details SET unit_name = ?, multiplier = ?, quantity = ?, total_base_qty = ?, cost_price = ?, sell_price = ?, total_cost = ?, expiry_date = ? WHERE import_detail_id = ?");
                    $updDet->execute([$unit_name, $multiplier, $quantity, $new_total_base_qty, $cost_price, ($sell_price > 0 ? $sell_price : null), $new_total_cost, $expiry_date, $import_detail_id]);

                    // Update product_batches
                    $updBatch = $pdo->prepare("UPDATE product_batches SET expiry_date = ?, initial_qty = ?, quantity = GREATEST(0, quantity + ?) WHERE import_detail_id = ?");
                    $updBatch->execute([$expiry_date, $new_total_base_qty, $diff_base_qty, $import_detail_id]);

                    $message = t('import_stock.msg_edit_success', 'ດັດແກ້ຂໍ້ມູນການຮັບສິນຄ້າເຂົ້າສຳເລັດ!');
                    $message_type = 'success';
                    logActivity($pdo, "ແກ້ໄຂການຮັບສິນຄ້າເຂົ້າ", "Detail ID: $import_detail_id");
                }

                $pdo->commit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message = t('import_stock.err_prefix', 'ຜິດພາດ: ') . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// Fetch categories for modal filter
$activeStoreId = getActiveStoreId($pdo);
$catStmt = $pdo->prepare("SELECT category_id, category_name FROM categories WHERE store_id = ? ORDER BY category_name ASC");
$catStmt->execute([$activeStoreId]);
$categories = $catStmt->fetchAll();

// Fetch products for current active store with categories & multi-units
$prodStmt = $pdo->prepare("
    SELECT p.product_id, p.product_name, p.barcode, p.unit, p.bprice, p.price, p.qty, p.category_id, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.store_id = ?
    ORDER BY p.product_name ASC
");
$prodStmt->execute([$activeStoreId]);
$products = $prodStmt->fetchAll();

$product_units_map = [];
$uRows = $pdo->query("SELECT * FROM product_units ORDER BY multiplier ASC")->fetchAll();
foreach ($uRows as $u) {
    $product_units_map[$u['product_id']][] = $u;
}

// Fetch Import History
$userStoreId = intval($_SESSION['store_id'] ?? 1);
$isAdmin = ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' || strtolower($_SESSION['status'] ?? '') === 'admin' || ($_SESSION['user_id'] ?? 0) == 1;
$isMain = isMainBranch($pdo, $userStoreId);

$historyWhere = (!$isAdmin && !$isMain) ? " WHERE p.store_id = :store_id " : "";
$historyQuery = "
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
        u.username,
        u.fname,
        u.lname,
        pb.initial_qty AS batch_initial_qty,
        pb.quantity AS batch_rem_qty
    FROM import_details id
    JOIN imports i ON id.import_id = i.import_id
    LEFT JOIN products p ON (id.product_id = p.product_id AND (p.store_id = i.store_id OR i.store_id <= 1))
    LEFT JOIN tbuser u ON i.created_by = u.Id
    LEFT JOIN product_batches pb ON id.import_detail_id = pb.import_detail_id
    {$historyWhere}
    GROUP BY id.import_detail_id, p.product_name, p.barcode, p.qty, p.unit, u.username, u.fname, u.lname, pb.initial_qty, pb.quantity
    ORDER BY id.import_detail_id DESC
";
$histStmt = $pdo->prepare($historyQuery);
if (!$isAdmin && !$isMain) {
    $histStmt->execute([':store_id' => $activeStoreId]);
} else {
    $histStmt->execute();
}
$importHistory = $histStmt->fetchAll();
$total_imports = count($importHistory);

// Next available auto invoice number (000001, 000002, 000003...)
$maxImpId = (int)$pdo->query("SELECT IFNULL(MAX(import_id), 0) + 1 FROM imports")->fetchColumn();
$next_invoice = str_pad($maxImpId, 6, '0', STR_PAD_LEFT);

// Current User Receiver Name
$receiverName = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? ''));
if (empty($receiverName)) $receiverName = $_SESSION['username'] ?? 'Admin';

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
            <i class="fas fa-truck-loading text-primary mr-2"></i> <?php echo htmlspecialchars(t('import_stock.page_title', 'ຮັບສິນຄ້າເຂົ້າສະຕັອກ')); ?>
          </h4>
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
                title: '<?php echo htmlspecialchars(t('import_stock.swal_saved_title', 'ບັນທຶກສຳເລັດ!'), ENT_QUOTES); ?>',
                text: '<?php echo addslashes($message); ?>',
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: '<?php echo htmlspecialchars(t('import_stock.ok_button', 'ຕົກລົງ'), ENT_QUOTES); ?>',
                timer: 3500,
                timerProgressBar: true
              });
            });
          </script>
        <?php endif; ?>
      <?php endif; ?>

      <!-- 1. DIRECT ENTRY FORM CARD (ຟອມບັນທຶກຮັບສິນຄ້າເຂົ້າ ໃນໜ້າຈໍເລີຍ) -->
      <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px;">

        <div class="card-body p-4">
          <form id="directImportForm" action="" method="POST">
            <input type="hidden" name="action" value="import_stock_bill">
            <input type="hidden" id="cart_json_input" name="cart_json" value="[]">

            <!-- Top Header Details Row: Invoice No, Date, Receiver, Supplier -->
            <div class="row bg-light p-3 rounded mb-3 border" style="border-color: #cbd5e1 !important;">
              <!-- 1. Auto Invoice Number -->
              <div class="col-md-3 mb-2 mb-md-0">
                <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
                  <?php echo htmlspecialchars(t('import_stock.invoice_no_label', 'ເລກທີໃບບິນຮັບເຂົ້າ')); ?> <small class="text-muted"></small>
                </label>
                <input type="text" name="invoice_number" id="invoice_number_input" class="form-control font-weight-bold bg-white text-primary border-primary" value="<?php echo $next_invoice; ?>" readonly style="height: 42px; border-radius: 8px; cursor: not-allowed; font-size: 1.05rem;">
              </div>

              <!-- 2. Import Date (Editable Date Picker - No Time) -->
              <div class="col-md-3 mb-2 mb-md-0">
                <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
                  <?php echo htmlspecialchars(t('import_stock.import_date_label', 'ວັນທີຮັບເຂົ້າ')); ?> <span class="text-danger">*</span>
                </label>
                <input type="date" name="import_date" id="import_date_input" class="form-control font-weight-bold" value="<?php echo date('Y-m-d'); ?>" required style="height: 42px; border-radius: 8px;">
              </div>

              <!-- 3. Receiver Name -->
              <div class="col-md-3 mb-2 mb-md-0">
                <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
                  <?php echo htmlspecialchars(t('import_stock.receiver_label', 'ຜູ້ບັນທຶກ / ຜູ້ຮັບເຂົ້າ')); ?>
                </label>
                <input type="text" class="form-control bg-white font-weight-bold" value="<?php echo htmlspecialchars($receiverName); ?>" readonly style="height: 42px; border-radius: 8px;">
              </div>

              <!-- 4. Supplier Name -->
              <div class="col-md-3">
                <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
                  <?php echo htmlspecialchars(t('import_stock.supplier_label', 'ຊື່ຜູ້ສະໜອງ / ຮ້ານສົ່ງ')); ?>
                </label>
                <input type="text" name="supplier_name" id="supplier_name_input" class="form-control" placeholder="<?php echo htmlspecialchars(t('import_stock.supplier_placeholder', 'ປ້ອນຊື່ຜູ້ສະໜອງ (ຖ້າມີ)')); ?>" style="height: 42px; border-radius: 8px;">
              </div>
            </div>

            <!-- Barcode Scanning & Item Entry Row -->
            <div class="row align-items-end mb-3">
              <!-- Barcode / Search Box -->
              <div class="col-md-4 mb-3 mb-md-0">
                <label class="font-weight-bold text-dark mb-1">
                  <?php echo htmlspecialchars(t('import_stock.scan_barcode_label', 'ສະແກນບາໂຄ້ດ / ລະຫັດສິນຄ້າ')); ?> <span class="text-danger">*</span>
                </label>
                <div class="input-group">
                  <input type="text" id="direct_barcode_input" class="form-control" placeholder="<?php echo htmlspecialchars(t('import_stock.scan_barcode_placeholder', 'ສະແກນບາໂຄ້ດ ຫຼື ປ້ອນລະຫັດ...')); ?>" autocomplete="off" style="height: 42px; border-radius: 8px 0 0 8px; border: 1.5px solid #007bff;" oninput="onDirectBarcodeChange()" onkeydown="onDirectBarcodeKeyDown(event)">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-outline-primary font-weight-bold" data-toggle="modal" data-target="#productSelectModal" title="<?php echo htmlspecialchars(t('import_stock.open_select_product_modal', 'ເປີດປັອບອັບເລືອກສິນຄ້າ')); ?>" style="height: 42px; border: 1.5px solid #007bff !important; border-radius: 0 8px 8px 0 !important; background-color: #eff6ff; color: #0056b3;">
                      <i class="fas fa-search-plus mr-1"></i> <?php echo htmlspecialchars(t('import_stock.select_product_button', 'ເລືອກສິນຄ້າ')); ?>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Quantity Input (Positioned directly next to Barcode Box) -->
              <div class="col-md-2 mb-3 mb-md-0">
                <label class="font-weight-bold text-dark mb-1">
                  <?php echo htmlspecialchars(t('import_stock.qty_label', 'ຈຳນວນ')); ?> <span class="text-danger">*</span>
                </label>
                <input type="number" id="direct_qty" class="form-control font-weight-bold border-primary text-center" min="1" value="1" style="height: 42px; border-radius: 8px; font-size: 1.1rem;" onkeydown="onQtyKeyDown(event)">
              </div>

              <!-- Unit Select -->
              <div class="col-md-2 mb-3 mb-md-0">
                <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('import_stock.unit_label', 'ຫົວໜ່ວຍ')); ?></label>
                <select id="direct_unit_key" class="form-control" style="height: 42px; border-radius: 8px;">
                  <option value="base"><?php echo htmlspecialchars(t('import_stock.unit_label', 'ຫົວໜ່ວຍ')); ?></option>
                </select>
              </div>

              <!-- Expiry Date -->
              <div class="col-md-2 mb-3 mb-md-0">
                <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('import_stock.expiry_date_label', 'ວັນໝົດອາຍຸ')); ?></label>
                <input type="date" id="direct_expiry_date" class="form-control" style="height: 42px; border-radius: 8px;">
              </div>

              <!-- Add Item Button (buy/sell price are edited inline in the items table below) -->
              <div class="col-md-2">
                <button type="button" class="btn btn-primary font-weight-bold btn-block shadow-sm" style="height: 42px; border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;" onclick="addCurrentItemToCart()">
                  <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('import_stock.add_item_button', 'ເພີ່ມລາຍການ')); ?>
                </button>
              </div>
            </div>

            <!-- Active Product Selected Banner -->
            <div id="direct_matched_banner" class="p-2.5 px-3 rounded border d-none align-items-center justify-content-between mb-3" style="border-color: #93c5fd !important; background-color: #eff6ff !important;">
              <div>
                <span class="font-weight-bold text-dark d-block" id="direct_matched_name" style="font-size: 1rem;">-</span>
                <small class="text-muted">
                   <?php echo htmlspecialchars(t('import_stock.barcode_label', 'ບາໂຄ້ດ:')); ?> <span id="direct_matched_barcode" class="font-weight-bold text-primary mr-2">-</span>
                   <?php echo htmlspecialchars(t('import_stock.bprice_label', 'ລາຄາຊື້:')); ?> <span id="direct_matched_bprice" class="font-weight-bold text-danger mr-2">0 ₭</span>
                   <?php echo htmlspecialchars(t('import_stock.current_stock_label', 'ສະຕັອກປັດຈຸບັນ:')); ?> <span id="direct_matched_stock" class="font-weight-bold text-success">0</span>
                </small>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetDirectSelection()"><i class="fas fa-times"></i></button>
            </div>

            <!-- LIVE IMPORT ITEMS TABLE (ຕາຕະລາງລາຍການຮັບເຂົ້າ ໃນໜ້າຈໍ) — single-row rows, scrolls sideways on mobile -->
            <div class="border rounded mb-3 shadow-sm" style="border-radius: 12px; overflow: hidden; background-color: #ffffff;">
              <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
              <table class="table table-hover mb-0 align-middle text-nowrap" style="min-width: 720px;">
                <thead class="font-weight-bold">
                  <tr>
                    <th class="text-center" style="width: 50px;"><?php echo htmlspecialchars(t('import_stock.col_no', 'ລຳດັບ')); ?></th>
                    <th><?php echo htmlspecialchars(t('import_stock.col_product_name', 'ຊື່ສິນຄ້າ')); ?></th>
                    <th class="text-center" style="width: 160px;"><?php echo htmlspecialchars(t('import_stock.col_import_qty', 'ຈຳນວນຮັບເຂົ້າ')); ?></th>
                    <th class="text-right" style="width: 150px;"><?php echo htmlspecialchars(t('import_stock.col_bprice', 'ລາຄາຊື້')); ?></th>
                    <th class="text-right" style="width: 150px;"><?php echo htmlspecialchars(t('import_stock.col_sell_price', 'ລາຄາຂາຍ')); ?></th>
                    <th class="text-right" style="width: 160px;"><?php echo htmlspecialchars(t('import_stock.col_total', 'ລວມ')); ?></th>
                    <th class="text-center" style="width: 80px;"><?php echo htmlspecialchars(t('import_stock.col_actions', 'ຈັດການ')); ?></th>
                  </tr>
                </thead>
                <tbody id="cart_table_body">
                  <tr id="empty_cart_row">
                    <td colspan="7" class="text-center text-muted py-5">
                      <i class="fas fa-box-open fa-3x d-block mb-2 text-muted" style="opacity: 0.4;"></i>
                      <span class="font-weight-bold"><?php echo htmlspecialchars(t('import_stock.empty_cart_title', 'ຍັງບໍ່ມີລາຍການສິນຄ້າໃນໃບບິນ')); ?></span><br>
                      <small><?php echo htmlspecialchars(t('import_stock.empty_cart_hint', 'ກະລຸນາກະແກນບາໂຄ້ດ ຫຼື ກົດປຸ່ມ "ເລືອກສິນຄ້າ" ເພື່ອເພີ່ມສິນຄ້າຮັບເຂົ້າ')); ?></small>
                    </td>
                  </tr>
                </tbody>
              </table>
              </div>

              <!-- Table Pagination Bar: Displays when > 10 items -->
              <div id="cart_pagination_row" class="px-3 py-2 bg-light border-top d-none align-items-center justify-content-between" style="border-color: #cbd5e1 !important;">
                <div class="text-muted font-weight-bold" style="font-size: 0.88rem;">
                  <?php echo htmlspecialchars(t('import_stock.showing_prefix', 'ສະແດງ')); ?> <span id="cart_page_start" class="text-dark">1</span> - <span id="cart_page_end" class="text-dark">10</span> <?php echo htmlspecialchars(t('import_stock.showing_from_total', 'ຈາກທັງໝົດ')); ?> <span id="cart_page_total" class="text-primary">0</span> <?php echo htmlspecialchars(t('import_stock.items_unit', 'ລາຍການ')); ?>
                </div>
                <div>
                  <ul class="pagination pagination-circle mb-0 justify-content-end" id="cartTablePagination">
                  </ul>
                </div>
              </div>

              <!-- Table Footer Action Bar: Save Stock Button (Right Aligned) -->
              <div class="card-footer bg-light border-top p-3 d-flex align-items-center justify-content-end" style="border-color: #e2e8f0 !important;">
                <?php if (hasPermission('import_stock', 'add')): ?>
                  <button type="button" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="height: 38px; border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;" onclick="submitDirectImportBill()">
                    <i class="fas fa-save mr-1.5" style="font-size: 1.1rem;"></i> <?php echo htmlspecialchars(t('import_stock.save_stock_button', 'ບັນທຶກສະຕັອກ')); ?>
                  </button>
                <?php else: ?>
                  <span class="badge badge-light text-muted" style="font-size: 0.9rem;"><?php echo htmlspecialchars(t('import_stock.view_only', 'ເບິ່ງຢ່າງດຽວ')); ?></span>
                <?php endif; ?>
              </div>
            </div>

          </form>
        </div>
      </div>


      </div>
    </div>
  </section>
</div>

<!-- Modal: Select Product Popup -->
<?php require_once __DIR__ . '/components/modal_select_product.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<!-- JAVASCRIPT LOGIC -->
<?php require_once __DIR__ . '/components/import_stock_js.php'; ?>
