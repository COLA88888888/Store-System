<?php
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../../config/db.php';

// Authorization check
if (empty($_SESSION['user_id']) || (!hasPermission('stock_transfer') && intval($_SESSION['user_id'] ?? 0) !== 1)) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$userStoreId = intval($_SESSION['store_id'] ?? 1);
$isAdmin = ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' || strtolower($_SESSION['status'] ?? '') === 'admin' || ($_SESSION['user_id'] ?? 0) == 1;
$isMain = isMainBranch($pdo, $userStoreId);
$canAllBranches = canViewAllBranches($pdo);

$message = '';
$message_type = '';

// Handle POST Transfer Execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'execute_transfer') {
    $from_store_id = intval($_POST['from_store_id'] ?? 1);
    if (!$canAllBranches) {
        $from_store_id = $userStoreId;
    }
    $to_store_id   = intval($_POST['to_store_id'] ?? 0);
    $notes         = trim($_POST['notes'] ?? '');
    $items_json    = $_POST['items_json'] ?? '[]';
    $items         = json_decode($items_json, true);
    
    $transfer_date = trim($_POST['transfer_date'] ?? date('Y-m-d'));
    if (empty($transfer_date)) {
        $transfer_date = date('Y-m-d');
    }
    $full_transfer_date = $transfer_date . ' ' . date('H:i:s');

    if ($from_store_id <= 0 || $to_store_id <= 0) {
        $message = t('stock_transfer.err_select_stores', 'ກະລຸນາເລືອກສາຂາຕົ້ນທາງ ແລະ ສາຂາປາຍທາງ!');
        $message_type = 'danger';
    } elseif ($from_store_id === $to_store_id) {
        $message = t('stock_transfer.err_same_store', 'ສາຂາຕົ້ນທາງ ແລະ ປາຍທາງ ຕ້ອງບໍ່ແມ່ນສາຂາດຽວກັນ!');
        $message_type = 'danger';
    } elseif (empty($items) || !is_array($items)) {
        $message = t('stock_transfer.err_no_items', 'ກະລຸນາເພີ່ມສິນຄ້າໃສ່ລາຍການໂອນຢ່າງນ້ອຍ 1 ລາຍການ!');
        $message_type = 'danger';
    } else {
        try {
            $pdo->beginTransaction();

            // Auto-generate transfer code (TRF-YYYYMMDD-001)
            $todayStr = date('Ymd');
            $countToday = (int)$pdo->query("SELECT COUNT(*) FROM st_stock_transfers WHERE DATE(transfer_date) = CURDATE()")->fetchColumn();
            $transfer_code = 'TRF-' . $todayStr . '-' . str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

            // Insert into stock_transfers
            $insTrf = $pdo->prepare("INSERT INTO st_stock_transfers (transfer_code, from_store_id, to_store_id, transfer_date, status, notes, created_by) VALUES (?, ?, ?, ?, 'completed', ?, ?)");
            $insTrf->execute([$transfer_code, $from_store_id, $to_store_id, $full_transfer_date, $notes, $_SESSION['user_id']]);
            $transfer_id = $pdo->lastInsertId();

            // Consolidate duplicate products into a single entry with combined quantity
            $consolidatedItems = [];
            foreach ($items as $itm) {
                $pId = intval($itm['product_id'] ?? 0);
                $q   = intval($itm['quantity'] ?? $itm['qty'] ?? 0);
                if ($pId <= 0 || $q <= 0) continue;
                if (!isset($consolidatedItems[$pId])) {
                    $consolidatedItems[$pId] = $itm;
                    $consolidatedItems[$pId]['qty'] = $q;
                    $consolidatedItems[$pId]['quantity'] = $q;
                } else {
                    $consolidatedItems[$pId]['qty'] += $q;
                    $consolidatedItems[$pId]['quantity'] += $q;
                }
            }
            $items = array_values($consolidatedItems);

            $transferredCount = 0;
            $importLines = [];
            foreach ($items as $item) {
                $product_id = intval($item['product_id'] ?? 0);
                $qty        = intval($item['quantity'] ?? $item['qty'] ?? 0);

                if ($product_id <= 0 || $qty <= 0) continue;

                // Fetch source product in $from_store_id
                $stmtSrc = $pdo->prepare("SELECT * FROM st_products WHERE product_id = ? AND store_id = ? FOR UPDATE");
                $stmtSrc->execute([$product_id, $from_store_id]);
                $srcProd = $stmtSrc->fetch();

                if (!$srcProd) {
                    throw new Exception(sprintf(t('stock_transfer.err_product_not_found', 'ບໍ່ພົບສິນຄ້າ ID %s ໃນສາຂາຕົ້ນທາງ!'), $product_id));
                }

                if ($srcProd['qty'] < $qty) {
                    throw new Exception(sprintf(t('stock_transfer.err_insufficient_stock', 'ສິນຄ້າ "%s" ໃນສາຂາຕົ້ນທາງ ມີພຽງ %s (ບໍ່ພໍໂອນ %s)!'), $srcProd['product_name'], $srcProd['qty'], $qty));
                }

                // Deduct from source branch
                $stmtDeduct = $pdo->prepare("UPDATE st_products SET qty = qty - ? WHERE product_id = ? AND store_id = ?");
                $stmtDeduct->execute([$qty, $product_id, $from_store_id]);

                // Add / Update target product in $to_store_id
                $stmtDst = $pdo->prepare("SELECT * FROM st_products WHERE product_id = ? AND store_id = ? FOR UPDATE");
                $stmtDst->execute([$product_id, $to_store_id]);
                $dstProd = $stmtDst->fetch();

                if ($dstProd) {
                    $stmtAddDst = $pdo->prepare("UPDATE st_products SET qty = qty + ? WHERE product_id = ? AND store_id = ?");
                    $stmtAddDst->execute([$qty, $product_id, $to_store_id]);
                } else {
                    $stmtInsDst = $pdo->prepare("INSERT INTO st_products (product_id, barcode, product_name, category_id, bprice, price, qty, unit, img_url, cook, cut_qty, min_qty, store_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmtInsDst->execute([
                        $srcProd['product_id'],
                        $srcProd['barcode'],
                        $srcProd['product_name'],
                        $srcProd['category_id'],
                        $srcProd['bprice'],
                        $srcProd['price'],
                        $qty,
                        $srcProd['unit'],
                        $srcProd['img_url'],
                        $srcProd['cook'] ?? 0,
                        $srcProd['cut_qty'] ?? 1,
                        $srcProd['min_qty'] ?? 5,
                        $to_store_id
                    ]);
                }

                // Save detail line
                $stmtDet = $pdo->prepare("INSERT INTO st_stock_transfer_details (transfer_id, product_id, product_name, barcode, qty, unit, bprice, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmtDet->execute([
                    $transfer_id,
                    $product_id,
                    $srcProd['product_name'],
                    $srcProd['barcode'],
                    $qty,
                    $srcProd['unit'],
                    $srcProd['bprice'],
                    $srcProd['price']
                ]);

                $importLines[] = [
                    'product_id' => $product_id,
                    'unit_name'  => $srcProd['unit'],
                    'quantity'   => $qty,
                    'cost_price' => floatval($srcProd['bprice']),
                ];

                $transferredCount++;
            }

            // Register the transfer as an "import" for the destination branch too, so its
            // ຕົ້ນທຶນ (cost brought into the branch) reports correctly include stock that
            // arrived via inter-branch transfer, not only stock bought directly from a supplier.
            if (!empty($importLines)) {
                $fromStoreName = $pdo->prepare("SELECT store_name FROM st_tbstore WHERE store_id = ?");
                $fromStoreName->execute([$from_store_id]);
                $fromStoreNameVal = $fromStoreName->fetchColumn() ?: "ສາຂາ #{$from_store_id}";

                $transferTotalCost = 0;
                foreach ($importLines as $line) {
                    $transferTotalCost += $line['quantity'] * $line['cost_price'];
                }

                $insImp = $pdo->prepare("INSERT INTO st_imports (invoice_number, supplier_name, import_date, total_cost, created_by, notes, store_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insImp->execute([
                    $transfer_code,
                    'ໂອນຈາກສາຂາ ' . $fromStoreNameVal,
                    $full_transfer_date,
                    $transferTotalCost,
                    $_SESSION['user_id'],
                    $notes,
                    $to_store_id
                ]);
                $transferImportId = $pdo->lastInsertId();

                $insDet = $pdo->prepare("INSERT INTO st_import_details (import_id, product_id, unit_name, multiplier, quantity, total_base_qty, cost_price, total_cost) VALUES (?, ?, ?, 1, ?, ?, ?, ?)");
                foreach ($importLines as $line) {
                    $insDet->execute([
                        $transferImportId,
                        $line['product_id'],
                        $line['unit_name'],
                        $line['quantity'],
                        $line['quantity'],
                        $line['cost_price'],
                        $line['quantity'] * $line['cost_price']
                    ]);
                }

                // Deduct the same value back out of the SOURCE branch's cost, so the cost of the
                // transferred stock moves WITH the goods instead of counting at both branches at once.
                // (No matching import_details row on purpose — this is a ledger adjustment, not a
                // purchase invoice line, so it stays out of the "ນຳເຂົ້າສິນຄ້າ" history list; the
                // actual movement is already visible there in "ປະຫວັດການໂອນສິນຄ້າ".)
                $toStoreName = $pdo->prepare("SELECT store_name FROM st_tbstore WHERE store_id = ?");
                $toStoreName->execute([$to_store_id]);
                $toStoreNameVal = $toStoreName->fetchColumn() ?: "ສາຂາ #{$to_store_id}";

                $insImpOut = $pdo->prepare("INSERT INTO st_imports (invoice_number, supplier_name, import_date, total_cost, created_by, notes, store_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insImpOut->execute([
                    $transfer_code,
                    'ໂອນອອກໄປສາຂາ ' . $toStoreNameVal,
                    $full_transfer_date,
                    -$transferTotalCost,
                    $_SESSION['user_id'],
                    $notes,
                    $from_store_id
                ]);
            }

            $pdo->commit();
            $message = sprintf(t('stock_transfer.success_message', 'ບັນທຶກການໂອນສິນຄ້າເລກທີ %s ສຳເລັດ (ລວມ %s ລາຍການ)!'), $transfer_code, $transferredCount);
            $message_type = 'success';
            logActivity($pdo, "ໂອນສິນຄ້າລະຫວ່າງສາຂາ", "Code: {$transfer_code}, From: Store #{$from_store_id} -> To: Store #{$to_store_id}");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = t('stock_transfer.error_prefix', 'ຜິດພາດ: ') . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Fetch all stores
