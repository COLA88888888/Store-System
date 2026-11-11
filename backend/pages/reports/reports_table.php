<div class="report-table-card card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden; max-width: 100%;">
  <div class="table-responsive" style="width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;">
    <table class="table table-hover table-striped align-middle report-table mb-0" style="font-size: 0.85rem; min-width: 720px;">
      <thead style="position: sticky; top: 0; z-index: 10; background: #ffffff !important; color: #1e293b;">
        <?php if ($view_mode === 'item'): ?>
          <!-- ITEM MODE HEADERS (11 COLUMNS - WHITE) -->
          <tr style="background: #ffffff; color: #1e293b;">
            <th class="text-center" style="width: 50px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;">#</th>
            <th class="text-left" style="width: 140px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ບິນ'); ?></th>
            <th class="text-left" style="width: 100px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ວັນທີ'); ?></th>
            <th class="text-left" style="width: 120px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ສາຂາ'); ?></th>
            <th class="text-left" style="width: 110px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ລະຫັດ'); ?></th>
            <th class="text-left" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ຊື່ສິນຄ້າ'); ?></th>
            <th class="text-center" style="width: 90px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ຈຳນວນ'); ?></th>
            <th class="text-right" style="width: 110px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ລາຄາ'); ?></th>
            <th class="text-right" style="width: 100px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ສ່ວນຫຼຸດ'); ?></th>
            <th class="text-right" style="width: 130px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ສຸດທິ'); ?></th>
            <th class="text-left" style="width: 120px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ພະນັກງານ'); ?></th>
          </tr>
        <?php else: ?>
          <!-- INVOICE MODE HEADERS (14 COLUMNS - WHITE) -->
          <tr style="background: #ffffff; color: #1e293b;">
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ລຳດັບ'); ?></th>
            <th class="text-left" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ເລກບິນ'); ?></th>
            <th class="text-left" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ວັນທີ'); ?></th>
            <th class="text-left" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ສາຂາ'); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ຈຳນວນ'); ?></th>
            <th class="text-right" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ລາຄາລວມ'); ?></th>
            <th class="text-right" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ສ່ວນຫຼຸດ'); ?></th>
            <th class="text-right" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ສຸດທິ'); ?></th>
            <th class="text-right" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ເງິນສົດ'); ?></th>
            <th class="text-right" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ໂອນ'); ?></th>
            <th class="text-center" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ທະນາຄານ'); ?></th>
            <th class="text-right" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ເງິນທອນ'); ?></th>
            <th class="text-left" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ຜູ້ຂາຍ'); ?></th>
            <th class="text-center no-print" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo tlh('ຈັດການ'); ?></th>
          </tr>
        <?php endif; ?>
      </thead>
      <tbody>
        <?php if (empty($display_data)): ?>
          <tr>
            <td colspan="<?php echo $view_mode === 'item' ? '10' : '13'; ?>" class="text-center py-5">
              <div class="text-muted font-weight-bold" style="font-size: 0.95rem;"><?php echo tlh('ບໍ່ພົບຂໍ້ມູນລາຍງານການຂາຍ'); ?></div>
              <small class="text-muted"><?php echo tlh('ກະລຸນາປ່ຽນເງື່ອນໄຂການຄົ້ນຫາ ຫຼື ເລືອກຊ່ວງວັນທີໃໝ່'); ?></small>
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
              <tr style="background-color: #eff6ff; border-top: 1px solid #bfdbfe; border-bottom: 1px solid #bfdbfe;">
                <td colspan="11" class="py-2 px-3 font-weight-bold" style="color: #1e40af; font-size: 0.88rem;">
                  <i class="fas fa-receipt mr-1 text-primary"></i> Bill: <strong><?php echo htmlspecialchars($bNo); ?></strong> | Date: <strong><?php echo htmlspecialchars($dateOnly); ?></strong>
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
                  </td>

                  <!-- ວັນທີ -->
                  <td class="text-left" style="font-size: 0.84rem; color: #475569;"><?php echo htmlspecialchars($dateOnly); ?></td>

                  <!-- ສາຂາ -->
                  <td class="text-left font-weight-bold text-dark" style="font-size: 0.82rem;">
                    <span class="badge badge-light border text-primary px-2 py-1"><i class="fas fa-store mr-1"></i><?php echo htmlspecialchars($itemRow['store_name'] ?? tl('ສາຂາ')); ?></span>
                  </td>

                  <!-- ລະຫັດ -->
                  <td class="text-left font-weight-bold text-secondary"><?php echo htmlspecialchars($itemRow['pro_code']); ?></td>

                  <!-- ຊື່ສິນຄ້າ -->
                  <td class="text-left font-weight-bold text-dark"><?php echo htmlspecialchars($itemRow['pro_name']); ?></td>

                  <!-- ຈຳນວນ -->
                  <td class="text-center font-weight-bold text-dark"><?php echo number_format($itemRow['qty']); ?></td>

                  <!-- ລາຄາ -->
                  <td class="text-right font-weight-bold text-dark"><?php echo number_format($unitPrice, 0); ?></td>

                  <!-- ສ່ວນຫຼຸດ -->
                  <td class="text-right font-weight-bold text-danger"><?php echo number_format($itemRow['discount'], 0); ?></td>

                  <!-- ສຸດທິ -->
                  <td class="text-right font-weight-bold text-dark"><?php echo number_format($itemRow['net'], 0); ?></td>

                  <!-- ພະນັກງານ -->
                  <td class="text-left text-muted font-weight-bold" style="font-size: 0.84rem;"><?php echo htmlspecialchars($itemRow['cashier']); ?></td>
                </tr>
              <?php endforeach; ?>

              <!-- 3. Bill Summary Row (#fef9c3) -->
              <tr style="background-color: #fef9c3; border-bottom: 2px solid #fde047;">
                <td colspan="9" class="text-right font-weight-bold text-dark pr-3"><?php echo tlh('ລວມບິນ:'); ?></td>
                <td class="text-right font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo number_format($bGroup['bill_net'], 0); ?></td>
                <td></td>
              </tr>
            <?php endforeach; ?>

          <?php else: ?>
            <!-- INVOICE MODE ROWS (13 COLUMNS) -->
            <?php $i = $start_record; foreach ($display_data as $row): ?>
              <tr>
                <td class="text-center font-weight-bold text-muted"><?php echo $i++; ?></td>
                <td class="text-left font-weight-bold text-primary">
                  <a href="javascript:void(0)" onclick="viewBillDetails('<?php echo htmlspecialchars($row['bill_no']); ?>')" class="text-primary" title="<?php echo tlh('ເບິ່ງລາຍລະອຽດບິນ'); ?>">
                    <?php echo htmlspecialchars($row['bill_no']); ?>
                  </a>
                </td>
                <td class="text-left" style="font-size: 0.84rem; color: #475569;"><?php echo htmlspecialchars($row['date_time']); ?></td>
                <td class="text-left font-weight-bold text-dark" style="font-size: 0.82rem;">
                  <span class="badge badge-light border text-primary px-2 py-1"><i class="fas fa-store mr-1"></i><?php echo htmlspecialchars($row['store_name'] ?? tl('ສາຂາ')); ?></span>
                </td>
                <td class="text-center font-weight-bold text-dark"><?php echo number_format($row['qty']); ?></td>
                <td class="text-right font-weight-bold text-secondary"><?php echo number_format($row['gross'], 0); ?> <?php echo kipSymbol(); ?></td>
                <td class="text-right text-danger font-weight-bold"><?php echo number_format($row['discount'], 0); ?> <?php echo kipSymbol(); ?></td>
                <td class="text-right font-weight-bold text-success" style="font-size: 0.94rem;"><?php echo number_format($row['net'], 0); ?> <?php echo kipSymbol(); ?></td>
                <td class="text-right font-weight-bold text-dark"><?php echo number_format($row['cash'], 0); ?> <?php echo kipSymbol(); ?></td>
                <td class="text-right font-weight-bold text-primary"><?php echo number_format($row['qr'], 0); ?> <?php echo kipSymbol(); ?></td>
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
                  <?php else: ?>
                    <span class="text-muted font-weight-bold">-</span>
                  <?php endif; ?>
                </td>
                <td class="text-right font-weight-bold text-info"><?php echo number_format($row['change'] ?? 0, 0); ?> <?php echo kipSymbol(); ?></td>
                <td class="text-left font-weight-bold text-dark" style="font-size: 0.84rem;"><?php echo htmlspecialchars($row['cashier']); ?></td>
                <td class="text-center no-print" style="white-space: nowrap !important;">
                  <div class="d-inline-flex align-items-center" style="gap: 6px;">
                    <button type="button" class="btn btn-sm btn-light border shadow-sm text-primary" onclick="viewBillDetails('<?php echo htmlspecialchars($row['bill_no']); ?>')" title="<?php echo tlh('ລາຍລະອຽດ'); ?>" style="width: 34px; height: 34px; padding: 0; font-size: 1.05rem;">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light border shadow-sm text-info" onclick="printBill('<?php echo htmlspecialchars($row['bill_no']); ?>')" title="<?php echo tlh('ພິມໃບບິນ'); ?>" style="width: 34px; height: 34px; padding: 0; font-size: 1.05rem;">
                      <i class="fas fa-print"></i>
                    </button>
                    <?php
                      $canDeleteBill = hasPermission('delete_bills') || ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ';
                      if (($row['status'] ?? 'SUCCESS') !== 'CANCEL' && $canDeleteBill):
                    ?>
                      <button type="button" class="btn btn-sm btn-light border shadow-sm text-danger" onclick="deleteBill('<?php echo htmlspecialchars($row['bill_no']); ?>')" title="<?php echo tlh('ລຶບບິນຂາຍ'); ?>" style="width: 34px; height: 34px; padding: 0; font-size: 1.05rem;">
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
              <td colspan="6" class="text-right text-dark"><?php echo tlh('ລວມທັງໝົດ:'); ?></td>
              <td class="text-center text-dark"><?php echo number_format($total_qty); ?></td>
              <td class="text-right text-secondary"><?php echo number_format($total_gross, 0); ?> <?php echo kipSymbol(); ?></td>
              <td class="text-right text-danger"><?php echo number_format($total_disc, 0); ?> <?php echo kipSymbol(); ?></td>
              <td class="text-right text-success" style="font-size: 1.0rem;"><?php echo number_format($total_net, 0); ?> <?php echo kipSymbol(); ?></td>
              <td></td>
            <?php else: ?>
              <td colspan="4" class="text-right text-dark"><?php echo tlh('ລວມທັງໝົດ:'); ?></td>
              <td class="text-center text-dark"><?php echo number_format($total_qty); ?></td>
              <td class="text-right text-secondary"><?php echo number_format($total_gross, 0); ?> <?php echo kipSymbol(); ?></td>
              <td class="text-right text-danger"><?php echo number_format($total_disc, 0); ?> <?php echo kipSymbol(); ?></td>
              <td class="text-right text-success" style="font-size: 1.0rem;"><?php echo number_format($total_net, 0); ?> <?php echo kipSymbol(); ?></td>
              <td class="text-right text-dark"><?php echo number_format($total_cash, 0); ?> <?php echo kipSymbol(); ?></td>
              <td class="text-right text-primary"><?php echo number_format($total_qr, 0); ?> <?php echo kipSymbol(); ?></td>
              <td></td>
              <td class="text-right text-info"><?php echo number_format($total_change ?? 0, 0); ?> <?php echo kipSymbol(); ?></td>
              <td colspan="2"></td>
            <?php endif; ?>
          </tr>
        </tfoot>
      <?php endif; ?>
    </table>
  </div>
</div>
