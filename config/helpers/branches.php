<?php
/**
 * ສ່ວນ: ຟັງຊັນຊ່ວຍ: ສາຂາ ແລະ ລະຫັດສິນຄ້າ
 * isMainBranch, canViewAllBranches (ໃຜເບິ່ງທຸກສາຂາໄດ້), getProductCodePrefix / generateProductId
 * (ລະຫັດສິນຄ້າ = ລະຫັດສາຂາ + ໝວດ + ລຳດັບ), getPosStoreId (ສາຂາຂອງຜູ້ໃຊ້ສຳລັບໜ້າຂາຍ),
 * getActiveStoreId (ສາຂາທີ່ກຳລັງເບິ່ງ).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// Global Branch Store Helper Functions
if (!function_exists('isMainBranch')) {
    function isMainBranch($pdo, $store_id = null) {
        if ($store_id === null) {
            $store_id = intval($_SESSION['store_id'] ?? 1);
        }
        $isAdmin = (
            ($_SESSION['status'] ?? '') === 'ຜູ້ບໍລິຫານ' ||
            strtolower($_SESSION['status'] ?? '') === 'admin' ||
            ($_SESSION['user_id'] ?? 0) == 1
        );
        if ($isAdmin && $store_id == 1) {
            return true;
        }
        if (!$pdo) return ($store_id == 1);
        try {
            $stmt = $pdo->prepare("SELECT is_main FROM st_tbstore WHERE store_id = ? LIMIT 1");
            $stmt->execute([$store_id]);
            $isMain = $stmt->fetchColumn();
            return (bool)$isMain;
        } catch (Exception $e) {
            return ($store_id == 1);
        }
    }
}

if (!function_exists('canViewAllBranches')) {
    // Seeing or switching between every branch is decided by the user's own branch, not their
    // role: the owner account (Id 1) and any user assigned to the main branch see every branch;
    // any user assigned to a sub-branch (admin or staff) is locked to their own store_id.
    function canViewAllBranches($pdo) {
        if (intval($_SESSION['user_id'] ?? 0) === 1) {
            return true;
        }
        if (!$pdo) {
            return false;
        }
        static $mainByStore = [];
        $storeId = intval($_SESSION['store_id'] ?? 0);
        if (!array_key_exists($storeId, $mainByStore)) {
            try {
                $stmt = $pdo->prepare("SELECT is_main FROM st_tbstore WHERE store_id = ? LIMIT 1");
                $stmt->execute([$storeId]);
                $mainByStore[$storeId] = (bool)$stmt->fetchColumn();
            } catch (Throwable $e) {
                $mainByStore[$storeId] = false;
            }
        }
        return $mainByStore[$storeId];
    }
}

// New product code = branch code + category id + 4-digit running number, e.g. branch "1",
// category 1, #0005 → 110005 — even for a shop with a single branch. Existing products keep
// their codes (sales history points at them). The number is unique across all branches.
if (!function_exists('getProductCodePrefix')) {
    // The branch code (digits only) that starts every new product code of that branch.
    function getProductCodePrefix($pdo, $storeId) {
        $prefix = '';
        try {
            $sc = $pdo->prepare("SELECT store_code FROM st_tbstore WHERE store_id = ? LIMIT 1");
            $sc->execute([intval($storeId)]);
            $prefix = preg_replace('/\D+/', '', (string)$sc->fetchColumn());
        } catch (Throwable $e) {}
        return $prefix !== '' ? $prefix : (string)intval($storeId);
    }
}
if (!function_exists('generateProductId')) {
    function generateProductId($pdo, $categoryId, $storeId) {
        $prefix = getProductCodePrefix($pdo, $storeId);
        $cat = intval($categoryId) > 0 ? (string)intval($categoryId) : '0';
        $basePrefix = $prefix . $cat;

        $stmt = $pdo->prepare("SELECT product_id FROM st_products WHERE store_id = ? AND category_id = ?");
        $stmt->execute([intval($storeId), intval($categoryId)]);
        $existingIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $usedSeqs = [];
        foreach ($existingIds as $pid) {
            $strPid = (string)$pid;
            if (strpos($strPid, $basePrefix) === 0 && strlen($strPid) >= strlen($basePrefix) + 4) {
                $seqPart = substr($strPid, strlen($basePrefix));
                if (ctype_digit($seqPart)) {
                    $usedSeqs[(int)$seqPart] = true;
                }
            }
        }

        $seq = 1;
        while (isset($usedSeqs[$seq])) {
            $seq++;
        }

        $chkGlobal = $pdo->prepare("SELECT 1 FROM st_products WHERE product_id = ? LIMIT 1");
        for ($guard = 0; $guard < 100000; $guard++, $seq++) {
            $candidate = intval($basePrefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT));
            $chkGlobal->execute([$candidate]);
            if (!$chkGlobal->fetchColumn()) {
                return $candidate;
            }
        }
        return intval($basePrefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT));
    }
}

if (!function_exists('formatProductIdColored')) {
    /**
     * ຈັດຮູບແບບລະຫັດສິນຄ້າ:
     * - ລະຫັດສາຂາ: ສີແດງ (#dc2626)
     * - ລະຫັດປະເພດສິນຄ້າ: ສີຟ້າ (#2563eb)
     * - ລະຫັດສິນຄ້າ (4 ຫຼັກ): ສີດຳ (#0f172a)
     */
    function formatProductIdColored($productId, $categoryId = null, $storeCode = null) {
        $fullCode = trim((string)$productId);
        if ($fullCode === '') return '';
        if (strlen($fullCode) < 5) {
            return '<span class="product-code-badge" style="font-family:\'Courier New\',Courier,monospace; font-weight:700;"><span style="color:#0f172a;">' . htmlspecialchars($fullCode) . '</span></span>';
        }
        $seq = substr($fullCode, -4);
        $prefix = substr($fullCode, 0, -4);
        $sc = preg_replace('/\D+/', '', (string)$storeCode);
        $cat = ($categoryId !== null && intval($categoryId) > 0) ? (string)intval($categoryId) : '';

        if ($sc !== '' && strpos($prefix, $sc) === 0) {
            $branch = $sc;
            $category = substr($prefix, strlen($sc));
        } elseif ($cat !== '' && substr($prefix, -strlen($cat)) === $cat) {
            $category = $cat;
            $branch = substr($prefix, 0, -strlen($cat));
        } else {
            $branch = substr($prefix, 0, 1);
            $category = substr($prefix, 1);
        }

        return '<span class="product-code-badge" style="font-family:\'Courier New\',Courier,monospace; font-weight:700; letter-spacing:0.5px;">' .
               '<span style="color:#dc2626;" title="ລະຫັດສາຂາ">' . htmlspecialchars($branch) . '</span>' .
               '<span style="color:#2563eb;" title="ລະຫັດປະເພດສິນຄ້າ">' . htmlspecialchars($category) . '</span>' .
               '<span style="color:#0f172a;" title="ລະຫັດສິນຄ້າ">' . htmlspecialchars($seq) . '</span>' .
               '</span>';
    }
}

