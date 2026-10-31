<?php
/**
 * ສ່ວນ: Checkout (1): ຄິດຍອດເງິນ
 * ພາຍໃນ transaction: ຍອດລວມ, ແລກຄະແນນສະສົມ, VAT, ເງິນທອນ, ເລກບິນ
 * (ລະຫັດສາຂານຳໜ້າ) ແລະ ທະນາຄານທີ່ຮັບໂອນ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ api/pos_backend.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

        $pdo->beginTransaction();
        
        $user_receive_name = $_SESSION['username'] ?? $_SESSION['fname'] ?? 'Admin';
        $sold_by_id        = intval($_SESSION['user_id'] ?? 1);
        
        // Calculate totals & Tax/VAT
        $subtotal = 0;
        $total_cost = 0;
        $total_qty = 0;
        
        foreach ($cart as $item) {
            $qty = floatval($item['quantity']);
            $price = floatval($item['unit_price']);
            $cost = floatval($item['cost_price']);
            $subtotal += ($qty * $price);
            $total_cost += ($qty * $cost);
            $total_qty += $qty;
        }
        
        $tax_type = !empty($company['tax_type']) ? $company['tax_type'] : 'none';
        $vat_percent = ($tax_type === 'none') ? 0.00 : floatval($company['vat_percent'] ?? 0);

        // ===== LOYALTY REDEMPTION: validate + price the rewards before any totals are fixed =====
        $points_redeemed = 0;
        $points_discount = 0.00;
        $redeem_notes    = [];
        if (!empty($redeem_ids)) {
            $redeemFail = function ($msg) use ($pdo) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => $msg]);
                exit();
            };
            if (loyaltySetting($pdo, 'loyalty_enabled', getPosStoreId(), '0') !== '1') {
                $redeemFail(t('pos.redeem_err_disabled', 'ລະບົບຄະແນນສະສົມຖືກປິດຢູ່ — ແລກຄະແນນບໍ່ໄດ້'));
            }
            if (intval($customer_id) <= 0) {
                $redeemFail(t('pos.redeem_err_no_customer', 'ກະລຸນາເລືອກລູກຄ້າກ່ອນແລກຄະແນນ'));
            }
            // Lock the customer's row so two tills can't spend the same points at once.
            // ຄະແນນແຍກແຕ່ລະສາຂາ: ລູກຄ້າສາຂາອື່ນ ແລກຄະແນນຢູ່ສາຂານີ້ບໍ່ໄດ້
            $lockStmt = $pdo->prepare("SELECT points FROM st_customers WHERE customer_id = ? AND (store_id = ? OR store_id = 0) FOR UPDATE");
            $lockStmt->execute([intval($customer_id), (int)getPosStoreId()]);
            $pointsAvailable = $lockStmt->fetchColumn();
            if ($pointsAvailable === false) {
                $redeemFail(t('pos.redeem_err_no_customer', 'ກະລຸນາເລືອກລູກຄ້າກ່ອນແລກຄະແນນ'));
            }

            $uniqIds = array_values(array_unique($redeem_ids));
            // ລາງວັນຂອງສາຂາອື່ນ ແລກຢູ່ສາຂານີ້ບໍ່ໄດ້ (ຄະແນນແຍກແຕ່ລະສາຂາ)
            $rwStmt = $pdo->prepare("SELECT * FROM st_loyalty_rewards WHERE is_active = 1 AND store_id = ? AND id IN (" . implode(',', array_fill(0, count($uniqIds), '?')) . ")");
            $rwStmt->execute(array_merge([(int)getPosStoreId()], $uniqIds));
            $rewardsById = [];
            foreach ($rwStmt->fetchAll(PDO::FETCH_ASSOC) as $rw) $rewardsById[(int)$rw['id']] = $rw;

            // Product rewards must arrive as matching 0-price cart lines tagged with reward_id.
            $rewardLineQty = [];
            foreach ($cart as $item) {
                $rid = intval($item['reward_id'] ?? 0);
                if ($rid > 0) $rewardLineQty[$rid] = ($rewardLineQty[$rid] ?? 0) + floatval($item['quantity']);
            }

            $discountFromRewards = 0.00;
            $redeemCount = array_count_values($redeem_ids);
            foreach ($redeemCount as $rid => $times) {
                if (!isset($rewardsById[$rid])) {
                    $redeemFail(t('pos.redeem_err_reward_gone', 'ລາງວັນທີ່ເລືອກຖືກປິດ ຫຼື ລຶບໄປແລ້ວ — ກະລຸນາເລືອກໃໝ່'));
                }
                $rw = $rewardsById[$rid];
                $points_redeemed += intval($rw['points_cost']) * $times;
                if ($rw['reward_type'] === 'product') {
                    if (abs(($rewardLineQty[$rid] ?? 0) - intval($rw['product_qty']) * $times) > 0.0001) {
                        $redeemFail(t('pos.redeem_err_mismatch', 'ລາຍການແລກຄະແນນໃນກະຕ່າບໍ່ຖືກຕ້ອງ — ກະລຸນາແລກໃໝ່'));
                    }
                } else {
                    // A discount reward needs the purchase to reach its minimum AND to be more
                    // than the discount itself (same rule the POS applies before redeeming).
                    $rwMinSpend = floatval($rw['min_spend'] ?? 0);
                    if ($subtotal < $rwMinSpend || $subtotal <= floatval($rw['discount_amount'])) {
                        $redeemFail(sprintf(t('pos.redeem_err_min_spend', 'ຍອດຊື້ບໍ່ຮອດເງື່ອນໄຂຂອງ "%s" — ຕ້ອງຊື້ຢ່າງໜ້ອຍ %s ' . kipSymbol()), $rw['reward_name'], number_format(max($rwMinSpend, floatval($rw['discount_amount']) + 1))));
                    }
                    $discountFromRewards += floatval($rw['discount_amount']) * $times;
                }
                $redeem_notes[] = $rw['reward_name'] . ($times > 1 ? ' ×' . $times : '');
            }
            foreach ($rewardLineQty as $rid => $q) {
                if (!isset($redeemCount[$rid])) {
                    $redeemFail(t('pos.redeem_err_mismatch', 'ລາຍການແລກຄະແນນໃນກະຕ່າບໍ່ຖືກຕ້ອງ — ກະລຸນາແລກໃໝ່'));
                }
            }
            if ($discountFromRewards > 0 && $subtotal <= $discountFromRewards) {
                $redeemFail(sprintf(t('pos.redeem_err_min_spend', 'ຍອດຊື້ບໍ່ຮອດເງື່ອນໄຂຂອງ "%s" — ຕ້ອງຊື້ຢ່າງໜ້ອຍ %s ' . kipSymbol()), implode(', ', $redeem_notes), number_format($discountFromRewards + 1)));
            }
            if ($points_redeemed > intval($pointsAvailable)) {
                $redeemFail(sprintf(t('pos.redeem_err_not_enough', 'ຄະແນນບໍ່ພໍ: ມີ %s ຄະແນນ ແຕ່ຕ້ອງໃຊ້ %s ຄະແນນ'), number_format(intval($pointsAvailable)), number_format($points_redeemed)));
            }
            // A discount reward can bring the bill down to 0, never below it.
            $points_discount = min($discountFromRewards, max(0, $subtotal - $discount_bill));
            $discount_bill += $points_discount;
        }

        $amount_after_discount = max(0, $subtotal - $discount_bill);
        $vat_amount = 0.00;
        $net_total = $amount_after_discount;

        if ($tax_type === 'exclusive' && $vat_percent > 0 && $vat_percent < 100) {
            // The shop's listed price is the net amount and the rate% is the tax's share of the
            // FINAL total (not of the net price) — e.g. a 75,000 item at 10% comes to 8,333.33
            // tax / 83,333.33 total, not a flat 7,500 add-on.
            $vat_amount = round($amount_after_discount * ($vat_percent / (100 - $vat_percent)), 2);
            $net_total = $amount_after_discount + $vat_amount;
        } elseif ($tax_type === 'inclusive' && $vat_percent > 0) {
            $vat_amount = round($amount_after_discount - ($amount_after_discount / (1 + ($vat_percent / 100))), 2);
            $net_total = $amount_after_discount;
        }

        $total_paid = $cash_received + $qr_received;
        $change = max(0, $total_paid - $net_total);

        // ຂາຍຕິດໜີ້ ຖືກລຶບອອກຈາກລະບົບແລ້ວ (ໂມດູນລູກຄ້າຕິດໜີ້) — ຄຳຂໍທີ່ຍັງສົ່ງ pay_mode=credit
        // (ເຊັ່ນ ແທັບເກົ່າທີ່ເປີດຄ້າງ) ຖືກປະຕິເສດ ບໍ່ໃຫ້ກາຍເປັນບິນທີ່ຈ່າຍບໍ່ຄົບ.
        if ($pay_mode === 'credit' || $payment_type === 'ຕິດໜີ້') {
            if ($pdo->inTransaction()) $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => (tl('ການຂາຍຕິດໜີ້ຖືກປິດແລ້ວ') . ' — ' . tl('ກະລຸນາໂຫຼດໜ້າຂາຍໃໝ່ ແລ້ວຊຳລະແບບປົກກະຕິ'))]);
            exit();
        }

        // Net bill value expressed in the tendered currency (KIP amount / rate),
        // ປັດເປັນຈຳນວນເຕັມ: ບໍ່ກາຍ .50 ປັດລົງ, ກາຍ .50 ປັດຂຶ້ນ (ຄືກັບທີ່ສະແດງໃນໜ້າຂາຍ).
        $paid_amount_foreign = ($paid_currency === 'KIP')
            ? round($net_total, 2)
            : roundForeignUnits($net_total / $paid_rate);

        // Generate daily resetting invoice number with branch code (Format: YYYYMMDD-[storeCode]0001)
        // e.g. Branch 1 -> 20261014-10001, Branch 2 -> 20261014-20001
        $todayDate = date('Y-m-d');
        $todayStr  = date('Ymd');
        $activeStoreId = getPosStoreId();

        $scStmt = $pdo->prepare("SELECT store_code FROM st_tbstore WHERE store_id = ? LIMIT 1");
        $scStmt->execute([$activeStoreId]);
        $storeCode = preg_replace('/\D+/', '', (string)$scStmt->fetchColumn());
        if ($storeCode === '') $storeCode = (string)$activeStoreId;

        $stmtSeq = $pdo->prepare("SELECT COUNT(*) FROM st_tbsale_save WHERE sale_date = :today_date AND store_id = :store_id");
        $stmtSeq->execute([':today_date' => $todayDate, ':store_id' => $activeStoreId]);
        $todayCount = intval($stmtSeq->fetchColumn() ?? 0);
        $nextSeq = $todayCount + 1;

        $chkBill = $pdo->prepare("SELECT 1 FROM st_tbsale_save WHERE sale_save_bill = ? LIMIT 1");
        for ($guard = 0; $guard < 10000; $guard++, $nextSeq++) {
            $seqStr = str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
            $invoice_no = $todayStr . '-' . $storeCode . $seqStr;
            $chkBill->execute([$invoice_no]);
            if (!$chkBill->fetchColumn()) break;
        }

        // Queue number: same daily-resetting counter, zero-padded to 2 digits (01, 02, ... 10).
        $queue_display = str_pad((string)$nextSeq, 2, '0', STR_PAD_LEFT);

        $bank_account_id = isset($_POST['bank_account_id']) && intval($_POST['bank_account_id']) > 0 ? intval($_POST['bank_account_id']) : null;
        $bank_name = isset($_POST['bank_name']) && trim($_POST['bank_name']) !== '' ? trim($_POST['bank_name']) : null;

        if (empty($bank_account_id) && ($qr_received > 0 || strpos($payment_type, 'ໂອນ') !== false || strpos($payment_type, 'QR') !== false || strpos($payment_type, 'Transfer') !== false)) {
            try {
                // This branch's own banks, plus any shared across every branch (store_id = 0).
                $bStmt = $pdo->prepare("SELECT * FROM st_bank_accounts WHERE is_active = 1 AND (store_id = ? OR store_id = 0) ORDER BY id ASC LIMIT 1");
                $bStmt->execute([$activeStoreId]);
                $bank_info = $bStmt->fetch(PDO::FETCH_ASSOC);
                if ($bank_info) {
                    $bank_account_id = intval($bank_info['id']);
                    $bank_name = $bank_info['bank_name'];
                }
            } catch (Throwable $e) {}
        } else if ($bank_account_id) {
            try {
                $bStmt = $pdo->prepare("SELECT * FROM st_bank_accounts WHERE id = ? AND (store_id = ? OR store_id = 0)");
                $bStmt->execute([$bank_account_id, $activeStoreId]);
                $bank_info = $bStmt->fetch(PDO::FETCH_ASSOC);
                if ($bank_info && !empty($bank_info['bank_name'])) {
                    $bank_name = $bank_info['bank_name'];
                }
            } catch (Throwable $e) {}
        }

