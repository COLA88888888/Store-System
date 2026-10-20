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
      }

      function apply() {
        var n = nav();
        if (!n) return;
        var on = !!posWin && window.innerWidth >= 992;
        if (!n.hasAttribute('data-theme-bg')) n.setAttribute('data-theme-bg', themeColor(n));
        document.body.classList.toggle('pos-nav-mode', on);
        n.style.setProperty('background-color', on ? 'transparent' : n.getAttribute('data-theme-bg'), 'important');
        try { if (posWin) posWin.document.documentElement.classList.toggle('pos-nav-mode', on); } catch (e) {}
        if (on) pushVars();
      }

      window.setPosNavMode = function(on, win) {
        if (on) posWin = win || null;
        else if (!win || win === posWin) posWin = null;
        apply();
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