// The POS always sells from the logged-in user's OWN branch (tbuser.store_id, refreshed into the
// session on every request) — switching the viewed branch with ?switch_store_id only changes
// reports / management pages, never which branch's stock, prices, banks and bills the till uses.
if (!function_exists('getPosStoreId')) {
    function getPosStoreId() {
        $sid = intval($_SESSION['store_id'] ?? 1);
        return $sid > 0 ? $sid : 1;
    }
}

if (!function_exists('getActiveStoreId')) {
    function getActiveStoreId($pdo) {
        $userStoreId = intval($_SESSION['store_id'] ?? 1);
        if (!canViewAllBranches($pdo)) {
            return $userStoreId;
        }

        // May see every branch: honour a branch picked via the switcher, if any
        if (isset($_SESSION['active_store_id']) && $_SESSION['active_store_id'] !== '') {
            return intval($_SESSION['active_store_id']);
        }
        return $userStoreId;
    }
}


// ລະຫັດອໍໂຕ້ແຍກຕາມສາຂາ: "<ລະຫັດສາຂາ>-<ລຳດັບໃນສາຂາ>" — ສາຂາ 1 = 1-1, 1-2 …, ສາຂາ 2 = 2-1 …
// ໃຊ້ກັບ ໝວດໝູ່, ຫົວໜ່ວຍ, ລູກຄ້າ, ຜູ້ສະໜອງ, ຜູ້ໃຊ້, ທະນາຄານ, ໂປຣໂມຊັ່ນ ແລະ ສິນຄ້າ.
// ລຳດັບເກັບໃນຖັນ branch_no (migration v16/v17 ໃນ config/migrations/schema_core.php). ລະຫັດພາຍໃນ
// (category_id, product_id, …) ບໍ່ປ່ຽນ — ການຂາຍ, ບາໂຄ້ດ ແລະ ຂໍ້ມູນເກົ່າ ຍັງຊີ້ໄປຫາມັນຄືເກົ່າ.
if (!function_exists('branchNoTables')) {
    // table => [ຖັນ id (PK), ຖັນສາຂາ]
    function branchNoTables() {
        return [
            'st_categories'    => ['category_id', 'store_id'],
            'st_units'         => ['unit_id', 'store_id'],
            'st_customers'     => ['customer_id', 'store_id'],
            'st_suppliers'     => ['supplier_id', 'store_id'],
            'st_tbuser'        => ['Id', 'store_id'],
            'st_bank_accounts' => ['id', 'store_id'],
            'st_promotions'    => ['id', 'branch_id'],
            'st_products'      => ['code1', 'store_id'],
        ];
    }
}
if (!function_exists('ensureBranchNos')) {
    // ໃສ່ລຳດັບໃຫ້ແຖວທີ່ຍັງບໍ່ມີ (ຂໍ້ມູນເກົ່າ, ແຖວທີ່ສ້າງຈາກບ່ອນອື່ນ) — ຕາມລຳດັບ id ພາຍໃນແຕ່ລະສາຂາ
    function ensureBranchNos($pdo, $table) {
        $tables = branchNoTables();
        if (!isset($tables[$table])) return;
        list($idCol, $sCol) = $tables[$table];
        try {
            $rows = $pdo->query("SELECT `$idCol` AS id, `$sCol` AS sid FROM `$table` WHERE branch_no IS NULL ORDER BY `$idCol` ASC")->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) return;
            $max = $pdo->query("SELECT `$sCol`, COALESCE(MAX(branch_no), 0) AS m FROM `$table` GROUP BY `$sCol`")->fetchAll(PDO::FETCH_KEY_PAIR);
            $upd = $pdo->prepare("UPDATE `$table` SET branch_no = ? WHERE `$idCol` = ? AND branch_no IS NULL");
            foreach ($rows as $r) {
                $sid = (int)$r['sid'];
                $max[$sid] = (int)($max[$sid] ?? 0) + 1;
                $upd->execute([$max[$sid], (int)$r['id']]);
            }
        } catch (Throwable $e) {}
    }
}
if (!function_exists('nextBranchNo')) {
    function nextBranchNo($pdo, $table, $storeId) {
        $tables = branchNoTables();
        if (!isset($tables[$table])) return 1;
        $sCol = $tables[$table][1];
        try {
            $st = $pdo->prepare("SELECT COALESCE(MAX(branch_no), 0) + 1 FROM `$table` WHERE `$sCol` = ?");
            $st->execute([intval($storeId)]);
            return max(1, (int)$st->fetchColumn());
        } catch (Throwable $e) {
            return 1;
        }
    }
}
if (!function_exists('branchCodeLabel')) {
    // "1-3": ລະຫັດສາຂາ (store_code) + ລຳດັບ. ບໍ່ມີລຳດັບ → ຄືນ $fallback (ລະຫັດພາຍໃນເກົ່າ)
    function branchCodeLabel($pdo, $storeId, $branchNo, $fallback = '') {
        static $codes = null;
        if ($codes === null) {
            $codes = [];
            try { $codes = $pdo->query("SELECT store_id, store_code FROM st_tbstore")->fetchAll(PDO::FETCH_KEY_PAIR); } catch (Throwable $e) {}
        }
        if ($branchNo === null || $branchNo === '' || (int)$branchNo < 1) return (string)$fallback;
        $sc = trim((string)($codes[(int)$storeId] ?? ''));
        if ($sc === '') $sc = (string)intval($storeId);
        return $sc . '-' . (int)$branchNo;
    }
}
if (!function_exists('branchCodeHtml')) {
    // HTML ຂອງລະຫັດ "1-3" ໃຫ້ລະຫັດສາຂາ (ສ່ວນໜ້າ "-") ເປັນສີແດງ — ໃຊ້ໃນຕາຕະລາງໝວດໝູ່ / ຫົວໜ່ວຍ
    function branchCodeHtml($code) {
        $code = (string)$code;
        // ລະຫັດລູກຄ້າ "CUST-<ລະຫັດສາຂາ>-<ເລກ>" — ສະເພາະລະຫັດສາຂາເປັນສີແດງ
        if (preg_match('/^(CUST-)([0-9A-Za-z]+)(-[0-9]+)$/i', $code, $c)) {
            return htmlspecialchars($c[1]) . '<span class="branch-code-prefix" style="color:#dc2626;font-weight:800;">' . htmlspecialchars($c[2]) . '</span>'
                 . htmlspecialchars($c[3]);
        }
        // ສະເພາະຮູບແບບ "<ລະຫັດສາຂາ>-<ເລກ>" — ລະຫັດອື່ນ ສະແດງຕາມເດີມ
        if (!preg_match('/^([0-9A-Za-z]+)(-[0-9]+)$/', $code, $m)) {
            return htmlspecialchars($code);
        }
        return '<span class="branch-code-prefix" style="color:#dc2626;font-weight:800;">' . htmlspecialchars($m[1]) . '</span>'
             . htmlspecialchars($m[2]);
    }
}
if (!function_exists('attachBranchCodes')) {
    // ໃສ່ 'display_code' ("1-3") ໃຫ້ທຸກແຖວຂອງລາຍການ — ໃຊ້ຫຼັງ ensureBranchNos() ໃນ backend ຂອງແຕ່ລະໜ້າ
    function attachBranchCodes($pdo, array &$rows, $table) {
        $tables = branchNoTables();
        if (!isset($tables[$table])) return;
        list($idCol, $sCol) = $tables[$table];
        foreach ($rows as &$row) {
            $row['display_code'] = branchCodeLabel($pdo, $row[$sCol] ?? 0, $row['branch_no'] ?? null, $row[$idCol] ?? '');
        }
        unset($row);
    }
}
if (!function_exists('branchStoreCode')) {
    // ລະຫັດສາຂາ (store_code) ຂອງ store_id — ສ່ວນໜ້າ "-" ຂອງລະຫັດອໍໂຕ້ (ໃຊ້ສ້າງຕົວຢ່າງລະຫັດໃນ JS)
    function branchStoreCode($pdo, $storeId) {
        $label = branchCodeLabel($pdo, $storeId, 1);
        return substr($label, 0, strrpos($label, '-'));
    }
}
if (!function_exists('customerCodeLabel')) {
    // ລະຫັດລູກຄ້າ "CUST-<ລະຫັດສາຂາ>-<ລຳດັບ 4 ຫຼັກ>" — ສາຂາ 1 = CUST-1-0001, ສາຂາ 2 = CUST-2-0001
    function customerCodeLabel($pdo, $storeId, $branchNo) {
        return 'CUST-' . branchStoreCode($pdo, $storeId) . '-' . str_pad((string)max(1, (int)$branchNo), 4, '0', STR_PAD_LEFT);
    }
}
