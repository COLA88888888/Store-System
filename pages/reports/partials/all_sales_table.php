<div class="report-table-card card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden; max-width: 100%;">
  <div class="table-responsive" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-hover table-striped align-middle report-table mb-0" style="font-size: 0.85rem; min-width: 720px;">
      <thead style="position: sticky; top: 0; z-index: 10; background: #ffffff !important; color: #1e293b;">
        <?php if ($view_mode === 'item'): ?>
          <!-- PRODUCT SUMMARY HEADERS FOR ALL SALES REPORT (7 COLUMNS) -->
          <tr style="background: #ffffff; color: #1e293b;">
            <th class="text-center" style="width: 60px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_no', 'ລຳດັບ')); ?></th>
            <th class="text-left" style="width: 130px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_code', 'ລະຫັດ')); ?></th>
            <th class="text-left" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_product_name', 'ຊື່ສິນຄ້າ')); ?></th>
            <th class="text-center" style="width: 100px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_qty', 'ຈຳນວນ')); ?></th>
            <th class="text-right" style="width: 140px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_gross', 'ລາຄາລວມ')); ?></th>
            <th class="text-right" style="width: 130px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.discount_label', 'ສ່ວນຫຼຸດ')); ?></th>
            <th class="text-right" style="width: 150px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_net', 'ສຸດທິ')); ?></th>
          </tr>
        <?php else: ?>
          <!-- ALL SALES INVOICE HEADERS (12 COLUMNS) -->
          <tr style="background: #ffffff; color: #1e293b;">
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0; vertical-align: middle;"><?php echo htmlspecialchars(t('reports.col_no', 'ລຳດັບ')); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0; vertical-align: middle;"><?php echo htmlspecialchars(t('reports.col_bill_no', 'ເລກບິນ')); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0; vertical-align: middle;"><?php echo htmlspecialchars(t('reports.col_date', 'ວັນທີ')); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0; vertical-align: middle;"><?php echo htmlspecialchars(t('reports.col_qty', 'ຈຳນວນ')); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0; vertical-align: middle;"><?php echo htmlspecialchars(t('reports.col_gross', 'ລາຄາລວມ')); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0; vertical-align: middle;"><?php echo htmlspecialchars(t('reports.discount_label', 'ສ່ວນຫຼຸດ')); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0; vertical-align: middle;"><?php echo htmlspecialchars(t('reports.col_net', 'ສຸດທິ')); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0; vertical-align: middle;"><?php echo htmlspecialchars(t('reports.col_cash', 'ເງິນສົດ')); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0; vertical-align: middle;"><?php echo htmlspecialchars(t('reports.col_transfer', 'ໂອນ')); ?></th>
            <th class="text-center" style="width: 100px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0; vertical-align: middle;"><?php echo htmlspecialchars(t('reports.col_currency', 'ສະກຸນເງິນ')); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0; vertical-align: middle;"><?php echo htmlspecialchars(t('reports.col_status', 'ສະຖານະ')); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0; vertical-align: middle;"><?php echo htmlspecialchars(t('reports.col_seller', 'ຜູ້ຂາຍ')); ?></th>
          </tr>
        <?php endif; ?>
      </thead>
      <tbody>
        <?php if (empty($display_data)): ?>
          <tr>
            <td colspan="<?php echo $view_mode === 'item' ? '7' : '13'; ?>" class="text-center py-5">
              <div class="text-muted font-weight-bold" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('reports.no_sales_data', 'ບໍ່ພົບຂໍ້ມູນລາຍງານການຂາຍ')); ?></div>
              <small class="text-muted"><?php echo htmlspecialchars(t('reports.change_filter_hint', 'ກະລຸນາປ່ຽນເງື່ອນໄຂການຄົ້ນຫາ ຫຼື ເລືອກຊ່ວງວັນທີໃໝ່')); ?></small>
            </td>
          </tr>
        <?php else: ?>

          <?php if ($view_mode === 'item'): ?>
            <!-- PRODUCT SUMMARY TABLE FOR ALL SALES REPORT (7 COLUMNS) -->
            <?php $i = $start_record; foreach ($display_data as $row): ?>
              <tr>
                <td class="text-center font-weight-bold text-muted"><?php echo $i++; ?></td>
                <td class="text-left font-weight-bold text-secondary"><?php echo htmlspecialchars($row['pro_code']); ?></td>
                <td class="text-left font-weight-bold text-dark"><?php echo htmlspecialchars($row['pro_name']); ?></td>
                <td class="text-center font-weight-bold text-dark"><?php echo number_format($row['qty']); ?></td>
                <td class="text-right font-weight-bold text-secondary"><?php echo displayMoney($pdo, $row['gross']); ?></td>
                <td class="text-right text-danger font-weight-bold"><?php echo displayMoney($pdo, $row['discount']); ?></td>
                <td class="text-right font-weight-bold text-success" style="font-size: 0.94rem;"><?php echo displayMoney($pdo, $row['net']); ?></td>
              </tr>
            <?php endforeach; ?>

          <?php else: ?>
            <!-- INVOICE SUMMARY TABLE FOR ALL SALES REPORT (12 COLUMNS) -->
            <?php $i = $start_record; foreach ($display_data as $row): ?>
              <tr>
                <td class="text-center font-weight-bold text-muted"><?php echo $i++; ?></td>
                <td class="text-left font-weight-bold text-primary">
                  <a href="javascript:void(0)" onclick="viewBillDetails('<?php echo htmlspecialchars($row['bill_no']); ?>')" class="text-primary" title="<?php echo htmlspecialchars(t('reports.view_detail_title', 'ເບິ່ງລາຍລະອຽດບິນ')); ?>">
                    <?php echo htmlspecialchars($row['bill_no']); ?>
                  </a>
                  <?php if (strpos($row['sale_remark'] ?? '', 'Quotation To Sale') !== false): ?>
                    <span class="badge badge-warning text-dark ml-1" style="font-size: 0.65rem;"><i class="fas fa-file-invoice mr-1"></i> ໃບສະເໜີລາຄາ</span>
                  <?php endif; ?>
                </td>
                <td class="text-left" style="font-size: 0.84rem; color: #475569;"><?php echo htmlspecialchars($row['date_time']); ?></td>
                <td class="text-center font-weight-bold text-dark"><?php echo number_format($row['qty']); ?></td>
                <td class="text-right font-weight-bold text-secondary"><?php echo displayMoney($pdo, $row['gross']); ?></td>
                <td class="text-right text-danger font-weight-bold"><?php echo displayMoney($pdo, $row['discount']); ?></td>
                <td class="text-right font-weight-bold text-success" style="font-size: 0.94rem;"><?php echo displayMoney($pdo, $row['net']); ?></td>
                <td class="text-right font-weight-bold text-dark"><?php echo displayMoney($pdo, $row['cash']); ?></td>
                <td class="text-right font-weight-bold text-primary">
                  <?php if (($row['qr'] ?? 0) > 0): ?>
                    <div><?php echo displayMoney($pdo, $row['qr']); ?></div>
                    <?php 
                      $bName = !empty($row['bank_name']) ? $row['bank_name'] : 'BCEL One';
                      $bLogoUrl = getBankLogoByInfo($bName, $row['bank_account_id'] ?? 0);
                    ?>
                    <div class="d-inline-flex align-items-center mt-1 justify-content-end">
                      <img src="<?php echo htmlspecialchars($bLogoUrl); ?>" style="width: 24px; height: 24px; object-fit: cover; border-radius: 50% !important; background: #ffffff; padding: 1px; border: 1.5px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.08);" title="<?php echo htmlspecialchars($bName); ?>" onerror="this.src='../../assets/img/banks/bcel.svg';">
                    </div>
                  <?php else: ?>
                    <span class="text-muted"><?php echo displayMoney($pdo, 0); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-center">
                  <?php
                    $__curCode = strtoupper($row['currency'] ?? 'KIP') ?: 'KIP';
                    $__curMeta = getCurrencyMeta()[$__curCode] ?? ['name' => $__curCode, 'symbol' => $__curCode];
                    $__curColor = ($__curCode === 'THB') ? '#0369a1' : (($__curCode === 'USD') ? '#047857' : (($__curCode === 'CNY') ? '#b91c1c' : '#a16207'));
                  ?>
                  <span class="badge border font-weight-bold" style="color: <?php echo $__curColor; ?>; border-color: <?php echo $__curColor; ?> !important; background: #ffffff; font-size: 0.76rem;" title="<?php echo htmlspecialchars($__curMeta['name']); ?>">
                    <?php echo htmlspecialchars($__curMeta['symbol'] . ' ' . $__curCode); ?>
                  </span>
                </td>
                <td class="text-center font-weight-bold">
                  <?php if (($row['status'] ?? 'SUCCESS') === 'CANCEL'): ?>
                    <span class="badge badge-danger px-2 py-1" style="font-size: 0.76rem;"><?php echo htmlspecialchars(t('reports.status_cancel', 'ຍົກເລີກ')); ?></span>
                  <?php else: ?>
                    <span class="badge badge-success px-2 py-1" style="font-size: 0.76rem;"><?php echo htmlspecialchars(t('reports.status_paid', 'ຈ່າຍແລ້ວ')); ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-left font-weight-bold text-dark" style="font-size: 0.84rem;"><?php echo htmlspecialchars($row['cashier']); ?></td>
                <td class="text-center no-print" style="white-space: nowrap !important;">
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>

        <?php endif; ?>
      </tbody>
      
      <!-- Table Footer Totals -->
      <?php if (!empty($sales_data)): ?>
        <tfoot style="background: #f8fafc; font-weight: 800; border-top: 2px solid #cbd5e1;">
          <tr>
            <?php if ($view_mode === 'item'): ?>
              <td colspan="3" class="text-right text-dark" style="white-space: nowrap !important;"><?php echo htmlspecialchars(t('reports.total_all_label', 'ລວມທັງໝົດ:')); ?></td>
              <td class="text-center text-dark" style="white-space: nowrap !important;"><?php echo number_format($total_qty); ?></td>
              <td class="text-right text-secondary" style="white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_gross); ?></td>
              <td class="text-right text-danger" style="white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_disc); ?></td>
              <td class="text-right text-success" style="font-size: 1.0rem; white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_net); ?></td>
            <?php else: ?>
              <td colspan="3" class="text-right text-dark" style="white-space: nowrap !important;"><?php echo htmlspecialchars(t('reports.total_all_label', 'ລວມທັງໝົດ:')); ?></td>
              <td class="text-center text-dark" style="white-space: nowrap !important;"><?php echo number_format($total_qty); ?></td>
              <td class="text-right text-secondary" style="white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_gross); ?></td>
              <td class="text-right text-danger" style="white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_disc); ?></td>
              <td class="text-right text-success" style="font-size: 1.0rem; white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_net); ?></td>
              <td class="text-right text-dark" style="white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_cash); ?></td>
              <td class="text-right text-primary" style="white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_qr); ?></td>
              <td></td>
              <td></td>
              <td colspan="2"></td>
            <?php endif; ?>
          </tr>
        </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
