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

