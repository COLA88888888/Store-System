<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * home/dashboard.php ແມ່ນໂຄງຫຼັກ (shell) ຂອງລະບົບ: ແຖບເທິງ, ເມນູຂ້າງ ແລະ iframe ທີ່ທຸກໜ້າໃນ pages/ ຖືກໂຫຼດເຂົ້າ. ໄຟລ໌ນີ້ຍັງເກັບສ່ວນ PHP ຕອນເລີ່ມ, <head>, ກອບເນື້ອຫາ ແລະ ທ້າຍໜ້າ; ສ່ວນໃຫຍ່ແຍກໄປ partials/dashboard/.
 *   - partials/dashboard/head_styles.php : CSS ຂອງໂຄງຫຼັກ
 *   - partials/dashboard/navbar.php : ແຖບເມນູດ້ານເທິງ (Navbar)
 *   - partials/dashboard/js_language.php : JS: ປ່ຽນພາສາທັນທີ
 *   - partials/dashboard/js_notifications.php : JS: ແຈ້ງເຕືອນ
 *   - partials/dashboard/js_notification_polling.php : JS: ດຶງແຈ້ງເຕືອນແບບ realtime ແລະ ໂມງ
 *   - partials/dashboard/js_theme_color.php : JS: ສີທີມ
 *   - partials/dashboard/js_pos_navbar.php : JS: ບັອກສະແກນຂອງໜ້າຂາຍຢູ່ໃນ Navbar
 *   - partials/dashboard/js_layout.php : JS: ພຶດຕິກຳຂອງ layout
 *   - partials/dashboard/modal_transfer_detail.php : Popup ລາຍລະອຽດການໂອນສິນຄ້າ
 */
// ============================================================
// dashboard.php - UNIFIED MASTER LAYOUT (ລະບົບດາດສ໌ບອດຫຼັກ)
// ============================================================
session_start();

// 1. ກວດສອບຄວາມປອດໄພ ແລະ ການເຂົ້າສູ່ລະບົບ
if (!isset($_SESSION['checked']) || $_SESSION['checked'] !== 1 || !isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php?expired=1');
    exit();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lang/translator.php';

$base_path = '../';
$site_name = 'Wlaodev POS';
$site_logo = '../assets/img/logosystem/Wlaodev.jpg';

// Own-account access expiry (owner-only field, set from Users Management). Two flavors:
// - $myAccessDaysLeft: unbounded, used to make the "ແພັກເກັດ" badge show THIS user's own
//   remaining days instead of the shop-wide license days, whenever they have a personal limit.
// - $myAccessDaysLeftForWarning: only set when <=10 days away, drives the notification-bell
//   warning item so the user can ask the owner to extend it before auth/access_expired.php locks them out.
$myAccessDaysLeft = null;
$myAccessDaysLeftForWarning = null;

if (!empty($_SESSION['user_id'])) {
    try {
        $uFetch = $pdo->prepare("SELECT profile_img, fname, lname, username, status, access_expire_date FROM st_tbuser WHERE Id = ? LIMIT 1");
        $uFetch->execute([$_SESSION['user_id']]);
        if ($userRow = $uFetch->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($userRow['profile_img'])) $_SESSION['profile_img'] = $userRow['profile_img'];
            if (isset($userRow['fname'])) $_SESSION['fname'] = $userRow['fname'];
            if (isset($userRow['lname'])) $_SESSION['lname'] = $userRow['lname'];
            if (isset($userRow['status'])) $_SESSION['status'] = $userRow['status'];

            if (intval($_SESSION['user_id']) !== 1 && !empty($userRow['access_expire_date'])) {
                $daysLeft = (int)ceil((strtotime($userRow['access_expire_date']) - strtotime(date('Y-m-d'))) / 86400);
                if ($daysLeft > 0) {
                    $myAccessDaysLeft = $daysLeft;
                    if ($daysLeft <= 10) {
                        $myAccessDaysLeftForWarning = $daysLeft;
                    }
                }
            }
        }
    } catch (Exception $e) {}
}

$display_name = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? ''));
if ($display_name === '') {
    $display_name = $_SESSION['username'] ?? 'admin';
}

$profile_img = $_SESSION['profile_img'] ?? 'default.png';
if (empty($profile_img) || !file_exists(__DIR__ . '/../assets/img/users/' . $profile_img)) {
    $profile_img = 'default.png';
}
$profile_img_path = '../assets/img/users/' . $profile_img;

