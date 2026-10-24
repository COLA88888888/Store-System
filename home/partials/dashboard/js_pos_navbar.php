<?php
/*
 * ສ່ວນ: JS: ບັອກສະແກນຂອງໜ້າຂາຍຢູ່ໃນ Navbar
 * ເມື່ອໜ້າຂາຍ (pages/pos/pos.php) ເປີດຢູ່ໃນ iframe ເທິງຈໍ ≥ 992px: ຍົກ iframe ຂຶ້ນໄປຢູ່ໃຕ້ Navbar,
 * ເຮັດໃຫ້ Navbar ໂປ່ງໃສ ແລະ ສົ່ງຕຳແໜ່ງຊ່ອງວ່າງ + ສີທີມເຂົ້າ iframe ເພື່ອໃຫ້ບັອກສະແກນໄປວາງຢູ່ກາງ Navbar.
 * ໜ້າຂາຍເອີ້ນ window.setPosNavMode(true/false) ເອງ (ຕອນໂຫຼດ ແລະ ຕອນອອກຈາກໜ້າ).
 * CSS ຂອງຝັ່ງໜ້າຂາຍ: html.pos-nav-mode ໃນ themes/pos/02-mobile-cart-search.css.
 */
?>  <?php echo themeCssLink('dashboard-pos-navbar.css'); /* CSS ຍ້າຍໄປ themes/dashboard-pos-navbar.css */ ?>
  <script>
    (function() {
      var posWin = null;   // the POS page's window while it is open in the iframe

      function nav() { return document.querySelector('.main-header.navbar'); }

      // Theme colour of the navbar: js_theme_color.php keeps it in data-theme-bg; before any
      // theme is picked it is still the inline colour from navbar.php.
      function themeColor(n) {
        return n.getAttribute('data-theme-bg') || n.style.getPropertyValue('background-color') || '#244886';
      }

      function pushVars() {
        var n = nav();
        if (!n || !posWin) return;
        var root;
        try { root = posWin.document.documentElement; } catch (e) { return; }
        var r = n.getBoundingClientRect();
        var left = n.querySelector('.navbar-nav:not(.ml-auto)');
        var right = n.querySelector('.navbar-nav.ml-auto');
        var l = left ? left.getBoundingClientRect().right - r.left + 12 : 64;
        var rr = right ? r.right - right.getBoundingClientRect().left + 16 : 560;
        root.style.setProperty('--pos-nav-left', Math.round(l) + 'px');
        root.style.setProperty('--pos-nav-right', Math.round(rr) + 'px');
        root.style.setProperty('--pos-nav-bg', themeColor(n));
        // ຈື່ຕຳແໜ່ງໄວ້ ໃຫ້ບັອກ "ຕົວແທນ" (#posNavGhost) ວາງບ່ອນດຽວກັນເປ໊ະ ໃນການຣີເຟຣດຄັ້ງຕໍ່ໄປ
        try { localStorage.setItem('StoreMini.pos_nav_left', Math.round(l)); } catch (e) {}
        placeGhost(Math.round(l), null);

        try {
          var prod = posWin.document.querySelector('.pos-products');
          if (prod) {
            var pRect = prod.getBoundingClientRect();
            var dist = posWin.innerWidth - pRect.right;
            if (dist > 0) {
              root.style.setProperty('--pos-products-right-dist', Math.round(dist) + 'px');
              try { localStorage.setItem('StoreMini.pos_nav_right_dist', Math.round(dist)); } catch (e) {}
              placeGhost(null, Math.round(dist));
            }
          }
        } catch (e) {}
      }

      // ບັອກສະແກນ "ຕົວແທນ" ໃນ Navbar (navbar.php #posNavGhost)
      function placeGhost(left, right) {
        var g = document.getElementById('posNavGhost');
        if (!g) return;
        if (left !== null) g.style.left = left + 'px';
        if (right !== null) g.style.right = right + 'px';
      }
      // ສະແດງ ເມື່ອໜ້າທີ່ກຳລັງເປີດແມ່ນໜ້າຂາຍ ແຕ່ບັອກແທ້ຍັງບໍ່ພ້ອມ (ໜ້າຂາຍຍັງບໍ່ໂຫຼດ ຫຼື ໂຕໂຫຼດຍັງບັງຢູ່)
      function updateGhost(loading) {
        var g = document.getElementById('posNavGhost');
        if (!g) return;
        var src = '';
        try { src = sessionStorage.getItem('StoreMini.currentIframeSrc') || ''; } catch (e) {}
        var posPage = !!posWin || /pos\/pos\.php/.test(src);
        var realReady = !!posWin && !loading;
        g.classList.toggle('show', window.innerWidth >= 992 && posPage && !realReady);
      }

      function isSystemLoading() {
        if (document.body.classList.contains('is-loading')) return true;
        var el = document.getElementById('iframeLoaderOverlay');
        if (el) {
          var disp = el.style.display || window.getComputedStyle(el).display;
          var op = parseFloat(el.style.opacity || window.getComputedStyle(el).opacity);
          if (disp !== 'none' && op > 0.05) return true;
        }
        return false;
      }

      function apply() {
        var n = nav();
        if (!n) return;
        var on = !!posWin && window.innerWidth >= 992;
        if (!n.hasAttribute('data-theme-bg')) n.setAttribute('data-theme-bg', themeColor(n));
        document.body.classList.toggle('pos-nav-mode', on);
        var loading = isSystemLoading();
        n.style.setProperty('background-color', (on && !loading) ? 'transparent' : n.getAttribute('data-theme-bg'), 'important');
        try { if (posWin) posWin.document.documentElement.classList.toggle('pos-nav-mode', on); } catch (e) {}
        if (on) pushVars();
        updateGhost(loading);
      }

      window.setPosNavMode = function(on, win) {
        if (on) posWin = win || null;
        else if (!win || win === posWin) {
          posWin = null;
          document.body.classList.remove('pos-modal-open');
        }
        apply();
      };

      window.setPosModalOpen = function(isOpen) {
        document.body.classList.toggle('pos-modal-open', !!isOpen);
      };
      // Called by js_theme_color.php after a theme change, so the navbar stays transparent
      // and the POS band picks up the new colour.
      window.refreshPosNavMode = apply;

      window.addEventListener('resize', apply);
      // Sidebar collapse/expand and late-loading navbar fonts change the free space.
      $(document).on('collapsed.lte.pushmenu shown.lte.pushmenu', function() { setTimeout(pushVars, 350); });
      $(window).on('load', pushVars);
    })();
  </script>
