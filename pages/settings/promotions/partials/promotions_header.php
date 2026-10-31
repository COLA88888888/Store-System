<?php echo themeCssLink('settings-promotions-header.css'); /* CSS ຍ້າຍໄປ themes/settings-promotions-header.css */ ?>

<!-- Page Header Title -->
<div class="row mb-3 align-items-center">
  <div class="col-sm-6 col-md-5">
    <h5 class="m-0 font-weight-bold" style="color: #1e293b; font-size: 1.15rem;">
      <i class="fas fa-tags text-primary mr-2"></i> <?php echo htmlspecialchars(t('promotions.title', 'ຈັດການໂປຣໂມຊັ່ນ')); ?>
    </h5>
  </div>
  <div class="col-sm-6 col-md-7 text-right d-flex align-items-center justify-content-end gap-2" style="gap: 10px;">
    <?php if ($canAllBranches && count($stores) > 1): /* ສາຂາດຽວ → ເຊື່ອງບັອກເລືອກສາຂາ */ ?>
      <form method="GET" action="" class="m-0 d-inline-block">
        <select name="branch_id" class="form-control form-control-sm font-weight-bold border-primary text-primary" style="height: 38px; border-radius: 8px; background-color: #eff6ff; min-width: 170px;" onchange="this.form.submit()">
          <?php foreach ($stores as $st): ?>
            <option value="<?php echo $st['store_id']; ?>" <?php echo ($filter_branch == $st['store_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('promotions.main_branch_suffix', '(ສາງຫຼັກ)')) : ''; ?>
            </option>
          <?php endforeach; ?>
          <option value="0" <?php echo ((int)$filter_branch === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('promotions.all_branches', '-- ທຸກສາຂາ --')); ?></option>
        </select>
      </form>
    <?php endif; ?>
    <?php if (hasPermission('promotions', 'add')): ?>
      <button type="button" class="btn btn-primary px-3.5 font-weight-bold text-white shadow-sm" data-toggle="modal" data-target="#addPromoModal" style="border-radius: 6px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
        <i class="fas fa-plus-circle mr-1.5"></i> <?php echo htmlspecialchars(t('promotions.new_promo_btn', 'ສ້າງໂປຣໂມຊັ່ນໃໝ່')); ?>
      </button>
    <?php endif; ?>
  </div>
</div>

<!-- Auto-closing Toast notification -->
<?php if ($message !== ''): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      Swal.fire({
        toast: true,
        position: 'top-end',
        icon: '<?php echo $message_type === "success" ? "success" : ($message_type === "warning" ? "warning" : "error"); ?>',
        title: <?php echo json_encode($message); ?>,
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true
      });
    });
  </script>
<?php endif; ?>
