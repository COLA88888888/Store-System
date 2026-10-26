<?php
// Standalone Page: ລາຍລະອຽດການຂາຍ (Sales Itemized Detail Report)
$_GET['type']      = 'all_sales';
$_GET['view_mode'] = $_GET['view_mode'] ?? 'item';

require_once __DIR__ . '/partials/reports_backend.php';
require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">

<div class="container-fluid p-3 p-md-4">
  <!-- Header & Page Meta -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
    <div>
      <h5 class="font-weight-bold text-dark mb-1" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-list-alt text-primary"></i> ລາຍລະອຽດການຂາຍ
      </h5>
    </div>
  </div>

  <!-- Search & Filter Controls -->
  <?php require_once __DIR__ . '/partials/reports_filter.php'; ?>

  <!-- KPI Summary Cards -->
  <?php require_once __DIR__ . '/partials/reports_kpi.php'; ?>

  <!-- Sales Detail Table -->
  <?php require_once __DIR__ . '/partials/reports_table.php'; ?>

  <!-- Pagination -->
  <?php require_once __DIR__ . '/partials/reports_pagination.php'; ?>
</div>

<!-- Modal & JavaScript -->
<?php require_once __DIR__ . '/partials/reports_modal.php'; ?>
<?php require_once __DIR__ . '/partials/js/reports_js.php'; ?>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
