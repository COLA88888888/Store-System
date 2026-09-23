<div class="report-table-card card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden; max-width: 100%;">
  <div class="table-responsive" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-hover table-striped align-middle report-table mb-0" style="font-size: 0.85rem; min-width: 720px;">
      <thead style="position: sticky; top: 0; z-index: 10; background: #ffffff !important; color: #1e293b;">
        <?php if ($view_mode === 'item'): ?>
          <!-- ITEM MODE HEADERS (11 COLUMNS - WHITE) -->
          <tr style="background: #ffffff; color: #1e293b;">
            <th class="text-center" style="width: 50px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;">#</th>
            <th class="text-left" style="width: 140px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_bill', 'ບິນ')); ?></th>
            <th class="text-left" style="width: 100px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_date', 'ວັນທີ')); ?></th>
            <th class="text-left" style="width: 120px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_store', 'ສາຂາ')); ?></th>
            <th class="text-left" style="width: 110px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_code', 'ລະຫັດ')); ?></th>
            <th class="text-left" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_product_name', 'ຊື່ສິນຄ້າ')); ?></th>
            <th class="text-center" style="width: 90px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_qty', 'ຈຳນວນ')); ?></th>
            <th class="text-right" style="width: 110px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_price', 'ລາຄາ')); ?></th>
            <th class="text-right" style="width: 100px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.discount_label', 'ສ່ວນຫຼຸດ')); ?></th>
            <th class="text-right" style="width: 130px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_net', 'ສຸດທິ')); ?></th>
            <th class="text-left" style="width: 120px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_employee', 'ພະນັກງານ')); ?></th>
          </tr>
        <?php else: ?>
          <!-- INVOICE MODE HEADERS (14 COLUMNS - WHITE) -->
          <tr style="background: #ffffff; color: #1e293b;">
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_no', 'ລຳດັບ')); ?></th>
            <th class="text-left" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_bill_no', 'ເລກບິນ')); ?></th>
            <th class="text-left" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_date', 'ວັນທີ')); ?></th>
            <th class="text-left" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_store', 'ສາຂາ')); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_qty', 'ຈຳນວນ')); ?></th>
            <th class="text-right" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_gross', 'ລາຄາລວມ')); ?></th>
            <th class="text-right" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.discount_label', 'ສ່ວນຫຼຸດ')); ?></th>
            <th class="text-right" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_net', 'ສຸດທິ')); ?></th>
            <th class="text-right" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_cash', 'ເງິນສົດ')); ?></th>
            <th class="text-right" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_transfer', 'ໂອນ')); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_bank', 'ທະນາຄານ')); ?></th>
            <th class="text-center" style="width: 100px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_currency', 'ສະກຸນເງິນ')); ?></th>
            <th class="text-right" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_change', 'ເງິນທອນ')); ?></th>
            <th class="text-left" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_seller', 'ຜູ້ຂາຍ')); ?></th>
            <th class="text-center no-print" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_manage', 'ຈັດການ')); ?></th>
          </tr>
        <?php endif; ?>
      </thead>
      <tbody>
        <?php if (empty($display_data)): ?>
          <tr>
            <td colspan="<?php echo $view_mode === 'item' ? '11' : '15'; ?>" class="text-center py-5">
              <div class="text-muted font-weight-bold" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('reports.no_sales_data', 'ບໍ່ພົບຂໍ້ມູນລາຍງານການຂາຍ')); ?></div>
              <small class="text-muted"><?php echo htmlspecialchars(t('reports.change_filter_hint', 'ກະລຸນາປ່ຽນເງື່ອນໄຂການຄົ້ນຫາ ຫຼື ເລືອກຊ່ວງວັນທີໃໝ່')); ?></small>
            </td>
          </tr>
        <?php else: ?>

          <?php if ($view_mode === 'item'): ?>
            <!-- GROUPED ITEM VIEW MODE MATCHING USER SCREENSHOT -->
            <?php 
            $itemSeq = $start_record;
            foreach ($grouped_bills as $bNo => $bGroup): 
              $dateOnly = explode(' ', $bGroup['date_time'])[0];
            ?>
              <!-- 1. Bill Banner Header Row (#eff6ff / #dbeafe) -->
              <?php
                $__curCode = strtoupper($bGroup['currency'] ?? 'KIP') ?: 'KIP';
                $__curMeta = getCurrencyMeta()[$__curCode] ?? ['name' => $__curCode, 'symbol' => $__curCode];
                $__curColor = ($__curCode === 'THB') ? '#0369a1' : (($__curCode === 'USD') ? '#047857' : (($__curCode === 'CNY') ? '#b91c1c' : '#a16207'));
              ?>
              <tr style="background-color: #eff6ff; border-top: 1px solid #bfdbfe; border-bottom: 1px solid #bfdbfe;">
                <td colspan="11" class="py-2 px-3 font-weight-bold" style="color: #1e40af; font-size: 0.88rem;">
                  <i class="fas fa-receipt mr-1 text-primary"></i> Bill: <strong><?php echo htmlspecialchars($bNo); ?></strong> | Date: <strong><?php echo htmlspecialchars($dateOnly); ?></strong>
                  <?php if ($__curCode !== 'KIP'): ?>
                    <span class="badge border font-weight-bold ml-2" style="color: <?php echo $__curColor; ?>; border-color: <?php echo $__curColor; ?> !important; background: #ffffff; font-size: 0.74rem;" title="<?php echo htmlspecialchars($__curMeta['name']); ?>">
                      <?php echo htmlspecialchars($__curMeta['symbol'] . ' ' . $__curCode); ?>
                    </span>
                  <?php endif; ?>
                </td>
              </tr>

              <!-- 2. Items under this bill -->
              <?php foreach ($bGroup['items'] as $itemRow): ?>
                <?php 
                  $unitPrice = ($itemRow['qty'] > 0 && isset($itemRow['price']) && $itemRow['price'] > 0) 
                               ? $itemRow['price'] 
                               : ($itemRow['gross'] / max(1, $itemRow['qty']));
                ?>
                <tr style="background-color: #ffffff;">
                  <!-- # -->
                  <td class="text-center font-weight-bold text-muted"><?php echo $itemSeq++; ?></td>
                  
                  <!-- ບິນ -->
                  <td class="text-left font-weight-bold text-primary">
                    <a href="javascript:void(0)" onclick="viewBillDetails('<?php echo htmlspecialchars($itemRow['bill_no']); ?>')" class="text-primary">
                      <?php echo htmlspecialchars($itemRow['bill_no']); ?>
                    </a>
                    <?php if (strpos($itemRow['sale_remark'] ?? '', 'Quotation To Sale') !== false): ?>
                      <span class="badge badge-warning text-dark ml-1" style="font-size: 0.65rem;"><i class="fas fa-file-invoice mr-1"></i> ໃບສະເໜີລາຄາ</span>
                    <?php endif; ?>
                  </td>

                  <!-- ວັນທີ -->
                  <td class="text-left" style="font-size: 0.84rem; color: #475569;"><?php echo htmlspecialchars($dateOnly); ?></td>

                  <!-- ສາຂາ -->
                  <td class="text-left font-weight-bold text-dark" style="font-size: 0.82rem;">
                    <span class="badge badge-light border text-primary px-2 py-1"><i class="fas fa-store mr-1"></i><?php echo htmlspecialchars($itemRow['store_name'] ?? t('reports.col_store', 'ສາຂາ')); ?></span>
                  </td>

                  <!-- ລະຫັດ -->
                  <td class="text-left font-weight-bold text-secondary"><?php echo htmlspecialchars($itemRow['pro_code']); ?></td>

                  <!-- ຊື່ສິນຄ້າ -->
                  <td class="text-left font-weight-bold text-dark"><?php echo htmlspecialchars($itemRow['pro_name']); ?></td>

                  <!-- ຈຳນວນ -->
                  <td class="text-center font-weight-bold text-dark"><?php echo number_format($itemRow['qty']); ?></td>

                  <!-- ລາຄາ -->
                  <td class="text-right font-weight-bold text-dark"><?php echo displayMoney($pdo, $unitPrice, false); ?></td>

                  <!-- ສ່ວນຫຼຸດ -->
                  <td class="text-right font-weight-bold text-danger"><?php echo displayMoney($pdo, $itemRow['discount'], false); ?></td>

                  <!-- ສຸດທິ -->
                  <td class="text-right font-weight-bold text-dark"><?php echo displayMoney($pdo, $itemRow['net'], false); ?></td>

                  <!-- ພະນັກງານ -->
                  <td class="text-left text-muted font-weight-bold" style="font-size: 0.84rem;"><?php echo htmlspecialchars($itemRow['cashier']); ?></td>
                </tr>
              <?php endforeach; ?>

              <!-- 3. Bill Summary Row (#fef9c3) -->
              <tr style="background-color: #fef9c3; border-bottom: 2px solid #fde047;">
                <td colspan="9" class="text-right font-weight-bold text-dark pr-3"><?php echo htmlspecialchars(t('reports.bill_total_label', 'ລວມບິນ:')); ?></td>
                <td class="text-right font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo displayMoney($pdo, $bGroup['bill_net'], false); ?></td>
                <td></td>
              </tr>
            <?php endforeach; ?>

          <?php else: ?>
            <!-- INVOICE MODE ROWS (13 COLUMNS) -->
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
                <td class="text-left font-weight-bold text-dark" style="font-size: 0.82rem;">
                  <span class="badge badge-light border text-primary px-2 py-1"><i class="fas fa-store mr-1"></i><?php echo htmlspecialchars($row['store_name'] ?? t('reports.col_store', 'ສາຂາ')); ?></span>
                </td>
                <td class="text-center font-weight-bold text-dark"><?php echo number_format($row['qty']); ?></td>
                <td class="text-right font-weight-bold text-secondary"><?php echo displayMoney($pdo, $row['gross']); ?></td>
                <td class="text-right text-danger font-weight-bold"><?php echo displayMoney($pdo, $row['discount']); ?></td>
                <td class="text-right font-weight-bold text-success" style="font-size: 0.94rem;"><?php echo displayMoney($pdo, $row['net']); ?></td>
                <td class="text-right font-weight-bold text-dark"><?php echo displayMoney($pdo, $row['cash']); ?></td>
                <td class="text-right font-weight-bold text-primary"><?php echo displayMoney($pdo, $row['qr']); ?></td>
                <td class="text-center align-middle">
                  <?php if (!empty($row['qr']) && $row['qr'] > 0): ?>
                    <?php
                      $bName = !empty($row['bank_name']) ? $row['bank_name'] : '';
                      $bCode = !empty($row['bank_code']) ? $row['bank_code'] : $bName;
                      $code = strtoupper(trim($bCode));
                      $logoPath = resolveBankLogo($row['bank_logo'] ?? '', $code);
                    ?>
                    <?php if (!empty($logoPath)): ?>
                      <img src="<?php echo htmlspecialchars($logoPath); ?>" style="width: 24px; height: 24px; object-fit: cover; border-radius: 50% !important; background: #ffffff; padding: 1px; border: 1.5px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.08);" title="<?php echo htmlspecialchars($bName ?: $code); ?>" onerror="this.style.display='none';">
                    <?php else: ?>
                      <!-- No bank logo on file (or the bill's bank_account_id never resolved,
                           e.g. this branch has no bank account configured) — a generic icon
                           beats an <img> with an empty src, which some browsers render as a
                           blank circle instead of triggering onerror. -->
                      <span class="d-inline-flex align-items-center justify-content-center" style="width: 24px; height: 24px; border-radius: 50%; background: #f1f5f9; border: 1.5px solid #cbd5e1;" title="<?php echo htmlspecialchars($bName ?: $code); ?>">
                        <i class="fas fa-university text-muted" style="font-size: 0.7rem;"></i>
                      </span>
                    <?php endif; ?>
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
                <td class="text-right font-weight-bold text-info"><?php echo displayMoney($pdo, $row['change'] ?? 0); ?></td>
                <td class="text-left font-weight-bold text-dark" style="font-size: 0.84rem;"><?php echo htmlspecialchars($row['cashier']); ?></td>
                <td class="text-center no-print" style="white-space: nowrap !important;">
                  <div class="d-inline-flex align-items-center" style="gap: 6px;">
                    <button type="button" class="btn btn-sm btn-light border shadow-sm text-primary" onclick="viewBillDetails('<?php echo htmlspecialchars($row['bill_no']); ?>')" title="<?php echo htmlspecialchars(t('reports.detail_opt', 'ລາຍລະອຽດ')); ?>" style="width: 34px; height: 34px; padding: 0; font-size: 1.05rem;">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light border shadow-sm text-info" onclick="printBill('<?php echo htmlspecialchars($row['bill_no']); ?>')" title="<?php echo htmlspecialchars(t('reports.print_bill_title', 'ພິມໃບບິນ')); ?>" style="width: 34px; height: 34px; padding: 0; font-size: 1.05rem;">
                      <i class="fas fa-print"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light border shadow-sm text-success" onclick="printDeliveryNote('<?php echo htmlspecialchars($row['bill_no']); ?>')" title="<?php echo htmlspecialchars(t('reports.print_delivery_note_title', 'ພິມບິນສົ່ງເຄື່ອງ')); ?>" style="width: 34px; height: 34px; padding: 0; font-size: 1.05rem;">
                      <i class="fas fa-truck"></i>
                    </button>
                    <?php
                      $canDeleteBill = hasPermission('delete_bills') || ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ';
                      if (($row['status'] ?? 'SUCCESS') !== 'CANCEL' && $canDeleteBill):
                    ?>
                      <button type="button" class="btn btn-sm btn-light border shadow-sm text-danger" onclick="deleteBill('<?php echo htmlspecialchars($row['bill_no']); ?>')" title="<?php echo htmlspecialchars(t('reports.delete_bill_title', 'ລຶບບິນຂາຍ')); ?>" style="width: 34px; height: 34px; padding: 0; font-size: 1.05rem;">
                        <i class="fas fa-trash-alt"></i>
                      </button>
                    <?php endif; ?>
                  </div>
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
              <td colspan="6" class="text-right text-dark" style="white-space: nowrap !important;"><?php echo htmlspecialchars(t('reports.total_all_label', 'ລວມທັງໝົດ:')); ?></td>
              <td class="text-center text-dark" style="white-space: nowrap !important;"><?php echo number_format($total_qty); ?></td>
              <td class="text-right text-secondary" style="white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_gross); ?></td>
              <td class="text-right text-danger" style="white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_disc); ?></td>
              <td class="text-right text-success" style="font-size: 1.0rem; white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_net); ?></td>
              <td></td>
            <?php else: ?>
              <td colspan="4" class="text-right text-dark" style="white-space: nowrap !important;"><?php echo htmlspecialchars(t('reports.total_all_label', 'ລວມທັງໝົດ:')); ?></td>
              <td class="text-center text-dark" style="white-space: nowrap !important;"><?php echo number_format($total_qty); ?></td>
              <td class="text-right text-secondary" style="white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_gross); ?></td>
              <td class="text-right text-danger" style="white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_disc); ?></td>
              <td class="text-right text-success" style="font-size: 1.0rem; white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_net); ?></td>
              <td class="text-right text-dark" style="white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_cash); ?></td>
              <td class="text-right text-primary" style="white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_qr); ?></td>
              <td></td>
              <td></td>
              <td class="text-right text-info" style="white-space: nowrap !important;"><?php echo displayMoney($pdo, $total_change ?? 0); ?></td>
              <td colspan="2"></td>
            <?php endif; ?>
          </tr>
        </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
