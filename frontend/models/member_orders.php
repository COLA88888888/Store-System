<?php
/**
 * ສະມາຊິກ: ເຫັນລາຄາ, ສັ່ງຊື້ພ້ອມທີ່ຢູ່ / ສາຂາຂົນສົ່ງ / ສະລິບໂອນເງິນ, ປະຫວັດການສັ່ງຊື້ ແລະ ຕິດຕາມສະຖານະ.
 *   - ລູກຄ້າທົ່ວໄປ (ບໍ່ໄດ້ລົງທະບຽນ) ບໍ່ເຫັນລາຄາ — ໃຊ້ຟອມຂໍໃບສະເໜີລາຄາ (components/product_order_form.php) ຄືເກົ່າ
 *   - ຟອມສັ່ງຊື້ຂອງສະມາຊິກ: components/member_order_form.php (ໜ້າສິນຄ້າ) ແລະ pages/checkout.php (ກະຕ່າ)
 *     ທັງສອງໃຊ້ memberValidateOrder() ຂ້າງລຸ່ມ → createDirectProductOrder() / createWebOrder() (models/orders.php)
 *   - ອໍເດີຜູກກັບສະມາຊິກດ້ວຍ w_web_orders.member_id (insertOrderRecord ໃສ່ໃຫ້ເອງ). ອໍເດີເກົ່າທີ່ສັ່ງດ້ວຍເບີດຽວກັນ
 *     ກ່ອນລົງທະບຽນ ກໍສະແດງໃນປະຫວັດ (pages/my_orders.php, pages/my_order.php)
 *   - ສະລິບ: ແນບຕອນສັ່ງ ຫຼື ແນບພາຍຫຼັງໃນໜ້າອໍເດີ (memberAttachSlip) — ພະນັກງານກວດໃນ backend/pages/web_orders/
 */

/** ສະມາຊິກ login ຢູ່ = ເຫັນລາຄາ */
function canSeePrices(): bool
{
    return currentMember() !== null;
}

/** ລາຄາສຳລັບສະແດງ: '1,250,000 ₭' — 0 = 'ສອບຖາມລາຄາ' */
function priceText(float $price): string
{
    return $price > 0 ? money($price) : t('ສອບຖາມລາຄາ');
}

/**
 * ກວດຟອມສັ່ງຊື້ຂອງສະມາຊິກ: ທີ່ຢູ່ + ຂົນສົ່ງ/ສາຂາ ບັງຄັບ, ເບີໂທ = ເບີທີ່ລົງທະບຽນ (ເບີຈິງ), ສະລິບບໍ່ບັງຄັບ (ແນບພາຍຫຼັງໄດ້)
 * ແຕ່ຖ້າແນບສະລິບ ຕ້ອງເລືອກທະນາຄານທີ່ໂອນ. ບັນທຶກໄຟລ໌ສະລິບເມື່ອທຸກຢ່າງຖືກຕ້ອງເທົ່ານັ້ນ.
 * @return array [$customer, $errors]
 */