$stores = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Target store pre-selected from GET
$preSelectedTarget = intval($_GET['target_store'] ?? 0);
$preSearch = trim($_GET['search'] ?? '');
$preProductId = intval($_GET['product_id'] ?? 0);
$autoAdd = intval($_GET['auto_add'] ?? 0);

$sourceStoreId = getActiveStoreId($pdo);
if (!$canAllBranches) {
    $sourceStoreId = $userStoreId;
}

// Fetch categories for modal filter
$catStmt = $pdo->query("SELECT category_id, category_name FROM st_categories ORDER BY category_id ASC");
$categories = $catStmt->fetchAll();

// Fetch products of source store with categories & units
$sourceProductsStmt = $pdo->prepare("
    SELECT p.product_id, p.product_name, p.barcode, p.unit, p.bprice, p.price, p.qty, p.category_id, c.category_name 
    FROM st_products p 
    LEFT JOIN st_categories c ON p.category_id = c.category_id 
    WHERE p.store_id = ?
    ORDER BY p.code1 DESC, p.product_name ASC
");
$sourceProductsStmt->execute([$sourceStoreId]);
$products = $sourceProductsStmt->fetchAll(PDO::FETCH_ASSOC);

$product_units_map = [];
$uRows = $pdo->query("SELECT * FROM st_product_units ORDER BY multiplier ASC")->fetchAll();
foreach ($uRows as $u) {
    $product_units_map[$u['product_id']][] = $u;
}

// Auto-generate transfer invoice code
$todayStr = date('Ymd');
$countToday = (int)$pdo->query("SELECT COUNT(*) FROM st_stock_transfers WHERE DATE(transfer_date) = CURDATE()")->fetchColumn();
$next_transfer_code = 'TRF-' . $todayStr . '-' . str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

// Receiver / Transferrer Name
$receiverName = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? ''));
if (empty($receiverName)) $receiverName = $_SESSION['username'] ?? 'Admin';

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/import_stock.css?v=<?php echo filemtime(__DIR__ . '/../../themes/import_stock.css'); ?>">
<link rel="stylesheet" href="../../themes/stock_transfer.css?v=<?php echo filemtime(__DIR__ . '/../../themes/stock_transfer.css'); ?>">

