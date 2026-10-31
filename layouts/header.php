<?php
if (!isset($base_path)) {
    $base_path = '';
}
if (empty($_SESSION['checked']) || empty($_SESSION['user_id'])) {
    echo "<script>window.top.location.href = '" . $base_path . "auth/login.php?expired=1';</script>";
    exit();
}
$header_site_name = 'Minimart WLaodev POS';
$header_site_logo = $base_path . 'assets/img/logosystem/Wlaodev.jpg';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(function_exists('getCurrentLang') ? getCurrentLang() : 'lo'); ?>" translate="no">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <script>
      // ປ້ອງກັນການຊູມໜ້າຈໍອັດຕະໂນມັດເມື່ອກົດປຸ່ມ ຫຼື ຊ່ອງຄົ້ນຫາເທິງມືຖື (iOS Safari & Chrome)
      document.addEventListener('gesturestart', function(e) { e.preventDefault(); }, { passive: false });
    </script>
    <meta name="google" content="notranslate">
    <title><?php echo htmlspecialchars($header_site_name); ?></title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($header_site_logo); ?>" type="image/jpeg">
    <link rel="icon" href="<?php echo htmlspecialchars($header_site_logo); ?>" type="image/jpeg">

    <!-- System Modern Font - Noto Sans Lao -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/local-font.css?v=<?php echo time(); ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/fontawesome-free/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>ionicons-2.0.1/css/ionicons.min.css">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <!-- iCheck -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/icheck-bootstrap/icheck-bootstrap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>dist/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/global-custom/01-base.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/global-custom/02-components.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo $base_path; ?>assets/css/global-custom/03-controls.css?v=<?php echo time(); ?>">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
    <!-- Select2 Searchable Dropdown -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- Flatpickr: base styles, then the plain white MiniPos calendar used by every date box.
         (assets/css/flatpickr.min.css is an old blue-gradient inline theme, no longer loaded:
         its unscoped rules restyled every popup calendar.) -->
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>plugins/flatpickr/minipos-datepicker.css?v=<?php echo @filemtime(__DIR__ . '/../plugins/flatpickr/minipos-datepicker.css'); ?>">

    <!-- ຄຳແປຂໍ້ຄວາມລາວໃນໄຟລ໌ .js ລ້ວນ: window.__tl('ລາວ') (lang/translator.php lxJsMapScript) -->
    <?php echo lxJsMapScript(); ?>

    <!-- Core JavaScript Libraries (Load in head so all page scripts & components can use $ and Swal) -->
    <script src="<?php echo $base_path; ?>plugins/jquery/jquery.min.js"></script>
    <script src="<?php echo $base_path; ?>plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo $base_path; ?>plugins/sweetalert2/sweetalert2.all.min.js"></script>
    <script>
      // ແກ້ໄຂບັນຫາ Bootstrap Modal ຍາດ Focus ເມື່ອມີ SweetAlert2 Popup ເປີດຊ້ອນເທິງ Modal
      // ເຮັດໃຫ້ສາມາດຄລິກພິມ ແລະ ປ້ອນຂໍ້ຄວາມໃນ Input ຂອງ SweetAlert2 ໄດ້ທຸກຟອມຢ່າງສະດວກ
      if (window.jQuery && $.fn.modal && $.fn.modal.Constructor) {
        $.fn.modal.Constructor.prototype._enforceFocus = function() {
          var that = this;
          $(document)
            .off('focusin.bs.modal')
            .on('focusin.bs.modal', function(e) {
              if (
                that._element !== e.target &&
                !that._element.contains(e.target) &&
                !$(e.target).closest('.swal2-container').length
              ) {
                that._element.focus();
              }
            });
        };
      }
    </script>
    <!-- Every <input type="date"> becomes the same Lao calendar, converted as the page is parsed -->
    <script src="<?php echo $base_path; ?>plugins/flatpickr/flatpickr.min.js"></script>
    <script src="<?php echo $base_path; ?>plugins/flatpickr/minipos-datepicker.js?v=<?php echo @filemtime(__DIR__ . '/../plugins/flatpickr/minipos-datepicker.js'); ?>"></script>
    <script>
      // Offline mode has been removed. Unregister any service worker still installed
      // from an older build so it can't serve stale cached pages.
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(function(regs) {
          regs.forEach(function(reg) { reg.unregister(); });
        }).catch(function() {});
      }
    </script>
    <?php echo themeCssLink('layout-header.css'); /* CSS ຍ້າຍໄປ themes/layout-header.css */ ?>
    <script>
      // ສີທີມ: ໃສ່ກ່ອນໜ້າຈະສະແດງ (ຢູ່ໃນ <head>) — ບໍ່ດັ່ງນັ້ນ ຣີເຟຣດແລ້ວປຸ່ມ/pagination ເປັນສີເລີ່ມຕົ້ນກ່ອນ
      // ແລ້ວຈຶ່ງປ່ຽນເປັນສີທີ່ເລືອກ. ກົດ CSS ມາຈາກ shell (home/partials/dashboard/js_theme_color.php)
      // ໃຊ້ id ດຽວກັນ ສະນັ້ນ shell ອັບເດດແທັກນີ້ຕໍ່ໄດ້ເລີຍ ເວລາປ່ຽນສີ.
      (function() {
        try {
          var c = localStorage.getItem('StoreMini.theme_color');
          var p = window.parent;
          if (!c || !p || p === window || typeof p.posThemeButtonCSS !== 'function') return;
          var s = document.createElement('style');
          s.id = 'posThemeBtnStyle';
          s.textContent = p.posThemeButtonCSS(c);
          document.head.appendChild(s);
          // ອົງປະກອບທີ່ຂຽນສີໄວ້ inline — ທາສີທັນທີທີ່ DOM ພ້ອມ (ບໍ່ລໍ load event)
          document.addEventListener('DOMContentLoaded', function() {
            try { if (typeof p.posThemeReinject === 'function') p.posThemeReinject(); } catch (e) {}
          });
        } catch (e) {}
      })();
    </script>
  </head>
  <body class="hold-transition sidebar-mini layout-fixed<?php echo !empty($pos_page) ? ' pos-page' : ''; ?>">
    <script>
      // Pages load inside the dashboard iframe, where there's no sidebar. AdminLTE only adds
      // body.iframe-mode (content-wrapper margin-left: 0) once its JS runs at the end of the
      // page, so until then the content sat 250px to the right and then snapped to full width
      // on every page change. Set it before anything paints.
      try { if (window.self !== window.top) document.body.classList.add('iframe-mode'); } catch (e) { document.body.classList.add('iframe-mode'); }
    </script>
    <?php if (empty($pos_page)): ?>
    <!-- Smart Delayed Global Preloader: Triggers only if live server latency > 600ms -->
    <div id="global-preloader">
        <div class="preloader-content">
            <div class="system-loader-wrapper">
                <div class="system-loader-pulse-ring"></div>
                <?php $wlao_ver = @filemtime(dirname(__DIR__) . '/assets/img/logosystem/Wlaodev.png') ?: 1; ?>
                <img src="<?php echo $base_path; ?>assets/img/logosystem/Wlaodev.png?v=<?php echo $wlao_ver; ?>" alt="WLaodev" class="system-loader-logo">
            </div>
            <span class="preloader-text">
                <span><?php echo htmlspecialchars(t('layout.loading', 'ກຳລັງດຶງຂໍ້ມູນ')); ?></span><span class="loader-dot d1">.</span><span class="loader-dot d2">.</span><span class="loader-dot d3">.</span>
            </span>
            <div class="system-loader-slow-hint" id="globalPreloaderSlowHint"><?php echo tlh('ກະລຸນາລໍຖ້າຈັກໜ່ອຍ... ລະບົບກຳລັງປະມວນຜົນ'); ?></div>
        </div>
    </div>
    <script>
      window._slowNetPreloaderTimer = setTimeout(function() {
        var p = document.getElementById('global-preloader');
        if (p) p.classList.add('show-slow-net');
        setTimeout(function() {
          var h = document.getElementById('globalPreloaderSlowHint');
          if (h) h.classList.add('show');
        }, 1600);
      }, 600);
      // Failsafe: footer.php normally hides it, but if the page never reaches the footer (a PHP
      // error mid-page, a script that fails to load) the overlay covered the page forever.
      (function() {
        function hideGlobalPreloader() {
          clearTimeout(window._slowNetPreloaderTimer);
          var p = document.getElementById('global-preloader');
          if (p) { p.classList.remove('show-slow-net'); p.style.display = 'none'; }
        }
        window.addEventListener('load', hideGlobalPreloader);
        setTimeout(hideGlobalPreloader, 25000);
      })();
    </script>
    <?php endif; ?>
