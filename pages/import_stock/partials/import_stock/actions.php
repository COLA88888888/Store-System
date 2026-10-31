<?php
/**
 * ສ່ວນ: ການກະທຳ POST: ບັນທຶກບິນນຳເຂົ້າ
 * ບັນທຶກບິນນຳເຂົ້າ + ລາຍການ, ເພີ່ມສະຕັອກ, ອັບເດດລາຄາທຶນ/ລາຄາຂາຍ ແລະ ຂໍ້ຄວາມແຈ້ງຜົນ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/import_stock/import_stock.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// ເລກບິນຮັບເຂົ້າ: IMP-{ລະຫັດສາຂາ}{ລຳດັບ 4 ຫຼັກ ຂອງສາຂານັ້ນ} — ສາຂາ 1: IMP-10001, IMP-10002…; ສາຂາ 2: IMP-20001…
// ລະຫັດສາຂາມາຈາກ st_tbstore.store_code. ເລກບິນເກົ່າ (000001…) ຄົງໄວ້ຄືເກົ່າ ບໍ່ນັບລວມ.
if (!function_exists('importInvoiceNumber')) {
    function importInvoiceNumber(PDO $pdo, $storeId) {
        $st = $pdo->prepare("SELECT store_code FROM st_tbstore WHERE store_id = ?");
        $st->execute([(int)$storeId]);
        $code = preg_replace('/[^0-9A-Za-z]/', '', (string)$st->fetchColumn());
        if ($code === '') $code = (string)(int)$storeId;
        $prefix = 'IMP-' . $code;
        $mx = $pdo->prepare("SELECT COALESCE(MAX(CAST(SUBSTRING(invoice_number, ?) AS UNSIGNED)), 0)
                             FROM st_imports WHERE store_id = ? AND invoice_number REGEXP ?");
        $mx->execute([strlen($prefix) + 1, (int)$storeId, '^' . $prefix . '[0-9]{4,}$']);
        return $prefix . str_pad((string)((int)$mx->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);
    }
}

// ອັບເດດສະຕັອກ + ລາຄາສິນຄ້າຫຼັງຮັບເຂົ້າ/ແກ້ໄຂ. ລາຄາໃນແຖວບິນເປັນລາຄາຂອງ "ຫົວໜ່ວຍທີ່ຮັບເຂົ້າ":
//  - ຫົວໜ່ວຍຫຼັກ (ປ໋ອງ): ຄືເກົ່າ — st_products.bprice = ລາຄາຊື້, price = ລາຄາຂາຍ (ຖ້າໃສ່)
//  - ຫົວໜ່ວຍໃຫຍ່ (ແພັກ/ແກັດ ×N): ອັບເດດລາຄາຂອງຫົວໜ່ວຍນັ້ນ (st_product_units); ຫົວໜ່ວຍຫຼັກໄດ້ຕົ້ນທຶນ = ລາຄາ ÷ N,
//    ລາຄາຂາຍຂອງຫົວໜ່ວຍຫຼັກບໍ່ແຕະ. ເມື່ອກ່ອນລາຄາແພັກ (72,000) ຂຽນທັບລາຄາປ໋ອງ (12,000) ໂດຍກົງ.
if (!function_exists('importApplyStockAndPrices')) {
    function importApplyStockAndPrices(PDO $pdo, $productId, $storeId, $unitRowId, $multiplier, $baseQtyDelta, $cost, $sell) {
        $whereStore = $storeId !== null ? ' AND store_id = ?' : '';
        $tail = $storeId !== null ? [$productId, $storeId] : [$productId];
        if ($unitRowId > 0 && $multiplier > 1) {
            $pdo->prepare("UPDATE st_product_units SET bprice = ?" . ($sell > 0 ? ", price = ?" : "") . " WHERE id = ? AND product_id = ?")
                ->execute(array_merge([$cost], $sell > 0 ? [$sell] : [], [$unitRowId, $productId]));
            $pdo->prepare("UPDATE st_products SET qty = GREATEST(0, qty + ?), bprice = ? WHERE product_id = ?" . $whereStore)
                ->execute(array_merge([$baseQtyDelta, round($cost / $multiplier, 2)], $tail));
            return;
        }
        if ($sell > 0) {
            $pdo->prepare("UPDATE st_products SET qty = GREATEST(0, qty + ?), bprice = ?, price = ? WHERE product_id = ?" . $whereStore)
                ->execute(array_merge([$baseQtyDelta, $cost, $sell], $tail));
        } else {
            $pdo->prepare("UPDATE st_products SET qty = GREATEST(0, qty + ?), bprice = ? WHERE product_id = ?" . $whereStore)
                ->execute(array_merge([$baseQtyDelta, $cost], $tail));
        }
    }
}

// ====== HANDLE POST ACTIONS ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- 1. ACTION: IMPORT STOCK BILL (MULTI-ITEM OR SINGLE ITEM) ---
    if ($_POST['action'] === 'import_stock_bill') {
        if (!hasPermission('import_stock', 'add')) {
            $message = t('import_stock.err_no_permission', 'ທ່ານບໍ່ມີສິດໃນການເພີ່ມ ຫຼື ບັນທຶກຮັບເຂົ້າສິນຄ້າ!');
            $message_type = 'warning';
        } else {
        $invoice_number = trim($_POST['invoice_number'] ?? '');
        $supplier_name  = trim($_POST['supplier_name'] ?? '');
        $raw_date       = trim($_POST['import_date'] ?? '');
        $import_date    = !empty($raw_date) ? $raw_date . ' ' . date('H:i:s') : date('Y-m-d H:i:s');
        $notes          = trim($_POST['notes'] ?? '');
        $cart_json      = $_POST['cart_json'] ?? '[]';
        $items          = json_decode($cart_json, true);


        if (!empty($items) && is_array($items)) {
            try {
                $pdo->beginTransaction();

                // Calculate bill total cost
                $total_bill_cost = 0;
                foreach ($items as $it) {
                    $q = intval($it['quantity'] ?? 0);
                    $c = floatval($it['cost_price'] ?? 0);
                    $total_bill_cost += ($q * $c);
                }

                $activeStoreId = getActiveStoreId($pdo);

                // ອອກເລກບິນໃໝ່ຕອນບັນທຶກສະເໝີ (ເລກໃນໜ້າເປັນພຽງຕົວຢ່າງ): ລັອກແຖວສາຂາໄວ້ ສອງຄົນບັນທຶກພ້ອມກັນຈະບໍ່ໄດ້ເລກຊ້ຳ
                $pdo->prepare("SELECT store_id FROM st_tbstore WHERE store_id = ? FOR UPDATE")->execute([$activeStoreId]);
                $invoice_number = importInvoiceNumber($pdo, $activeStoreId);

                // Insert master `imports` record with selected import_date and store_id
                $insImp = $pdo->prepare("INSERT INTO st_imports (invoice_number, supplier_name, import_date, total_cost, created_by, notes, store_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insImp->execute([$invoice_number, $supplier_name, $import_date, $total_bill_cost, $_SESSION['user_id'], $notes, $activeStoreId]);
                $import_id = $pdo->lastInsertId();

                $insertedCount = 0;
                foreach ($items as $item) {
                    $product_id   = intval($item['product_id'] ?? 0);
                    $unit_key     = trim($item['unit_key'] ?? 'base');
                    $quantity     = intval($item['quantity'] ?? 0);
                    $cost_price   = floatval($item['cost_price'] ?? 0);
                    $sell_price   = floatval($item['sell_price'] ?? 0);
                    $expiry_date  = !empty($item['expiry_date']) ? $item['expiry_date'] : null;

                    if ($product_id <= 0 || $quantity <= 0) continue;

                    // Fetch Product
                    $activeStoreId = getActiveStoreId($pdo);
                    $pStmt = $pdo->prepare("SELECT * FROM st_products WHERE product_id = ? AND store_id = ? FOR UPDATE");
                    $pStmt->execute([$product_id, $activeStoreId]);
                    $product = $pStmt->fetch();

                    if (!$product) continue;

                    $unit_name = !empty($product['unit']) ? $product['unit'] : t('import_stock.default_unit', 'ອັນ');
                    $multiplier = 1;
                    $unitRowId  = 0;

                    if ($unit_key !== 'base') {
                        $uStmt = $pdo->prepare("SELECT unit_name, multiplier FROM st_product_units WHERE id = ? AND product_id = ?");
                        $uStmt->execute([intval($unit_key), $product_id]);
                        $puRow = $uStmt->fetch();
                        if ($puRow) {
                            $unit_name  = $puRow['unit_name'];
                            $multiplier = max(1, intval($puRow['multiplier']));
                            $unitRowId  = intval($unit_key);
                        }
                    }

                    $total_base_qty = $quantity * $multiplier;
                    $total_cost     = $quantity * $cost_price;

                    // Insert into import_details
                    $insDet = $pdo->prepare("INSERT INTO st_import_details (import_id, product_id, unit_name, multiplier, quantity, total_base_qty, cost_price, sell_price, total_cost, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $insDet->execute([
                        $import_id, $product_id, $unit_name, $multiplier,
                        $quantity, $total_base_qty, $cost_price, ($sell_price > 0 ? $sell_price : null), $total_cost, $expiry_date
                    ]);
                    $import_detail_id = $pdo->lastInsertId();

                    // Insert into product_batches
                    $insBatch = $pdo->prepare("INSERT INTO st_product_batches (product_id, import_detail_id, expiry_date, initial_qty, quantity) VALUES (?, ?, ?, ?, ?)");
                    $insBatch->execute([$product_id, $import_detail_id, $expiry_date, $total_base_qty, $total_base_qty]);

                    // Update product stock + buy price, and the sell price too when one was entered (ຕາມຫົວໜ່ວຍ — importApplyStockAndPrices)
                    importApplyStockAndPrices($pdo, $product_id, $activeStoreId, $unitRowId, $multiplier, $total_base_qty, $cost_price, $sell_price);

                    $insertedCount++;
                }

                $pdo->commit();
                $message = sprintf(t('import_stock.msg_bill_success', 'ບັນທຶກໃບບິນຮັບສິນຄ້າເຂົ້າເລກທີ %s ສຳເລັດ (ລວມ %s ລາຍການ)!'), $invoice_number, $insertedCount);
                $message_type = 'success';
                logActivity($pdo, "ຮັບສິນຄ້າເຂົ້າ (ໃບບິນ)", "ໃບບິນ: $invoice_number, $insertedCount ລາຍການ, ລວມ: " . number_format($total_bill_cost) . " " . kipSymbol());
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message = t('import_stock.err_prefix', 'ຜິດພາດ: ') . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            $message = t('import_stock.err_no_items', 'ກະລຸນາເພີ່ມສິນຄ້າໃສ່ລາຍການຮັບເຂົ້າຢ່າງນ້ອຍ 1 ລາຍການ!');
            $message_type = 'danger';
        }
        }
    }

    // --- 2. ACTION: DELETE/CANCEL IMPORT DETAIL ---
    elseif ($_POST['action'] === 'delete_import_detail') {
        $import_detail_id = intval($_POST['import_detail_id'] ?? 0);
        if ($import_detail_id > 0) {
            try {
                $pdo->beginTransaction();

                $detStmt = $pdo->prepare("
                    SELECT id.*, p.qty AS current_product_qty, pb.initial_qty AS batch_initial_qty, pb.quantity AS batch_rem_qty
                    FROM st_import_details id
                    JOIN st_products p ON id.product_id = p.product_id
                    LEFT JOIN st_product_batches pb ON id.import_detail_id = pb.import_detail_id
                    WHERE id.import_detail_id = ? FOR UPDATE
                ");
                $detStmt->execute([$import_detail_id]);
                $det = $detStmt->fetch();

                if ($det) {
                    $bRem = isset($det['batch_rem_qty']) ? intval($det['batch_rem_qty']) : null;
                    $bInit = isset($det['batch_initial_qty']) ? intval($det['batch_initial_qty']) : null;
                    $pQty = intval($det['current_product_qty']);
                    $baseQty = intval($det['total_base_qty']);

                    if (($bRem !== null && $bInit !== null && $bRem < $bInit) || ($pQty < $baseQty)) {
                        throw new Exception(t('import_stock.err_has_movement', 'ບໍ່ສາມາດລົບ/ຍົກເລີກໃບບິນນີ້ໄດ້ ເນື່ອງຈາກສິນຄ້ານີ້ມີການເຄື່ອນໄຫວ ຫຼື ຖືກຂາຍອອກໄປແລ້ວ!'));
                    }

                    // Revert stock quantity in products
                    $revStock = $pdo->prepare("UPDATE st_products SET qty = GREATEST(0, qty - ?) WHERE product_id = ?");
                    $revStock->execute([$det['total_base_qty'], $det['product_id']]);

                    // Update total cost in imports master
                    $updImp = $pdo->prepare("UPDATE st_imports SET total_cost = GREATEST(0, total_cost - ?) WHERE import_id = ?");
                    $updImp->execute([$det['total_cost'], $det['import_id']]);

                    // Delete batches
                    $delBatch = $pdo->prepare("DELETE FROM st_product_batches WHERE import_detail_id = ?");
                    $delBatch->execute([$import_detail_id]);

                    // Delete detail
                    $delDet = $pdo->prepare("DELETE FROM st_import_details WHERE import_detail_id = ?");
                    $delDet->execute([$import_detail_id]);

                    $message = t('import_stock.msg_cancel_success', 'ຍົກເລີກການຮັບສິນຄ້າເຂົ້າສຳເລັດ (ປັບສະຕັອກຄືນແລ້ວ)!');
                    $message_type = 'success';
                    logActivity($pdo, "ຍົກເລີກການຮັບສິນຄ້າເຂົ້າ", "Detail ID: $import_detail_id");
                }

                $pdo->commit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message = $e->getMessage();
                $message_type = 'danger';
            }
        }
    }

    // --- 3. ACTION: EDIT IMPORT DETAIL ---
    elseif ($_POST['action'] === 'edit_import_detail') {
        $import_detail_id = intval($_POST['import_detail_id'] ?? 0);
        $supplier_name    = trim($_POST['supplier_name'] ?? '');
        $quantity         = intval($_POST['quantity'] ?? 0);
        $cost_price       = floatval($_POST['cost_price'] ?? 0);
        $sell_price       = floatval($_POST['sell_price'] ?? 0);
        $unit_key         = trim($_POST['unit_key'] ?? 'base');
        $expiry_date      = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $notes            = trim($_POST['notes'] ?? '');

        if ($import_detail_id > 0 && $quantity > 0) {
            try {
                $pdo->beginTransaction();

                $detStmt = $pdo->prepare("SELECT * FROM st_import_details WHERE import_detail_id = ? FOR UPDATE");
                $detStmt->execute([$import_detail_id]);
                $oldDet = $detStmt->fetch();

                if ($oldDet) {
                    $product_id = $oldDet['product_id'];

                    // Fetch Product
                    $pStmt = $pdo->prepare("SELECT * FROM st_products WHERE product_id = ? FOR UPDATE");
                    $pStmt->execute([$product_id]);
                    $product = $pStmt->fetch();

                    $unit_name = !empty($product['unit']) ? $product['unit'] : t('import_stock.default_unit', 'ອັນ');
                    $multiplier = 1;
                    $unitRowId  = 0;

                    if ($unit_key !== 'base') {
                        $uStmt = $pdo->prepare("SELECT unit_name, multiplier FROM st_product_units WHERE id = ? AND product_id = ?");
                        $uStmt->execute([intval($unit_key), $product_id]);
                        $puRow = $uStmt->fetch();
                        if ($puRow) {
                            $unit_name  = $puRow['unit_name'];
                            $multiplier = max(1, intval($puRow['multiplier']));
                            $unitRowId  = intval($unit_key);
                        }
                    }

                    $new_total_base_qty = $quantity * $multiplier;
                    $new_total_cost     = $quantity * $cost_price;

                    $diff_base_qty = $new_total_base_qty - $oldDet['total_base_qty'];
                    $diff_cost     = $new_total_cost - $oldDet['total_cost'];

                    // Update products stock + buy price, and the sell price when one was entered (ຕາມຫົວໜ່ວຍ — importApplyStockAndPrices)
                    importApplyStockAndPrices($pdo, $product_id, null, $unitRowId, $multiplier, $diff_base_qty, $cost_price, $sell_price);

                    // Update master import
                    $updImp = $pdo->prepare("UPDATE st_imports SET supplier_name = ?, total_cost = GREATEST(0, total_cost + ?), notes = IF(? != '', ?, notes) WHERE import_id = ?");
                    $updImp->execute([$supplier_name, $diff_cost, $notes, $notes, $oldDet['import_id']]);

                    // Update import_details
                    $updDet = $pdo->prepare("UPDATE st_import_details SET unit_name = ?, multiplier = ?, quantity = ?, total_base_qty = ?, cost_price = ?, sell_price = ?, total_cost = ?, expiry_date = ? WHERE import_detail_id = ?");
                    $updDet->execute([$unit_name, $multiplier, $quantity, $new_total_base_qty, $cost_price, ($sell_price > 0 ? $sell_price : null), $new_total_cost, $expiry_date, $import_detail_id]);

                    // Update product_batches
                    $updBatch = $pdo->prepare("UPDATE st_product_batches SET expiry_date = ?, initial_qty = ?, quantity = GREATEST(0, quantity + ?) WHERE import_detail_id = ?");
                    $updBatch->execute([$expiry_date, $new_total_base_qty, $diff_base_qty, $import_detail_id]);

                    $message = t('import_stock.msg_edit_success', 'ດັດແກ້ຂໍ້ມູນການຮັບສິນຄ້າເຂົ້າສຳເລັດ!');
                    $message_type = 'success';
                    logActivity($pdo, "ແກ້ໄຂການຮັບສິນຄ້າເຂົ້າ", "Detail ID: $import_detail_id");
                }

                $pdo->commit();
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $message = t('import_stock.err_prefix', 'ຜິດພາດ: ') . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