function memberValidateOrder(array $member, array $post, ?array $slipFile, array $banks): array
{
    $post['tel'] = $member['tel'];
    unset($post['quick_inquiry']);
    $hasSlip = $slipFile && (int)($slipFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    [$c, $errors] = validateOrderCustomer($post, $hasSlip ? $banks : []);
    $c['member_id'] = (int)$member['id'];
    if (!$hasSlip && $c['bank_account_id']) {
        foreach ($banks as $b) {
            if ((int)$b['id'] === (int)$c['bank_account_id']) {
                $c['bank_name'] = $b['bank_name'] . ' (' . $b['account_number'] . ')';
            }
        }
    }
    if ($hasSlip && !$errors) {
        $up = saveOrderSlipUpload($slipFile);
        if ($up['ok']) {
            $c['slip_img'] = $up['path'];
        } else {
            $errors['slip'] = $up['error'];
        }
    }
    return [$c, $errors];
}

/** ຄ່າເລີ່ມຕົ້ນຂອງຟອມສັ່ງຊື້ = ຂໍ້ມູນໃນບັນຊີສະມາຊິກ (ທີ່ຢູ່ ແລະ ຂົນສົ່ງທີ່ບັນທຶກໄວ້) */
function memberOrderDefaults(array $member): array
{
    return [
        'name'            => (string)$member['name'],
        'tel'             => (string)$member['tel'],
        'email'           => (string)$member['email'],
        'province'        => (string)$member['province'],
        'district'        => (string)$member['district'],
        'village'         => (string)$member['village'],
        'address'         => (string)$member['address'],
        'courier_code'    => (string)($member['courier_code'] ?: 'anousith'),
        'courier_branch'  => (string)$member['courier_branch'],
        'bank_account_id' => 0,
        'note'            => '',
    ];
}

/** ສັ່ງຊື້ສຳເລັດ → ບັນທຶກທີ່ຢູ່/ຂົນສົ່ງ ລົງບັນຊີ ຖ້າບັນຊີຍັງບໍ່ມີ (ຄັ້ງໜ້າຕື່ມໃຫ້ເອງ) */
function memberRememberAddress(PDO $pdo, array $member, array $c): void
{
    $set = [];
    $vals = [];
    foreach (['province', 'district', 'village', 'address', 'courier_code', 'courier_branch'] as $k) {
        if (trim((string)$member[$k]) === '' && trim((string)($c[$k] ?? '')) !== '') {
            $set[] = "`$k` = ?";
            $vals[] = $c[$k];
        }
    }
    if ($set) {
        $vals[] = (int)$member['id'];
        $pdo->prepare("UPDATE w_web_members SET " . implode(', ', $set) . " WHERE id = ?")->execute($vals);
    }
}

/** ເງື່ອນໄຂ SQL: ອໍເດີຂອງສະມາຊິກ (member_id) + ອໍເດີທີ່ສັ່ງດ້ວຍເບີດຽວກັນກ່ອນລົງທະບຽນ */
function memberOrderWhere(array $member): array
{
    $tel = preg_replace('/\D+/', '', (string)$member['tel']);
    if (strlen($tel) >= 9) {
        $tail = substr($tel, -8);
        return ["(o.member_id = ? OR (o.member_id IS NULL AND REPLACE(REPLACE(REPLACE(o.customer_tel, ' ', ''), '-', ''), '+', '') LIKE ?))", [(int)$member['id'], '%' . $tail]];
    }
    return ['o.member_id = ?', [(int)$member['id']]];
}

/** ປະຫວັດການສັ່ງຊື້ (ໃໝ່ → ເກົ່າ) ພ້ອມລາຍການສິນຄ້າ. $status = '' (ທັງໝົດ) | 'active' (ກຳລັງດຳເນີນການ) | ສະຖານະ */
function memberOrders(PDO $pdo, array $member, string $status = '', int $limit = 50): array
{
    [$where, $params] = memberOrderWhere($member);
    if ($status === 'active') {
        $where .= " AND o.status NOT IN ('completed','cancelled')";
    } elseif ($status !== '') {
        $where .= ' AND o.status = ?';
        $params[] = $status;
    }
    $st = $pdo->prepare("SELECT o.* FROM w_web_orders o WHERE $where ORDER BY o.id DESC LIMIT " . max(1, min(200, $limit)));
    $st->execute($params);
    return attachItemsToOrders($pdo, $st->fetchAll(PDO::FETCH_ASSOC));
}

/** ນັບອໍເດີຕາມສະຖານະ: ['all' => n, 'active' => n, 'completed' => n, 'cancelled' => n] */
function memberOrderCounts(PDO $pdo, array $member): array
{
    [$where, $params] = memberOrderWhere($member);
    $st = $pdo->prepare("SELECT o.status, COUNT(*) c FROM w_web_orders o WHERE $where GROUP BY o.status");
    $st->execute($params);
    $out = ['all' => 0, 'active' => 0, 'completed' => 0, 'cancelled' => 0];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $out['all'] += (int)$r['c'];
        $key = in_array($r['status'], ['completed', 'cancelled'], true) ? $r['status'] : 'active';
        $out[$key] += (int)$r['c'];
    }
    return $out;
}

