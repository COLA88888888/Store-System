<?php
/**
 * ແບນເນີໜ້າຫຼັກ — ອ່ານຮູບທັງໝົດໃນ backend/assets/img/baner/ (ລຽງຕາມຊື່ໄຟລ໌).
 * ເພີ່ມ/ລຶບ ແບນເນີ = ເອົາຮູບໃສ່/ອອກ ໂຟນເດີນັ້ນ; ມີຫຼາຍກວ່າ 1 ຮູບ → ສະໄລເລື່ອນອັດຕະໂນມັດ (components/banner.php + assets/js/main.js).
 * ຮູບຕົ້ນສະບັບໃຫຍ່ (PNG ຫຼາຍ MB) ຈຶ່ງສ້າງສຳເນົາ WebP ກວ້າງ 1920px ໄວ້ທີ່ assets/img/banner/ ຄັ້ງທຳອິດທີ່ເປີດ
 * (ຊື່ໄຟລ໌ມີ filemtime — ແກ້ຮູບຕົ້ນສະບັບແລ້ວຈະສ້າງໃໝ່ເອງ). ສ້າງບໍ່ໄດ້ → ໃຊ້ຮູບຕົ້ນສະບັບ.
 */

const BANNER_SOURCE_DIR = 'backend/assets/img/baner';
const BANNER_MAX_WIDTH = 1920;

/** @return array<int, array{url: string, width: int, height: int}> */
function getBanners(): array
{
    $projectDir = dirname(__DIR__, 2);
    $files = glob($projectDir . '/' . BANNER_SOURCE_DIR . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
    sort($files, SORT_NATURAL | SORT_FLAG_CASE);

    // ລຶບສຳເນົາ WebP ຂອງຮູບທີ່ຖືກເອົາອອກຈາກໂຟນເດີແລ້ວ
    $sourceNames = array_map(fn($f) => pathinfo($f, PATHINFO_FILENAME), $files);
    foreach (glob(dirname(__DIR__) . '/assets/img/banner/*.webp') ?: [] as $cached) {
        $source = preg_replace('/-\d+-\d+$/', '', pathinfo($cached, PATHINFO_FILENAME));
        if (!in_array($source, $sourceNames, true)) {
            @unlink($cached);
        }
    }

    $banners = [];
    foreach ($files as $file) {
        $size = @getimagesize($file);
        if (!$size) {
            continue;
        }
        $slug = pathinfo($file, PATHINFO_FILENAME);
        $optimized = bannerOptimized($file, $size);
        if ($optimized) {
            $item = $optimized;
        } else {
            $item = [
                'url'    => siteRoot() . BANNER_SOURCE_DIR . '/' . rawurlencode(basename($file)),
                'width'  => (int)$size[0],
                'height' => (int)$size[1],
            ];
        }
        $item['slug'] = $slug;
        $banners[] = $item;
    }
    return $banners;
}

/** ແບນເນີຕາມຊື່ໄຟລ໌ (ບໍ່ມີນາມສະກຸນ) ເຊັ່ນ 'wlaodev'; ບໍ່ພົບ → ຮູບທຳອິດ, ບໍ່ມີຮູບເລີຍ → null */
function getBannerBySlug(string $slug): ?array
{
    $banners = getBanners();
    foreach ($banners as $b) {
        if ($b['slug'] === $slug) {
            return $b;
        }
    }
    return $banners[0] ?? null;
}

function bannerOptimized(string $file, array $size): ?array
{
    if (!function_exists('imagewebp')) {
        return null;
    }
    [$w, $h] = [(int)$size[0], (int)$size[1]];
    $newW = min($w, BANNER_MAX_WIDTH);
    $newH = (int)round($h * $newW / $w);
    $name = pathinfo($file, PATHINFO_FILENAME) . '-' . filemtime($file) . '-' . $newW . '.webp';
    $cacheDir = dirname(__DIR__) . '/assets/img/banner';
    $target = $cacheDir . '/' . $name;

    if (!is_file($target)) {
        if (!is_dir($cacheDir) && !@mkdir($cacheDir, 0775, true)) {
            return null;
        }
        $src = match ($size[2]) {
            IMAGETYPE_PNG  => @imagecreatefrompng($file),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file),
            default        => false,
        };
        if (!$src) {
            return null;
        }
        $dst = imagecreatetruecolor($newW, $newH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);
        $ok = @imagewebp($dst, $target . '.tmp', 85) && @rename($target . '.tmp', $target);
        imagedestroy($src);
        imagedestroy($dst);
        if (!$ok) {
            @unlink($target . '.tmp');
            return null;
        }
        // ລຶບສຳເນົາເກົ່າຂອງຮູບດຽວກັນ (ກ່ອນແກ້ໄຂ)
        foreach (glob($cacheDir . '/' . pathinfo($file, PATHINFO_FILENAME) . '-[0-9]*-[0-9]*.webp') ?: [] as $old) {
            if ($old !== $target) {
                @unlink($old);
            }
        }
    }
    return ['url' => url('assets/img/banner/' . rawurlencode($name)), 'width' => $newW, 'height' => $newH];
}
