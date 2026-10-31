<?php
/**
 * ສ່ວນ: ການກະທຳ: ແກ້ໄຂສິນຄ້າ
 * ອັບເດດຂໍ້ມູນສິນຄ້າ, ປ່ຽນ/ລຶບຮູບ, ວັນໝົດອາຍຸ ແລະ ຫົວໜ່ວຍເພີ່ມເຕີມ ພ້ອມກວດບາໂຄ້ດຊ້ຳ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/products/products.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

        $product_id  = intval($_POST['product_id'] ?? 0);
        $name        = trim($_POST['product_name'] ?? '');
        $barcode     = trim($_POST['barcode'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $bprice      = floatval(str_replace(',', '', $_POST['bprice'] ?? '0'));
        $price       = floatval(str_replace(',', '', $_POST['price'] ?? '0'));
        // unit_id is the real link into the units table; the plain-text `unit` column is
        // kept in sync from it so existing readers (POS, receipts, reports) still work.
        $unit_id     = intval($_POST['unit_id'] ?? 0) ?: null;
        $supplier_id = intval($_POST['supplier_id'] ?? 0) ?: null;
        $zone        = trim($_POST['zone'] ?? '') ?: null;
        $shelf       = trim($_POST['shelf'] ?? '') ?: null;
        $unit        = '';
        if ($unit_id !== null) {
            $unitNameStmt = $pdo->prepare("SELECT unit_name FROM st_units WHERE unit_id = ?");
            $unitNameStmt->execute([$unit_id]);
            $unit = (string)($unitNameStmt->fetchColumn() ?: '');
        }
        $remove_img  = intval($_POST['remove_img'] ?? 0);

        if ($product_id > 0 && $name !== '' && $category_id > 0) {
            try {
                // ບາໂຄ້ດ ບໍ່ບັງຄັບ — ກວດແຕ່ເມື່ອປ້ອນມາ
                if (!empty($barcode) && !preg_match('/^[0-9]+$/', $barcode)) {
                    $message = (tl('ລະຫັດບາໂຄດ') . ' "') . htmlspecialchars($barcode) . ('" ' . tl('ບໍ່ຖືກຕ້ອງ! ກະລຸນາປ້ອນແຕ່ຕົວເລກ (0-9) ເທົ່ານັ້ນ!'));
                    $message_type = 'warning';
                } else {
                    $store_id = getActiveStoreId($pdo);
                    // Duplicate = same name + category + unit (same name with another unit is allowed)
                    $nameChkStmt = $pdo->prepare("SELECT product_id FROM st_products WHERE product_name = ? AND category_id = ? AND unit_id <=> ? AND product_id != ? AND store_id = ? LIMIT 1");
                    $nameChkStmt->execute([$name, $category_id, $unit_id, $product_id, $store_id]);
                    $dupName = $nameChkStmt->fetch();

                    if ($dupName) {
                        $message = (tl('ຊື່ສິນຄ້າ') . ' "') . htmlspecialchars($name) . ('" ' . tl('ຫົວໜ່ວຍນີ້ ມີແລ້ວໃນໝວດໝູ່ດຽວກັນ (ID:') . ' ') . $dupName['product_id'] . ')';
                        $message_type = 'warning';
                    } elseif (!empty($barcode)) {
                        $chkStmt = $pdo->prepare("
                            SELECT p.product_name, p.product_id FROM st_products p WHERE p.barcode = ? AND p.product_id != ? AND p.store_id = ?
                            UNION ALL
                            SELECT p.product_name, u.product_id FROM st_product_units u JOIN st_products p ON u.product_id = p.product_id WHERE u.barcode = ? AND u.product_id != ? AND p.store_id = ?
                            LIMIT 1
                        ");
                        $chkStmt->execute([$barcode, $product_id, $store_id, $barcode, $product_id, $store_id]);
                        $dup = $chkStmt->fetch();
                        if ($dup) {
                            $message = (tl('ລະຫັດບາໂຄດ') . ' "') . htmlspecialchars($barcode) . ('" ' . tl('ຖືກນຳໃຊ້ແລ້ວ ໃນສິນຄ້າ:') . ' "') . htmlspecialchars($dup['product_name']) . '" (ID: ' . $dup['product_id'] . ')';
                            $message_type = 'warning';
                        }
                    }
                }

                if (empty($message_type) || $message_type !== 'warning') {
                    // Get current image
                    $curStmt = $pdo->prepare("SELECT img_url FROM st_products WHERE product_id = ?");
                    $curStmt->execute([$product_id]);
                    $currentImg = $curStmt->fetchColumn();
                    $img_name = $currentImg;

                    // If user chose to remove image
                    if ($remove_img === 1) {
                        deleteProductImageFile($currentImg);
                        $img_name = null;
                    }

                    // If new image uploaded
                    $uploadError = null;
                    $uploadedImg = saveUploadedProductImage('product_img', $uploadError);
                    if ($uploadError) {
                        $message = $uploadError;
                        $message_type = 'warning';
                    }
                    if ($uploadedImg !== null) {
                        if (!empty($currentImg)) {
                            deleteProductImageFile($currentImg);
                        }
                        $img_name = $uploadedImg;
                    }

                    $userStoreId = intval($_SESSION['store_id'] ?? 1);
                    $isMain = isMainBranch($pdo, $userStoreId);
                    $canAllBranches = canViewAllBranches($pdo);
                    if ($isAdmin && isset($_POST['qty'])) {
                        $qty = max(0, intval($_POST['qty']));
                        if ($canAllBranches) {
                            $stmt = $pdo->prepare("UPDATE st_products SET product_name=?, barcode=?, category_id=?, bprice=?, price=?, unit=?, unit_id=?, img_url=?, qty=? WHERE product_id=?");
                            $stmt->execute([$name, $barcode, $category_id, $bprice, $price, $unit, $unit_id, $img_name, $qty, $product_id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE st_products SET product_name=?, barcode=?, category_id=?, bprice=?, price=?, unit=?, unit_id=?, img_url=?, qty=? WHERE product_id=? AND store_id=?");
                            $stmt->execute([$name, $barcode, $category_id, $bprice, $price, $unit, $unit_id, $img_name, $qty, $product_id, $userStoreId]);
                        }
                    } else {
                        if ($canAllBranches) {
                            $stmt = $pdo->prepare("UPDATE st_products SET product_name=?, barcode=?, category_id=?, bprice=?, price=?, unit=?, unit_id=?, img_url=? WHERE product_id=?");
                            $stmt->execute([$name, $barcode, $category_id, $bprice, $price, $unit, $unit_id, $img_name, $product_id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE st_products SET product_name=?, barcode=?, category_id=?, bprice=?, price=?, unit=?, unit_id=?, img_url=? WHERE product_id=? AND store_id=?");
                            $stmt->execute([$name, $barcode, $category_id, $bprice, $price, $unit, $unit_id, $img_name, $product_id, $userStoreId]);
                        }
                    }

                    // Deduct-stock-on-sale flag — separate UPDATE so all the variants above stay simple.
                    $cut_qty = isset($_POST['cut_qty']) ? 1 : 0;
                    if ($canAllBranches) {
                        $pdo->prepare("UPDATE st_products SET cut_qty=? WHERE product_id=?")->execute([$cut_qty, $product_id]);
                    } else {
                        $pdo->prepare("UPDATE st_products SET cut_qty=? WHERE product_id=? AND store_id=?")->execute([$cut_qty, $product_id, $userStoreId]);
                    }

                    // ວັນໝົດອາຍຸ (ບໍ່ບັງຄັບ) — ສະເພາະສາຂານີ້ ເພາະແຕ່ລະສາຂາມີລັອດສິນຄ້າຂອງຕົນເອງ
                    try {
                        $pdo->prepare("UPDATE st_products SET expiry_date=? WHERE product_id=? AND store_id=?")->execute([normalizeExpiryDate($_POST['expiry_date'] ?? ''), $product_id, ($canAllBranches && intval($_POST['expiry_store_id'] ?? 0) > 0) ? intval($_POST['expiry_store_id']) : $userStoreId]);
                    } catch (Throwable $e) {}

                    // ຜູ້ສະໜອງ — ອັບເດດແຍກ ເພື່ອບໍ່ຕ້ອງແກ້ SQL UPDATE ທັງ 4 ຕົວຂ້າງເທິງ
                    try {
                        if ($canAllBranches) {
                            $pdo->prepare("UPDATE st_products SET supplier_id=? WHERE product_id=?")->execute([$supplier_id, $product_id]);
                        } else {
                            $pdo->prepare("UPDATE st_products SET supplier_id=? WHERE product_id=? AND store_id=?")->execute([$supplier_id, $product_id, $userStoreId]);
                        }
                    } catch (Throwable $e) {}

                    // ໂຊນ ແລະ ຊັ້ນວາງ
                    try {
                        if ($canAllBranches) {
                            $pdo->prepare("UPDATE st_products SET zone=?, shelf=? WHERE product_id=?")->execute([$zone, $shelf, $product_id]);
                        } else {
                            $pdo->prepare("UPDATE st_products SET zone=?, shelf=? WHERE product_id=? AND store_id=?")->execute([$zone, $shelf, $product_id, $userStoreId]);
                        }
                    } catch (Throwable $e) {}

                    // ອັບເດດຫຼາຍລາຄາ/ຫຼາຍຫົວໜ່ວຍ (product_units)
                    $delUnitsStmt = $pdo->prepare("DELETE FROM st_product_units WHERE product_id = ?");
                    $delUnitsStmt->execute([$product_id]);

                    if (isset($_POST['extra_unit_name']) && is_array($_POST['extra_unit_name'])) {
                        $insertUnitStmt = $pdo->prepare("INSERT INTO st_product_units (product_id, unit_name, multiplier, bprice, price, barcode) VALUES (?, ?, ?, ?, ?, ?)");
                        for ($i = 0; $i < count($_POST['extra_unit_name']); $i++) {
                            $uName = trim($_POST['extra_unit_name'][$i] ?? '');
                            // Quantity is optional in the form — a blank/0 multiplier defaults to
                            // 1 (same quantity as the base unit) instead of silently dropping the row.
                            $uMult = intval($_POST['extra_unit_multiplier'][$i] ?? 1);
                            if ($uMult <= 0) $uMult = 1;
                            $uBprice = floatval(str_replace(',', '', $_POST['extra_unit_bprice'][$i] ?? '0'));
                            $uPrice = floatval(str_replace(',', '', $_POST['extra_unit_price'][$i] ?? '0'));
                            $uBarcode = trim($_POST['extra_unit_barcode'][$i] ?? '');
                            if ($uName !== '' && $uPrice > 0) {
                                $insertUnitStmt->execute([$product_id, $uName, $uMult, $uBprice, $uPrice, $uBarcode]);
                            }
                        }
                    }

                    $message = tl('ແກ້ໄຂສິນຄ້າສຳເລັດ!');
                    $message_type = 'success';
                    logActivity($pdo, "ແກ້ໄຂສິນຄ້າ", "ID: $product_id, ຊື່: $name");
                }
            } catch (Exception $e) {
                $message = (tl('ຜິດພາດ:') . ' ') . $e->getMessage();
                $message_type = 'danger';
            }
        }
