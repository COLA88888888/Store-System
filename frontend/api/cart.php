<?php
/**
 * API ກະຕ່າສິນຄ້າ (JSON) — ເອີ້ນຈາກ assets/js/cart.js.
 *   GET                         → {success, count} (ບໍ່ມີຍອດເງິນ: ເວັບບໍ່ເປີດເຜີຍລາຄາ)
 *   POST action=add  id, qty    → ເພີ່ມສິນຄ້າ (ກວດສະຕັອກ)
 *   POST action=set  id, qty    → ຕັ້ງຈຳນວນ (qty 0 = ລຶບ)
 *   POST action=remove id       → ລຶບອອກ
 *   POST action=clear           → ລ້າງກະຕ່າ
 * ທຸກ POST ຕ້ອງມີ CSRF token (header X-CSRF-Token). ສະເພາະສະມາຊິກ — ລູກຄ້າທົ່ວໄປ → 401 {need_member: true}
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

$summary = function () use ($pdo, $shopStoreId) {
    $cart = cartDetails($pdo, $shopStoreId);
    return ['count' => $cart['qty']]; // ບໍ່ສົ່ງຍອດເງິນ — ໃສ່ສິນຄ້າ 1 ຊິ້ນ ກໍ່ຈະຮູ້ລາຄາໄດ້
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['success' => true] + $summary());
}
if (!currentMember($pdo)) {   // ກະຕ່າສະເພາະສະມາຊິກ — ລູກຄ້າທົ່ວໄປ → cart.js ເປີດ popup ລົງທະບຽນ
    jsonOut(['success' => false, 'need_member' => true, 'count' => 0, 'message' => t('ກະລຸນາລົງທະບຽນ ຫຼື ເຂົ້າສູ່ລະບົບ ກ່ອນໃຊ້ກະຕ່າສິນຄ້າ — ສະມາຊິກເຫັນລາຄາ ແລະ ສັ່ງຊື້ໄດ້ເລີຍ')], 401);
}
if (!checkCsrf()) {
    jsonOut(['success' => false, 'message' => t('ໝົດເວລາ ກະລຸນາໂຫຼດໜ້າໃໝ່')], 419);
}
if (!$pdo) {
    jsonOut(['success' => false, 'message' => t('ລະບົບບໍ່ພ້ອມໃຊ້ງານ')], 503);
}

$action = (string)($_POST['action'] ?? '');
$idRaw = $_POST['id'] ?? 0;
$id = is_numeric($idRaw) ? (int)$idRaw : trim((string)$idRaw);
$qty = max(1, (int)($_POST['qty'] ?? 1));

if (in_array($action, ['add', 'set'], true)) {
    $product = null;
    if (is_numeric($id) && (int)$id > 0 && $pdo) {
        $product = getProduct($pdo, $shopStoreId, (int)$id);
    }
    if (!$product && function_exists('hardwareFallback')) {
        $hwFallback = hardwareFallback();
        if (isset($hwFallback[$id])) {
            $hw = $hwFallback[$id];
            $product = [
                'product_id'   => 0,
                'item_slug'    => $id,
                'product_name' => $hw['card']['title'],
                'price'        => 0.0,
                'unit'         => t('ອັນ'),
                'qty'          => 999,
                'cut_qty'      => 0,
            ];
        }
    }

    if (!$product || !productInStock($product)) {
        jsonOut(['success' => false, 'message' => t('ສິນຄ້ານີ້ໝົດ ຫຼື ບໍ່ມີຂາຍແລ້ວ')] + $summary());
    }
    $current = cartItems()[$id] ?? 0;
    $wanted = $action === 'add' ? $current + max(1, $qty) : $qty;
    $max = productMaxQty($product);
    if ($wanted > $max) {
        cartSet($id, $max);
        jsonOut(['success' => false, 'message' => t('ມີສິນຄ້າພຽງ %d %s', $max, $product['unit'] ?: t('ອັນ'))] + $summary());
    }
    cartSet($id, $wanted);
    jsonOut(['success' => true, 'message' => $action === 'add' ? t('ເພີ່ມ "%s" ໃສ່ກະຕ່າແລ້ວ', $product['product_name']) : t('ອັບເດດແລ້ວ')] + $summary());
}
if ($action === 'remove') {
    cartRemove($id);
    jsonOut(['success' => true, 'message' => t('ລຶບອອກແລ້ວ')] + $summary());
}
if ($action === 'clear') {
    cartClear();
    jsonOut(['success' => true, 'message' => t('ລ້າງກະຕ່າແລ້ວ')] + $summary());
}
jsonOut(['success' => false, 'message' => t('ຄຳສັ່ງບໍ່ຖືກຕ້ອງ')], 400);
