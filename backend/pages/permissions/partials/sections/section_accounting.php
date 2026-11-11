<!-- SECTION 5: ຈັດການທະນາຄານ / ບັນຊີ -->
<tr class="table-primary">
  <td colspan="5" class="font-weight-bold text-uppercase py-2" style="font-size: 0.82rem; letter-spacing: 0.5px;">
    <i class="fas fa-university mr-1"></i> <?php echo htmlspecialchars(t('permissions.section_accounting', '5. ຈັດການທະນາຄານ & ບັນຊີ')); ?>
  </td>
</tr>

<!-- 5.1 ຈັດການທະນາຄານ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-university text-info mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_accounting_title', 'ຈັດການທະນາຄານ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_accounting_desc', 'ຕັ້ງຄ່າບັນຊີທະນາຄານ, ບັນຊີໂອນ ແລະ QR Code')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_accounting_view_<?php echo $u['Id']; ?>" 
             data-perm="accounting" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['accounting']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'accounting', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_accounting_add_<?php echo $u['Id']; ?>" 
             data-perm="accounting" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['accounting']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'accounting', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_accounting_edit_<?php echo $u['Id']; ?>" 
             data-perm="accounting" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['accounting']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'accounting', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_accounting_del_<?php echo $u['Id']; ?>" 
             data-perm="accounting" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['accounting']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'accounting', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>
