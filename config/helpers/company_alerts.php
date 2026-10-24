<?php
/**
 * ສ່ວນ: ຟັງຊັນຊ່ວຍ: ຂໍ້ມູນຮ້ານ ແລະ ແຈ້ງເຕືອນ
 * ລຶບໂລໂກ້ເກົ່າ, ຂໍ້ມູນຮ້ານຕາມສາຂາ (getCompanyInfoForBranch), ແຈ້ງເຕືອນສິນຄ້າໃກ້ໝົດ,
 * ການໂອນສິນຄ້າເຂົ້າສາຂາ, ສິນຄ້າໃໝ່ ແລະ ການນຳເຂົ້າຂອງສາຂາຍ່ອຍ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

if (!function_exists('cleanupUnusedLogos')) {
    // ລົບໄຟລ໌ໂລໂກ້ເກົ່າ (assets/img/logo/logo_*) ທີ່ບໍ່ມີສາຂາໃດທີ່ຍັງມີຢູ່ໃຊ້ແລ້ວ — ເອີ້ນຫຼັງປ່ຽນໂລໂກ້
    // ແລະ ຫຼັງລົບສາຂາ, ເພື່ອບໍ່ໃຫ້ໄຟລ໌ເກົ່າຄ້າງໃນໂຟນເດີ. A file still used by any existing branch
    // (including one another branch shares) is kept; logo.png (the default) is never touched.
    function cleanupUnusedLogos($pdo) {
        $dir = dirname(__DIR__) . '/../assets/img/logo/';
        if (!$pdo || !is_dir($dir)) return 0;
        try {
            $used = [];
            $rows = $pdo->query("SELECT c.img_url FROM st_tbcompanyinfo c INNER JOIN st_tbstore s ON s.store_id = c.branch_id")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($rows as $u) {
                $u = basename(trim((string)$u));
                if ($u !== '') $used[strtolower($u)] = true;
            }
        } catch (Throwable $e) {
            return 0;   // can't tell what's in use — delete nothing
        }
        $removed = 0;
        foreach (glob($dir . 'logo_*.{png,jpg,jpeg,webp,PNG,JPG,JPEG,WEBP}', GLOB_BRACE) ?: [] as $file) {
            if (is_file($file) && !isset($used[strtolower(basename($file))]) && @unlink($file)) {
                $removed++;
            }
        }
        return $removed;
    }
}

if (!function_exists('getCompanyInfoForBranch')) {
    // Each branch owns its full profile (name/address/phone/tax/receipt-footer/logo/QR) on its
    // own tbcompanyinfo row. A branch that hasn't customised its own row yet inherits the main
    // branch's row for the fields that live ONLY there (tax/logo/receipt-footer/QR) — but
    // name/address/phone always come from THIS branch's own tbstore row instead, since the
    // main branch already enters those once, per branch, in Branch Management when the branch
    // is created; there's no reason to make the shop type them a second time here.
    function getCompanyInfoForBranch($pdo, $storeId) {
        if (!$pdo) {
            return [
                'com_id' => 1,
                'com_name_la' => 'Mini POS Store',
                'com_address' => '',
                'com_tel' => '',
                'barcode' => 'ຂອບໃຈທີ່ມາອຸດໜູນ, ໂອກາດໜ້າເຊີນໃໝ່!',
                'img_url' => 'logo.png',
                'vat_percent' => 0,
                'tax_type' => 'none'
            ];
        }
        $mainStoreId = intval($pdo->query("SELECT store_id FROM st_tbstore WHERE is_main = 1 ORDER BY store_id ASC LIMIT 1")->fetchColumn() ?: 1);
        $storeId = intval($storeId);

        $company = [];
        try {
            $stmt = $pdo->prepare("SELECT * FROM st_tbcompanyinfo WHERE branch_id = ? LIMIT 1");
            $stmt->execute([$storeId]);
            $ownRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            if (!empty($ownRow['com_name_la'])) {
                $company = $ownRow;
            }
        } catch (Throwable $e) {}

        // This branch hasn't entered its own profile yet — inherit the main branch's, so a
        // freshly added branch shows something sensible instead of a blank receipt.
        if (!$company && $storeId !== $mainStoreId) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM st_tbcompanyinfo WHERE branch_id = ? LIMIT 1");
                $stmt->execute([$mainStoreId]);
                $company = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {}
        }

        if (!$company) {
            try {
                // `tbstore.logo_path` doesn't exist on every install — only SELECT it when present.
                $stCols = $pdo->query("SHOW COLUMNS FROM `st_tbstore`")->fetchAll(PDO::FETCH_COLUMN);
                $logoExpr = in_array('logo_path', $stCols, true) ? '`logo_path`' : "'logo.png'";
                $stmt = $pdo->prepare("SELECT store_name as com_name_la, address as com_address, tel as com_tel, 'ຂອບໃຈທີ່ມາອຸດໜູນ, ໂອກາດໜ້າເຊີນໃໝ່!' as barcode, {$logoExpr} as img_url FROM st_tbstore WHERE store_id = ? LIMIT 1");
                $stmt->execute([$storeId]);
                $company = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) { $company = []; }
        }

        // Empty DB (fresh / restored / wiped): return a safe shape so callers never hit
        // "undefined array key" on every field.
        $company = array_merge([
            'com_name_la' => '', 'com_address' => '', 'com_tel' => '', 'com_email' => '',
            'tax_id' => '', 'barcode' => '', 'img_url' => 'logo.png', 'qr_img' => '',
            'vat_percent' => 0, 'tax_type' => 'none',
        ], $company);

        // This branch's own tbstore row always wins for name/address/phone — even when the
        // rest of $company above came from the main branch's tbcompanyinfo fallback.
        if (empty($ownRow['com_name_la'])) {
            try {
                $stmt = $pdo->prepare("SELECT store_name, address, tel FROM st_tbstore WHERE store_id = ? LIMIT 1");
                $stmt->execute([$storeId]);
                $ownStoreRow = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($ownStoreRow) {
                    $company['com_name_la'] = $ownStoreRow['store_name'];
                    if (empty($company['com_address'])) $company['com_address'] = $ownStoreRow['address'];
                    if (empty($company['com_tel']))     $company['com_tel']     = $ownStoreRow['tel'];
                }
            } catch (Throwable $e) {}
        }

        return $company;
    }
}

if (!function_exists('getLowStockAlerts')) {
    function getLowStockAlerts($pdo, $store_id = null) {
        if (!$pdo) return [];
        try {
            // cut_qty = 0 = service / made-to-order item that never deducts stock — its qty
            // column isn't meaningful inventory, so it must never trigger a low/out-of-stock alert.
            if ($store_id !== null && intval($store_id) > 0) {
                $sql = "SELECT p.*, s.store_name, s.is_main
                        FROM st_products p
                        JOIN st_tbstore s ON p.store_id = s.store_id
                        WHERE p.qty <= 10   -- ໃກ້ໝົດ = ເຫຼືອ 10 ລົງໄປ (ລວມໝົດແລ້ວ 0)
                          AND p.cut_qty != 0
                          AND s.status = 'active'
                          AND p.store_id = ?
                        ORDER BY p.qty ASC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([intval($store_id)]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $sql = "SELECT p.*, s.store_name, s.is_main
                        FROM st_products p
                        JOIN st_tbstore s ON p.store_id = s.store_id
                        WHERE p.qty <= 10   -- ໃກ້ໝົດ = ເຫຼືອ 10 ລົງໄປ (ລວມໝົດແລ້ວ 0)
                          AND p.cut_qty != 0
                          AND s.status = 'active'
                        ORDER BY s.is_main ASC, p.qty ASC";
                return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('getIncomingTransfersForStore')) {
    function getIncomingTransfersForStore($pdo, $store_id) {
        if (!$pdo || empty($store_id)) return [];
        try {
            $sql = "SELECT t.*, s.store_name as from_store_name, to_s.store_name as to_store_name
                    FROM st_stock_transfers t
                    LEFT JOIN st_tbstore s ON t.from_store_id = s.store_id
                    LEFT JOIN st_tbstore to_s ON t.to_store_id = to_s.store_id
                    WHERE (t.to_store_id = ? OR t.from_store_id = ?)
                      AND t.transfer_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY t.transfer_id DESC LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([intval($store_id), intval($store_id)]);
            $transfers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($transfers as &$t) {
                $trfId = intval($t['transfer_id']);
                $stmtDet = $pdo->prepare("
                    SELECT COALESCE(NULLIF(TRIM(d.product_name), ''), p.product_name, 'ສິນຄ້າ') as prod_name,
                           SUM(d.qty) as total_qty,
                           SUM(d.qty * d.bprice) as line_value
                    FROM st_stock_transfer_details d
                    LEFT JOIN st_products p ON d.product_id = p.product_id
                    WHERE d.transfer_id = ?
                    GROUP BY d.product_id, prod_name
                ");
                $stmtDet->execute([$trfId]);
                $details = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

                $summaryParts = [];
                $totalValue = 0;
                foreach ($details as $d) {
                    $summaryParts[] = $d['prod_name'] . ' x' . $d['total_qty'];
                    $totalValue += floatval($d['line_value']);
                }

                $t['total_items'] = count($details);
                $t['item_summary'] = implode(', ', $summaryParts);
                $t['total_value'] = $totalValue;
            }
            unset($t);

            return $transfers;
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('getNewProductsForStore')) {
    function getNewProductsForStore($pdo, $store_id) {
        if (!$pdo || empty($store_id)) return [];
        try {
            $sql = "SELECT p.*, s.store_name 
                    FROM st_products p 
                    JOIN st_tbstore s ON p.store_id = s.store_id 
                    WHERE p.store_id = ? AND p.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY p.product_id DESC LIMIT 10";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([intval($store_id)]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
}

if (!function_exists('getSubBranchStockImportsForMain')) {
    function getSubBranchStockImportsForMain($pdo) {
        if (!$pdo) return [];
        try {
            $sql = "SELECT id.import_detail_id, id.import_id, id.product_id, id.unit_name, id.quantity, id.cost_price, id.total_cost,
                           COALESCE(p.product_name, 'ສິນຄ້າ') as product_name, s.store_id, s.store_name, i.import_date, i.invoice_number, i.supplier_name, i.notes,
                           COALESCE(NULLIF(u.fname, ''), u.username, 'Admin') as creator_name
                    FROM st_import_details id
                    JOIN st_imports i ON id.import_id = i.import_id
                    JOIN st_tbstore s ON (
                        CASE 
                            WHEN i.store_id > 0 THEN i.store_id
                            ELSE (SELECT store_id FROM st_products WHERE product_id = id.product_id LIMIT 1)
                        END
                    ) = s.store_id
                    LEFT JOIN st_products p ON (id.product_id = p.product_id AND p.store_id = s.store_id)
                    LEFT JOIN st_tbuser u ON i.created_by = u.Id
                    WHERE (s.is_main = 0 OR s.store_id != 1)
                      AND i.import_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    ORDER BY id.import_detail_id DESC LIMIT 20";
            $stmt = $pdo->query($sql);
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Exception $e) {
            return [];
        }
    }
}

