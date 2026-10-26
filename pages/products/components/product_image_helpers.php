<?php
// Product image helpers — shared by products.php (single add / edit) and api/products_api.php
// (bulk-add rows upload their image ahead of the form submit).
// ====== Helper Functions ສຳລັບຈັດການຮູບພາບສິນຄ້າ (ເກັບໄວ້ໃນ assets/product_img) ======
function saveUploadedProductImage($fileInputKey = 'product_img', &$errorMsg = null) {
    if (!isset($_FILES[$fileInputKey]) || empty($_FILES[$fileInputKey]['name'])) {
        return null;
    }
    
    $fileError = $_FILES[$fileInputKey]['error'];
    if ($fileError !== UPLOAD_ERR_OK) {
        if ($fileError === UPLOAD_ERR_INI_SIZE || $fileError === UPLOAD_ERR_FORM_SIZE) {
            $errorMsg = 'ຂະໜາດຮູບພາບໃຫຍ່ເກີນກຳນົດຂອງເຊີເວີ';
        } elseif ($fileError !== UPLOAD_ERR_NO_FILE) {
            $errorMsg = 'ເກີດຂໍ້ຜິດພາດໃນການອັບໂຫຼດຮູບ (Error Code: ' . $fileError . ')';
        }
        return null;
    }

    $maxFileSize = 10 * 1024 * 1024; // 10MB
    if ($_FILES[$fileInputKey]['size'] > $maxFileSize) {
        $errorMsg = 'ຂະໜາດຮູບພາບໃຫຍ່ເກີນ 10MB';
        return null;
    }

    $fileTmp  = $_FILES[$fileInputKey]['tmp_name'];
    $fileName = $_FILES[$fileInputKey]['name'];
    $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'bmp', 'jfif', 'avif'];
    if (!in_array($fileExt, $allowed)) {
        $errorMsg = 'ຮູບແບບໄຟລ໌ບໍ່ຖືກຕ້ອງ (ຮອງຮັບ JPG, PNG, WEBP, GIF, JFIF)';
        return null;
    }

    $img_name = 'prod_' . time() . '_' . mt_rand(1000, 9999) . '.' . $fileExt;

    // ໂຟນເດີເກັບຮູບພາບ — ບ່ອນດຽວ (assets/product_img). ບໍ່ຕ້ອງກັອບປີ້ໄປໂຟນເດີອື່ນອີກຕໍ່ໄປ.
    $dir = __DIR__ . '/../../../assets/product_img/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }

    $targetPath = $dir . $img_name;
    // ປັບຂະໜາດ ແລະ ຈັດກາງຮູບພາບໃຫ້ເທົ່າກັນອັດຕະໂນມັດ (600x600 px) ເຫັນເຕັມສ່ວນທັງໝົດ
    $normalized = false;
    if (extension_loaded('gd')) {
        $normalized = normalizeProductImageGD($fileTmp, $targetPath, 600, 0.90);
    }

    if (!$normalized) {
        if (!@move_uploaded_file($fileTmp, $targetPath)) {
            $errorMsg = 'ບໍ່ສາມາດບັນທຶກໄຟລ໌ລົງເຊີເວີໄດ້ ກະລຸນາກວດສອບ Permission';
            return null;
        }
    }

    return $img_name;
}

/**
 * ປັບຂະໜາດ ແລະ ຈັດກາງຮູບພາບສິນຄ້າໃຫ້ເທົ່າກັນທັງໝົດ (600x600 px)
 * - ຕັດຂອບວ່າງສ່ວນເກີນ (Auto-crop excess borders)
 * - ຮັກສາອັດຕາສ່ວນເດີມ ບໍ່ໃຫ້ບິດບ້ຽວ (Aspect ratio)
 * - ວາງໄວ້ເຄິ່ງກາງພື້ນຫຼັງສີຂາວສະອາດ ເຫັນສິນຄ້າເຕັມສ່ວນ 100% ບໍ່ຖືກຕັດຂອບ
 */
