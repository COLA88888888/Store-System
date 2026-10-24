<?php
/**
 * ສ່ວນ: ແບ່ງໜ້າ ແລະ ສະຖານະບິນຕິດໜີ້
 * ຕັດແຖວຕາມໜ້າ, ດຶງສະຖານະໜີ້ຂອງບິນໃນໜ້າ (ຍັງບໍ່ຈ່າຍ/ຍັງບໍ່ຄົບ/ຈ່າຍແລ້ວ), ຟັງຊັນສ້າງປ້າຍ ແລະ
 * ຈັດກຸ່ມບິນໃນໂໝດລາຍການ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/partials/reports_backend.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// Slice $sales_data into $display_data for pagination
$total_records = count($sales_data);

if ($per_page === 'all') {
    $total_pages = 1;
    $page = 1;
    $display_data = $sales_data;
    $start_record = ($total_records > 0) ? 1 : 0;
    $end_record = $total_records;
} else {
    $total_pages = max(1, intval(ceil($total_records / $per_page)));
    if ($page > $total_pages) $page = $total_pages;

    $offset = ($page - 1) * $per_page;
    $display_data = array_slice($sales_data, $offset, $per_page);
    $start_record = ($total_records > 0) ? ($offset + 1) : 0;
    $end_record = min($offset + $per_page, $total_records);
}

// ໂມດູນລູກຄ້າຕິດໜີ້ ຖືກລຶບອອກແລ້ວ (ຕາຕະລາງ st_customer_debts ບໍ່ມີແລ້ວ) — ບໍ່ມີປ້າຍໜີ້ຄ້າງ;
// ຕົວແປຍັງຢູ່ (ຫວ່າງ) ເພາະຕາຕະລາງລາຍງານສົ່ງໃຫ້ reportDebtBadge / reportBillStatusBadge ຂ້າງລຸ່ມ.
$openDebtByBill = [];
$debtStatusByBill = [];
$debtPaidByBill = [];
if (!function_exists('reportBillStatusBadge')) {
    // Status column: a credit bill reads ຍັງບໍ່ທັນຈ່າຍ / ຍັງບໍ່ຄົບ until its debt is fully paid —
    // with "ຈ່າຍແລ້ວ: X / ຄ້າງ: Y" under it — then ຈ່າຍແລ້ວ like any other bill.
    function reportBillStatusBadge($pdo, array $row, array $debtStatusByBill, array $openDebtByBill, array $debtPaidByBill = []) {
        $style = 'font-size: 0.76rem;';
        if (($row['status'] ?? 'SUCCESS') === 'CANCEL') {
            return '<span class="badge badge-danger px-2 py-1" style="' . $style . '">' . htmlspecialchars(t('reports.status_cancel', 'ຍົກເລີກ')) . '</span>';
        }
        $bill = $row['bill_no'];
        $ds = $debtStatusByBill[$bill] ?? null;
        if ($ds === 'unpaid' || $ds === 'partial') {
            $partial = $ds === 'partial';
            return '<span class="badge px-2 py-1" style="' . $style . 'background:' . ($partial ? '#f59e0b' : '#dc2626') . ';color:#fff;">'
                . htmlspecialchars($partial ? t('reports.status_partial', 'ຍັງບໍ່ຄົບ') : t('reports.status_unpaid', 'ຍັງບໍ່ທັນຈ່າຍ')) . '</span>'
                . '<div style="font-size:0.74rem;line-height:1.45;margin-top:3px;white-space:nowrap;">'
                . '<div style="color:#15803d;">' . htmlspecialchars(t('reports.debt_paid_label', 'ຈ່າຍແລ້ວ:')) . ' ' . displayMoney($pdo, $debtPaidByBill[$bill] ?? 0) . '</div>'
                . '<div style="color:#dc2626;font-weight:700;">' . htmlspecialchars(t('reports.debt_owed_label', 'ຄ້າງ:')) . ' ' . displayMoney($pdo, $openDebtByBill[$bill] ?? 0) . '</div>'
                . '</div>';
        }
        return '<span class="badge badge-success px-2 py-1" style="' . $style . '">' . htmlspecialchars(t('reports.status_paid', 'ຈ່າຍແລ້ວ')) . '</span>';
    }
}
if (!function_exists('reportDebtBadge')) {
    // Red "ຕິດໜີ້ <balance>" under the bill number while the bill is still owed.
    function reportDebtBadge($pdo, $billNo, array $openDebtByBill) {
        if (!isset($openDebtByBill[$billNo])) return '';
        return '<div class="report-debt-badge" style="color:#dc2626;font-weight:700;font-size:0.78rem;line-height:1.5;white-space:nowrap;"><i class="fas fa-hand-holding-usd mr-1"></i>'
            . htmlspecialchars(t('reports.debt_badge', 'ຕິດໜີ້')) . ' '
            . displayMoney($pdo, $openDebtByBill[$billNo]) . '</div>';
    }
}

$grouped_bills = [];
if ($view_mode === 'item') {
    foreach ($display_data as $row) {
        $bno = $row['bill_no'];
        if (!isset($grouped_bills[$bno])) {
            $grouped_bills[$bno] = [
                'bill_no'   => $bno,
                'date_time' => $row['date_time'],
                'cashier'   => $row['cashier'],
                'currency'  => $row['currency'] ?? 'KIP',
                'items'     => [],
                'bill_net'  => 0,
            ];
        }
        $grouped_bills[$bno]['items'][] = $row;
        $grouped_bills[$bno]['bill_net'] += $row['net'];
    }
}
