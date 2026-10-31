<?php
/**
 * ສ່ວນ: ດຶງແຖວການຂາຍ
 * ໂໝດລາຍການສິນຄ້າ (ແບ່ງເງິນສົດ/ໂອນຕາມສັດສ່ວນ) ຫຼື ໂໝດບິນ (ເງິນສົດ/ໂອນ/ທອນ/ທະນາຄານ)
 * ພ້ອມຍອດລວມທັງໝົດ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/reports/partials/reports_backend.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

if ($view_mode === 'item') {
    // VIEW MODE 2: ສະແດງຕາມລາຍການສິນຄ້າ (ITEM DETAIL VIEW)
    $sql = "
        SELECT d.*, s.sale_date, s.sale_time, s.user_receive, s.customer_name, s.type_pay, s.sale_status,
               s.sale_amount AS bill_gross, s.sale_discount_bill AS bill_discount, s.sale_barlance AS bill_net,
               s.sale_pay, s.sale_return,
               " . ($hasCashCol ? "s.cash_received," : "") . "
               " . ($hasQrCol ? "s.qr_received," : "") . "
               " . ($hasCurrencyCol ? "s.paid_currency, s.paid_rate, s.paid_amount_foreign," : "") . "
               p.product_name, p.bprice, cat.category_name, COALESCE(st.store_name, 'ສາຂາ') AS store_name
        FROM st_tbsale_save_detail d
        INNER JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
        LEFT JOIN st_products p ON (d.save_proid = p.product_id AND (p.store_id = s.store_id OR s.store_id IS NULL OR s.store_id <= 1))
        LEFT JOIN st_categories cat ON p.category_id = cat.category_id
        LEFT JOIN st_tbstore st ON s.store_id = st.store_id
        WHERE {$whereClause}
        GROUP BY d.Id
        ORDER BY s.sale_date DESC, s.sale_time DESC, d.Id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $qty   = floatval($r['save_qty']);
        $price = floatval($r['save_price']);
        $gross = floatval($r['save_money'] ?? ($qty * $price));

        $billGross = floatval($r['bill_gross'] ?? 0);
        $billDisc  = floatval($r['bill_discount'] ?? 0);
        $itemDisc  = ($billGross > 0) ? round(($gross / $billGross) * $billDisc, 2) : 0;
        $net       = max(0, $gross - $itemDisc);

        $typePay  = $r['type_pay'] ?? '';
        $billNet  = floatval($r['bill_net'] ?? 0);
        $billPay  = floatval($r['sale_pay'] ?? 0);
        $billRet  = floatval($r['sale_return'] ?? 0);
        $billPaid = max(0, $billPay - $billRet);

        if (isset($r['cash_received']) && isset($r['qr_received']) && ($r['cash_received'] > 0 || $r['qr_received'] > 0)) {
            $billCash = floatval($r['cash_received']);
            $billQr   = floatval($r['qr_received']);
        } elseif ($typePay === 'ຕິດໜີ້') {
            // Credit sale with no deposit: nothing was received at the till.
            $billCash = 0;
            $billQr   = 0;
        } else {
            if (mb_strpos($typePay, 'ໂອນ') !== false || mb_strpos($typePay, 'QR') !== false) {
                if (mb_strpos($typePay, 'ເງິນສົດ') !== false) {
                    $billCash = max(0, $billPaid);
                    $billQr   = max(0, $billNet - $billCash);
                } else {
                    $billCash = 0;
                    $billQr   = $billNet;
                }
            } else {
                $billCash = $billNet;
                $billQr   = 0;
            }
        }
        // Credit bill: add what has been repaid since, by channel
        if ($typePay === 'ຕິດໜີ້' && isset($debtRepaidByBill[$r['save_bill']])) {
            $billCash += (float)$debtRepaidByBill[$r['save_bill']]['cash'];
            $billQr   += (float)$debtRepaidByBill[$r['save_bill']]['qr'];
        }

        $billTip = max(0, $billPaid - $billNet);
        $ratio   = ($billNet > 0) ? ($net / $billNet) : 0;
        $itemCash = round($billCash * $ratio, 2);
        $itemQr   = round($billQr * $ratio, 2);
        $itemTip  = round($billTip * $ratio, 2);

        // Real cost only — d.cost_price (recorded at checkout time) falling back to the
        // product's current bprice for older sales. NEVER a guessed margin: a non-stock/
        // service item (cut_qty = 0) typically has no purchase cost at all, and fabricating
        // one (e.g. 70% of the sale price) would misreport its profit. When no real cost is
        // on file, $profit is left null so the KPI card can hide instead of showing a guess.
        $realCostPrice = floatval($r['cost_price'] ?? 0);
        $fallbackBprice = floatval($r['bprice'] ?? 0);
        $hasCost  = $realCostPrice > 0 || $fallbackBprice > 0;
        $buyPrice = $realCostPrice > 0 ? $realCostPrice : $fallbackBprice;
        $itemCost = $qty * $buyPrice;
        $profit   = $hasCost ? max(0, $net - $itemCost) : null;

        if ($hasCost) {
            $has_cost_data = true;
        }

        $sales_data[] = [
            'bill_no'   => $r['save_bill'],
            'date_time' => date('d/m/Y', strtotime($r['sale_date'])) . ' ' . substr($r['sale_time'], 0, 5),
            'pro_code'  => $r['save_proid'] ?: ($r['product_id'] ?? '-'),
            'pro_name'  => $r['save_proname'] ?: ($r['product_name'] ?? tl('ສິນຄ້າ')),
            'store_name'=> $r['store_name'] ?? tl('ສາຂາ'),
            'qty'       => $qty,
            'price'     => $price,
            'gross'     => $gross,
            'discount'  => $itemDisc,
            'net'       => $net,
            'profit'    => $profit,
            'cash'      => $itemCash,
            'qr'        => $itemQr,
            'tip'       => $itemTip,
            'status'    => $r['sale_status'] ?? 'SUCCESS',
            'cashier'   => $r['user_receive'] ?? 'Admin',
            'currency'  => strtoupper($r['paid_currency'] ?? 'KIP') ?: 'KIP'
        ];

        $total_qty    += $qty;
        $total_gross  += $gross;
        $total_disc   += $itemDisc;
        $total_net    += $net;
        if ($hasCost) {
            $total_profit += $profit;
        }
        $total_cash  += $itemCash;
        $total_qr    += $itemQr;
        $total_tip   += $itemTip;
    }

    $total_bills_count = count($sales_data);



} else {
    // VIEW MODE 1: ສະແດງຕາມບິນ (INVOICE SUMMARY VIEW)
    $sql = "
        SELECT s.*, COALESCE(st.store_name, 'ສາຂາ') AS store_name
        FROM st_tbsale_save s
        LEFT JOIN st_tbstore st ON s.store_id = st.store_id
        WHERE {$whereClause}
        ORDER BY s.sale_date DESC, s.sale_time DESC, s.Id DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $r) {
        $qty   = floatval($r['sale_qty'] ?? 0);
        $gross = floatval($r['sale_amount'] ?? 0);
        $disc  = floatval($r['sale_discount_bill'] ?? 0);
        $net   = floatval($r['sale_barlance'] ?? max(0, $gross - $disc));

        $typePay = $r['type_pay'] ?? '';
        $pay     = floatval($r['sale_pay'] ?? 0);
        $ret     = floatval($r['sale_return'] ?? 0);
        $paid    = max(0, $pay - $ret);

        if (isset($r['cash_received']) && isset($r['qr_received']) && ($r['cash_received'] > 0 || $r['qr_received'] > 0)) {
            $cash = floatval($r['cash_received']);
            $qr   = floatval($r['qr_received']);
        } elseif ($typePay === 'ຕິດໜີ້') {
            // Credit sale with no deposit: nothing was received at the till.
            $cash = 0;
            $qr   = 0;
        } else {
            if (mb_strpos($typePay, 'ໂອນ') !== false || mb_strpos($typePay, 'QR') !== false) {
                if (mb_strpos($typePay, 'ເງິນສົດ') !== false) {
                    $cash = max(0, $paid);
                    $qr   = max(0, $net - $cash);
                } else {
                    $cash = 0;
                    $qr   = $net;
                }
            } else {
                $cash = $net;
                $qr   = 0;
            }
        }

        if (isset($r['tip_amount']) && $r['tip_amount'] > 0) {
            $tip = floatval($r['tip_amount']);
        } else if (isset($r['tip']) && $r['tip'] > 0) {
            $tip = floatval($r['tip']);
        } else {
            $tip = max(0, $paid - $net);
        }

        $change = floatval($r['sale_return'] ?? 0);

        $bAccId = intval($r['bank_account_id'] ?? 0);
        // Credit bill: add what has been repaid since, by channel; a transfer repayment also
        // supplies the bank (logo) when the bill itself had none.
        if ($typePay === 'ຕິດໜີ້' && isset($debtRepaidByBill[$r['sale_save_bill']])) {
            $rp = $debtRepaidByBill[$r['sale_save_bill']];
            $cash += (float)$rp['cash'];
            $qr   += (float)$rp['qr'];
            if (!$bAccId && (float)$rp['qr'] > 0) {
                $bAccId = intval($rp['bank_account_id'] ?? 0);
                if (empty($r['bank_name']) && !empty($rp['bank_name'])) $r['bank_name'] = $rp['bank_name'];
            }
        }
        // Unscoped lookup — this bill's own bank_account_id is a concrete reference that must
        // resolve regardless of which branch's report is currently being viewed (see the note
        // where $all_bank_accounts_map is built above).
        $bInfo = $all_bank_accounts_map[$bAccId] ?? [];
        $bName = !empty($r['bank_name']) ? $r['bank_name'] : ($bInfo['bank_name'] ?? '');
        $bCode = !empty($bInfo['bank_code']) ? $bInfo['bank_code'] : $bName;
        $bLogo = !empty($bInfo['bank_logo']) ? $bInfo['bank_logo'] : '';

        if (empty($bName) && $qr > 0) {
            $tPay = strtoupper($r['type_pay'] ?? '');
            if (strpos($tPay, 'BCEL') !== false || strpos($tPay, 'BCL') !== false) {
                $bName = 'BCEL One';
                $bCode = 'BCEL';
            } else if (strpos($tPay, 'LDB') !== false) {
                $bName = 'LDB';
                $bCode = 'LDB';
            } else if (strpos($tPay, 'JDB') !== false) {
                $bName = 'JDB';
                $bCode = 'JDB';
            } else if (!empty($bank_accounts_map)) {
                $firstBank = reset($bank_accounts_map);
                $bName = $firstBank['bank_name'];
                $bCode = !empty($firstBank['bank_code']) ? $firstBank['bank_code'] : $bName;
                $bLogo = !empty($firstBank['bank_logo']) ? $firstBank['bank_logo'] : '';
            } else {
                $bName = 'BCEL One';
                $bCode = 'BCEL';
            }
        }

        $sales_data[] = [
            'bill_no'         => $r['sale_save_bill'],
            'date_time'       => date('d/m/Y', strtotime($r['sale_date'])) . ' ' . substr($r['sale_time'], 0, 5),
            'store_name'      => $r['store_name'] ?? tl('ສາຂາ'),
            'qty'             => $qty,
            'gross'           => $gross,
            'discount'        => $disc,
            'net'             => $net,
            'cash'            => $cash,
            'qr'              => $qr,
            'change'          => $change,
            'tip'             => $tip,
            'status'          => $r['sale_status'] ?? 'SUCCESS',
            'type_pay'        => $typePay,
            'bank_account_id' => $bAccId,
            'bank_name'       => $bName,
            'bank_code'       => $bCode,
            'bank_logo'       => $bLogo,
            'cashier'         => $r['user_receive'] ?? 'Admin',
            'customer'        => (function($cn) {
                $aliases = ['ລູກຄ້າທົ່ວໄປ', 'Walk-in Customer', 'ลูกค้าทั่วไป', '散客', '普通顾客', 'Khách lẻ', 'Khách thông thường', 'General Customer'];
                $clean = trim((string)$cn);
                return (empty($clean) || in_array($clean, $aliases, true)) ? t('reports.default_customer', 'ລູກຄ້າທົ່ວໄປ') : $clean;
            })($r['customer_name'] ?? ''),
            'currency'        => strtoupper($r['paid_currency'] ?? 'KIP') ?: 'KIP'
        ];

        $total_qty    += $qty;
        $total_gross  += $gross;
        $total_disc   += $disc;
        $total_net    += $net;
        $total_cash   += $cash;
        $total_qr     += $qr;
        $total_change += $change;
        $total_tip    += $tip;
    }

    $total_bills_count = count($sales_data);

    // Invoice view has no per-item rows to derive cost from, so pull real cost (d.cost_price,
    // falling back to the product's current bprice) straight from the sale details for the
    // same filtered bills. Never assume a margin — if nothing here has a recorded cost,
    // $has_cost_data stays false and reports_kpi.php hides the ກຳໄລ card instead of guessing.
    try {
        $costStmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(d.save_qty * (CASE WHEN d.cost_price > 0 THEN d.cost_price ELSE COALESCE(p.bprice, 0) END)), 0) AS known_cost,
                MAX(CASE WHEN d.cost_price > 0 OR COALESCE(p.bprice, 0) > 0 THEN 1 ELSE 0 END) AS has_cost
            FROM st_tbsale_save_detail d
            INNER JOIN st_tbsale_save s ON d.save_bill = s.sale_save_bill
            LEFT JOIN st_products p ON (d.save_proid = p.product_id AND (p.store_id = s.store_id OR s.store_id IS NULL OR s.store_id <= 1))
            WHERE {$whereClause}
        ");
        $costStmt->execute($params);
        $costRow = $costStmt->fetch(PDO::FETCH_ASSOC);
        $has_cost_data = !empty($costRow['has_cost']);
        if ($has_cost_data) {
            $total_profit = max(0, $total_net - floatval($costRow['known_cost'] ?? 0));
        }
    } catch (Exception $e) {}
}

