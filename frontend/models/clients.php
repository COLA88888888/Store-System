<?php
/**
 * ໂລໂກ້ລູກຄ້າ (ໜ້າຫຼັກ components/clients.php)
 * ໃຊ້ຮູບຕົ້ນສະບັບໂດຍກົງຈາກ backend/assets/img/clients/ (ບໍ່ລົບພື້ນຫຼັງ ບໍ່ຕັດຮູບ ຕາມທີ່ລູກຄ້າອັບໂຫຼດ)
 */

const CLIENT_LOGO_DIR = 'backend/assets/img/clients';

/**
 * ຄືນ URL ຂອງໂລໂກ້ລູກຄ້າ 1 ໄຟລ໌ (ຮູບຕົ້ນສະບັບຕາມທີ່ອັບໂຫຼດ)
 */
function clientLogoUrl(?string $logoFile): ?string
{
    if (empty($logoFile)) {
        return null;
    }
    $projectDir = dirname(__DIR__, 2);
    $fn = basename($logoFile);
    $file = $projectDir . '/' . CLIENT_LOGO_DIR . '/' . $fn;
    if (!is_file($file)) {
        return null;
    }
    return backendUrl('assets/img/clients/' . rawurlencode($fn)) . '?v=' . filemtime($file);
}

/**
 * @return array<int, array{url: string, name: string, shop_name?: string}>
 */
function getClientLogos(): array
{
    global $pdo;
    $projectDir = dirname(__DIR__, 2);
    $logos = [];
    $usedFiles = [];

    // 1) ໂລໂກ້ລູກຄ້າ ທີ່ອັບໂຫຼດຈາກຫຼັງບ້ານ (w_web_works)
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            $rows = $pdo->query("SELECT shop_name, logo_img FROM w_web_works WHERE logo_img IS NOT NULL AND logo_img <> '' ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $fn = basename((string)$r['logo_img']);
                $file = $projectDir . '/' . CLIENT_LOGO_DIR . '/' . $fn;
                if (!is_file($file)) {
                    continue;
                }
                $usedFiles[$fn] = true;
                $base = pathinfo($file, PATHINFO_FILENAME);
                $shopName = trim((string)$r['shop_name']);
                $logos[] = [
                    'url'       => backendUrl('assets/img/clients/' . rawurlencode($fn)) . '?v=' . filemtime($file),
                    'name'      => $shopName !== '' ? $shopName : trim(preg_replace('/[-_]+/', ' ', preg_replace('/^\d+[-_]/', '', $base))),
                    'shop_name' => $shopName,
                ];
            }
        } catch (Throwable $e) {
            // ຕາຕະລາງຍັງບໍ່ທັນສ້າງ → ຂ້າມໄປອ່ານຈາກໂຟນເດີ
        }
    }

    // 2) ໄຟລ໌ໂລໂກ້ອື່ນໆທີ່ວາງໄວ້ໃນ backend/assets/img/clients/ ໂດຍກົງ
    $files = glob($projectDir . '/' . CLIENT_LOGO_DIR . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);
    foreach ($files as $file) {
        $fn = basename($file);
        if (isset($usedFiles[$fn])) {
            continue;
        }
        $base = pathinfo($file, PATHINFO_FILENAME);
        $logos[] = [
            'url'  => backendUrl('assets/img/clients/' . rawurlencode($fn)) . '?v=' . filemtime($file),
            'name' => trim(preg_replace('/[-_]+/', ' ', preg_replace('/^\d+[-_]/', '', $base))),
        ];
    }

    // ເຮັດຄວາມສະອາດ cache ເກົ່າທີ່ເຄີຍລົບພື້ນຫຼັງ (ຖ້າມີ)
    $cacheDir = dirname(__DIR__) . '/assets/img/clients';
    if (is_dir($cacheDir)) {
        foreach (glob($cacheDir . '/*-v3-*.png') ?: [] as $cached) {
            @unlink($cached);
        }
    }

    return $logos;
}
