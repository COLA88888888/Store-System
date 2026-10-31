<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ໜ້າບັນທຶກຮັບສິນຄ້າເຂົ້າສາງ: ບັນທຶກບິນນຳເຂົ້າ (POST), ດຶງສິນຄ້າ/ຜູ້ສະໜອງ/ປະຫວັດ ແລ້ວສະແດງຟອມປ້ອນ.
 *   - partials/import_stock/actions.php : ການກະທຳ POST: ບັນທຶກບິນນຳເຂົ້າ
 */
session_start();
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once __DIR__ . '/../../config/db.php';

// Check authorization
if (empty($_SESSION['user_id']) || (!hasPermission('import_stock') && intval($_SESSION['user_id'] ?? 0) !== 1)) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$message = '';
$message_type = '';

// Let a receipt line record the sell price applied at receipt time (nullable, additive migration)
try { $pdo->exec("ALTER TABLE st_import_details ADD COLUMN sell_price DECIMAL(15,2) NULL AFTER cost_price"); } catch (Throwable $e) {}

// ການກະທຳ POST: ບັນທຶກບິນນຳເຂົ້າ
require __DIR__ . '/partials/import_stock/actions.php';
// Fetch categories for modal filter
$activeStoreId = getActiveStoreId($pdo);
$catStmt = $pdo->query("SELECT category_id, category_name FROM st_categories ORDER BY category_id ASC");
$categories = $catStmt->fetchAll();

// Fetch active suppliers so the receiving form is a dropdown, not free text
$suppliers = [];
try {
    $supStmt = $pdo->prepare("SELECT supplier_id, supplier_name FROM st_suppliers WHERE store_id = ? AND status = 'active' ORDER BY supplier_name ASC");
    $supStmt->execute([$activeStoreId]);
    $suppliers = $supStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

// Fetch products for current active store with categories & multi-units
$prodStmt = $pdo->prepare("
    SELECT p.product_id, p.product_name, p.barcode, p.unit, p.bprice, p.price, p.qty, p.category_id, c.category_name 
    FROM st_products p 
    LEFT JOIN st_categories c ON p.category_id = c.category_id 
    WHERE p.store_id = ?
    ORDER BY p.product_name ASC
");
$prodStmt->execute([$activeStoreId]);
$products = $prodStmt->fetchAll();

$product_units_map = [];
$uRows = $pdo->query("SELECT * FROM st_product_units ORDER BY multiplier ASC")->fetchAll();
foreach ($uRows as $u) {
    $product_units_map[$u['product_id']][] = $u;
}

// Fetch Import History
$userStoreId = intval($_SESSION['store_id'] ?? 1);
$isAdmin = ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' || strtolower($_SESSION['status'] ?? '') === 'admin' || ($_SESSION['user_id'] ?? 0) == 1;
$isMain = isMainBranch($pdo, $userStoreId);
$canAllBranches = canViewAllBranches($pdo);

$historyWhere = (!$canAllBranches) ? " WHERE p.store_id = :store_id " : "";
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
    FROM st_import_details id
    JOIN st_imports i ON id.import_id = i.import_id
    LEFT JOIN st_products p ON (id.product_id = p.product_id AND (p.store_id = i.store_id OR i.store_id <= 1))
    LEFT JOIN st_tbuser u ON i.created_by = u.Id
    LEFT JOIN st_product_batches pb ON id.import_detail_id = pb.import_detail_id
    {$historyWhere}
    GROUP BY id.import_detail_id, p.product_name, p.barcode, p.qty, p.unit, u.username, u.fname, u.lname, pb.initial_qty, pb.quantity
    ORDER BY id.import_detail_id DESC
";
$histStmt = $pdo->prepare($historyQuery);
if (!$canAllBranches) {
    $histStmt->execute([':store_id' => $activeStoreId]);
} else {
    $histStmt->execute();
}
$importHistory = $histStmt->fetchAll();
$total_imports = count($importHistory);

// ເລກບິນຖັດໄປຂອງສາຂານີ້ (IMP-10001, IMP-10002… — importInvoiceNumber() ໃນ partials/import_stock/actions.php)
$next_invoice = importInvoiceNumber($pdo, $activeStoreId);

// Current User Receiver Name
$receiverName = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? ''));
if (empty($receiverName)) $receiverName = $_SESSION['username'] ?? 'Admin';

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/import_stock.css?v=<?php echo filemtime(__DIR__ . '/../../themes/import_stock.css'); ?>">