/** ອໍເດີ 1 ລາຍການຂອງສະມາຊິກ (ບໍ່ແມ່ນຂອງຕົນ → null) */
function memberOrder(PDO $pdo, array $member, string $orderNo): ?array
{
    [$where, $params] = memberOrderWhere($member);
    $params[] = $orderNo;
    $st = $pdo->prepare("SELECT o.* FROM w_web_orders o WHERE $where AND o.order_no = ? LIMIT 1");
    $st->execute($params);
    $o = $st->fetch(PDO::FETCH_ASSOC);
    return $o ? attachItemsToOrders($pdo, [$o])[0] : null;
}

/** ຍອດລວມທີ່ສະແດງໃຫ້ລູກຄ້າ: total_amount ຫຼື ຜົນລວມລາຍການ */
function orderDisplayTotal(array $o): float
{
    $total = (float)$o['total_amount'];
    if ($total <= 0) {
        foreach ($o['items'] ?? [] as $it) {
            $total += (float)$it['subtotal'];
        }
    }
    return $total;
}

/** ແນບ / ປ່ຽນ ສະລິບພາຍຫຼັງ (ອໍເດີທີ່ຍັງບໍ່ສຳເລັດ ຫຼື ຍົກເລີກ). null = ສຳເລັດ, string = ຂໍ້ຜິດພາດ */
function memberAttachSlip(PDO $pdo, array $order, ?array $file, int $bankId, array $banks): ?string
{
    if (in_array($order['status'], ['completed', 'cancelled'], true)) {
        return t('ອໍເດີນີ້ປິດແລ້ວ ແນບສະລິບບໍ່ໄດ້');
    }
    $bankName = '';
    foreach ($banks as $b) {
        if ((int)$b['id'] === $bankId) {
            $bankName = $b['bank_name'] . ' (' . $b['account_number'] . ')';
        }
    }
    if ($banks && $bankName === '') {
        return t('ກະລຸນາເລືອກທະນາຄານທີ່ໂອນເງິນ');
    }
    $up = saveOrderSlipUpload($file);
    if (!$up['ok']) {
        return $up['error'];
    }
    $pdo->prepare("UPDATE w_web_orders SET slip_img = ?, slip_uploaded_at = NOW(), bank_account_id = ?, bank_name = ? WHERE id = ?")
        ->execute([$up['path'], $bankId ?: null, $bankName ?: null, (int)$order['id']]);
    // ປ່ຽນສະລິບ → ລຶບໄຟລ໌ສະລິບເກົ່າອອກຈາກໂຟນເດີ (ບໍ່ໃຫ້ຄ້າງ)
    $oldSlip = (string)($order['slip_img'] ?? '');
    if ($oldSlip !== '' && $oldSlip !== $up['path'] && str_starts_with($oldSlip, 'backend/assets/web_slips/')) {
        @unlink(dirname(__DIR__, 2) . '/' . $oldSlip);
    }
    try {
        $pdo->prepare("INSERT INTO w_web_order_status_log (order_id, status, note) VALUES (?, ?, ?)")
            ->execute([(int)$order['id'], $order['status'], 'ລູກຄ້າແນບສະລິບໂອນເງິນ']);
    } catch (Throwable $e) {
    }
    staffNotifyOrder($pdo, (string)$order['order_no'], 'slip');   // ແຈ້ງພະນັກງານໃຫ້ກວດສະລິບ
    return null;
}