// ດຶງໜ້າເລີ່ມຕົ້ນຕາມສິດ (Default Page Route based on permissions)
$default_iframe_src = 'home.php';
if (!hasPermission('dashboard')) {
    if (hasPermission('sale')) {
        $default_iframe_src = '../pages/pos/pos.php';
    } elseif (hasPermission('customers')) {
        $default_iframe_src = '../pages/customers/customers.php';
    } elseif (hasPermission('categories')) {
        $default_iframe_src = '../pages/categories/categories.php';
    } elseif (hasPermission('products')) {
        $default_iframe_src = '../pages/products/products.php';
    } elseif (hasPermission('import_stock')) {
        $default_iframe_src = '../pages/import_stock/import_stock.php';
    } elseif (hasPermission('import_list')) {
        $default_iframe_src = '../pages/import_stock/import_list.php';
    } elseif (hasPermission('stock_transfer')) {
        $default_iframe_src = '../pages/import_stock/stock_transfer.php';
    } elseif (hasPermission('transfer_history')) {
        $default_iframe_src = '../pages/import_stock/transfer_history.php';
    } elseif (hasPermission('accounting')) {
        $default_iframe_src = '../pages/bank/bank.php';
    } elseif (hasPermission('report') || hasPermission('daily_report') || hasPermission('all_sales') || hasPermission('best_seller') || hasPermission('profit_cost') || hasPermission('financial') || hasPermission('category_sales') || hasPermission('delete_bills')) {
        $default_iframe_src = '../pages/reports/daily_report.php';
    } elseif (hasPermission('users')) {
        $default_iframe_src = '../pages/users_manage/users_manage.php';
    } elseif (hasPermission('permissions')) {
        $default_iframe_src = '../pages/permissions/permissions.php';
    } elseif (hasPermission('setup') || hasPermission('branches')) {
        $default_iframe_src = '../pages/branches/branches.php';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLang()); ?>" translate="no">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="google" content="notranslate">
  <title><?php echo htmlspecialchars($site_name); ?></title>
  <link rel="shortcut icon" href="<?php echo htmlspecialchars($site_logo); ?>" type="image/jpeg">
  <link rel="icon" href="<?php echo htmlspecialchars($site_logo); ?>" type="image/jpeg">

  <script>
    // ຍ້າຍຂໍ້ມູນ localStorage ເກົ່າ (MiniPos.*) ມາເປັນ StoreMini.* ຄັ້ງດຽວ — ລະບົບອື່ນໃນ localhost
    // (Pharmacy, ClothingPOS, …) ໃຊ້ຊື່ key ດຽວກັນ ຈຶ່ງແຍກອອກ. ສີທີມບໍ່ຍ້າຍ ເພາະເຄີຍປົນກັບລະບົບອື່ນ.
    (function() {
      try {
        if (localStorage.getItem('StoreMini.migrated_keys')) return;
        ['held_orders', 'active_bills', 'bill_opened', 'tender_currency', 'local_stocks',
         'minipos_modal_page_size', 'minipos_product_category_filter', 'minipos_product_per_page',
         'minipos_last_add_category_id', 'minipos_last_add_unit_id'].forEach(function(k) {
          var v = localStorage.getItem('MiniPos.' + k);
          if (v !== null && localStorage.getItem('StoreMini.' + k) === null) localStorage.setItem('StoreMini.' + k, v);
        });
        localStorage.setItem('StoreMini.migrated_keys', '1');
      } catch (e) {}
    })();
  </script>
  <script>
    (function() {
      // ກວດສອບວ່າ Tab ນີ້ໄດ້ຜ່ານການ Login ໂດຍກົງຫຼືບໍ່ (ຖ້າກັອບປີ້ Link ມາວາງຢູ່ Tab ໃໝ່ -> ເດັ້ງໄປ Login ທັນທີ)
      try {
        var activeTabToken = sessionStorage.getItem('StoreMini.pos_tab_active');
        if (!activeTabToken || activeTabToken.indexOf('TOKEN_') !== 0) {
          window.location.href = '../auth/logout.php?expired=1';
          return;
        }
        var savedSrc = sessionStorage.getItem('StoreMini.currentIframeSrc') || '';
        var isPos = savedSrc.indexOf('pos.php') !== -1 || savedSrc.indexOf('pos/') !== -1;
        if (isPos) {
          document.documentElement.classList.add('sidebar-collapse');
        }
      } catch(e) {
        window.location.href = '../auth/logout.php?expired=1';
      }
    })();
  </script>

<?php require __DIR__ . '/partials/dashboard/head_styles.php'; /* CSS ຂອງໂຄງຫຼັກ */ ?>
  <!-- Scripts -->
  <script src="../sweetalert/dist/sweetalert2.all.min.js"></script>
  <script src="../plugins/jquery/jquery.min.js"></script>
  <script src="../plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
</head>
<body class="hold-transition sidebar-mini sidebar-no-expand layout-fixed">
<script>
  if (document.documentElement.classList.contains('sidebar-collapse')) {
    document.body.classList.add('sidebar-collapse');
  }
</script>

<div class="wrapper">

<?php require __DIR__ . '/partials/dashboard/navbar.php'; /* ແຖບເມນູດ້ານເທິງ (Navbar) */ ?>
<?php require __DIR__ . '/partials/dashboard/js_language.php'; /* JS: ປ່ຽນພາສາທັນທີ */ ?>
<?php require __DIR__ . '/partials/dashboard/js_notifications.php'; /* JS: ແຈ້ງເຕືອນ */ ?>
<?php require __DIR__ . '/partials/dashboard/js_notification_polling.php'; /* JS: ດຶງແຈ້ງເຕືອນແບບ realtime ແລະ ໂມງ */ ?>
  <!-- /.navbar -->

  <!-- ແຖບເມນູທາງຊ້າຍ (Unified Dynamic Sidebar) -->
  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

<?php require __DIR__ . '/partials/dashboard/js_theme_color.php'; /* JS: ສີທີມ */ ?>
<?php require __DIR__ . '/partials/dashboard/js_pos_navbar.php'; /* JS: ບັອກສະແກນຂອງໜ້າຂາຍຢູ່ໃນ Navbar */ ?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper" style="background-color: #f4f6f9; position: relative;">
    <!-- Page Loading Spinner Overlay (12-Dot Pulse Circle Spinner from Screenshot) -->
    <style>
      .loader-dots-spinner {
        color: #0263ff;
        display: inline-block;
        position: relative;
        width: 56px;
        height: 56px;
      }
      .loader-dots-spinner div {
        transform-origin: 28px 28px;
        animation: loader-dots-spinner-anim 1.2s linear infinite;
      }
      .loader-dots-spinner div:after {
        content: " ";
        display: block;
        position: absolute;
        top: 4px;
        left: 25px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #0263ff;
      }
      .loader-dots-spinner div:nth-child(1) { transform: rotate(0deg); animation-delay: -1.1s; }
      .loader-dots-spinner div:nth-child(2) { transform: rotate(30deg); animation-delay: -1s; }
      .loader-dots-spinner div:nth-child(3) { transform: rotate(60deg); animation-delay: -0.9s; }
      .loader-dots-spinner div:nth-child(4) { transform: rotate(90deg); animation-delay: -0.8s; }
      .loader-dots-spinner div:nth-child(5) { transform: rotate(120deg); animation-delay: -0.7s; }
      .loader-dots-spinner div:nth-child(6) { transform: rotate(150deg); animation-delay: -0.6s; }
      .loader-dots-spinner div:nth-child(7) { transform: rotate(180deg); animation-delay: -0.5s; }
      .loader-dots-spinner div:nth-child(8) { transform: rotate(210deg); animation-delay: -0.4s; }
      .loader-dots-spinner div:nth-child(9) { transform: rotate(240deg); animation-delay: -0.3s; }
      .loader-dots-spinner div:nth-child(10) { transform: rotate(270deg); animation-delay: -0.2s; }
      .loader-dots-spinner div:nth-child(11) { transform: rotate(300deg); animation-delay: -0.1s; }
      .loader-dots-spinner div:nth-child(12) { transform: rotate(330deg); animation-delay: 0s; }
      @keyframes loader-dots-spinner-anim {
        0% { opacity: 1; }
        100% { opacity: 0.15; }
      }
    </style>
    <div id="iframeLoaderOverlay" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.94); z-index: 999; display: flex; flex-direction: column; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
      <div class="loader-dots-spinner">
        <div></div><div></div><div></div><div></div>
        <div></div><div></div><div></div><div></div>
        <div></div><div></div><div></div><div></div>
      </div>
      <div style="margin-top: 18px; font-weight: 700; color: #1e293b; font-size: 1.05rem; letter-spacing: 0.5px;" data-i18n="layout.loading_data">
        <?php echo htmlspecialchars(t('layout.loading_data', 'ກຳລັງໂຫຼດຂໍ້ມູນ...')); ?>
      </div>
    </div>

    <iframe width="100%" height="100%" frameborder="0" name="frame" src="<?php echo $default_iframe_src; ?>" style="background-color: #f4f6f9;"></iframe>
    <style>
      .content-wrapper {
        height: calc(var(--app-vh, 1vh) * 100 - 64px - 42px) !important;
      }
      @media (max-width: 991.98px) {
        .content-wrapper {
          height: calc(var(--app-vh, 1vh) * 100 - 64px - 42px) !important;
        }
      }
    </style>
    <script>
      // 100vh on tablet/mobile browsers can include area hidden behind the address bar/toolbar,
      // which made the content-wrapper (and anything pinned to its bottom, like the POS action
      // buttons) taller than what's actually visible with no way to scroll to it. Measure the
      // real visible height with window.innerHeight instead and drive the CSS off that.
      (function () {
        function setAppVh() {
          document.documentElement.style.setProperty('--app-vh', (window.innerHeight * 0.01) + 'px');
        }
        setAppVh();
        window.addEventListener('resize', setAppVh);
        window.addEventListener('orientationchange', setAppVh);
      })();
      function hideFastLoader() {
        var loader = document.getElementById('iframeLoaderOverlay');
        if (loader) {
          loader.style.opacity = '0';
          loader.style.transition = 'opacity 0.25s ease';
          setTimeout(function() { loader.style.display = 'none'; }, 250);
        }
      }
      function showFastLoader() {
        var loader = document.getElementById('iframeLoaderOverlay');
        if (loader) {
          loader.style.display = 'flex';
          loader.style.opacity = '1';
        }
      }
      (function() {
        var defaultSrc = '<?php echo $default_iframe_src; ?>';
        var hasDashboardPerm = <?php echo hasPermission('dashboard') ? 'true' : 'false'; ?>;
        var savedSrc = sessionStorage.getItem('StoreMini.currentIframeSrc');
        if (!hasDashboardPerm && (!savedSrc || savedSrc === 'home.php' || savedSrc.endsWith('/home.php'))) {
          savedSrc = defaultSrc;
          sessionStorage.setItem('StoreMini.currentIframeSrc', defaultSrc);
        }
        if (savedSrc) {
          document.getElementsByName('frame')[0].src = savedSrc;
        }
        // Safety timeout: automatically hide loading spinner after 600ms
        setTimeout(hideFastLoader, 600);
      })();
    </script>
  </div>

  <!-- Main Footer -->
  <footer class="main-footer" style="background: #ffffff; border-top: 1px solid #cbd5e1; color: #475569; padding: 0 20px; font-size: 0.88rem; height: 42px; display: flex; align-items: center; justify-content: space-between; box-sizing: border-box;">
    <div style="font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 6px;">
      <span>Wlaodev Pos System</span>
    </div>
    <div style="font-weight: 700; color: #64748b;">
      <span>Version 3.8.26</span>
    </div>
  </footer>