<div class="content-wrapper bg-light" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
  
  <!-- Page Header -->
  <section class="content-header py-3">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h4 class="m-0 font-weight-bold text-dark">
            <i class="fas fa-exchange-alt text-primary mr-2"></i> <?php echo htmlspecialchars(t('stock_transfer.page_title', 'ໂອນສິນຄ້າລະຫວ່າງສາຂາ')); ?>
          </h4>
        </div>
      </div>
    </div>
  </section>

  <!-- Main Content -->
  <section class="content pb-5">
    <div class="container-fluid">

      <!-- Alert Notification -->
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
              try {
                if (window.parent && typeof window.parent.pollLiveNotifications === 'function') {
                  window.parent.pollLiveNotifications();
                }
              } catch(e) {}
              Swal.fire({
                title: '<?php echo addslashes(t('stock_transfer.swal_saved_title', 'ບັນທຶກສຳເລັດ!')); ?>',
                text: '<?php echo addslashes($message); ?>',
                icon: 'success',
                confirmButtonColor: '#10b981',
                confirmButtonText: '<?php echo addslashes(t('stock_transfer.ok_button', 'ຕົກລົງ')); ?>',
                timer: 3500,
                timerProgressBar: true
              });
            });
          </script>
        <?php endif; ?>
      <?php endif; ?>

      <!-- 1. DIRECT ENTRY FORM CARD — a transfer needs a source AND a destination branch,
           so there's nothing meaningful to show with fewer than 2 active branches -->
      <?php if (count($stores) > 1): ?>
      <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px;">
        <div class="card-body p-4">
          <?php require_once __DIR__ . '/components/transfer_form_component.php'; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="card shadow-sm border-0 mb-4" style="border-radius: 16px;">
        <div class="card-body p-5 text-center text-muted">
          <i class="fas fa-code-branch fa-3x d-block mb-3" style="opacity: 0.35;"></i>
          <div class="font-weight-bold" style="font-size: 1rem;">
            <?php echo htmlspecialchars(t('stock_transfer.needs_two_branches', 'ຕ້ອງມີຢ່າງໜ້ອຍ 2 ສາຂາຈຶ່ງຈະໂອນສິນຄ້າລະຫວ່າງສາຂາໄດ້')); ?>
          </div>
          <small><?php echo htmlspecialchars(t('stock_transfer.needs_two_branches_hint', 'ກະລຸນາເພີ່ມສາຂາໃໝ່ໃນໜ້າຈັດການສາຂາກ່ອນ')); ?></small>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </section>
</div>

<!-- Modal: Select Product Popup -->
<?php require_once __DIR__ . '/components/modal_select_product.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<!-- JavaScript Logic Component -->
<?php require_once __DIR__ . '/components/transfer_js_component.php'; ?>
