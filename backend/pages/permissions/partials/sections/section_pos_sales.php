<!-- SECTION 2: ຂາຍສິນຄ້າ POS -->
<tr class="table-primary">
  <td colspan="5" class="font-weight-bold text-uppercase py-2" style="font-size: 0.82rem; letter-spacing: 0.5px;">
    <i class="fas fa-cash-register mr-1"></i> <?php echo htmlspecialchars(t('permissions.section_pos_sales', '2. ຂາຍສິນຄ້າ POS')); ?>
  </td>
</tr>

<!-- 2.1 ຂາຍສິນຄ້າ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-shopping-cart text-warning mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_sale_title', 'ຂາຍສິນຄ້າ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_sale_desc', 'ເຂົ້າເຖິງໜ້າຄິດເງິນ ແລະ ຂາຍສິນຄ້າ POS')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_sale_view_<?php echo $u['Id']; ?>" 
             data-perm="sale" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['sale']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'sale', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_sale_add_<?php echo $u['Id']; ?>" 
             data-perm="sale" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['sale']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'sale', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_sale_edit_<?php echo $u['Id']; ?>" 
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
             id="perm_sale_del_<?php echo $u['Id']; ?>" 
             data-perm="edit" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['edit']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'edit', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>

<!-- 2.2 ລາຍການຂາຍສິນຄ້າ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-list-alt text-purple mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_item_sales_title', '↳ ລາຍການຂາຍສິນຄ້າ (Sales List)')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_item_sales_desc', 'ເບິ່ງລາຍການບິນຂາຍ ແລະ ປະຫວັດການຂາຍ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_item_sales_view_<?php echo $u['Id']; ?>" 
             data-perm="item_sales" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['item_sales']) || !empty($u['sale']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'item_sales', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_item_sales_edit_<?php echo $u['Id']; ?>" 
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
             id="perm_item_sales_del_<?php echo $u['Id']; ?>" 
             data-perm="edit" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['edit']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'edit', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>
