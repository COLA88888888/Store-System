<!-- SECTION 4: ຂໍ້ມູນສິນຄ້າ (PRODUCT & INVENTORY) -->
<tr class="table-primary">
  <td colspan="5" class="font-weight-bold text-uppercase py-2" style="font-size: 0.82rem; letter-spacing: 0.5px;">
    <i class="fas fa-boxes mr-1"></i> <?php echo htmlspecialchars(t('permissions.section_inventory', '4. ຂໍ້ມູນສິນຄ້າ & ຄັງສິນຄ້າ')); ?>
  </td>
</tr>

<!-- 4.1 ປະເພດສິນຄ້າ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-th-list text-info mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_categories_title', 'ໝວດໝູ່ສິນຄ້າ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_categories_desc', 'ຈັດການໝວດໝູ່ ແລະ ປະເພດສິນຄ້າ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_categories_view_<?php echo $u['Id']; ?>" 
             data-perm="categories" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['categories']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'categories', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_categories_add_<?php echo $u['Id']; ?>" 
             data-perm="categories" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['categories']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'categories', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_categories_edit_<?php echo $u['Id']; ?>" 
             data-perm="categories" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['categories']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'categories', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_categories_del_<?php echo $u['Id']; ?>" 
             data-perm="categories" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['categories']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'categories', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>

<!-- 4.1b ຫົວໜ່ວຍ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-ruler-combined text-info mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_units_title', 'ຈັດການຫົວໜ່ວຍ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_units_desc', 'ຈັດການຫົວໜ່ວຍສິນຄ້າ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_units_view_<?php echo $u['Id']; ?>"
             data-perm="units"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['units']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'units', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_units_add_<?php echo $u['Id']; ?>"
             data-perm="units"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['units']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'units', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_units_edit_<?php echo $u['Id']; ?>"
             data-perm="units"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['units']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'units', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_units_del_<?php echo $u['Id']; ?>"
             data-perm="units"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['units']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'units', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>

<!-- 4.2 ລາຍການສິນຄ້າ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-box text-primary mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_products_title', 'ລາຍການສິນຄ້າ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_products_desc', 'ເພີ່ມ, ແກ້ໄຂ, ປັບສະຕັອກ ແລະ ຈັດການສິນຄ້າ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_products_view_<?php echo $u['Id']; ?>" 
             data-perm="products" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['products']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'products', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_products_add_<?php echo $u['Id']; ?>" 
             data-perm="products" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['products']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'products', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_products_edit_<?php echo $u['Id']; ?>" 
             data-perm="products" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['products']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'products', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_products_del_<?php echo $u['Id']; ?>" 
             data-perm="products" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['products']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'products', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>

