<!-- Modal: Add Category -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <form id="addCategoryForm" action="" method="POST" novalidate>
        <input type="hidden" name="action" value="add_category">
        <div class="modal-header" style="background: linear-gradient(135deg, #2c5aa0, #244886) !important;">
          <h5 class="modal-title font-weight-bold text-white" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
            <i class="fas fa-folder-plus mr-2"></i> <?php echo htmlspecialchars(t('categories.modal_add_title', 'ເພີ່ມປະເພດສິນຄ້າ')); ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body p-4">
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('categories.label_code', 'ລະຫັດປະເພດສິນຄ້າ')); ?></label>
            <input type="text" id="add_cat_id" name="category_id" class="form-control bg-light font-weight-bold text-primary" style="border-radius: 8px; height: 42px;" readonly>
            <small class="form-text text-muted" style="font-size: 0.82rem;"><i class="fas fa-lock mr-1"></i><?php echo htmlspecialchars(t('categories.hint_code_locked', 'ລະຫັດຈະລຽນລຳດັບອັດຕະໂນມັດ (ແກ້ໄຂບໍ່ໄດ້)')); ?></small>
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('categories.label_name', 'ຊື່ປະເພດສິນຄ້າ')); ?> <span class="text-danger">*</span></label>
            <input type="text" id="add_cat_name" name="category_name" class="form-control" placeholder="<?php echo htmlspecialchars(t('categories.placeholder_name', 'ປ້ອນຊື່ປະເພດສິນຄ້າ')); ?>" style="border-radius: 8px; height: 42px;">
          </div>
          <div class="form-group mb-3">
            <label class="font-weight-bold text-dark mb-1"><?php echo htmlspecialchars(t('categories.label_desc', 'ລາຍລະອຽດ')); ?></label>
            <textarea name="description" class="form-control" rows="3" placeholder="<?php echo htmlspecialchars(t('categories.placeholder_desc', 'ປ້ອນລາຍລະອຽດ(ບໍ່ບັງຄັບ)')); ?>" style="border-radius: 8px;"></textarea>
          </div>
          <?php if (!empty($catPrinters)): ?>
          <div class="form-group mb-0">
            <label class="font-weight-bold text-dark mb-1"><i class="fas fa-print text-primary mr-1"></i> <?php echo htmlspecialchars(t('categories.label_printer', 'ເຄື່ອງພິມໃບຍ່ອຍ (kitchen ticket)')); ?></label>
            <select name="printer_id" id="add_cat_printer" class="form-control" style="border-radius: 8px; height: 42px;">
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
          <button type="button" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="border-radius: 6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;" onclick="submitAddCategory()">
            <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('categories.btn_save', 'ບັນທຶກ')); ?>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
var I18N_ADD_CATEGORY = <?php echo tjson([
    'categories.warn_enter_code_title' => 'ກະລຸນາປ້ອນລະຫັດ',
    'categories.warn_code_empty' => 'ລະຫັດປະເພດສິນຄ້າບໍ່ສາມາດຫວ່າງໄດ້!',
    'categories.warn_enter_name_title' => 'ກະລຸນາປ້ອນຊື່',
    'categories.warn_name_empty' => 'ຊື່ປະເພດສິນຄ້າບໍ່ສາມາດຫວ່າງໄດ້!',
    'categories.btn_ok' => 'ຕົກລົງ',
]); ?>;

function submitAddCategory() {
  var catId   = $('#add_cat_id').val().trim();
  var catName = $('#add_cat_name').val().trim();

  if (catId === '' || parseInt(catId) < 1) {
    Swal.fire({
      icon: 'warning',
      title: I18N_ADD_CATEGORY['categories.warn_enter_code_title'],
      text: I18N_ADD_CATEGORY['categories.warn_code_empty'],
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_ADD_CATEGORY['categories.btn_ok']
    });
    $('#add_cat_id').focus();
    return;
  }

  if (catName === '') {
    Swal.fire({
      icon: 'warning',
      title: I18N_ADD_CATEGORY['categories.warn_enter_name_title'],
      text: I18N_ADD_CATEGORY['categories.warn_name_empty'],
      confirmButtonColor: '#2563eb',
      confirmButtonText: I18N_ADD_CATEGORY['categories.btn_ok']
    });
    $('#add_cat_name').focus();
    return;
  }

  $('#addCategoryForm').submit();
}

// Submit form when Enter is pressed inside add modal inputs
$(document).ready(function() {
  $(document).on('keydown', '#addCategoryModal input', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      submitAddCategory();
    }
  });
});
</script>
