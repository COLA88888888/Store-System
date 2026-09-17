<?php
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../../config/db.php';

// Authorization check
if (empty($_SESSION['user_id']) || (!hasPermission('transfer_history') && !hasPermission('stock_transfer') && intval($_SESSION['user_id'] ?? 0) !== 1)) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$userStoreId = intval($_SESSION['store_id'] ?? 1);
$isAdmin = ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' || strtolower($_SESSION['status'] ?? '') === 'admin' || ($_SESSION['user_id'] ?? 0) == 1;
$isMain = isMainBranch($pdo, $userStoreId);
$canAllBranches = canViewAllBranches($pdo);

$message = '';
$message_type = '';

// Handle POST cancel transfer action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_transfer') {
    $transfer_id = intval($_POST['transfer_id'] ?? 0);
    if ($transfer_id > 0) {
        try {
            $pdo->beginTransaction();

            // Fetch transfer
            $stmtTrf = $pdo->prepare("SELECT * FROM stock_transfers WHERE transfer_id = ? FOR UPDATE");
            $stmtTrf->execute([$transfer_id]);
            $transfer = $stmtTrf->fetch();

            if (!$transfer) {
                throw new Exception(t('transfer_history.err_not_found', 'ບໍ່ພົບຂໍ້ມູນໃບໂອນນີ້ໃນລະບົບ!'));
            }

            if ($transfer['status'] === 'cancelled') {
                throw new Exception(t('transfer_history.err_already_cancelled', 'ໃບໂອນນີ້ຖືກຍົກເລີກໄປແລ້ວ!'));
            }

            // Fetch details
            $stmtDet = $pdo->prepare("SELECT * FROM stock_transfer_details WHERE transfer_id = ?");
            $stmtDet->execute([$transfer_id]);
            $details = $stmtDet->fetchAll();

            $from_store_id = $transfer['from_store_id'];
            $to_store_id   = $transfer['to_store_id'];

            // Validate if target store has enough stock to return
            foreach ($details as $item) {
                $product_id = $item['product_id'];
                $qty        = $item['qty'];

                // Check stock in target store
                $stmtCheckTarget = $pdo->prepare("SELECT qty, product_name FROM products WHERE product_id = ? AND store_id = ? FOR UPDATE");
                $stmtCheckTarget->execute([$product_id, $to_store_id]);
                $targetProd = $stmtCheckTarget->fetch();

                if (!$targetProd || $targetProd['qty'] < $qty) {
                    $prodName = $targetProd ? $targetProd['product_name'] : $item['product_name'];
                    $curQty = $targetProd ? $targetProd['qty'] : 0;
                    throw new Exception(sprintf(t('transfer_history.err_stock_used', 'ບໍ່ສາມາດຍົກເລີກໃບໂອນໄດ້ ເນື່ອງຈາກສິນຄ້າ "%s" ໃນສາຂາປາຍທາງຖືກໃຊ້ ຫຼື ຂາຍໄປແລ້ວ (ສະຕັອກປັດຈຸບັນມີ: %s, ຕ້ອງການຫັກຄືນ: %s)!'), $prodName, $curQty, $qty));
                }
            }

            // Revert stocks
            $totalCost = 0;
            foreach ($details as $item) {
                $product_id = $item['product_id'];
                $qty        = $item['qty'];

                // Deduct from target store
                $stmtDeductTarget = $pdo->prepare("UPDATE products SET qty = qty - ? WHERE product_id = ? AND store_id = ?");
                $stmtDeductTarget->execute([$qty, $product_id, $to_store_id]);

                // Return to source store
                $stmtReturnSrc = $pdo->prepare("UPDATE products SET qty = qty + ? WHERE product_id = ? AND store_id = ?");
                $stmtReturnSrc->execute([$qty, $product_id, $from_store_id]);

                $totalCost += $qty * (float)$item['bprice'];
            }

            // The original transfer recorded ຕົ້ນທຶນ (cost) moving with the goods: a positive
            // "imports" ledger row at the destination branch and a matching negative row at the
            // source branch. Cancelling must reverse both, or the cost stays booked at the
            // destination (and missing at the source) even after the stock itself moved back.
            if ($totalCost > 0) {
                $fromStoreNameStmt = $pdo->prepare("SELECT store_name FROM tbstore WHERE store_id = ?");
                $fromStoreNameStmt->execute([$from_store_id]);
                $fromStoreNameVal = $fromStoreNameStmt->fetchColumn() ?: "ສາຂາ #{$from_store_id}";

                $toStoreNameStmt = $pdo->prepare("SELECT store_name FROM tbstore WHERE store_id = ?");
                $toStoreNameStmt->execute([$to_store_id]);
                $toStoreNameVal = $toStoreNameStmt->fetchColumn() ?: "ສາຂາ #{$to_store_id}";

                $insImpRevert = $pdo->prepare("INSERT INTO imports (invoice_number, supplier_name, import_date, total_cost, created_by, notes, store_id) VALUES (?, ?, NOW(), ?, ?, ?, ?)");

                // Reverse the destination branch's cost-in
                $insImpRevert->execute([
                    $transfer['transfer_code'],
                    'ຍົກເລີກໂອນຈາກສາຂາ ' . $fromStoreNameVal,
                    -$totalCost,
                    $_SESSION['user_id'],
                    'ຍົກເລີກໃບໂອນເລກທີ ' . $transfer['transfer_code'],
                    $to_store_id
                ]);

                // Restore the source branch's cost that was deducted out
                $insImpRevert->execute([
                    $transfer['transfer_code'],
                    'ຍົກເລີກໂອນອອກໄປສາຂາ ' . $toStoreNameVal,
                    $totalCost,
                    $_SESSION['user_id'],
                    'ຍົກເລີກໃບໂອນເລກທີ ' . $transfer['transfer_code'],
                    $from_store_id
                ]);
            }

            // Update status
            $stmtUpdateStatus = $pdo->prepare("UPDATE stock_transfers SET status = 'cancelled' WHERE transfer_id = ?");
            $stmtUpdateStatus->execute([$transfer_id]);

            $pdo->commit();
            $message = sprintf(t('transfer_history.msg_cancel_success', 'ຍົກເລີກໃບໂອນເລກທີ %s ແລະ ຄືນສະຕັອກເຂົ້າຕົ້ນທາງສຳເລັດ!'), $transfer['transfer_code']);
            $message_type = 'success';
            logActivity($pdo, "ຍົກເລີກໃບໂອນສິນຄ້າ", "Code: {$transfer['transfer_code']}");
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = t('transfer_history.err_prefix', 'ຜິດພາດ:') . ' ' . $e->getMessage();
            $message_type = 'danger';
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

// Fetch all branches for filtering
$stores = $pdo->query("SELECT * FROM tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Filters — default date range: 1st of the current month → today
$from_date  = trim($_GET['from_date'] ?? date('Y-m-01'));
$to_date    = trim($_GET['to_date'] ?? date('Y-m-d'));
$filter_from_store = isset($_GET['from_store']) && $_GET['from_store'] !== '' ? intval($_GET['from_store']) : 0;
$filter_to_store   = isset($_GET['to_store']) && $_GET['to_store'] !== '' ? intval($_GET['to_store']) : 0;
$search     = trim($_GET['search'] ?? '');

$where = ["1=1"];
$params = [];

// Lock sub-branch users to view only their related transfers
if (!$canAllBranches) {
    $where[] = "(t.from_store_id = :user_store_id OR t.to_store_id = :user_store_id)";
    $params[':user_store_id'] = $userStoreId;
}

if (!empty($from_date)) {
    $where[] = "DATE(t.transfer_date) >= :from_date";
    $params[':from_date'] = $from_date;
}
if (!empty($to_date)) {
    $where[] = "DATE(t.transfer_date) <= :to_date";
    $params[':to_date'] = $to_date;
}
if ($filter_from_store > 0) {
    $where[] = "t.from_store_id = :filter_from_store";
    $params[':filter_from_store'] = $filter_from_store;
}
if ($filter_to_store > 0) {
    $where[] = "t.to_store_id = :filter_to_store";
    $params[':filter_to_store'] = $filter_to_store;
}
if (!empty($search)) {
    $where[] = "(t.transfer_code LIKE :search OR t.notes LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$whereClause = implode(" AND ", $where);

// Fetch Inter-branch transfers with filters
$sqlTransfers = "
    SELECT t.*, f.store_name as from_store_name, to_s.store_name as to_store_name, u.username as creator_name,
           (SELECT COUNT(*) FROM stock_transfer_details d WHERE d.transfer_id = t.transfer_id) as total_items,
           (SELECT SUM(qty) FROM stock_transfer_details d WHERE d.transfer_id = t.transfer_id) as total_qty,
           (SELECT GROUP_CONCAT(CONCAT(product_name, ' x', qty, ' ', unit) SEPARATOR ', ') FROM stock_transfer_details d WHERE d.transfer_id = t.transfer_id) as product_list
    FROM stock_transfers t
    LEFT JOIN tbstore f ON t.from_store_id = f.store_id
    LEFT JOIN tbstore to_s ON t.to_store_id = to_s.store_id
    LEFT JOIN tbuser u ON t.created_by = u.Id
    WHERE {$whereClause}
    ORDER BY t.transfer_id DESC
";
$stmtTrf = $pdo->prepare($sqlTransfers);
$stmtTrf->execute($params);
$transfers = $stmtTrf->fetchAll(PDO::FETCH_ASSOC);

if (!empty($_GET['fetch_table'])) {
    require_once __DIR__ . '/components/history_table_component.php';
    exit();
}

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">
<link rel="stylesheet" href="../../themes/transfer_history.css?v=<?php echo filemtime(__DIR__ . '/../../themes/transfer_history.css'); ?>">

<div class="container-fluid p-3 p-md-4" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
  
  <!-- Page Header -->
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="gap: 10px;">
    <div>
      <h4 class="font-weight-bold text-dark m-0">
        <i class="fas fa-history text-info mr-2"></i> <?php echo htmlspecialchars(t('transfer_history.page_title', 'ປະຫວັດການໂອນສິນຄ້າ')); ?>
      </h4>
      <span class="text-muted" style="font-size: 0.85rem;"><?php echo htmlspecialchars(t('transfer_history.page_subtitle', 'ຕິດຕາມ ແລະ ກວດສອບລາຍການໂອນສິນຄ້າລະຫວ່າງສາຂາ')); ?></span>
    </div>
    <div>
      <a href="stock_transfer.php" class="btn btn-primary btn-sm font-weight-bold px-3 py-2" style="border-radius: 8px; background: linear-gradient(135deg, #2c5aa0, #244886);">
        <i class="fas fa-exchange-alt mr-1"></i> <?php echo htmlspecialchars(t('transfer_history.new_transfer_btn', 'ໂອນສິນຄ້າໃໝ່')); ?>
      </a>
    </div>
  </div>

  <!-- SweetAlert Notification -->
  <?php if ($message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          icon: '<?php echo $message_type === "success" ? "success" : "error"; ?>',
          title: '<?php echo $message_type === "success" ? htmlspecialchars(t('transfer_history.msg_success_title', 'ສຳເລັດ'), ENT_QUOTES) : htmlspecialchars(t('transfer_history.msg_error_title', 'ແຈ້ງເຕືອນ'), ENT_QUOTES); ?>',
          text: '<?php echo addslashes($message); ?>',
          confirmButtonColor: '#2563eb'
        });
      });
    </script>
  <?php endif; ?>

  <!-- Filters Card Component -->
  <?php require_once __DIR__ . '/components/history_filter_component.php'; ?>

  <!-- Results Table Component -->
  <?php require_once __DIR__ . '/components/history_table_component.php'; ?>

</div>

<!-- Modal & Cancellation Form Component -->
<?php require_once __DIR__ . '/components/history_modal_component.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>

<!-- Javascript Logic Component -->
<?php require_once __DIR__ . '/components/history_js_component.php'; ?>
