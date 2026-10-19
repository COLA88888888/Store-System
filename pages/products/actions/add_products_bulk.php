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
        $bImgs   = [];

        // ຟອມສົ່ງແຖວມາເປັນ JSON (bulk_json) ເພື່ອບໍ່ໃຫ້ຊົນ max_input_vars ເວລານຳເຂົ້າຈາກ Excel ຫຼາຍແຖວ
        if (isset($_POST['bulk_json'])) {
            $bulkRows = json_decode((string)$_POST['bulk_json'], true);
            $bCats = $bNames = $bBars = $bUnits = $bBprice = $bPrice = $bSupplierId = $bImgs = [];
            foreach (is_array($bulkRows) ? $bulkRows : [] as $br) {
                if (!is_array($br)) continue;
                $bCats[]       = (string)($br['category_id'] ?? '');
                $bNames[]      = (string)($br['name'] ?? '');
                $bBars[]       = (string)($br['barcode'] ?? '');
                $bUnits[]      = (string)($br['unit_id'] ?? '');
                $bBprice[]     = (string)($br['bprice'] ?? '');
                $bPrice[]      = (string)($br['price'] ?? '');
                $bSupplierId[] = (string)($br['supplier_id'] ?? '');
                $bImgs[]       = (string)($br['img'] ?? '');
            }
        }

        // ຜູ້ສະໜອງ ບັອກດຽວ ສຳລັບທັງຊຸດ — ເລືອກແລ້ວໃຊ້ກັບທຸກລາຍການ (ທັບຄ່າລາຍແຖວຈາກ Excel)
        $batchSupplierId = intval($_POST['bulk_supplier_id'] ?? 0) ?: null;

        // ຮູບພາບລາຍແຖວ (ບໍ່ບັງຄັບ) ຖືກອັບໂຫຼດໄວ້ກ່ອນແລ້ວຜ່ານ api_product.php?action=upload_image —
        // ຮັບສະເພາະຊື່ໄຟລ໌ທີ່ລະບົບສ້າງ ແລະ ມີຢູ່ຈິງໃນ assets/product_img ເທົ່ານັ້ນ.
        $bulkImg = function ($name) {
            $name = basename(trim((string)$name));
            if (!preg_match('/^prod_\d+_\d+\.(jpg|jpeg|png|webp|gif|bmp|jfif|avif)$/i', $name)) return null;
            return is_file(dirname(__DIR__) . '/../../assets/product_img/' . $name) ? $name : null;
        };

        if (!is_array($bNames) || count($bNames) === 0) {
            $message = 'ບໍ່ພົບຂໍ້ມູນລາຍການສິນຄ້າ!';
            $message_type = 'danger';
        } else {
            $activeStoreId = getActiveStoreId($pdo);
            $added = 0; $skipped = 0; $errors = [];
            $batchBarcodes = [];
            // One switch applies to the WHOLE batch — either every row in this bulk-add
            // deducts stock on sale, or none of them do. The form's checkbox is inverted
            // ("bulk_no_cut_qty": checked = do NOT deduct), unchecked by default.
            $batchCutQty = isset($_POST['bulk_no_cut_qty']) ? 0 : 1;

            // unit_id -> unit_name (keeps the plain-text `unit` column in sync, like single-add)
            $unitNames = [];
            try {
                foreach ($pdo->query("SELECT unit_id, unit_name FROM st_units")->fetchAll(PDO::FETCH_ASSOC) as $ur) {
                    $unitNames[(int)$ur['unit_id']] = (string)$ur['unit_name'];
                }
            } catch (Throwable $e) {}

            $insStmt   = $pdo->prepare("INSERT INTO st_products (product_id, product_name, barcode, category_id, bprice, price, unit, unit_id, supplier_id, img_url, qty, store_id, cut_qty) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)");
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
                    $errors[] = $label . ': ຊື່ສິນຄ້າ ຫົວໜ່ວຍນີ້ ມີແລ້ວໃນໝວດໝູ່ດຽວກັນ';
                    $skipped++;
                    continue;
                }

                // ລາຄາຊື້ (bprice) ບໍ່ບັງຄັບ — ວ່າງ = 0. ບາໂຄ້ດກໍ່ບໍ່ບັງຄັບຄືກັນ (ຄືກັບເພີ່ມທີລະລາຍການ),
                // ແຕ່ຖ້າໃສ່ມາຕ້ອງເປັນຕົວເລກ ແລະ ບໍ່ຊ້ຳກັບລາຍການອື່ນ.
                if ($name === '' || $category_id <= 0 || $unit_id === null || $price <= 0) {
                    $errors[] = $label . ': ຂໍ້ມູນບໍ່ຄົບ';
                    $skipped++;
                    continue;
                }
                if ($barcode !== '') {
                    if (!preg_match('/^[0-9]+$/', $barcode)) {
                        $errors[] = $label . ': ບາໂຄ້ດບໍ່ຖືກຕ້ອງ';
                        $skipped++;
                        continue;
                    }
                    if (isset($batchBarcodes[$barcode])) {
                        $errors[] = $label . ': ບາໂຄ້ດ "' . $barcode . '" ຊ້ຳໃນຟອມ';
                        $skipped++;
                        continue;
                    }
                    $dupStmt->execute([$barcode, $activeStoreId, $barcode, $activeStoreId]);
                    $dupName = $dupStmt->fetchColumn();
                    if ($dupName !== false) {
                        $errors[] = $label . ': ບາໂຄ້ດ "' . $barcode . '" ມີແລ້ວ (' . $dupName . ')';
                        $skipped++;
                        continue;
                    }
                }

                // product_id = branch code + Category ID + running 4-digit sequence, re-counted
                // each row so rows inserted earlier in this same batch are taken into account.
                $product_id = generateProductId($pdo, $category_id, $activeStoreId);

                $unitName = $unit_id !== null ? ($unitNames[$unit_id] ?? '') : '';

                try {
                    $insStmt->execute([$product_id, $name, $barcode, $category_id, $bprice, $price, $unitName, $unit_id, $supplier_id, $img_name, $activeStoreId, $batchCutQty]);
                    if ($barcode !== '') {
                        $batchBarcodes[$barcode] = true;
                    }
                    if ($img_name) $insertedImgs[$img_name] = true;
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
                $message = 'ເພີ່ມສິນຄ້າສຳເລັດ ' . $added . ' ລາຍການ'
                         . ($skipped > 0 ? (' — ຂ້າມ ' . $skipped . ' ລາຍການ: ' . implode('; ', array_slice($errors, 0, 5))) : '');
                $message_type = $skipped > 0 ? 'warning' : 'success';
                logActivity($pdo, "ເພີ່ມສິນຄ້າຫຼາຍລາຍການ", "ສຳເລັດ: $added, ຂ້າມ: $skipped");
            } else {
                $message = $skipped > 0
                    ? ('ບໍ່ສາມາດເພີ່ມໄດ້: ' . implode('; ', array_slice($errors, 0, 6)))
                    : 'ກະລຸນາປ້ອນຢ່າງໜ້ອຍ 1 ລາຍການ!';
                $message_type = 'danger';
            }
        }
