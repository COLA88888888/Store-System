<?php
/*
 * ສ່ວນ: JS: ພຶດຕິກຳຂອງ layout
 * ເປີດ/ປິດເມນູຂ້າງ, ໄຮໄລທ໌ເມນູທີ່ເລືອກ, ສົ່ງການສະແກນບາໂຄ້ດເຂົ້າໜ້າຂາຍ, ເມນູແບບ accordion,
 * ປິດເມນູເທິງມືຖື, ຢືນຢັນອອກຈາກລະບົບ ແລະ ອອກອັດຕະໂນມັດເມື່ອບໍ່ເຄື່ອນໄຫວ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ home/dashboard.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><script>
  $(function() {
    var posUserToggledManual = false;

    // Handle manual Hamburger PushMenu toggle click
    $(document).on('click', '#topNavbarPushMenuBtn, [data-widget="pushmenu"]', function(e) {
      e.preventDefault();
      e.stopPropagation();

      posUserToggledManual = true;

      var isMobile = window.innerWidth < 768;
      var isCollapsed = $('body').hasClass('sidebar-collapse') || $('html').hasClass('sidebar-collapse');
      
      if (isMobile) {
        if ($('body').hasClass('sidebar-open')) {
          $('html, body').removeClass('sidebar-open sidebar-is-opening');
        } else {
          $('html, body').addClass('sidebar-open').removeClass('sidebar-collapse sidebar-closed');
        }
      } else {
        if (isCollapsed) {
          $('html, body').removeClass('sidebar-collapse sidebar-closed');
        } else {
          $('html, body').addClass('sidebar-collapse');
        }
      }
    });

    // Disable hover expansion on sidebar completely
    $('.main-sidebar, .brand-link').off('mouseenter mouseleave mouseover mouseout');
    $(document).off('mouseenter mouseleave mouseover mouseout', '.main-sidebar, .brand-link');

    // Restore sidebar scroll position if available
    var sidebarEl = document.querySelector('.sidebar');
    if (sidebarEl && window.jQuery && jQuery.fn.overlayScrollbars) {
      var os = $(sidebarEl).overlayScrollbars();
      if (os && window.innerWidth <= 991) {
        // overlayScrollbars caches its scroll height from when the sidebar was still a
        // hidden/collapsed off-canvas drawer, so on mobile the bottom of the menu can end
        // up unreachable and scrolling feels like it jumps — fall back to native scrolling.
        os.destroy();
        sidebarEl.style.overflowY = 'auto';
      } else {
        var savedScroll = sessionStorage.getItem('StoreMini.sidebarScrollTop');
        if (os && savedScroll) {
          os.scroll({ y: parseInt(savedScroll, 10) }, 0);
        }
      }
    }

    var loaderSafetyTimer = null; 
    var slowNetHintTimer = null;

    function showFastLoader() {
      clearTimeout(loaderSafetyTimer);
      clearTimeout(slowNetHintTimer);
      document.body.classList.add('is-loading');
      if (typeof window.refreshPosNavMode === 'function') window.refreshPosNavMode();
      $('#systemLoaderSlowHint').removeClass('show');
      $('#iframeLoaderOverlay').stop(true, true).css({ opacity: 1 }).show();

      // ຖ້າເນັດຊ້າເກີນ 2.2 ວິນາທີ ໃຫ້ໂຊຄຳແນະນຳ
      slowNetHintTimer = setTimeout(function() {
        $('#systemLoaderSlowHint').addClass('show');
      }, 2200);

      // Failsafe 25 ວິນາທີ ບໍ່ໃຫ້ຕັດໄວເກີນໄປ ເພື່ອໃຫ້ໂຫຼດຕາມອິນເຕີເນັດຕົວຈິງ
      loaderSafetyTimer = setTimeout(function() {
        hideFastLoader();
      }, 25000);
    }

    function hideFastLoader() {
      clearTimeout(loaderSafetyTimer);
      clearTimeout(slowNetHintTimer);
      $('#systemLoaderSlowHint').removeClass('show');
      $('#iframeLoaderOverlay').stop(true, true).fadeOut(220, function() {
        document.body.classList.remove('is-loading');
        if (typeof window.refreshPosNavMode === 'function') window.refreshPosNavMode();
      });
    }

    // ===== Exclusive Active Menu Highlight =====
    var $sidebar = $('.nav-sidebar');
    
    // Highlight initial active menu item on DOM ready
    var currentSrc = sessionStorage.getItem('StoreMini.currentIframeSrc') || $('iframe[name="frame"]').attr('src');
    if (currentSrc) {
      var normSrc = currentSrc.replace(/\\/g, '/');
      $('.nav-sidebar .nav-link').each(function() {
        var href = $(this).attr('href') || $(this).attr('data-href');
        if (!href || href === '#' || href === 'javascript:void(0);') return;
        var normHref = href.replace(/\\/g, '/');
        if (normHref === normSrc || normSrc.endsWith(normHref) || normHref.endsWith(normSrc)) {
          $('.nav-sidebar .nav-link').removeClass('active');
          $(this).addClass('active');
          var $tree = $(this).closest('.has-treeview');
          if ($tree.length) {
            $tree.addClass('menu-open');
            $tree.children('.nav-treeview').show();
          }
          return false;
        }
      });
    }

    // ປ້ອງກັນບໍ່ໃຫ້ Browser ສະແດງ URL tooltip (status bubble) ຢູ່ແຈລຸ່ມຊ້າຍ ເຊິ່ງຈະໄປບັງ "Wlaodev Pos System V 3.8.26"
    // ຍ້າຍ href → data-href ເທື່ອດຽວຕອນໂຫຼດ ແລະ ບໍ່ໃສ່ຄືນ: ເມື່ອກ່ອນສະຫຼັບໄປມາທຸກເທື່ອ mouseenter/mouseleave
    // ເຮັດໃຫ້ browser ຄິດສະໄຕລ໌ລິ້ງໃໝ່ (:link ↔ ບໍ່ແມ່ນລິ້ງ) ຕົວໜັງສືເມນູຈຶ່ງກະພິບ. ເມນູແມ່ (dropdown) ບໍ່ມີ href
    // ຈຶ່ງນິ່ງມາຕະຫຼອດ. ການກົດທຸກບ່ອນອ່ານ attr('href') || attr('data-href') ຢູ່ແລ້ວ.
    function stripSidebarHref(el) {
      var h = $(el).attr('href');
      if (h) {
        $(el).attr('data-href', h).removeAttr('href').css('cursor', 'pointer');
      }
    }
    $('.main-sidebar a[href]').each(function() { stripSidebarHref(this); });
    // ລິ້ງທີ່ເພີ່ມເຂົ້າມາພາຍຫຼັງ
    $(document).on('mouseenter', '.main-sidebar a[href]', function() { stripSidebarHref(this); });

    $sidebar.on('click', '.nav-link', function(e) {
      var href = $(this).attr('href') || $(this).attr('data-href');
      
      if (!href || href === '#' || href === 'javascript:void(0);' || $(this).closest('.nav-item').hasClass('has-treeview')) {
        return;
      }

      e.preventDefault();

      // Reset manual toggle flag on new menu navigation
      posUserToggledManual = false;

      // Show high-speed 12-dot pulse loader overlay on menu click
      showFastLoader();

      // REALTIME: Highlight clicked menu item immediately with 0ms delay!
      $('.nav-sidebar .nav-link').removeClass('active');
      $(this).addClass('active');

      // Keep parent treeview dropdown open & expanded if inside a dropdown
      var $parentTree = $(this).closest('.has-treeview');
      if ($parentTree.length) {
        $parentTree.addClass('menu-open');
        $parentTree.children('.nav-treeview').show();
      }

      sessionStorage.setItem('StoreMini.currentIframeSrc', href);
      $('iframe[name="frame"]').attr('src', href);

      if (href && (href.indexOf('pos.php') !== -1 || href.indexOf('/pos/') !== -1)) {
        $('html, body').addClass('sidebar-collapse').removeClass('sidebar-closed sidebar-open');
      } else {
        $('html, body').removeClass('sidebar-collapse sidebar-closed sidebar-open');
      }

      if (sidebarEl && window.jQuery && jQuery.fn.overlayScrollbars) {
        var osInstance = $(sidebarEl).overlayScrollbars();
        if (osInstance && typeof osInstance.scroll === 'function') {
          var scrollObj = osInstance.scroll();
          var scrollTop = 0;
          if (scrollObj) {
            if (scrollObj.position && typeof scrollObj.position.y !== 'undefined') {
              scrollTop = scrollObj.position.y;
            } else if (typeof scrollObj.y !== 'undefined') {
              scrollTop = scrollObj.y;
            }
          }
          sessionStorage.setItem('StoreMini.sidebarScrollTop', scrollTop);
        }
      }
    });

    $(document).on('click', 'a.brand-link', function(e) {
      var href = $(this).attr('href') || $(this).attr('data-href');
      if (href && href !== '#' && href !== 'javascript:void(0);') {
        e.preventDefault();
        showFastLoader();
        sessionStorage.setItem('StoreMini.currentIframeSrc', href);
        $('iframe[name="frame"]').attr('src', href);
      }
    });

    // ຖ້າໂຟກັສຍັງຄ້າງຢູ່ shell (ເມນູ/ແຖບເທິງ) ຕອນເປີດໜ້າຂາຍ, ສົ່ງການພິມຂອງເຄື່ອງສະແກນ
    // ຕໍ່ເຂົ້າຊ່ອງສະແກນໃນ iframe — ບໍ່ຕ້ອງຄລິກທີ່ຊ່ອງກ່ອນ
    $(document).on('keydown.posScanForward', function(e) {
      if (e.ctrlKey || e.altKey || e.metaKey) return;
      var ae = document.activeElement;
      if (ae && (ae.tagName === 'INPUT' || ae.tagName === 'TEXTAREA' || ae.tagName === 'SELECT' || ae.isContentEditable)) return;
      if ($('.modal.show, .swal2-container').length) return;
      try {
        var f = document.querySelector('iframe[name="frame"]');
        var w = f && f.contentWindow;
        var input = w && w.document.getElementById('barcodeInput');
        if (!input) return;
        if (e.key === 'Enter') {
          e.preventDefault();
          if (typeof w.handleBarcodeSubmit === 'function') w.handleBarcodeSubmit();
        } else if (e.key && e.key.length === 1) {
          e.preventDefault();
          input.value += e.key;
          if (typeof w.filterGrid === 'function') w.filterGrid();
        } else {
          return;
        }
        w.focus();
        input.setAttribute('inputmode', 'none');
        input.focus({ preventScroll: true });
      } catch (err) {}
    });

    $('iframe[name="frame"]').on('load', function() {
      // Hide high-speed 12-dot pulse loader overlay smoothly on page load
      hideFastLoader();

      // Bind click/touchstart event inside iframe to close sidebar
      try {
        var iframeWin = this.contentWindow;
        var iframeDoc = this.contentDocument || iframeWin.document;
        if (iframeDoc && window.jQuery) {
          $(iframeDoc).off('click.audioUnlock touchstart.audioUnlock').on('click.audioUnlock touchstart.audioUnlock', function() {
            if (window.unlockAudioContext) window.unlockAudioContext();
          });
          $(iframeDoc).off('click.notifClose touchstart.notifClose').on('click.notifClose touchstart.notifClose', function() {
            $('.dropdown-menu.show').removeClass('show');
            $('.nav-item.dropdown.show').removeClass('show');
          });
          $(iframeDoc).off('click.sidebarClose touchstart.sidebarClose').on('click.sidebarClose touchstart.sidebarClose', function() {
            if (window.innerWidth <= 992) {
              var isSidebarOpen = $('body').hasClass('sidebar-open') || $('html').hasClass('sidebar-open');
              if (isSidebarOpen) {
                $('#topNavbarPushMenuBtn').click();
              }
            }
          });

          // Count activity inside the iframe as activity — otherwise a user filling a long
          // form gets force-logged-out after 15 min even while working. Throttled to 1×/20s.
          var _idlePing = 0;
          $(iframeDoc).off('mousemove.idle keydown.idle click.idle scroll.idle touchstart.idle wheel.idle')
            .on('mousemove.idle keydown.idle click.idle scroll.idle touchstart.idle wheel.idle', function() {
              var now = Date.now();
              if (now - _idlePing > 20000) {
                _idlePing = now;
                if (window.resetIdleTimer) window.resetIdleTimer();
              }
            });
        }
      } catch(e) {
        // Suppress cross-origin warnings if any
      }

      try {
        var path = this.contentWindow.location.pathname;
        var search = this.contentWindow.location.search || '';
        var fullTarget = path.substring(path.lastIndexOf('/') + 1) + search;
        var page = path.substring(path.lastIndexOf('/') + 1);

        var isPosPage = (path.indexOf('pos.php') !== -1 || path.indexOf('/pos/') !== -1);

        if (isPosPage) {
          // ເຂົ້າໜ້າຂາຍແລ້ວສະແກນໄດ້ທັນທີ: ຍ້າຍໂຟກັສຈາກເມນູ (shell) ເຂົ້າຊ່ອງສະແກນໃນ iframe
          try {
            var _posWin = this.contentWindow;
            var _posInput = _posWin.document.getElementById('barcodeInput');
            if (_posInput) {
              if (document.activeElement && document.activeElement !== this) document.activeElement.blur();
              _posWin.focus();
              _posInput.setAttribute('inputmode', 'none');
              _posInput.focus({ preventScroll: true });
            }
          } catch (e) {}

          // Auto-collapse on POS page initial load unless user manually toggled hamburger
          if (!posUserToggledManual) {
            $('html, body').addClass('sidebar-collapse').removeClass('sidebar-closed sidebar-open');
          }
        } else {
          // Auto-expand sidebar for ALL other pages
          posUserToggledManual = false;
          $('html, body').removeClass('sidebar-collapse sidebar-closed sidebar-open');
        }

        if (page && page !== 'blank') {
          var normPath = path.replace(/\\/g, '/');
          var frameParams = new URLSearchParams(search);

          // ຄະແນນຂອງລິ້ງເມນູ ທຽບກັບໜ້າທີ່ໂຫຼດ: 0 = ບໍ່ກົງ, 1 = ໄຟລ໌ກົງ, 2 = ໄຟລ໌ + query ຂອງລິ້ງກົງ.
          // ເມນູທີ່ເປີດໄຟລ໌ດຽວກັນແຕ່ຕ່າງ query (ນັບສະຕັອກ = stock_check.php, ປະຫວັດ = stock_check.php?view=checks)
          // ຈຶ່ງໄຮໄລ້ຖືກອັນ. data-active-when="view=checks|from=history" = ເງື່ອນໄຂ query ອື່ນທີ່ນັບວ່າກົງ (ອັນໃດອັນໜຶ່ງ).
          function linkScore($a) {
            var href = $a.attr('href') || $a.attr('data-href');
            if (!href || href === '#' || href.indexOf('javascript:') === 0) return 0;
            var parts = href.replace(/\\/g, '/').split('?');
            var hTail = parts[0].replace(/^(\.\.?\/)+/, '');
            var hDir = /\/index\.php$/.test(hTail) ? hTail.replace(/index\.php$/, '') : null;   // .../stores/index.php ≡ .../stores/
            if (!hTail || !(normPath === parts[0] || normPath.endsWith('/' + hTail) || (hDir && normPath.endsWith('/' + hDir)))) return 0;
            var conds = $a.attr('data-active-when') || parts[1] || '';
            if (!conds) return 1;
            var any = conds.split('|').some(function(cond) {
              var ok = true;
              new URLSearchParams(cond).forEach(function(v, k) { if (frameParams.get(k) !== v) ok = false; });
              return ok;
            });
            return any ? 2 : 0;
          }

          var $best = null, bestScore = 0;
          $('.nav-sidebar .nav-link').each(function() {
            var s = linkScore($(this));
            if (s > bestScore) { bestScore = s; $best = $(this); }
          });

          // ເມນູທີ່ໄຮໄລ້ຢູ່ ກົງດີເທົ່າກັບອັນທີ່ດີທີ່ສຸດແລ້ວ → ບໍ່ຍ້າຍ (ເຊັ່ນ ເມນູ 2 ອັນທີ່ໄປໜ້າດຽວກັນ)
          var $currentActive = $('.nav-sidebar .nav-link.active[target="frame"]');
          var alreadyMatches = bestScore > 0 && $currentActive.length && linkScore($currentActive.first()) >= bestScore;

          if (!alreadyMatches) {
            var matched = false;

            if ($best) {
              $('.nav-sidebar .nav-link').removeClass('active');
              $best.addClass('active');

              var $bestTree = $best.closest('.has-treeview');
              if ($bestTree.length) {
                $bestTree.addClass('menu-open');
                $bestTree.children('.nav-treeview').show();
              }

              sessionStorage.setItem('StoreMini.currentIframeSrc', $best.attr('href') || $best.attr('data-href'));
              matched = true;
            }

            if (!matched) {
              // Fallback: match by filename only (no cross-folder matching)
              $('.nav-sidebar .nav-link').each(function() {
                var href = $(this).attr('href') || $(this).attr('data-href');
                if (!href || href === '#' || href === 'javascript:void(0);') return;
                var hrefNorm = href.replace(/\\/g, '/');
                var hrefPage = hrefNorm.substring(hrefNorm.lastIndexOf('/') + 1);
                if (hrefPage && hrefPage !== 'index.php' && hrefPage === page) {
                  $('.nav-sidebar .nav-link').removeClass('active');
                  $(this).addClass('active');

                  var $tree = $(this).closest('.has-treeview');
                  if ($tree.length) {
                    $tree.addClass('menu-open');
                    $tree.children('.nav-treeview').show();
                  }

                  sessionStorage.setItem('StoreMini.currentIframeSrc', href);
                  return false;
                }
              });
            }
          }
        }
      } catch(e) {}
    });

    // ===== ແຕະເທື່ອດຽວ ເປີດເມນູໄດ້ເລີຍ (ໂທລະສັບ / iPhone) =====
    // Safari ເທິງ iPhone ໃຊ້ການແຕະທຳອິດເປັນ "hover" ກ່ອນ, ຈຶ່ງຕ້ອງແຕະ 2 ເທື່ອ ກວ່າເມນູຈະເປີດ.
    // ແຕະສັ້ນໆ (ນິ້ວບໍ່ເລື່ອນ) ເທິງລິ້ງເມນູ → ກົດໃຫ້ທັນທີ ແລະ ຢຸດ hover/click ຈຳລອງຂອງ browser.
    // ລາກນິ້ວເພື່ອເລື່ອນເມນູ ຍັງເລື່ອນໄດ້ປົກກະຕິ (ບໍ່ນັບເປັນການແຕະ).
    var menuTap = null;
    $(document).on('touchstart', '.main-sidebar a.nav-link', function(e) {
      var t = e.originalEvent.touches && e.originalEvent.touches[0];
      menuTap = (t && e.originalEvent.touches.length === 1)
        ? { el: this, x: t.clientX, y: t.clientY, time: Date.now() } : null;
    });
    $(document).on('touchmove', '.main-sidebar a.nav-link', function(e) {
      if (!menuTap) return;
      var t = e.originalEvent.touches && e.originalEvent.touches[0];
      if (!t || Math.abs(t.clientX - menuTap.x) > 10 || Math.abs(t.clientY - menuTap.y) > 10) menuTap = null;
    });
    $(document).on('touchend', '.main-sidebar a.nav-link', function(e) {
      var tap = menuTap;
      menuTap = null;
      if (!tap || tap.el !== this || Date.now() - tap.time > 700) return;
      e.preventDefault();   // ບໍ່ໃຫ້ browser ສົ່ງ hover + click ຈຳລອງຕາມມາ (ຈະກົດຊ້ຳ)
      this.click();         // ເອີ້ນ click handler ທັງໝົດ ແລະ ເປີດລິ້ງ (target="frame") ຄືກົດປົກກະຕິ
    });
    $(document).on('touchcancel', '.main-sidebar a.nav-link', function() { menuTap = null; });

    // ===== Universal Accordion Dropdown Toggle (Only 1 dropdown open at a time) =====
    $(document).on('click', '.nav-sidebar .has-treeview > .nav-link', function(e) {
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();

      var $parent = $(this).closest('.has-treeview');
      var $tree = $parent.children('.nav-treeview');
      var isOpen = $parent.hasClass('menu-open');

      // Accordion: close ONLY the other menus that are actually open. Iterating every
      // .has-treeview and .stop(true,false)-ing closed ones left in-flight slides frozen
      // mid-height, which nudged the whole sidebar (and every chevron) sideways.
      $('.nav-sidebar .has-treeview.menu-open, .nav-sidebar .has-treeview.menu-is-opening').not($parent).each(function() {
        var $otherParent = $(this);
        // Drop the classes now so the chevron rotates back in step with the slide,
        // not 300ms later in a callback.
        $otherParent.removeClass('menu-open menu-is-opening');
        $otherParent.children('.nav-treeview').stop(true, true).slideUp(300);
      });

      // Toggle current menu — same 300ms both ways, and finish (not freeze) any running anim
      if (isOpen) {
        $parent.removeClass('menu-open menu-is-opening');
        $tree.stop(true, true).slideUp(300);
      } else {
        $parent.addClass('menu-is-opening');
        $tree.stop(true, true).slideDown(300, function() {
          $parent.addClass('menu-open').removeClass('menu-is-opening');
        });
      }
    });

    // PushMenu event sync
    $(document).on('collapsed.lte.pushmenu', function() {
      $('html, body').addClass('sidebar-collapse');
    });
    $(document).on('shown.lte.pushmenu', function() {
      $('html, body').removeClass('sidebar-collapse');
    });

    // ===== Auto-close sidebar on mobile when menu item clicked =====
    // ເຊື່ອງ sidebar ອັດຕະໂນມັດ ເມື່ອກົດ menu ໃນໜ້າຈໍ mobile (≤992px)
    $(document).on('click', '.nav-sidebar .nav-link', function() {
      var isMobile = window.innerWidth <= 992;
      var href = $(this).attr('href') || $(this).attr('data-href');
      var isLeafItem = href && href !== '#' && href !== 'javascript:void(0);' && !$(this).closest('.nav-item').hasClass('has-treeview');

      if (isMobile && isLeafItem) {
        $('body').removeClass('sidebar-open sidebar-is-opening');
        if (window.jQuery && $.fn.PushMenu) {
          try { $('[data-widget="pushmenu"]').PushMenu('collapse'); } catch(err) {}
          try { $('[data-widget="pushmenu"]').PushMenu('close'); } catch(err) {}
        }
      }
    });

    // ===== Close Sidebar on Mobile when clicking/tapping on blank area outside sidebar =====
    // ກົດ ຫຼື ແຕະບ່ອນຫວ່າງເປົ່າ (Outside Sidebar) ເພື່ອເຊື່ອງ sidebar ໃນໜ້າຈໍ mobile (≤992px)
    $(document).on('click touchstart', function(e) {
      if (window.innerWidth <= 992) {
        var $target = $(e.target);
        var isSidebarOpen = $('body').hasClass('sidebar-open') || $('html').hasClass('sidebar-open');
        
        if (isSidebarOpen) {
          var isInsideSidebar = $target.closest('.main-sidebar').length > 0;
          var isPushMenuBtn = $target.closest('#topNavbarPushMenuBtn').length > 0 || $target.closest('[data-widget="pushmenu"]').length > 0;
          
          if (!isInsideSidebar && !isPushMenuBtn) {
            $('#topNavbarPushMenuBtn').click();
          }
        }
      }
    });

  });

  // ກົດອອກຈາກລະບົບ: ກວດກ່ອນວ່າກະຍັງເປີດຢູ່ບໍ່ (api/shifts_backend.php can_logout). ພະນັກງານຂາຍຕ້ອງກົດປິດກະເອງ
  // ກ່ອນຈຶ່ງອອກໄດ້ — ລາຍງານຕາມກະຈຶ່ງຖືກຕ້ອງ. ເຊີເວີກໍກັນຊ້ຳ (auth/logout.php). ກວດບໍ່ໄດ້ (ເນັດຂັດ) → ຖາມອອກຕາມປົກກະຕິ.
  function confirmLogout() {
    $.ajax({ url: '../api/shifts_backend.php', data: { action: 'can_logout' }, dataType: 'json', cache: false, timeout: 6000 })
      .done(function (r) {
        if (r && r.success && r.allowed === false) showLogoutBlocked(r);
        else askLogoutConfirm();
      })
      .fail(askLogoutConfirm);
  }

  function showLogoutBlocked(r) {
    var L = I18N_LAYOUT;
    var esc = function (s) { return $('<div>').text(s || '').html(); };
    var shiftLine = r.shift_name ? '<div class="mt-2" style="font-size:0.85rem; color:#64748b;"><i class="fas fa-user-clock mr-1"></i>' + esc(r.shift_name) + (r.end_time ? ' · ' + esc(r.end_time) : '') + '</div>' : '';
    if (r.before_end) {
      Swal.fire({
        icon: 'warning',
        title: L['layout.logout_blocked_title'],
        html: '<div style="font-size:0.92rem; color:#475569;">' + L['layout.logout_blocked_before'].replace('%s', '<b>' + esc(r.end_time) + '</b>') + '</div>' + shiftLine,
        confirmButtonColor: '#2563eb',
        confirmButtonText: L['layout.ok'],
        heightAuto: false
      });
      return;
    }
    Swal.fire({
      icon: 'warning',
      title: L['layout.logout_blocked_title'],
      html: '<div style="font-size:0.92rem; color:#475569;">' + L['layout.logout_blocked_after'] + '</div>' + shiftLine,
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fas fa-cash-register mr-1"></i> ' + L['layout.close_shift_now'],
      cancelButtonText: L['layout.cancel'],
      heightAuto: false
    }).then(function (res) {
      if (!res.isConfirmed) return;
      // ໜ້າຂາຍເປີດຟອມປິດກະເອງເມື່ອມີ ?close_shift=1 (pages/pos/partials/js/pos_shift_js.php)
      var src = '../pages/pos/pos.php?close_shift=1';
      try { sessionStorage.setItem('StoreMini.currentIframeSrc', '../pages/pos/pos.php'); } catch (e) {}
      var f = document.querySelector('iframe[name="frame"]');
      if (f) f.src = src; else window.location.href = src;
    });
  }

  // ເຊີເວີກັນການອອກໄວ້ (auth/logout.php ສົ່ງກັບມາບ່ອນນີ້) → ສະແດງແຈ້ງເຕືອນ
  $(function () {
    try {
      if (sessionStorage.getItem('StoreMini.logout_blocked')) {
        sessionStorage.removeItem('StoreMini.logout_blocked');
        setTimeout(confirmLogout, 600);
      }
    } catch (e) {}
  });

  function askLogoutConfirm() {
    Swal.fire({
      title: '<span style="font-size:1.15rem; font-weight:700; color:#1e293b;">' + I18N_LAYOUT['layout.logout_confirm_title'] + '</span>',
      html: '<div style="font-size:0.90rem; color:#475569; font-weight:600; margin-top:4px;">' + I18N_LAYOUT['layout.logout_confirm_text'] + '</div>',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#ef4444',
      cancelButtonColor: '#64748b',
      confirmButtonText: '<i class="fas fa-power-off mr-1"></i> ' + I18N_LAYOUT['layout.logout'],
      cancelButtonText: I18N_LAYOUT['layout.cancel'],
      heightAuto: false
    }).then(function(result) {
      if (result.isConfirmed) {
        sessionStorage.clear();
        window.location.href = '../auth/logout.php';
      }
    });
  }

  // ===== Client-Side Auto Idle Inactivity Timeout (15 ນາທີ = 900,000ms) =====
  (function() {
    var idleTime = 0;
    var maxIdleTime = 15 * 60 * 1000; // 15 ນາທີ
    var lastPingTime = Date.now();

    function resetIdleTimer() {
      idleTime = 0;
      // ຖ້າຜູ້ໃຊ້ນຳໃຊ້ຢູ່ — ສົ່ງ Heartbeat ໄປອັບເດດ Session ຢູ່ Server ເປັນໄລຍະ (ທຸກໆ 2 ນາທີ)
      if (Date.now() - lastPingTime > 120000) {
        lastPingTime = Date.now();
        fetch('../config/db.php', { method: 'HEAD' }).catch(function(){});
      }
    }

    // Reset idle timer ຢ່າງຕໍ່ເນື່ອງເມື່ອມີການເຄື່ອນໄຫວ ຫຼື ນຳໃຊ້ລະບົບ
    window.onload = resetIdleTimer;
    window.onmousemove = resetIdleTimer;
    window.onmousedown = resetIdleTimer;
    window.ontouchstart = resetIdleTimer;
    window.onclick = resetIdleTimer;
    window.onkeydown = resetIdleTimer;
    window.onscroll = resetIdleTimer;
    window.addEventListener('scroll', resetIdleTimer, true);
    window.addEventListener('message', resetIdleTimer);

    // Activity INSIDE the iframe (typing in a form, clicking, scrolling a page) does not
    // bubble to this parent window — without this the user gets logged out mid-task after
    // 15 min even while actively working. The iframe-load handler calls this on iframe events.
    window.resetIdleTimer = resetIdleTimer;

    setInterval(function() {
      idleTime += 5000; // ກວດສອບທຸກໆ 5 ວິນາທີ
      if (idleTime >= maxIdleTime) {
        sessionStorage.clear();
        window.location.href = '../auth/logout.php?expired=1';
      }
    }, 5000);
  })();
</script>
