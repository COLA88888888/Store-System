<!-- Modal: Edit Unit -->
<div class="modal fade" id="editUnitModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <form id="editUnitForm" action="" method="POST" novalidate>
        <input type="hidden" name="action" value="edit_unit">
        <input type="hidden" name="unit_id" id="edit_unit_id">
        <div class="modal-header" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important;">
          <h5 class="modal-title font-weight-bold text-white" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-edit mr-2"></i> <?php echo htmlspecialchars(t('units.modal_edit_title', 'ແກ້ໄຂຫົວໜ່ວຍ')); ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('units.label_code', 'ລະຫັດຫົວໜ່ວຍ')); ?></label>
            <input type="text" name="new_unit_id" id="edit_unit_id_input" class="form-control bg-light font-weight-bold text-primary" style="border-radius: 8px; height: 42px;" readonly>
            <small class="form-text text-muted" style="font-size: 0.82rem;"><i class="fas fa-lock mr-1"></i><?php echo htmlspecialchars(t('units.hint_code_locked', 'ລະຫັດຫົວໜ່ວຍ (ແກ້ໄຂບໍ່ໄດ້)')); ?></small>
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('units.label_name', 'ຊື່ຫົວໜ່ວຍ')); ?> <span class="text-danger">*</span></label>
            <input type="text" name="unit_name" id="edit_unit_name" class="form-control" style="border-radius: 8px; height: 42px;">
          </div>
          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('units.label_desc', 'ລາຍລະອຽດ')); ?></label>
            <textarea name="description" id="edit_unit_desc" class="form-control" rows="3" style="border-radius: 8px;"></textarea>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0 pb-4 px-4">
          <button type="button" class="btn btn-light font-weight-bold px-4" style="border-radius: 6px;" data-dismiss="modal"><?php echo htmlspecialchars(t('units.btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="button" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;" onclick="submitEditUnit()">
            <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('units.btn_update', 'ອັບເດດ')); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var I18N_EDIT_UNIT = <?php echo tjson([
    'units.warn_enter_code_title' => 'ກະລຸນາປ້ອນລະຫັດ',
    'units.warn_code_empty' => 'ລະຫັດຫົວໜ່ວຍບໍ່ສາມາດຫວ່າງໄດ້!',
    'units.warn_enter_name_title' => 'ກະລຸນາປ້ອນຊື່',
    'units.warn_name_empty' => 'ຊື່ຫົວໜ່ວຍບໍ່ສາມາດຫວ່າງໄດ້!',
    'units.btn_ok' => 'ຕົກລົງ',
]); ?>;

function submitEditUnit() {
  var unitId   = $('#edit_unit_id_input').val().trim();
  var unitName = $('#edit_unit_name').val().trim();

  if (unitId === '' || parseInt(unitId) < 1) {
    Swal.fire({
      icon: 'warning',
      title: I18N_EDIT_UNIT['units.warn_enter_code_title'],
      text: I18N_EDIT_UNIT['units.warn_code_empty'],
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_EDIT_UNIT['units.btn_ok']
    });
    $('#edit_unit_id_input').focus();
    return;
  }

  if (unitName === '') {
    Swal.fire({
      icon: 'warning',
      title: I18N_EDIT_UNIT['units.warn_enter_name_title'],
      text: I18N_EDIT_UNIT['units.warn_name_empty'],
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_EDIT_UNIT['units.btn_ok']
    });
    $('#edit_unit_name').focus();
    return;
  }

  $('#editUnitForm').submit();
}
</script>
