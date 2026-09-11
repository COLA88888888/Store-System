<?php
// POS Master View Page - Modular & Clean Architecture
require_once __DIR__ . '/../../api/pos_backend.php';

$pos_page = true;
require_once __DIR__ . '/../../layouts/header.php';
?>

<?php
$todayDate = date('Y-m-d');
$stmtSeq = $pdo->prepare("SELECT COUNT(*) FROM tbsale_save WHERE sale_date = ?");
$stmtSeq->execute([$todayDate]);
$todaySaleCount = intval($stmtSeq->fetchColumn() ?? 0);
?>
<?php
  // Multi-currency: base data stays in KIP. These drive the receipt's 4-currency
  // net-total block and (when not KIP) the POS money display.
  $posDisplayCurrency = getDisplayCurrency($pdo);
?>
<script>
window.POS_BACKEND_URL = '../../api/pos_backend.php';
window.CURRENT_USER_NAME = "<?php echo htmlspecialchars($_SESSION['fname'] ?? $_SESSION['username'] ?? 'Admin'); ?>";
window.POS_TODAY_SALE_COUNT = <?php echo $todaySaleCount; ?>;
window.POS_EX_RATES = <?php echo json_encode($posDisplayCurrency['rates'], JSON_UNESCAPED_UNICODE); ?>;
window.POS_CURRENCY_META = <?php echo json_encode(getCurrencyMeta(), JSON_UNESCAPED_UNICODE); ?>;
window.POS_DISPLAY_CURRENCY = <?php echo json_encode($posDisplayCurrency['code']); ?>;
// category_id -> { printer_id, printer_name }. Empty = no kitchen-ticket splitting configured.
window.POS_KITCHEN_ROUTES = <?php echo json_encode($posKitchenRoutes ?? [], JSON_UNESCAPED_UNICODE); ?>;
window.STORE_NAME = <?php echo json_encode($company['com_name_la'] ?? ($company['com_name'] ?? 'POS'), JSON_UNESCAPED_UNICODE); ?>;
// Show a daily-resetting queue number on the receipt (coffee-shop style). Off for general retail.
window.POS_QUEUE_ENABLED = <?php echo getSetting($pdo, 'queue_enabled', '0') === '1' ? 'true' : 'false'; ?>;

// Stop SweetAlert2 from reflowing the page when an alert opens: heightAuto:false keeps it
// from forcing html/body to height:auto, scrollbarPadding:false keeps it from adding a
// body padding-right. Without this the cart's action buttons jump on every stock warning.
(function () {
  if (!window.Swal || !Swal.fire) return;
  var _fire = Swal.fire.bind(Swal);
  Swal.fire = function (a) {
    if (a && typeof a === 'object' && !Array.isArray(a)) {
      if (a.heightAuto === undefined) a.heightAuto = false;
      if (a.scrollbarPadding === undefined) a.scrollbarPadding = false;
    }
    return _fire.apply(Swal, arguments);
  };
})();
</script>
<link rel="stylesheet" href="../../themes/pos.css?v=<?php echo time(); ?>">

<div class="pos-wrapper">
  <!-- Mobile Navigation / Tab Switcher (Visible ONLY on Mobile Phones < 768px) -->
  <div class="pos-mobile-nav border-bottom bg-white d-md-none">
    <div class="d-flex" style="gap: 6px;">
      <button type="button" class="btn flex-fill font-weight-bold btn-primary active py-2 px-2 shadow-sm" id="mobileTabProductsBtn" onclick="switchMobilePosTab('products')" style="border-radius: 10px; font-size: 1.35rem; min-height: 40px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; font-weight: 800;">
        <i class="fas fa-boxes mr-2" style="font-size: 1.4rem;"></i> ເລືອກສິນຄ້າ
      </button>
      <button type="button" class="btn flex-fill font-weight-bold btn-outline-primary py-2 px-2 shadow-sm" id="mobileTabCartBtn" onclick="switchMobilePosTab('cart')" style="border-radius: 10px; font-size: 1.35rem; min-height: 40px; display: flex; align-items: center; justify-content: center; transition: all 0.2s; font-weight: 800;">
        <i class="fas fa-shopping-cart mr-2" style="font-size: 1.4rem;"></i> ລາຍການຂາຍ
        <span class="badge badge-danger ml-2 font-weight-bold" id="mobileCartCountBadge" style="display: none; font-size: 0.9rem; padding: 4px 8px; border-radius: 10px;">0</span>
      </button>
    </div>
  </div>

  <!-- Left Side: Products Grid & Barcode Scanner -->
  <div class="pos-products">
    <?php require_once __DIR__ . '/partials/search_and_categories.php'; ?>
    <?php require_once __DIR__ . '/partials/product_grid.php'; ?>
  </div>

  <!-- Right Side: POS Cart Panel -->
  <?php require_once __DIR__ . '/partials/cart_section.php'; ?>
