<?php
$bp = isset($base_path) ? rtrim($base_path, '/') . '/' : '../../../';
?>
<!-- ============================================================
     exchange_rate_table.php - ຕາຕະລາງສະແດງປະຫວັດອັດຕາແລກປ່ຽນເງິນ
     ============================================================ -->
<div class="card border-0 shadow-sm" style="border-radius: 12px; border: 1.5px solid #e2e8f0; background: #ffffff;">
  <!-- ຫົວຂໍ້ຕາຕະລາງ ແລະ ຈຳນວນລາຍການລວມ -->
  <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
    <h6 class="m-0 font-weight-bold text-dark" style="font-size: 0.95rem;">
      <i class="fas fa-history mr-2 text-primary"></i> <?php echo htmlspecialchars(t('exchange_rate.table_title', 'ຕາຕະລາງອັດຕາແລກປ່ຽນເງິນ')); ?>
    </h6>
    <span class="badge badge-light font-weight-bold text-muted border px-2.5 py-1">
      <?php echo htmlspecialchars(sprintf(t('exchange_rate.total_records', 'ລວມທັງໝົດ: %s ລາຍການ'), number_format(count($historyRates)))); ?>
    </span>
  </div>

  <!-- ຕາຕະລາງລາຍລະອຽດອັດຕາແລກປ່ຽນເງິນ (ກີບ, ບາດ, ໂດລາ) -->
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
      <thead style="background: #f8fafc; color: #475569; font-size: 0.82rem;" class="font-weight-bold">
        <tr>
          <th class="text-center" style="width: 70px;"><?php echo htmlspecialchars(t('exchange_rate.col_no', 'ລຳດັບ')); ?></th>
          <th class="text-center" style="width: 120px;"><?php echo htmlspecialchars(t('exchange_rate.col_date', 'ວັນທີ')); ?></th>
          <th class="text-center" style="width: 110px;"><?php echo htmlspecialchars(t('exchange_rate.col_time', 'ເວລາ')); ?></th>
          <th class="text-right" style="width: 150px;">
            <img src="<?php echo $bp; ?>assets/img/flag_img/Flag_of_Laos.webp" alt="LAK" style="width: 24px; height: 16px; margin-right: 6px; vertical-align: -2px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
            <span class="flag-icon flag-icon-la mr-1" style="display:none;"></span>
            <?php echo htmlspecialchars(t('exchange_rate.col_kip', 'ກີບ')); ?>
          </th>
          <th class="text-right" style="width: 170px;">
            <img src="<?php echo $bp; ?>assets/img/flag_img/Flag_of_Thailand.webp" alt="THB" style="width: 24px; height: 16px; margin-right: 6px; vertical-align: -2px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
            <span class="flag-icon flag-icon-th mr-1" style="display:none;"></span>
            <?php echo htmlspecialchars(t('exchange_rate.col_thb', 'ບາດ')); ?>
          </th>
          <th class="text-right" style="width: 170px;">
            <img src="<?php echo $bp; ?>assets/img/flag_img/flag-Stars.webp" alt="USD" style="width: 24px; height: 16px; margin-right: 6px; vertical-align: -2px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
            <span class="flag-icon flag-icon-us mr-1" style="display:none;"></span>
            <?php echo htmlspecialchars(t('exchange_rate.col_usd', 'ໂດລາ')); ?>
          </th>
          <th class="text-right" style="width: 170px;">
            <img src="<?php echo $bp; ?>assets/img/flag_img/Flag-chaina.webp" alt="CNY" style="width: 24px; height: 16px; margin-right: 6px; vertical-align: -2px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
            <span class="flag-icon flag-icon-cn mr-1" style="display:none;"></span>
            <?php echo htmlspecialchars(t('exchange_rate.col_cny', 'ຢວນ')); ?>
          </th>
          <th class="text-center" style="width: 150px;"><?php echo htmlspecialchars(t('exchange_rate.col_user', 'ຜູ້ບັນທຶກ')); ?></th>
          <th class="text-center" style="width: 130px;"><?php echo htmlspecialchars(t('exchange_rate.col_action', 'ຈັດການ')); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($historyRates)): ?>
          <tr>
            <td colspan="9" class="text-center py-5 text-muted">
              <i class="fas fa-exchange-alt fa-2x mb-2 d-block text-muted opacity-50"></i>
              <?php echo htmlspecialchars(t('exchange_rate.empty_state', 'ບໍ່ມີຂໍ້ມູນອັດຕາແລກປ່ຽນເງິນ')); ?>
            </td>
          </tr>
        <?php else: ?>
          <?php $i = 1; foreach ($historyRates as $r): ?>
            <?php 
              $thbVal = floatval($r['ex_kip_bath']);
              $usdVal = floatval($r['ex_kip_us']);
              $cnyVal = floatval($r['ex_kip_cn'] ?? 0);
            ?>
            <tr>
              <td class="text-center font-weight-bold text-muted"><?php echo $i++; ?></td>
              <td class="text-center font-weight-bold text-dark"><?php echo date('d/m/Y', strtotime($r['ex_date'])); ?></td>
              <td class="text-center text-muted"><?php echo htmlspecialchars($r['ex_time']); ?></td>
              <td class="text-right">
                <span class="badge border px-2.5 py-1.5 font-weight-bold text-dark d-inline-flex align-items-center" style="font-size: 0.88rem; border-radius: 6px; background-color: #f8fafc; border-color: #cbd5e1;">
                  <img src="<?php echo $bp; ?>assets/img/flag_img/Flag_of_Laos.webp" alt="LAK" style="width: 24px; height: 16px; margin-right: 6px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                  <span class="flag-icon flag-icon-la mr-1.5" style="display:none;"></span>
                  1,000 ₭
                </span>
              </td>
              <td class="text-right">
                <span class="badge border px-2.5 py-1.5 font-weight-bold text-success d-inline-flex align-items-center" style="font-size: 0.88rem; background-color: #f0fdf4; border-color: #bbf7d0; border-radius: 6px;">
                  <img src="<?php echo $bp; ?>assets/img/flag_img/Flag_of_Thailand.webp" alt="THB" style="width: 24px; height: 16px; margin-right: 6px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                  <span class="flag-icon flag-icon-th mr-1.5" style="display:none;"></span>
                  <?php echo number_format($thbVal, 0); ?> ₭
                </span>
              </td>
              <td class="text-right">
                <span class="badge border px-2.5 py-1.5 font-weight-bold text-primary d-inline-flex align-items-center" style="font-size: 0.88rem; background-color: #eff6ff; border-color: #bfdbfe; border-radius: 6px;">
                  <img src="<?php echo $bp; ?>assets/img/flag_img/flag-Stars.webp" alt="USD" style="width: 24px; height: 16px; margin-right: 6px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                  <span class="flag-icon flag-icon-us mr-1.5" style="display:none;"></span>
                  <?php echo number_format($usdVal, 0); ?> ₭
                </span>
              </td>
              <td class="text-right">
                <span class="badge border px-2.5 py-1.5 font-weight-bold text-warning d-inline-flex align-items-center" style="font-size: 0.88rem; background-color: #fffbeb; border-color: #fde68a; border-radius: 6px; color: #b45309 !important;">
                  <img src="<?php echo $bp; ?>assets/img/flag_img/Flag-chaina.webp" alt="CNY" style="width: 24px; height: 16px; margin-right: 6px; border-radius: 3px; object-fit: cover; box-shadow: 0 1px 2px rgba(0,0,0,0.18);" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                  <span class="flag-icon flag-icon-cn mr-1.5" style="display:none;"></span>
                  <?php echo number_format($cnyVal, 0); ?> ₭
                </span>
              </td>
              <td class="text-center">
                <span class="badge badge-light border text-dark font-weight-bold px-2.5 py-1" style="font-size: 0.80rem;">
                  <i class="fas fa-user-circle mr-1 text-muted"></i><?php echo htmlspecialchars($r['ex_userlogin'] ?? 'admin'); ?>
                </span>
              </td>
              <td class="text-center">
                <!-- ປຸ່ມຈັດການ (ແກ້ໄຂ ແລະ ລຶບ) -->
                <?php if (hasPermission('exchange_rate', 'edit') || hasPermission('exchange_rate', 'del')): ?>
                  <div class="btn-group btn-group-sm" role="group">
                    <?php if (hasPermission('exchange_rate', 'edit')): ?>
                      <button type="button" class="btn btn-outline-primary btn-sm px-2" title="<?php echo htmlspecialchars(t('exchange_rate.title_edit', 'ແກ້ໄຂ')); ?>"
                              onclick="editRate(<?php echo $r['Id']; ?>, '<?php echo number_format($thbVal, 0); ?>', '<?php echo number_format($usdVal, 0); ?>', '<?php echo number_format($cnyVal, 0); ?>')">
                        <i class="fas fa-edit"></i>
                      </button>
                    <?php endif; ?>
                    <?php if (hasPermission('exchange_rate', 'del')): ?>
                      <button type="button" class="btn btn-outline-danger btn-sm px-2" title="<?php echo htmlspecialchars(t('exchange_rate.title_delete', 'ລຶບ')); ?>"
                              onclick="deleteRate(<?php echo $r['Id']; ?>)">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <span class="badge badge-light text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('exchange_rate.view_only', 'ເບິ່ງຢ່າງດຽວ')); ?></span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>