<?php
/**
 * ສ່ວນ: Checkout (2): ບັນທຶກບິນ
 * ບັນທຶກ tbsale_save, sales, ລາຍການ + ຕັດສະຕັອກ (ລັອກແຖວ), ໜີ້ລູກຄ້າ, ຄະແນນສະສົມ, commit
 * ແລ້ວສົ່ງຜົນ JSON (ຈື່ໄວ້ກັນກົດຊ້ຳ).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ api/pos_backend.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

        // Insert into tbsale_save
        $stmtSave = $pdo->prepare("
            INSERT INTO st_tbsale_save (sale_save_bill, sale_date, sale_time, user_receive, customer_id, customer_name, ip_address, sale_qty, sale_amount, sale_discount_bill, sale_barlance, sale_pay, sale_return, type_pay, bank_account_id, bank_name, sale_status, store_id, paid_currency, paid_rate, paid_amount_foreign, queue_no, cash_received, qr_received)
            VALUES (:invoice_no, CURDATE(), CURTIME(), :user_id, :customer_id, :customer_name, :ip_address, :sale_qty, :sale_amount, :discount_bill, :net_total, :cash_received, :change, :payment_type, :bank_account_id, :bank_name, 'SUCCESS', :store_id, :paid_currency, :paid_rate, :paid_amount_foreign, :queue_no, :cash_received_col, :qr_received_col)
        ");
        $stmtSave->execute([
            ':invoice_no'      => $invoice_no,
            ':queue_no'        => $queue_display,
            ':user_id'         => $user_receive_name,
            ':customer_id'     => $customer_id,
            ':customer_name'   => $customer_name,
            ':ip_address'      => $client_ip,
            ':sale_qty'        => $total_qty,
            ':sale_amount'     => $subtotal,
            ':discount_bill'   => $discount_bill,
            ':net_total'       => $net_total,
            ':cash_received'   => $cash_received,
            ':change'          => $change,
            ':payment_type'    => $payment_type,
            ':bank_account_id' => $bank_account_id,
            ':bank_name'       => $bank_name,
            ':store_id'        => $activeStoreId,
            ':paid_currency'      => $paid_currency,
            ':paid_rate'         => $paid_rate,
            ':paid_amount_foreign' => $paid_amount_foreign,
            ':cash_received_col' => $cash_received,
            ':qr_received_col'   => $qr_received
        ]);

        // Insert into physical sales table with tax details
        try {
            $stmtSalesTable = $pdo->prepare("
                INSERT INTO st_sales (invoice_number, sold_by, customer_id, customer_name, subtotal, discount_amount, vat_amount, tax_type, vat_rate, total_amount, cash_received, change_amount, payment_type, bank_account_id, bank_name, status, store_id, created_at, paid_currency, paid_rate, paid_amount_foreign)
                VALUES (:invoice_no, :sold_by_id, :customer_id, :customer_name, :subtotal, :discount_bill, :vat_amount, :tax_type, :vat_rate, :net_total, :cash_received, :change, :payment_type, :bank_account_id, :bank_name, 'SUCCESS', :store_id, NOW(), :paid_currency, :paid_rate, :paid_amount_foreign)
                ON DUPLICATE KEY UPDATE subtotal = :subtotal, discount_amount = :discount_bill, vat_amount = :vat_amount, tax_type = :tax_type, vat_rate = :vat_rate, total_amount = :net_total, bank_account_id = :bank_account_id, bank_name = :bank_name, store_id = :store_id, paid_currency = :paid_currency, paid_rate = :paid_rate, paid_amount_foreign = :paid_amount_foreign
            ");
            $stmtSalesTable->execute([
                ':invoice_no'      => $invoice_no,
                ':sold_by_id'      => $sold_by_id,
                ':customer_id'     => $customer_id,
                ':customer_name'   => $customer_name,
                ':subtotal'        => $subtotal,
                ':discount_bill'   => $discount_bill,
                ':vat_amount'      => $vat_amount,
                ':tax_type'        => $tax_type,
                ':vat_rate'        => $vat_percent,
                ':net_total'       => $net_total,
                ':cash_received'   => $cash_received,
                ':change'          => $change,
                ':payment_type'    => $payment_type,
                ':bank_account_id' => $bank_account_id,
                ':bank_name'       => $bank_name,
                ':store_id'        => $activeStoreId,
                ':paid_currency'      => $paid_currency,
                ':paid_rate'         => $paid_rate,
                ':paid_amount_foreign' => $paid_amount_foreign
            ]);
        } catch (Throwable $ex) {}
        
        // Insert into tbsale_save_detail and update inventory
        $stmtDetail = $pdo->prepare("
            INSERT INTO st_tbsale_save_detail (save_bill, save_date, save_time, save_proid, save_proname, save_qty, save_price, save_orig_price, is_reward, cost_price, save_money, save_net_money, user_receives, multiplier)
            VALUES (:save_bill, CURDATE(), CURTIME(), :save_proid, :save_proname, :save_qty, :save_price, :save_orig_price, :is_reward, :cost_price, :save_money, :save_net_money, :user_receives, :multiplier)
        ");
        
        $stmtUpdateStock = $pdo->prepare("
            UPDATE st_products SET qty = qty - :deduct_qty WHERE product_id = :product_id AND store_id = :store_id
        ");

        $stmtCheckStock = $pdo->prepare("
            SELECT product_name, qty, cut_qty FROM st_products WHERE product_id = :pid AND store_id = :store_id FOR UPDATE
        ");
        
        $details_summary = [];
        $updated_stocks = [];
        
        foreach ($cart as $item) {
            $pid = intval($item['product_id']);
            $proname = $item['product_name'] . ' (' . $item['unit_name'] . ')';
            $qty = floatval($item['quantity']);
            $price = floatval($item['unit_price']);
            $cost = floatval($item['cost_price']);
            $multiplier = intval($item['multiplier'] ?? 1);
            $total_item = $qty * $price;
            $deduct_stock_qty = $qty * $multiplier;

            $is_gift = !empty($item['is_free_gift']) || $price == 0 || strpos($item['product_name'], '(ແຖມ)') !== false;
            // A points-reward product is a real item leaving the shelf — it gets the normal
            // stock check below, not the promo-gift shortcut.
            $is_reward_line = intval($item['reward_id'] ?? 0) > 0;

            if ($is_gift && !$is_reward_line) {
                $stmtCheckStock->execute([':pid' => $pid, ':store_id' => $activeStoreId]);
                $pData = $stmtCheckStock->fetch();
                $shouldCutQty = $pData ? intval($pData['cut_qty'] ?? 0) : 0;
                $currentDbStock = $pData ? floatval($pData['qty']) : 0;
            } else {
                $stmtCheckStock->execute([':pid' => $pid, ':store_id' => $activeStoreId]);
                $pData = $stmtCheckStock->fetch();
                if (!$pData) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    echo json_encode(['success' => false, 'message' => "ບໍ່ພົບຂໍ້ມູນສິນຄ້າ ID: {$pid} ໃນລະບົບ!"]);
                    exit();
                }

                $shouldCutQty = intval($pData['cut_qty'] ?? 1); // 1=ຕັດ qty, 0=ບໍ່ຕັດ qty
                $currentDbStock = floatval($pData['qty']);

                if ($shouldCutQty) {
                    if ($currentDbStock <= 0) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        echo json_encode(['success' => false, 'message' => "ສິນຄ້າ \"{$pData['product_name']}\" ໝົດແລ້ວ! ບໍ່ສາມາດຂາຍໄດ້"]);
                        exit();
                    }

                    if ($currentDbStock < $deduct_stock_qty) {
                        if ($pdo->inTransaction()) $pdo->rollBack();
                        echo json_encode(['success' => false, 'message' => "ສິນຄ້າ \"{$pData['product_name']}\" ເຫຼືອພຽງ {$currentDbStock} ອັນ! ບໍ່ພໍສຳລັບການຂາຍ"]);
                        exit();
                    }
                }
            }
            
            $stmtDetail->execute([
                ':save_bill'    => $invoice_no,
                ':save_proid'   => $pid,
                ':save_proname' => $proname,
                ':save_qty'     => $qty,
                ':save_price'   => $price,
                // pre-promotion unit price + reward flag, so a reprint draws this line exactly
                // like the checkout receipt did (struck-through price / [reward] tag)
                ':save_orig_price' => floatval($item['original_price'] ?? $price),
                ':is_reward'    => !empty($is_reward_line) ? 1 : 0,
                ':cost_price'   => $cost,
                ':save_money'   => $total_item,
                ':save_net_money'=> $total_item,
                ':user_receives'=> $user_receive_name,
                ':multiplier'   => $multiplier
            ]);
            
            $newStock = $currentDbStock;
            // ຕັດ qty ສະເພາະສິນຄ້າທີ່ cut_qty = 1 ເທົ່ານັ້ນ
            if ($shouldCutQty) {
                $stmtUpdateStock->execute([
                    ':deduct_qty' => $deduct_stock_qty,
                    ':product_id' => $pid,
                    ':store_id'   => $activeStoreId
                ]);
                $newStock = max(0, $currentDbStock - $deduct_stock_qty);
            }

            $updated_stocks[] = [
                'product_id' => $pid,
                'new_qty'    => $newStock,
                'cut_qty'    => $shouldCutQty
            ];

            $unit_name = trim($item['unit_name'] ?? '');
            $proname = $item['product_name'] . ($unit_name !== '' ? ' (' . $unit_name . ')' : '');
            $orig_price = floatval($item['original_price'] ?? $price);
            $is_gift = !empty($item['is_free_gift']) || $price == 0 || strpos($item['product_name'], '(ແຖມ)') !== false;

            $details_summary[] = [
                'proname'        => $proname,
                'unit_name'      => $unit_name,
                'qty'            => $qty,
                'price'          => $price,
                'original_price' => $orig_price,
                'is_free_gift'   => $is_gift,
                'is_reward'      => $is_reward_line,
                'total'          => $total_item
            ];
        }
        
        // ===== ລູກຄ້າຕິດໜີ້: record the unpaid part of a credit sale =====
        // Not wrapped in a try/catch: a credit sale without its debt row must roll back.
        $credit_due_date = null;
        if ($credit_amount > 0) {
            // ກຳນົດຊຳລະ (optional): a real yyyy-mm-dd date, not in the past
            $dueIn = trim((string)($_POST['due_date'] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dueIn) && strtotime($dueIn) !== false && $dueIn >= date('Y-m-d')) {
                $credit_due_date = $dueIn;
            }
            $pdo->prepare("INSERT INTO st_customer_debts (store_id, customer_id, customer_name, invoice_number, bill_amount, deposit_amount, amount, paid_amount, balance, status, due_date, note, created_by, created_at)
                           VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, 'unpaid', ?, NULL, ?, NOW())")
                ->execute([$activeStoreId, intval($customer_id), $customer_name, $invoice_no, $net_total, $total_paid, $credit_amount, $credit_amount, $credit_due_date, $user_receive_name]);
        }

        // ===== CUSTOMER LOYALTY POINTS =====
        // 1. Spend the redeemed points (validated + locked above). Not wrapped in a
        //    try/catch: if this fails the whole sale must roll back, or the customer would
        //    get the reward without the points being taken.
        $points_earned = 0;
        $customer_points_balance = 0;
        $loyalty_active = 0;
        if ($points_redeemed > 0) {
            changeCustomerPoints($pdo, $customer_id, -$points_redeemed, 'redeem', $invoice_no, implode(', ', $redeem_notes), $activeStoreId);
            $pdo->prepare("UPDATE st_tbsale_save SET points_redeemed = ?, points_discount = ? WHERE sale_save_bill = ?")
                ->execute([$points_redeemed, $points_discount, $invoice_no]);
        }
        // 2. Earn Y points for every X kip actually paid — $net_total is already after the
        //    points discount, so a redeemed discount doesn't earn points back on itself.
        //    ຂາຍຕິດໜີ້: only the deposit paid today counts; the part on credit isn't paid yet.
        $points_base = ($credit_amount > 0) ? $total_paid : $net_total;
        if (!empty($customer_id) && intval($customer_id) > 0) {
            if (getSetting($pdo, 'loyalty_enabled', '0') === '1') {
                $loyalty_active = 1;
                $lpSpend = (int)getSetting($pdo, 'loyalty_spend_amount', '100000');
                $lpPer   = (int)getSetting($pdo, 'loyalty_points_earned', '1');
                if ($lpSpend > 0 && $lpPer > 0) {
                    $points_earned = intdiv((int)round($points_base), $lpSpend) * $lpPer;
                    if ($points_earned > 0) {
                        try {
                            changeCustomerPoints($pdo, $customer_id, $points_earned, 'earn', $invoice_no, null, $activeStoreId);
                            $pdo->prepare("UPDATE st_tbsale_save SET points_earned = ? WHERE sale_save_bill = ?")
                                ->execute([$points_earned, $invoice_no]);
                        } catch (Throwable $e) { $points_earned = 0; }
                    }
                }
            }
            try {
                $cpStmt = $pdo->prepare("SELECT points FROM st_customers WHERE customer_id = ?");
                $cpStmt->execute([intval($customer_id)]);
                $customer_points_balance = (int)$cpStmt->fetchColumn();
            } catch (Throwable $e) {}
        }

        $pdo->commit();

        $responsePayload = [
            'success'        => true,
            'points_earned'   => $points_earned,
            'points_redeemed' => $points_redeemed,
            'points_discount' => $points_discount,
            'customer_id'     => $customer_id,
            'customer_points' => $customer_points_balance,
            'loyalty_active'  => $loyalty_active,
            'invoice_number' => $invoice_no,
            'queue_number'   => $queue_display,
            'date'           => date('d/m/Y H:i'),
            'cashier'        => $user_receive_name,
            'customer_name'  => $customer_name,
            'customer_phone' => trim($_POST['customer_phone'] ?? ''),
            'subtotal'       => $subtotal,
            'discount_amount'=> $discount_bill,
            'tax_type'       => $tax_type,
            'vat_rate'       => $vat_percent,
            'vat_amount'     => $vat_amount,
            'total_amount'   => $net_total,
            'paid_currency'  => $paid_currency,
            'paid_rate'      => $paid_rate,
            'cash_received'  => $cash_received,
            'qr_received'    => $qr_received,
            'pay_mode'       => $pay_mode,
            'change'         => $change,
            'credit_amount'  => $credit_amount,
            'credit_due_date'=> $credit_due_date ? date('d/m/Y', strtotime($credit_due_date)) : '',
            'payment_type'   => $payment_type,
            'bank_account_id'=> $bank_account_id,
            'bank_name'      => $bank_name,
            'bank_account_no'=> $bank_info['account_number'] ?? '',
            'bank_account_name'=> $bank_info['account_name'] ?? '',
            'bank_qr_img'    => resolveBankQr($bank_info['qr_code_img'] ?? '', $bank_info['bank_code'] ?? ''),
            'details'        => $details_summary,
            'updated_stocks' => $updated_stocks
        ];

        // Remember this exact request so a duplicate arriving within the window above (see the
        // idempotency guard) replays this same successful response instead of billing twice.
        $_SESSION['pos_last_checkout_fp']       = $idemFp;
        $_SESSION['pos_last_checkout_time']     = time();
        $_SESSION['pos_last_checkout_response'] = $responsePayload;

        if (ob_get_length()) ob_clean();
        echo json_encode($responsePayload);
        exit();
