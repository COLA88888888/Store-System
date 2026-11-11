<!-- SECTION 3: ຈັດການລູກຄ້າ -->
<tr class="table-primary">
  <td colspan="5" class="font-weight-bold text-uppercase py-2" style="font-size: 0.82rem; letter-spacing: 0.5px;">
    <i class="fas fa-user-friends mr-1"></i> <?php echo htmlspecialchars(t('permissions.section_customers', '3. ຈັດການລູກຄ້າ')); ?>
  </td>
</tr>

<!-- 3.1 ຈັດການລູກຄ້າ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-user-friends text-info mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_customers_title', 'ຈັດການລູກຄ້າ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_customers_desc', 'ເພີ່ມ, ແກ້ໄຂ ແລະ ຈັດການຂໍ້ມູນລູກຄ້າ/ສະມາຊິກ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_customers_view_<?php echo $u['Id']; ?>" 
             data-perm="customers" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['customers']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'customers', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_customers_add_<?php echo $u['Id']; ?>" 
             data-perm="customers" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['customers']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'customers', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_customers_edit_<?php echo $u['Id']; ?>" 
             data-perm="customers" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['customers']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'customers', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_customers_del_<?php echo $u['Id']; ?>" 
             data-perm="customers" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['customers']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'customers', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>
