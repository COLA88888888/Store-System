<!-- SECTION 6: ລາຍງານ (REPORTS) -->
<tr class="table-primary">
  <td colspan="5" class="font-weight-bold text-uppercase py-2" style="font-size: 0.95rem; letter-spacing: 0.5px;">
    <i class="fas fa-file-invoice-dollar mr-1"></i> <?php echo htmlspecialchars(t('permissions.section_reports', '6. ລາຍງານ')); ?>
  </td>
</tr>

<!-- 6.1 ລາຍງານປະຈຳວັນ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-calendar-day text-info mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_daily_report_title', 'ລາຍງານປະຈຳວັນ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_daily_report_desc', 'ເບິ່ງສະຫຼຸບຍອດຂາຍ ແລະ ປະຫວັດປະຈຳວັນ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_daily_report_view_<?php echo $u['Id']; ?>" 
             data-perm="daily_report" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['daily_report']) || !empty($u['report']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'daily_report', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<!-- 6.2 ລາຍງານການຂາຍທັງໝົດ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-file-invoice-dollar text-success mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_all_sales_title', 'ລາຍງານການຂາຍທັງໝົດ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_all_sales_desc', 'ເບິ່ງລາຍງານການຂາຍລວມທັງໝົດຕາມໄລຍະເວລາ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_all_sales_view_<?php echo $u['Id']; ?>" 
             data-perm="all_sales" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['all_sales']) || !empty($u['report']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'all_sales', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<!-- 6.3 ລາຍງານສິນຄ້າຂາຍດີ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-fire text-danger mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_best_seller_title', 'ລາຍງານສິນຄ້າຂາຍດີ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_best_seller_desc', 'ເບິ່ງອັນດັບສິນຄ້າທີ່ຂາຍດີທີ່ສຸດ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_best_seller_view_<?php echo $u['Id']; ?>" 
             data-perm="best_seller" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['best_seller']) || !empty($u['report']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'best_seller', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<!-- 6.4 ລາຍງານກຳໄລ-ຕົ້ນທຶນ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-chart-line text-warning mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_profit_cost_title', 'ລາຍງານກຳໄລ-ຕົ້ນທຶນ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_profit_cost_desc', 'ວິເຄາະຕົ້ນທຶນ, ລາຍຮັບ ແລະ ກຳໄລສຸດທິ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_profit_cost_view_<?php echo $u['Id']; ?>" 
             data-perm="profit_cost" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['profit_cost']) || !empty($u['report']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'profit_cost', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<!-- 6.5 ລາຍງານການເງິນ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-wallet text-info mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_financial_title', 'ລາຍງານການເງິນ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_financial_desc', 'ເບິ່ງສະຫຼຸບການຮັບເງິນສົດ/ໂອນ ຕາມຊ່ອງທາງ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_financial_view_<?php echo $u['Id']; ?>" 
             data-perm="financial" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['financial']) || !empty($u['report']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'financial', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="badge badge-light text-muted"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<!-- 6.6 ລາຍງານຕາມປະເພດສິນຄ້າ -->
<tr>
  <td class="pl-4">
    <div class="d-flex align-items-center">
      <i class="fas fa-layer-group text-primary mr-3" style="font-size: 1.2rem; width: 24px;"></i>
      <div>
        <div class="font-weight-bold text-dark" style="font-size: 0.95rem;"><?php echo htmlspecialchars(t('permissions.module_category_sales_title', 'ລາຍງານຕາມປະເພດສິນຄ້າ')); ?></div>
        <div class="text-muted" style="font-size: 0.8rem;"><?php echo htmlspecialchars(t('permissions.module_category_sales_desc', 'ເບິ່ງສະຖິຕິຍອດຂາຍແຍກຕາມໝວດໝູ່/ປະເພດ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_category_sales_<?php echo $u['Id']; ?>" 
             data-perm="category_sales" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['category_sales']) || !empty($u['report']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'category_sales', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<!-- 6.7 ປະຫວັດການລົບບິນຂາຍ -->
<tr class="bg-light">
  <td class="pl-4">
    <div class="perm-module-info">
      <i class="fas fa-trash-alt text-danger mr-2"></i>
      <div>
        <div class="perm-module-title" style="font-size: 0.92rem;"><?php echo htmlspecialchars(t('permissions.module_delete_bills_title', '↳ ປະຫວັດການລົບບິນຂາຍ')); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox" 
             id="perm_delete_bills_<?php echo $u['Id']; ?>" 
             data-perm="delete_bills" 
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u['delete_bills']) || !empty($u['report']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, 'delete_bills', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>

<?php
// 6.8 / 6.9 ລາຍງານປິດກະ ແລະ ລາຍງານລາຍຮັບຕາມກະ (ເມນູ ລາຍງານ › … — pages/reports/shift_close_report.php,
// shift_revenue_report.php) — ສະວິດເບິ່ງຂອງແຕ່ລະໜ້າ; ບໍ່ມີສະວິດ = ຕາມຖັນ report (hasPermission alias)
foreach ([
    ['shift_close_report',   'fa-cash-register', t('permissions.module_shift_close_report_title', '↳ ລາຍງານປິດກະ')],
    ['shift_revenue_report', 'fa-user-clock',    t('permissions.module_shift_revenue_report_title', '↳ ລາຍງານລາຍຮັບຕາມກະ')],
] as [$__shMod, $__shIcon, $__shTitle]):
?>
<tr class="bg-light">
  <td class="pl-4">
    <div class="perm-module-info">
      <i class="fas <?php echo $__shIcon; ?> text-info mr-2"></i>
      <div>
        <div class="perm-module-title" style="font-size: 0.92rem;"><?php echo htmlspecialchars($__shTitle); ?></div>
      </div>
    </div>
  </td>
  <td class="text-center">
    <label class="matrix-switch">
      <input type="checkbox"
             id="perm_<?php echo $__shMod; ?>_view_<?php echo $u['Id']; ?>"
             data-perm="<?php echo $__shMod; ?>"
             data-user-id="<?php echo $u['Id']; ?>"
             <?php echo (!empty($u[$__shMod]) || !empty($u['report']) || $isAdmin) ? 'checked' : ''; ?>
             <?php echo ($isAdmin) ? 'disabled' : ''; ?>
             onchange="toggleUserPerm(<?php echo $u['Id']; ?>, '<?php echo $__shMod; ?>', this)">
      <span class="matrix-slider"></span>
    </label>
  </td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
  <td class="text-center"><span class="perm-na-badge"><?php echo htmlspecialchars(t('permissions.na', 'ບໍ່ມີ')); ?></span></td>
</tr>
<?php endforeach; ?>
