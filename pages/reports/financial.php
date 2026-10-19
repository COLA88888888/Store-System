<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * ລາຍງານການເງິນ: ຕາຕະລາງເງິນສົດ/ເງິນໂອນ/ເງິນທອນ/ຮັບຈິງ ຕໍ່ບິນ (ລວມການຊຳລະໜີ້ຂອງບິນຕິດໜີ້) ພ້ອມສະຖານະ.
 *   - partials/financial/page_body.php : ສ່ວນສະແດງຜົນ
 */
// Standalone Page: ລາຍງານການເງິນ (Financial Summary Report)
session_start();

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';

require_once dirname(__DIR__, 2) . '/config/db.php';

if (!hasPermission('financial') && !hasPermission('report')) {
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$from_date    = trim($_GET['from_date'] ?? date('Y-m-01'));
$to_date      = trim($_GET['to_date'] ?? date('Y-m-d'));
$search       = trim($_GET['search'] ?? '');
$filter_store_id = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : intval($_SESSION['store_id'] ?? 1);
if (!canViewAllBranches($pdo)) {
    $filter_store_id = intval($_SESSION['store_id'] ?? 1);
}
$page         = max(1, intval($_GET['page'] ?? 1));
$per_page_raw = trim($_GET['per_page'] ?? '10');

// Fetch all active branches for selection
$branchesList = [];
try {
    $branchesList = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Check store_id / paid_currency columns
$hasStoreIdCol  = false;
$hasCurrencyCol = false;
try {
    $saleCols = $pdo->query("SHOW COLUMNS FROM st_tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
    $hasStoreIdCol  = in_array('store_id', $saleCols) || in_array('branch_id', $saleCols);
    $storeColName   = in_array('store_id', $saleCols) ? 'store_id' : 'branch_id';
    $hasCurrencyCol = in_array('paid_currency', $saleCols);
} catch (Exception $e) {}

if ($per_page_raw === 'all') {
    $per_page = 999999;
} else {
    $per_page = max(1, intval($per_page_raw));
}

// SQL Filter Conditions — cancelled bills must not be counted in the money totals
// (they have their own report: ປະຫວັດການລົບບິນຂາຍ / delete_bills)
$where   = ["(s.sale_status IS NULL OR s.sale_status != 'CANCEL')"];
$params  = [];

if (!empty($from_date)) {
    $where[] = "s.sale_date >= :from_date";
    $params[':from_date'] = $from_date;
}
if (!empty($to_date)) {
    $where[] = "s.sale_date <= :to_date";
    $params[':to_date'] = $to_date;
}
if (!empty($search)) {
    $where[] = "(s.sale_save_bill LIKE :search OR s.user_receive LIKE :search OR s.type_pay LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}
if ($filter_store_id > 0 && $hasStoreIdCol) {
    $where[] = "s.{$storeColName} = :filter_store_id";
    $params[':filter_store_id'] = $filter_store_id;
}

// Currency Filter — which currency the customer actually tendered on the bill. Once
// applied, every money figure on the page switches to show in that currency instead
// of the store's default, via setDisplayCurrencyOverride().
$currency_filter = strtoupper(trim($_GET['currency_filter'] ?? ''));
if ($currency_filter !== '' && $hasCurrencyCol && isset(getCurrencyMeta()[$currency_filter])) {
    $where[] = "COALESCE(NULLIF(s.paid_currency, ''), 'KIP') = :currency_filter";
    $params[':currency_filter'] = $currency_filter;

    $__curRates = getExchangeRates($pdo);
    $__curRate  = ($currency_filter === 'KIP') ? 1.0 : (float)($__curRates[$currency_filter] ?? 0);
    if ($currency_filter === 'KIP' || $__curRate > 0) {
        setDisplayCurrencyOverride(array_merge(
            ['code' => $currency_filter, 'rate' => $__curRate, 'rates' => $__curRates],
            getCurrencyMeta()[$currency_filter]
        ));
    }
} else {
    $currency_filter = '';
}

$whereClause = implode(" AND ", $where);

// Check if cash_received / qr_received columns exist dynamically
$hasCashCol = !empty($pdo->query("SHOW COLUMNS FROM st_tbsale_save LIKE 'cash_received'")->fetch());
$hasQrCol   = !empty($pdo->query("SHOW COLUMNS FROM st_tbsale_save LIKE 'qr_received'")->fetch());

// Count Total Financial Records
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM st_tbsale_save s WHERE {$whereClause}");
$stmtCount->execute($params);
$total_records = (int)$stmtCount->fetchColumn();

$total_pages = max(1, ceil($total_records / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

// Fetch Paginated Financial Records
$sql = "
    SELECT s.* 
    FROM st_tbsale_save s
    WHERE {$whereClause}
    ORDER BY s.sale_date DESC, s.sale_time DESC, s.Id DESC
    LIMIT {$per_page} OFFSET {$offset}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$finances = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/../../layouts/header.php';
?>

<link rel="stylesheet" href="../../themes/reports.css?v=<?php echo filemtime(__DIR__ . '/../../themes/reports.css'); ?>">

<?php require __DIR__ . '/partials/financial/page_body.php'; /* ສ່ວນສະແດງຜົນ */ ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('financialSearchInput');
    const table = document.querySelector('.table');
    
    function calculateTotals() {
        let sumCash = 0, sumQr = 0, sumChange = 0, sumRec = 0;
        const rows = table.querySelectorAll('tbody tr:not(#noSearchResultRow)');
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cells = row.querySelectorAll('td');
                if (cells.length >= 10) {
                    sumCash += parseFloat(cells[4].textContent.replace(/[^0-9.-]+/g, '')) || 0;
                    sumQr += parseFloat(cells[5].textContent.replace(/[^0-9.-]+/g, '')) || 0;
                    sumChange += parseFloat(cells[6].textContent.replace(/[^0-9.-]+/g, '')) || 0;
                    sumRec += parseFloat(cells[7].textContent.replace(/[^0-9.-]+/g, '')) || 0;
                }
            }
        });
        const elCash = document.getElementById('tot_cash');
        const elQr = document.getElementById('tot_qr');
        const elChange = document.getElementById('tot_change');
        const elRec = document.getElementById('tot_rec');

        // repMoney() returns markup (<span class="cur-sym">…</span>), so assign as HTML
        if (elCash) elCash.innerHTML = repMoney(sumCash);
        if (elQr) elQr.innerHTML = repMoney(sumQr);
        if (elChange) elChange.innerHTML = repMoney(sumChange);
        if (elRec) elRec.innerHTML = repMoney(sumRec);
    }

    if (searchInput && table) {
        calculateTotals();
        searchInput.addEventListener('input', function() {
            const val = this.value.trim().toLowerCase();
            const rows = table.querySelectorAll('tbody tr:not(#noSearchResultRow)');
            let visibleCount = 0;

            if (val === '') {
                rows.forEach(row => row.style.display = '');
                const oldNoRow = document.getElementById('noSearchResultRow');
                if (oldNoRow) oldNoRow.remove();
                calculateTotals();
                return;
            }

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(val)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            let noRow = document.getElementById('noSearchResultRow');
            if (visibleCount === 0 && rows.length > 0) {
                if (!noRow) {
                    noRow = document.createElement('tr');
                    noRow.id = 'noSearchResultRow';
                    const colCount = table.querySelectorAll('thead th').length || 9;
                    noRow.innerHTML = `<td colspan="${colCount}" class="text-center py-5 text-muted font-weight-bold" style="font-size: 0.95rem;">
                        <i class="fas fa-exclamation-circle fa-2x mb-2 text-secondary opacity-50 d-block"></i>
                        <?php echo htmlspecialchars(t('reports.no_search_match', 'ບໍ່ພົບຂໍ້ມູນທີ່ຕົງກັບຄຳຄົ້ນຫາ'), ENT_QUOTES); ?>
                    </td>`;
                    table.querySelector('tbody').appendChild(noRow);
                }
            } else {
                if (noRow) noRow.remove();
            }
            calculateTotals();
        });
    } else if (table) {
        calculateTotals();
    }
});

function viewBillDetails(billId) {
    if (typeof window.showBillDetailModal === 'function') {
        window.showBillDetailModal(billId);
    } else {
        alert('ເລກບິນ: ' + billId);
    }
}
</script>

<!-- html2pdf & Shared Report JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<?php require_once __DIR__ . '/partials/js/reports_js.php'; ?>
<?php require_once __DIR__ . '/../../layouts/footer.php'; ?>
