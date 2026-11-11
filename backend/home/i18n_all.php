<?php
/**
 * ຄຳແປທຸກພາສາ (window.I18N_ALL_MERGED) ເປັນໄຟລ໌ JavaScript ແຍກ ໃຫ້ browser ເກັບ cache ໄດ້.
 * ເມື່ອກ່ອນຝັງຢູ່ໃນ home/dashboard.php ທຸກຄັ້ງທີ່ເປີດ (~1 MB) → ດຽວນີ້ໂຫຼດຄັ້ງດຽວ ແລ້ວໃຊ້ cache.
 * ເອີ້ນຈາກ: home/partials/dashboard/js_language.php ດ້ວຍ ?v=<ເວລາແກ້ໄຂລ່າສຸດຂອງໄຟລ໌ໃນ lang/>
 *   → ແກ້ໄຟລ໌ພາສາເມື່ອໃດ v ປ່ຽນ, browser ໂຫຼດໃໝ່ເອງ.
 * ບໍ່ include config/db.php (ບໍ່ຕ້ອງ session / DB) — ຄຳແປບໍ່ແມ່ນຂໍ້ມູນລັບ.
 * ໃຊ້ໂດຍ: js_language.php (ສະຫຼັບພາສາທັນທີ), pages/reports/partials/reports_kpi.php,
 *         pages/reports/partials/daily_report/charts_js.php (ຜ່ານ window.parent).
 */
require_once __DIR__ . '/../lang/translator.php';

header('Content-Type: application/javascript; charset=utf-8');
header('X-Content-Type-Options: nosniff');
if (isset($_GET['v'])) {
    header('Cache-Control: public, max-age=31536000, immutable');
} else {
    header('Cache-Control: no-cache');
}

$all = [];
foreach (POS_SUPPORTED_LANGS as $lc) {
    $all[$lc] = loadLangDictionary($lc);
}
echo 'window.I18N_ALL_MERGED = ' . json_encode($all, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ';';
