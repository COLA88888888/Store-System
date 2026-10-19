<?php
/**
 * ສ່ວນ: ໂປຣໂມຊັ່ນ
 * ປິດໂປຣທີ່ໝົດອາຍຸ, ດຶງໂປຣທີ່ໃຊ້ໄດ້ຂອງສາຂາ ແລະ ຟັງຊັນຈັບຄູ່ໂປຣກັບສິນຄ້າ/ໝວດ/ຫົວໜ່ວຍ ແລະ
 * ຄິດລາຄາຫຼັງສ່ວນຫຼຸດ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ api/pos_backend.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// Auto-deactivate expired promotions & Fetch active promotions for current branch/store
$activePromos = [];
try {
    // Scope promos to the branch the POS is actually operating as (honours ?switch_store_id
    // for admin/main-branch users), matching how products/categories are scoped below.
    $currentPosStoreId = getPosStoreId();
    $pdo->exec("UPDATE st_promotions SET status = 0 WHERE status = 1 AND end_date < CURDATE()");
    $stmtActivePromos = $pdo->prepare("
        SELECT * FROM st_promotions 
        WHERE status = 1 
          AND (branch_id = ? OR branch_id = 0)
          AND CURDATE() BETWEEN start_date AND end_date 
        ORDER BY id DESC
    ");
    $stmtActivePromos->execute([$currentPosStoreId]);
    $activePromos = $stmtActivePromos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Loosen string comparison so a promo's free-typed target still matches the real product/
// category name despite spacing, casing, zero-width chars or Unicode form differences
// (common with Lao text entered by hand).
function promoNormalizeName($s) {
    $s = (string)$s;
    if (class_exists('Normalizer')) {
        $n = Normalizer::normalize($s, Normalizer::FORM_C);
        if ($n !== false) $s = $n;
    }
    // Fold the two ways Lao vowels get typed: ເ+ເ vs ແ, and ເ+ໂ etc. (ແກັດ vs ເເກັດ, ແກ້ວ vs ເເກ້ວ)
    $s = str_replace(["\u{0EC0}\u{0EC0}", "\u{0EC0}\u{0EC1}"], "\u{0EC1}", $s);
    // drop zero-width / BOM / all whitespace, then lowercase
    $s = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}\s]+/u', '', $s);
    return mb_strtolower(trim($s));
}
function promoNameMatches($actual, $target) {
    $a = promoNormalizeName($actual);
    $t = promoNormalizeName($target);
    if ($a === '' || $t === '') return false;
    return $a === $t || mb_stripos($a, $t) !== false || mb_stripos($t, $a) !== false;
}
// Unit matching must be EXACT (after normalising) — never substring. BeerLao's ປ໋ອງຍາວ and
// ປ໋ອງສັ້ນ share the "ປ໋ອງ" prefix, so a loose match would discount both when a promo names
// only one. "all"/empty means every unit.
function promoUnitMatches($actualUnit, $targetUnit) {
    $t = promoNormalizeName($targetUnit);
    if ($t === '' || $t === 'all') return true;
    return promoNormalizeName($actualUnit) === $t;
}

function getProductPromotion($product, $activePromos, $dispCur = null) {
    if (empty($activePromos)) return null;

    $pName = trim($product['product_name'] ?? '');
    $cName = trim($product['category_name'] ?? '');
    // Codes a promo's target may be entered as instead of the product name
    $pCodes = array_filter([
        trim((string)($product['barcode'] ?? '')),
        trim((string)($product['code1'] ?? '')),
        trim((string)($product['product_id'] ?? '')),
    ], 'strlen');

    $matchedDiscountPromo = null;
    $matchedGiftPromo = null;

    foreach ($activePromos as $promo) {
        $targetType = $promo['target_type'] ?? 'all';
        $targetName = trim($promo['target_name'] ?? '');

        // Match the promo to the PRODUCT (or its category, or "all"). Unit scoping is applied
        // later, per sellable unit — a promo aimed at one unit (e.g. ແກັດ) still "belongs" to
        // the product; its other units simply stay at full price.
        $isMatch = false;
        if ($targetType === 'all' || empty($targetName) || $targetName === 'ທຸກສິນຄ້າ') {
            $isMatch = true;
        } elseif ($targetType === 'category') {
            $isMatch = (!empty($cName) && promoNameMatches($cName, $targetName));
        } elseif ($targetType === 'product') {
            $isMatch = (!empty($pName) && promoNameMatches($pName, $targetName))
                       || in_array($targetName, $pCodes, true);
        }
        if (!$isMatch) continue;

        if (floatval($promo['discount_value'] ?? 0) > 0 && !$matchedDiscountPromo) {
            $matchedDiscountPromo = $promo;
        }
        if (trim($promo['gift_product_name'] ?? '') !== '' && !$matchedGiftPromo) {
            $matchedGiftPromo = $promo;
        }
        if ($matchedDiscountPromo && $matchedGiftPromo) break;
    }

    $promoToUse = $matchedDiscountPromo ?? $matchedGiftPromo;
    if (!$promoToUse) return null;

    $promoType = $promoToUse['promo_type'] ?? 'discount';
    $discType  = $promoToUse['discount_type'] ?? 'percentage';
    $discVal   = floatval($promoToUse['discount_value'] ?? 0);
    $giftName  = trim($matchedGiftPromo['gift_product_name'] ?? $promoToUse['gift_product_name'] ?? '');
    $giftQty   = intval($matchedGiftPromo['gift_qty'] ?? $promoToUse['gift_qty'] ?? 1);
    if ($giftQty < 1) $giftQty = 1;

    $tunit = trim($promoToUse['target_unit_name'] ?? 'all');
    if ($tunit === '') $tunit = 'all';
    $unitSuffix = ($tunit !== 'all') ? ' (ສະເພາະ ' . $tunit . ')' : '';

    // Discount descriptor (unit-agnostic — the actual amount is computed per selected unit).
    // A fixed ₭ amount is shown converted into the POS's active display currency.
    $discountText = '';
    if ($discType === 'percentage' && $discVal > 0) {
        $discountText = 'ຫຼຸດ ' . (floor($discVal) == $discVal ? intval($discVal) : number_format($discVal, 1)) . '%';
    } elseif ($discVal > 0) {
        $curCode = strtoupper((string)($dispCur['code'] ?? 'KIP'));
        if ($curCode !== 'KIP' && function_exists('convertFromKip')) {
            $amt = convertFromKip($discVal, $curCode, $dispCur['rates'] ?? null);
            $dec = (int)($dispCur['decimals'] ?? 2);
            $sym = (string)($dispCur['symbol'] ?? $curCode);
            $discountText = 'ຫຼຸດ ' . number_format((float)$amt, $dec, '.', ',') . ' ' . $sym;
        } else {
            $discountText = 'ຫຼຸດ ' . number_format($discVal) . '₭';
        }
    }
    $giftText = ($giftName !== '') ? ('ແຖມ ' . $giftName . ($giftQty > 1 ? ' x' . $giftQty : '')) : '';

    // Badge always leads with the promo name so the cashier can see which promo is in effect
    $parts = array_values(array_filter([$discountText, $giftText]));
    $badge = trim($promoToUse['promo_name'] ?? '') !== '' ? trim($promoToUse['promo_name']) : 'ໂປຣ';
    if ($parts) $badge .= ' · ' . implode(' + ', $parts);
    $badge .= $unitSuffix;

    return [
        'promo_name'        => $promoToUse['promo_name'] ?? '',
        'promo_type'        => $promoType,
        'discount_type'     => $discType,
        'discount_value'    => $discVal,
        'badge'             => $badge,
        'min_qty'           => intval($promoToUse['min_qty'] ?? 0),
        'min_amount'        => floatval($promoToUse['min_amount'] ?? 0),
        'gift_product_name' => $giftName,
        'gift_qty'          => $giftQty,
        'target_unit_name'  => $tunit
    ];
}

// Apply a promo to ONE sellable unit. Returns [originalPrice, effectivePrice, wasDiscounted].
// The discount only bites when the promo targets "all" units or this unit's name — so a promo
// scoped to ແກັດ leaves ຫໍ່ (and every other unit) untouched.
function applyPromoToUnit($promo, $unitName, $unitPrice) {
    $unitPrice = floatval($unitPrice);
    $hit = promoUnitMatches($unitName, $promo['target_unit_name'] ?? 'all');
    if (!$hit || floatval($promo['discount_value'] ?? 0) <= 0) {
        return [$unitPrice, $unitPrice, false];
    }
    $dv = floatval($promo['discount_value']);
    if (($promo['discount_type'] ?? 'percentage') === 'percentage') {
        $eff = $unitPrice - ($unitPrice * $dv / 100);
    } else {
        $eff = $unitPrice - $dv;
    }
    return [$unitPrice, max(0, round($eff)), true];
}

