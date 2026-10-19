<?php
/*
 * ສ່ວນ: ແຖວສິດ: ອັດຕາແລກປ່ຽນ → ຖານຂໍ້ມູນ
 * ແຖວສະວິດ ເບິ່ງ/ເພີ່ມ/ແກ້ໄຂ/ລົບ ຂອງ: ອັດຕາແລກປ່ຽນ, ໂປຣໂມຊັ່ນ, ປັບລາຄາ, ເຄື່ອງພິມ, ຄະແນນສະສົມ ແລະ
 * ຖານຂໍ້ມູນ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/permissions/partials/sections/section_setup.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
?><!-- 7.6 ອັດຕາແລກປ່ຽນເງິນ (Exchange Rate) -->
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
  <?php // add = new reward, edit = earning rule + edit / switch rewards, delete = remove a reward (pages/settings/loyalty/index.php) ?>
  <?php foreach (['add', 'edit', 'del'] as $__act): ?>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_loyalty_<?php echo $__act; ?>_<?php echo $u['Id']; ?>"
             data-perm="loyalty"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['loyalty']) || !empty($u['setup']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'loyalty', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <?php endforeach; ?>
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
