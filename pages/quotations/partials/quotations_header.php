<!-- Header: ໃບສະເໜີລາຄາ -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3" style="gap: 10px;">
  <div>
    <h5 class="m-0 font-weight-bold text-dark" style="font-size: 1.1rem; white-space: nowrap;">
      <i class="fas fa-file-invoice-dollar text-warning mr-2"></i> <?php echo htmlspecialchars(t('quotations.page_title', 'ໃບສະເໜີລາຄາ')); ?>
    </h5>
  </div>
  <div class="d-flex align-items-center justify-content-end ml-auto" style="gap: 8px; flex-wrap: wrap;">
    <?php if (hasPermission('quotations', 'add')): ?>
      <button type="button" class="btn btn-primary font-weight-bold text-white shadow-sm px-3 py-2" onclick="openQuotationModal()" style="border-radius: 8px; font-size: 0.88rem; height: 38px; white-space: nowrap; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
        <i class="fas fa-plus-circle mr-1"></i> <?php echo htmlspecialchars(t('quotations.btn_add_new', 'ສ້າງໃບສະເໜີລາຄາໃໝ່')); ?>
      </button>
    <?php endif; ?>
  </div>
</div>
