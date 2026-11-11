<?php
/**
 * ຜົນງານຕິດຕັ້ງລະບົບໃຫ້ລູກຄ້າ — ອ່ານຈາກຕາຕະລາງ w_web_works (ອັບໂຫຼດຈາກຫຼັງບ້ານ backend/pages/web_works/)
 * ລວມກັບ $siteWorks (includes/content.php), ຮູບຈາກໂຟນເດີ backend/assets/img/works/<slug>/ (ທຸກໄຟລ໌ jpg/png/webp; ຮູບທຳອິດ = ຮູບໃຫຍ່ຂອງບັດ).
 * ໃຊ້ໂດຍ components/works.php (ໜ້າຫຼັກ), pages/portfolio.php (ເມນູ "ຜົນງານ"), pages/work.php.
 */

const WORKS_IMAGE_DIR = 'backend/assets/img/works';

/** @return array<int, array> ລຽງວັນທີໃໝ່ສຸດກ່ອນ (ບໍ່ມີວັນທີ = ທ້າຍ ຕາມລຳດັບເດີມ); $withPhotosOnly = ສະເພາະອັນທີ່ມີຮູບ */
function getWorks(int $limit = 0, bool $withPhotosOnly = false): array
{
    global $pdo, $siteWorks;
    $works = [];
    $seenSlugs = [];
    $order = 0;

    // 1) ຜົນງານທີ່ອັບໂຫຼດຈາກຫຼັງບ້ານ (w_web_works) — ອັບລົງກ່ອນ ຂຶ້ນກ່ອນຕາມລຳດັບ (id ASC)
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $rows = $pdo->query("SELECT * FROM w_web_works ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $slug = trim((string)($r['slug'] ?? ''));
                if ($slug === '') {
                    continue;
                }
                $shopName = trim((string)($r['shop_name'] ?? ''));
                $title = trim((string)($r['title'] ?? ''));
                $w = [
                    'id'          => (int)$r['id'],
                    'slug'        => $slug,
                    'shop_name'   => $shopName,
                    'title'       => $title !== '' ? $title : $shopName,
                    'category'    => trim((string)($r['category'] ?? '')),
                    'date'        => trim((string)($r['work_date'] ?? '')),
                    'text'        => trim((string)($r['description'] ?? '')),
                    'logo'        => function_exists('clientLogoUrl') ? clientLogoUrl($r['logo_img'] ?? null) : null,
                    'photos_list' => json_decode((string)($r['photos_json'] ?? '[]'), true) ?: [],
                ];
                $w['photos'] = workImages($w);
                // ຖ້າອັບໂຫຼດແຕ່ໂລໂກ້ລູກຄ້າ + ຊື່ຮ້ານ (ບໍ່ມີຮູບຜົນງານ ແລະ ບໍ່ມີລາຍລະອຽດ) → ສະແດງສະເພາະໃນສ່ວນໂລໂກ້ລູກຄ້າ
                if (!$w['photos'] && $w['text'] === '') {
                    continue;
                }
                if (!$withPhotosOnly || $w['photos']) {
                    $w['_order'] = $order++;
                    $works[] = $w;
                    $seenSlugs[$slug] = true;
                }
            }
        } catch (Throwable $e) {
            // ຕາຕະລາງຍັງບໍ່ທັນສ້າງ → ໃຊ້ $siteWorks ຕາມປົກກະຕິ
        }
    }

    // 2) ຜົນງານຄົງທີ່ໃນ includes/content.php ($siteWorks)
    foreach ($siteWorks ?? [] as $w) {
        if (isset($seenSlugs[$w['slug'] ?? ''])) {
            continue;
        }
        $w += ['category' => '', 'date' => '', 'shop_name' => '', 'logo' => null];
        $w['photos'] = workImages($w);
        $w['_order'] = $order++;
        if (!$withPhotosOnly || $w['photos']) {
            $works[] = $w;
        }
    }
    return $limit > 0 ? array_slice($works, 0, $limit) : $works;
}

/** ມີຜົນງານແທ້ຢ່າງໜ້ອຍ 1 ລາຍການບໍ — false = ເຊື່ອງເມນູ/ລິ້ງ/ຕົວເລກຜົນງານທັງໝົດ */
function hasWorks(): bool
{
    return !empty(getWorks(1));
}

function getWork(string $slug): ?array
{
    foreach (getWorks() as $w) {
        if ($w['slug'] === $slug) {
            return $w;
        }
    }
    return null;
}

/** @return string[] URL ຂອງຮູບ */
function workImages(array $w): array
{
    $projectDir = dirname(__DIR__, 2);
    $dir = $projectDir . '/' . WORKS_IMAGE_DIR . '/' . $w['slug'];
    $out = [];
    $used = [];

    // ຖ້າມີລຳດັບຮູບຈາກຖານຂໍ້ມູນ (photos_list) ໃຫ້ໃຊ້ຕາມລຳດັບນັ້ນກ່ອນ
    if (!empty($w['photos_list']) && is_array($w['photos_list']) && is_dir($dir)) {
        foreach ($w['photos_list'] as $fn) {
            $fn = basename((string)$fn);
            $f = $dir . '/' . $fn;
            if ($fn !== '' && is_file($f)) {
                $used[$fn] = true;
                $rawUrl = siteRoot() . WORKS_IMAGE_DIR . '/' . rawurlencode($w['slug']) . '/' . rawurlencode($fn) . '?v=' . filemtime($f);
                $out[] = optimizedImageUrl($f, $rawUrl, 1600);
            }
        }
    }

    $files = is_dir($dir) ? (glob($dir . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: []) : [];
    if ($files) {
        sort($files, SORT_NATURAL | SORT_FLAG_CASE);
        foreach ($files as $f) {
            $fn = basename($f);
            if (isset($used[$fn])) {
                continue;
            }
            $rawUrl = siteRoot() . WORKS_IMAGE_DIR . '/' . rawurlencode($w['slug']) . '/' . rawurlencode($fn) . '?v=' . filemtime($f);
            $out[] = optimizedImageUrl($f, $rawUrl, 1600);
        }
    }
    if ($out) {
        return $out;
    }

    foreach ($w['images'] ?? [] as $path) {
        if (is_file($projectDir . '/' . $path)) {
            $out[] = siteRoot() . $path;
        }
    }
    if (!$out && ($single = portfolioImage($w['slug']))) {
        $out[] = $single;
    }
    return $out;
}

function workDate(string $ymd): string
{
    if ($ymd === '') {
        return '';
    }
    $t = strtotime($ymd);
    return $t ? date('d/m/Y', $t) : $ymd;
}
