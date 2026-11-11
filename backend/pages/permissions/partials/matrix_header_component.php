<!-- 1. Detail Header (ຂໍ້ມູນຜູ້ນຳໃຊ້ + ສາຂາ + ປຸ່ມກຳນົດສິດດ່ວນ) -->
<div class="perm-detail-header">
  <div class="d-flex align-items-center flex-wrap" style="width: 100%;">
    
    <!-- Avatar ຜູ້ໃຊ້ -->
    <div class="user-avatar-circle mr-3 <?php echo $isAdmin ? 'user-avatar-admin' : ''; ?>" style="width: 46px; height: 46px; font-size: 1.2rem;">
      <?php if ($u['Id'] == 1): ?>
        <i class="fas fa-crown text-white"></i>
      <?php else: ?>
        <?php echo mb_substr($displayName, 0, 1, 'UTF-8'); ?>
      <?php endif; ?>
    </div>

    <!-- ຊື່, ID, ບົດບາດ ແລະ ຊື່ສາຂາ (ສາຂາ [ຊື່ສາຂາ]) -->
    <div>
      <h5 class="font-weight-bold mb-1 text-dark" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif; font-size: 1.1rem;">
        <?php echo $displayName; ?>
        <?php 
          $uStatusClean = mb_strtolower(trim((string)$uStatus));
          if ($u['Id'] == 1): 
        ?>
          <span class="badge badge-danger ml-2 px-2 py-1" style="font-size: 0.75rem; border-radius: 6px;"><i class="fas fa-shield-alt mr-1"></i><?php echo htmlspecialchars(t('permissions.badge_super_admin', 'Admin')); ?></span>
        <?php elseif ($isRoleAdmin || $isAdmin || $uStatusClean === 'ຜູ້ບໍລິຫານ' || $uStatusClean === 'admin' || $uStatusClean === 'super admin' || $uStatusClean === 'administrator'): ?>
          <span class="badge badge-primary ml-2 px-2 py-1" style="font-size: 0.75rem; border-radius: 6px;"><i class="fas fa-user-shield mr-1"></i><?php echo htmlspecialchars(t('permissions.role_admin_badge', t('layout.role_admin', 'ຜູ້ບໍລິຫານ'))); ?></span>
        <?php elseif ($uStatusClean === 'ຜູ້ຈັດການ' || $uStatusClean === 'manager'): ?>
          <span class="badge badge-dark ml-2 px-2 py-1" style="font-size: 0.75rem; border-radius: 6px;"><i class="fas fa-user-tie mr-1"></i><?php echo htmlspecialchars(t('layout.role_manager', 'ຜູ້ຈັດການ')); ?></span>
        <?php else: ?>
          <span class="badge badge-secondary ml-2 px-2 py-1" style="font-size: 0.75rem; border-radius: 6px;"><i class="fas fa-user mr-1"></i><?php echo htmlspecialchars(t('layout.role_staff', 'ພະນັກງານ')); ?></span>
        <?php endif; ?>
      </h5>
      <div class="text-muted" style="font-size: 0.85rem;">
        <i class="fas fa-store text-primary mr-1"></i>
        <?php
          $branchPrefix = t('permissions.branch_prefix', 'ສາຂາ');
          $bName = !empty($u['store_name']) ? $u['store_name'] : ($branchPrefix . ' #' . ($u['store_id'] ?? '1'));
          echo htmlspecialchars(mb_strpos($bName, 'ສາຂາ') === 0 ? $bName : ($branchPrefix . ' ' . $bName));
        ?>
      </div>
    </div>

    <!-- ປຸ່ມກຳນົດສິດດ່ວນ (Quick Presets Dropdown) -->
    <div class="ml-auto mt-2 mt-md-0">
      <?php if (!$isAdmin): ?>
        <div class="dropdown">
          <button class="btn btn-warning text-dark font-weight-bold shadow-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false" style="border-radius: 10px; font-size: 0.88rem; padding: 8px 16px;">
            <i class="fas fa-magic mr-1"></i> <?php echo htmlspecialchars(t('permissions.quick_preset_btn', 'ກຳນົດສິດດ່ວນ')); ?>
          </button>
          <div class="dropdown-menu dropdown-menu-right shadow-lg border-0" style="border-radius: 12px; min-width: 220px;">
            <h6 class="dropdown-header text-uppercase font-weight-bold text-muted" style="font-size: 0.72rem;"><?php echo htmlspecialchars(t('permissions.quick_preset_header', 'ເລືອກຮູບແບບສິດ (Presets)')); ?></h6>
            <a class="dropdown-item py-2" href="javascript:void(0)" onclick="applyRolePreset(<?php echo $u['Id']; ?>, 'cashier')">
              <i class="fas fa-cash-register text-success mr-2"></i> <?php echo htmlspecialchars(t('permissions.preset_cashier', 'ພະນັກງານຂາຍ POS')); ?>
            </a>
            <a class="dropdown-item py-2" href="javascript:void(0)" onclick="applyRolePreset(<?php echo $u['Id']; ?>, 'accountant')">
              <i class="fas fa-calculator text-primary mr-2"></i> <?php echo htmlspecialchars(t('permissions.preset_accountant', 'ຄົນຈັດການບັນຊີ')); ?>
            </a>
            <a class="dropdown-item py-2" href="javascript:void(0)" onclick="applyRolePreset(<?php echo $u['Id']; ?>, 'stock_keeper')">
              <i class="fas fa-boxes text-warning mr-2"></i> <?php echo htmlspecialchars(t('permissions.preset_stock_keeper', 'ພະນັກງານຄັງສິນຄ້າ')); ?>
            </a>
            <a class="dropdown-item py-2" href="javascript:void(0)" onclick="applyRolePreset(<?php echo $u['Id']; ?>, 'auditor')">
              <i class="fas fa-search-dollar text-info mr-2"></i> <?php echo htmlspecialchars(t('permissions.preset_auditor', 'ຜູ້ກວດສອບບັນຊີ')); ?>
            </a>
            <a class="dropdown-item py-2" href="javascript:void(0)" onclick="applyRolePreset(<?php echo $u['Id']; ?>, 'manager')">
              <i class="fas fa-user-tie text-purple mr-2"></i> <?php echo htmlspecialchars(t('permissions.preset_manager', 'ຜູ້ບໍລິຫານ / ຈັດການທັງໝົດ')); ?>
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item py-2 text-success font-weight-bold" href="javascript:void(0)" onclick="applyRolePreset(<?php echo $u['Id']; ?>, 'all_on')">
              <i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars(t('permissions.preset_all_on', 'ເປີດທຸກສິດ')); ?>
            </a>
            <a class="dropdown-item py-2 text-danger font-weight-bold" href="javascript:void(0)" onclick="applyRolePreset(<?php echo $u['Id']; ?>, 'all_off')">
              <i class="fas fa-times-circle mr-2"></i> <?php echo htmlspecialchars(t('permissions.preset_all_off', 'ປິດທຸກສິດ')); ?>
            </a>
          </div>
        </div>
      <?php elseif (($u['Id'] ?? 0) == 1): ?>
        <span class="badge badge-success px-3 py-2" style="border-radius: 8px; font-size: 0.82rem;">
          <i class="fas fa-check-double mr-1"></i> <?php echo htmlspecialchars(t('permissions.admin_full_badge', 'ເປີດສິດ 100% (ຜູ້ບໍລິຫານ)')); ?>
        </span>
      <?php else: ?>
        <span class="badge badge-warning text-dark px-3 py-2" style="border-radius: 8px; font-size: 0.82rem;">
          <i class="fas fa-lock mr-1"></i> <?php echo htmlspecialchars(t('permissions.admin_locked_badge', 'ມີແຕ່ຜູ້ບໍລິຫານຄົນທຳອິດເທົ່ານັ້ນທີ່ແກ້ໄຂໄດ້')); ?>
        </span>
      <?php endif; ?>
    </div>
  </div>
</div>
