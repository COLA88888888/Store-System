<?php
// Price Adjustment Page - Modular Clean Architecture
session_start();
$base_path = '../../../';
require_once __DIR__ . '/../../../config/db.php';

if (empty($_SESSION['user_id']) || (!hasPermission('price_adjustment'))) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

// Load Backend Logic & Data Fetching from centralized API directory
require_once __DIR__ . '/../../../api/price_adj_backend.php';

require_once __DIR__ . '/../../../layouts/header.php';
?>

<?php echo themeCssLink('settings-price-adjustment.css'); /* CSS ຍ້າຍໄປ themes/settings-price-adjustment.css */ ?>

<div class="container-fluid p-4">
  <!-- Header Title & Add Button -->
  <div class="d-flex flex-wrap align-items-center justify-content-between mb-3" style="gap: 10px;">
    <div>
      <h5 class="m-0 font-weight-bold text-dark" style="font-size: 1.1rem; white-space: nowrap;">
        <i class="fas fa-tags text-warning mr-2"></i> <?php echo htmlspecialchars(t('price_adjustment.header_title', 'ຈັດການປັບລາຄາສິນຄ້າ')); ?>
      </h5>
    </div>
    <div class="d-flex align-items-center justify-content-end ml-auto pa-head-actions" style="gap: 8px; flex-wrap: wrap;">
      <?php if ($canAllBranches): ?>
        <form method="GET" action="" class="m-0 d-inline-block pa-store-form">
          <select name="store_id" class="form-control form-control-sm font-weight-bold border-warning text-warning" style="height: 38px; border-radius: 8px; background-color: #fffbeb; min-width: 130px;" onchange="this.form.submit()">
            <?php foreach ($stores as $st): ?>
              <option value="<?php echo $st['store_id']; ?>" <?php echo ($filter_store == $st['store_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($st['store_name']); ?> <?php echo !empty($st['is_main']) ? htmlspecialchars(t('price_adjustment.badge_main_branch', '(ສາງຫຼັກ)')) : ''; ?>
              </option>
            <?php endforeach; ?>
            <option value="0" <?php echo ((int)$filter_store === 0) ? 'selected' : ''; ?>><?php echo htmlspecialchars(t('price_adjustment.opt_all_branches', '-- ທຸກສາຂາ --')); ?></option>
          </select>
        </form>
      <?php endif; ?>
      <?php if (hasPermission('price_adjustment', 'add')): ?>
        <button type="button" class="btn btn-primary font-weight-bold text-white shadow-sm px-3 py-2" data-toggle="modal" data-target="#priceAdjModal" style="border-radius: 6px; font-size: 0.88rem; height: 38px; white-space: nowrap; background: linear-gradient(135deg, #2c5aa0, #244886); border: none;">
          <i class="fas fa-plus-circle mr-1.5"></i> <?php echo htmlspecialchars(t('price_adjustment.btn_add_new', 'ປັບລາຄາສິນຄ້າໃໝ່')); ?>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Toast Notification -->
  <?php if ($message !== ''): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: '<?php echo $message_type === "success" ? "success" : "error"; ?>',
          title: <?php echo json_encode($message); ?>,
          showConfirmButton: false,
          timer: 1500,
          timerProgressBar: true
        });
      });
    </script>
  <?php endif; ?>

  <!-- Modal Form Partial -->
  <?php require_once __DIR__ . '/partials/price_adj_modal.php'; ?>

  <!-- History Report Table Partial -->
  <div class="row">
    <div class="col-12 mb-4">
      <?php require_once __DIR__ . '/partials/price_adj_table.php'; ?>
    </div>
  </div>
</div>

<!-- JavaScript Functionality Partial -->
<?php require_once __DIR__ . '/partials/price_adj_js.php'; ?>

<?php require_once __DIR__ . '/../../../layouts/footer.php'; ?>
