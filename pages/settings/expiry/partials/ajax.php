<?php
/**
 * ສ່ວນ: AJAX ຂອງ popup ກຳນົດວັນໝົດອາຍຸ
 * ຄົ້ນຫາສິນຄ້າດ້ວຍບາໂຄ້ດ/ຊື່ (ແບ່ງໜ້າ 30 ລາຍການ), ບັນທຶກ ແລະ ລຶບວັນໝົດອາຍຸ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ pages/settings/expiry/index.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// ===== AJAX for the "set expiry date" modal =====
// The product is chosen by scanning its barcode (or typing the barcode / product code) — the
// modal never loads the product list, so it stays fast with thousands of products.
// ex_ajax=barcode: exact barcode (or product code) -> one product of this branch.
$exAjax = $_GET['ex_ajax'] ?? '';
// Name shown in the picker — name + unit only; the barcode is still searchable but not displayed
$exText = function ($r) {
    return $r['product_name'] . (!empty($r['unit']) ? ' (' . $r['unit'] . ')' : '');
};
if ($exAjax === 'barcode') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$canSetExpiry) {
        echo json_encode(['results' => [], 'found' => false]);
        exit();
    }
    try {
        $code = trim((string)($_GET['code'] ?? ''));
        $row = false;
        if ($code !== '') {
            $st = $pdo->prepare("SELECT product_id, product_name, unit, barcode, expiry_date FROM st_products
                WHERE store_id = ? AND (barcode = ? OR CAST(product_id AS CHAR) = ?) ORDER BY (barcode = ?) DESC LIMIT 1");
            $st->execute([$activeStoreId, $code, $code, $code]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
        }
        echo json_encode($row
            ? ['found' => true, 'id' => (int)$row['product_id'], 'text' => $exText($row), 'expiry' => $row['expiry_date'] ?? '']
            : ['found' => false], JSON_UNESCAPED_UNICODE);
        exit();
    } catch (Throwable $e) {
        echo json_encode(['results' => [], 'found' => false]);
    }
    exit();
}
// ex_ajax=search: Select2 search by name / barcode / product code — for products without a barcode.
// Paged 30 at a time so it stays fast with thousands of products.
if ($exAjax === 'search') {
    header('Content-Type: application/json; charset=utf-8');
    if (!$canSetExpiry) {
        echo json_encode(['results' => []]);
        exit();
    }
    try {
        $q = trim((string)($_GET['q'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 30;
        $sql = "SELECT product_id, product_name, unit, barcode, expiry_date FROM st_products WHERE store_id = ?";
        $params = [$activeStoreId];
        if ($q !== '') {
            $sql .= " AND (product_name LIKE ? OR barcode LIKE ? OR CAST(product_id AS CHAR) LIKE ?)";
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        $sql .= " ORDER BY product_name ASC LIMIT " . ($limit + 1) . " OFFSET " . (($page - 1) * $limit);
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        $more = count($rows) > $limit;
        $results = [];
        foreach (array_slice($rows, 0, $limit) as $r) {
            $results[] = ['id' => (int)$r['product_id'], 'text' => $exText($r), 'expiry' => $r['expiry_date'] ?? '', 'barcode' => (string)($r['barcode'] ?? '')];
        }
        echo json_encode(['results' => $results, 'pagination' => ['more' => $more]], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        echo json_encode(['results' => []]);
    }
    exit();
}

$postAction = ($_SERVER['REQUEST_METHOD'] === 'POST') ? ($_POST['action'] ?? '') : '';

try {
    if ($postAction === 'set_expiry' || $postAction === 'clear_expiry') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $expDate   = $postAction === 'set_expiry' ? normalizeExpiryDate($_POST['expiry_date'] ?? '') : null;
        $pStmt = $pdo->prepare("SELECT product_name, expiry_date FROM st_products WHERE product_id = ? AND store_id = ? LIMIT 1");
        $pStmt->execute([$productId, $activeStoreId]);
        $pRow  = $pStmt->fetch(PDO::FETCH_ASSOC);
        $pName = $pRow ? $pRow['product_name'] : false;

        // Which switch this request needs: setting a date on an untracked product is "add",
        // changing an existing date is "edit", clearing it is "delete".
        if ($postAction === 'clear_expiry') {
            $allowed = $canDelExpiry;
        } else {
            $allowed = empty($pRow['expiry_date']) ? $canAddExpiry : $canEditExpiry;
        }

        if (!$allowed) {
            $message = t('expiry.err_no_permission', 'ທ່ານບໍ່ມີສິດແກ້ໄຂການຕັ້ງຄ່ານີ້!');
            $message_type = 'warning';
        } elseif ($pName === false) {
            $message = t('expiry.err_product', 'ກະລຸນາເລືອກສິນຄ້າ');
            $message_type = 'warning';
        } elseif ($postAction === 'set_expiry' && $expDate === null) {
            $message = t('expiry.err_date', 'ກະລຸນາເລືອກວັນໝົດອາຍຸ');
            $message_type = 'warning';
        } else {
            $pdo->prepare("UPDATE st_products SET expiry_date = ? WHERE product_id = ? AND store_id = ?")
                ->execute([$expDate, $productId, $activeStoreId]);
            if ($expDate !== null) {
                $message = t('expiry.msg_set', 'ບັນທຶກວັນໝົດອາຍຸສຳເລັດ!');
                logActivity($pdo, 'ກຳນົດວັນໝົດອາຍຸສິນຄ້າ', "ສິນຄ້າ: $pName, ວັນໝົດອາຍຸ: " . date('d/m/Y', strtotime($expDate)));
            } else {
                $message = t('expiry.msg_cleared', 'ຍົກເລີກການຕິດຕາມວັນໝົດອາຍຸແລ້ວ');
                logActivity($pdo, 'ຍົກເລີກວັນໝົດອາຍຸສິນຄ້າ', "ສິນຄ້າ: $pName");
            }
            $message_type = 'success';
        }
    }
} catch (Exception $e) {
    $message = t('expiry.msg_error_prefix', 'ຜິດພາດ: ') . $e->getMessage();
    $message_type = 'danger';
}

$alertDays = getExpiryAlertDays($pdo);

