<?php
/*
 * ສ່ວນ: ເຊື່ອມໜ້າລາຍງານເຂົ້າກັບ assets/js/report-export.js (ພິມ / Excel / PDF — Phetsarath OT, ສີດຳທຳມະດາ).
 * ກ່ອນ require ໃຫ້ໜ້າຕັ້ງ $rptExport = [
 *     'table'    => '.exr-table',            // selector ຕາຕະລາງທີ່ຈະເອົາອອກ
 *     'title'    => 'ລາຍງານ…',
 *     'fileBase' => 'expiry_report',         // ຊື່ໄຟລ໌ (+ _YYYY-MM-DD.xls/.pdf)
 *     'store'    => 'ສາຂາ…',                 // ແຖວທີສອງຂອງຫົວລາຍງານ
 *     'meta'     => ['ເງື່ອນໄຂ…', …],         // ແຖວເງື່ອນໄຂ (ຕໍ່ກັນດ້ວຍ |)
 * ];
 * ສ້າງ rptExport(action): ຖ້າ URL ເປັນ per_page=all ຢູ່ແລ້ວ → ເຮັດເລີຍ, ບໍ່ດັ່ງນັ້ນໂຫຼດໜ້າໃໝ່ດ້ວຍລາຍການທັງໝົດ
 * ແລ້ວ RptExport.autoRun ສັ່ງໃຫ້ເອງຫຼັງໂຫຼດ. ໃຊ້ຄູ່ກັບ layouts/partials/report/export_buttons.php.
 */
$rptPrintedBy = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '')) ?: ($_SESSION['username'] ?? '');
?>
<script src="<?php echo $base_path; ?>assets/js/report-export.js?v=<?php echo @filemtime(dirname(__DIR__, 3) . '/assets/js/report-export.js'); ?>"></script>
<script>
(function () {
  var O = <?php echo json_encode($rptExport, JSON_UNESCAPED_UNICODE); ?>;
  RptExport.config({ printedBy: <?php echo json_encode($rptPrintedBy, JSON_UNESCAPED_UNICODE); ?> });
  function getReport() { return RptExport.fromTable(document.querySelector(O.table), O); }
  window.rptExport = function (action) {
    if (new URL(window.location.href).searchParams.get('per_page') === 'all') RptExport.run(action, getReport());
    else RptExport.reloadAll(action);
  };
  RptExport.autoRun(getReport);
})();
</script>
