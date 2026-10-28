<div class="card border-0 shadow-sm" style="border-radius: 14px; border: 1.5px solid #e2e8f0; background: #ffffff;">
  <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
    <h6 class="m-0 font-weight-bold text-dark">
      <i class="fas fa-list-alt text-primary mr-2"></i> <?php echo htmlspecialchars(t('price_adjustment.table_title', 'ລາຍງານການປັບລາຄາທັງໝົດ')); ?>
    </h6>
    <span class="badge badge-light border font-weight-bold text-muted px-2.5 py-1"><?php echo htmlspecialchars(sprintf(t('price_adjustment.total_records', '%d ລາຍການ'), count($adjustments))); ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="priceAdjTable" style="font-size: 0.86rem;">
      <thead style="background: #f8fafc; color: #475569; font-size: 0.80rem;" class="font-weight-bold">
        <tr style="white-space: nowrap;">
          <th class="text-center" style="width: 50px; white-space: nowrap;"><?php echo htmlspecialchars(t('price_adjustment.col_no', 'ລ/ດ')); ?></th>
          <th class="text-center" style="white-space: nowrap;"><?php echo htmlspecialchars(t('price_adjustment.col_code', 'ລະຫັດ')); ?></th>
          <th style="white-space: nowrap;"><?php echo htmlspecialchars(t('price_adjustment.col_barcode', 'ບາໂຄ້ດ')); ?></th>
          <th><?php echo htmlspecialchars(t('price_adjustment.col_product_name', 'ຊື່ສິນຄ້າ')); ?></th>
          <th><?php echo htmlspecialchars(t('price_adjustment.col_branch', 'ສາຂາ')); ?></th>
          <th class="text-right"><?php echo htmlspecialchars(t('price_adjustment.col_old_price', 'ລາຄາເກົ່າ')); ?></th>
          <th class="text-right"><?php echo htmlspecialchars(t('price_adjustment.col_new_price', 'ລາຄາໃໝ່')); ?></th>
          <th class="text-right"><?php echo htmlspecialchars(t('price_adjustment.col_diff', 'ຜົນຕ່າງ')); ?></th>
          <th class="text-center"><?php echo htmlspecialchars(t('price_adjustment.col_datetime', 'ວັນທີ & ເວລາ')); ?></th>
          <th><?php echo htmlspecialchars(t('price_adjustment.col_adjuster', 'ຜູ້ປັບ')); ?></th>
          <th><?php echo htmlspecialchars(t('price_adjustment.col_detail', 'ລາຍລະອຽດ')); ?></th>
          <th class="text-center" style="width: 55px;"><?php echo htmlspecialchars(t('price_adjustment.col_action', 'ຈັດການ')); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($adjustments)): ?>
          <tr>
            <td colspan="12" class="text-center py-5 text-muted">
              <i class="fas fa-tags fa-2x mb-2 d-block text-secondary opacity-50"></i>
              <?php echo htmlspecialchars(t('price_adjustment.empty_state', 'ບໍ່ມີປະຫວັດການປັບລາຄາ')); ?>
            </td>
          </tr>
        <?php else: ?>
          <?php $i = 1; foreach ($adjustments as $adj): ?>
            <?php
              $diff = floatval($adj['diff_amount'] ?? 0);
              $oldP = floatval($adj['old_price'] ?? 0);
              $newP = floatval($adj['new_price'] ?? 0);
              if ($adj['target_price_type'] === 'bprice') {
                  $oldP = floatval($adj['old_bprice'] ?? 0);
                  $newP = floatval($adj['new_bprice'] ?? 0);
              }
              $prodIdCode = $adj['product_id'] ? ('P-' . str_pad($adj['product_id'], 4, '0', STR_PAD_LEFT)) : '-';
              $barcodeVal = !empty($adj['prod_barcode']) ? $adj['prod_barcode'] : '-';
            ?>
            <tr class="price-adj-row">
              <!-- 1. ລຳດັບ -->
              <td class="text-center font-weight-bold text-muted"><?php echo $i++; ?></td>

              <!-- 2. ລະຫັດສິນຄ້າ -->
              <td class="text-center font-weight-bold text-dark" style="font-family: monospace;">
                <?php if ($adj['target_mode'] === 'category'): ?>
                  <span class="badge badge-light border text-muted"><?php echo htmlspecialchars(t('price_adjustment.badge_category', 'ໝວດໝູ່')); ?></span>
                <?php else: ?>
                  <span class="badge badge-secondary px-2 py-1"><?php echo htmlspecialchars($prodIdCode); ?></span>
                <?php endif; ?>
              </td>

              <!-- 3. ບາໂຄ້ດ -->
              <td class="font-weight-bold text-secondary" style="font-family: monospace;">
                <?php if ($adj['target_mode'] === 'category'): ?>
                  -
                <?php else: ?>
                  <i class="fas fa-barcode text-muted mr-1" style="font-size: 0.8rem;"></i><?php echo htmlspecialchars($barcodeVal); ?>
                <?php endif; ?>
              </td>

              <!-- 4. ຊື່ສິນຄ້າ -->
              <td class="font-weight-bold text-dark">
                <?php if ($adj['target_mode'] === 'category'): ?>
                  <span class="badge badge-info font-weight-bold mr-1"><i class="fas fa-folder mr-1"></i> <?php echo htmlspecialchars(t('price_adjustment.badge_category', 'ໝວດໝູ່')); ?>: <?php echo htmlspecialchars($adj['category_name'] ?? t('price_adjustment.badge_category_all', 'ທຸກສິນຄ້າ')); ?></span>
                <?php else: ?>
                  <?php echo htmlspecialchars($adj['product_name'] ?? (t('price_adjustment.product_id_fallback', 'ສິນຄ້າ ID: ') . $adj['product_id'])); ?>
                <?php endif; ?>
              </td>

              <!-- 5. ສາຂາ -->
              <td class="font-weight-bold">
                <span class="badge badge-light border text-primary px-2 py-1" style="font-size: 0.78rem;">
                  <i class="fas fa-store mr-1 text-primary"></i><?php echo htmlspecialchars($adj['store_name'] ?: t('price_adjustment.badge_main_branch_fallback', 'ສາຂາຫຼັກ')); ?>
                </span>
              </td>

              <!-- 5. ລາຄາເກົ່າ -->
              <td class="text-right text-muted font-weight-bold">
                <?php echo number_format($oldP, 0); ?> <?php echo kipSymbol(); ?>
              </td>

              <!-- 6. ລາຄາໃໝ່ -->
              <td class="text-right font-weight-bold text-primary">
                <?php echo number_format($newP, 0); ?> <?php echo kipSymbol(); ?>
              </td>

              <!-- 7. ຜົນຕ່າງ -->
              <td class="text-right font-weight-bold">
                <?php if ($diff > 0): ?>
                  <span class="text-danger"><i class="fas fa-arrow-up mr-1" style="font-size:0.7rem;"></i>+<?php echo number_format($diff, 0); ?> <?php echo kipSymbol(); ?></span>
                <?php elseif ($diff < 0): ?>
                  <span class="text-success"><i class="fas fa-arrow-down mr-1" style="font-size:0.7rem;"></i><?php echo number_format($diff, 0); ?> <?php echo kipSymbol(); ?></span>
                <?php else: ?>
                  <span class="text-muted">0 <?php echo kipSymbol(); ?></span>
                <?php endif; ?>
              </td>

              <!-- 8. ວັນທີ & ເວລາ -->
              <td class="text-center text-muted small" style="white-space: nowrap; font-family: monospace;">
                <?php echo date('d/m/Y', strtotime($adj['adjust_date'])); ?> <small><?php echo date('H:i', strtotime($adj['adjust_time'])); ?></small>
              </td>

              <!-- 9. ຜູ້ປັບ -->
              <td class="small font-weight-bold text-dark">
                <i class="fas fa-user-circle text-secondary mr-1"></i><?php echo htmlspecialchars($adj['username'] ?? 'admin'); ?>
              </td>

              <!-- 10. ລາຍລະອຽດ / ໝາຍເຫດ -->
              <td>
                <small class="text-muted">
                  <?php
                    $typeText = t('price_adjustment.type_price', 'ລາຄາຂາຍ');
                    if ($adj['target_price_type'] === 'bprice') $typeText = t('price_adjustment.type_bprice', 'ລາຄາຊື້/ທຶນ');
                    if ($adj['target_price_type'] === 'both') $typeText = t('price_adjustment.type_both', 'ທັງຂາຍ&ຊື້');

                    $calcText = t('price_adjustment.calc_set', 'ຕັ້ງໃໝ່');
                    if ($adj['calc_type'] === 'increase') $calcText = t('price_adjustment.calc_increase', 'ເພີ່ມ');
                    if ($adj['calc_type'] === 'decrease') $calcText = t('price_adjustment.calc_decrease', 'ຫຼຸດ');

                    echo '<strong>' . htmlspecialchars($typeText) . ' (' . htmlspecialchars($calcText) . ')</strong>';
                    if (!empty($adj['remark'])) {
                        echo '<span class="d-block text-truncate" style="max-width: 140px;" title="' . htmlspecialchars($adj['remark']) . '">' . htmlspecialchars($adj['remark']) . '</span>';
                    }
                  ?>
                </small>
              </td>

              <!-- 11. ຈັດການ (ແກ້ໄຂ / ລຶບ - Real-time AJAX) -->
              <td class="text-center" style="white-space: nowrap;">
                <?php
                  $canEdit = hasPermission('price_adjustment', 'edit') && $adj['target_mode'] !== 'category' && !empty($adj['product_id']);
                  $canDel  = hasPermission('price_adjustment', 'del');
                  if ($canEdit):
                    $editData = json_encode([
                      'adjust_id'         => (int)$adj['adjust_id'],
                      'product_id'        => (int)$adj['product_id'],
                      'product_name'      => $adj['product_name'] ?? '',
                      'old_bprice'        => (float)$adj['old_bprice'],
                      'old_price'         => (float)$adj['old_price'],
                      'target_price_type' => $adj['target_price_type'] ?? 'price',
                      'calc_type'         => $adj['calc_type'] ?? 'set',
                      'adjust_value'      => (float)$adj['adjust_value'],
                      'remark'            => $adj['remark'] ?? '',
                    ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
                ?>
                  <button type="button" class="btn btn-outline-primary btn-sm px-2 py-1 mr-1" title="<?php echo htmlspecialchars(t('price_adjustment.title_edit', 'ແກ້ໄຂ')); ?>" style="border-radius: 6px;" onclick='editPriceAdjLog(<?php echo $editData; ?>)'>
                    <i class="fas fa-edit" style="font-size: 0.84rem;"></i>
                  </button>
                <?php endif; ?>
                <?php if ($canDel): ?>
                  <button type="button" class="btn btn-outline-danger btn-sm px-2 py-1" title="<?php echo htmlspecialchars(t('price_adjustment.title_delete', 'ລຶບປະຫວັດ')); ?>" style="border-radius: 6px;" onclick="deletePriceAdjLog(<?php echo $adj['adjust_id']; ?>, this)">
                    <i class="fas fa-trash-alt" style="font-size: 0.84rem;"></i>
                  </button>
                <?php endif; ?>
                <?php if (!$canEdit && !$canDel): ?>
                  <span class="badge badge-light text-muted" style="font-size: 0.75rem;">-</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination Footer -->
  <div class="card-footer bg-white border-top d-flex align-items-center justify-content-between py-3 px-3 px-md-4" style="gap: 10px; flex-wrap: wrap; flex-direction: row;">
    <div class="d-flex align-items-center" style="gap: 8px; font-size: 0.85rem;">
      <span class="text-muted font-weight-bold">
        <?php echo htmlspecialchars(t('price_adjustment.page_showing', 'ສະແດງ')); ?>
        <span id="padj_page_info_start">0</span>-<span id="padj_page_info_end">0</span>
        <?php echo htmlspecialchars(t('price_adjustment.page_items', 'ລາຍການ')); ?>
      </span>
      <select id="padjPageSizeSelect" class="form-control form-control-sm font-weight-bold" style="width: auto; border-radius: 6px;" onchange="changePadjPageSize()">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="all"><?php echo htmlspecialchars(t('price_adjustment.page_all', 'ທັງໝົດ')); ?></option>
      </select>
    </div>
    <nav aria-label="Price Adjustment Pagination" style="margin-left: auto;">
      <ul class="pagination pagination-circle mb-0 justify-content-end" id="padjPagination"></ul>
    </nav>
  </div>
</div>
