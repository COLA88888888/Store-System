<?php
// Report Main Controller View - Modular Architecture
require_once __DIR__ . '/partials/reports_backend.php';
require_once __DIR__ . '/../../layouts/header.php';

// Define Meta titles per report type
$report_metas = [
    'daily'        => ['title' => 'ລາຍງານປະຈຳວັນ', 'icon' => 'fas fa-calendar-day text-info'],
    'all_sales'    => ['title' => 'ລາຍງານການຂາຍທັງໝົດ', 'icon' => 'fas fa-file-invoice-dollar text-success'],
    'best_seller'  => ['title' => 'ລາຍງານສິນຄ້າຂາຍດີ', 'icon' => 'fas fa-fire text-danger'],
    'profit_cost'  => ['title' => 'ລາຍງານກຳໄລ-ຕົ້ນທຶນ', 'icon' => 'fas fa-chart-line text-warning'],
    'financial'    => ['title' => 'ລາຍງານການເງິນ', 'icon' => 'fas fa-wallet text-info'],
    'category'     => ['title' => 'ລາຍງານຕາມ/ປະເພດສິນຄ້າ', 'icon' => 'fas fa-layer-group text-primary'],
    'delete_bills' => ['title' => 'ລາຍງານປະຫວັດລຶບບິນຂາຍ', 'icon' => 'fas fa-trash-alt text-danger']
];
$current_meta = $report_metas[$type] ?? ['title' => 'ລາຍງານການຂາຍ', 'icon' => 'fas fa-chart-bar text-primary'];
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">

<div class="container-fluid p-3 p-md-4">

  <!-- Header & Page Meta -->
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-2">
    <div>
      <h3 class="font-weight-bold text-dark mb-1 report-header-title" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="<?php echo $current_meta['icon']; ?> mr-2"></i> <?php echo htmlspecialchars($current_meta['title']); ?>
      </h3>
      <small class="text-muted report-header-subtitle">ລາຍງານສຳຫຼວດຍອດຂາຍ ແລະ ປະຫວັດທຸລະກຳຂາຍສິນຄ້າ</small>
    </div>
  </div>

  <!-- 1. Search & Filter Bar Partial -->
  <?php require_once __DIR__ . '/partials/reports_filter.php'; ?>

  <!-- 2. KPI Summary Cards Partial -->
  <?php require_once __DIR__ . '/partials/reports_kpi.php'; ?>

  <!-- 3. Main Sales Report Table Partial -->
  <?php require_once __DIR__ . '/partials/reports_table.php'; ?>

  <!-- 4. Circular Blue Pagination Partial -->
  <?php require_once __DIR__ . '/partials/reports_pagination.php'; ?>

</div>

<!-- 5. Bill Details Modal Partial (fetches $company itself if not already set) -->
<?php require_once __DIR__ . '/partials/reports_modal.php'; ?>

<!-- 6. JavaScript Handler Partial -->
<?php require_once __DIR__ . '/partials/js/reports_js.php'; ?>

<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
