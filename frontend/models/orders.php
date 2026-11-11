<?php
/**
 * ບັນທຶກຄຳສັ່ງຊື້ຈາກເວັບໄຊ ລົງ w_web_orders + w_web_order_items (ສ້າງໂດຍ backend/config/migrations/schema_website.php).
 * ຮອງຮັບ:
 *   - ຂໍ້ມູນທີ່ຢູ່ຈັດສົ່ງລະອຽດ (ແຂວງ, ເມືອງ, ບ້ານ, ລາຍລະອຽດ)
 *   - ບໍລິສັດຂົນສົ່ງໃນລາວ (ພ້ອມໂລໂກ້) + ສາຂາປາຍທາງ
 *   - ບັນຊີທະນາຄານຈາກລະບົບຫຼັງບ້ານ (w_bank_accounts ທີ່ is_active = 1)
 *   - ອັບໂຫຼດຮູບສະລິບການໂອນເງິນ (ເກັບໄວ້ backend/assets/web_slips/ ໃຫ້ພະນັກງານຫຼັງບ້ານກວດສອບ ແລະ ຈັດສົ່ງເຄື່ອງ)
 */

function getLaoProvincesAndDistricts(): array
{
    return [
        t('ນະຄອນຫຼວງວຽງຈັນ') => [
            t('ຈັນທະບູລີ'), t('ສີໂຄດຕະບອງ'), t('ໄຊເສດຖາ'), t('ສີສັດຕະນາກ'), t('ນາຊາຍທອງ'),
            t('ໄຊທານີ'), t('ຫາດຊາຍຟອງ'), t('ສັງທອງ'), t('ປາກງື່ມ'),
        ],
        t('ແຂວງວຽງຈັນ') => [
            t('ໂພນໂຮງ'), t('ທຸລະຄົມ'), t('ແກ້ວອຸດົມ'), t('ກາສີ'), t('ວັງວຽງ'),
            t('ເຟືອງ'), t('ຊະນະຄາມ'), t('ແມດ'), t('ວຽງຄຳ'), t('ຫີນເຫີບ'), t('ໝື່ນ'),
        ],
        t('ຫຼວງພະບາງ') => [
            t('ນະຄອນຫຼວງພະບາງ'), t('ຊຽງເງິນ'), t('ນານ'), t('ປາກອູ'), t('ນ້ຳບາກ'),
            t('ງອຍ'), t('ປາກແຊງ'), t('ໂພນໄຊ'), t('ຈອມເພັດ'), t('ວຽງຄຳ'), t('ພູຄູນ'), t('ໂພນທອງ'),
        ],
        t('ຈຳປາສັກ') => [
            t('ນະຄອນປາກເຊ'), t('ຊະນະສົມບູນ'), t('ບາຈຽງຈະເລີນສຸກ'), t('ປາກຊ່ອງ'), t('ປະທຸມພອນ'),
            t('ໂພນທອງ'), t('ຈຳປາສັກ'), t('ສຸຂຸມາ'), t('ມຸນລະປະໂມກ'), t('ໂຂງ'),
        ],
        t('ສະຫວັນນະເຂດ') => [
            t('ນະຄອນໄກສອນ ພົມວິຫານ'), t('ອຸທຸມພອນ'), t('ອາດສະພັງທອງ'), t('ພິນ'), t('ເຊໂປນ'),
            t('ນອງ'), t('ທ່າປາງທອງ'), t('ສອງຄອນ'), t('ຈຳພອນ'), t('ຊົນນະບູລີ'),
            t('ໄຊບູລີ'), t('ວິລະບູລີ'), t('ອາດສະພອນ'), t('ໄຊພູທອງ'), t('ພະລານໄຊ'),
        ],
        t('ຄຳມ່ວນ') => [
            t('ທ່າແຂກ'), t('ມະຫາໄຊ'), t('ໜອງບົກ'), t('ຫີນບູນ'), t('ຍົມມະລາດ'),
            t('ບົວລະພາ'), t('ນາກາຍ'), t('ເຊບັ້ງໄຟ'), t('ໄຊບົວທອງ'), t('ຄູນຄຳ'),
        ],
        t('ບໍລິຄຳໄຊ') => [
            t('ປາກຊັນ'), t('ທ່າພະບາດ'), t('ປາກກະດິງ'), t('ບໍລິຄັນ'), t('ຄຳເກີດ'), t('ວຽງທອງ'), t('ໄຊຈຳພອນ'),
        ],
        t('ອຸດົມໄຊ') => [
            t('ໄຊ'), t('ຫຼາ'), t('ນາໝໍ້'), t('ງາ'), t('ແບງ'), t('ຮຸນ'), t('ປາກແບງ'),
        ],
        t('ບໍ່ແກ້ວ') => [
            t('ຫ້ວຍຊາຍ'), t('ຕົ້ນເຜິ້ງ'), t('ເມິງ'), t('ຜາອຸດົມ'), t('ປາກທາ'),
        ],
        t('ຫຼວງນ້ຳທາ') => [
            t('ຫຼວງນ້ຳທາ'), t('ສິງ'), t('ລອງ'), t('ວຽງພູຄາ'), t('ນາແລ'),
        ],
        t('ຜົ້ງສາລີ') => [
            t('ຜົ້ງສາລີ'), t('ໃໝ່'), t('ຂວາ'), t('ສຳພັນ'), t('ບຸນເໜືອ'), t('ຍອດອູ'), t('ບຸນໃຕ້'),
        ],
        t('ໄຊຍະບູລີ') => [
            t('ໄຊຍະບູລີ'), t('ຄອບ'), t('ຫົງສາ'), t('ເງິນ'), t('ຊຽງຮ່ອນ'),
            t('ພຽງ'), t('ປາກລາຍ'), t('ແກ່ນທ້າວ'), t('ບໍ່ແຕນ'), t('ທົ່ງມີໄຊ'), t('ໄຊສະຖານ'),
        ],
        t('ຊຽງຂວາງ') => [
            t('ແປກ'), t('ຄຳ'), t('ໜອງແຮດ'), t('ຄູນ'), t('ໝອກໃໝ່'), t('ພູກູດ'), t('ຜາໄຊ'),
        ],
        t('ຫົວພັນ') => [
            t('ຊຳເໜືອ'), t('ຊຽງຄໍ້'), t('ຮ້ຽມ'), t('ວຽງໄຊ'), t('ຫົວເມືອງ'),
            t('ຊຳໃຕ້'), t('ສົບເບົາ'), t('ແອດ'), t('ກວັນ'), t('ຊ່ອນ'),
        ],
        t('ສາລະວັນ') => [
            t('ສາລະວັນ'), t('ຕະໂອ້ຍ'), t('ຕຸ້ມລານ'), t('ລະຄອນເພັງ'),
            t('ວາປີ'), t('ຄົງເຊໂດນ'), t('ເລົ່າງາມ'), t('ສະມ້ວຍ'),
        ],
        t('ເຊກອງ') => [
            t('ລະມາມ'), t('ກະລຶມ'), t('ດາກຈຶງ'), t('ທ່າແຕງ'),
        ],
        t('ອັດຕະປື') => [
            t('ສາມັກຄີໄຊ'), t('ຊານໄຊ'), t('ສະໜາມໄຊ'), t('ສansai' !== '' ? 'ພູວົງ' : 'ພູວົງ'), t('ໄຊເສດຖາ'),
        ],
        t('ໄຊສົມບູນ') => [
            t('ອະນຸວົງ'), t('ລ້ອງແຈ້ງ'), t('ລ້ອງຊານ'), t('ຮົ່ມ'), t('ທ່າໂທມ'),
        ],
    ];
}

