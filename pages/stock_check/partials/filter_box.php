<?php
/*
 * ສ່ວນ: ກ່ອງຕົວກອງ (ແບບດຽວກັບໜ້າທະນາຄານ — pages/bank/partials/bank_header.php)
 * ແຕ່ວັນທີ / ຫາວັນທີ (+ ຊ່ອງຄົ້ນຫາສິນຄ້າ ໃນແຖບປະຫວັດ), ປຸ່ມຄົ້ນຫາ ແລະ ລ້າງຄ່າ.
 * ຖືກ require ຈາກ list_view.php ແລະ history_view.php (ໃຊ້ $scView, $scDateFrom, $scDateTo, $scHistorySearch).
 */
?>
      <div class="sc-filter-box mb-3">
        <form method="get" class="sc-filter-form">
          <?php if ($scView === 'history'): ?><input type="hidden" name="view" value="history"><?php endif; ?>
          <div class="sf-date">
            <label><i class="fas fa-calendar-alt text-primary mr-1"></i> ແຕ່ວັນທີ:</label>
            <input type="date" name="from" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($scDateFrom); ?>">
          </div>
          <div class="sf-date">
            <label><i class="fas fa-calendar-check text-primary mr-1"></i> ຫາວັນທີ:</label>
            <input type="date" name="to" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($scDateTo); ?>">
          </div>
          <?php if ($scView === 'history'): ?>
          <div class="sf-search">
            <label><i class="fas fa-box text-info mr-1"></i> ສິນຄ້າ:</label>
            <input type="text" name="q" class="form-control form-control-sm font-weight-bold" placeholder="ຊື່ສິນຄ້າ / ບາໂຄ້ດ" value="<?php echo htmlspecialchars($scHistorySearch); ?>">
          </div>
          <?php endif; ?>
          <div class="sf-act">
            <button type="submit" class="btn btn-primary btn-sm font-weight-bold sf-search-btn">
              <i class="fas fa-search mr-1"></i> ຄົ້ນຫາ
            </button>
            <a href="stock_check.php<?php echo $scView === 'history' ? '?view=history' : ''; ?>" class="btn btn-light btn-sm border font-weight-bold sf-reset" title="ລ້າງຄ່າຄົ້ນຫາ">
              <i class="fas fa-redo"></i>
            </a>
          </div>
        </form>
      </div>
