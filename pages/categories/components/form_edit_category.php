<!-- Modal: Edit Category -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <form id="editCategoryForm" action="" method="POST" novalidate>
        <input type="hidden" name="action" value="edit_category">
        <input type="hidden" name="category_id" id="edit_cat_id">
        <div class="modal-header" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important;">
          <h5 class="modal-title font-weight-bold text-white" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-edit mr-2"></i> <?php echo htmlspecialchars(t('categories.modal_edit_title', 'ແກ້ໄຂປະເພດສິນຄ້າ')); ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('categories.label_name', 'ຊື່ປະເພດສິນຄ້າ')); ?> <span class="text-danger">*</span></label>
            <input type="text" name="category_name" id="edit_cat_name" class="form-control" placeholder="<?php echo htmlspecialchars(t('categories.placeholder_name', 'ປ້ອນຊື່ປະເພດສິນຄ້າ')); ?>" style="border-radius: 8px; height: 42px;">
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('categories.label_desc', 'ລາຍລະອຽດ')); ?></label>
            <textarea name="description" id="edit_cat_desc" class="form-control" rows="3" placeholder="<?php echo htmlspecialchars(t('categories.placeholder_desc', 'ປ້ອນລາຍລະອຽດ(ບໍ່ບັງຄັບ)')); ?>" style="border-radius: 8px;"></textarea>
          </div>
          <?php if (!empty($catPrinters)): ?>
          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark mb-1"><i class="fas fa-print text-primary mr-1"></i> <?php echo htmlspecialchars(t('categories.label_printer', 'ເຄື່ອງພິມໃບຍ່ອຍ (kitchen ticket)')); ?></label>
            <select name="printer_id" id="edit_cat_printer" class="form-control" style="border-radius: 8px; height: 42px;">
              <option value=""><?php echo htmlspecialchars(t('categories.printer_none', '— ບໍ່ພິມໃບຍ່ອຍ —')); ?></option>
              <?php foreach ($catPrinters as $pr): ?>
                <option value="<?php echo (int)$pr['id']; ?>"><?php echo htmlspecialchars($pr['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>
        </div>
        <div class="modal-footer border-0 pt-0 pb-4 px-4">
          <button type="button" class="btn btn-light font-weight-bold px-4" style="border-radius: 6px;" data-dismiss="modal"><?php echo htmlspecialchars(t('categories.btn_cancel', 'ຍົກເລີກ')); ?></button>
          <button type="button" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;" onclick="submitEditCategory()">
            <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('categories.btn_update', 'ອັບເດດ')); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var I18N_EDIT_CATEGORY = <?php echo tjson([
    'categories.warn_enter_name_title' => 'ກະລຸນາປ້ອນຊື່',
    'categories.warn_name_empty' => 'ຊື່ປະເພດສິນຄ້າບໍ່ສາມາດຫວ່າງໄດ້!',
    'categories.btn_ok' => 'ຕົກລົງ',
]); ?>;

function submitEditCategory() {
  var catName = $('#edit_cat_name').val().trim();

  if (catName === '') {
    Swal.fire({
      icon: 'warning',
      title: I18N_EDIT_CATEGORY['categories.warn_enter_name_title'],
      text: I18N_EDIT_CATEGORY['categories.warn_name_empty'],
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_EDIT_CATEGORY['categories.btn_ok']
    });
    $('#edit_cat_name').focus();
    return;
  }

  $('#editCategoryForm').submit();
}
</script>
