<?php
/*
 * ສ່ວນ: JS: ສີທີມ
 * ປ່ຽນສີແຖບເທິງ, ເມນູຂ້າງ, ປຸ່ມ ແລະ pagination ຕາມສີທີ່ເລືອກ (ບັນທຶກໃນ browser) ແລະ
 * ສົ່ງສີເຂົ້າໄປໃນທຸກໜ້າຂອງ iframe.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/dashboard.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?>  <script>
    // Theme colour: recolour sidebar + navbar chrome from a per-browser saved choice.
    (function() {
      var STORE_KEY = 'StoreMini.theme_color';
      var DEFAULT_COLOR = '#244886';

      // Lighten (percent > 0) or darken (percent < 0) a #rrggbb colour.
      function shade(hex, percent) {
        hex = String(hex).replace('#', '');
        if (hex.length !== 6) return '#' + hex;
        var r = parseInt(hex.substr(0, 2), 16),
            g = parseInt(hex.substr(2, 2), 16),
            b = parseInt(hex.substr(4, 2), 16);
        var t = percent < 0 ? 0 : 255, p = Math.abs(percent);
        r = Math.round((t - r) * p) + r;
        g = Math.round((t - g) * p) + g;
        b = Math.round((t - b) * p) + b;
        return '#' + [r, g, b].map(function(x) { return ('0' + x.toString(16)).slice(-2); }).join('');
      }

      // Recolour the active / clicked sidebar menu highlight to match the theme.
      function paintActiveHighlight(color) {
        var css =
          '.nav-sidebar > .nav-item:not(.has-treeview) > .nav-link.active,' +
          '.nav-sidebar .nav-treeview .nav-link.active{' +
          'background:linear-gradient(135deg,' + shade(color, -0.28) + ',' + shade(color, 0.38) + ') !important;}';
        var tag = document.getElementById('posThemeActiveStyle');
        if (!tag) {
          tag = document.createElement('style');
          tag.id = 'posThemeActiveStyle';
          document.head.appendChild(tag);
        }
        tag.textContent = css;
      }

      // Override rules for buttons / primary accents — injected into BOTH the shell
      // and the iframe page so the whole system follows the chosen colour.
      // includeInline: also retarget the hard-coded blue-gradient inline styles
      // (safe inside the iframe; skipped in the shell where it would hit chrome).
      // NOTE: every selector is prefixed with `body ` so it out-specifies the
      // per-page theme stylesheets (themes/pos.css, themes/reports.css, …) that
      // are <link>ed inside <body> and would otherwise win on source order.
      function buttonCSS(color, includeInline) {
        var d = shade(color, -0.18), l = shade(color, 0.20), gd = shade(color, -0.28);
        var rules = [
          'body .btn-primary,body .btn-primary.disabled,body .btn-primary:disabled{background:' + color + '!important;background-image:none!important;border-color:' + d + '!important}',
          'body .btn-primary:hover,body .btn-primary:focus,body .btn-primary:not(:disabled):not(.disabled):active,body .btn-primary:not(:disabled):not(.disabled).active{background:' + d + '!important;background-image:none!important;border-color:' + d + '!important}',
          'body .btn-outline-primary{color:' + color + '!important;border-color:' + color + '!important;background:transparent!important}',
          'body .btn-outline-primary:hover,body .btn-outline-primary:not(:disabled):not(.disabled):active,body .btn-outline-primary:not(:disabled):not(.disabled).active{background:' + color + '!important;border-color:' + color + '!important;color:#fff!important}',
          'body .btn-info{background:' + color + '!important;background-image:none!important;border-color:' + d + '!important}',
          'body .btn-info:hover,body .btn-info:focus,body .btn-info:not(:disabled):not(.disabled):active{background:' + d + '!important;background-image:none!important;border-color:' + d + '!important}',
          'body .badge-primary{background:' + color + '!important;background-image:none!important}',
          // themes/pos.css sets `.bg-primary{background:linear-gradient(...)!important}` — a
          // background-color-only override leaves that gradient IMAGE on top, so clear it too.
          'body .bg-primary{background:' + color + '!important;background-image:none!important}',
          'body .text-primary{color:' + color + '!important}',
          'body .btn-link{color:' + color + '!important}',
          // Pagination — plain .pagination (Bootstrap / global-custom.css / report-pagination).
          'body .pagination .page-item .page-link{color:' + color + '!important}',
          'body .pagination .page-item.active .page-link{background:' + color + '!important;background-image:none!important;border-color:' + color + '!important;color:#fff!important;box-shadow:0 4px 10px rgba(0,0,0,0.18)!important}',
          'body .pagination .page-item:not(.active):not(.disabled) .page-link:hover{background-color:' + l + '!important;border-color:' + color + '!important;color:' + d + '!important}',
          'body .pagination .page-item.disabled .page-link{color:#6c757d!important}',
          // .pagination-circle inside modals: themes/import_stock.css uses ".modal .pagination-circle ..." (5 classes) — need matching depth to win.
          'body .pagination-circle .page-item.active .page-link,body .modal .pagination-circle .page-item.active .page-link{background:' + color + '!important;background-image:none!important;border-color:' + color + '!important;color:#fff!important}',
          'body .pagination-circle .page-item .page-link:hover,body .modal .pagination-circle .page-item:not(.active):not(.disabled) .page-link:hover{background-color:' + l + '!important;border-color:' + color + '!important;color:' + d + '!important}',
          'body .custom-control-input:checked~.custom-control-label::before{background-color:' + color + '!important;border-color:' + color + '!important}',
          'body .nav-pills .nav-link.active{background-color:' + color + '!important}',
          'body .swal2-styled.swal2-confirm{background-color:' + color + '!important}'
        ];
        if (includeInline) {
          // NOTE: inline hard-coded #2c5aa0/#244886 gradients are handled by paintIframeInline()
          // (JS) not CSS — an [style*="#hex"] rule stops matching once we've rewritten the style,
          // which is exactly why a 2nd colour pick used to "stick".
          // POS scan box (.pos-search-wrapper / .pos-search-badge) is deliberately NOT themed —
          // it stays white whatever colour is picked (themes/pos/02-mobile-cart-search.css).
        }
        return rules.join('');
      }

      function injectStyle(doc, id, css) {
        if (!doc || !doc.head) return;
        var tag = doc.getElementById(id);
        if (!tag) {
          tag = doc.createElement('style');
          tag.id = id;
          doc.head.appendChild(tag);
        }
        tag.textContent = css;
      }

      function removeStyle(doc, id) {
        if (!doc) return;
        var tag = doc.getElementById(id);
        if (tag) tag.remove();
      }

      function iframeDoc() {
        try {
          var f = document.querySelector('iframe[name="frame"]');
          return f ? (f.contentDocument || f.contentWindow.document) : null;
        } catch (e) { return null; }
      }

      // Some page elements (modal headers, "add" buttons, section bars) hard-code
      // `style="background: linear-gradient(135deg,#2c5aa0,#244886) !important"` inline.
      // Inline !important can only be beaten by JS setting the property directly.
      // We tag each one with .pos-inline-themed on first touch so a 2nd/3rd colour pick
      // still finds it — after the first rewrite its style no longer contains the old hex.
      function paintIframeInline(color) {
        var idoc = iframeDoc();
        if (!idoc || !idoc.body) return;
        var l = shade(color, 0.20), d = shade(color, -0.18);
        var grad = 'linear-gradient(135deg, ' + l + ', ' + color + ')';
        var BRAND = /#2c5aa0|#244886/i;
        idoc.querySelectorAll('[style*="#2c5aa0"],[style*="#244886"],.pos-inline-themed').forEach(function(el) {
          // Form fields keep a neutral fill — only their border may follow the theme.
          var isField = (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT');
          if (!el.classList.contains('pos-inline-themed')) {
            var s = el.getAttribute('style') || '';
            // Only recolour a property whose VALUE actually carries the brand hex — a
            // white `background:#fff` sitting next to a `border:...#244886` must stay white.
            var hasBg = false, hasBorder = false;
            s.split(';').forEach(function(decl) {
              var i = decl.indexOf(':');
              if (i < 0 || !BRAND.test(decl.slice(i + 1))) return;
              var prop = decl.slice(0, i).trim().toLowerCase();
              if (prop === 'background' || prop === 'background-color' || prop === 'background-image') hasBg = true;
              else if (prop === 'border' || prop === 'border-color') hasBorder = true;
            });
            if (isField) hasBg = false;
            if (!hasBg && !hasBorder) return;
            el.classList.add('pos-inline-themed');
            el.dataset.posBg = hasBg ? '1' : '';
            el.dataset.posBorder = hasBorder ? '1' : '';
          }
          if (el.dataset.posBg) el.style.setProperty('background', grad, 'important');
          if (el.dataset.posBorder) el.style.setProperty('border-color', d, 'important');
        });
      }

      function paint(color) {
        var nav = document.querySelector('.main-header.navbar');
        if (nav) {
          nav.setAttribute('data-theme-bg', color);
          nav.style.setProperty('background-color', color, 'important');
        }
        var side = document.querySelector('.main-sidebar');
        if (side) side.style.setProperty('background-color', color, 'important');
        document.querySelectorAll('.main-sidebar .brand-link, .main-sidebar .sidebar-custom-footer').forEach(function(el) {
          el.style.setProperty('background-color', color, 'important');
        });
        // The .wrapper::before "shield" (fixed block behind the logo that hides the white
        // gap while the sidebar slides) is a pseudo-element — recolour it via an injected
        // rule so it stays invisible against the theme instead of flashing the old blue
        // during the collapse/expand animation.
        injectStyle(document, 'posThemeShieldStyle', '.wrapper::before{background:' + color + ' !important;background-color:' + color + ' !important}');
        // Flat-fill the navbar notification dropdown headers so they track the theme too.
        document.querySelectorAll('.main-header .dropdown-header').forEach(function(el) {
          el.style.setProperty('background-color', color, 'important');
        });
        paintActiveHighlight(color);
        injectStyle(document, 'posThemeBtnStyle', buttonCSS(color, false));
        injectStyle(iframeDoc(), 'posThemeBtnStyle', buttonCSS(color, true));
        paintIframeInline(color);
        // POS page open: navbar back to transparent, new colour into the POS band (js_pos_navbar.php)
        if (window.refreshPosNavMode) window.refreshPosNavMode();
        document.querySelectorAll('.pos-theme-swatch').forEach(function(el) {
          el.classList.toggle('active', (el.getAttribute('data-color') || '').toLowerCase() === color.toLowerCase());
        });
      }

      var currentColor = DEFAULT_COLOR;

      function applyThemeColor(color, save) {
        currentColor = color;
        paint(color);
        if (save) { try { localStorage.setItem(STORE_KEY, color); } catch (e) {} }
      }

      // Re-inject the button theme into every iframe page as it loads.
      function reinjectIframe() {
        if (currentColor && currentColor !== DEFAULT_COLOR) {
          injectStyle(iframeDoc(), 'posThemeBtnStyle', buttonCSS(currentColor, true));
          paintIframeInline(currentColor);
        }
      }
      // ໃຫ້ໜ້າໃນ iframe ເອີ້ນໃຊ້ໄດ້ຈາກ <head> ຂອງມັນເອງ (layouts/header.php) — ໃສ່ສີທີມກ່ອນໜ້າຈະສະແດງ,
      // ບໍ່ດັ່ງນັ້ນ ຣີເຟຣດແລ້ວປຸ່ມຈະເປັນສີເລີ່ມຕົ້ນ (ຟ້າ) ໄລຍະໜຶ່ງ ກ່ອນ load event ຈຶ່ງປ່ຽນເປັນສີທີ່ເລືອກ.
      window.posThemeButtonCSS = function(color) { return buttonCSS(color, true); };
      window.posThemeReinject = reinjectIframe;

      if (window.jQuery) {
        jQuery(function($) {
          $('iframe[name="frame"]').on('load', reinjectIframe);
          reinjectIframe();               // in case the frame already finished loading
          setTimeout(reinjectIframe, 500); // and once more after first paint settles
        });
      }

      // Apply saved choice immediately (elements already exist at this point).
      var saved = null;
      try { saved = localStorage.getItem(STORE_KEY); } catch (e) {}
      if (saved) applyThemeColor(saved, false);

      document.addEventListener('DOMContentLoaded', function() {
        if (saved) applyThemeColor(saved, false);
        document.querySelectorAll('.pos-theme-swatch').forEach(function(el) {
          el.addEventListener('click', function() {
            applyThemeColor(this.getAttribute('data-color'), true);
          });
        });
        var resetBtn = document.getElementById('posThemeReset');
        if (resetBtn) {
          resetBtn.addEventListener('click', function() {
            try { localStorage.removeItem(STORE_KEY); } catch (e) {}
            currentColor = DEFAULT_COLOR;
            applyThemeColor(DEFAULT_COLOR, false);
            // Drop overrides so the stylesheets' original blues return.
            removeStyle(document, 'posThemeActiveStyle');
            removeStyle(document, 'posThemeBtnStyle');
            removeStyle(document, 'posThemeShieldStyle');
            removeStyle(iframeDoc(), 'posThemeBtnStyle');
          });
        }
      });
    })();
  </script>

  <script>
    (function() {
      var defaultSrc = '<?php echo $default_iframe_src; ?>';
      var hasDashboardPerm = <?php echo hasPermission('dashboard') ? 'true' : 'false'; ?>;
      var savedSrc = sessionStorage.getItem('StoreMini.currentIframeSrc');
      
      // If user does not have dashboard permission and savedSrc is home.php, clear it
      if (!hasDashboardPerm && (!savedSrc || savedSrc === 'home.php')) {
        savedSrc = defaultSrc;
        sessionStorage.setItem('StoreMini.currentIframeSrc', defaultSrc);
      }
      
      var targetSrc = savedSrc || defaultSrc;
      if (targetSrc) {
        var links = document.querySelectorAll('.nav-sidebar a.nav-link');
        for (var i = 0; i < links.length; i++) {
          links[i].classList.remove('active');
        }
        for (var i = 0; i < links.length; i++) {
          var href = links[i].getAttribute('href');
          if (href && href !== '#' && (href === targetSrc || targetSrc.endsWith(href))) {
            links[i].classList.add('active');
            var treeview = links[i].closest('.nav-treeview');
            if (treeview) {
              var parentLiGroup = treeview.closest('.nav-item');
              if (parentLiGroup) {
                parentLiGroup.classList.add('menu-open');
              }
            }
            break;
          }
        }
      }
    })();
  </script>

