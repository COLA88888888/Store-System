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
  <button type="button" onclick="scExportExcel()" class="btn btn-success font-weight-bold sc-exp-btn" style="background: linear-gradient(135deg, #16a34a, #15803d);">
    <i class="fas fa-file-excel mr-1"></i> Excel
  </button>
  <button type="button" onclick="scExportPDF()" class="btn btn-danger font-weight-bold sc-exp-btn" style="background: linear-gradient(135deg, #dc2626, #b91c1c);">
    <i class="fas fa-file-pdf mr-1"></i> PDF
  </button>
  <button type="button" onclick="scPrintReport()" class="btn btn-secondary font-weight-bold sc-exp-btn" style="background: linear-gradient(135deg, #475569, #334155);">
    <i class="fas fa-print mr-1"></i> ພິມ
  </button>
</div>
