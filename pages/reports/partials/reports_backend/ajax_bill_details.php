<?php
/**
 * ສ່ວນ: AJAX: ລາຍລະອຽດບິນ
 * ສົ່ງຂໍ້ມູນບິນ 1 ໃບເປັນ JSON ສຳລັບ popup ແລະ ພິມຄືນ: ຫົວບິນ, VAT, ທະນາຄານ/QR, ວັນກຳນົດຊຳລະໜີ້,
 * ເບີໂທລູກຄ້າ, ຄະແນນ ແລະ ລາຍການສິນຄ້າ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/partials/reports_backend.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// Handle AJAX Request for Bill Items Modal Details
if (isset($_GET['action']) && $_GET['action'] === 'get_bill_details') {
    header('Content-Type: application/json');
    $billNo = trim($_GET['bill_no'] ?? '');

    if (empty($billNo)) {
        echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບເລກບິນ!']);
        exit();
    }

    try {
        $stmtB = $pdo->prepare("
            SELECT s.*, sl.vat_amount, sl.tax_type, sl.vat_rate,
                   COALESCE(NULLIF(u.fname, ''), s.user_receive, 'Admin') AS cashier_display_name,
                   COALESCE(NULLIF(s.customer_name, ''), 'ລູກຄ້າທົ່ວໄປ') AS customer_display_name
            FROM st_tbsale_save s
            LEFT JOIN st_sales sl ON s.sale_save_bill = sl.invoice_number 
            LEFT JOIN st_tbuser u ON (s.user_receive = u.username OR s.user_receive = u.fname)
            WHERE s.sale_save_bill = :bill
        ");
        $stmtB->execute([':bill' => $billNo]);
        $billData = $stmtB->fetch(PDO::FETCH_ASSOC);

        if (!$billData) {
            echo json_encode(['success' => false, 'message' => 'ບໍ່ພົບຂໍ້ມູນບິນຂາຍນີ້!']);
            exit();
        }

        // Tax settings are shared across all branches (live on the main branch's row only)
        $companyTax = getCompanyInfoForBranch($pdo, getActiveStoreId($pdo));
        if ($companyTax) {
            if (empty($billData['tax_type']) || $billData['tax_type'] === 'none') {
                if (!empty($companyTax['tax_type']) && $companyTax['tax_type'] !== 'none') {
                    $billData['tax_type'] = $companyTax['tax_type'];
                }
            }
            if (empty($billData['vat_rate']) || floatval($billData['vat_rate']) == 0) {
                if (!empty($companyTax['vat_percent'])) {
                    $billData['vat_rate'] = floatval($companyTax['vat_percent']);
                }
            }
            $billData['tax_id'] = $companyTax['tax_id'] ?? '';
        }

        // Resolve the exact bank/QR that was used at checkout time — a reprint must show
        // the same QR code and account the customer actually paid to, not the store's default.
        $billData['bank_qr_img'] = '';
        $billData['bank_account_no'] = '';
        $billData['bank_account_name'] = '';
        if (!empty($billData['bank_account_id'])) {
            try {
                $bStmt = $pdo->prepare("SELECT * FROM st_bank_accounts WHERE id = ?");
                $bStmt->execute([$billData['bank_account_id']]);
                $bInfo = $bStmt->fetch(PDO::FETCH_ASSOC);
                if ($bInfo) {
                    $billData['bank_qr_img'] = resolveBankQr($bInfo['qr_code_img'] ?? '', $bInfo['bank_code'] ?? '');
                    $billData['bank_account_no'] = $bInfo['account_number'] ?? '';
                    $billData['bank_account_name'] = $bInfo['account_name'] ?? '';
                    if (empty($billData['bank_name'])) {
                        $billData['bank_name'] = $bInfo['bank_name'] ?? '';
                    }
                }
            } catch (Throwable $e) {}
        }

        // ຂາຍຕິດໜີ້: the promised payment date, for the reprinted receipt
        $billData['credit_due_date'] = '';
        try {
            $cdStmt = $pdo->prepare("SELECT due_date FROM st_customer_debts WHERE invoice_number = ? AND status <> 'cancelled' AND due_date IS NOT NULL LIMIT 1");
            $cdStmt->execute([$billNo]);
            $cdDue = $cdStmt->fetchColumn();
            if ($cdDue) $billData['credit_due_date'] = date('d/m/Y', strtotime($cdDue));
        } catch (Throwable $e) {}

        $billData['customer_phone']   = '';
        if (!empty($billData['customer_id'])) {
            try {
                $cStmt = $pdo->prepare("SELECT phone FROM st_customers WHERE customer_id = ? LIMIT 1");
                $cStmt->execute([$billData['customer_id']]);
                $cRow = $cStmt->fetch(PDO::FETCH_ASSOC);
                if ($cRow) {
                    $billData['customer_phone']   = $cRow['phone'] ?? '';
                }
            } catch (Throwable $e) {}
        }

        // Customer's points balance right after this sale (as printed on the checkout receipt when
        // points were redeemed) — from the points log, not today's balance.
        $billData['points_balance_after'] = null;
        try {
            $plStmt = $pdo->prepare("SELECT balance_after FROM st_loyalty_points_log WHERE sale_bill = ? AND log_type IN ('redeem','earn') ORDER BY id DESC LIMIT 1");
            $plStmt->execute([$billNo]);
            $plBal = $plStmt->fetchColumn();
            if ($plBal !== false) $billData['points_balance_after'] = (int)$plBal;
        } catch (Throwable $e) {}

        $amountAfterDisc = max(0, floatval($billData['sale_amount'] ?? 0) - floatval($billData['sale_discount_bill'] ?? 0));
        if ((empty($billData['vat_amount']) || floatval($billData['vat_amount']) == 0) && !empty($billData['vat_rate']) && floatval($billData['vat_rate']) > 0) {
            $taxT = $billData['tax_type'] ?? 'inclusive';
            $vatR = floatval($billData['vat_rate']);
            if ($taxT === 'exclusive' && $vatR < 100) {
                $billData['vat_amount'] = round($amountAfterDisc * ($vatR / (100 - $vatR)), 2);
            } elseif ($taxT === 'inclusive') {
                $billData['vat_amount'] = round($amountAfterDisc - ($amountAfterDisc / (1 + ($vatR / 100))), 2);
            }
        }

        $stmtD = $pdo->prepare("
            SELECT d.*, p.product_name, cat.category_name 
            FROM st_tbsale_save_detail d
            LEFT JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
            LEFT JOIN st_products p ON (d.save_proid = p.product_id AND (p.store_id = s.store_id OR s.store_id IS NULL OR s.store_id <= 1))
            LEFT JOIN st_categories cat ON p.category_id = cat.category_id
            WHERE d.save_bill = :bill
            GROUP BY d.Id
            ORDER BY d.Id ASC
        ");
        $stmtD->execute([':bill' => $billNo]);
        $details = $stmtD->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'bill' => $billData,
            'details' => $details
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