<!-- 4.2b ຈັດການຜູ້ສະໜອງ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-truck text-success mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_suppliers_title', 'ຈັດການຜູ້ສະໜອງ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_suppliers_desc', 'ເພີ່ມ, ແກ້ໄຂ ແລະ ຈັດການຂໍ້ມູນຜູ້ສະໜອງສິນຄ້າ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_suppliers_view_<?php echo $u['Id']; ?>"
             data-perm="suppliers"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['suppliers']) || !empty($u['products']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'suppliers', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_suppliers_add_<?php echo $u['Id']; ?>"
             data-perm="suppliers"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['suppliers']) || !empty($u['products']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'suppliers', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_suppliers_edit_<?php echo $u['Id']; ?>"
             data-perm="suppliers"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['suppliers']) || !empty($u['products']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'suppliers', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_suppliers_del_<?php echo $u['Id']; ?>"
             data-perm="suppliers"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['suppliers']) || !empty($u['products']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'suppliers', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>

<!-- 4.3 ນຳເຂົ້າສິນຄ້າ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-truck-loading text-warning mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_import_stock_title', 'ນຳເຂົ້າສິນຄ້າ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_import_stock_desc', 'ບັນທຶກການຮັບ ແລະ ນຳເຂົ້າສິນຄ້າໃໝ່')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_import_stock_view_<?php echo $u['Id']; ?>" 
             data-perm="import_stock" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['import_stock']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'import_stock', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_import_stock_add_<?php echo $u['Id']; ?>" 
             data-perm="import_stock" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['import_stock']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'import_stock', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<!-- 4.4 ລາຍການສິນຄ້າຮັບເຂົ້າ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-list-alt text-success mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_import_list_title', 'ລາຍການສິນຄ້າຮັບເຂົ້າ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_import_list_desc', 'ເບິ່ງປະຫວັດໃບບິນຮັບສິນຄ້າເຂົ້າ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_import_list_view_<?php echo $u['Id']; ?>" 
             data-perm="import_list" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['import_list']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'import_list', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_import_list_edit_<?php echo $u['Id']; ?>" 
             data-perm="edit" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['edit']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'edit', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_import_list_del_<?php echo $u['Id']; ?>" 
             data-perm="edit" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['edit']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'edit', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>

<!-- 4.4.1 ເຊັກສະຕັອກ (ຜູ້ຈັດການ): ເບິ່ງ / ເພີ່ມ = ເລີ່ມນັບໃໝ່ / ແກ້ໄຂ = ນັບ + ຢືນຢັນປັບສະຕັອກ / ລົບ = ລຶບໃບທີ່ຍັງບໍ່ຢືນຢັນ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-clipboard-check text-success mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_stock_check_title', 'ເຊັກສະຕັອກ (ຜູ້ຈັດການ)')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_stock_check_desc', 'ເພີ່ມ = ເລີ່ມນັບ, ແກ້ໄຂ = ຢືນຢັນປັບສະຕັອກ, ລົບ = ລຶບໃບ')); ?></div>
      </div>
    </div>
  </td>
  <?php foreach (['view', 'add', 'edit', 'del'] as $scAct): ?>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_stock_check_<?php echo $scAct; ?>_<?php echo $u['Id']; ?>"
             data-perm="stock_check"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['stock_check']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'stock_check', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <?php endforeach; ?>
</tr>

<!-- 4.4.2 ນັບສະຕັອກ (ພະນັກງານນັບ): ເບິ່ງ = ເບິ່ງໃບ/ປະຫວັດ + Excel/PDF/ພິມ, ແກ້ໄຂ = ປ້ອນ/ບັນທຶກຈຳນວນຕົວຈິງ.
     ເລີ່ມນັບ, ຢືນຢັນ ແລະ ລຶບ ບໍ່ມີ — ເປັນຂອງຜູ້ຈັດການ (ແຖວເທິງ). ບໍ່ເຫັນຜົນການນັບຈົນກວ່າໃບຈະຢືນຢັນ. -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-user-check text-info mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_stock_count_title', 'ນັບສະຕັອກ (ພະນັກງານນັບ)')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_stock_count_desc', 'ປ້ອນຈຳນວນຕົວຈິງ, ສົ່ງອອກ/ພິມລາຍງານ ເທົ່ານັ້ນ')); ?></div>
      </div>
    </div>
  </td>
  <?php foreach (['view' => true, 'add' => false, 'edit' => true, 'del' => false] as $scAct => $scHas): ?>
  <td class="text-center">
    <?php if ($scHas): ?>
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_stock_count_<?php echo $scAct; ?>_<?php echo $u['Id']; ?>"
             data-perm="stock_count"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['stock_count']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'stock_count', this)">
      <span class="matrix-slider"></span>
    </label>
    <?php else: ?>
    <span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span>
    <?php endif; ?>
  </td>
  <?php endforeach; ?>
</tr>

<!-- 4.5 ໂອນສິນຄ້າລະຫວ່າງສາຂາ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-exchange-alt text-primary mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_stock_transfer_title', 'ໂອນສິນຄ້າລະຫວ່າງສາຂາ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_stock_transfer_desc', 'ໂອນສິນຄ້າໄປສາຂາອື່ນ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_stock_transfer_view_<?php echo $u['Id']; ?>" 
             data-perm="stock_transfer" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['stock_transfer']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'stock_transfer', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_stock_transfer_add_<?php echo $u['Id']; ?>" 
             data-perm="stock_transfer" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['stock_transfer']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'stock_transfer', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<!-- 4.6 ປະຫວັດການໂອນສິນຄ້າ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-history text-info mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_transfer_history_title', 'ປະຫວັດການໂອນສິນຄ້າ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_transfer_history_desc', 'ຕິດຕາມ ແລະ ຍົກເລີກໃບໂອນສິນຄ້າ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_transfer_history_view_<?php echo $u['Id']; ?>" 
             data-perm="transfer_history" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['transfer_history']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'transfer_history', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>
