<?php
/*
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ແຖວສະວິດສິດຂອງໝວດ "ຕັ້ງຄ່າລະບົບ" ໃນຕາຕະລາງກຳນົດສິດ (ຜູ້ໃຊ້, ສິດ, ສາຂາ, ຂໍ້ມູນຮ້ານ, ພິມບາໂຄ້ດ, ໝົດອາຍຸ ...).
 *   - setup/rates_to_database.php : ແຖວສິດ: ອັດຕາແລກປ່ຽນ → ຖານຂໍ້ມູນ
 */
?>
<!-- SECTION 7: ຕັ້ງຄ່າລະບົບ (SYSTEM SETTINGS) -->
<tr class="table-primary">
  <td colspan="5" class="font-weight-bold text-uppercase py-2" style="font-size: 0.82rem; letter-spacing: 0.5px;">
    <i class="fas fa-cogs mr-1"></i> <?php echo htmlspecialchars(t('permissions.section_setup', '7. ຕັ້ງຄ່າລະບົບ & ຈັດການຜູ້ນຳໃຊ້')); ?>
  </td>
</tr>

<!-- 7.1 ຈັດການຜູ້ນຳໃຊ້ (Users Management) -->
<tr>
  <td class="pl-4">
    <div class="perm-module-info">
      <div class="perm-module-icon icon-bg-users">
        <i class="fas fa-users-cog"></i>
      </div>
      <div>
        <div class="perm-module-title"><?php echo htmlspecialchars(t('permissions.module_users_title', 'ຈັດການຜູ້ນຳໃຊ້')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_users_view_<?php echo $u['Id']; ?>" 
             data-perm="users" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['users']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'users', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_users_add_<?php echo $u['Id']; ?>" 
             data-perm="users" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['users']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'users', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_users_edit_<?php echo $u['Id']; ?>" 
             data-perm="users" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['users']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'users', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_users_del_<?php echo $u['Id']; ?>" 
             data-perm="users" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['users']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'users', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>

<!-- 7.2 ກຳນົດສິດ (Permissions Management) -->
<tr>
  <td class="pl-4">
    <div class="perm-module-info">
      <div class="perm-module-icon" style="background: #fef3c7; color: #d97706;">
        <i class="fas fa-user-shield"></i>
      </div>
      <div>
        <div class="perm-module-title"><?php echo htmlspecialchars(t('permissions.module_permissions_title', 'ກຳນົດສິດ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_permissions_view_<?php echo $u['Id']; ?>" 
             data-perm="permissions" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['permissions']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'permissions', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_permissions_edit_<?php echo $u['Id']; ?>" 
             data-perm="permissions" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['permissions']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'permissions', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<!-- 7.3 ຈັດການສາຂາ (Multi-Branch Management) -->
<tr>
  <td class="pl-4">
    <div class="perm-module-info">
      <div class="perm-module-icon" style="background: #dcfce7; color: #16a34a;">
        <i class="fas fa-network-wired"></i>
      </div>
      <div>
        <div class="perm-module-title"><?php echo htmlspecialchars(t('permissions.module_branches_title', 'ຈັດການສາຂາ')); ?></div>
        <div class="perm-module-desc"><?php echo htmlspecialchars(t('permissions.module_branches_desc', 'ເພີ່ມ, ແກ້ໄຂ ແລະ ຈັດການສາຂາທັງໝົດ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_branches_view_<?php echo $u['Id']; ?>" 
             data-perm="branches" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['branches']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'branches', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_branches_add_<?php echo $u['Id']; ?>" 
             data-perm="branches" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['branches']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'branches', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_branches_edit_<?php echo $u['Id']; ?>" 
             data-perm="branches" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['branches']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'branches', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_branches_del_<?php echo $u['Id']; ?>" 
             data-perm="branches" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['branches']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'branches', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>

<!-- 7.4 ຂໍ້ມູນຮ້ານ (Store Settings) -->
<tr>
  <td class="pl-4">
    <div class="perm-module-info">
      <div class="perm-module-icon" style="background: #eff6ff; color: #2563eb;">
        <i class="fas fa-store"></i>
      </div>
      <div>
        <div class="perm-module-title"><?php echo htmlspecialchars(t('permissions.module_stores_title', 'ຂໍ້ມູນຮ້ານ')); ?></div>
        <div class="perm-module-desc"><?php echo htmlspecialchars(t('permissions.module_stores_desc', 'ຈັດການຂໍ້ມູນຮ້ານ, ເລກຜູ້ເສຍອາກອນ, ໂລໂກ້ ແລະ ທີ່ຢູ່')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_stores_view_<?php echo $u['Id']; ?>" 
             data-perm="stores" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['stores']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'stores', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_stores_edit_<?php echo $u['Id']; ?>" 
             data-perm="stores" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['stores']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'stores', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<!-- 7.5 ພິມບາໂຄ້ດ (Print Barcode) -->
<tr>
  <td class="pl-4">
    <div class="perm-module-info">
      <div class="perm-module-icon" style="background: #e0f2fe; color: #0284c7;">
        <i class="fas fa-barcode"></i>
      </div>
      <div>
        <div class="perm-module-title"><?php echo htmlspecialchars(t('permissions.module_print_barcode_title', 'ພິມບາໂຄ້ດ')); ?></div>
        <div class="perm-module-desc"><?php echo htmlspecialchars(t('permissions.module_print_barcode_desc', 'ພິມບາໂຄ້ດ ແລະ ລາຄາສິນຄ້າອອກເຈ້ຍ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_print_barcode_view_<?php echo $u['Id']; ?>" 
             data-perm="print_barcode" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['print_barcode']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'print_barcode', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<!-- 7.55 ສິນຄ້າໝົດອາຍຸ (Expiry) — view = open the page, add = give a product an expiry date,
     edit = change an existing date, delete = stop tracking (see pages/settings/expiry/index.php) -->
<tr>
  <td class="pl-4">
    <div class="perm-module-info">
      <div class="perm-module-icon" style="background: #fee2e2; color: #dc2626;">
        <i class="fas fa-hourglass-half"></i>
      </div>
      <div>
        <div class="perm-module-title"><?php echo htmlspecialchars(t('permissions.module_expiry_title', 'ສິນຄ້າໝົດອາຍຸ')); ?></div>
        <div class="perm-module-desc"><?php echo htmlspecialchars(t('permissions.module_expiry_desc', 'ເບິ່ງ ແລະ ກຳນົດວັນໝົດອາຍຸສິນຄ້າ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_expiry_view_<?php echo $u['Id']; ?>"
             data-perm="expiry"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['expiry']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'expiry', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <?php foreach (['add', 'edit', 'del'] as $__act): ?>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_expiry_<?php echo $__act; ?>_<?php echo $u['Id']; ?>"
             data-perm="expiry"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['expiry']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'expiry', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <?php endforeach; ?>
</tr>

<!-- 7.56 ຈັດການກະເຮັດວຽກ (ເມນູ ຕັ້ງຄ່າລະບົບ › ຈັດການກະເຮັດວຽກ — pages/settings/shifts/index.php) -->
<tr>
  <td class="pl-4">
    <div class="perm-module-info">
      <div class="perm-module-icon" style="background: #e0f2fe; color: #0891b2;">
        <i class="fas fa-user-clock"></i>
      </div>
      <div>
        <div class="perm-module-title"><?php echo htmlspecialchars(t('permissions.module_shifts_title', 'ຈັດການກະເຮັດວຽກ')); ?></div>
        <div class="perm-module-desc"><?php echo htmlspecialchars(t('permissions.module_shifts_desc', 'ຕັ້ງຄ່າກະ, ເວລາເປີດ-ປິດ ແລະ ພະນັກງານປະຈຳກະ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_shifts_view_<?php echo $u['Id']; ?>"
             data-perm="shifts"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['shifts']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'shifts', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<?php require __DIR__ . '/setup/rates_to_database.php'; /* ແຖວສິດ: ອັດຕາແລກປ່ຽນ → ຖານຂໍ້ມູນ */ ?>