</div>
<!-- ./wrapper -->

<!-- Bootstrap 4 -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.js"></script>

<?php require __DIR__ . '/partials/dashboard/js_layout.php'; /* JS: ພຶດຕິກຳຂອງ layout */ ?>
<?php require __DIR__ . '/partials/dashboard/modal_transfer_detail.php'; /* Popup ລາຍລະອຽດການໂອນສິນຄ້າ */ ?>
<script>
  // Keep the kip symbol (U+20AD) glued to its amount in the app shell too (low-stock alerts,
  // transfer modals, JS-rendered notification rows). Mirrors the pass in layouts/footer.php;
  // the shell doesn't include that file.
  (function() {
      var KIP = '₭';
      var NBSP = String.fromCharCode(160);
      var KIP_RE = /(\d)\s+₭/g;
      function fixKipInNode(node) {
          var v = node.nodeValue;
          if (!v || v.indexOf(KIP) === -1) return;
          var next = v.replace(KIP_RE, '$1' + NBSP + KIP);
          if (next !== v) node.nodeValue = next;
      }
      function walkAndFix(root) {
          if (!root) return;
          if (root.nodeType === 3) { fixKipInNode(root); return; }
          if (root.nodeType !== 1) return;
          var tag = root.tagName;
          if (tag === 'SCRIPT' || tag === 'STYLE' || tag === 'TEXTAREA') return;
          var walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, null);
          var n;
          while ((n = walker.nextNode())) fixKipInNode(n);
      }
      function boot() {
          walkAndFix(document.body);
          if (!('MutationObserver' in window) || !document.body) return;
          var queued = false, pending = [];
          var mo = new MutationObserver(function(muts) {
              for (var i = 0; i < muts.length; i++) {
                  var m = muts[i];
                  if (m.type === 'characterData') { pending.push(m.target); }
                  else { for (var j = 0; j < m.addedNodes.length; j++) pending.push(m.addedNodes[j]); }
              }
              if (queued) return;
              queued = true;
              requestAnimationFrame(function() {
                  queued = false;
                  var batch = pending; pending = [];
                  for (var k = 0; k < batch.length; k++) walkAndFix(batch[k]);
              });
          });
          mo.observe(document.body, { childList: true, subtree: true, characterData: true });
      }
      if (document.readyState === 'loading') {
          document.addEventListener('DOMContentLoaded', boot);
      } else {
          boot();
      }
  })();
</script>

</body>
</html>
