<!-- SECTION: ໃບສະເໜີລາຄາ -->
<tr class="table-primary">
  <td colspan="5" class="font-weight-bold text-uppercase py-2" style="font-size: 0.82rem; letter-spacing: 0.5px;">
    <i class="fas fa-file-invoice-dollar mr-1"></i> <?php echo htmlspecialchars(t('permissions.section_quotations', 'ໃບສະເໜີລາຄາ')); ?>
  </td>
</tr>

<!-- ໃບສະເໜີລາຄາ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-file-invoice-dollar text-warning mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_quotations_title', 'ໃບສະເໜີລາຄາ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_quotations_desc', 'ສ້າງ, ແກ້ໄຂ ແລະ ຈັດການໃບສະເໜີລາຄາໃຫ້ລູກຄ້າ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_quotations_view_<?php echo $u['Id']; ?>"
             data-perm="quotations"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['quotations']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'quotations', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_quotations_add_<?php echo $u['Id']; ?>"
             data-perm="quotations"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['quotations']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'quotations', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_quotations_edit_<?php echo $u['Id']; ?>"
             data-perm="quotations"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['quotations']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'quotations', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_quotations_del_<?php echo $u['Id']; ?>"
             data-perm="quotations"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['quotations']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'quotations', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>
