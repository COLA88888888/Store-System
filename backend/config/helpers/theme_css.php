<?php
/**
 * ສ່ວນ: ຟັງຊັນຊ່ວຍ: ລິ້ງໄຟລ໌ CSS ໃນໂຟນເດີ themes/
 * themeCssLink('reports.css') → <link rel="stylesheet" href="<base_path>themes/reports.css?v=<filemtime>">
 *   - path ອີງຕາມ $base_path ຂອງໜ້າ (ຫຼື ສົ່ງ $base ເອງ ສຳລັບໜ້າທີ່ບໍ່ມີ $base_path)
 *   - ?v= ແມ່ນເວລາແກ້ໄຂໄຟລ໌ → browser ໂຫຼດໃໝ່ທັນທີເມື່ອແກ້ CSS (ບໍ່ຕ້ອງ Ctrl+F5)
 * CSS ທີ່ເຄີຍຂຽນປົນຢູ່ໃນໂຄ້ດ (<style>) ຖືກຍ້າຍມາໄວ້ themes/ ແລ້ວເອີ້ນຜ່ານຟັງຊັນນີ້ ຢູ່ຈຸດເດີມ
 * (ລຳດັບ cascade ຄືເກົ່າ).
 * ໄຟລ໌ນີ້ຖືກ require ຈາກ config/db.php.
 */
if (!function_exists('themeCssLink')) {
    function themeCssLink($file, $base = null) {
        $file = ltrim(str_replace('\\', '/', (string)$file), '/');
        if ($base === null) {
            $base = $GLOBALS['base_path'] ?? '';
        }
        $abs = dirname(__DIR__, 2) . '/themes/' . $file;
        $ver = is_file($abs) ? filemtime($abs) : 0;
        return '<link rel="stylesheet" href="' . htmlspecialchars($base . 'themes/' . $file, ENT_QUOTES) . '?v=' . $ver . '">';
    }
}
