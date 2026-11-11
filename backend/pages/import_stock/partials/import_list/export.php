<?php
/*
 * ສ່ວນ: Excel / PDF / ພິມ ລາຍການສິນຄ້າຮັບເຂົ້າ
 * ສ້າງຂໍ້ມູນລາຍງານຈາກ $importList (ທຸກແຖວໃນຊ່ວງວັນທີ — ແບ່ງໜ້າຢູ່ຝັ່ງ browser ຈຶ່ງຄົບແລ້ວ) ທັງແບບບິນ ແລະ ແບບລາຍລະອຽດ,
 * ກັ່ນຕອງຕາມຄຳຄົ້ນຫາໃນບັອກ #import_search ແບບດຽວກັບຕາຕະລາງ, ແລ້ວສົ່ງໃຫ້ assets/js/report-export.js
 * (ຕາຕະລາງມີເສັ້ນຂອບທຸກຊ່ອງ, ຫົວລາຍງານ ສາຂາ/ຊ່ວງວັນທີ, ແຖວລວມ, ພິມໂດຍ/ວັນທີພິມ).
 * ປຸ່ມ: layouts/partials/report/export_buttons.php ໃນຫົວກາດຕາຕະລາງ (import_list.php).
 * ໄຟລ໌ນີ້ຖືກ require ຈາກ pages/import_stock/import_list.php ຫຼັງ footer (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */
$ilExpCreator = function ($row) {
    $n = trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
    return $n !== '' ? $n : ($row['username'] ?? 'Admin');
};
$ilExpMoney = function ($v) use ($pdo) { return trim(html_entity_decode(strip_tags(displayMoney($pdo, $v)))); };
$ilExpDate = function ($d) { return !empty($d) ? date('d/m/Y', strtotime($d)) : '-'; };

$ilExpRows = [];
$ilExpTotal = 0.0;
foreach ($importList as $row) {
    $creator = $ilExpCreator($row);
    $cost = (float)($row['total_cost'] ?? 0);
    if ($view_type === 'bill') {
        $search = strtolower(($row['invoice_number'] ?? '') . ' ' . ($row['supplier_name'] ?? '') . ' ' . ($row['store_name'] ?? '') . ' ' . ($row['notes'] ?? '') . ' ' . $creator);
        $cells = [$ilExpDate($row['import_date'] ?? ''), $row['invoice_number'] ?? '', $row['store_name'] ?: t('import_list.main_store_default', 'ສາຂາຫຼັກ'),
                  $row['supplier_name'] ?: '-', number_format((float)($row['item_count'] ?? 0)), $ilExpMoney($cost), $creator, $row['notes'] ?: '-'];
    } else {
        $search = strtolower(($row['invoice_number'] ?? '') . ' ' . ($row['product_name'] ?? '') . ' ' . ($row['supplier_name'] ?? '') . ' ' . ($row['store_name'] ?? '') . ' ' . $creator);
        $qtyText = number_format((float)($row['quantity'] ?? 0)) . (!empty($row['unit_name']) ? ' ' . $row['unit_name'] : '');
        $baseText = number_format((float)($row['total_base_qty'] ?? 0)) . (!empty($row['base_unit']) ? ' ' . $row['base_unit'] : '');
        $cells = [$ilExpDate($row['import_date'] ?? ''), $row['invoice_number'] ?? '', $row['store_name'] ?: t('import_list.main_store_default', 'ສາຂາຫຼັກ'),
                  $row['product_name'] ?? '-', $qtyText, $baseText, $ilExpMoney($cost), $ilExpDate($row['expiry_date'] ?? ''), $creator];
    }
    // v = ມູນຄ່າໃນສະກຸນທີ່ສະແດງ (ສຳລັບແຖວລວມ)
    $ilExpRows[] = ['s' => $search, 'c' => $cells, 'v' => (float)str_replace(',', '', displayMoney($pdo, $cost, false))];
}

if ($view_type === 'bill') {
    $ilExpHeaders = [['t' => t('import_list.col_no', 'ລຳດັບ'), 'a' => 'c'], ['t' => t('import_list.col_import_date', 'ວັນທີຮັບເຂົ້າ'), 'a' => 'c'],
        ['t' => t('import_list.col_invoice_no', 'ເລກທີໃບບິນ'), 'a' => 'c'], ['t' => t('import_list.col_store', 'ສາຂາ')], ['t' => t('import_list.col_supplier', 'ຜູ້ສະໜອງ')],
        ['t' => t('import_list.col_item_count', 'ຈຳນວນລາຍການ'), 'a' => 'c'], ['t' => t('import_list.col_total_cost', 'ມູນຄ່າທຶນລວມ'), 'a' => 'r'],
        ['t' => t('import_list.col_creator', 'ຜູ້ບັນທຶກ')], ['t' => t('import_list.col_notes', 'ໝາຍເຫດ')]];
    $ilExpCostCol = 6;
} else {
    $ilExpHeaders = [['t' => t('import_list.col_no', 'ລຳດັບ'), 'a' => 'c'], ['t' => t('import_list.col_import_date', 'ວັນທີຮັບເຂົ້າ'), 'a' => 'c'],
        ['t' => t('import_list.col_invoice_no', 'ເລກທີໃບບິນ'), 'a' => 'c'], ['t' => t('import_list.col_store', 'ສາຂາ')], ['t' => t('import_list.col_product_name', 'ຊື່ສິນຄ້າ')],
        ['t' => t('import_list.col_received_qty', 'ຈຳນວນຮັບເຂົ້າ'), 'a' => 'c'], ['t' => t('import_list.col_total_base_qty', 'ຈຳນວນຍ່ອຍລວມ'), 'a' => 'c'],
        ['t' => t('import_list.col_total_cost', 'ມູນຄ່າທຶນລວມ'), 'a' => 'r'], ['t' => t('import_list.col_expiry_date', 'ວັນໝົດອາຍຸ'), 'a' => 'c'],
        ['t' => t('import_list.col_creator', 'ຜູ້ບັນທຶກ')]];
    $ilExpCostCol = 7;
}

