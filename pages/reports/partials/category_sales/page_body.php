<?php
/*
 * ສ່ວນ: ສ່ວນສະແດງຜົນ
 * ຫົວໜ້າ, ປຸ່ມສົ່ງອອກ Excel/PDF/ພິມ, ຕົວກັ່ນຕອງ, ຕາຕະລາງປະເພດ ແລະ pagination.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/category_sales.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><!-- category_sales build: dedup-by-name-2026-08-30 | categories in dropdown: <?php echo count($all_categories); ?> -->

<div class="container-fluid p-3 p-md-4">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap" style="row-gap: 10px;">
    <div>
      <h5 class="font-weight-bold text-dark mb-1" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-layer-group text-primary mr-2"></i> <?php echo htmlspecialchars(t('reports.title_category_full', 'ລາຍງານຕາມປະເພດສິນຄ້າ ແລະ ລາຍການສິນຄ້າທີ່ຂາຍ')); ?>
      </h5>
    </div>

    <!-- Export & Print Buttons (Excel, PDF, Print) -->
    <div class="d-flex align-items-center justify-content-end ml-auto no-print" style="gap: 5px;">
      <button type="button" onclick="exportReportExcel('all')" class="btn btn-xs btn-success font-weight-bold px-2 d-inline-flex align-items-center shadow-sm" style="border-radius: 6px; height: 30px; font-size: 0.78rem; background: linear-gradient(135deg, #16a34a, #15803d); border: none;">
        <i class="fas fa-file-excel mr-1" style="font-size: 0.8rem;"></i> Excel
      </button>
      <button type="button" onclick="exportReportPDF('all')" class="btn btn-xs btn-danger font-weight-bold px-2 d-inline-flex align-items-center shadow-sm" style="border-radius: 6px; height: 30px; font-size: 0.78rem; background: linear-gradient(135deg, #dc2626, #b91c1c); border: none;">
        <i class="fas fa-file-pdf mr-1" style="font-size: 0.8rem;"></i> PDF
      </button>
      <button type="button" onclick="printReportTable('all')" class="btn btn-xs btn-primary font-weight-bold px-2 d-inline-flex align-items-center shadow-sm" style="border-radius: 6px; height: 30px; font-size: 0.78rem; background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
        <i class="fas fa-print mr-1" style="font-size: 0.8rem;"></i> <?php echo htmlspecialchars(t('reports.print_btn', 'ພິມ')); ?>
      </button>
    </div>
  </div>

  <!-- Search & Filter Bar -->
  <div class="report-filter-box no-print mb-3.5" style="padding: 12px 16px;">
    <form method="GET" action="category_sales.php" class="d-flex flex-column flex-md-row align-items-md-end flex-wrap" style="gap: 12px;">
      <!-- Per Page Dropdown (ໂຊລາຍການ - ໜ້າສຸດ) -->
      <div style="flex: 1 1 120px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-layer-group text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.per_page_label', 'ໂຊລາຍການ:')); ?>
        </label>
        <select name="per_page" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; background: #ffffff; border: 1.5px solid #cbd5e1; width: 100%;">
          <option value="5" <?php echo $per_page_raw === '5' ? 'selected' : ''; ?>>5</option>
          <option value="10" <?php echo $per_page_raw === '10' ? 'selected' : ''; ?>>10</option>
          <option value="25" <?php echo $per_page_raw === '25' ? 'selected' : ''; ?>>25</option>
          <option value="50" <?php echo $per_page_raw === '50' ? 'selected' : ''; ?>>50</option>
          <option value="100" <?php echo $per_page_raw === '100' ? 'selected' : ''; ?>>100</option>
          <option value="all" <?php echo $per_page_raw === 'all' ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('reports.all_opt', 'ທັງໝົດ')); ?></option>
        </select>
      </div>

      <!-- From Date (ຕັ້ງແຕ່ວັນທີ) -->
      <div style="flex: 1 1 130px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?>
        </label>
        <input type="date" name="from_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($from_date); ?>" style="border-radius: 8px; height: 38px; width: 100%;">
      </div>

      <!-- To Date (ຫາວັນທີ) -->
      <div style="flex: 1 1 130px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.to_date', 'ຫາວັນທີ:')); ?>
        </label>
        <input type="date" name="to_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($to_date); ?>" style="border-radius: 8px; height: 38px; width: 100%;">
      </div>

      <!-- Category Filter Dropdown (ເລືອກປະເພດສິນຄ້າ) -->
      <div style="flex: 1 1 150px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-tags text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.category_label', 'ປະເພດສິນຄ້າ:')); ?>
        </label>
        <select name="category_id" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; background: #ffffff; border: 1.5px solid #cbd5e1; width: 100%;">
          <option value=""><?php echo htmlspecialchars(t('reports.select_category_opt', '-- ເລືອກປະເພດສິນຄ້າ --')); ?></option>
          <?php foreach ($all_categories as $c): ?>
            <option value="<?php echo $c['category_id']; ?>" <?php echo $category_id == $c['category_id'] ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($c['category_name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Branch Filter Dropdown (ເລືອກສາຂາ) — only worth showing once there's more than one branch -->
      <?php if (canViewAllBranches($pdo) && count($branchesList) > 1): ?>
      <div style="flex: 1 1 140px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-store text-info mr-1"></i> <?php echo htmlspecialchars(t('reports.branch_label', 'ສາຂາ:')); ?>
        </label>
        <select name="store_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #cbd5e1; width: 100%;" onchange="this.form.submit()">
          <?php foreach ($branchesList as $b): ?>
            <option value="<?php echo $b['store_id']; ?>" <?php echo ($filter_store_id == $b['store_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($b['store_name']); ?> <?php echo !empty($b['is_main']) ? htmlspecialchars(t('reports.main_branch_suffix', '(ສາຂາໃຫຍ່)')) : ''; ?>
            </option>
          <?php endforeach; ?>
          <option value="0" <?php echo ((int)$filter_store_id === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('reports.all_branches_opt', '-- ທຸກສາຂາ --')); ?></option>
        </select>
      </div>
      <?php else: ?>
        <input type="hidden" name="store_id" value="0">
      <?php endif; ?>

      <!-- Currency Filter (ສະກຸນເງິນ) -->
      <div style="flex: 1 1 150px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-coins text-warning mr-1"></i> <?php echo htmlspecialchars(t('reports.currency_label', 'ສະກຸນເງິນ:')); ?>
        </label>
        <select name="currency_filter" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #a16207; color: #854d0e; background: #fefce8; width: 100%;">
          <option value=""><?php echo htmlspecialchars(t('reports.all_currencies_opt', '-- ທຸກສະກຸນເງິນ --')); ?></option>
          <?php foreach (getCurrencyMeta() as $__cc => $__ci): ?>
            <option value="<?php echo htmlspecialchars($__cc); ?>" <?php echo $currency_filter === $__cc ? 'selected' : ''; ?>>
              <?php /* ລະຫັດສະກຸນເງິນເທົ່ານັ້ນ (KIP → LAK) ບໍ່ມີສັນຍາລັກ */ echo htmlspecialchars($__cc === 'KIP' ? 'LAK' : $__cc); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Action Buttons (ຄົ້ນຫາ & ຣີໂຫລດ) -->
      <div style="flex: 1 1 130px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block d-md-none" style="font-size: 0.82rem; visibility: hidden;">&nbsp;</label>
        <div class="d-flex align-items-center" style="gap: 8px; width: 100%;">
          <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); flex: 1;">
            <i class="fas fa-search mr-1.5"></i> <?php echo htmlspecialchars(t('reports.search_btn', 'ຄົ້ນຫາ')); ?>
          </button>
          <a href="category_sales.php" class="btn btn-light btn-sm border font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px;" title="<?php echo htmlspecialchars(t('reports.clear', 'ລ້າງຄ່າ')); ?>">
            <i class="fas fa-redo"></i>
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Category Table Card -->
  <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
    <div class="card-body p-0">
      <div class="table-responsive" style="overflow: visible;">
        <table class="table table-hover align-middle mb-0 text-nowrap report-table" style="font-size: 0.88rem;">
          <thead style="background-color: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;">
            <tr style="background: #ffffff; color: #1e293b;">
              <th class="text-center py-3" style="width: 60px; border-bottom: 2px solid #cbd5e1;"><?php echo htmlspecialchars(t('reports.col_no', 'ລຳດັບ')); ?></th>
              <th class="py-3" style="border-bottom: 2px solid #cbd5e1;"><?php echo htmlspecialchars(t('reports.col_category_product', 'ປະເພດສິນຄ້າ / ລາຍການສິນຄ້າ')); ?></th>
              <th class="text-center py-3" style="width: 120px; border-bottom: 2px solid #cbd5e1;"><?php echo htmlspecialchars(t('reports.col_qty', 'ຈຳນວນ')); ?></th>
              <th class="text-center py-3" style="width: 130px; border-bottom: 2px solid #cbd5e1;"><?php echo htmlspecialchars(t('reports.discount_label', 'ສ່ວນຫຼຸດ')); ?></th>
              <th class="text-center py-3" style="width: 150px; border-bottom: 2px solid #cbd5e1;"><?php echo htmlspecialchars(t('reports.col_stock_cut_actual', 'ຕັດສະຕັອກຕົວຈິງ')); ?></th>
              <th class="text-right py-3" style="width: 160px; border-bottom: 2px solid #cbd5e1;"><?php echo htmlspecialchars(t('reports.col_total_money', 'ລວມເງິນ')); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($display_data)): ?>
              <tr>
                <td colspan="6" class="text-center py-5 text-muted font-weight-bold">
                  <i class="fas fa-layer-group fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                  <?php echo htmlspecialchars(t('reports.no_category_data', 'ບໍ່ພົບຂໍ້ມູນປະເພດສິນຄ້າ')); ?>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($display_data as $idx => $row): ?>
                <?php
                  $cid = $row['category_name'];
                  $prods = $category_products[$cid] ?? [];
                ?>
                <!-- Category Header Row -->
                <tr class="cat-header-row" style="background-color: #f1f5f9; border-top: 2px solid #cbd5e1; font-weight: bold;">
                  <td class="text-center align-middle text-dark" style="font-size: 0.95rem; font-weight: 700; background-color: #f1f5f9;">
                    <?php echo $offset + $idx + 1; ?>
                  </td>
                  <td class="align-middle text-dark" style="font-size: 0.96rem; font-weight: 700; background-color: #f1f5f9;">
                    <?php echo htmlspecialchars($row['category_name']); ?>
                  </td>
                  <td class="text-center align-middle text-dark" style="font-size: 0.95rem; font-weight: 700; background-color: #f1f5f9;">
                    <?php echo number_format($row['total_qty_sold']); ?>
                  </td>
                  <td class="text-center align-middle" style="background-color: #f1f5f9;">
                    <?php if (!empty($row['total_category_discount']) && floatval($row['total_category_discount']) > 0): ?>
                      <span class="font-weight-bold text-danger" style="font-size: 0.9rem;">
                        -<?php echo displayMoney($pdo, $row['total_category_discount']); ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center align-middle text-dark" style="font-size: 0.95rem; font-weight: 700; background-color: #f1f5f9;">
                    <?php echo number_format($row['total_stock_cut_qty']); ?>
                  </td>
                  <td class="text-right align-middle text-dark" style="font-size: 0.96rem; font-weight: 700; background-color: #f1f5f9;">
                    <?php echo displayMoney($pdo, max(0, floatval($row['total_category_revenue']) - floatval($row['total_category_discount'])), false); ?>
                  </td>
                </tr>

                <!-- Product Detail Rows -->
                <?php if (!empty($prods)): ?>
                  <?php foreach ($prods as $pIdx => $p): ?>
                    <?php
                      // $p['product_name'] and $p['unit_label'] were already normalized (redundant
                      // leading "1" stripped) and merged with same-named duplicates during aggregation above.
                      $unitMultiplier = intval($p['unit_multiplier'] ?? 1);
                      $baseUnit       = trim($p['base_unit'] ?? '');
                      $unitLabel      = trim($p['unit_label'] ?? '');
                      $displayName    = $p['product_name'] ?? '';
                      $showUnitDetail = ($unitMultiplier > 1 && $unitLabel !== '' && $baseUnit !== '' && $unitLabel !== $baseUnit);
                    ?>
                    <tr style="background-color: #ffffff;">
                      <td class="text-center align-middle text-muted" style="font-size: 0.88rem; padding-left: 20px;">
                        <?php echo $pIdx + 1; ?>
                      </td>
                      <td class="align-middle text-secondary" style="font-size: 0.9rem; padding-left: 28px;">
                        <?php echo htmlspecialchars($displayName); ?>
                        <?php if ($showUnitDetail): ?>
                          <div class="text-muted" style="font-size: 0.76rem; font-weight: 600;">
                            (1 <?php echo htmlspecialchars($unitLabel); ?> = <?php echo number_format($unitMultiplier); ?> <?php echo htmlspecialchars($baseUnit); ?>)
                          </div>
                        <?php endif; ?>
                      </td>
                      <td class="text-center align-middle text-secondary" style="font-size: 0.9rem;">
                        <?php echo number_format($p['qty_sold']); ?>
                      </td>
                      <td class="text-center align-middle">
                        <?php if (!empty($p['item_discount']) && floatval($p['item_discount']) > 0): ?>
                          <span class="font-weight-bold text-danger" style="font-size: 0.86rem;">
                            -<?php echo displayMoney($pdo, $p['item_discount']); ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted">-</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-center align-middle text-secondary" style="font-size: 0.9rem;">
                        <?php echo number_format($p['stock_cut_qty']); ?>
                      </td>
                      <td class="text-right align-middle text-dark" style="font-size: 0.9rem;">
                        <?php echo displayMoney($pdo, max(0, floatval($p['item_revenue']) - floatval($p['item_discount'])), false); ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr style="background-color: #ffffff;">
                    <td></td>
                    <td colspan="6" class="text-muted italic py-2" style="font-size: 0.85rem; padding-left: 28px;">
                      <?php echo htmlspecialchars(t('reports.no_category_sales_history', '(ບໍ່ມີປະຫວັດການຂາຍ)')); ?>
                    </td>
                  </tr>
                <?php endif; ?>

              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <?php if (!empty($display_data)): ?>
            <tfoot style="background: #f8fafc; border-top: 2px solid #cbd5e1;">
              <tr class="font-weight-bold" style="font-size: 0.9rem; color: #0f172a;">
                <td colspan="2" class="text-center py-3 font-weight-bold" style="background: #f8fafc; color: #0f172a;"><?php echo htmlspecialchars(t('reports.total_all_label', 'ລວມທັງໝົດ:')); ?></td>
                <td class="text-center py-3 text-dark font-weight-bold" style="background: #f8fafc;"><?php echo number_format($grand_total_qty); ?></td>
                <td class="text-center py-3 font-weight-bold" style="background: #f8fafc;">
                  <?php if ($grand_total_discount > 0): ?>
                    <span class="font-weight-bold text-danger" style="font-size: 0.94rem;">-<?php echo displayMoney($pdo, $grand_total_discount); ?></span>
                  <?php else: ?>
                    <span class="text-muted">-</span>
                  <?php endif; ?>
                </td>
                <td class="text-center py-3 text-dark font-weight-bold" style="background: #f8fafc;"><?php echo number_format($grand_total_stock_cut); ?></td>
                <td class="text-right py-3 text-primary font-weight-bold" style="background: #f8fafc; font-size: 0.96rem;"><?php echo displayMoney($pdo, max(0, $grand_total_revenue - $grand_total_discount)); ?></td>
              </tr>
              <tr style="font-size: 0.88rem; color: #16a34a;">
                <td colspan="5" class="text-right py-2 font-weight-bold" style="background: #f8fafc; color: #16a34a;">
                   <?php echo htmlspecialchars(t('reports.total_cash_label', 'ເງິນສົດ:')); ?>
                </td>
                <td class="text-right py-2 font-weight-bold" style="background: #f8fafc; color: #16a34a;">
                  <?php echo displayMoney($pdo, $cash_total); ?>
                </td>
              </tr>
              <tr style="font-size: 0.88rem; color: #2563eb;">
                <td colspan="5" class="text-right py-2 font-weight-bold" style="background: #f8fafc; color: #2563eb;">
                   <?php echo htmlspecialchars(t('reports.total_transfer_label', 'ເງິນໂອນ:')); ?>
                </td>
                <td class="text-right py-2 font-weight-bold" style="background: #f8fafc; color: #2563eb;">
                  <?php echo displayMoney($pdo, $transfer_total); ?>
                </td>
              </tr>
              <tr style="font-size: 0.98rem; border-top: 2px solid #cbd5e1;">
                <td colspan="5" class="text-right py-2.5 font-weight-bold" style="background: #f1f5f9; color: #0f172a;">
                   <?php echo htmlspecialchars(t('reports.total_label', 'ລວມ:')); ?>
                </td>
                <td class="text-right py-2.5 font-weight-bold" style="background: #f1f5f9; color: #0f172a; font-size: 1.02rem;">
                  <?php echo displayMoney($pdo, $grand_total_paid); ?>
                </td>
              </tr>
            </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Table Card Footer: Circular Blue Pagination Aligned Right -->
    <div class="card-footer bg-white border-top py-3 px-3.5 d-flex flex-column flex-md-row justify-content-between align-items-center no-print" style="row-gap: 12px; border-bottom-left-radius: 12px; border-bottom-right-radius: 12px;">

      <nav aria-label="Page navigation" class="ml-auto">
        <ul class="pagination report-pagination mb-0">
          <!-- Previous Page -->
          <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
            <a class="page-link" href="category_sales.php?page=<?php echo max(1, $page - 1); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&category_id=<?php echo urlencode($category_id); ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo urlencode($per_page_raw); ?>&currency_filter=<?php echo urlencode($currency_filter); ?>">
              <i class="fas fa-chevron-left" style="font-size: 0.76rem;"></i>
            </a>
          </li>

          <?php
            $range = 2;
            $startP = max(1, $page - $range);
            $endP   = min($total_pages, $page + $range);

            if ($startP > 1) {
                echo '<li class="page-item"><a class="page-link" href="category_sales.php?page=1&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&category_id=' . urlencode($category_id) . '&search=' . urlencode($search) . '&per_page=' . urlencode($per_page_raw) . '&currency_filter=' . urlencode($currency_filter) . '">1</a></li>';
                if ($startP > 2) {
                    echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
                }
            }

            for ($p = $startP; $p <= $endP; $p++) {
                $activeClass = ($p == $page) ? 'active' : '';
                echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="category_sales.php?page=' . $p . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&category_id=' . urlencode($category_id) . '&search=' . urlencode($search) . '&per_page=' . urlencode($per_page_raw) . '&currency_filter=' . urlencode($currency_filter) . '">' . $p . '</a></li>';
            }

            if ($endP < $total_pages) {
                if ($endP < $total_pages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="category_sales.php?page=' . $total_pages . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&category_id=' . urlencode($category_id) . '&search=' . urlencode($search) . '&per_page=' . urlencode($per_page_raw) . '&currency_filter=' . urlencode($currency_filter) . '">' . $total_pages . '</a></li>';
            }
          ?>

          <!-- Next Page -->
          <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="category_sales.php?page=<?php echo min($total_pages, $page + 1); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&category_id=<?php echo urlencode($category_id); ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo urlencode($per_page_raw); ?>&currency_filter=<?php echo urlencode($currency_filter); ?>">
              <i class="fas fa-chevron-right" style="font-size: 0.76rem;"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</div>

