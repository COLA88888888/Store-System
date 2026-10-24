<?php
/*
 * ສ່ວນ: Excel / PDF / ພິມ ສຳລັບທຸກຕາຕະລາງໃນໂມດູນເຊັກສະຕັອກ
 * ສ້າງເປັນຕາຕະລາງລາຍງານທຳມະດາ (ຫົວລາຍງານ + ຕາຕະລາງມີຂອບ + ແຖວລວມ) ຈາກຂໍ້ມູນ ບໍ່ແມ່ນຈາກໜ້າຈໍ,
 * ສະນັ້ນ ໄດ້ຂໍ້ມູນຄົບທຸກໜ້າ ແລະ ບໍ່ມີປຸ່ມ/ຊ່ອງປ້ອນຕິດໄປນຳ.
 * ແຕ່ລະໜ້າກຳນົດ scGetReport() ທີ່ສົ່ງຄືນ { title, fileBase, meta: [..], headers: [{t, a}], rows: [[..]], footer: [..]|null }
 * (a = 'l' | 'c' | 'r' ຈັດຊ້າຍ/ກາງ/ຂວາ). ຕົວສ້າງລາຍງານ (Phetsarath OT, ສີດຳທຳມະດາ) ໃຊ້ຮ່ວມກັບລາຍງານອື່ນ
 * ຢູ່ assets/js/report-export.js. ຖືກ require ຈາກ pages/stock_check/stock_check.php ຫຼັງ footer.
 */
?>
<script src="<?php echo $base_path; ?>assets/js/report-export.js?v=<?php echo @filemtime(dirname(__DIR__, 3) . '/assets/js/report-export.js'); ?>"></script>
<script>
RptExport.config({
  storeName: <?php echo json_encode($scStoreName, JSON_UNESCAPED_UNICODE); ?>,
  printedBy: <?php echo json_encode(trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '')) ?: ($_SESSION['username'] ?? ''), JSON_UNESCAPED_UNICODE); ?>
});
function scExportExcel() { RptExport.excel(scGetReport()); }
function scExportPDF()   { RptExport.pdf(scGetReport()); }
function scPrintReport() { RptExport.print(scGetReport()); }
</script>
