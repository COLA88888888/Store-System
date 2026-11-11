<?php
/**
 * ຄຳແປຂໍ້ມູນຈາກຖານຂໍ້ມູນ (ຊື່ສິນຄ້າ, ຊື່ໝວດ) — ຕາຕະລາງ w_translations (backend migration v35).
 *   ອ່ານ:  localizeProducts() / localizeCategories() ແທນຊື່ລາວດ້ວຍຄຳແປຂອງພາສາທີ່ເລືອກ (models/products.php ເອີ້ນໃຊ້)
 *          ຄຳແປໃຊ້ໄດ້ສະເພາະເມື່ອ source_hash = md5(ຊື່ລາວປັດຈຸບັນ) → ແກ້ຊື່ລາວ = ສະແດງລາວຈົນກວ່າຈະແປໃໝ່.
 *   ແປ:   dbTrProcessMissing() ແປອັນທີ່ຍັງບໍ່ມີ / ລ້າສະໄໝ ດ້ວຍ chatTranslate() (includes/translate.php: Claude ຖ້າມີ key, ບໍ່ດັ່ງນັ້ນ MyMemory)
 *          ຖືກເອີ້ນຈາກ api/translate_missing.php — (1) ຫຼັງບ້ານສັ່ງທັນທີທີ່ເພີ່ມ/ແກ້ສິນຄ້າ ຫຼື ໝວດ (backend/config/helpers/web_translate.php)
 *          (2) footer ສົ່ງ beacon ເມື່ອໜ້າທີ່ເປີດມີຊື່ທີ່ຍັງບໍ່ໄດ້ແປ ($GLOBALS['dbTrMissing']).
 */

const DB_TR_LANGS = ['th', 'en', 'zh', 'vi'];
const DB_TR_ENTITIES = [
    'products'   => ['table' => 'w_products',   'id' => 'product_id',  'field' => 'product_name'],
    'categories' => ['table' => 'w_categories', 'id' => 'category_id', 'field' => 'category_name'],
];

/** [entity_id => text] ຂອງຄຳແປທີ່ຍັງໃຊ້ໄດ້ (hash ກົງ) — $sources = [entity_id => ຂໍ້ຄວາມລາວ] */
function dbTrLookup(PDO $pdo, string $entity, array $sources, string $lang): array
{
    if (!$sources || !in_array($lang, DB_TR_LANGS, true) || !isset(DB_TR_ENTITIES[$entity])) {
        return [];
    }
    $ids = array_map('intval', array_keys($sources));
    $marks = implode(',', array_fill(0, count($ids), '?'));
    try {
        $st = $pdo->prepare("SELECT entity_id, source_hash, text FROM w_translations
                             WHERE entity = ? AND field = ? AND lang = ? AND entity_id IN ($marks)");
        $st->execute(array_merge([$entity, DB_TR_ENTITIES[$entity]['field'], $lang], $ids));
    } catch (Throwable $e) {
        return [];   // ຕາຕະລາງຍັງບໍ່ມີ (migration v35 ຍັງບໍ່ແລ່ນ)
    }
    $out = [];
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $id = (int)$r['entity_id'];
        if (isset($sources[$id]) && $r['source_hash'] === md5((string)$sources[$id]) && trim($r['text']) !== '') {
            $out[$id] = $r['text'];
        }
    }
    return $out;
}

/** ແທນຄ່າ $rows[$i][$textKey] ດ້ວຍຄຳແປ (ຕາມ $idKey). ຂາດຄຳແປ → ຄົງລາວໄວ້ ແລະ ໝາຍ dbTrMissing ໃຫ້ footer ສັ່ງແປ */
function localizeRows(array $rows, string $entity, string $idKey, string $textKey): array
{
    global $pdo;
    $lang = function_exists('currentLang') ? currentLang() : 'lo';
    if ($lang === 'lo' || !$rows || !($pdo instanceof PDO)) {
        return $rows;
    }
    $sources = [];
    foreach ($rows as $r) {
        if (!empty($r[$idKey]) && trim((string)($r[$textKey] ?? '')) !== '') {
            $sources[(int)$r[$idKey]] = (string)$r[$textKey];
        }
    }
    $map = dbTrLookup($pdo, $entity, $sources, $lang);
    foreach ($rows as $k => $r) {
        $id = (int)($r[$idKey] ?? 0);
        if (!isset($sources[$id])) {
            continue;
        }
        if (isset($map[$id])) {
            $rows[$k][$textKey] = $map[$id];
        } elseif (preg_match('/[\x{0E80}-\x{0EFF}]/u', $sources[$id])) {   // ມີຕົວລາວ ແຕ່ຍັງບໍ່ໄດ້ແປ
            $GLOBALS['dbTrMissing'] = true;
        }
    }
    return $rows;
}

