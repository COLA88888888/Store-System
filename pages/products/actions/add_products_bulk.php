<?php
/**
 * ສ່ວນ: ການກະທຳ: ເພີ່ມສິນຄ້າຫຼາຍລາຍການພ້ອມກັນ
 * ຮັບແຖວເປັນ JSON, ໃຊ້ຜູ້ສະໜອງບັອກດຽວກັບທັງຊຸດ, ຮູບພາບລາຍແຖວທີ່ອັບໂຫຼດໄວ້ກ່ອນ,
 * ກວດແຖວທີ່ບໍ່ຄົບ/ບາໂຄ້ດຊ້ຳ ແລ້ວບັນທຶກທີ່ເຫຼືອ; ລຶບຮູບຂອງແຖວທີ່ຖືກຂ້າມ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/products/products.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

        $bCats   = $_POST['b_category_id'] ?? [];
        $bNames  = $_POST['b_name'] ?? [];
        $bBars   = $_POST['b_barcode'] ?? [];
        $bUnits  = $_POST['b_unit_id'] ?? [];
        $bBprice = $_POST['b_bprice'] ?? [];
        $bPrice  = $_POST['b_price'] ?? [];
        $bSupplierId = $_POST['b_supplier_id'] ?? [];
        $bZones  = $_POST['b_zone'] ?? [];
        $bShelves = $_POST['b_shelf'] ?? [];
        $bExpiry = $_POST['b_expiry'] ?? [];
        $bImgs   = [];

        // ຟອມສົ່ງແຖວມາເປັນ JSON (bulk_json) ເພື່ອບໍ່ໃຫ້ຊົນ max_input_vars ເວລານຳເຂົ້າຈາກ Excel ຫຼາຍແຖວ
        $bExtraUnits = [];
        if (isset($_POST['bulk_json'])) {
            $bulkRows = json_decode((string)$_POST['bulk_json'], true);
            $bCats = $bNames = $bBars = $bUnits = $bBprice = $bPrice = $bSupplierId = $bZones = $bShelves = $bImgs = $bExpiry = $bExtraUnits = [];
            foreach (is_array($bulkRows) ? $bulkRows : [] as $br) {
                if (!is_array($br)) continue;
                $bCats[]       = (string)($br['category_id'] ?? '');
                $bNames[]      = (string)($br['name'] ?? '');
                $bBars[]       = (string)($br['barcode'] ?? '');
                $bUnits[]      = (string)($br['unit_id'] ?? '');
                $bBprice[]     = (string)($br['bprice'] ?? '');
                $bPrice[]      = (string)($br['price'] ?? '');
                $bSupplierId[] = (string)($br['supplier_id'] ?? '');
                $bZones[]      = (string)($br['zone'] ?? '');
                $bShelves[]    = (string)($br['shelf'] ?? '');
                $bImgs[]       = (string)($br['img'] ?? '');
                $bExpiry[]     = (string)($br['expiry'] ?? '');
                $bExtraUnits[] = isset($br['extra_units']) && is_array($br['extra_units']) ? $br['extra_units'] : [];
            }
        }

        // ຕັ້ງຄ່າທັງຊຸດ (Batch Header): ຜູ້ສະໜອງ, ໂຊນ, ຊັ້ນວາງ
        $batchSupplierId = intval($_POST['bulk_supplier_id'] ?? 0) ?: null;
        $batchZone       = trim($_POST['bulk_zone'] ?? '') ?: null;
        $batchShelf      = trim($_POST['bulk_shelf'] ?? '') ?: null;

        // ຮູບພາບລາຍແຖວ (ບໍ່ບັງຄັບ) ຖືກອັບໂຫຼດໄວ້ກ່ອນແລ້ວຜ່ານ api/products_api.php?action=upload_image —
        // ຮັບສະເພາະຊື່ໄຟລ໌ທີ່ລະບົບສ້າງ ແລະ ມີຢູ່ຈິງໃນ assets/product_img ເທົ່ານັ້ນ.
        $bulkImg = function ($name) {
            $name = basename(trim((string)$name));
            if (!preg_match('/^prod_\d+_\d+\.(jpg|jpeg|png|webp|gif|bmp|jfif|avif)$/i', $name)) return null;
            return is_file(dirname(__DIR__) . '/../../assets/product_img/' . $name) ? $name : null;
        };

        if (!is_array($bNames) || count($bNames) === 0) {
            $message = tl('ບໍ່ພົບຂໍ້ມູນລາຍການສິນຄ້າ!');
            $message_type = 'danger';
        } else {
            $activeStoreId = getActiveStoreId($pdo);
            $added = 0; $skipped = 0; $errors = [];
            $batchBarcodes = [];
            // One switch applies to the WHOLE batch — either every row in this bulk-add
            // deducts stock on sale, or none of them do. "bulk_cut_qty" is checked by default
            // (= deduct), like the single-add form's switch; unchecked = services / made-to-order.
            $batchCutQty = isset($_POST['bulk_cut_qty']) ? 1 : 0;

            // unit_id -> unit_name (keeps the plain-text `unit` column in sync, like single-add)
            $unitNames = [];
            try {
                foreach ($pdo->query("SELECT unit_id, unit_name FROM st_units")->fetchAll(PDO::FETCH_ASSOC) as $ur) {
                    $unitNames[(int)$ur['unit_id']] = (string)$ur['unit_name'];
                }
            } catch (Throwable $e) {}

            $insStmt   = $pdo->prepare("INSERT INTO st_products (product_id, product_name, barcode, category_id, bprice, price, unit, unit_id, supplier_id, img_url, qty, store_id, cut_qty, zone, shelf, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?)");
            $insertUnitStmt = $pdo->prepare("INSERT INTO st_product_units (product_id, unit_name, multiplier, bprice, price, barcode) VALUES (?, ?, ?, ?, ?, ?)");
            $dupStmt   = $pdo->prepare("
                SELECT p.product_name FROM st_products p WHERE p.barcode = ? AND p.store_id = ?
                UNION ALL
                SELECT p.product_name FROM st_product_units u JOIN st_products p ON u.product_id = p.product_id WHERE u.barcode = ? AND p.store_id = ?
                LIMIT 1
            ");
            // Same name + category is fine when the unit differs (see single-add above)
            $dupNameStmt = $pdo->prepare("SELECT product_id FROM st_products WHERE product_name = ? AND category_id = ? AND unit_id <=> ? AND store_id = ? LIMIT 1");

            $insertedImgs = [];
            $rowCount = count($bNames);
            for ($i = 0; $i < $rowCount; $i++) {
                $name        = trim($bNames[$i] ?? '');
                $category_id = intval($bCats[$i] ?? 0);
                $barcode     = trim($bBars[$i] ?? '');
                $unit_id     = intval($bUnits[$i] ?? 0) ?: null;
                $bprice      = floatval(str_replace(',', '', $bBprice[$i] ?? '0'));
                $price       = floatval(str_replace(',', '', $bPrice[$i] ?? '0'));
                $supplier_id = $batchSupplierId ?: (intval($bSupplierId[$i] ?? 0) ?: null);
                $img_name    = $bulkImg($bImgs[$i] ?? '');

                // Fully-blank row -> skip without complaint
                if ($name === '' && $category_id <= 0 && $barcode === '' && $unit_id === null && $bprice <= 0 && $price <= 0) {
                    continue;
                }

                $label = $name !== '' ? $name : ('#' . ($i + 1));
                
                // Check duplicate name + unit in same category
                $dupNameStmt->execute([$name, $category_id, $unit_id, $activeStoreId]);
                if ($dupNameStmt->fetch()) {
                    $errors[] = $label . (': ' . tl('ຊື່ສິນຄ້າ ຫົວໜ່ວຍນີ້ ມີແລ້ວໃນໝວດໝູ່ດຽວກັນ'));
                    $skipped++;
                    continue;
                }

                // ລາຄາຊື້ (bprice) ບໍ່ບັງຄັບ — ວ່າງ = 0. ບາໂຄ້ດກໍ່ບໍ່ບັງຄັບຄືກັນ (ຄືກັບເພີ່ມທີລະລາຍການ),
                // ແຕ່ຖ້າໃສ່ມາຕ້ອງເປັນຕົວເລກ ແລະ ບໍ່ຊ້ຳກັບລາຍການອື່ນ.
                if ($name === '' || $category_id <= 0 || $unit_id === null || $price <= 0) {
                    $errors[] = $label . (': ' . tl('ຂໍ້ມູນບໍ່ຄົບ'));
                    $skipped++;
                    continue;
                }
                if ($barcode !== '') {
                    if (!preg_match('/^[0-9]+$/', $barcode)) {
                        $errors[] = $label . (': ' . tl('ບາໂຄ້ດບໍ່ຖືກຕ້ອງ'));
                        $skipped++;
                        continue;
                    }
                    if (isset($batchBarcodes[$barcode])) {
                        $errors[] = $label . (': ' . tl('ບາໂຄ້ດ') . ' "') . $barcode . ('" ' . tl('ຊ້ຳໃນຟອມ'));
                        $skipped++;
                        continue;
                    }
                    $dupStmt->execute([$barcode, $activeStoreId, $barcode, $activeStoreId]);
                    $dupName = $dupStmt->fetchColumn();
                    if ($dupName !== false) {
                        $errors[] = $label . (': ' . tl('ບາໂຄ້ດ') . ' "') . $barcode . ('" ' . tl('ມີແລ້ວ') . ' (') . $dupName . ')';
                        $skipped++;
                        continue;
                    }
                }

                // ກວດສອບບາໂຄ້ດຂອງຫົວໜ່ວຍເພີ່ມເຕີມ (ແພັກ, ແກັດ...) ຖ້າມີ
                $rowExtraUnits = $bExtraUnits[$i] ?? [];
                $hasUnitError = false;
                if (!empty($rowExtraUnits) && is_array($rowExtraUnits)) {
                    foreach ($rowExtraUnits as $eu) {
                        $euBc = trim($eu['barcode'] ?? '');
                        if ($euBc !== '') {
                            if (!preg_match('/^[0-9]+$/', $euBc)) {
                                $errors[] = $label . ' (' . ($eu['unit_name'] ?? '') . '): ' . tl('ບາໂຄ້ດບໍ່ຖືກຕ້ອງ');
                                $hasUnitError = true;
                                break;
                            }
                            if (isset($batchBarcodes[$euBc])) {
                                $errors[] = $label . ' (' . ($eu['unit_name'] ?? '') . '): ' . tl('ບາໂຄ້ດ') . ' "' . $euBc . '" ' . tl('ຊ້ຳໃນຟອມ');
                                $hasUnitError = true;
                                break;
                            }
                            $dupStmt->execute([$euBc, $activeStoreId, $euBc, $activeStoreId]);
                            $dupName = $dupStmt->fetchColumn();
                            if ($dupName !== false) {
                                $errors[] = $label . ' (' . ($eu['unit_name'] ?? '') . '): ' . tl('ບາໂຄ້ດ') . ' "' . $euBc . '" ' . tl('ມີແລ້ວ') . ' (' . $dupName . ')';
                                $hasUnitError = true;
                                break;
                            }
                        }
                    }
                }
                if ($hasUnitError) {
                    $skipped++;
                    continue;
                }

                // product_id = branch code + Category ID + running 4-digit sequence, re-counted
                // each row so rows inserted earlier in this same batch are taken into account.
                $product_id = generateProductId($pdo, $category_id, $activeStoreId);

                $unitName = $unit_id !== null ? ($unitNames[$unit_id] ?? '') : '';
                $zone     = trim($bZones[$i] ?? '') ?: $batchZone;
                $shelf    = trim($bShelves[$i] ?? '') ?: $batchShelf;

                try {
                    $expiry   = normalizeExpiryDate($bExpiry[$i] ?? '');   // ວ່າງ / ຜິດຮູບແບບ = null
                    $insStmt->execute([$product_id, $name, $barcode, $category_id, $bprice, $price, $unitName, $unit_id, $supplier_id, $img_name, $activeStoreId, $batchCutQty, $zone, $shelf, $expiry]);
                    if ($barcode !== '') {
                        $batchBarcodes[$barcode] = true;
                    }
                    if ($img_name) $insertedImgs[$img_name] = true;

                    // ບັນທຶກຫົວໜ່ວຍເພີ່ມເຕີມ (ເຊັ່ນ: ແພັກ, ແກັດ)
                    if (!empty($rowExtraUnits) && is_array($rowExtraUnits)) {
                        foreach ($rowExtraUnits as $eu) {
                            $uName = trim($eu['unit_name'] ?? '');
                            $uMult = intval($eu['multiplier'] ?? 1);
                            if ($uMult <= 0) $uMult = 1;
                            $uBprice = floatval(str_replace(',', '', $eu['bprice'] ?? '0'));
                            $uPrice = floatval(str_replace(',', '', $eu['price'] ?? '0'));
                            $uBarcode = trim($eu['barcode'] ?? '');
                            if ($uName !== '' && $uPrice > 0) {
                                $insertUnitStmt->execute([$product_id, $uName, $uMult, $uBprice, $uPrice, $uBarcode]);
                                if ($uBarcode !== '') {
                                    $batchBarcodes[$uBarcode] = true;
                                }
                            }
                        }
                    }

                    $added++;
                } catch (Throwable $e) {
                    $errors[] = $label . ': ' . $e->getMessage();
                    $skipped++;
                }
            }

            // Images uploaded for rows that were skipped / blank never got a product — remove them.
            foreach ($bImgs as $bi) {
                $bi = $bulkImg($bi);
                if ($bi && !isset($insertedImgs[$bi])) deleteProductImageFile($bi);
            }

            if ($added > 0) {
                $message = (tl('ເພີ່ມສິນຄ້າສຳເລັດ') . ' ') . $added . (' ' . tl('ລາຍການ'))
                         . ($skipped > 0 ? ((' — ' . tl('ຂ້າມ') . ' ') . $skipped . (' ' . tl('ລາຍການ:') . ' ') . implode('; ', array_slice($errors, 0, 5))) : '');
                $message_type = $skipped > 0 ? 'warning' : 'success';
                logActivity($pdo, "ເພີ່ມສິນຄ້າຫຼາຍລາຍການ", "ສຳເລັດ: $added, ຂ້າມ: $skipped");
            } else {
                $message = $skipped > 0
                    ? ((tl('ບໍ່ສາມາດເພີ່ມໄດ້:') . ' ') . implode('; ', array_slice($errors, 0, 6)))
                    : tl('ກະລຸນາປ້ອນຢ່າງໜ້ອຍ 1 ລາຍການ!');
                $message_type = 'danger';
            }
        }
