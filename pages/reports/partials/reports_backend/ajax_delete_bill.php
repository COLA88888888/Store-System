<?php
/**
 * ສ່ວນ: AJAX: ລົບ (ຍົກເລີກ) ບິນ
 * ຍົກເລີກບິນ: ຄືນສະຕັອກ (ຄູນຫົວໜ່ວຍ), ຄືນ/ຫັກຄະແນນສະສົມ, ຍົກເລີກໜີ້ຂອງບິນຕິດໜີ້
 * ແລ້ວປ່ຽນສະຖານະເປັນ CANCEL.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/partials/reports_backend.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// Handle AJAX Request for Delete Bill & Restore Product Inventory Stock
$reqAction = $_REQUEST['action'] ?? '';
if ($reqAction === 'delete_bill') {
    header('Content-Type: application/json');
    $billNo = trim($_REQUEST['bill_no'] ?? '');

    if (empty($billNo)) {
        echo json_encode(['success' => false, 'message' => t('reports.del_err_no_bill')]);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 1. Fetch bill header
        $stmtB = $pdo->prepare("SELECT * FROM st_tbsale_save WHERE sale_save_bill = :bill");
        $stmtB->execute([':bill' => $billNo]);
        $bill = $stmtB->fetch(PDO::FETCH_ASSOC);

        if (!$bill) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => t('reports.del_err_not_found')]);
            exit();
        }

        if (($bill['sale_status'] ?? '') === 'CANCEL') {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => t('reports.del_err_already')]);
            exit();
        }

        // 2. Fetch bill items
        $stmtD = $pdo->prepare("SELECT save_proid, save_qty, multiplier FROM st_tbsale_save_detail WHERE save_bill = :bill");
        $stmtD->execute([':bill' => $billNo]);
        $details = $stmtD->fetchAll(PDO::FETCH_ASSOC);

        // 3. Restore Product Inventory Stock — must restore save_qty * multiplier (the actual
        // quantity that was cut at checkout), not just save_qty, or a sale made in a multi-unit
        // (e.g. 1 "ຕູດ" = 12 base units) only gets 1 unit restored instead of 12.
        $billStoreId = intval($bill['store_id'] ?? 1);
        $stmtRestore = $pdo->prepare("UPDATE st_products SET qty = qty + :restore_qty WHERE product_id = :proid AND store_id = :store_id");
        foreach ($details as $item) {
            $proid = $item['save_proid'];
            $qty   = floatval($item['save_qty']) * (intval($item['multiplier'] ?? 1) ?: 1);
            if (!empty($proid) && $qty > 0) {
                $stmtRestore->execute([
                    ':restore_qty' => $qty,
                    ':proid'       => $proid,
                    ':store_id'    => $billStoreId
                ]);
            }
        }

        // 4. Undo the bill's loyalty points: give back what was redeemed on it and take back
        // what it earned, each logged separately so the customer's history stays readable.
        $billCustomerId = intval($bill['customer_id'] ?? 0);
        if ($billCustomerId > 0) {
            $billRedeemed = intval($bill['points_redeemed'] ?? 0);
            $billEarned   = intval($bill['points_earned'] ?? 0);
            if ($billRedeemed > 0) {
                changeCustomerPoints($pdo, $billCustomerId, $billRedeemed, 'redeem_refund', $billNo, null, $billStoreId);
            }
            if ($billEarned > 0) {
                changeCustomerPoints($pdo, $billCustomerId, -$billEarned, 'earn_reversal', $billNo, null, $billStoreId);
            }
        }

        // 5. Update sale_status = 'CANCEL'
        $stmtCancel = $pdo->prepare("UPDATE st_tbsale_save SET sale_status = 'CANCEL' WHERE sale_save_bill = :bill");
        $stmtCancel->execute([':bill' => $billNo]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            // ຕາມພາສາທີ່ເລືອກ — ກົດທຸງແລ້ວ set_lang.php ບັນທຶກພາສາໃສ່ session ທັນທີ ຈຶ່ງບໍ່ຕ້ອງໂຫຼດໜ້າ
            'message' => t('reports.delete_success_prefix') . ' ' . $billNo . ' ' . t('reports.delete_success_suffix')
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => t('reports.del_err_generic') . ' ' . $e->getMessage()]);
    }
    exit();
}

