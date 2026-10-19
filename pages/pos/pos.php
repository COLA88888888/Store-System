<?php
// POS Master View Page - Modular & Clean Architecture
require_once __DIR__ . '/../../api/pos_backend.php';

$pos_page = true;
require_once __DIR__ . '/../../layouts/header.php';
?>

<?php
$todayDate = date('Y-m-d');
$stmtSeq = $pdo->prepare("SELECT COUNT(*) FROM st_tbsale_save WHERE sale_date = ?");
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
// { printer_id, printer_name, printer_type, paper_width } or null. Set in Settings > Printers.
// printer_type 'ip' -> the customer receipt prints straight over LAN, no browser dialog.
window.POS_RECEIPT_PRINTER = <?php echo json_encode($posReceiptPrinter, JSON_UNESCAPED_UNICODE); ?>;
window.STORE_NAME = <?php echo json_encode($company['com_name_la'] ?? ($company['com_name'] ?? 'POS'), JSON_UNESCAPED_UNICODE); ?>;
// Show a daily-resetting queue number on the receipt (coffee-shop style). Off for general retail.
window.POS_QUEUE_ENABLED = <?php echo getSetting($pdo, 'queue_enabled', '0') === '1' ? 'true' : 'false'; ?>;
// Loyalty redemption: { enabled, rewards: [{ id, name, points, type: 'discount'|'product', ... }] }
window.POS_LOYALTY = <?php echo json_encode($posLoyalty, JSON_UNESCAPED_UNICODE); ?>;

// Wide screens: the dashboard lifts this page under its navbar so the scan box sits inside it
// (home/partials/dashboard/js_pos_navbar.php). Released again when the page is left.
(function () {
  try {
    if (window.parent === window || !window.parent.setPosNavMode) return;
    window.parent.setPosNavMode(true, window);
    window.addEventListener('pagehide', function () {
      try { window.parent.setPosNavMode(false, window); } catch (e) {}
    });
  } catch (e) {}
})();

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
<!-- CSS ໜ້າຂາຍ ແຍກເປັນ 4 ໄຟລ໌ (ລຳດັບສຳຄັນ) -->
<link rel="stylesheet" href="../../themes/pos/01-layout.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../../themes/pos/02-mobile-cart-search.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../../themes/pos/03-products-checkout.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="../../themes/pos/04-cart-items.css?v=<?php echo time(); ?>">

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
<div id="mobileFloatingCartBar" class="d-md-none" style="display: none;">
  <!-- ແຖບກະຕ່າລອຍ (CSS: .pos-float-* ໃນ themes/pos/04-cart-items.css) -->
  <div class="pos-float-bar">
    <!-- 1. ລ້າງກະຕ່າ -->
    <button type="button" class="pos-float-clear" onclick="clearCart()" title="<?php echo htmlspecialchars(t('pos.clear', 'ລ້າງ')); ?>">
      <i class="fas fa-trash-alt"></i>
    </button>

    <!-- 2. ເບິ່ງກະຕ່າ: ຈຳນວນ (ແຖວເທິງ) + ຍອດລວມ (ແຖວລຸ່ມ) -->
    <button type="button" class="btn btn-primary pos-float-cart" onclick="switchMobilePosTab('cart')">
      <span class="pos-float-cart-icon"><i class="fas fa-shopping-basket"></i></span>
      <span class="pos-float-cart-text">
        <span class="pos-float-cart-count" id="floatingCartItemCount">0 <?php echo htmlspecialchars(t('pos.cart_count_items', 'ລາຍການ')); ?></span>
        <span class="pos-float-cart-total" id="floatingCartTotal">0 ₭</span>
      </span>
      <i class="fas fa-chevron-right pos-float-cart-arrow"></i>
    </button>

    <!-- 3. ຊຳລະເງິນທັນທີ -->
    <button type="button" id="btnCheckoutFloating" class="pos-float-pay" onclick="openCheckoutModal()" title="<?php echo htmlspecialchars(t('pos.pay', 'ຊຳລະເງິນ')); ?>">
      <i class="fas fa-money-bill-wave"></i> <span><?php echo htmlspecialchars(t('pos.pay', 'ຊຳລະເງິນ')); ?></span>
    </button>
  </div>
</div>

<!-- POS Partial Modals -->
<?php require_once __DIR__ . '/partials/customer_modals.php'; ?>
<?php require_once __DIR__ . '/partials/loyalty_modal.php'; ?>
<?php require_once __DIR__ . '/partials/held_orders_modal.php'; ?>
<?php require_once __DIR__ . '/partials/active_bills_modal.php'; ?>
<?php require_once __DIR__ . '/partials/checkout_modal.php'; ?>

<!-- Hidden Iframe for Thermal Receipt Printing -->
<iframe id="posPrintIframe" style="position: fixed; right: -9999px; bottom: -9999px; width: 300px; height: 300px; border: none; opacity: 0; pointer-events: none;"></iframe>

<!-- POS JavaScript Functionality -->
<?php require_once __DIR__ . '/partials/pos_js.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
