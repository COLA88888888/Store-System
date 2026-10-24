<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../config/db.php';

// Authorization check
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied: Please log in']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'get_transfer_details') {
    $transfer_id = intval($_GET['transfer_id'] ?? 0);
    if ($transfer_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid transfer ID']);
        exit();
    }

    try {
        // Fetch transfer header details
        $stmtTrf = $pdo->prepare("
            SELECT t.*, f.store_name as from_store_name, to_s.store_name as to_store_name, u.username as creator_name
            FROM st_stock_transfers t
            LEFT JOIN st_tbstore f ON t.from_store_id = f.store_id
            LEFT JOIN st_tbstore to_s ON t.to_store_id = to_s.store_id
            LEFT JOIN st_tbuser u ON t.created_by = u.Id
            WHERE t.transfer_id = ?
        ");
        $stmtTrf->execute([$transfer_id]);
        $transfer = $stmtTrf->fetch(PDO::FETCH_ASSOC);

        if (!$transfer) {
            echo json_encode(['success' => false, 'message' => 'Transfer record not found']);
            exit();
        }

        // Format date
        $transfer['transfer_date'] = date('d/m/Y H:i', strtotime($transfer['transfer_date']));

        // Fetch transfer details directly from stock_transfer_details (preventing duplicate product store JOINs)
        $stmtDet = $pdo->prepare("
            SELECT 
                d.product_id,
                COALESCE(NULLIF(TRIM(d.product_name), ''), p.product_name, 'ສິນຄ້າ') as product_name,
                COALESCE(NULLIF(TRIM(d.barcode), ''), p.barcode, '') as prod_barcode,
                COALESCE(NULLIF(TRIM(d.unit), ''), p.unit, 'ອັນ') as unit,
                d.qty,
                d.bprice
            FROM st_stock_transfer_details d
            LEFT JOIN st_products p ON d.product_id = p.product_id AND p.store_id = ?
            WHERE d.transfer_id = ?
            ORDER BY d.id ASC
        ");
        $stmtDet->execute([intval($transfer['from_store_id']), $transfer_id]);
        $details = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'transfer' => $transfer,
            'details' => $details
        ]);
        exit();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit();
    }
} elseif ($action === 'poll_notifications') {
    try {
        $userStoreId = intval($_SESSION['store_id'] ?? 1);
        $userIsAdmin = ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' || strtolower($_SESSION['status'] ?? '') === 'admin' || ($_SESSION['user_id'] ?? 0) == 1;
        $userIsMain = isMainBranch($pdo, $userStoreId);
        $userCanAllBranches = canViewAllBranches($pdo);

        if (!$userCanAllBranches) {
            // Sub-branch notification poll
            $incomingTransfers = getIncomingTransfersForStore($pdo, $userStoreId);
            $subLowStock = getLowStockAlerts($pdo, $userStoreId);
            $subExpiry = getExpiryAlerts($pdo, $userStoreId);
            $subNewProducts = getNewProductsForStore($pdo, $userStoreId);

            foreach ($incomingTransfers as &$t) {
                $t['formatted_date'] = date('d/m H:i', strtotime($t['transfer_date']));
            }
            unset($t);

            // ແຈ້ງເຕືອນເຊັກສະຕັອກ: ສົ່ງມາກວດ / ອະນຸມັດແລ້ວ (config/helpers/stock_check_alerts.php)
            $stockChecks = getStockCheckNotifications($pdo);
            $totalCount = count($incomingTransfers) + count($subLowStock) + count($subNewProducts) + count($subExpiry) + count($stockChecks);

            echo json_encode([
                'success' => true,
                'is_main' => false,
                'total_count' => $totalCount,
                'incoming_transfers' => $incomingTransfers,
                'sub_low_stock' => $subLowStock,
                'sub_expiry' => $subExpiry,
                'sub_new_products' => $subNewProducts,
                'stock_checks' => $stockChecks
            ]);
            exit();
        } else {
            // Main branch notification poll (Low stock & Out of stock across all stores + Sub-branch Stock Imports)
            $lowStockList = getLowStockAlerts($pdo);
            $expiryList = getExpiryAlerts($pdo);
            $subBranchImports = getSubBranchStockImportsForMain($pdo);

            foreach ($subBranchImports as &$imp) {
                $imp['formatted_date'] = date('d/m H:i', strtotime($imp['import_date']));
            }
            unset($imp);

            $stockChecks = getStockCheckNotifications($pdo);
            $totalCount = count($lowStockList) + count($subBranchImports) + count($expiryList) + count($stockChecks);

            $groupedLowStock = [];
            foreach ($lowStockList as $alert) {
                $stId = $alert['store_id'];
                if (!isset($groupedLowStock[$stId])) {
                    $groupedLowStock[$stId] = [
                        'store_id' => $alert['store_id'],
                        'store_name' => $alert['store_name'],
                        'is_main' => $alert['is_main'] ?? 0,
                        'items' => []
                    ];
                }
                $groupedLowStock[$stId]['items'][] = $alert;
            }

            echo json_encode([
                'success' => true,
                'is_main' => true,
                'total_count' => $totalCount,
                'grouped_low_stock' => array_values($groupedLowStock),
                'sub_branch_imports' => $subBranchImports,
                'expiry_list' => $expiryList,
                'stock_checks' => $stockChecks
            ]);
            exit();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
} elseif ($action === 'validate_transfer_products') {
    try {
        $from_store_id = intval($_POST['from_store_id'] ?? 0);
        $to_store_id = intval($_POST['to_store_id'] ?? 0);
        $items_raw = $_POST['items'] ?? '[]';
        $items = is_array($items_raw) ? $items_raw : json_decode($items_raw, true);

        // 1. Check if system has at least 2 active branches
        $activeStoreCount = (int)$pdo->query("SELECT COUNT(*) FROM st_tbstore WHERE status = 'active'")->fetchColumn();
        if ($activeStoreCount < 2) {
            echo json_encode([
                'success' => false,
                'message' => 'ລະບົບຕ້ອງມີຢ່າງໜ້ອຍ 2 ສາຂາຂຶ້ນໄປ ຈຶ່ງສາມາດໂອນສິນຄ້າລະຫວ່າງສາຂາໄດ້!'
            ]);
            exit();
        }

        if ($from_store_id <= 0 || $to_store_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ກະລຸນາເລືອກສາຂາຕົ້ນທາງ ແລະ ສາຂາປາຍທາງ!']);
            exit();
        }

        if ($from_store_id === $to_store_id) {
            echo json_encode(['success' => false, 'message' => 'ສາຂາຕົ້ນທາງ ແລະ ສາຂາປາຍທາງ ຕ້ອງບໍ່ແມ່ນສາຂາດຽວກັນ!']);
            exit();
        }

        if (empty($items) || !is_array($items)) {
            echo json_encode(['success' => false, 'message' => 'ກະລຸນາເພີ່ມສິນຄ້າໃສ່ລາຍການໂອນຢ່າງນ້ອຍ 1 ລາຍການ!']);
            exit();
        }

        // 2. Validate each product in destination branch
        $errors = [];
        foreach ($items as $itm) {
            $pId = intval($itm['product_id'] ?? 0);
            if ($pId <= 0) continue;

            // Fetch source product with its category
            $stmtSrc = $pdo->prepare("
                SELECT p.*, c.category_name 
                FROM st_products p 
                LEFT JOIN st_categories c ON p.category_id = c.category_id 
                WHERE p.product_id = ? AND p.store_id = ?
            ");
            $stmtSrc->execute([$pId, $from_store_id]);
            $src = $stmtSrc->fetch(PDO::FETCH_ASSOC);
            if (!$src) continue;

            $pName = $src['product_name'] ?: "ID #{$pId}";

            // Fetch destination product by product_id OR barcode
            $stmtDst = $pdo->prepare("
                SELECT p.*, c.category_name 
                FROM st_products p 
                LEFT JOIN st_categories c ON p.category_id = c.category_id 
                WHERE (p.product_id = ? OR (p.barcode = ? AND p.barcode != '' AND p.barcode != '-'))
                  AND p.store_id = ?
            ");
            $stmtDst->execute([$pId, $src['barcode'] ?? '', $to_store_id]);
            $dst = $stmtDst->fetch(PDO::FETCH_ASSOC);

            // Must exist in destination branch
            if (!$dst) {
                $errors[] = "• ສິນຄ້າ \"{$pName}\" ຍັງບໍ່ມີໃນສາຂາປາຍທາງ! ຕ້ອງໄດ້ເພີ່ມເຂົ້າທາງນັ້ນກ່ອນ ຈຶ່ງສາມາດໂອນໄດ້.";
                continue;
            }

            // Must have same category
            $srcCat = trim($src['category_name'] ?? '');
            $dstCat = trim($dst['category_name'] ?? '');
            if (mb_strtolower($srcCat) !== mb_strtolower($dstCat)) {
                $srcCatShow = $srcCat ?: 'ບໍ່ມີປະເພດ';
                $dstCatShow = $dstCat ?: 'ບໍ່ມີປະເພດ';
                $errors[] = "• ສິນຄ້າ \"{$pName}\" ມີປະເພດບໍ່ຕົງກັນ (ຕົ້ນທາງ: \"{$srcCatShow}\", ປາຍທາງ: \"{$dstCatShow}\")! ຕ້ອງເປັນປະເພດສິນຄ້າດຽວກັນ.";
            }

            // Must have same unit
            $srcUnit = trim($src['unit'] ?? '');
            $dstUnit = trim($dst['unit'] ?? '');
            if (mb_strtolower($srcUnit) !== mb_strtolower($dstUnit)) {
                $srcUnitShow = $srcUnit ?: 'ອັນ';
                $dstUnitShow = $dstUnit ?: 'ອັນ';
                $errors[] = "• ສິນຄ້າ \"{$pName}\" ມີຫົວໜ່ວຍບໍ່ຕົງກັນ (ຕົ້ນທາງ: \"{$srcUnitShow}\", ປາຍທາງ: \"{$dstUnitShow}\")! ຕ້ອງເປັນຫົວໜ່ວຍດຽວກັນ.";
            }
        }

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'message' => implode("\n", $errors),
                'errors' => $errors
            ]);
            exit();
        }

        echo json_encode(['success' => true]);
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}
