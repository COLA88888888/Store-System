<?php
/*
 * ສ່ວນ: ແຖບຕົວກອງ ແລະ ປຸ່ມສົ່ງອອກ (ລວມຢູ່ໃນຫົວບັດດຽວ — ເປັນບັອກດຽວ)
 * ສະແກນ, ແຕ່ວັນທີ, ຫາວັນທີ, ຄົ້ນຫາ, ລ້າງຄ່າ ແລະ ປຸ່ມ Excel / PDF / ພິມ.
 * ຖືກ require ຈາກ list_view.php (ໜ້າ "ປະຫວັດ").
 */
?>
      <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap" style="gap: 12px;">
        <form method="get" class="sc-filter-form mb-0" style="flex: 1 1 auto;">
          <?php /* ຕ້ອງສົ່ງ view=checks ໄປນຳ — ບໍ່ດັ່ງນັ້ນ backend ເປີດໜ້ານັບສະຕັອກແທນປະຫວັດ */ ?>
          <input type="hidden" name="view" value="checks">
          <?php if (($canAllBranches) && count($stores) > 1): ?>
          <div class="sf-store">
            <label><i class="fas fa-store text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.col_store', 'ສາຂາ')); ?></label>
            <select name="store_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; border-color: #cbd5e1; height: 38px; width: auto; min-width: 170px;" onchange="this.form.submit()">
              <?php foreach ($stores as $st): ?>
                <option value="<?php echo $st['store_id']; ?>" <?php echo ($selectedStoreId == $st['store_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('products.store_main_suffix', '(ສາງຫຼັກ)')) : htmlspecialchars(t('products.store_branch_suffix', '(ສາຂາຍ່ອຍ)')); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
          <div class="sf-search">
            <div class="input-group" style="flex-wrap: nowrap;">
              <div class="input-group-prepend">
                <span class="input-group-text bg-white border-right-0 text-muted" style="border-radius: 8px 0 0 8px; border-color: #cbd5e1; padding: 0 10px;">
                  <i class="fas fa-barcode text-primary"></i>
                </span>
              </div>
              <input type="text" name="q" id="scFilterScanInput" class="form-control form-control-sm font-weight-bold border-left-0"
                     style="border-radius: 0 8px 8px 0; border-color: #cbd5e1;"
                     placeholder="<?php echo $sc('sc.s195'); ?>"
                     value="<?php echo htmlspecialchars($scSearch); ?>"
                     autocomplete="off">
            </div>
          </div>
          <div class="sf-date">
            <label><i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo $sc('sc.s196'); ?></label>
            <input type="date" name="from" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($scDateFrom); ?>">
          </div>
          <div class="sf-date">
            <label><i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo $sc('sc.s197'); ?></label>
            <input type="date" name="to" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($scDateTo); ?>">
          </div>
          <div class="sf-act">
            <button type="submit" class="btn btn-primary btn-sm font-weight-bold sf-search-btn">
              <i class="fas fa-search mr-1"></i> <?php echo $sc('sc.s198'); ?>
            </button>
            <a href="stock_check.php?view=checks<?php echo (($canAllBranches) && count($stores) > 1) ? '&store_id=' . $selectedStoreId : ''; ?>" class="btn btn-light btn-sm border font-weight-bold sf-reset" title="<?php echo $sc('sc.s199'); ?>">
              <i class="fas fa-redo"></i>
            </a>
          </div>
        </form>
        <div class="sc-header-exports ml-auto sc-no-print d-none d-md-block">
          <?php require __DIR__ . '/export_buttons.php'; ?>
        </div>
      </div>
      <script>
      (function() {
        // ຮັບຄ່າຈາກເຄື່ອງສະແກນບາໂຄ້ດອັດຕະໂນມັດ ເມື່ອບໍ່ໄດ້ໂຟກັສຢູ່ໃນ input ອື່ນ
        var scanBuf = '';
        var scanTmr = null;
        document.addEventListener('keydown', function(e) {
          var tag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
          if (tag === 'input' || tag === 'textarea' || tag === 'select') return;
          if (document.querySelector('.modal.show')) return;

          if (e.key === 'Enter' || e.keyCode === 13) {
            if (scanBuf.length >= 2) {
              var inp = document.getElementById('scFilterScanInput');
              if (inp) {
                inp.value = scanBuf;
                inp.form.submit();
              }
            }
            scanBuf = '';
            if (scanTmr) clearTimeout(scanTmr);
          } else if (e.key && e.key.length === 1) {
            scanBuf += e.key;
            if (scanTmr) clearTimeout(scanTmr);
            scanTmr = setTimeout(function() { scanBuf = ''; }, 300);
          }
        });
      })();
      </script>
