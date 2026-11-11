<?php
/*
 * ຟັງຊັນຊ່ວຍ: ຕົວກັ່ນຕອງ "ກະ" ຂອງໜ້າລາຍງານ — ລະບົບກະເຮັດວຽກຖືກລຶບອອກ (09/11/2026), ຕາຕະລາງ w_shifts / w_shift_sessions ຖືກລຶບ.
 * ຄົງຟັງຊັນໄວ້ໃຫ້ໜ້າລາຍງານເກົ່າ (best_seller, financial, profit_cost, category_sales, daily_report, delete_bills,
 * reports_backend) ເອີ້ນໄດ້ຄືເກົ່າ: ຄືນຄ່າຫວ່າງສະເໝີ = ບໍ່ກັ່ນຕອງ ແລະ ບໍ່ສະແດງບັອກເລືອກກະ (partials/shift_filter_select.php).
 */
if (!function_exists('reportShiftFilter')) {
    function reportShiftFilter($pdo, $storeId, $alias = 's', $enabled = true) {
        return ['options' => [], 'id' => 0, 'sql' => ''];
    }
}
if (!function_exists('reportShiftQs')) {
    function reportShiftQs() {
        return '';
    }
}
