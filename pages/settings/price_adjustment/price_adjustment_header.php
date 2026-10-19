<!-- Top Header Row -->
<div class="row mb-3 align-items-center">
  <div class="col-sm-6 col-md-5">
    <h5 class="m-0 font-weight-bold" style="color: #1e293b; font-size: 1.15rem;">
      <i class="fas fa-tags text-primary mr-2"></i> <?php echo htmlspecialchars(t('price_adjustment.header_title2', 'ປັບລາຄາຂາຍສິນຄ້າ')); ?>
    </h5>
  </div>
  <div class="col-sm-6 col-md-7 text-right d-flex align-items-center justify-content-end" style="gap: 10px;">
    <?php if ($canAllBranches): ?>
      <form method="GET" action="" class="m-0 d-inline-block">
        <select name="store_id" class="form-control form-control-sm font-weight-bold border-warning text-warning" style="height: 38px; border-radius: 8px; background-color: #fffbeb; min-width: 170px;" onchange="this.form.submit()">
          <?php foreach ($stores as $st): ?>
            <option value="<?php echo $st['store_id']; ?>" <?php echo ($filter_store == $st['store_id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('price_adjustment.badge_main_branch', '(ສາງຫຼັກ)')) : ''; ?>
            </option>
          <?php endforeach; ?>
          <option value="0" <?php echo ((int)$filter_store === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('price_adjustment.opt_all_branches', '-- ທຸກສາຂາ --')); ?></option>
        </select>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($message !== ''): ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      Swal.fire({
        icon: '<?php echo $message_type === "success" ? "success" : "error"; ?>',
        title: '<?php echo $message_type === "success" ? t('price_adjustment.swal_success_title', 'ສຳເລັດ') : t('price_adjustment.swal_error_title', 'ແຈ້ງເຕືອນ'); ?>',
        text: '<?php echo addslashes($message); ?>',
        confirmButtonColor: '#2563eb'
      });
    });
  </script>
<?php endif; ?>