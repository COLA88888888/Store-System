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
    </script>
  </body>
</html>
