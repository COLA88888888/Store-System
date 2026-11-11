<?php
// Standalone Page: ລາຍງານການຂາຍທັງໝົດ (All Sales Invoices Report)
// Uses shared partials via reports_backend.php (view_mode=invoice)
$_GET['type'] = 'all_sales';
if (!isset($_GET['view_mode'])) {
    $_GET['view_mode'] = 'invoice';
}
// ຄ່າເລີ່ມຕົ້ນ: ສະແດງຕາມບິນ (invoice)

require_once __DIR__ . '/partials/reports_backend.php';
require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">
<!-- html2pdf Library for direct PDF file download -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div class="container-fluid p-3 p-md-4">
  <!-- Header & Page Meta -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap" style="row-gap: 10px;">
    <div>
      <h5 class="font-weight-bold text-dark mb-0" style="font-family: 'Nuanta', 'Noto Sans Lao', 'Souliyo', 'Boon', sans-serif;">
        <i class="fas fa-file-invoice-dollar text-success mr-2"></i> <?php echo htmlspecialchars(t('reports.title_all_sales', 'ລາຍງານການຂາຍທັງໝົດ')); ?>
      </h5>
    </div>

    <!-- Export & Print Buttons (Excel, PDF, Print) -->
    <div class="d-flex align-items-center justify-content-end ml-auto no-print" style="gap: 5px;">
      <button type="button" onclick="exportReportExcel('all')" class="btn btn-xs btn-success font-weight-bold px-2 d-inline-flex align-items-center shadow-sm" style="border-radius: 6px; height: 30px; font-size: 0.78rem; background: linear-gradient(135deg, #16a34a, #15803d); border: none;">
        <i class="fas fa-file-excel mr-1" style="font-size: 0.8rem;"></i> Excel
      </button>
      <button type="button" onclick="exportReportPDF('all')" class="btn btn-xs btn-danger font-weight-bold px-2 d-inline-flex align-items-center shadow-sm" style="border-radius: 6px; height: 30px; font-size: 0.78rem; background: linear-gradient(135deg, #dc2626, #b91c1c); border: none;">
        <i class="fas fa-file-pdf mr-1" style="font-size: 0.8rem;"></i> PDF
      </button>
      <button type="button" onclick="printReportTable('all')" class="btn btn-xs btn-primary font-weight-bold px-2 d-inline-flex align-items-center shadow-sm" style="border-radius: 6px; height: 30px; font-size: 0.78rem; background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
        <i class="fas fa-print mr-1" style="font-size: 0.8rem;"></i> <?php echo htmlspecialchars(t('reports.print_btn', 'ພິມ')); ?>
      </button>
    </div>
  </div>

  <!-- Search & Filter Controls (shared partial) -->
  <?php require_once __DIR__ . '/partials/reports_filter.php'; ?>

  <!-- Dedicated Invoice Sales Report Table (12 Columns: Status, Cash, Transfer, etc.) -->
  <?php require_once __DIR__ . '/partials/all_sales_table.php'; ?>

  <!-- Circular Blue Pagination (shared partial) -->
  <?php require_once __DIR__ . '/partials/reports_pagination.php'; ?>
</div>

<!-- Modal & JavaScript (shared partials) -->
<?php require_once __DIR__ . '/partials/reports_modal.php'; ?>
<?php require_once __DIR__ . '/partials/js/reports_js.php'; ?>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
