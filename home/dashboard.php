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
 *   - partials/dashboard/js_live_permissions.php : JS: ສິດ realtime (ເມນູ/ໜ້າ ສະແດງ-ເຊື່ອງ ທັນທີທີ່ສິດປ່ຽນ)
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
$site_name = 'Minimart Wlaodev POS';
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
    } elseif (hasPermission('stock_check') || hasPermission('stock_count')) {
        $default_iframe_src = '../pages/stock_check/stock_check.php';
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

// ພະນັກງານເຂົ້າລະບົບ + ສາຂາມີກະ ແຕ່ຍັງບໍ່ມີກະເປີດ → ໄປໜ້າຂາຍສະເໝີ (ໜ້າຂາຍເດັ້ງຟອມປ້ອນເງິນລິ້ນຊັກ = ເປີດກະ,
// pages/pos/partials/js/pos_shift_js.php) ທັບໜ້າທີ່ເປີດຄ້າງໄວ້ໃນ sessionStorage
$forceShiftOpen = false;
if (($_SESSION['status'] ?? '') === 'ພະນັກງານ' && intval($_SESSION['user_id'] ?? 0) !== 1 && hasPermission('sale')) {
    try {
        $fsStore = intval($_SESSION['store_id'] ?? 0);
        $fsSt = $pdo->prepare("SELECT EXISTS(SELECT 1 FROM st_shifts WHERE store_id = ? AND status = 1)
                                  AND NOT EXISTS(SELECT 1 FROM st_shift_sessions WHERE store_id = ? AND status = 'open')");
        $fsSt->execute([$fsStore, $fsStore]);
        $forceShiftOpen = (bool)$fsSt->fetchColumn();
    } catch (Throwable $e) {}
    if ($forceShiftOpen) $default_iframe_src = '../pages/pos/pos.php';
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(getCurrentLang()); ?>" translate="no">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
  <script>
    // ປ້ອງກັນການຊູມໜ້າຈໍອັດຕະໂນມັດເມື່ອກົດປຸ່ມ ຫຼື ຊ່ອງຄົ້ນຫາເທິງມືຖື (iOS Safari & Chrome)
    document.addEventListener('gesturestart', function(e) { e.preventDefault(); }, { passive: false });
  </script>
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
  <!-- ເຄື່ອງພິມ Bluetooth: ການເຊື່ອມຕໍ່ຢູ່ກັບໜ້າຫຼັກນີ້ (ໜ້າໃນ iframe ໃຊ້ຕົວດຽວກັນ) ຈຶ່ງບໍ່ຂາດເວລາປ່ຽນໜ້າ.
       ເຊື່ອມ/ຕັ້ງຄ່າ ຢູ່ ຕັ້ງຄ່າ > ເຄື່ອງພິມ (pages/settings/printers/partials/bluetooth_card.php) -->
  <script src="../assets/js/bluetooth-printer-escpos.js?v=<?php echo @filemtime(__DIR__ . '/../assets/js/bluetooth-printer-escpos.js'); ?>"></script>
  <script src="../assets/js/bluetooth-printer.js?v=<?php echo @filemtime(__DIR__ . '/../assets/js/bluetooth-printer.js'); ?>"></script>
  <script>
    // ເປີດລະບົບ: ເຊື່ອມເຄື່ອງພິມ Bluetooth ທີ່ຕັ້ງໄວ້ຄືນອັດຕະໂນມັດທັນທີ ແລະ ກວດຊ້ຳທຸກ 4 ວິ ຖ້າເຄື່ອງພິມຫາກໍ່ເປີດ
    (function () {
      if (!window.MiniposBtPrinter) return;
      var enabled = function () {
        try { var s = JSON.parse(localStorage.getItem('StoreMini.bt_printer_settings') || '{}') || {}; return s.enabled !== false; } catch (e) { return true; }
      };
      var tryIt = function () { if (enabled() && MiniposBtPrinter.list().length && !MiniposBtPrinter.isConnected()) MiniposBtPrinter.reconnect(); };
      document.addEventListener('DOMContentLoaded', tryIt);
      setInterval(tryIt, 4000);
      // ກົດ F5 / Ctrl+R ໃຫ້ຣີເຟຣດສະເພາະໜ້າໃນ iframe ເພື່ອບໍ່ໃຫ້ການເຊື່ອມຕໍ່ Bluetooth ໃນໜ້າຫຼັກຂາດ
      window.addEventListener('keydown', function (e) {
        var isReload = e.key === 'F5' || ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey && (e.key === 'r' || e.key === 'R'));
        if (!isReload || !MiniposBtPrinter.list().length) return;
        var fr = document.getElementsByName('frame')[0];
        if (fr && fr.contentWindow) {
          e.preventDefault();
          if (typeof showFastLoader === 'function') showFastLoader();
          try { fr.contentWindow.location.reload(); } catch (x) { fr.src = fr.src; }
        }
      }, true);
    })();
  </script>
</head>
<body class="hold-transition sidebar-mini sidebar-no-expand layout-fixed is-loading">
<script>
  if (document.documentElement.classList.contains('sidebar-collapse')) {
    document.body.classList.add('sidebar-collapse');
  }
</script>

<div class="wrapper">

<?php require __DIR__ . '/partials/dashboard/navbar.php'; /* ແຖບເມນູດ້ານເທິງ (Navbar) */ ?>
<?php require __DIR__ . '/partials/dashboard/js_language.php'; /* JS: ປ່ຽນພາສາທັນທີ */ ?>
<?php require __DIR__ . '/partials/dashboard/js_notifications.php'; /* JS: ແຈ້ງເຕືອນ */ ?>
<?php require __DIR__ . '/partials/dashboard/js_stock_check_notif.php'; /* JS: ແຈ້ງເຕືອນເຊັກສະຕັອກ (ສົ່ງກວດ / ອະນຸມັດແລ້ວ) */ ?>
<?php require __DIR__ . '/partials/dashboard/js_notification_polling.php'; /* JS: ດຶງແຈ້ງເຕືອນແບບ realtime ແລະ ໂມງ */ ?>
  <!-- /.navbar -->

  <!-- ແຖບເມນູທາງຊ້າຍ (Unified Dynamic Sidebar) -->
  <?php include __DIR__ . '/../layouts/sidebar.php'; ?>

<?php require __DIR__ . '/partials/dashboard/js_theme_color.php'; /* JS: ສີທີມ */ ?>
<?php require __DIR__ . '/partials/dashboard/js_pos_navbar.php'; /* JS: ບັອກສະແກນຂອງໜ້າຂາຍຢູ່ໃນ Navbar */ ?>
  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper" style="background-color: #f4f6f9; position: relative;">
    <!-- Page Loading Logo Overlay -->
    <?php echo themeCssLink('dashboard-preloader.css'); /* CSS ຍ້າຍໄປ themes/dashboard-preloader.css */ ?>
    <div id="iframeLoaderOverlay" class="system-loader-overlay">
      <div class="system-loader-wrapper">
        <div class="system-loader-pulse-ring"></div>
        <?php $wlao_ver = @filemtime(__DIR__ . '/../assets/img/logosystem/Wlaodev.png') ?: 1; ?>
        <img src="../assets/img/logosystem/Wlaodev.png?v=<?php echo $wlao_ver; ?>" alt="WLaodev" class="system-loader-logo">
      </div>
      <div class="system-loader-text" data-i18n="layout.loading_data">
        <span><?php echo htmlspecialchars(t('layout.loading_data', 'ກຳລັງໂຫຼດຂໍ້ມູນ')); ?></span><span class="loader-dot d1">.</span><span class="loader-dot d2">.</span><span class="loader-dot d3">.</span>
      </div>
      <div class="system-loader-slow-hint" id="systemLoaderSlowHint">ກະລຸນາລໍຖ້າຈັກໜ່ອຍ... ລະບົບກຳລັງປະມວນຜົນ</div>
    </div>

    <iframe id="mainContentFrame" width="100%" height="100%" frameborder="0" name="frame" src="<?php echo $default_iframe_src; ?>" onload="if (typeof hideFastLoader === 'function') hideFastLoader();" style="background-color: #f4f6f9;"></iframe>
    <?php echo themeCssLink('dashboard-frame.css'); /* CSS ຍ້າຍໄປ themes/dashboard-frame.css */ ?>
    <script>
      (function () {
        function setAppVh() {
          document.documentElement.style.setProperty('--app-vh', (window.innerHeight * 0.01) + 'px');
        }
        setAppVh();
        window.addEventListener('resize', setAppVh);
        window.addEventListener('orientationchange', setAppVh);
      })();

      var loaderSafetyTimer = null;
      var slowNetHintTimer = null;

      function hideFastLoader() {
        clearTimeout(loaderSafetyTimer);
        clearTimeout(slowNetHintTimer);
        var hint = document.getElementById('systemLoaderSlowHint');
        if (hint) hint.classList.remove('show');
        var loader = document.getElementById('iframeLoaderOverlay');
        if (loader) {
          loader.style.opacity = '0';
          loader.style.transition = 'opacity 0.25s ease';
          setTimeout(function() {
            loader.style.display = 'none';
            document.body.classList.remove('is-loading');
            if (typeof window.refreshPosNavMode === 'function') window.refreshPosNavMode();
          }, 250);
        } else {
          document.body.classList.remove('is-loading');
          if (typeof window.refreshPosNavMode === 'function') window.refreshPosNavMode();
        }
      }

      function showFastLoader() {
        clearTimeout(loaderSafetyTimer);
        clearTimeout(slowNetHintTimer);
        document.body.classList.add('is-loading');
        if (typeof window.refreshPosNavMode === 'function') window.refreshPosNavMode();
        var hint = document.getElementById('systemLoaderSlowHint');
        if (hint) hint.classList.remove('show');
        var loader = document.getElementById('iframeLoaderOverlay');
        if (loader) {
          loader.style.display = 'flex';
          loader.style.opacity = '1';
        }
        // ຖ້າເນັດຊ້າເກີນ 2.2 ວິນາທີ ໃຫ້ໂຊຄຳແນະນຳ
        slowNetHintTimer = setTimeout(function() {
          var h = document.getElementById('systemLoaderSlowHint');
          if (h) h.classList.add('show');
        }, 2200);
        // Failsafe ຍາວ 25 ວິນາທີ ເພື່ອໃຫ້ໂຫຼດຕາມຄວາມໄວອິນເຕີເນັດຕົວຈິງ
        loaderSafetyTimer = setTimeout(hideFastLoader, 25000);
      }

      (function() {
        var defaultSrc = '<?php echo $default_iframe_src; ?>';
        var hasDashboardPerm = <?php echo hasPermission('dashboard') ? 'true' : 'false'; ?>;
        var savedSrc = sessionStorage.getItem('StoreMini.currentIframeSrc');
        var forceShiftOpen = <?php echo $forceShiftOpen ? 'true' : 'false'; ?>;   // ພະນັກງານຕ້ອງເປີດກະກ່ອນ → ໜ້າຂາຍ
        if (forceShiftOpen || (!hasDashboardPerm && (!savedSrc || savedSrc === 'home.php' || savedSrc.endsWith('/home.php')))) {
          savedSrc = defaultSrc;
          sessionStorage.setItem('StoreMini.currentIframeSrc', defaultSrc);
        }
        if (savedSrc) {
          document.getElementsByName('frame')[0].src = savedSrc;
        }
        // Failsafe ສຳລັບການເປີດໜ້າທຳອິດ
        loaderSafetyTimer = setTimeout(hideFastLoader, 15000);
      })();
    </script>
  </div>

</div>
<!-- ./wrapper -->

<!-- Bootstrap 4 -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="../dist/js/adminlte.js"></script>

<?php require __DIR__ . '/partials/dashboard/js_layout.php'; /* JS: ພຶດຕິກຳຂອງ layout */ ?>
<?php require __DIR__ . '/partials/dashboard/js_live_permissions.php'; /* JS: ສິດ realtime — ສະແດງ/ເຊື່ອງເມນູ, ໂຫຼດໜ້າໃໝ່ເມື່ອສິດປ່ຽນ */ ?>
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
