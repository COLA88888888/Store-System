<!-- ADD USER MODAL FORM -->
<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl my-2" role="document">
    <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">

      <form id="addUserForm" method="POST" enctype="multipart/form-data"
            onsubmit="return submitUserForm(event, 'addUserForm', 'add_user');" novalidate
            style="display: contents;">

        <input type="hidden" name="action" value="add_user">

        <!-- HEADER -->
        <div class="modal-header text-white py-3 px-4" style="border-radius: 16px 16px 0 0; background-color: #244886;">
          <h5 class="modal-title font-weight-bold" style="font-family: 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif; font-size: 1.2rem;">
            <i class="fas fa-user-plus mr-2"></i> <?php echo htmlspecialchars(t('users_manage.add_modal_title', 'ເພີ່ມຜູ້ໃຊ້ໃໝ່')); ?>
          </h5>
          <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <!-- BODY (scrollable) -->
        <div class="modal-body p-4">
          <div class="row">
            <!-- LEFT: Avatar -->
            <div class="col-md-4 col-lg-3 text-center border-right pr-md-4 py-3 d-flex flex-column align-items-center justify-content-center">
              <div class="position-relative my-2 d-inline-block" style="width:135px;height:135px;cursor:pointer;"
                   onclick="document.getElementById('add_profile_img').click();">
                <img id="add_avatar_preview"
                     src="<?php echo $base_path; ?>assets/img/users/default.png"
                     class="shadow-sm"
                     style="width:135px;height:135px;object-fit:cover;border-radius:14px;border:3px solid #60a5fa;background:#f8fafc;">

                <!-- Camera -->
                <label for="add_profile_img"
                       class="position-absolute d-flex align-items-center justify-content-center shadow-sm"
                       style="right:-6px;bottom:-6px;width:34px;height:34px;background:#0284c7;color:#fff;border-radius:50%;border:2px solid #fff;cursor:pointer;margin:0;z-index:2;"
                       onclick="event.stopPropagation();">
                  <i class="fas fa-camera" style="font-size:0.9rem;"></i>
                </label>

                <!-- Remove -->
                <button type="button" id="btn_remove_add_avatar"
                        onclick="event.stopPropagation(); removeAvatar('add_profile_img','add_avatar_preview','btn_remove_add_avatar')"
                        class="position-absolute align-items-center justify-content-center border-0 shadow-sm"
                        style="right:-6px;top:-6px;width:30px;height:30px;background:#ef4444;color:#fff;border-radius:50%;border:2px solid #fff;cursor:pointer;display:none;z-index:3;">
                  <i class="fas fa-times" style="font-size:0.85rem;"></i>
                </button>
              </div>

              <label for="add_profile_img" class="text-muted font-weight-bold mt-2 mb-0 text-center"
                     style="font-size:0.82rem;line-height:1.4;cursor:pointer;">
              </label>
              <input type="file" name="profile_img" id="add_profile_img"
                     accept="image/jpeg,image/png,image/webp" style="display:none;"
                     onchange="previewAvatar(this,'add_avatar_preview','btn_remove_add_avatar')">
            </div>

            <!-- RIGHT: Fields -->
            <div class="col-md-8 col-lg-9 pl-md-4 py-2">
              <div class="row">
                <!-- ລະຫັດ & ສະຖານະ -->
                <div class="col-md-6 form-group mb-3">
                  <label class="font-weight-bold text-muted mb-1"><?php echo htmlspecialchars(t('users_manage.label_user_code', 'ລະຫັດຜູ້ນຳໃຊ້:')); ?> <span class="text-danger">*</span></label>
                  <input type="text" name="user_code" id="add_user_code" class="form-control"
                         placeholder="<?php echo htmlspecialchars(t('users_manage.placeholder_user_code', 'ກະລຸນາປ້ອນລະຫັດ')); ?>" required>
                </div>
                <div class="col-md-6 form-group mb-3">
                  <label class="font-weight-bold text-muted mb-1"><?php echo htmlspecialchars(t('users_manage.label_role', 'ຕຳແໜ່ງ:')); ?> <span class="text-danger">*</span></label>
                  <select name="status" class="form-control" required>
                    <option value=""><?php echo htmlspecialchars(t('users_manage.option_select_role', '-- ເລືອກຕຳແໜ່ງ --')); ?></option>
                    <option value="ຜູ້ບໍລິຫານ"><?php echo htmlspecialchars(t('users_manage.role_admin', 'ຜູ້ບໍລິຫານ')); ?></option>
                    <option value="ພະນັກງານ"><?php echo htmlspecialchars(t('users_manage.role_employee', 'ພະນັກງານ')); ?></option>
                  </select>
                </div>

                <!-- ຊື່ຜູ້ໃຊ້ງານ & ສາຂາ -->
                <div class="col-md-6 form-group mb-3">
                  <label class="font-weight-bold text-muted mb-1"><?php echo htmlspecialchars(t('users_manage.label_username', 'ຊື່ຜູ້ນຳໃຊ້:')); ?> <span class="text-danger">*</span></label>
                  <input type="text" name="fname" class="form-control" placeholder="<?php echo htmlspecialchars(t('users_manage.placeholder_username', 'ກະລຸນາປ້ອນຊື່')); ?>" required>
                </div>
                <div class="col-md-6 form-group mb-3">
                  <label class="font-weight-bold text-muted mb-1"><?php echo htmlspecialchars(t('users_manage.label_branch', 'ປະຈຳສາຂາ:')); ?> <span class="text-danger">*</span></label>
                  <select name="store_id" class="form-control" required>
                    <?php if (!empty($storesList)): ?>
                      <?php foreach ($storesList as $st): ?>
                        <option value="<?php echo $st['store_id']; ?>">
                          <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('users_manage.branch_main_suffix', '(ສາຂາໃຫຍ່)')) : ''; ?>
                        </option>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <option value="1"><?php echo htmlspecialchars(t('users_manage.default_store', 'ສາຂາຫຼັກ')); ?></option>
                    <?php endif; ?>
                  </select>
                </div>
                <!-- ລະຫັດຜ່ານ -->
                <div class="col-md-6 form-group mb-3">
                  <label class="font-weight-bold text-muted mb-1"><?php echo htmlspecialchars(t('users_manage.label_password', 'ລະຫັດຜ່ານ:')); ?> <span class="text-danger">*</span></label>
                  <div class="input-group">
                    <input type="password" name="password" id="add_password" class="form-control"
                           placeholder="<?php echo htmlspecialchars(t('users_manage.placeholder_password', 'ປ້ອນລະຫັດຜ່ານ')); ?>" required>
                    <div class="input-group-append">
                      <button type="button" class="btn btn-outline-secondary"
                              onclick="togglePassVisibility('add_password', this)">
                        <i class="fas fa-eye"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- ເພດ & ຢືນຢັນລະຫັດ -->
                <div class="col-md-6 form-group mb-3 d-flex align-items-center flex-wrap" style="min-height:45px;">
                  <label class="font-weight-bold text-muted mb-0 mr-3"><?php echo htmlspecialchars(t('users_manage.label_gender', 'ເພດ:')); ?> <span class="text-danger">*</span></label>
                  <div class="d-flex align-items-center">
                    <div class="custom-control custom-radio custom-control-inline mr-4 mb-0">
                      <input type="radio" id="add_gender_male" name="gender" value="ຊາຍ"
                             class="custom-control-input" checked>
                      <label class="custom-control-label font-weight-normal" for="add_gender_male"><?php echo htmlspecialchars(t('users_manage.gender_male', 'ຊາຍ')); ?></label>
                    </div>
                    <div class="custom-control custom-radio custom-control-inline mb-0">
                      <input type="radio" id="add_gender_female" name="gender" value="ຍິງ"
                             class="custom-control-input">
                      <label class="custom-control-label font-weight-normal" for="add_gender_female"><?php echo htmlspecialchars(t('users_manage.gender_female', 'ຍິງ')); ?></label>
                    </div>
                  </div>
                </div>
                <div class="col-md-6 form-group mb-3">
                  <label class="font-weight-bold text-muted mb-1"><?php echo htmlspecialchars(t('users_manage.label_confirm_password', 'ຢືນຢັນລະຫັດ:')); ?></label>
                  <div class="input-group">
                    <input type="password" name="confirm_password" id="add_confirm_password"
                           class="form-control" placeholder="<?php echo htmlspecialchars(t('users_manage.placeholder_confirm_password', 'ຢືນຢັນລະຫັດ')); ?>">
                    <div class="input-group-append">
                      <button type="button" class="btn btn-outline-secondary"
                              onclick="togglePassVisibility('add_confirm_password', this)">
                        <i class="fas fa-eye"></i>
                      </button>
                    </div>
                  </div>
                </div>

                <!-- ວັນເດືອນປີເກີດ & ເບີໂທ -->
                <div class="col-md-6 form-group mb-3">
                  <label class="font-weight-bold text-muted mb-1"><?php echo htmlspecialchars(t('users_manage.label_dob', 'ວັນເດືອນປີເກີດ:')); ?> <span class="text-danger">*</span></label>
                  <input type="date" name="dob" id="add_dob"
                         onclick="this.showPicker ? this.showPicker() : null"
                         class="form-control" style="cursor:pointer;" required>
                </div>
                <!-- ເບີໂທ & ທີ່ຢູ່ -->
                <div class="col-md-6 form-group mb-3">
                  <label class="font-weight-bold text-muted mb-1"><?php echo htmlspecialchars(t('users_manage.label_tel', 'ເບີໂທ:')); ?> <span class="text-danger">*</span></label>
                  <input type="text" name="tel" class="form-control" placeholder="020..." required>
                </div>
                <div class="col-md-6 form-group mb-3">
                  <label class="font-weight-bold text-muted mb-1"><?php echo htmlspecialchars(t('users_manage.label_address', 'ທີ່ຢູ່:')); ?> <span class="text-danger">*</span></label>
                  <textarea name="address" class="form-control" rows="1"
                            placeholder="<?php echo htmlspecialchars(t('users_manage.placeholder_address_optional', 'ບ້ານ, ເມືອງ, ແຂວງ (ບໍ່ບັງຄັບ)')); ?>"></textarea>
                </div>

                <!-- ໝາຍເຫດ (ດ້ານລຸ່ມ) -->
                <div class="col-md-12 form-group mb-3">
                  <label class="font-weight-bold text-muted mb-1"><?php echo htmlspecialchars(t('users_manage.label_notes', 'ໝາຍເຫດ:')); ?></label>
                  <textarea name="notes" class="form-control" rows="2"
                            placeholder="<?php echo htmlspecialchars(t('users_manage.placeholder_notes_optional', 'ໝາຍເຫດ (ບໍ່ບັງຄັບ)')); ?>"></textarea>
                </div>

                <?php if (isOwnerUser()): ?>
                <!-- ຈຳນວນມື້ອະນຸຍາດໃຫ້ໃຊ້ງານ (ສະເພາະຜູ້ບໍລິຫານຄົນທຳອິດເທົ່ານັ້ນທີ່ກຳນົດໄດ້) -->
                <div class="col-md-6 form-group mb-3">
                  <label class="font-weight-bold text-muted mb-1"><?php echo htmlspecialchars(t('users_manage.label_access_days', 'ຈຳນວນມື້ອະນຸຍາດໃຫ້ໃຊ້ງານ:')); ?></label>
                  <input type="number" name="access_days" min="1" class="form-control"
                         placeholder="<?php echo htmlspecialchars(t('users_manage.placeholder_access_days', 'ຫວ່າງໄວ້ = ບໍ່ຈຳກັດ')); ?>">
                  <small class="text-muted"><?php echo htmlspecialchars(t('users_manage.hint_access_days', 'ນັບຈາກມື້ນີ້ ຫາກຫວ່າງໄວ້ຈະໃຊ້ງານໄດ້ບໍ່ຈຳກັດ')); ?></small>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- FOOTER -->
        <div class="modal-footer bg-light py-3 px-4" style="border-radius: 0 0 16px 16px;">
          <button type="button" class="btn btn-secondary px-4 font-weight-bold" data-dismiss="modal" style="border-radius:6px;">
            <i class="fas fa-times mr-1"></i> <?php echo htmlspecialchars(t('users_manage.btn_cancel', 'ຍົກເລີກ')); ?>
          </button>
          <button type="submit" class="btn btn-primary px-4 font-weight-bold shadow-sm" style="border-radius:6px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-save mr-1"></i> <?php echo htmlspecialchars(t('users_manage.btn_save', 'ບັນທຶກ')); ?>
          </button>
        </div>

      </form>
    </div>
  </div>
</div>
