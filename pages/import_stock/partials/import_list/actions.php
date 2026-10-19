<?php
/**
 * ສ່ວນ: ການກະທຳ POST
 * ແກ້ໄຂລາຍການນຳເຂົ້າ, ແກ້ໄຂຫົວບິນ, ລົບລາຍການ/ບິນ ພ້ອມປັບສະຕັອກໃຫ້ຖືກຕ້ອງ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/import_stock/import_list.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// ====== HANDLE POST ACTIONS ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    // --- 1. ACTION: DELETE SINGLE IMPORT DETAIL ---
    if ($_POST['action'] === 'delete_import_detail') {
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
                        throw new Exception(t('import_list.err_has_movement', 'ບໍ່ສາມາດລົບ/ຍົກເລີກໃບບິນນີ້ໄດ້ ເນື່ອງຈາກສິນຄ້ານີ້ມີການເຄື່ອນໄຫວ ຫຼື ຖືກຂາຍອອກໄປແລ້ວ!'));
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

                    $message = t('import_list.msg_cancel_success', 'ຍົກເລີກການຮັບສິນຄ້າເຂົ້າສຳເລັດ (ປັບສະຕັອກຄືນແລ້ວ)!');
                    $message_type = 'success';
                    logActivity($pdo, "ຍົກເລີກການຮັບສິນຄ້າເຂົ້າ", "Detail ID: $import_detail_id");
                }

                $pdo->commit();
            } catch (Exception $e) {
                // MySQL can auto-rollback the whole transaction itself (deadlock, lock-wait
                // timeout, etc.) before we get here — calling rollBack() on an already-gone
                // transaction throws "There is no active transaction" and crashes the page
                // with an uncaught fatal, masking the real error. Guard it.
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $message = $e->getMessage();
                $message_type = 'danger';
            }
        }
    }

    // --- 2. ACTION: DELETE ENTIRE MASTER IMPORT BILL ---
    elseif ($_POST['action'] === 'delete_master_import') {
        $import_id = intval($_POST['import_id'] ?? 0);
        if ($import_id > 0) {
            try {
                $pdo->beginTransaction();

                $detStmt = $pdo->prepare("
                    SELECT id.*, p.qty AS current_product_qty, pb.initial_qty AS batch_initial_qty, pb.quantity AS batch_rem_qty
                    FROM st_import_details id
                    JOIN st_products p ON id.product_id = p.product_id
                    LEFT JOIN st_product_batches pb ON id.import_detail_id = pb.import_detail_id
                    WHERE id.import_id = ? FOR UPDATE
                ");
                $detStmt->execute([$import_id]);
                $details = $detStmt->fetchAll();

                foreach ($details as $det) {
                    $bRem = isset($det['batch_rem_qty']) ? intval($det['batch_rem_qty']) : null;
                    $bInit = isset($det['batch_initial_qty']) ? intval($det['batch_initial_qty']) : null;
                    $pQty = intval($det['current_product_qty']);
                    $baseQty = intval($det['total_base_qty']);

                    if (($bRem !== null && $bInit !== null && $bRem < $bInit) || ($pQty < $baseQty)) {
                        throw new Exception(t('import_list.err_bill_has_movement', 'ບໍ່ສາມາດລົບໃບບິນນີ້ໄດ້ ເນື່ອງຈາກມີສິນຄ້າໃນໃບບິນນີ້ຖືກຂາຍ ຫຼື ເຄື່ອນໄຫວແລ້ວ!'));
                    }
                }

                foreach ($details as $det) {
                    $pdo->prepare("UPDATE st_products SET qty = GREATEST(0, qty - ?) WHERE product_id = ?")
                        ->execute([$det['total_base_qty'], $det['product_id']]);
                    $pdo->prepare("DELETE FROM st_product_batches WHERE import_detail_id = ?")
                        ->execute([$det['import_detail_id']]);
                }

                $pdo->prepare("DELETE FROM st_import_details WHERE import_id = ?")->execute([$import_id]);
                $pdo->prepare("DELETE FROM st_imports WHERE import_id = ?")->execute([$import_id]);

                $message = t('import_list.msg_delete_bill_success', 'ລົບໃບບິນຮັບເຂົ້າສິນຄ້າສຳເລັດ (ປັບສະຕັອກຄືນແລ້ວ)!');
                $message_type = 'success';
                logActivity($pdo, "ລົບໃບບິນຮັບເຂົ້າສິນຄ້າ", "Import ID: $import_id");

                $pdo->commit();
            } catch (Exception $e) {
                // MySQL can auto-rollback the whole transaction itself (deadlock, lock-wait
                // timeout, etc.) before we get here — calling rollBack() on an already-gone
                // transaction throws "There is no active transaction" and crashes the page
                // with an uncaught fatal, masking the real error. Guard it.
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
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

                    $unit_name = !empty($product['unit']) ? $product['unit'] : t('import_list.default_unit', 'ອັນ');
                    $multiplier = 1;

                    if ($unit_key !== 'base') {
                        $uStmt = $pdo->prepare("SELECT unit_name, multiplier FROM st_product_units WHERE id = ? AND product_id = ?");
                        $uStmt->execute([intval($unit_key), $product_id]);
                        $puRow = $uStmt->fetch();
                        if ($puRow) {
                            $unit_name  = $puRow['unit_name'];
                            $multiplier = max(1, intval($puRow['multiplier']));
                        }
                    }

                    $new_total_base_qty = $quantity * $multiplier;
                    $new_total_cost     = $quantity * $cost_price;

                    $diff_base_qty = $new_total_base_qty - $oldDet['total_base_qty'];
                    $diff_cost     = $new_total_cost - $oldDet['total_cost'];

                    // Update products stock
                    $updStock = $pdo->prepare("UPDATE st_products SET qty = GREATEST(0, qty + ?), bprice = ? WHERE product_id = ?");
                    $updStock->execute([$diff_base_qty, $cost_price, $product_id]);

                    // Update master import
                    $updImp = $pdo->prepare("UPDATE st_imports SET supplier_name = ?, total_cost = GREATEST(0, total_cost + ?), notes = IF(? != '', ?, notes) WHERE import_id = ?");
                    $updImp->execute([$supplier_name, $diff_cost, $notes, $notes, $oldDet['import_id']]);

                    // Update import_details
                    $updDet = $pdo->prepare("UPDATE st_import_details SET unit_name = ?, multiplier = ?, quantity = ?, total_base_qty = ?, cost_price = ?, total_cost = ?, expiry_date = ? WHERE import_detail_id = ?");
                    $updDet->execute([$unit_name, $multiplier, $quantity, $new_total_base_qty, $cost_price, $new_total_cost, $expiry_date, $import_detail_id]);

                    // Update product_batches
                    $updBatch = $pdo->prepare("UPDATE st_product_batches SET expiry_date = ?, initial_qty = ?, quantity = GREATEST(0, quantity + ?) WHERE import_detail_id = ?");
                    $updBatch->execute([$expiry_date, $new_total_base_qty, $diff_base_qty, $import_detail_id]);

                    $message = t('import_list.msg_edit_detail_success', 'ດັດແກ້ຂໍ້ມູນການຮັບສິນຄ້າເຂົ້າສຳເລັດ!');
                    $message_type = 'success';
                    logActivity($pdo, "ແກ້ໄຂການຮັບສິນຄ້າເຂົ້າ", "Detail ID: $import_detail_id");
                }

                $pdo->commit();
            } catch (Exception $e) {
                // MySQL can auto-rollback the whole transaction itself (deadlock, lock-wait
                // timeout, etc.) before we get here — calling rollBack() on an already-gone
                // transaction throws "There is no active transaction" and crashes the page
                // with an uncaught fatal, masking the real error. Guard it.
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $message = t('import_list.err_prefix', 'ຜິດພາດ: ') . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }

    // --- 4. ACTION: EDIT MASTER IMPORT BILL (SUPPLIER, DATE, NOTES) ---
    elseif ($_POST['action'] === 'edit_master_import') {
        $import_id     = intval($_POST['import_id'] ?? 0);
        $supplier_name = trim($_POST['supplier_name'] ?? '');
        $raw_date      = trim($_POST['import_date'] ?? '');
        $import_date   = !empty($raw_date) ? $raw_date . ' ' . date('H:i:s') : date('Y-m-d H:i:s');
        $notes         = trim($_POST['notes'] ?? '');

        if ($import_id > 0) {
            try {
                $updMaster = $pdo->prepare("UPDATE st_imports SET supplier_name = ?, import_date = ?, notes = ? WHERE import_id = ?");
                $updMaster->execute([$supplier_name, $import_date, $notes, $import_id]);

                $message = t('import_list.msg_edit_master_success', 'ດັດແກ້ຂໍ້ມູນໃບບິນຮັບເຂົ້າສຳເລັດ!');
                $message_type = 'success';
                logActivity($pdo, "ແກ້ໄຂຂໍ້ມູນໃບບິນຮັບເຂົ້າ", "Import ID: $import_id");
            } catch (Exception $e) {
                $message = t('import_list.err_prefix', 'ຜິດພາດ: ') . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