function getLaoProvinces(): array
{
    return array_keys(getLaoProvincesAndDistricts());
}

function getLaoCouriers(): array
{
    return [
        'anousith' => [
            'code'  => 'anousith',
            'name'  => t('ອະນຸສິດ (Anousith Express)'),
            'short' => 'Anousith',
            'logo'  => asset('img/couriers/anousith.jpg'),
        ],
        'hal' => [
            'code'  => 'hal',
            'name'  => t('ຮຸ່ງອາລຸນ (HAL Logistics)'),
            'short' => 'HAL Logistics',
            'logo'  => asset('img/couriers/hal.jpg'),
        ],
        'mixay' => [
            'code'  => 'mixay',
            'name'  => t('ມີໄຊ (Mixay Express)'),
            'short' => 'Mixay Express',
            'logo'  => asset('img/couriers/mixay.jpg'),
        ],
    ];
}

/**
 * ດຶງບັນຊີທະນາຄານທີ່ເປີດໃຊ້ງານຈາກລະບົບຫຼັງບ້ານ (w_bank_accounts).
 */
function getActiveBankAccounts(?PDO $pdo, int $storeId): array
{
    if (!$pdo) {
        return [];
    }
    try {
        $st = $pdo->prepare("SELECT id, bank_name, account_number, account_name, bank_code, bank_logo, qr_code_img
                             FROM w_bank_accounts
                             WHERE is_active = 1 AND (store_id = ? OR store_id = 0)
                             ORDER BY id ASC");
        $st->execute([$storeId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            $rows = $pdo->query("SELECT id, bank_name, account_number, account_name, bank_code, bank_logo, qr_code_img
                                 FROM w_bank_accounts WHERE is_active = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        return [];
    }

    $root = dirname(__DIR__, 2);
    $out = [];
    foreach ($rows as $r) {
        $logoFile = basename(trim((string)($r['bank_logo'] ?? '')));
        $qrFile   = basename(trim((string)($r['qr_code_img'] ?? '')));
        $r['logo_url'] = ($logoFile !== '' && is_file($root . '/backend/assets/img/banks/' . $logoFile))
            ? url('backend/assets/img/banks/' . rawurlencode($logoFile))
            : '';
        $r['qr_url'] = ($qrFile !== '' && is_file($root . '/backend/assets/img/qr/' . $qrFile))
            ? url('backend/assets/img/qr/' . rawurlencode($qrFile))
            : '';
        $out[] = $r;
    }
    return $out;
}

function validateOrderCustomer(array $in, array $banks = []): array
{
    $couriers = getLaoCouriers();
    $courierCode = trim((string)($in['courier_code'] ?? ''));
    $courierInfo = $couriers[$courierCode] ?? null;

    $bankId = (int)($in['bank_account_id'] ?? 0);
    $bankName = '';
    foreach ($banks as $b) {
        if ((int)$b['id'] === $bankId) {
            $bankName = $b['bank_name'] . ' (' . $b['account_number'] . ')';
            break;
        }
    }

    $c = [
        'name'            => trim((string)($in['name'] ?? '')),
        'tel'             => trim((string)($in['tel'] ?? '')),
        'email'           => trim((string)($in['email'] ?? '')),
        'province'        => trim((string)($in['province'] ?? '')),
        'district'        => trim((string)($in['district'] ?? '')),
        'village'         => trim((string)($in['village'] ?? '')),
        'address'         => trim((string)($in['address'] ?? '')),
        'courier_code'    => $courierInfo ? $courierCode : '',
        'courier_name'    => $courierInfo ? $courierInfo['name'] : '',
        'courier_branch'  => trim((string)($in['courier_branch'] ?? '')),
        'bank_account_id' => $bankId ?: null,
        'bank_name'       => $bankName,
        'slip_img'        => null,
        'note'            => trim((string)($in['note'] ?? '')),
    ];

    $errors = [];
    $isQuickInquiry = !empty($in['quick_inquiry']);
    if ($c['name'] === '' || mb_strlen($c['name']) > 150) {
        $errors['name'] = t('ກະລຸນາປ້ອນຊື່ ແລະ ນາມສະກຸນຜູ້ຮັບ');
    }
    if (!preg_match('/^[0-9+\-\s]{6,20}$/', $c['tel'])) {
        $errors['tel'] = t('ກະລຸນາປ້ອນເບີໂທທີ່ຖືກຕ້ອງ');
    }
    if ($c['email'] !== '' && !filter_var($c['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = t('ອີເມວບໍ່ຖືກຕ້ອງ');
    }
    if (!$isQuickInquiry) {
        if ($c['province'] === '') {
            $errors['province'] = t('ກະລຸນາເລືອກແຂວງ');
        }
        if ($c['district'] === '') {
            $errors['district'] = t('ກະລຸນາປ້ອນເມືອງ');
        }
        if ($c['village'] === '') {
            $errors['village'] = t('ກະລຸນາປ້ອນບ້ານ');
        }
        if (!$courierInfo) {
            $errors['courier_code'] = t('ກະລຸນາເລືອກບໍລິສັດຂົນສົ່ງ');
        }
        if ($c['courier_branch'] === '') {
            $errors['courier_branch'] = t('ກະລຸນາລະບຸສາຂາຂົນສົ່ງປາຍທາງ');
        }
    }
    if ($banks && !$bankName) {
        $errors['bank_account_id'] = t('ກະລຸນາເລືອກທະນາຄານທີ່ໂອນເງິນ');
    }
    return [$c, $errors];
}

/**
 * ບັນທຶກຮູບສະລິບໂອນເງິນລົງ backend/assets/web_slips/
 * @return array{ok: bool, path?: string, error?: string}
 */
function saveOrderSlipUpload(?array $file): array
{
    if (!$file || empty($file['tmp_name']) || (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => t('ກະລຸນາແນບຮູບສະລິບການໂອນເງິນ')];
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => t('ອັບໂຫຼດຮູບສະລິບບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່')];
    }
    if ((int)$file['size'] > 8 * 1024 * 1024) {
        return ['ok' => false, 'error' => t('ຂະໜາດຮູບສະລິບຕ້ອງບໍ່ເກີນ 8 MB')];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => t('ຮອງຮັບສະເພາະໄຟລ໌ຮູບພາບ JPG, PNG ຫຼື WEBP')];
    }

    $dir = dirname(__DIR__, 2) . '/backend/assets/web_slips';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    // ສະລິບມີຂໍ້ມູນບັນຊີລູກຄ້າ → ຫ້າມເປີດໂດຍກົງທັງໝົດ; ເບິ່ງຜ່ານ api/slip.php (ເຈົ້າຂອງອໍເດີ) / backend/pages/web_orders/slip.php (ພະນັກງານ)
    $ht = $dir . '/.htaccess';
    if (!is_file($ht) || !str_starts_with((string)file_get_contents($ht), 'Require all denied')) {
        @file_put_contents($ht, "Require all denied\n# web_slips: ເບິ່ງຜ່ານ frontend/api/slip.php ຫຼື backend/pages/web_orders/slip.php\n");
    }

    $name = 'slip_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
    $dest = $dir . '/' . $name;
    if (!@move_uploaded_file($file['tmp_name'], $dest)) {
        return ['ok' => false, 'error' => t('ບັນທຶກຮູບສະລິບບໍ່ສຳເລັດ')];
    }
    return ['ok' => true, 'path' => 'backend/assets/web_slips/' . $name];
}

function insertOrderRecord(PDO $pdo, int $storeId, array $customer, array $lines, float $total, int $totalQty): array
{
    $fullAddress = implode(', ', array_filter([
        $customer['village'] ? t('ບ້ານ ') . $customer['village'] : '',
        $customer['district'] ? t('ເມືອງ ') . $customer['district'] : '',
        $customer['province'] ?: '',
        $customer['address'] ?: '',
    ]));

    // ສະມາຊິກ login ຢູ່ → ຜູກອໍເດີກັບບັນຊີ (ປະຫວັດການສັ່ງຊື້ pages/my_orders.php)
    $memberId = (int)($customer['member_id'] ?? (currentMember($pdo)['id'] ?? 0));
    $st = $pdo->prepare("INSERT INTO w_web_orders
        (store_id, member_id, customer_name, customer_tel, customer_email, province, district, village, customer_address,
         courier_code, courier_name, courier_branch, bank_account_id, bank_name, slip_img, slip_uploaded_at, note, items_count, total_qty, total_amount)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $st->execute([
        $storeId,
        $memberId ?: null,
        $customer['name'],
        $customer['tel'],
        $customer['email'] ?: null,
        $customer['province'] ?: null,
        $customer['district'] ?: null,
        $customer['village'] ?: null,
        $fullAddress ?: null,
        $customer['courier_code'] ?: null,
        $customer['courier_name'] ?: null,
        $customer['courier_branch'] ?: null,
        $customer['bank_account_id'] ?: null,
        $customer['bank_name'] ?: null,
        $customer['slip_img'] ?: null,
        !empty($customer['slip_img']) ? date('Y-m-d H:i:s') : null,
        $customer['note'] ?: null,
        count($lines),
        $totalQty,
        $total,
    ]);
    $orderId = (int)$pdo->lastInsertId();
    $orderNo = 'WEB' . date('ymd') . '-' . str_pad((string)$orderId, 4, '0', STR_PAD_LEFT);
    $pdo->prepare("UPDATE w_web_orders SET order_no = ? WHERE id = ?")->execute([$orderNo, $orderId]);

    $itemSt = $pdo->prepare("INSERT INTO w_web_order_items (order_id, product_id, item_slug, product_name, price, qty, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($lines as [$p, $qty, $subtotal]) {
        $itemSt->execute([
            $orderId,
            (int)($p['product_id'] ?? 0),
            $p['item_slug'] ?? null,
            $p['product_name'],
            (float)$p['price'],
            (int)$qty,
            (float)$subtotal,
        ]);
    }
    return ['order_id' => $orderId, 'order_no' => $orderNo];
}

/**
 * ສັ່ງຊື້ສິນຄ້າໂດຍກົງຈາກໜ້າລາຍລະອຽດສິນຄ້າ (pages/solution.php ຫຼື pages/product.php)
 * $item = ['product_id' => int, 'item_slug' => ?string, 'product_name' => string, 'price' => float]
 */
function createDirectProductOrder(PDO $pdo, int $storeId, array $item, int $qty, array $customer): array
{
    $qty = max(1, min(999, $qty));
    $price = max(0.0, (float)($item['price'] ?? 0));
    $subtotal = $qty * $price;
    try {
        $pdo->beginTransaction();
        if (!empty($item['product_id'])) {
            $products = getProductsByIds($pdo, $storeId, [(int)$item['product_id']], true);
            $dbProd = $products[(int)$item['product_id']] ?? null;
            if (!$dbProd) {
                $pdo->rollBack();
                return ['success' => false, 'message' => t('ບໍ່ພົບສິນຄ້ານີ້ ຫຼື ຢຸດຂາຍແລ້ວ')];
            }
            if ($qty > productMaxQty($dbProd)) {
                $pdo->rollBack();
                return ['success' => false, 'message' => t('"%s" ເຫຼືອພຽງ %d %s', $dbProd['product_name'], productMaxQty($dbProd), $dbProd['unit'] ?: t('ອັນ'))];
            }
            $price = (float)$dbProd['price'];
            $subtotal = $qty * $price;
            $item['product_name'] = $dbProd['product_name'];
            $item['price'] = $price;
        }
        $res = insertOrderRecord($pdo, $storeId, $customer, [[$item, $qty, $subtotal]], $subtotal, $qty);
        $pdo->commit();
        rememberCustomerOrder($res['order_no']);
        staffNotifyOrder($pdo, $res['order_no']);   // ແຈ້ງພະນັກງານ (includes/staff_notify.php — config/notify.php)
        memberNotify($pdo, (int)(currentMember($pdo)['id'] ?? 0), 'order_new', ['order_no' => $res['order_no']], 'pages/my_order.php?no=' . rawurlencode($res['order_no']));   // ກະດິ່ງສະມາຊິກ
        return ['success' => true, 'order_no' => $res['order_no']];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('frontend createDirectProductOrder: ' . $e->getMessage());
        return ['success' => false, 'message' => t('ບັນທຶກຄຳສັ່ງຊື້ບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່')];
    }
}

/**
 * @param array $cartItems [product_id => qty]
 * @return array{success: bool, order_no?: string, message?: string}
 */
function createWebOrder(PDO $pdo, int $storeId, array $customer, array $cartItems): array
{
    if (!$cartItems) {
        return ['success' => false, 'message' => t('ກະຕ່າສິນຄ້າຫວ່າງເປົ່າ')];
    }
    try {
        $pdo->beginTransaction();
        $numericIds = [];
        foreach (array_keys($cartItems) as $k) {
            if (is_numeric($k) && (int)$k > 0) {
                $numericIds[] = (int)$k;
            }
        }
        $products = ($pdo && $numericIds) ? getProductsByIds($pdo, $storeId, $numericIds, true) : [];
        $hwFallback = function_exists('hardwareFallback') ? hardwareFallback() : [];

        $lines = [];
        $total = 0.0;
        $totalQty = 0;
        foreach ($cartItems as $k => $qty) {
            $qty = (int)$qty;
            if ($qty <= 0) {
                continue;
            }
            $p = null;
            if (is_numeric($k) && isset($products[(int)$k])) {
                $p = $products[(int)$k];
            } elseif (isset($hwFallback[$k])) {
                $hw = $hwFallback[$k];
                $p = [
                    'product_id'   => 0,
                    'item_slug'    => (string)$k,
                    'product_name' => $hw['card']['title'],
                    'price'        => 0.0,
                    'unit'         => t('ອັນ'),
                    'qty'          => 999,
                    'cut_qty'      => 0,
                ];
            }

            if (!$p) {
                $pdo->rollBack();
                return ['success' => false, 'message' => t('ມີສິນຄ້າໃນກະຕ່າທີ່ບໍ່ມີຂາຍແລ້ວ — ກະລຸນາກວດກະຕ່າອີກຄັ້ງ')];
            }
            if ($qty > productMaxQty($p)) {
                $pdo->rollBack();
                return ['success' => false, 'message' => t('"%s" ເຫຼືອພຽງ %d %s', $p['product_name'], productMaxQty($p), $p['unit'] ?: t('ອັນ'))];
            }
            $subtotal = $qty * (float)$p['price'];
            $lines[] = [$p, $qty, $subtotal];
            $total += $subtotal;
            $totalQty += $qty;
        }

        $res = insertOrderRecord($pdo, $storeId, $customer, $lines, $total, $totalQty);
        $pdo->commit();
        rememberCustomerOrder($res['order_no']);
        staffNotifyOrder($pdo, $res['order_no']);   // ແຈ້ງພະນັກງານ (includes/staff_notify.php — config/notify.php)
        memberNotify($pdo, (int)(currentMember($pdo)['id'] ?? 0), 'order_new', ['order_no' => $res['order_no']], 'pages/my_order.php?no=' . rawurlencode($res['order_no']));   // ກະດິ່ງສະມາຊິກ
        return ['success' => true, 'order_no' => $res['order_no']];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('frontend createWebOrder: ' . $e->getMessage());
        return ['success' => false, 'message' => t('ບັນທຶກຄຳສັ່ງຊື້ບໍ່ສຳເລັດ ກະລຸນາລອງໃໝ່')];
    }
}

function rememberCustomerOrder(string $orderNo): void
{
    $_SESSION['last_order_no'] = $orderNo;
    $list = $_SESSION['my_orders'] ?? [];
    if (!is_array($list)) {
        $list = [];
    }
    array_unshift($list, $orderNo);
    $_SESSION['my_orders'] = array_slice(array_values(array_unique($list)), 0, 15);
}

function getWebOrder(PDO $pdo, string $orderNo): ?array
{
    $st = $pdo->prepare("SELECT * FROM w_web_orders WHERE order_no = ? LIMIT 1");
    $st->execute([$orderNo]);
    $order = $st->fetch();
    if (!$order) {
        return null;
    }
    $items = $pdo->prepare("SELECT * FROM w_web_order_items WHERE order_id = ? ORDER BY id");
    $items->execute([$order['id']]);
    $order['items'] = $items->fetchAll();
    return $order;
}

/** ລຳດັບສະຖານະ 6 ຂັ້ນ (ພະນັກງານປ່ຽນໃນ backend/pages/web_orders/) — cancelled ຢູ່ນອກລຳດັບ */
const ORDER_STEPS = ['pending', 'confirmed', 'packing', 'shipped', 'arrived', 'completed'];

/** ຂໍ້ມູນສະແດງຂອງສະຖານະ: step (1–6, ຍົກເລີກ = 0), tone (ສີ), img (ຮູບ 3D), icon (emoji — ໃຊ້ໃນຂໍ້ຄວາມແຈ້ງເຕືອນ), label (ສັ້ນ), title + desc (ປ້າຍໃຫຍ່) */
function orderStatusMeta(string $status): array
{
    $map = [
        'pending'   => ['step' => 1, 'tone' => 'warn',    'icon' => '📝', 'label' => t('ຮັບຄຳສັ່ງຊື້'),     'title' => t('ໄດ້ຮັບຄຳສັ່ງຊື້ແລ້ວ — ລໍຖ້າຊຳລະເງິນ'), 'desc' => t('ກະລຸນາໂອນຊຳລະເງິນ ແລະ ແຈ້ງສະລິບໃຫ້ທີມງານ ເພື່ອຢືນຢັນອໍເດີ')],
        'confirmed' => ['step' => 2, 'tone' => 'info',    'icon' => '💳', 'label' => t('ຢືນຢັນ / ຮັບເງິນ'),  'title' => t('ຢືນຢັນອໍເດີ ແລະ ຮັບເງິນແລ້ວ'),        'desc' => t('ພະນັກງານກວດຍອດເງິນແລ້ວ ກຳລັງສົ່ງຕໍ່ໃຫ້ຝ່າຍກຽມສິນຄ້າ')],
        'packing'   => ['step' => 3, 'tone' => 'info',    'icon' => '📦', 'label' => t('ກຳລັງແພັກສິນຄ້າ'),   'title' => t('ກຳລັງກຽມ ແລະ ແພັກສິນຄ້າ'),          'desc' => t('ທີມງານກຳລັງກວດ ແລະ ຫຸ້ມຫໍ່ສິນຄ້າຂອງທ່ານຢ່າງລະມັດລະວັງ')],
        'shipped'   => ['step' => 4, 'tone' => 'primary', 'icon' => '🚚', 'label' => t('ກຳລັງຈັດສົ່ງ'),      'title' => t('ສິນຄ້າກຳລັງເດີນທາງໄປຫາທ່ານ'),        'desc' => t('ຝາກເຄື່ອງກັບບໍລິສັດຂົນສົ່ງແລ້ວ — ກວດເລກໃບຝາກເຄື່ອງດ້ານລຸ່ມ')],
        'arrived'   => ['step' => 5, 'tone' => 'primary', 'icon' => '📍', 'label' => t('ເຄື່ອງຮອດແລ້ວ'),     'title' => t('ເຄື່ອງຮອດສາຂາປາຍທາງແລ້ວ — ໄປຮັບໄດ້'),  'desc' => t('ກະລຸນາໄປຮັບເຄື່ອງທີ່ສາຂາຂົນສົ່ງ ພ້ອມເລກໃບຝາກເຄື່ອງ')],
        'completed' => ['step' => 6, 'tone' => 'ok',      'icon' => '🎉', 'label' => t('ຮັບເຄື່ອງແລ້ວ'),      'title' => t('ຮັບເຄື່ອງຮຽບຮ້ອຍ — ສຳເລັດ'),          'desc' => t('ຂອບໃຈທີ່ໃຊ້ບໍລິການ Wlaodev ມີຫຍັງສົງໄສ ແຊັດຫາພວກເຮົາໄດ້ຕະຫຼອດ')],
        'cancelled' => ['step' => 0, 'tone' => 'err',     'icon' => '✖',  'label' => t('ຍົກເລີກ'),           'title' => t('ຄຳສັ່ງຊື້ນີ້ຖືກຍົກເລີກແລ້ວ'),          'desc' => t('ຖ້າມີຂໍ້ສົງໄສ ກະລຸນາຕິດຕໍ່ທີມງານ')],
    ];
    $key = isset($map[$status]) ? $status : 'pending';
    // ຮູບ 3D ຂອງແຕ່ລະສະຖານະ (assets/img/order/<status>.png — Fluent Emoji 3D, MIT) ໃຊ້ແທນໄອຄອນ
    return $map[$key] + ['img' => asset('img/order/' . $key . '.png')];
}

/** ປະຫວັດສະຖານະຂອງອໍເດີ (ເກົ່າ → ໃໝ່): [['status','time','note'], …] — ຂັ້ນທຳອິດ = ເວລາສັ່ງຊື້ ສະເໝີ */
function orderStatusHistory(PDO $pdo, array $order): array
{
    $hist = [['status' => 'pending', 'time' => $order['created_at'], 'note' => '']];
    try {
        $st = $pdo->prepare("SELECT status, note, created_at FROM w_web_order_status_log WHERE order_id = ? ORDER BY id");
        $st->execute([$order['id']]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if ($r['status'] === 'pending' && count($hist) === 1) {
                continue;   // ມີ "ຮັບຄຳສັ່ງຊື້" ຢູ່ແລ້ວ
            }
            $hist[] = ['status' => $r['status'], 'time' => $r['created_at'], 'note' => (string)$r['note']];
        }
    } catch (Throwable $e) {
    }
    return $hist;
}

/** ເວລາຫຼ້າສຸດທີ່ໄປຮອດແຕ່ລະສະຖານະ: ['confirmed' => '2026-11-05 10:00:00', …] (ໃຊ້ສະແດງໃຕ້ຂັ້ນຕອນ) */
function orderStepTimes(array $history): array
{
    $times = [];
    foreach ($history as $h) {
        $times[$h['status']] = $h['time'];
    }
    return $times;
}

function searchWebOrders(PDO $pdo, string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return [];
    }
    $digits = preg_replace('/\D+/', '', $q);
    if (stripos($q, 'WEB') === 0 || strpos($q, '-') !== false) {
        $st = $pdo->prepare("SELECT * FROM w_web_orders WHERE UPPER(order_no) = UPPER(?) ORDER BY id DESC LIMIT 10");
        $st->execute([$q]);
    } elseif (strlen($digits) >= 6) {
        $tail = substr($digits, -8);
        $st = $pdo->prepare("SELECT * FROM w_web_orders WHERE REPLACE(REPLACE(REPLACE(customer_tel, ' ', ''), '-', ''), '+', '') LIKE ? OR UPPER(order_no) = UPPER(?) ORDER BY id DESC LIMIT 15");
        $st->execute(['%' . $tail . '%', $q]);
    } else {
        $st = $pdo->prepare("SELECT * FROM w_web_orders WHERE UPPER(order_no) = UPPER(?) OR customer_tel = ? ORDER BY id DESC LIMIT 10");
        $st->execute([$q, $q]);
    }
    $orders = $st->fetchAll();
    return attachItemsToOrders($pdo, $orders);
}

function getOrdersByNos(PDO $pdo, array $nos): array
{
    $nos = array_values(array_filter(array_map('trim', $nos)));
    if (!$nos) {
        return [];
    }
    $marks = implode(',', array_fill(0, count($nos), '?'));
    $st = $pdo->prepare("SELECT * FROM w_web_orders WHERE order_no IN ($marks) ORDER BY id DESC");
    $st->execute($nos);
    return attachItemsToOrders($pdo, $st->fetchAll());
}

function attachItemsToOrders(PDO $pdo, array $orders): array
{
    if (!$orders) {
        return [];
    }
    $ids = array_column($orders, 'id');
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT * FROM w_web_order_items WHERE order_id IN ($marks) ORDER BY id ASC");
    $st->execute($ids);
    $byOrder = [];
    foreach ($st->fetchAll() as $it) {
        $byOrder[(int)$it['order_id']][] = $it;
    }
    foreach ($orders as &$o) {
        $o['items'] = $byOrder[(int)$o['id']] ?? [];
    }
    unset($o);
    return $orders;
}