<div class="content-wrapper bg-light mp-compact">
  
  <!-- Page Header -->
  <section class="content-header py-3">
    <div class="container-fluid">
      <div class="row align-items-center">
        <div class="col-sm-6">
          <h4 class="m-0 font-weight-bold text-dark" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
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
      <div class="card sys-card shadow-sm border-0 mb-4" style="border-radius: 16px;">

        <div class="card-body p-4">
          <form id="directImportForm" action="" method="POST">
            <input type="hidden" name="action" value="import_stock_bill">
            <input type="hidden" id="cart_json_input" name="cart_json" value="[]">

            <!-- Top Header Details Row: Invoice No, Date, Receiver, Supplier -->
            <div class="row bg-light p-3 rounded mb-3 border" style="border-color: #cbd5e1 !important;">
              <!-- 1. Auto Invoice Number (ໂທລະສັບ: ຢູ່ແຖວດຽວກັບ ຜູ້ຮັບເຂົ້າ) -->
              <div class="col-6 col-md-3 mb-2 mb-md-0 order-1">
                <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
                  <?php echo htmlspecialchars(t('import_stock.invoice_no_label', 'ເລກທີໃບບິນຮັບເຂົ້າ')); ?> <small class="text-muted"></small>
                </label>
                <input type="text" name="invoice_number" id="invoice_number_input" class="form-control font-weight-bold bg-white text-primary border-primary" value="<?php echo $next_invoice; ?>" readonly style="height: 42px; border-radius: 8px; cursor: not-allowed; font-size: 1.05rem;">
              </div>

              <!-- 2. Import Date (Editable Date Picker - No Time) -->
              <div class="col-12 col-md-3 mb-2 mb-md-0 order-3 order-md-2">
                <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
                  <?php echo htmlspecialchars(t('import_stock.import_date_label', 'ວັນທີຮັບເຂົ້າ')); ?> <span class="text-danger">*</span>
                </label>
                <input type="date" name="import_date" id="import_date_input" class="form-control font-weight-bold" value="<?php echo date('Y-m-d'); ?>" required style="height: 42px; border-radius: 8px;">
              </div>

              <!-- 3. Receiver Name (ໂທລະສັບ: ຖັດຈາກ ເລກທີໃບບິນ) -->
              <div class="col-6 col-md-3 mb-2 mb-md-0 order-2 order-md-3">
                <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
                  <?php echo htmlspecialchars(t('import_stock.receiver_label', 'ຜູ້ບັນທຶກ / ຜູ້ຮັບເຂົ້າ')); ?>
                </label>
                <input type="text" class="form-control bg-white font-weight-bold" value="<?php echo htmlspecialchars($receiverName); ?>" readonly style="height: 42px; border-radius: 8px;">
              </div>

              <!-- 4. Supplier Name -->
              <div class="col-12 col-md-3 order-4">
                <label class="font-weight-bold text-dark mb-1" style="font-size: 0.88rem;">
                  <?php echo htmlspecialchars(t('import_stock.supplier_label', 'ຊື່ຜູ້ສະໜອງ / ຮ້ານສົ່ງ')); ?>
                </label>
                <select name="supplier_name" id="supplier_name_input" class="form-control" style="height: 42px; border-radius: 8px;">
                  <option value=""><?php echo htmlspecialchars(t('import_stock.supplier_placeholder', '-- ບໍ່ລະບຸ / ເລືອກຜູ້ສະໜອງ --')); ?></option>
                  <?php foreach ($suppliers as $sp): ?>
                    <option value="<?php echo htmlspecialchars($sp['supplier_name']); ?>"><?php echo htmlspecialchars($sp['supplier_name']); ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if (empty($suppliers)): ?>
                  <small class="text-muted"><?php echo htmlspecialchars(t('import_stock.supplier_empty_hint', 'ຍັງບໍ່ມີຜູ້ສະໜອງ — ເພີ່ມໄດ້ທີ່ເມນູ "ຈັດການຜູ້ສະໜອງ"')); ?></small>
                <?php endif; ?>
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
              <div class="col-6 col-md-2 mb-3 mb-md-0">
                <label class="font-weight-bold text-dark mb-1">
                  <?php echo htmlspecialchars(t('import_stock.qty_label', 'ຈຳນວນ')); ?> <span class="text-danger">*</span>
                </label>
                <input type="number" id="direct_qty" class="form-control font-weight-bold border-primary text-center" min="1" value="1" style="height: 42px; border-radius: 8px; font-size: 1.1rem;" onkeydown="onQtyKeyDown(event)">
              </div>

              <!-- Unit Select -->
              <div class="col-6 col-md-2 mb-3 mb-md-0">
                <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('import_stock.unit_label', 'ຫົວໜ່ວຍ')); ?></label>
                <select id="direct_unit_key" class="form-control" style="height: 42px; border-radius: 8px;">
                  <option value="base"><?php echo htmlspecialchars(t('import_stock.unit_label', 'ຫົວໜ່ວຍ')); ?></option>
                </select>
              </div>

              <!-- Expiry Date -->
              <div class="col-md-2 mb-3 mb-md-0">
                <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('import_stock.expiry_date_label', 'ວັນໝົດອາຍຸ')); ?></label>
                <input type="date" id="direct_expiry_date" class="form-control font-weight-bold" style="height: 42px; border-radius: 8px; cursor: pointer;">
              </div>

              <!-- Add Item Button (buy/sell price are edited inline in the items table below) -->
              <div class="col-md-2 mt-2 mt-md-0">
                <?php /* ປ້າຍວ່າງ: ໃຫ້ປຸ່ມຢູ່ລະດັບດຽວກັບຊ່ອງປ້ອນຂ້າງໆ (ທີ່ມີປ້າຍຢູ່ເທິງ) ໃນຈໍໃຫຍ່ */ ?>
                <label class="font-weight-bold mb-1 d-none d-md-block" style="visibility: hidden;">&nbsp;</label>
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
                   <?php echo htmlspecialchars(t('import_stock.bprice_label', 'ລາຄາຊື້:')); ?> <span id="direct_matched_bprice" class="font-weight-bold text-danger mr-2">0 <?php echo kipSymbol(); ?></span>
                   <?php echo htmlspecialchars(t('import_stock.current_stock_label', 'ສະຕັອກປັດຈຸບັນ:')); ?> <span id="direct_matched_stock" class="font-weight-bold text-success">0</span>
                </small>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetDirectSelection()"><i class="fas fa-times"></i></button>
            </div>

            <!-- LIVE IMPORT ITEMS TABLE (ຕາຕະລາງລາຍການຮັບເຂົ້າ ໃນໜ້າຈໍ) — single-row rows, scrolls sideways on mobile -->
            <div class="border rounded mb-3 shadow-sm" style="border-radius: 12px; overflow: hidden; background-color: #ffffff;">
              <div class="table-responsive" style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
              <table class="table table-hover mb-0 align-middle text-nowrap" style="min-width: 900px;">
                <thead class="font-weight-bold">
                  <tr>
                    <th class="text-center" style="width: 50px;"><?php echo htmlspecialchars(t('import_stock.col_no', 'ລຳດັບ')); ?></th>
                    <th><?php echo htmlspecialchars(t('import_stock.col_product_name', 'ຊື່ສິນຄ້າ')); ?></th>
                    <th class="text-center" style="width: 200px;"><?php echo htmlspecialchars(t('import_stock.col_import_qty', 'ຈຳນວນຮັບເຂົ້າ')); ?></th>
                    <th class="text-center" style="width: 150px;"><?php echo htmlspecialchars(t('import_stock.expiry_date_label', 'ວັນໝົດອາຍຸ')); ?></th>
                    <th class="text-right" style="width: 150px;"><?php echo htmlspecialchars(t('import_stock.col_bprice', 'ລາຄາຊື້')); ?></th>
                    <th class="text-right" style="width: 150px;"><?php echo htmlspecialchars(t('import_stock.col_sell_price', 'ລາຄາຂາຍ')); ?></th>
                    <th class="text-right" style="width: 160px;"><?php echo htmlspecialchars(t('import_stock.col_total', 'ລວມ')); ?></th>
                    <th class="text-center" style="width: 80px;"><?php echo htmlspecialchars(t('import_stock.col_actions', 'ຈັດການ')); ?></th>
                  </tr>
                </thead>
                <tbody id="cart_table_body">
                  <tr id="empty_cart_row">
                    <td colspan="8" class="text-center text-muted py-5">
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
                  <button type="button" class="btn btn-primary font-weight-bold px-4 shadow-sm btn-mobile-block" style="height: 38px; border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;" onclick="submitDirectImportBill()">
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