/** ແຖວສິນຄ້າ (product_name + category_name) */
function localizeProducts(array $rows): array
{
    $rows = localizeRows($rows, 'products', 'product_id', 'product_name');
    return localizeRows($rows, 'categories', 'category_id', 'category_name');
}

function localizeProduct(?array $row): ?array
{
    return $row ? localizeProducts([$row])[0] : null;
}

function localizeCategories(array $rows): array
{
    return localizeRows($rows, 'categories', 'category_id', 'category_name');
}

/**
 * ແປຊື່ສິນຄ້າ (ສາຂາຂອງເວັບ) ແລະ ຊື່ໝວດ ທີ່ຍັງບໍ່ມີຄຳແປ ຫຼື ຄຳແປລ້າສະໄໝ (ຊື່ລາວຖືກແກ້) — ເຮັດສູງສຸດ $maxCalls ຄັ້ງ
 * (1 ຄັ້ງ = 1 ຂໍ້ຄວາມ × 1 ພາສາ). ຄືນ ['done' => ແປແລ້ວ, 'failed' => ແປບໍ່ໄດ້, 'left' => ຍັງເຫຼືອ]
 */
function dbTrProcessMissing(PDO $pdo, int $storeId, int $maxCalls = 40): array
{
    $stats = ['done' => 0, 'failed' => 0, 'left' => 0];
    $sources = [
        'products'   => $pdo->prepare("SELECT product_id AS id, product_name AS txt FROM w_products WHERE store_id = ? AND price >= 0"),
        'categories' => $pdo->prepare("SELECT category_id AS id, category_name AS txt FROM w_categories"),
    ];
    $sources['products']->execute([$storeId]);
    $sources['categories']->execute();
    $upsert = $pdo->prepare("INSERT INTO w_translations (entity, entity_id, field, lang, source_hash, text) VALUES (?, ?, ?, ?, ?, ?)
                             ON DUPLICATE KEY UPDATE source_hash = VALUES(source_hash), text = VALUES(text)");
    foreach ($sources as $entity => $st) {
        $field = DB_TR_ENTITIES[$entity]['field'];
        $items = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $txt = trim((string)$r['txt']);
            if ($txt !== '' && preg_match('/[\x{0E80}-\x{0EFF}]/u', $txt)) {   // ແປສະເພາະທີ່ມີຕົວລາວ (ຊື່ອັງກິດລ້ວນ ບໍ່ຕ້ອງແປ)
                $items[(int)$r['id']] = $txt;
            }
        }
        if (!$items) {
            continue;
        }
        $have = [];
        $q = $pdo->prepare("SELECT entity_id, lang, source_hash FROM w_translations WHERE entity = ? AND field = ?");
        $q->execute([$entity, $field]);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $have[(int)$r['entity_id']][$r['lang']] = $r['source_hash'];
        }
        foreach ($items as $id => $txt) {
            $hash = md5($txt);
            foreach (DB_TR_LANGS as $lang) {
                if (($have[$id][$lang] ?? '') === $hash) {
                    continue;
                }
                if ($stats['done'] + $stats['failed'] >= $maxCalls) {
                    $stats['left']++;
                    continue;
                }
                $tr = chatTranslate($txt, 'lo', $lang);
                if ($tr === null || trim($tr) === '') {
                    $stats['failed']++;
                    continue;
                }
                $upsert->execute([$entity, $id, $field, $lang, $hash, mb_substr(trim($tr), 0, 500)]);
                $stats['done']++;
            }
        }
    }
    return $stats;
}
