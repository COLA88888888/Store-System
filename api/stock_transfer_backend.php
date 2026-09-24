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
            FROM stock_transfers t
            LEFT JOIN tbstore f ON t.from_store_id = f.store_id
            LEFT JOIN tbstore to_s ON t.to_store_id = to_s.store_id
            LEFT JOIN tbuser u ON t.created_by = u.Id
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
            FROM stock_transfer_details d
            LEFT JOIN products p ON d.product_id = p.product_id AND p.store_id = ?
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

            $totalCount = count($incomingTransfers) + count($subLowStock) + count($subNewProducts) + count($subExpiry);

            echo json_encode([
                'success' => true,
                'is_main' => false,
                'total_count' => $totalCount,
                'incoming_transfers' => $incomingTransfers,
                'sub_low_stock' => $subLowStock,
                'sub_expiry' => $subExpiry,
                'sub_new_products' => $subNewProducts
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

            $totalCount = count($lowStockList) + count($subBranchImports) + count($expiryList);

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
                'expiry_list' => $expiryList
            ]);
            exit();
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}
