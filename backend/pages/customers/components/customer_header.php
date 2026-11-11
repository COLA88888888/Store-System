<!-- Component: Customer Header -->
<section class="content-header py-3">
  <div class="container-fluid">
    <div class="row align-items-center">
      <div class="col-sm-6 col-md-5">
        <h5 class="m-0 font-weight-bold text-dark" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif; font-size: 18px;">
          <i class="fas fa-user-friends text-info mr-2"></i> <?php echo htmlspecialchars(t('customers.title', 'ລາຍງານລູກຄ້າທັງໝົດ')); ?>
        </h5>
      </div>
      <div class="col-sm-6 col-md-7 text-right d-flex align-items-center justify-content-end mt-3 mt-sm-0" style="gap: 10px;">
        <?php if ($canAllBranches && count($stores) >= 2): ?>
          <form method="GET" action="" class="m-0 d-inline-block">
            <select name="store_id" class="form-control form-control-sm font-weight-bold border-info text-info" style="height: 38px; border-radius: 8px; background-color: #f0f9ff; min-width: 170px;" onchange="this.form.submit()">
              <?php foreach ($stores as $st): ?>
                <option value="<?php echo $st['store_id']; ?>" <?php echo ($filter_store == $st['store_id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('customers.main_branch_suffix', '(ສາງຫຼັກ)')) : ''; ?>
                </option>
              <?php endforeach; ?>
              <option value="0" <?php echo ((int)$filter_store === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('customers.all_branches', '-- ທຸກສາຂາ --')); ?></option>
            </select>
          </form>
        <?php endif; ?>
        <?php if (hasPermission('customers', 'add')): ?>
          <button type="button" class="btn btn-primary px-3 py-1 font-weight-bold shadow-sm" data-toggle="modal" data-target="#addCustomerModal" style="border-radius: 8px; height: 38px; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
            <i class="fas fa-user-plus mr-1"></i> <?php echo htmlspecialchars(t('customers.add_new', 'ເພີ່ມລູກຄ້າໃໝ່')); ?>
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
