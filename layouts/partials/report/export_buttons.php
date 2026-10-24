<?php
/*
 * ສ່ວນ: ປຸ່ມ Excel / PDF / ພິມ ຂອງລາຍງານ (ແບບດຽວກັນທົ່ວລະບົບ — ສີຂຽວ / ແດງ / ຟ້າ).
 * ກົດແລ້ວເອີ້ນ rptExport('excel'|'pdf'|'print') ທີ່ layouts/partials/report/export_js.php ສ້າງໄວ້:
 * ຖ້າໜ້າຍັງບໍ່ໄດ້ສະແດງທັງໝົດ ຈະໂຫຼດໜ້າໃໝ່ດ້ວຍ per_page=all ກ່ອນ ແລ້ວຈຶ່ງພິມ / ອອກໄຟລ໌.
 * ສະໄຕລ໌: themes/report-table.css (.rpt-btn). ໃຊ້ໃນ: pages/reports/expiry_report.php
 */
?>
<div class="rpt-export no-print">
  <button type="button" class="btn rpt-btn rpt-btn-excel" onclick="rptExport('excel')"><i class="fas fa-file-excel"></i> Excel</button>
  <button type="button" class="btn rpt-btn rpt-btn-pdf" onclick="rptExport('pdf')"><i class="fas fa-file-pdf"></i> PDF</button>
  <button type="button" class="btn rpt-btn rpt-btn-print" onclick="rptExport('print')"><i class="fas fa-print"></i> <?php echo htmlspecialchars(t('reports.print_btn', 'ພິມ')); ?></button>
</div>
