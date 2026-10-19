<!-- SECTION: ລູກຄ້າຕິດໜີ້ -->
<tr class="table-primary">
  <td colspan="5" class="font-weight-bold text-uppercase py-2" style="font-size: 0.82rem; letter-spacing: 0.5px;">
    <i class="fas fa-hand-holding-usd mr-1"></i> <?php echo htmlspecialchars(t('permissions.section_customer_debt', 'ລູກຄ້າຕິດໜີ້')); ?>
  </td>
</tr>

<!-- ລູກຄ້າຕິດໜີ້: ເບິ່ງ = ເປີດໜ້າ, ເພີ່ມ = ຂາຍຕິດໜີ້ໃນ POS / ເພີ່ມໜີ້ຍົກມາ, ແກ້ໄຂ = ຮັບຊຳລະໜີ້, ລົບ = ຍົກເລີກໜີ້ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-hand-holding-usd text-danger mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_customer_debt_title', 'ລູກຄ້າຕິດໜີ້')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_customer_debt_desc', 'ຂາຍຕິດໜີ້ໃນ POS, ຮັບຊຳລະໜີ້ ແລະ ຈັດການໜີ້ຂອງລູກຄ້າ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_customer_debt_view_<?php echo $u['Id']; ?>"
             data-perm="customer_debt"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['customer_debt']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'customer_debt', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_customer_debt_add_<?php echo $u['Id']; ?>"
             data-perm="customer_debt"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['customer_debt']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'customer_debt', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_customer_debt_edit_<?php echo $u['Id']; ?>"
             data-perm="customer_debt"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['customer_debt']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'customer_debt', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_customer_debt_del_<?php echo $u['Id']; ?>"
             data-perm="customer_debt"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['customer_debt']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'customer_debt', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
</tr>
