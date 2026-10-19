<?php
/*
 * ສ່ວນ: ສ່ວນສະແດງຜົນ
 * ຫົວໜ້າ, ປຸ່ມສົ່ງອອກ, ຕົວກັ່ນຕອງ, ຕາຕະລາງ (ສະຖານະ ຍັງບໍ່ທັນຈ່າຍ/ຍັງບໍ່ຄົບ/ຈ່າຍແລ້ວ ພ້ອມຈຳນວນ)
 * ແລະ pagination.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/financial.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><div class="container-fluid p-3 p-md-4">
  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="row-gap: 10px;">
    <div>
      <h5 class="font-weight-bold text-dark mb-0" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-wallet text-info mr-2"></i> <?php echo htmlspecialchars(t('reports.title_financial', 'ລາຍງານການເງິນ')); ?>
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
    <form method="GET" action="financial.php" class="d-flex flex-column flex-md-row align-items-md-end flex-wrap" style="gap: 12px;">
      <!-- Per Page Dropdown (ໂຊລາຍການ - ໜ້າສຸດ) -->
      <div style="flex: 1 1 140px; width: 100%;">
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
      <div style="flex: 1 1 140px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-alt text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.from_date', 'ຕັ້ງແຕ່ວັນທີ:')); ?>
        </label>
        <input type="date" name="from_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($from_date); ?>" style="border-radius: 8px; height: 38px; width: 100%;">
      </div>

      <!-- To Date (ຫາວັນທີ) -->
      <div style="flex: 1 1 140px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-calendar-check text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.to_date', 'ຫາວັນທີ:')); ?>
        </label>
        <input type="date" name="to_date" class="form-control form-control-sm font-weight-bold" value="<?php echo htmlspecialchars($to_date); ?>" style="border-radius: 8px; height: 38px; width: 100%;">
      </div>

      <!-- Branch Filter (ເລືອກສາຂາ) — only worth showing once there's more than one branch -->
      <?php if (canViewAllBranches($pdo) && count($branchesList) > 1): ?>
      <div style="flex: 1 1 150px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-store text-info mr-1"></i> <?php echo htmlspecialchars(t('reports.branch_label', 'ສາຂາ:')); ?>
        </label>
        <select name="store_id" class="form-control form-control-sm font-weight-bold" style="border-radius: 8px; height: 38px; width: 100%;" onchange="this.form.submit()">
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

      <!-- Search Input (ຄົ້ນຫາ ເລກບິນ / ຊື່) -->
      <div style="flex: 1 1 180px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-search text-primary mr-1"></i> <?php echo htmlspecialchars(t('reports.search_bill_seller_label', 'ຄົ້ນຫາ (ເລກບິນ / ຊື່):')); ?>
        </label>
        <input type="text" name="search" id="financialSearchInput" class="form-control form-control-sm" placeholder="<?php echo htmlspecialchars(t('reports.search_placeholder_bill_emp', 'ປ້ອນເລກບິນ ຫຼື ຊື່ພະນັກງານ...')); ?>" value="<?php echo htmlspecialchars($search); ?>" oninput="debounceSubmit(this.form)" style="border-radius: 8px; height: 38px; width: 100%;">
      </div>

      <!-- Currency Filter (ສະກຸນເງິນ) -->
      <div style="flex: 1 1 150px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block" style="font-size: 0.82rem; white-space: nowrap;">
          <i class="fas fa-coins text-warning mr-1"></i> <?php echo htmlspecialchars(t('reports.currency_label', 'ສະກຸນເງິນ:')); ?>
        </label>
        <select name="currency_filter" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()" style="border-radius: 8px; height: 38px; font-size: 0.85rem; border: 1.5px solid #a16207; color: #854d0e; background: #fefce8; width: 100%;">
          <option value=""><?php echo htmlspecialchars(t('reports.all_currencies_opt', '-- ທຸກສະກຸນເງິນ --')); ?></option>
          <?php foreach (getCurrencyMeta() as $__cc => $__ci): ?>
            <option value="<?php echo htmlspecialchars($__cc); ?>" <?php echo $currency_filter === $__cc ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($__ci['name'] . ' (' . $__ci['symbol'] . ')'); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Action Buttons (ຄົ້ນຫາ & ຣີໂຫລດ) -->
      <div style="flex: 1 1 140px; width: 100%;">
        <label class="font-weight-bold text-dark mb-1 d-block d-md-none" style="font-size: 0.82rem; visibility: hidden;">&nbsp;</label>
        <div class="d-flex align-items-center" style="gap: 8px; width: 100%;">
          <button type="submit" class="btn btn-primary btn-sm font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); flex: 1;">
            <i class="fas fa-search mr-1.5"></i> <?php echo htmlspecialchars(t('reports.search_btn', 'ຄົ້ນຫາ')); ?>
          </button>
          <a href="financial.php" class="btn btn-light btn-sm border font-weight-bold px-3 d-inline-flex align-items-center justify-content-center" style="border-radius: 8px; height: 38px;" title="<?php echo htmlspecialchars(t('reports.clear', 'ລ້າງຄ່າ')); ?>">
            <i class="fas fa-redo"></i>
          </a>
        </div>
      </div>
    </form>
  </div>

  <!-- Financial Table Card (11 Columns) -->
  <div class="card border-0 shadow-sm" style="border-radius: 12px; overflow: hidden;">
    <div class="card-body p-0">
      <div class="table-responsive" style="overflow: visible;">
        <table class="table table-hover align-middle mb-0 text-nowrap" style="font-size: 0.88rem;">
          <thead style="background-color: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;">
            <tr style="background: #ffffff; color: #1e293b;">
              <th class="text-center py-3" style="width: 50px; background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_no', 'ລຳດັບ')); ?></th>
              <th class="py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_bill_no', 'ເລກບິນ')); ?></th>
              <th class="py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_date_alt', 'ວັນທີ່')); ?></th>
              <th class="text-center py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.discount_label', 'ສ່ວນຫຼຸດ')); ?></th>
              <th class="text-right py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_cash', 'ເງິນສົດ')); ?></th>
              <th class="text-right py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.transfer_opt', 'ເງິນໂອນ')); ?></th>
              <th class="text-right py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_change', 'ເງິນທອນ')); ?></th>
              <th class="text-right py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_received', 'ຮັບຈິງ')); ?></th>
              <th class="text-center py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_status', 'ສະຖານະ')); ?></th>
              <th class="text-center py-3" style="background: #ffffff; color: #1e293b; border-bottom: 2px solid #e2e8f0;"><?php echo htmlspecialchars(t('reports.col_time', 'ເວລາ')); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($finances)): ?>
              <tr>
                <td colspan="10" class="text-center py-5 text-muted">
                  <i class="fas fa-wallet fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                  <?php echo htmlspecialchars(t('reports.no_financial_data', 'ບໍ່ພົບຂໍ້ມູນລາຍງານການເງິນ')); ?>
                </td>
              </tr>
            <?php else: ?>
              <?php
                // ຂາຍຕິດໜີ້: debt state per bill on this page — paid so far (deposit + repayments)
                // and what is still owed. Cancelled debts are ignored (the bill shows ຍົກເລີກ).
                $finDebts = [];
                $finRepaid = [];
                $finBills = array_values(array_filter(array_column($finances, 'sale_save_bill')));
                if ($finBills) {
                    try {
                        $fd = $pdo->prepare("SELECT invoice_number, SUM(deposit_amount + paid_amount) AS paid, SUM(balance) AS balance,
                                                    SUM(status IN ('unpaid','partial')) AS open_cnt, SUM(paid_amount) AS repaid
                                             FROM st_customer_debts WHERE status <> 'cancelled'
                                               AND invoice_number IN (" . implode(',', array_fill(0, count($finBills), '?')) . ")
                                             GROUP BY invoice_number");
                        $fd->execute($finBills);
                        foreach ($fd->fetchAll(PDO::FETCH_ASSOC) as $d) $finDebts[$d['invoice_number']] = $d;

                        // Repayments since the sale, per channel — added to the bill's ເງິນສົດ / ໂອນ below
                        $fr = $pdo->prepare("SELECT d.invoice_number,
                                                    SUM(CASE WHEN p.bank_account_id IS NULL AND p.payment_method = 'ເງິນສົດ' THEN p.amount ELSE 0 END) AS cash,
                                                    SUM(CASE WHEN p.bank_account_id IS NULL AND p.payment_method = 'ເງິນສົດ' THEN 0 ELSE p.amount END) AS qr
                                             FROM st_customer_debt_payments p JOIN st_customer_debts d ON d.id = p.debt_id
                                             WHERE d.status <> 'cancelled' AND d.invoice_number IN (" . implode(',', array_fill(0, count($finBills), '?')) . ")
                                             GROUP BY d.invoice_number");
                        $fr->execute($finBills);
                        foreach ($fr->fetchAll(PDO::FETCH_ASSOC) as $d) $finRepaid[$d['invoice_number']] = $d;
                    } catch (Throwable $e) {}
                }
              ?>
              <?php foreach ($finances as $idx => $row): ?>
                <?php
                  $net     = floatval($row['sale_barlance'] ?? 0);
                  $pay     = floatval($row['sale_pay'] ?? 0);
                  $ret     = floatval($row['sale_return'] ?? 0);
                  $typePay = $row['type_pay'] ?? '';

                  if ($hasCashCol && $hasQrCol && (floatval($row['cash_received'] ?? 0) > 0 || floatval($row['qr_received'] ?? 0) > 0)) {
                      $cash = floatval($row['cash_received']);
                      $qr   = floatval($row['qr_received']);
                  } elseif ($typePay === 'ຕິດໜີ້') {
                      // Credit sale with no deposit: nothing was received at the till.
                      $cash = 0;
                      $qr   = 0;
                  } else {
                      if (mb_strpos($typePay, 'ໂອນ') !== false || mb_strpos($typePay, 'QR') !== false) {
                          if (mb_strpos($typePay, 'ເງິນສົດ') !== false) {
                              $cash = max(0, $pay - $ret);
                              $qr   = max(0, $net - $cash);
                          } else {
                              $cash = 0;
                              $qr   = $net;
                          }
                      } else {
                          $cash = $net;
                          $qr   = 0;
                      }
                  }

                  $change  = max(0, $ret);
                  $received = max(0, $pay);
                  // Credit bill: repayments taken since count in their own column (cash / transfer)
                  if ($typePay === 'ຕິດໜີ້' && isset($finRepaid[$row['sale_save_bill']])) {
                      $rp = $finRepaid[$row['sale_save_bill']];
                      $cash     += (float)$rp['cash'];
                      $qr       += (float)$rp['qr'];
                      $received += (float)$rp['cash'] + (float)$rp['qr'];
                  }
                  $status  = $row['sale_status'] ?? 'SUCCESS';
                  $time    = substr($row['sale_time'] ?? '00:00', 0, 5);
                  $dateFormatted = date('d/m/Y', strtotime($row['sale_date']));
                ?>
                <tr>
                  <td class="text-center font-weight-bold text-muted"><?php echo $offset + $idx + 1; ?></td>
                  <td class="font-weight-bold text-primary">
                    <?php echo htmlspecialchars($row['sale_save_bill']); ?>
                    <?php if (!empty($finDebts[$row['sale_save_bill']]) && (int)$finDebts[$row['sale_save_bill']]['open_cnt'] > 0): ?>
                      <div style="color: #dc2626; font-weight: 700; font-size: 0.78rem; white-space: nowrap;"><i class="fas fa-hand-holding-usd mr-1"></i><?php echo htmlspecialchars(t('reports.debt_badge', 'ຕິດໜີ້')); ?></div>
                    <?php endif; ?>
                  </td>
                  <td class="text-left" style="font-size: 0.84rem; color: #475569;"><?php echo $dateFormatted; ?></td>
                  <td class="text-center">
                    <?php $billDiscount = floatval($row['sale_discount_bill'] ?? 0); ?>
                    <?php if ($billDiscount > 0): ?>
                      <span class="badge badge-danger font-weight-bold px-2 py-1" style="font-size: 0.76rem; border-radius: 6px;">
                        -<?php echo displayMoney($pdo, $billDiscount); ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-right font-weight-bold text-dark"><?php echo displayMoney($pdo, $cash); ?></td>
                  <td class="text-right font-weight-bold text-primary"><?php echo displayMoney($pdo, $qr); ?></td>
                  <td class="text-right font-weight-bold text-info"><?php echo displayMoney($pdo, $change); ?></td>
                  <td class="text-right font-weight-bold text-success" style="font-size: 0.94rem;"><?php echo displayMoney($pdo, $received); ?></td>
                  <td class="text-center font-weight-bold">
                    <?php $fDebt = $finDebts[$row['sale_save_bill']] ?? null; ?>
                    <?php if ($status === 'CANCEL'): ?>
                      <span class="badge badge-danger px-2 py-1" style="font-size: 0.76rem;"><?php echo htmlspecialchars(t('reports.status_cancel', 'ຍົກເລີກ')); ?></span>
                    <?php elseif ($fDebt && (int)$fDebt['open_cnt'] > 0): ?>
                      <?php $fPartial = (float)$fDebt['repaid'] > 0; ?>
                      <span class="badge px-2 py-1" style="font-size: 0.76rem; color: #fff; background: <?php echo $fPartial ? '#f59e0b' : '#dc2626'; ?>;">
                        <?php echo htmlspecialchars($fPartial ? t('reports.status_partial', 'ຍັງບໍ່ຄົບ') : t('reports.status_unpaid', 'ຍັງບໍ່ທັນຈ່າຍ')); ?>
                      </span>
                      <div style="font-size: 0.74rem; line-height: 1.45; margin-top: 3px; text-align: left; display: inline-block;">
                        <div style="color: #15803d;"><?php echo htmlspecialchars(t('reports.debt_paid_label', 'ຈ່າຍແລ້ວ:')); ?> <?php echo displayMoney($pdo, $fDebt['paid']); ?></div>
                        <div style="color: #dc2626; font-weight: 700;"><?php echo htmlspecialchars(t('reports.debt_owed_label', 'ຄ້າງ:')); ?> <?php echo displayMoney($pdo, $fDebt['balance']); ?></div>
                      </div>
                    <?php else: ?>
                      <span class="badge badge-success px-2 py-1" style="font-size: 0.76rem;"><?php echo htmlspecialchars(t('reports.status_paid', 'ຈ່າຍແລ້ວ')); ?></span>
                    <?php endif; ?>
                  </td>
                  <td class="text-center font-weight-bold text-dark" style="font-size: 0.84rem;"><?php echo $time; ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <?php if (!empty($finances)): ?>
            <tfoot style="background: #f8fafc; border-top: 2px solid #cbd5e1;">
              <tr class="font-weight-bold" style="font-size: 0.9rem; color: #0f172a;">
                <td colspan="4" class="text-center py-3 text-uppercase font-weight-bold" style="background: #f8fafc; color: #0f172a;"><?php echo htmlspecialchars(t('reports.total_all_label', 'ລວມທັງໝົດ:')); ?></td>
                <td class="text-right py-3 text-dark font-weight-bold" style="background: #f8fafc; color: #0f172a;" id="tot_cash">0 ₭</td>
                <td class="text-right py-3 text-primary font-weight-bold" style="background: #f8fafc;" id="tot_qr">0 ₭</td>
                <td class="text-right py-3 text-info font-weight-bold" style="background: #f8fafc;" id="tot_change">0 ₭</td>
                <td class="text-right py-3 text-success font-weight-bold" style="background: #f8fafc; font-size: 0.96rem;" id="tot_rec">0 ₭</td>
                <td colspan="2" style="background: #f8fafc;"></td>
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
            <a class="page-link" href="financial.php?page=<?php echo max(1, $page - 1); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo urlencode($per_page_raw); ?>&currency_filter=<?php echo urlencode($currency_filter); ?>">
              <i class="fas fa-chevron-left" style="font-size: 0.76rem;"></i>
            </a>
          </li>

          <?php
            $range = 2;
            $startP = max(1, $page - $range);
            $endP   = min($total_pages, $page + $range);

            if ($startP > 1) {
                echo '<li class="page-item"><a class="page-link" href="financial.php?page=1&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&search=' . urlencode($search) . '&per_page=' . urlencode($per_page_raw) . '&currency_filter=' . urlencode($currency_filter) . '">1</a></li>';
                if ($startP > 2) {
                    echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
                }
            }

            for ($p = $startP; $p <= $endP; $p++) {
                $activeClass = ($p == $page) ? 'active' : '';
                echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="financial.php?page=' . $p . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&search=' . urlencode($search) . '&per_page=' . urlencode($per_page_raw) . '&currency_filter=' . urlencode($currency_filter) . '">' . $p . '</a></li>';
            }

            if ($endP < $total_pages) {
                if ($endP < $total_pages - 1) {
                    echo '<li class="page-item disabled"><span class="page-link" style="border:none;">...</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="financial.php?page=' . $total_pages . '&from_date=' . urlencode($from_date) . '&to_date=' . urlencode($to_date) . '&search=' . urlencode($search) . '&per_page=' . urlencode($per_page_raw) . '&currency_filter=' . urlencode($currency_filter) . '">' . $total_pages . '</a></li>';
            }
          ?>

          <!-- Next Page -->
          <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
            <a class="page-link" href="financial.php?page=<?php echo min($total_pages, $page + 1); ?>&from_date=<?php echo urlencode($from_date); ?>&to_date=<?php echo urlencode($to_date); ?>&search=<?php echo urlencode($search); ?>&per_page=<?php echo urlencode($per_page_raw); ?>&currency_filter=<?php echo urlencode($currency_filter); ?>">
              <i class="fas fa-chevron-right" style="font-size: 0.76rem;"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</div>

