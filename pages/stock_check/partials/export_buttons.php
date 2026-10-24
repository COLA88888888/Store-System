<?php
/*
 * ສ່ວນ: ປຸ່ມ Excel / PDF / ພິມ (ແບບດຽວກັບໜ້າລາຍງານ). ຟັງຊັນຢູ່ໃນ export_js.php.
 * $scExpPlace = 'top' (ຄ່າເລີ່ມຕົ້ນ): ຂວາມືຂອງຫົວຕາຕະລາງ — ສະແດງສະເພາະຈໍໃຫຍ່
 *             = 'bottom': ແຖວເຕັມຄວາມກວ້າງ ລຸ່ມສຸດຂອງບັດ — ສະແດງສະເພາະໂທລະສັບ
 * ຖືກ require ຈາກ list_view.php, history_view.php ແລະ count_view.php (ສອງຄັ້ງ: top + bottom).
 */
$scExpBottom = (($scExpPlace ?? 'top') === 'bottom');
$scExpPlace = 'top';   // ຄືນຄ່າເລີ່ມຕົ້ນ ສຳລັບການ require ຄັ້ງຕໍ່ໄປ
?>
<div class="<?php echo $scExpBottom ? 'd-flex d-md-none sc-export-mobile' : 'd-none d-md-inline-flex ml-auto'; ?> align-items-center sc-no-print sc-export-btns">
  <?php /* ສີປຸ່ມ: .rpt-btn-* ໃນ themes/report-table.css (ຂຽວ / ແດງ / ຟ້າ — ແບບດຽວກັບລາຍງານອື່ນ) */ ?>
  <button type="button" onclick="scExportExcel()" class="btn sc-exp-btn rpt-btn rpt-btn-excel">
    <i class="fas fa-file-excel"></i> Excel
  </button>
  <button type="button" onclick="scExportPDF()" class="btn sc-exp-btn rpt-btn rpt-btn-pdf">
    <i class="fas fa-file-pdf"></i> PDF
  </button>
  <button type="button" onclick="scPrintReport()" class="btn sc-exp-btn rpt-btn rpt-btn-print">
    <i class="fas fa-print"></i> <?php echo htmlspecialchars(t('reports.print_btn', 'ພິມ')); ?>
  </button>
</div>
