<?php
if (!isset($base_path)) {
    $base_path = '';
}
?>
    <script src="<?php echo $base_path; ?>plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
    <script src="<?php echo $base_path; ?>dist/js/adminlte.js"></script>
    <!-- Select2 JS -->
    <script src="<?php echo $base_path; ?>plugins/select2/js/select2.full.min.js"></script>
    <!-- Flatpickr Lao Datepicker JS -->
    <script src="<?php echo $base_path; ?>assets/js/flatpickr.min.js"></script>
    <!-- Fallback: the date picker normally loads in <head> (layouts/header.php); load it here if it did not -->
    <script>
      if (!window.miniposDatePicker) {
        if (typeof window.flatpickr !== 'function') document.write('<script src="<?php echo $base_path; ?>plugins/flatpickr/flatpickr.min.js"><\/script>');
        document.write('<script src="<?php echo $base_path; ?>plugins/flatpickr/minipos-datepicker.js?v=<?php echo @filemtime(__DIR__ . '/../plugins/flatpickr/minipos-datepicker.js'); ?>"><\/script>');
      }
    </script>

    <script>
      // Smart Preloader: Immediately cancel timer & hide preloader on fast page load
      function hidePreloader() {
          if (window._slowNetPreloaderTimer) clearTimeout(window._slowNetPreloaderTimer);
          var p = document.getElementById('global-preloader');
          if (p) {
              p.classList.remove('show-slow-net');
              p.style.display = 'none';
          }
      }
      hidePreloader();
      document.addEventListener('DOMContentLoaded', hidePreloader);
      window.addEventListener('load', hidePreloader);

      // ===== KEEP THE KIP SYMBOL (U+20AD) ON THE SAME LINE AS ITS AMOUNT (every page) =====
      // Currency prints all over as number_format(...) . ' K' with a normal, breakable space,
      // so a narrow column can drop the symbol to its own line. Replace the whitespace between
      // a digit and the kip sign with a non-breaking space, and keep doing it for content
      // rendered later (POS cart, AJAX tables, count-up animations, modals).
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

      document.addEventListener('DOMContentLoaded', function() {
          // ===== SIDEBAR SMART SCROLL =====
          var sidebarEl = document.querySelector('.main-sidebar .sidebar');
          if (sidebarEl) {
              if (typeof $ !== 'undefined' && $.fn.overlayScrollbars) {
                  try {
                      var osInstance = $(sidebarEl).overlayScrollbars();
                      if (osInstance) osInstance.destroy();
                  } catch(e) {}
              }

              function checkSidebarScroll() {
                  if (sidebarEl.scrollHeight > sidebarEl.clientHeight) {
                      sidebarEl.style.overflowY = 'auto';
                  } else {
                      sidebarEl.style.overflowY = 'hidden';
                  }
              }

              checkSidebarScroll();
              var observer = new MutationObserver(function() {
                  setTimeout(checkSidebarScroll, 200);
              });
              observer.observe(sidebarEl, { childList: true, subtree: true, attributes: true });

              window.addEventListener('resize', checkSidebarScroll);
          }
      });

      // ===== ກວດຈັບແປ້ນພິມບໍ່ແມ່ນພາສາອັງກິດ ເວລາສະແກນບາໂຄ້ດ ທຸກໜ້າ =====
      (function() {
          var _kbAlertLock = 0;
          // ຕົວອັກສອນແຖວຕົວເລກເທິງແປ້ນພິມລາວ/ໄທ (Digit0-Digit9, Minus) ເມື່ອເຄື່ອງສະແກນຍິງຕົວເລກແຕ່ແປ້ນພິມບໍ່ແມ່ນ ENG
          var _digitRowNonEnRe = /^[ຢຟໂຖຸູຄຕຈຂๅ\/\-ภถุึคตจ0-9\s]+$/;

          window.hasNonEnglishChars = function(str) {
              return /[^\x20-\x7E]/.test(String(str || ''));
          };

          window.isLikelyBarcodeNonEnglish = function(str) {
              str = String(str || '').trim();
              if (!str || !window.hasNonEnglishChars(str)) return false;
              // ຖ້າທຸກຕົວອັກສອນມາຈາກແຖວຕົວເລກ (Digit0-9) ຂອງແປ້ນພິມລາວ/ໄທ ຍາວ >= 3 ຕົວ → ແມ່ນການສະແກນບາໂຄ້ດແນ່ນອນ
              if (str.length >= 3 && _digitRowNonEnRe.test(str)) return true;
              return false;
          };

          window.showSwitchKeyboardAlert = function(badText, targetInput, onAfterClose) {
              var now = Date.now();
              if (window._kbAlertActive) return;
              if (now - _kbAlertLock < 600) return;
              _kbAlertLock = now;
              window._kbAlertActive = true;
              window._kbAlertOpenedAt = now;

              var cleanBad = String(badText || '').trim();
              var restoreEl = targetInput || document.activeElement;
              // ຖອນໂຟກັສອອກກ່ອນ ເພື່ອບໍ່ໃຫ້ເຄື່ອງສະແກນທີ່ກຳລັງຍິງຄ້າງ ຫຼື ກົດ Enter ປິດກ່ອງແຈ້ງເຕືອນເອງ
              if (document.activeElement && typeof document.activeElement.blur === 'function') {
                  try { document.activeElement.blur(); } catch (e) {}
              }

              if (typeof Swal !== 'undefined' && Swal.fire) {
                  var htmlMsg = '<div style="font-size:0.96rem;">' + <?php echo json_encode(t('layout.kb_switch_text'), JSON_UNESCAPED_UNICODE); ?> + '</div>';
                  if (cleanBad) {
                      var safeTxt = document.createElement('div');
                      safeTxt.textContent = cleanBad;
                      htmlMsg += '<div style="font-size:1.08rem; font-weight:700; color:#ef4444; margin:8px 0; word-break:break-all;">' + safeTxt.innerHTML + '</div>';
                  }
                  htmlMsg += '<small class="text-muted d-block mt-1">' + <?php echo json_encode(t('layout.kb_switch_hint'), JSON_UNESCAPED_UNICODE); ?>.replace('%s', '<b>Alt + Shift</b>').replace('%s', '<b>Win + Space</b>').replace('%s', '<b>ENG</b>') + '</small>';

                  Swal.fire({
                      icon: 'warning',
                      title: <?php echo json_encode(t('layout.kb_switch_title'), JSON_UNESCAPED_UNICODE); ?>,
                      html: htmlMsg,
                      showConfirmButton: true,
                      confirmButtonText: '<i class="fas fa-check mr-1"></i> ' + <?php echo json_encode(t('layout.ok'), JSON_UNESCAPED_UNICODE); ?>,
                      confirmButtonColor: '#f59e0b',
                      allowOutsideClick: false,
                      allowEscapeKey: false,
                      allowEnterKey: false,
                      focusConfirm: false,
                      heightAuto: false,
                      didClose: function() {
                          window._kbAlertActive = false;
                          if (typeof onAfterClose === 'function') {
                              onAfterClose();
                          } else if (restoreEl && typeof restoreEl.focus === 'function') {
                              try {
                                  restoreEl.value = '';
                                  restoreEl.dispatchEvent(new Event('input', { bubbles: true }));
                                  restoreEl.focus();
                              } catch (e) {}
                          }
                      }
                  });
              } else {
                  alert(<?php echo json_encode(t('layout.kb_switch_title'), JSON_UNESCAPED_UNICODE); ?>);
                  window._kbAlertActive = false;
              }
          };

          // ບາໂຄ້ດທີ່ສະແກນ ບໍ່ມີໃນລະບົບ → ເຕືອນດຽວກັນທຸກໜ້າ (ຂາຍ, ນຳເຂົ້າ, ໂອນ, ກວດນັບ, ວັນໝົດອາຍຸ, ປັບລາຄາ)
          // ກັນເຄື່ອງສະແກນທີ່ຍິງ Enter ຕາມມາປິດກ່ອງເອງ ດ້ວຍ _kbAlertActive ຄືກັບກ່ອງເຕືອນແປ້ນພິມ
          window.showBarcodeNotFoundAlert = function(code, targetInput, onAfterClose) {
              if (window._kbAlertActive) return;
              window._kbAlertActive = true;
              var restoreEl = targetInput || document.activeElement;
              if (document.activeElement && typeof document.activeElement.blur === 'function') {
                  try { document.activeElement.blur(); } catch (e) {}
              }
              var safeTxt = document.createElement('div');
              safeTxt.textContent = String(code || '').trim();
              var done = function() {
                  window._kbAlertActive = false;
                  if (typeof onAfterClose === 'function') {
                      onAfterClose();
                  } else if (restoreEl && typeof restoreEl.focus === 'function') {
                      try { restoreEl.focus(); if (restoreEl.select) restoreEl.select(); } catch (e) {}
                  }
              };
              if (typeof Swal === 'undefined' || !Swal.fire) {
                  alert(<?php echo json_encode(t('layout.barcode_not_found_alert'), JSON_UNESCAPED_UNICODE); ?>);
                  done();
                  return;
              }
              Swal.fire({
                  icon: 'warning',
                  title: <?php echo json_encode(t('layout.barcode_not_found_title'), JSON_UNESCAPED_UNICODE); ?>,
                  html: '<div style="font-size:0.96rem;">' + <?php echo json_encode(t('layout.barcode_not_found_text'), JSON_UNESCAPED_UNICODE); ?> + '</div>' +
                        (safeTxt.innerHTML ? '<div style="font-size:1.08rem; font-weight:700; color:#ef4444; margin:8px 0; word-break:break-all;">' + safeTxt.innerHTML + '</div>' : ''),
                  showConfirmButton: true,
                  confirmButtonText: '<i class="fas fa-check mr-1"></i> ' + <?php echo json_encode(t('layout.ok'), JSON_UNESCAPED_UNICODE); ?>,
                  confirmButtonColor: '#f59e0b',
                  allowOutsideClick: false,
                  allowEscapeKey: false,
                  allowEnterKey: false,
                  focusConfirm: false,
                  heightAuto: false,
                  didClose: done
              });
          };

          // ຊ່ອງທີ່ຮັບສະເພາະບາໂຄ້ດ/ຕົວເລກເທົ່ານັ້ນ (ຫ້າມມີພາສາລາວ/ໄທ ເດັດຂາດ)
          function isPureBarcodeInput(el) {
              if (!el || el.tagName !== 'INPUT') return false;
              var id = el.id || '';
              var name = el.name || '';
              if (id === 'add_barcode' || id === 'edit_barcode' || id === 'exScan' || id === 'quick_search_code') return true;
              if (name === 'barcode' || name === 'extra_unit_barcode[]' || name === 'b_barcode[]') return true;
              if (el.classList && (el.classList.contains('bulk-barcode') || el.classList.contains('sc-counted'))) return true;
              return false;
          }

          // ຊ່ອງທີ່ໃຊ້ສະແກນບາໂຄ້ດ ຫຼື ຄົ້ນຫາສິນຄ້າໄດ້ທຸກໜ້າ
          function isScanCapableInput(el) {
              if (!el || el.tagName !== 'INPUT') return false;
              if (isPureBarcodeInput(el)) return true;
              var id = el.id || '';
              var scanIds = [
                  'barcodeInput', 'scScanInput', 'direct_barcode_input', 'import_barcode_input',
                  'modal_product_search', 'productSearch', 'pageBarcodeSearch', 'searchQ', 'import_search'
              ];
              if (scanIds.indexOf(id) !== -1) return true;
              if (el.name === 'q') return true;
              if (el.classList && el.classList.contains('select2-search__field')) return true;
              var ph = (el.getAttribute('placeholder') || '');
              // placeholder ແປຕາມພາສາແລ້ວ — ຮູ້ຈັກ "ບາໂຄ້ດ" ທັງ 4 ພາສາ
              if (ph.indexOf('ບາໂຄ້ດ') !== -1 || ph.toLowerCase().indexOf('barcode') !== -1 || ph.indexOf('บาร์โค้ด') !== -1 || ph.indexOf('条码') !== -1) return true;
              return false;
          }

          var _burst = { el: null, start: 0, last: 0, count: 0, nonEnCount: 0, digitCodeNonEn: 0, sample: '' };

          document.addEventListener('keydown', function(e) {
              // ຖ້າກ່ອງເຕືອນປ່ຽນແປ້ນພິມກຳລັງເປີດຢູ່ → ບລັອກທຸກປຸ່ມຈາກເຄື່ອງສະແກນ (ລວມທັງ Enter) ບໍ່ໃຫ້ປິດກ່ອງເອງ ຈົນກວ່າຈະຄລິກປຸ່ມ "ຕົກລົງ"
              if (window._kbAlertActive) {
                  if (!e.ctrlKey && !e.altKey && !e.metaKey) {
                      e.preventDefault();
                      e.stopImmediatePropagation();
                  }
                  return;
              }
              if (e.ctrlKey || e.altKey || e.metaKey) return;
              var el = e.target;
              var k = e.key || '';

              // 1) ຊ່ອງບາໂຄ້ດລ້ວນ (ຫຼື ຊ່ອງຈຳນວນນັບ): ຖ້າກົດປຸ່ມແລ້ວອອກເປັນພາສາອື່ນ ໃຫ້ເຕືອນທັນທີ
              if (isPureBarcodeInput(el) && k.length === 1 && window.hasNonEnglishChars(k)) {
                  e.preventDefault();
                  e.stopImmediatePropagation();
                  window.showSwitchKeyboardAlert(k, el);
                  return;
              }

              // 2) ຈັບຈັງຫວະເຄື່ອງສະແກນບາໂຄ້ດໃນຊ່ອງສະແກນ/ຄົ້ນຫາ ແລະ ຕອນສະແກນລອຍ (global)
              if (k.length === 1) {
                  var now = Date.now();
                  if (_burst.el !== el || (now - _burst.last > 75)) {
                      _burst.el = el;
                      _burst.start = now;
                      _burst.count = 0;
                      _burst.nonEnCount = 0;
                      _burst.digitCodeNonEn = 0;
                      _burst.sample = '';
                  }
                  _burst.count++;
                  _burst.last = now;
                  _burst.sample += k;
                  if (window.hasNonEnglishChars(k)) {
                      _burst.nonEnCount++;
                      if (/^(Digit[0-9]|Numpad[0-9]|Minus)$/.test(e.code || '')) {
                          _burst.digitCodeNonEn++;
                      }
                  }

                  // ຖ້າເຄື່ອງສະແກນພິມໄວ (>= 4 ຕົວ, ສະເລ່ຍ < 50ms/ຕົວ) ແລ້ວເປັນພາສາອື່ນ → ຢຸດ ແລະ ເຕືອນທັນທີ
                  if (_burst.count >= 4 && _burst.nonEnCount >= 2 && ((now - _burst.start) / (_burst.count - 1) < 50)) {
                      if (isScanCapableInput(el) || !el || (el.tagName !== 'INPUT' && el.tagName !== 'TEXTAREA')) {
                          e.preventDefault();
                          e.stopImmediatePropagation();
                          var badSample = (el && el.tagName === 'INPUT' ? (el.value + k) : _burst.sample);
                          _burst.count = 0;
                          if (el && el.tagName === 'INPUT') {
                              el.value = '';
                              el.dispatchEvent(new Event('input', { bubbles: true }));
                          }
                          window.showSwitchKeyboardAlert(badSample, el && el.tagName === 'INPUT' ? el : null);
                          return;
                      }
                  }
              } else if (k === 'Enter' && isScanCapableInput(el)) {
                  var val = (el.value || '').trim();
                  var isFastScan = (_burst.count >= 4 && (Date.now() - _burst.last <= 200) && ((_burst.last - _burst.start) / (_burst.count - 1) < 55));
                  var isDigitRowScan = (_burst.digitCodeNonEn >= 3 && _burst.digitCodeNonEn === _burst.nonEnCount && val.length >= 3);
                  _burst.count = 0;
                  if (val && window.hasNonEnglishChars(val) && (isFastScan || isDigitRowScan || window.isLikelyBarcodeNonEnglish(val) || isPureBarcodeInput(el))) {
                      e.preventDefault();
                      e.stopImmediatePropagation();
                      window.showSwitchKeyboardAlert(val, el);
                      return;
                  }
              }
          }, true);

          // ກວດຈັບກໍລະນີ IME / ວາງຂໍ້ຄວາມ (input event) ໃນຊ່ອງບາໂຄ້ດລ້ວນ
          document.addEventListener('input', function(e) {
              var el = e.target;
              if (!el || el.tagName !== 'INPUT') return;
              var v = el.value || '';
              if (!v || !window.hasNonEnglishChars(v)) return;
              if (isPureBarcodeInput(el)) {
                  var bad = v;
                  el.value = v.replace(/[^\x20-\x7E]/g, '');
                  window.showSwitchKeyboardAlert(bad, el);
              }
          }, true);

          // ກົດ F5 / Ctrl+R ໃນໜ້າຍ່ອຍ (iframe) ໃຫ້ຣີເຟຣດສະເພາະໜ້ານີ້ ເພື່ອຮັກສາການເຊື່ອມຕໍ່ Bluetooth ຂອງໜ້າຫຼັກໄວ້
          window.addEventListener('keydown', function(e) {
              var isReload = e.key === 'F5' || ((e.ctrlKey || e.metaKey) && !e.shiftKey && !e.altKey && (e.key === 'r' || e.key === 'R'));
              if (!isReload) return;
              try {
                  if (window.top !== window && window.top.MiniposBtPrinter && window.top.MiniposBtPrinter.list().length) {
                      e.preventDefault();
                      window.location.reload();
                  }
              } catch (x) {}
          }, true);
      })();
    </script>
  </body>
</html>