function normalizeProductImageGD($sourcePath, $destPath, $targetSize = 600, $fillRatio = 0.90) {
    if (!file_exists($sourcePath) || !extension_loaded('gd')) return false;
    $raw = @file_get_contents($sourcePath);
    if (!$raw) return false;

    $src = @imagecreatefromstring($raw);
    if (!$src) return false;

    // Handle EXIF orientation if JPEG
    if (function_exists('exif_read_data')) {
        try {
            $exif = @exif_read_data($sourcePath);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3: $src = imagerotate($src, 180, 0); break;
                    case 6: $src = imagerotate($src, -90, 0); break;
                    case 8: $src = imagerotate($src, 90, 0); break;
                }
            }
        } catch (Throwable $e) {}
    }

    $w = imagesx($src);
    $h = imagesy($src);
    if ($w <= 0 || $h <= 0) {
        imagedestroy($src);
        return false;
    }

    // Detect background colors by sampling the corners & edges
    $corners = [
        imagecolorat($src, 2, 2),
        imagecolorat($src, max(0, $w - 3), 2),
        imagecolorat($src, 2, max(0, $h - 3)),
        imagecolorat($src, max(0, $w - 3), max(0, $h - 3)),
        imagecolorat($src, (int)($w / 2), 2),
        imagecolorat($src, 2, (int)($h / 2))
    ];

    $bgColors = [];
    foreach ($corners as $c) {
        $r = ($c >> 16) & 0xFF;
        $g = ($c >> 8) & 0xFF;
        $b = $c & 0xFF;
        $bgColors[] = [$r, $g, $b];
    }

    $isBg = function($r, $g, $b, $a = 0) use ($bgColors) {
        if ($a > 100) return true; // transparent
        if ($r >= 246 && $g >= 246 && $b >= 246) return true; // white
        foreach ($bgColors as $bg) {
            $diff = abs($r - $bg[0]) + abs($g - $bg[1]) + abs($b - $bg[2]);
            if ($diff < 28) return true;
        }
        return false;
    };

    $minX = $w; $minY = $h; $maxX = 0; $maxY = 0;
    $step = max(1, (int)(min($w, $h) / 300));

    for ($y = 0; $y < $h; $y += $step) {
        for ($x = 0; $x < $w; $x += $step) {
            $rgba = imagecolorat($src, $x, $y);
            $r = ($rgba >> 16) & 0xFF;
            $g = ($rgba >> 8) & 0xFF;
            $b = $rgba & 0xFF;
            $a = ($rgba >> 24) & 0x7F;

            if (!$isBg($r, $g, $b, $a)) {
                if ($x < $minX) $minX = $x;
                if ($x > $maxX) $maxX = $x;
                if ($y < $minY) $minY = $y;
                if ($y > $maxY) $maxY = $y;
            }
        }
    }

    $cropW = $maxX - $minX;
    $cropH = $maxY - $minY;

    if ($cropW < 20 || $cropH < 20 || ($cropW > $w * 0.95 && $cropH > $h * 0.95)) {
        $minX = 0;
        $minY = 0;
        $cropW = $w;
        $cropH = $h;
    } else {
        $padX = (int)($cropW * 0.03);
        $padY = (int)($cropH * 0.03);
        $minX = max(0, $minX - $padX);
        $minY = max(0, $minY - $padY);
        $maxX = min($w - 1, $maxX + $padX);
        $maxY = min($h - 1, $maxY + $padY);
        $cropW = $maxX - $minX + 1;
        $cropH = $maxY - $minY + 1;
    }

    $canvas = imagecreatetruecolor($targetSize, $targetSize);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefilledrectangle($canvas, 0, 0, $targetSize, $targetSize, $white);

    $maxDimAllowed = $targetSize * $fillRatio;
    $scale = min($maxDimAllowed / $cropW, $maxDimAllowed / $cropH);

    $scaledW = (int)round($cropW * $scale);
    $scaledH = (int)round($cropH * $scale);

    $dstX = (int)round(($targetSize - $scaledW) / 2);
    $dstY = (int)round(($targetSize - $scaledH) / 2);

    imagecopyresampled(
        $canvas, $src,
        $dstX, $dstY,
        $minX, $minY,
        $scaledW, $scaledH,
        $cropW, $cropH
    );

    $ext = strtolower(pathinfo($destPath, PATHINFO_EXTENSION));
    $success = false;
    if ($ext === 'png') {
        $success = imagepng($canvas, $destPath, 8);
    } elseif ($ext === 'webp' && function_exists('imagewebp')) {
        $success = imagewebp($canvas, $destPath, 90);
    } else {
        $success = imagejpeg($canvas, $destPath, 92);
    }

    imagedestroy($src);
    imagedestroy($canvas);

    return $success;
}

function deleteProductImageFile($imgFile) {
    if (empty($imgFile)) return;
    $filename = basename(trim($imgFile));
    // ປ້ອງກັນບໍ່ໃຫ້ລົບໄຟລ໌ລະບົບ ຫຼື ໄຟລ໌ Default
    $protected = ['image.jpg', 'file.png', '404.png', 'repeat.png', '', '.', '..'];
    if (in_array(strtolower($filename), $protected)) {
        return;
    }
    $filePath = __DIR__ . '/../../../assets/product_img/' . $filename;
    if (file_exists($filePath) && is_file($filePath)) {
        @unlink($filePath);
    }
}

function getProductImagePath($imgFile) {
    if (empty($imgFile)) return null;
    static $imgPathCache = [];
    $key = trim($imgFile);
    if (isset($imgPathCache[$key])) {
        return $imgPathCache[$key];
    }
    $filename = basename($key);
    $res = '../../assets/product_img/' . $filename;
    $imgPathCache[$key] = $res;
    return $res;
}

