<?php
/**
 * ສ່ວນ: ຂໍ້ມູນ: ຕົວກັ່ນຕອງ ແລະ ລາຍການນຳເຂົ້າ
 * ຮັບວັນທີ/ສາຂາ/ໂໝດສະແດງ ແລ້ວດຶງລາຍການນຳເຂົ້າ (ລວມການໂອນຈາກສາຂາອື່ນ) ພ້ອມຍອດຕົ້ນທຶນລວມ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/import_stock/import_list.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// ====== GET FILTER PARAMETERS ======
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date   = $_GET['to_date']   ?? date('Y-m-d');
$view_type = $_GET['view_type'] ?? 'bill';

// ====== FETCH IMPORT RECORDS BASED ON VIEW TYPE & DATE RANGE ======
$activeStoreId = getActiveStoreId($pdo);
$userStoreId = intval($_SESSION['store_id'] ?? 1);
$isAdmin = ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' || strtolower($_SESSION['status'] ?? '') === 'admin' || ($_SESSION['user_id'] ?? 0) == 1;
$isMain = isMainBranch($pdo, $userStoreId);
$canAllBranches = canViewAllBranches($pdo);

// Fetch stores list for branch filter dropdown
$stores = $pdo->query("SELECT * FROM st_tbstore WHERE status = 'active' ORDER BY is_main DESC, store_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// GET filter_store parameter
$filter_store = isset($_GET['store_id']) && $_GET['store_id'] !== '' ? intval($_GET['store_id']) : intval($_SESSION['store_id'] ?? 1);
if (!$canAllBranches) {
    $filter_store = $userStoreId;
}

// Fetch categories for modal filter
$targetProdStore = ($filter_store > 0) ? $filter_store : $userStoreId;
$catStmt = $pdo->prepare("SELECT category_id, category_name FROM st_categories WHERE store_id = ? ORDER BY category_name ASC");
$catStmt->execute([$targetProdStore]);
$categories = $catStmt->fetchAll();

// Fetch products for modal filter (filtered by active/selected store)
$pStmt = $pdo->prepare("
    SELECT p.product_id, p.product_name, p.barcode, p.unit, p.bprice, p.price, p.qty, p.category_id, c.category_name 
    FROM st_products p 
    LEFT JOIN st_categories c ON p.category_id = c.category_id 
    WHERE p.store_id = ?
    ORDER BY p.product_name ASC
");
$pStmt->execute([$targetProdStore]);
$products = $pStmt->fetchAll();

$product_units_map = [];
$uRows = $pdo->query("SELECT * FROM st_product_units ORDER BY multiplier ASC")->fetchAll();
foreach ($uRows as $u) {
    $product_units_map[$u['product_id']][] = $u;
}

// Build store filter WHERE condition for bill queries
$whereClause = "WHERE DATE(i.import_date) BETWEEN :from_date AND :to_date";
$params = [
    ':from_date' => $from_date,
    ':to_date'   => $to_date
];

if ($filter_store > 0) {
    $whereClause .= " AND (CASE WHEN i.store_id > 0 THEN i.store_id ELSE p.store_id END) = :filter_store";
    $params[':filter_store'] = $filter_store;
}

// Pre-fetch items per bill for the View Bill Items Modal (strictly branch scoped)
$allBillWhere = "";
$allBillParams = [];
if ($filter_store > 0) {
    $allBillWhere = "WHERE (CASE WHEN i.store_id > 0 THEN i.store_id ELSE p.store_id END) = :filter_store";
    $allBillParams[':filter_store'] = $filter_store;
}

$allBillStmt = $pdo->prepare("
    SELECT id.*, p.product_name, p.barcode, p.unit AS base_unit,
    (
        CASE 
            WHEN (pb.quantity IS NOT NULL AND pb.initial_qty IS NOT NULL AND pb.quantity < pb.initial_qty)
                 OR (p.qty < id.total_base_qty) THEN 1 
            ELSE 0 
        END
    ) AS has_movement
    FROM st_import_details id
    JOIN st_imports i ON id.import_id = i.import_id
    LEFT JOIN st_products p ON (id.product_id = p.product_id AND p.store_id = (CASE WHEN i.store_id > 0 THEN i.store_id ELSE 1 END))
    LEFT JOIN st_product_batches pb ON id.import_detail_id = pb.import_detail_id
    {$allBillWhere}
    GROUP BY id.import_detail_id, p.product_name, p.barcode, p.unit
    ORDER BY id.import_detail_id ASC
");
$allBillStmt->execute($allBillParams);
$allBillItems = $allBillStmt->fetchAll();

$billItemsMap = [];
foreach ($allBillItems as $bi) {
    $bi['invoice_number'] = '';
    $billItemsMap[$bi['import_id']][] = $bi;
}

$importList = [];
if ($view_type === 'bill') {
    $billQuery = "
        SELECT 
            i.import_id,
            i.invoice_number,
            i.supplier_name,
            i.import_date,
            i.total_cost,
            i.notes,
            u.username,
            u.fname,
            u.lname,
            s.store_name,
            s.store_id,
            COUNT(DISTINCT id.import_detail_id) AS item_count,
            SUM(id.total_base_qty) AS sum_base_qty,
            MAX(
                CASE 
                    WHEN (pb.quantity IS NOT NULL AND pb.initial_qty IS NOT NULL AND pb.quantity < pb.initial_qty)
                         OR (p.qty < id.total_base_qty) THEN 1 
                    ELSE 0 
                END
            ) AS has_movement
        FROM st_imports i
        LEFT JOIN st_import_details id ON i.import_id = id.import_id
        LEFT JOIN st_products p ON (id.product_id = p.product_id AND p.store_id = (CASE WHEN i.store_id > 0 THEN i.store_id ELSE 1 END))
        LEFT JOIN st_tbstore s ON (CASE WHEN i.store_id > 0 THEN i.store_id ELSE p.store_id END) = s.store_id
        LEFT JOIN st_product_batches pb ON id.import_detail_id = pb.import_detail_id
        LEFT JOIN st_tbuser u ON i.created_by = u.Id
        {$whereClause}
        GROUP BY i.import_id
        ORDER BY i.import_date DESC, i.import_id DESC
    ";
    $stmt = $pdo->prepare($billQuery);
    $stmt->execute($params);
    $importList = $stmt->fetchAll();
} else {
    $detailQuery = "
        SELECT 
            id.import_detail_id,
            id.import_id,
            id.product_id,
            id.quantity,
            id.unit_name,
            id.multiplier,
            id.total_base_qty,
            id.cost_price,
            id.total_cost,
            id.expiry_date,
            i.invoice_number,
            i.supplier_name,
            i.import_date,
            p.product_name,
            p.qty AS current_product_qty,
            p.unit AS base_unit,
            s.store_name,
            s.store_id,
            u.username,
            u.fname,
            u.lname,
            pb.initial_qty AS batch_initial_qty,
            pb.quantity AS batch_rem_qty
        FROM st_import_details id
        JOIN st_imports i ON id.import_id = i.import_id
        LEFT JOIN st_products p ON (id.product_id = p.product_id AND p.store_id = (CASE WHEN i.store_id > 0 THEN i.store_id ELSE 1 END))
        LEFT JOIN st_tbstore s ON (CASE WHEN i.store_id > 0 THEN i.store_id ELSE p.store_id END) = s.store_id
        LEFT JOIN st_tbuser u ON i.created_by = u.Id
        LEFT JOIN st_product_batches pb ON id.import_detail_id = pb.import_detail_id
        {$whereClause}
        GROUP BY id.import_detail_id
        ORDER BY i.import_date DESC, id.import_detail_id DESC
    ";
    $stmt = $pdo->prepare($detailQuery);
    $stmt->execute($params);
    $importList = $stmt->fetchAll();

    // Stock transferred OUT of a branch has no import_details line (only a negative "imports"
    // ledger row), so it never showed here and the total kept the full imported value. List each
    // transferred product as a red minus line: import 100, transfer 50 → the total drops by 50's cost.
    try {
        $trfWhere = "WHERE st.status = 'completed' AND DATE(st.transfer_date) BETWEEN :from_date AND :to_date";
        $trfParams = [':from_date' => $from_date, ':to_date' => $to_date];
        if ($filter_store > 0) {
            $trfWhere .= " AND st.from_store_id = :filter_store";
            $trfParams[':filter_store'] = $filter_store;
        }
        $trfStmt = $pdo->prepare("
            SELECT d.product_id, d.product_name, d.qty, d.unit, d.bprice,
                   st.transfer_code, st.transfer_date, st.from_store_id,
                   fs.store_name, ts.store_name AS to_store_name,
                   u.username, u.fname, u.lname
            FROM st_stock_transfer_details d
            JOIN st_stock_transfers st ON st.transfer_id = d.transfer_id
            LEFT JOIN st_tbstore fs ON fs.store_id = st.from_store_id
            LEFT JOIN st_tbstore ts ON ts.store_id = st.to_store_id
            LEFT JOIN st_tbuser u ON u.Id = st.created_by
            {$trfWhere}
        ");
        $trfStmt->execute($trfParams);
        foreach ($trfStmt->fetchAll() as $t) {
            $q = (int)$t['qty'];
            $importList[] = [
                'is_transfer_out' => true,
                'import_detail_id' => 0,
                'product_id'      => $t['product_id'],
                'product_name'    => $t['product_name'],
                'quantity'        => -$q,
                'unit_name'       => $t['unit'],
                'total_base_qty'  => -$q,
                'base_unit'       => $t['unit'],
                'cost_price'      => (float)$t['bprice'],
                'total_cost'      => -$q * (float)$t['bprice'],
                'expiry_date'     => null,
                'invoice_number'  => $t['transfer_code'],
                'supplier_name'   => t('import_list.transfer_out_to', 'ໂອນອອກໄປສາຂາ') . ' ' . ($t['to_store_name'] ?? ''),
                'import_date'     => $t['transfer_date'],
                'store_name'      => $t['store_name'],
                'store_id'        => $t['from_store_id'],
                'username'        => $t['username'],
                'fname'           => $t['fname'],
                'lname'           => $t['lname'],
                'current_product_qty' => 0,
            ];
        }
        usort($importList, function ($a, $b) { return strcmp((string)$b['import_date'], (string)$a['import_date']); });
    } catch (Throwable $e) {}
}

$total_records = count($importList);
$grandTotalCost = 0;
foreach ($importList as $gRow) { $grandTotalCost += (float)($gRow['total_cost'] ?? 0); }
