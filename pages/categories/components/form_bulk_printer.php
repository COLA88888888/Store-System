<!-- Modal: Bulk-assign kitchen printer to every category -->
<?php if (!empty($catPrinters) && hasPermission('categories', 'edit')): ?>
<div class="modal fade" id="bulkPrinterModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <form id="bulkPrinterForm" action="" method="POST">
        <input type="hidden" name="action" value="bulk_set_printer">
        <div class="modal-header" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important;">
          <h5 class="modal-title font-weight-bold text-white" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-print mr-2"></i> <?php echo htmlspecialchars(t('categories.bulk_printer_title', 'ຕັ້ງເຄື່ອງພິມໃຫ້ທຸກໝວດໝູ່')); ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4">
          <p class="text-muted small mb-3"><?php echo htmlspecialchars(t('categories.bulk_printer_hint', 'ເລືອກເຄື່ອງພິມແລ້ວກົດຢືນຢັນ — ທຸກໝວດໝູ່ສິນຄ້າໃນສາຂານີ້ຈະຖືກຕັ້ງໃຫ້ພິມໃບຍ່ອຍໄປເຄື່ອງນີ້ໝົດ (ທັບຄ່າເກົ່າ).')); ?></p>
          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('categories.label_printer', 'ເຄື່ອງພິມໃບຍ່ອຍ (kitchen ticket)')); ?></label>
            <select name="printer_id" id="bulk_printer_id" class="form-control" style="border-radius: 8px; height: 42px;">
              <option value=""><?php echo htmlspecialchars(t('categories.printer_none', '— ບໍ່ພິມໃບຍ່ອຍ —')); ?></option>
              <?php foreach ($catPrinters as $pr): ?>
                <option value="<?php echo (int)$pr['id']; ?>"><?php echo htmlspecialchars($pr['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0 pb-4 px-4">
          <button type="button" class="btn btn-light font-weight-bold px-4" style="border-radius: 6px;" data-dismiss="modal"><?php echo htmlspecialchars(t('categories.btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="button" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;" onclick="submitBulkPrinter()">
            <i class="fas fa-check mr-1"></i> <?php echo htmlspecialchars(t('categories.btn_confirm', 'ຢືນຢັນ')); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var I18N_BULK_PRINTER = <?php echo tjson([
    'categories.bulk_printer_confirm_title' => 'ຢືນຢັນຕັ້ງເຄື່ອງພິມທຸກໝວດ?',
    'categories.bulk_printer_confirm_body'  => 'ຈະທັບຄ່າເຄື່ອງພິມເດີມຂອງທຸກໝວດໝູ່ໃນສາຂານີ້ດ້ວຍຄ່າໃໝ່ນີ້.',
    'categories.btn_confirm'                => 'ຢືນຢັນ',
    'categories.btn_cancel'                 => 'ຍົກເລີກ',
]); ?>;

function submitBulkPrinter() {
  Swal.fire({
    title: I18N_BULK_PRINTER['categories.bulk_printer_confirm_title'],
    text: I18N_BULK_PRINTER['categories.bulk_printer_confirm_body'],
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#64748b',
    confirmButtonText: I18N_BULK_PRINTER['categories.btn_confirm'],
    cancelButtonText: I18N_BULK_PRINTER['categories.btn_cancel'],
    heightAuto: false
  }).then(function(result) {
    if (result.isConfirmed) {
      $('#bulkPrinterForm').submit();
    }
  });
}
</script>
<?php endif; ?>
