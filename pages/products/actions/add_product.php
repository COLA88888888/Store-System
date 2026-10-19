<?php
/**
 * ສ່ວນ: ການກະທຳ: ເພີ່ມສິນຄ້າທີລະລາຍການ
 * ກວດຊື່/ປະເພດ/ບາໂຄ້ດຊ້ຳ, ອັບໂຫຼດຮູບ, ສ້າງລະຫັດສິນຄ້າ (ລະຫັດສາຂາ + ໝວດ + ລຳດັບ), ບັນທຶກສິນຄ້າ,
 * ວັນໝົດອາຍຸ ແລະ ຫົວໜ່ວຍເພີ່ມເຕີມ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/products/products.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

        $name        = trim($_POST['product_name'] ?? '');
        $barcode     = trim($_POST['barcode'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $bprice      = floatval(str_replace(',', '', $_POST['bprice'] ?? '0'));
        $price       = floatval(str_replace(',', '', $_POST['price'] ?? '0'));
        // unit_id is the real link into the units table; the plain-text `unit` column is
        // kept in sync from it so existing readers (POS, receipts, reports) still work.
        $unit_id     = intval($_POST['unit_id'] ?? 0) ?: null;
        $supplier_id = intval($_POST['supplier_id'] ?? 0) ?: null;
        $unit        = '';
        if ($unit_id !== null) {
            $unitNameStmt = $pdo->prepare("SELECT unit_name FROM st_units WHERE unit_id = ?");
            $unitNameStmt->execute([$unit_id]);
            $unit = (string)($unitNameStmt->fetchColumn() ?: '');
        }

        // Handle Image Upload
        $uploadError = null;
        $img_name = saveUploadedProductImage('product_img', $uploadError);
        if ($uploadError) {
            $message = $uploadError;
            $message_type = 'warning';
        }

        if ($name !== '' && $category_id > 0) {
            try {
                // ບາໂຄ້ດ ບໍ່ບັງຄັບ — ກວດແຕ່ເມື່ອປ້ອນມາ
                if (!empty($barcode) && !preg_match('/^[0-9]+$/', $barcode)) {
                    $message = 'ລະຫັດບາໂຄດ "' . htmlspecialchars($barcode) . '" ບໍ່ຖືກຕ້ອງ! ກະລຸນາປ້ອນແຕ່ຕົວເລກ (0-9) ເທົ່ານັ້ນ!';
                    $message_type = 'warning';
                } else {
                    $store_id = getActiveStoreId($pdo);
                    // Duplicate = same name + same category + same unit. The same name in the same
                    // category is allowed when the unit differs (e.g. ນ້ຳດື່ມ ຂວດ / ນ້ຳດື່ມ ແພັກ).
                    $nameChkStmt = $pdo->prepare("SELECT product_id FROM st_products WHERE product_name = ? AND category_id = ? AND unit_id <=> ? AND store_id = ? LIMIT 1");
                    $nameChkStmt->execute([$name, $category_id, $unit_id, $store_id]);
                    $dupName = $nameChkStmt->fetch();

                    if ($dupName) {
                        $message = 'ຊື່ສິນຄ້າ "' . htmlspecialchars($name) . '" ຫົວໜ່ວຍນີ້ ມີແລ້ວໃນໝວດໝູ່ດຽວກັນ (ID: ' . $dupName['product_id'] . ')';
                        $message_type = 'warning';
                    } elseif (!empty($barcode)) {
                        $chkStmt = $pdo->prepare("
                            SELECT p.product_name, p.product_id FROM st_products p WHERE p.barcode = ? AND p.store_id = ?
                            UNION ALL
                            SELECT p.product_name, u.product_id FROM st_product_units u JOIN st_products p ON u.product_id = p.product_id WHERE u.barcode = ? AND p.store_id = ?
                            LIMIT 1
                        ");
                        $chkStmt->execute([$barcode, $store_id, $barcode, $store_id]);
                        $dup = $chkStmt->fetch();
                        if ($dup) {
                            $message = 'ລະຫັດບາໂຄດ "' . htmlspecialchars($barcode) . '" ຖືກນຳໃຊ້ແລ້ວ ໃນສິນຄ້າ: "' . htmlspecialchars($dup['product_name']) . '" (ID: ' . $dup['product_id'] . ')';
                            $message_type = 'warning';
                        }
                    }
                }

                if (empty($message_type) || $message_type !== 'warning') {
                    // ສ້າງ ລະຫັດສິນຄ້າອໍໂຕ້: ລະຫັດສາຂາ + Category ID + Sequence (generateProductId)
                    // The form's preview code is kept only if it still has this branch's prefix and
                    // nobody took it meanwhile; otherwise a fresh one is generated.
                    $product_id = intval($_POST['product_id'] ?? 0);
                    $codePrefix = getProductCodePrefix($pdo, getActiveStoreId($pdo));
                    $takenChk = $pdo->prepare("SELECT 1 FROM st_products WHERE product_id = ? LIMIT 1");
                    $takenChk->execute([$product_id]);
                    $codePattern = '/^' . preg_quote($codePrefix . (intval($category_id) > 0 ? intval($category_id) : ''), '/') . '\d{4,}$/';
                    if ($product_id <= 0 || !preg_match($codePattern, (string)$product_id) || $takenChk->fetchColumn()) {
                        $product_id = generateProductId($pdo, $category_id, getActiveStoreId($pdo));
                    }

                    // Create the product in the current active branch only. `qty` is the
                    // starting stock (admin only; a non-admin always gets qty 0). The
                    // active-branch barcode duplicate check above already covers this store.
                    $activeStoreId = getActiveStoreId($pdo);
                    $qty = $isAdmin ? max(0, intval($_POST['qty'] ?? 0)) : 0;
                    // Deduct stock on sale? Unchecked = service / made-to-order item.
                    $cut_qty = isset($_POST['cut_qty']) ? 1 : 0;

                    $stmt      = $pdo->prepare("INSERT INTO st_products (product_id, product_name, barcode, category_id, bprice, price, unit, unit_id, supplier_id, img_url, qty, cut_qty, store_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $existsChk = $pdo->prepare("SELECT 1 FROM st_products WHERE product_id = ? AND store_id = ? LIMIT 1");

                    $existsChk->execute([$product_id, $activeStoreId]);
                    if (!$existsChk->fetchColumn()) {
                        $stmt->execute([$product_id, $name, $barcode, $category_id, $bprice, $price, $unit, $unit_id, $supplier_id, $img_name, $qty, $cut_qty, $activeStoreId]);
                    }

                    // ວັນໝົດອາຍຸ (ບໍ່ບັງຄັບ) — ວ່າງ = ບໍ່ຕິດຕາມ, ຈະບໍ່ແຈ້ງເຕືອນ
                    $expiry_date = normalizeExpiryDate($_POST['expiry_date'] ?? '');
                    try {
                        $pdo->prepare("UPDATE st_products SET expiry_date = ? WHERE product_id = ? AND store_id = ?")->execute([$expiry_date, $product_id, $activeStoreId]);
                    } catch (Throwable $e) {}

                    // ບັນທຶກຫຼາຍລາຄາ/ຫຼາຍຫົວໜ່ວຍ (product_units) ຖ້າມີ
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

                    $message = 'ເພີ່ມສິນຄ້າສຳເລັດ! (ລະຫັດ: ' . $product_id . ')';
                    $message_type = 'success';
                    logActivity($pdo, "ເພີ່ມສິນຄ້າ", "ID: $product_id, ຊື່: $name");
                }
            } catch (Exception $e) {
                $message = 'ຜິດພາດ: ' . $e->getMessage();
                $message_type = 'danger';
            }
        } else {
            $message = 'ກະລຸນາປ້ອນຊື່ ແລະ ເລືອກປະເພດສິນຄ້າ!';
            $message_type = 'danger';
        }