/** URL ຂອງຮູບບິນຮັບເຄື່ອງ (ພະນັກງານແນບ) — ຜ່ານ api/slip.php?type=receipt. '' = ຍັງບໍ່ມີ */
function receiptUrl(?string $path, string $orderNo): string
{
    $path = (string)$path;
    return $path !== '' && $orderNo !== '' && is_file(dirname(__DIR__, 2) . '/' . $path) ? url('api/slip.php?type=receipt&no=' . rawurlencode($orderNo)) : '';
}

/** URL ຂອງຮູບສະລິບ — ຜ່ານ api/slip.php (ສະເພາະເຈົ້າຂອງອໍເດີ; ໂຟນເດີ web_slips ຫ້າມເປີດໂດຍກົງ). '' = ບໍ່ມີສະລິບ */
function slipUrl(?string $path, string $orderNo = ''): string
{
    $path = (string)$path;
    return $path !== '' && $orderNo !== '' && is_file(dirname(__DIR__, 2) . '/' . $path) ? url('api/slip.php?no=' . rawurlencode($orderNo)) : '';
}

/** ຮູບສິນຄ້າທຳອິດຂອງແຕ່ລະອໍເດີ (ຕາຕະລາງອໍເດີ) → [order_id => url|null] — ອ່ານ w_products.img_url ຄັ້ງດຽວ */
function memberOrderThumbs(PDO $pdo, array $orders): array
{
    $pid = [];
    foreach ($orders as $o) {
        $first = $o['items'][0]['product_id'] ?? 0;
        if ($first) {
            $pid[(int)$o['id']] = (int)$first;
        }
    }
    $img = [];
    if ($pid) {
        $ids = array_values(array_unique($pid));
        $st = $pdo->prepare("SELECT product_id, img_url FROM w_products WHERE product_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
        $st->execute($ids);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $img[(int)$r['product_id']] = productImage($r['img_url']);
        }
    }
    $out = [];
    foreach ($orders as $o) {
        $out[(int)$o['id']] = $img[$pid[(int)$o['id']] ?? 0] ?? null;
    }
    return $out;
}

/**
 * ອໍເດີແບບແບ່ງໜ້າ (pages/my_orders.php): $status ຄືກັບ memberOrders ('' | 'active' | ສະຖານະ)
 * → ['orders' => [...ພ້ອມ items], 'total' => n, 'page' => ໜ້າປັດຈຸບັນ (ປັບໃຫ້ຢູ່ໃນຂອບເຂດ), 'pages' => n, 'per' => n]
 */
function memberOrdersPaged(PDO $pdo, array $member, string $status, int $page, int $per = 10): array
{
    [$where, $params] = memberOrderWhere($member);
    if ($status === 'active') {
        $where .= " AND o.status NOT IN ('completed','cancelled')";
    } elseif ($status !== '') {
        $where .= ' AND o.status = ?';
        $params[] = $status;
    }
    $st = $pdo->prepare("SELECT COUNT(*) FROM w_web_orders o WHERE $where");
    $st->execute($params);
    $total = (int)$st->fetchColumn();
    $pages = max(1, (int)ceil($total / $per));
    $page = max(1, min($pages, $page));
    $st = $pdo->prepare("SELECT o.* FROM w_web_orders o WHERE $where ORDER BY o.id DESC LIMIT " . (int)$per . " OFFSET " . (($page - 1) * $per));
    $st->execute($params);
    return ['orders' => attachItemsToOrders($pdo, $st->fetchAll(PDO::FETCH_ASSOC)), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => $per];
}

/** ຮູບສິນຄ້າຂອງແຕ່ລະລາຍການໃນອໍເດີ (pages/my_order.php) → [product_id => url|null] */
function memberItemImages(PDO $pdo, array $items): array
{
    $ids = array_values(array_unique(array_filter(array_map(fn($i) => (int)($i['product_id'] ?? 0), $items))));
    if (!$ids) {
        return [];
    }
    $st = $pdo->prepare("SELECT product_id, img_url FROM w_products WHERE product_id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")");
    $st->execute($ids);
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $out[(int)$r['product_id']] = productImage($r['img_url']);
    }
    return $out;
}
