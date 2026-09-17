<!-- Table Card: ລຳດັບ, ຊື່ໂປຣ, ປະເພດ, ສ່ວນຫຼຸດ, ວັນທີ, ເງື່ອນໄຂ, ສິນຄ້າ/ປະເພດ, ສະຖານະ, ຈັດການ -->
<div class="card border-0 shadow-sm" style="border-radius: 12px; border: 1.5px solid #e2e8f0; background: #ffffff;">
  <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
    <h6 class="m-0 font-weight-bold text-dark"><i class="fas fa-list mr-2 text-primary"></i> <?php echo htmlspecialchars(t('promotions.list_title', 'ລາຍການໂປຣໂມຊັ່ນທັງໝົດ')); ?></h6>
    <span class="badge badge-light border font-weight-bold text-muted px-2.5 py-1"><?php echo count($promos); ?> <?php echo htmlspecialchars(t('promotions.items_unit', 'ລາຍການ')); ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="promoTable" style="font-size: 0.88rem;">
      <thead style="background: #f8fafc; color: #475569; font-size: 0.82rem;" class="font-weight-bold">
        <tr style="white-space: nowrap;">
          <th class="text-center" style="width: 55px; white-space: nowrap;"><?php echo htmlspecialchars(t('promotions.col_no', 'ລຳດັບ')); ?></th>
          <th style="white-space: nowrap;"><?php echo htmlspecialchars(t('promotions.col_name', 'ຊື່ໂປຣ')); ?></th>
          <th style="white-space: nowrap;"><?php echo htmlspecialchars(t('promotions.col_branch', 'ສາຂາ')); ?></th>
          <th style="white-space: nowrap;"><?php echo htmlspecialchars(t('promotions.col_type', 'ປະເພດ')); ?></th>
          <th class="text-right" style="white-space: nowrap;"><?php echo htmlspecialchars(t('promotions.col_discount', 'ສ່ວນຫຼຸດ')); ?></th>
          <th class="text-center" style="white-space: nowrap;"><?php echo htmlspecialchars(t('promotions.col_date', 'ວັນທີ')); ?></th>
          <th class="text-center" style="white-space: nowrap;"><?php echo htmlspecialchars(t('promotions.col_condition', 'ເງື່ອນໄຂ')); ?></th>
          <th style="white-space: nowrap;"><?php echo htmlspecialchars(t('promotions.col_target', 'ສິນຄ້າ/ປະເພດ')); ?></th>
          <th class="text-center" style="width: 80px; white-space: nowrap;"><?php echo htmlspecialchars(t('promotions.col_status', 'ສະຖານະ')); ?></th>
          <th class="text-center" style="width: 100px; white-space: nowrap;"><?php echo htmlspecialchars(t('promotions.col_manage', 'ຈັດການ')); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($promos)): ?>
          <tr><td colspan="10" class="text-center py-5 text-muted"><i class="fas fa-tags fa-2x mb-2 d-block text-secondary opacity-50"></i><?php echo htmlspecialchars(t('promotions.empty', 'ບໍ່ມີລາຍການໂປຣໂມຊັ່ນ')); ?></td></tr>
        <?php else: ?>
          <?php $i = 1; foreach ($promos as $p): ?>
            <?php
              // Past its end date? (timezone is forced to Asia/Vientiane globally)
              $promoEndTs = !empty($p['end_date']) ? strtotime($p['end_date'] . ' 23:59:59') : 0;
              $isExpired  = $promoEndTs > 0 && $promoEndTs < time();
            ?>
            <tr class="promo-row" style="white-space: nowrap;<?php echo $isExpired ? ' background:#fef2f2;' : ''; ?>">
              <!-- 1. ລຳດັບ -->
              <td class="text-center font-weight-bold text-muted" style="white-space: nowrap;"><?php echo $i++; ?></td>

              <!-- 2. ຊື່ໂປຣ -->
              <td class="font-weight-bold text-dark text-nowrap" style="white-space: nowrap;"><?php echo htmlspecialchars($p['promo_name']); ?></td>

              <!-- 3. ສາຂາ -->
              <td style="white-space: nowrap;">
                <?php if (empty($p['branch_id'])): ?>
                  <span class="badge badge-light border text-info font-weight-bold px-2 py-1" style="font-size: 0.76rem;"><i class="fas fa-globe mr-1"></i><?php echo htmlspecialchars(t('promotions.global_badge', 'ທຸກສາຂາ (Global)')); ?></span>
                <?php else: ?>
                  <span class="badge badge-light border text-primary font-weight-bold px-2 py-1" style="font-size: 0.76rem;"><i class="fas fa-store mr-1"></i><?php echo htmlspecialchars($p['store_name'] ?: t('promotions.branch_prefix', 'ສາຂາ ') . $p['branch_id']); ?></span>
                <?php endif; ?>
              </td>

              <!-- 3. ປະເພດ -->
              <td style="white-space: nowrap;">
                <span class="badge badge-light border text-dark font-weight-bold px-2 py-1" style="font-size: 0.76rem; white-space: nowrap;">
                  <?php
                    switch ($p['promo_type'] ?? 'discount') {
                      case 'qty_discount': echo htmlspecialchars(t('promotions.type_qty_discount', 'ສ່ວນຫຼຸດຕາມຈຳນວນ')); break;
                      case 'amount_discount': echo htmlspecialchars(t('promotions.type_amount_discount', 'ສ່ວນຫຼຸດຕາມຍອດຊື້')); break;
                      case 'buy_x_get_y': echo htmlspecialchars(t('promotions.type_buy_x_get_y', 'ຊື້ X ແຖມ Y')); break;
                      default: echo htmlspecialchars(t('promotions.type_general', 'ສ່ວນຫຼຸດທົ່ວໄປ')); break;
                    }
                  ?>
                </span>
              </td>

              <!-- 4. ສ່ວນຫຼຸດ -->
              <td class="text-right font-weight-bold text-danger" style="font-size: 0.92rem;">
                <?php
                  if (($p['discount_type'] ?? '') === 'percentage') {
                      $val = floatval($p['discount_value']);
                      echo number_format($val, floor($val) == $val ? 0 : 2) . '%';
                  } else {
                      echo htmlspecialchars($promoFmtMoney(floatval($p['discount_value'])), ENT_QUOTES);
                  }
                ?>
              </td>

              <!-- 5. ວັນທີ (Start Date - End Date) -->
              <td class="text-center font-weight-bold text-muted" style="font-size: 0.8rem; font-family: monospace;">
                <?php echo date('d/m/Y', strtotime($p['start_date'])); ?> -
                <span class="<?php echo $isExpired ? 'text-danger font-weight-bold' : ''; ?>"><?php echo date('d/m/Y', strtotime($p['end_date'])); ?></span>
              </td>

              <!-- 6. ເງື່ອນໄຂ -->
              <td class="text-center small" style="white-space: nowrap;">
                <?php
                  $conds = [];
                  if (!empty($p['min_qty']) && $p['min_qty'] > 0) {
                      $conds[] = '<span class="font-weight-bold text-dark">' . number_format($p['min_qty'], 0) . ' ' . htmlspecialchars(t('promotions.unit_pieces', 'ຊິ້ນ')) . '</span>';
                  }
                  if (!empty($p['min_amount']) && $p['min_amount'] > 0) {
                      $conds[] = '<span class="text-primary font-weight-bold">' . htmlspecialchars($promoFmtMoney(floatval($p['min_amount'])), ENT_QUOTES) . '</span>';
                  }
                  if (!empty($conds)) {
                      echo implode(' , ', $conds);
                  } else {
                      echo '<span class="text-muted">' . htmlspecialchars(t('promotions.no_condition', 'ບໍ່ມີເງື່ອນໄຂ')) . '</span>';
                  }
                ?>
              </td>

              <!-- 7. ສິນຄ້າ/ປະເພດ -->
              <td>
                <?php
                  $targetDisplay = $p['target_name'] ?? t('promotions.all_products', 'ທຸກສິນຄ້າ');
                  // If a code was entered as the target, show the matching product name instead
                  if (($p['target_type'] ?? '') === 'product' && !empty($productNameByCode[trim((string)($p['target_name'] ?? ''))])) {
                      $targetDisplay = $productNameByCode[trim((string)$p['target_name'])];
                  }
                ?>
                <span class="badge font-weight-bold px-2 py-1" style="font-size: 0.76rem; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">
                  <i class="fas fa-box-open mr-1"></i> <?php echo htmlspecialchars($targetDisplay); ?>
                </span>
              </td>

              <!-- 8. ສະຖານະ (Toggle Switch - Real-time AJAX) -->
              <td class="text-center" style="white-space: nowrap;">
                <?php if (hasPermission('promotions', 'edit')): ?>
                  <button type="button" class="btn btn-link p-0 border-0 shadow-none align-middle" style="outline: none; text-decoration: none; cursor: pointer;" onclick="togglePromoStatus(<?php echo $p['id']; ?>, <?php echo $p['status'] ? 0 : 1; ?>, this)" title="<?php echo $p['status'] ? htmlspecialchars(t('promotions.toggle_disable', 'ປິດໃຊ້ງານ')) : htmlspecialchars(t('promotions.toggle_enable', 'ເປີດໃຊ້ງານ')); ?>">
                    <div style="width: 48px; height: 20px; background: <?php echo $p['status'] ? '#10b981' : '#cbd5e1'; ?>; border-radius: 20px; position: relative; transition: background-color 0.2s ease-in-out; display: inline-block; vertical-align: middle;">
                      <div style="width: 14px; height: 14px; background: #ffffff; border-radius: 50%; position: absolute; top: 3px; <?php echo $p['status'] ? 'right: 3px;' : 'left: 3px;'; ?> transition: all 0.2s ease-in-out; box-shadow: 0 1px 3px rgba(0,0,0,0.3);"></div>
                    </div>
                  </button>
                <?php else: ?>
                  <span class="badge <?php echo $p['status'] ? 'badge-success' : 'badge-secondary'; ?> px-2 py-1">
                    <?php echo $p['status'] ? htmlspecialchars(t('promotions.status_on', 'ເປີດ')) : htmlspecialchars(t('promotions.status_off', 'ປິດ')); ?>
                  </span>
                <?php endif; ?>
                <?php if ($isExpired): ?>
                  <div class="mt-1">
                    <span class="badge badge-danger px-2 py-1" style="font-size: 0.72rem;">
                      <i class="fas fa-clock mr-1"></i><?php echo htmlspecialchars(t('promotions.expired_badge', 'ໝົດອາຍຸ')); ?>
                    </span>
                  </div>
                <?php endif; ?>
              </td>

              <!-- 9. ຈັດການ -->
              <td class="text-center">
                <?php if (hasPermission('promotions', 'edit') || hasPermission('promotions', 'del')): ?>
                  <div class="btn-group btn-group-sm" role="group">
                    <?php if (hasPermission('promotions', 'edit')): ?>
                      <button type="button" class="btn btn-outline-primary btn-sm px-2" title="<?php echo htmlspecialchars(t('promotions.edit', 'ແກ້ໄຂ')); ?>"
                              onclick="editPromo(<?php echo htmlspecialchars(json_encode($p)); ?>)">
                        <i class="fas fa-edit"></i>
                      </button>
                    <?php endif; ?>
                    <?php if (hasPermission('promotions', 'del')): ?>
                      <button type="button" class="btn btn-outline-danger btn-sm px-2" title="<?php echo htmlspecialchars(t('promotions.delete', 'ລຶບ')); ?>" onclick="deletePromo(<?php echo $p['id']; ?>, this)">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="badge badge-light text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('promotions.view_only', 'ເບິ່ງຢ່າງດຽວ')); ?></span>
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
        <?php echo htmlspecialchars(t('promotions.page_showing', 'ສະແດງ')); ?>
        <span id="promo_page_info_start">0</span>-<span id="promo_page_info_end">0</span>
        <?php echo htmlspecialchars(t('promotions.page_items', 'ລາຍການ')); ?>
      </span>
      <select id="promoPageSizeSelect" class="form-control form-control-sm font-weight-bold" style="width: auto; border-radius: 6px;" onchange="changePromoPageSize()">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="all"><?php echo htmlspecialchars(t('promotions.page_all', 'ທັງໝົດ')); ?></option>
      </select>
    </div>
    <nav aria-label="Promotion Pagination" style="margin-left: auto;">
      <ul class="pagination pagination-circle mb-0 justify-content-end" id="promoPagination"></ul>
    </nav>
  </div>
</div>
