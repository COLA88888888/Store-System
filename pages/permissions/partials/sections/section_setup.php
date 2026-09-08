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

<!-- 7.6 ອັດຕາແລກປ່ຽນເງິນ (Exchange Rate) -->
<!-- 7.6 ອັດຕາແລກປ່ຽນເງິນ (Exchange Rate) -->
<tr>
  <td class="pl-4">
    <div class="perm-module-info">
      <div class="perm-module-icon" style="background: #dcfce7; color: #16a34a;">
        <i class="fas fa-exchange-alt"></i>
      </div>
      <div>
        <div class="perm-module-title"><?php echo htmlspecialchars(t('permissions.module_exchange_rate_title', 'ອັດຕາແລກປ່ຽນເງິນ')); ?></div>
        <div class="perm-module-desc"><?php echo htmlspecialchars(t('permissions.module_exchange_rate_desc', 'ຕັ້ງຄ່າ ແລະ ປັບອັດຕາແລກປ່ຽນເງິນຕ່າງປະເທດ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_exchange_rate_view_<?php echo $u['Id']; ?>" 
             data-perm="exchange_rate" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['exchange_rate']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'exchange_rate', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_exchange_rate_add_<?php echo $u['Id']; ?>" 
             data-perm="exchange_rate" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['exchange_rate']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'exchange_rate', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_exchange_rate_edit_<?php echo $u['Id']; ?>" 
             data-perm="exchange_rate" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['exchange_rate']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'exchange_rate', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_exchange_rate_del_<?php echo $u['Id']; ?>" 
             data-perm="exchange_rate" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['exchange_rate']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'exchange_rate', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>

<!-- 7.7 ໂປຣໂມຊັ່ນ (Promotions) -->
<tr>
  <td class="pl-4">
    <div class="perm-module-info">
      <div class="perm-module-icon" style="background: #ffe4e6; color: #e11d48;">
        <i class="fas fa-percent"></i>
      </div>
      <div>
        <div class="perm-module-title"><?php echo htmlspecialchars(t('permissions.module_promotions_title', 'ໂປຣໂມຊັ່ນ')); ?></div>
        <div class="perm-module-desc"><?php echo htmlspecialchars(t('permissions.module_promotions_desc', 'ສ້າງ, ແກ້ໄຂ ແລະ ຈັດການໂປຣໂມຊັ່ນສ່ວນຫຼຸດສິນຄ້າ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_promotions_view_<?php echo $u['Id']; ?>" 
             data-perm="promotions" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['promotions']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'promotions', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_promotions_add_<?php echo $u['Id']; ?>" 
             data-perm="promotions" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['promotions']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'promotions', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_promotions_edit_<?php echo $u['Id']; ?>" 
             data-perm="promotions" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['promotions']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'promotions', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_promotions_del_<?php echo $u['Id']; ?>" 
             data-perm="promotions" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['promotions']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'promotions', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>

<!-- 7.8 ປັບລາຄາສິນຄ້າ (Price Adjustment) -->
<tr>
  <td class="pl-4">
    <div class="perm-module-info">
      <div class="perm-module-icon" style="background: #fef3c7; color: #d97706;">
        <i class="fas fa-tags"></i>
      </div>
      <div>
        <div class="perm-module-title"><?php echo htmlspecialchars(t('permissions.module_price_adjustment_title', 'ປັບລາຄາສິນຄ້າ')); ?></div>
        <div class="perm-module-desc"><?php echo htmlspecialchars(t('permissions.module_price_adjustment_desc', 'ປັບລາຄາຂາຍ ແລະ ລາຄາຊື້ສິນຄ້າເປັນຊຸດ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_price_adjustment_view_<?php echo $u['Id']; ?>" 
             data-perm="price_adjustment" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['price_adjustment']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'price_adjustment', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_price_adjustment_add_<?php echo $u['Id']; ?>" 
             data-perm="price_adjustment" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['price_adjustment']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'price_adjustment', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_price_adjustment_edit_<?php echo $u['Id']; ?>" 
             data-perm="price_adjustment" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['price_adjustment']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'price_adjustment', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_price_adjustment_del_<?php echo $u['Id']; ?>" 
             data-perm="price_adjustment" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['price_adjustment']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'price_adjustment', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>

<!-- 7.9 ຕັ້ງຄ່າປິ່ນເຕີ (Printer Settings) -->
<tr>
  <td class="pl-4">
    <div class="perm-module-info">
      <div class="perm-module-icon" style="background: #f0fdf4; color: #16a34a;">
        <i class="fas fa-print"></i>
      </div>
      <div>
        <div class="perm-module-title"><?php echo htmlspecialchars(t('permissions.module_printers_title', 'ຕັ້ງຄ່າປິ່ນເຕີ')); ?></div>
        <div class="perm-module-desc"><?php echo htmlspecialchars(t('permissions.module_printers_desc', 'ຕັ້ງຄ່າເຄື່ອງພິມ ໃບບິນ ແລະ ບາໂຄ້ດ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_printers_view_<?php echo $u['Id']; ?>" 
             data-perm="printers" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['printers']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'printers', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_printers_add_<?php echo $u['Id']; ?>" 
             data-perm="printers" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['printers']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'printers', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_printers_edit_<?php echo $u['Id']; ?>" 
             data-perm="printers" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['printers']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'printers', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_printers_del_<?php echo $u['Id']; ?>" 
             data-perm="printers" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['printers']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'printers', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>

<!-- 7.10 ຄະແນນສະສົມລູກຄ້າ (Loyalty Points) -->
<tr>
  <td class="pl-4">
    <div class="perm-module-info">
      <div class="perm-module-icon" style="background: #fef3c7; color: #d97706;">
        <i class="fas fa-star"></i>
      </div>
      <div>
        <div class="perm-module-title"><?php echo htmlspecialchars(t('permissions.module_loyalty_title', 'ຄະແນນສະສົມລູກຄ້າ')); ?></div>
        <div class="perm-module-desc"><?php echo htmlspecialchars(t('permissions.module_loyalty_desc', 'ຕັ້ງຄ່າກົດເກນ ແລະ ຈັດການຄະແນນສະສົມຂອງລູກຄ້າ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_loyalty_view_<?php echo $u['Id']; ?>"
             data-perm="loyalty"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['loyalty']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'loyalty', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_loyalty_edit_<?php echo $u['Id']; ?>"
             data-perm="loyalty"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['loyalty']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'loyalty', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<!-- 7.5 ຖານຂໍ້ມູນ (Database Management) -->
<tr>
  <td class="pl-4">
    <div class="perm-module-info">
      <div class="perm-module-icon" style="background: #e0f2fe; color: #0284c7;">
        <i class="fas fa-database"></i>
      </div>
      <div>
        <div class="perm-module-title"><?php echo htmlspecialchars(t('permissions.module_database_title', 'ຈັດການຖານຂໍ້ມູນ')); ?></div>
        <div class="perm-module-desc"><?php echo htmlspecialchars(t('permissions.module_database_desc', 'ເຂົ້າເຖິງ ແລະ ເບິ່ງສະຖິຕິ/ຈັດການຖານຂໍ້ມູນລະບົບ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_database_view_<?php echo $u['Id']; ?>" 
             data-perm="database" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['database']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'database', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>