</div>

<!-- Mobile Floating Cart Bar (Visible ONLY on Mobile Phones < 768px when on Products tab) -->
<div id="mobileFloatingCartBar" class="d-md-none" style="display: none; position: fixed; bottom: 4px; left: 8px; right: 8px; z-index: 1050;">

  <div class="d-flex align-items-center p-1.5 shadow-lg" style="border-radius: 16px; background: rgba(30, 41, 59, 0.96) !important; backdrop-filter: blur(8px); gap: 6px; box-shadow: 0 8px 24px rgba(0,0,0,0.3) !important;">
    <!-- 1. Clear Cart Button -->
    <button type="button" class="btn btn-danger font-weight-bold d-flex align-items-center justify-content-center px-2.5 py-2" onclick="clearCart()" title="<?php echo htmlspecialchars(t('pos.clear', 'ລ້າງ')); ?>" style="border-radius: 12px; min-height: 44px; flex-shrink: 0;">
      <i class="fas fa-trash-alt" style="font-size: 1.05rem;"></i>
    </button>

    <!-- 2. View Cart Details (Middle Button) -->
    <button type="button" class="btn btn-primary flex-fill font-weight-bold px-2 py-2 d-flex justify-content-between align-items-center" onclick="switchMobilePosTab('cart')" style="border-radius: 12px; min-height: 44px; font-size: 0.88rem; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
      <span class="text-truncate mr-1"><i class="fas fa-shopping-basket mr-1"></i> <span id="floatingCartItemCount">0 <?php echo htmlspecialchars(t('pos.cart_count_items', 'ລາຍການ')); ?></span></span>
      <span class="font-weight-bold text-nowrap" id="floatingCartTotal">0 ₭</span>
    </button>

    <!-- 3. Direct Checkout Button -->
    <button type="button" id="btnCheckoutFloating" class="btn btn-warning font-weight-bold text-dark d-flex align-items-center justify-content-center px-3 py-2 shadow-sm" onclick="openCheckoutModal()" title="<?php echo htmlspecialchars(t('pos.pay', 'ຊຳລະເງິນ')); ?>" style="border-radius: 12px; min-height: 44px; flex-shrink: 0; background: #eab308; border-color: #ca8a04; font-size: 0.92rem; white-space: nowrap;">
      <i class="fas fa-money-bill-wave mr-1"></i> <span><?php echo htmlspecialchars(t('pos.pay', 'ຊຳລະເງິນ')); ?></span>
    </button>
  </div>
</div>

<!-- POS Partial Modals -->
<?php require_once __DIR__ . '/partials/customer_modals.php'; ?>
<?php require_once __DIR__ . '/partials/held_orders_modal.php'; ?>
<?php require_once __DIR__ . '/partials/active_bills_modal.php'; ?>
<?php require_once __DIR__ . '/partials/checkout_modal.php'; ?>

<!-- Hidden Iframe for Thermal Receipt Printing -->
<iframe id="posPrintIframe" style="position: fixed; right: -9999px; bottom: -9999px; width: 300px; height: 300px; border: none; opacity: 0; pointer-events: none;"></iframe>

<!-- POS JavaScript Functionality -->
<?php require_once __DIR__ . '/partials/pos_js.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