$ilExpStoreName = '';
foreach ($stores as $__s) { if ((int)$__s['store_id'] === (int)$filter_store) $ilExpStoreName = $__s['store_name']; }
if ((int)$filter_store === 0) $ilExpStoreName = t('import_list.all_branches', 'ທຸກສາຂາ');
$ilExpPrintedBy = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '')) ?: ($_SESSION['username'] ?? '');
// ຮູບແບບເງິນຂອງສະກຸນທີ່ສະແດງ ("{n} ກີບ", "$ {n}"...) ແລະ ຈຳນວນທົດສະນິຍົມ — ແຖວລວມເປັນຮູບແບບດຽວກັບແຕ່ລະແຖວ
$ilExpZero = displayMoney($pdo, 0, false);
$ilExpMoneyTpl = str_replace($ilExpZero, '{n}', $ilExpMoney(0));
$ilExpDecimals = strpos($ilExpZero, '.') !== false ? strlen($ilExpZero) - strpos($ilExpZero, '.') - 1 : 0;
?>
<script src="<?php echo $base_path; ?>assets/js/report-export.js?v=<?php echo @filemtime(__DIR__ . '/../../../../assets/js/report-export.js'); ?>"></script>
<script>
RptExport.config({ storeName: <?php echo json_encode($ilExpStoreName, JSON_UNESCAPED_UNICODE); ?>, printedBy: <?php echo json_encode($ilExpPrintedBy, JSON_UNESCAPED_UNICODE); ?> });
var IL_EXPORT = {
  rows: <?php echo json_encode($ilExpRows, JSON_UNESCAPED_UNICODE); ?>,
  headers: <?php echo json_encode($ilExpHeaders, JSON_UNESCAPED_UNICODE); ?>,
  costCol: <?php echo (int)$ilExpCostCol; ?>,
  moneyTpl: <?php echo json_encode($ilExpMoneyTpl, JSON_UNESCAPED_UNICODE); ?>,
  decimals: <?php echo (int)$ilExpDecimals; ?>,
  isBill: <?php echo $view_type === 'bill' ? 'true' : 'false'; ?>,
  range: <?php echo json_encode(date('d/m/Y', strtotime($from_date)) . ' - ' . date('d/m/Y', strtotime($to_date))); ?>
};
// ຍອດລວມ ໃນຮູບແບບເງິນດຽວກັບແຖວ
function ilExpMoney(v) {
  var d = IL_EXPORT.decimals;
  var txt = v.toLocaleString('en-US', { minimumFractionDigits: d, maximumFractionDigits: d });
  return IL_EXPORT.moneyTpl.replace('{n}', txt);
}
window.rptExport = function (action) {
  var q = ($('#import_search').val() || '').toLowerCase().trim();
  var list = IL_EXPORT.rows.filter(function (r) { return !q || r.s.indexOf(q) !== -1; });   // ກົງກັບ filterImportHistory()
  var total = 0;
  var rows = list.map(function (r, i) { total += r.v; return [i + 1].concat(r.c); });
  var footer = null;
  if (rows.length) {
    footer = IL_EXPORT.headers.map(function () { return ''; });
    footer[1] = '<?php echo htmlspecialchars(t('import_list.total_prefix', 'ລວມ'), ENT_QUOTES); ?>';
    footer[IL_EXPORT.costCol] = ilExpMoney(total);
  }
  RptExport.run(action, {
    title: '<?php echo htmlspecialchars(t('import_list.page_title', 'ລາຍການສິນຄ້າຮັບເຂົ້າ'), ENT_QUOTES); ?>',
    fileBase: 'import_list',
    meta: ['<?php echo htmlspecialchars(t('import_list.export_date_range', 'ວັນທີ'), ENT_QUOTES); ?> ' + IL_EXPORT.range,
           IL_EXPORT.isBill ? '<?php echo htmlspecialchars(t('import_list.view_by_bill', 'ສະແດງຕາມໃບບິນ'), ENT_QUOTES); ?>' : '<?php echo htmlspecialchars(t('import_list.view_by_item', 'ສະແດງຕາມລາຍການສິນຄ້າ'), ENT_QUOTES); ?>',
           q ? '<?php echo htmlspecialchars(t('import_list.search_label', 'ຄົ້ນຫາ:'), ENT_QUOTES); ?> ' + q : '',
           '<?php echo htmlspecialchars(t('import_list.total_prefix', 'ລວມ'), ENT_QUOTES); ?> ' + rows.length + ' ' + (IL_EXPORT.isBill ? '<?php echo htmlspecialchars(t('import_list.unit_bills', 'ໃບບິນ'), ENT_QUOTES); ?>' : '<?php echo htmlspecialchars(t('import_list.unit_items', 'ລາຍການ'), ENT_QUOTES); ?>')].filter(Boolean),
    headers: IL_EXPORT.headers,
    rows: rows,
    footer: footer
  });
};
</script>
