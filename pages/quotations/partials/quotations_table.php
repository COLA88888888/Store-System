<!-- Table Card: ໃບສະເໜີລາຄາ -->
<div class="card border-0 shadow-sm" style="border-radius: 12px; border: 1.5px solid #e2e8f0; background: #ffffff;">
  <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap py-3" style="gap: 12px;">
    <span class="badge badge-secondary" style="font-size: 0.85rem; padding: 7px 12px;"><span id="quoteCountNum"><?php echo count($quotations); ?></span> <?php echo htmlspecialchars(t('quotations.count_label', 'ລາຍການ')); ?></span>
    <div class="d-flex align-items-center ml-auto flex-grow-1 justify-content-end" style="gap: 8px; max-width: 480px;">
      <div class="input-group" style="flex: 1 1 auto;">
        <div class="input-group-prepend">
          <span class="input-group-text bg-white" style="border-radius: 8px 0 0 8px; border-right: none;"><i class="fas fa-search text-muted"></i></span>
        </div>
        <input type="text" id="quoteSearch" class="form-control border-left-0" placeholder="<?php echo htmlspecialchars(t('quotations.search_ph', 'ຄົ້ນຫາ ເລກທີ/ຊື່ລູກຄ້າ...')); ?>" onkeyup="filterQuotations()" style="height: 44px; font-size: 0.95rem; border-radius: 0 8px 8px 0; border-left: none;">
      </div>
      <button type="button" id="quoteSearchClear" class="btn btn-light border d-flex align-items-center justify-content-center" style="border-radius: 8px; height: 44px; width: 44px;" title="<?php echo htmlspecialchars(t('quotations.clear', 'ລ້າງຄ່າ')); ?>" onclick="document.getElementById('quoteSearch').value='';filterQuotations();document.getElementById('quoteSearch').focus();"><i class="fas fa-redo"></i></button>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="quoteTable" style="font-size: 0.88rem;">
      <thead style="background: #f8fafc; color: #475569; font-size: 0.82rem;" class="font-weight-bold">
        <tr style="white-space: nowrap;">
          <th class="text-center" style="width: 55px;"><?php echo htmlspecialchars(t('quotations.col_no', 'ລຳດັບ')); ?></th>
          <th><?php echo htmlspecialchars(t('quotations.col_quote_no', 'ເລກທີໃບສະເໜີ')); ?></th>
          <th><?php echo htmlspecialchars(t('quotations.col_customer', 'ລູກຄ້າ')); ?></th>
          <th class="text-center"><?php echo htmlspecialchars(t('quotations.col_date', 'ວັນທີ')); ?></th>
          <th class="text-right"><?php echo htmlspecialchars(t('quotations.col_total', 'ຍອດລວມ')); ?></th>
          <th class="text-center" style="width: 120px;"><?php echo htmlspecialchars(t('quotations.col_status', 'ສະຖານະ')); ?></th>
          <th><?php echo htmlspecialchars(t('quotations.col_creator', 'ຜູ້ສ້າງ')); ?></th>
          <th class="text-center" style="width: 190px;"><?php echo htmlspecialchars(t('quotations.col_action', 'ຈັດການ')); ?></th>
        </tr>
      </thead>
      <tbody>
        <tr id="quoteNoResult" style="display: none;">
          <td colspan="9" class="text-center text-muted py-4">
            <i class="fas fa-search fa-lg mb-2 d-block opacity-50"></i>
            <?php echo htmlspecialchars(t('quotations.no_result', 'ບໍ່ພົບໃບສະເໜີລາຄາທີ່ຄົ້ນຫາ')); ?>
          </td>
        </tr>
        <?php if (empty($quotations)): ?>
          <tr>
            <td colspan="9" class="text-center py-5 text-muted">
              <i class="fas fa-file-invoice-dollar fa-2x mb-2 d-block text-secondary opacity-50"></i>
              <?php echo htmlspecialchars(t('quotations.empty_state', 'ຍັງບໍ່ມີໃບສະເໜີລາຄາ')); ?>
            </td>
          </tr>
        <?php else: ?>
          <?php $i = 1; foreach ($quotations as $q):
            $qSearchAttr = strtolower(($q['quotation_no'] ?? '') . ' ' . ($q['customer_name'] ?? '') . ' ' . ($q['customer_tel'] ?? ''));
            $statusMap = [
              'pending'   => ['badge-warning', t('quotations.status_pending', 'ລໍຖ້າຕອບຮັບ')],
              'accepted'  => ['badge-info', t('quotations.status_accepted', 'ລູກຄ້າຮັບ')],
              'rejected'  => ['badge-danger', t('quotations.status_rejected', 'ຍົກເລີກ')],
              'expired'   => ['badge-secondary', t('quotations.status_expired', 'ໝົດອາຍຸ')],
              'converted' => ['badge-success', t('quotations.status_converted', 'ຂາຍແລ້ວ')],
            ];
            $stKey = $q['status'] ?? 'pending';
            $stBadge = $statusMap[$stKey] ?? $statusMap['pending'];
            $isConverted = ($stKey === 'converted');
            $qJson = htmlspecialchars(json_encode($q), ENT_QUOTES, 'UTF-8');
          ?>
            <tr class="quote-row" data-s="<?php echo htmlspecialchars($qSearchAttr); ?>">
              <td class="text-center text-muted font-weight-bold row-no"><?php echo $i++; ?></td>
              <td class="font-weight-bold text-dark" style="font-family: monospace;"><?php echo htmlspecialchars($q['quotation_no']); ?></td>
              <td class="font-weight-bold text-dark"><?php echo htmlspecialchars($q['customer_name']); ?></td>
              <td class="text-center text-muted" style="white-space: nowrap;"><?php echo !empty($q['quote_date']) ? date('d/m/Y', strtotime($q['quote_date'])) : '-'; ?></td>
              <td class="text-right font-weight-bold text-primary"><?php echo htmlspecialchars($quoteFmtMoney((float)$q['total_amount']), ENT_QUOTES); ?></td>
              <td class="text-center"><span class="badge <?php echo $stBadge[0]; ?> px-2 py-1"><?php echo htmlspecialchars($stBadge[1]); ?></span></td>
              <td class="text-muted small"><?php echo htmlspecialchars($q['creator_name'] ?? '-'); ?></td>
              <td class="text-center">
                <div class="btn-group btn-group-sm">
                  <a href="print_quotation.php?quotation_id=<?php echo (int)$q['quotation_id']; ?>" target="_blank" class="btn btn-outline-secondary" title="<?php echo htmlspecialchars(t('quotations.title_print', 'ພິມ / ເບິ່ງ')); ?>">
                    <i class="fas fa-print"></i>
                  </a>
                  <?php if (hasPermission('quotations', 'edit') && !$isConverted): ?>
                    <button type="button" class="btn btn-outline-primary" title="<?php echo htmlspecialchars(t('quotations.title_edit', 'ແກ້ໄຂ')); ?>" onclick='openQuotationModal(<?php echo $qJson; ?>)'>
                      <i class="fas fa-edit"></i>
                    </button>
                  <?php endif; ?>
                  <?php if (hasPermission('quotations', 'edit') && !$isConverted): ?>
                    <button type="button" class="btn btn-outline-success" title="<?php echo htmlspecialchars(t('quotations.title_sell', 'ຂາຍ (ລູກຄ້າຮັບແລ້ວ)')); ?>" onclick="confirmConvertToSale(<?php echo (int)$q['quotation_id']; ?>, '<?php echo htmlspecialchars(addslashes($q['quotation_no']), ENT_QUOTES); ?>')">
                      <i class="fas fa-cash-register"></i>
                    </button>
                  <?php endif; ?>
                  <?php if (hasPermission('quotations', 'del')): ?>
                    <button type="button" class="btn btn-outline-danger" title="<?php echo htmlspecialchars(t('quotations.title_delete', 'ລົບ')); ?>" onclick="deleteQuotation(<?php echo (int)$q['quotation_id']; ?>, '<?php echo htmlspecialchars(addslashes($q['quotation_no']), ENT_QUOTES); ?>')">
                      <i class="fas fa-trash-alt"></i>
                    </button>
                  <?php endif; ?>
                </div>
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
        <?php echo htmlspecialchars(t('quotations.page_showing', 'ສະແດງ')); ?>
        <span id="quote_page_info_start">0</span>-<span id="quote_page_info_end">0</span>
        <?php echo htmlspecialchars(t('quotations.page_items', 'ລາຍການ')); ?>
      </span>
      <select id="quotePageSizeSelect" class="form-control form-control-sm font-weight-bold" style="width: auto; border-radius: 6px;" onchange="changeQuotePageSize()">
        <option value="10">10</option>
        <option value="25">25</option>
        <option value="50">50</option>
        <option value="all"><?php echo htmlspecialchars(t('quotations.page_all', 'ທັງໝົດ')); ?></option>
      </select>
    </div>
    <nav aria-label="Quotation Pagination" style="margin-left: auto;">
      <ul class="pagination pagination-circle mb-0 justify-content-end" id="quotePagination"></ul>
    </nav>
  </div>
</div>
