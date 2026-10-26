<!-- VIEW USER DETAILS MODAL FORM -->
<div class="modal fade" id="viewUserModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content" style="border-radius: 16px; overflow: hidden; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
      <div class="modal-header text-white py-3" style="background-color: #244886;">
        <h5 class="modal-title font-weight-bold" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
          <i class="fas fa-id-card mr-2"></i> <?php echo htmlspecialchars(t('users_manage.view_modal_title', 'ລາຍລະອຽດຂໍ້ມູນຜູ້ນຳໃຊ້')); ?>
        </h5>
        <button type="button" class="close text-white opacity-90" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body p-4">
        <div class="row">
          <!-- LEFT COLUMN: Profile Avatar & Main Info -->
          <div class="col-md-4 text-center border-right pr-md-3 py-2 d-flex flex-column align-items-center justify-content-center">
            <img id="view_avatar" src="" class="shadow-sm mb-3" style="width: 135px; height: 135px; object-fit: cover; border-radius: 14px; border: 4px solid #38bdf8; background-color: #f8fafc;">
            <h5 id="view_fullname" class="font-weight-bold text-dark mb-1"></h5>
            <div id="view_status_badge" class="mb-2"></div>
            <small class="text-muted">ID: #<span id="view_id"></span> <span id="view_user_code_wrap">| <span id="view_user_code"></span></span></small>
          </div>

          <!-- RIGHT COLUMN: Detailed Information Grid -->
          <div class="col-md-8 pl-md-4 py-2">
            <h6 class="font-weight-bold text-primary mb-3 border-bottom pb-2">
              <i class="fas fa-user-circle mr-1"></i> <?php echo htmlspecialchars(t('users_manage.view_section_personal', 'ຂໍ້ມູນສ່ວນຕົວ & ບັນຊີ')); ?>
            </h6>

            <div class="row" style="font-size: 0.95rem;">
              <div class="col-6 mb-3">
                <span class="text-muted d-block" style="font-size: 0.8rem;"><i class="fas fa-venus-mars mr-1"></i> <?php echo htmlspecialchars(t('users_manage.view_label_gender', 'ເພດ:')); ?></span>
                <strong id="view_gender" class="text-dark"></strong>
              </div>
              <div class="col-6 mb-3">
                <span class="text-muted d-block" style="font-size: 0.8rem;"><i class="fas fa-calendar-alt mr-1"></i> <?php echo htmlspecialchars(t('users_manage.view_label_dob', 'ວັນເດືອນປີເກີດ:')); ?></span>
                <strong id="view_dob" class="text-dark"></strong>
              </div>
              <div class="col-6 mb-3">
                <span class="text-muted d-block" style="font-size: 0.8rem;"><i class="fas fa-phone mr-1"></i> <?php echo htmlspecialchars(t('users_manage.view_label_tel', 'ເບີໂທລະສັບ:')); ?></span>
                <strong id="view_tel" class="text-dark"></strong>
              </div>
              <div class="col-6 mb-3">
                <span class="text-muted d-block" style="font-size: 0.8rem;"><i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars(t('users_manage.view_label_address', 'ທີ່ຢູ່:')); ?></span>
                <span id="view_address" class="text-dark font-weight-bold"></span>
              </div>
              <div class="col-6 mb-2">
                <span class="text-muted d-block" style="font-size: 0.8rem;"><i class="fas fa-sticky-note mr-1"></i> <?php echo htmlspecialchars(t('users_manage.view_label_notes', 'ໝາຍເຫດ:')); ?></span>
                <span id="view_notes" class="text-muted"></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer bg-light py-3">
        <button type="button" class="btn btn-secondary px-4 font-weight-bold" data-dismiss="modal" style="border-radius: 6px;"><?php echo htmlspecialchars(t('users_manage.btn_close', 'ປິດ')); ?></button>
        <button type="button" class="btn btn-primary px-4 font-weight-bold shadow-sm" id="btn_edit_from_view" style="border-radius: 6px;">
          <i class="fas fa-edit mr-1"></i> <?php echo htmlspecialchars(t('users_manage.btn_edit', 'ແກ້ໄຂ')); ?>
        </button>
      </div>
    </div>
  </div>
</div>
