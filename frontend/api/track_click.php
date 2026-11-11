<?php
/**
 * Beacon ນັບການກົດປຸ່ມຕິດຕໍ່ (ໂທ / WhatsApp / Facebook / ແຊັດ / ແຜນທີ່) — assets/js/main.js ສົ່ງ navigator.sendBeacon
 * ຕອນກົດລິ້ງ tel:, wa.me, facebook, Google Maps ຫຼື ປຸ່ມແຊັດ. ບັນທຶກລົງ w_web_contact_clicks (backend migration v33),
 * ເບິ່ງສະຫຼຸບທີ່ backend/pages/web_chats/. ບໍ່ນັບ bot, ເຄື່ອງທີ່ຕັ້ງ ?nocount ແລະ ຈຳກັດ 30 ຄັ້ງ / session.
 * ຕອບ 204 ສະເໝີ (ບໍ່ມີຂໍ້ມູນໃຫ້ອ່ານ).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

const CLICK_CHANNELS = ['tel', 'whatsapp', 'facebook', 'chat', 'map'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo && !visitIgnored() && !visitIsBot()) {
    $channel = (string)($_POST['channel'] ?? '');
    $count = (int)($_SESSION['click_beacons'] ?? 0);
    if (in_array($channel, CLICK_CHANNELS, true) && $count < 30) {
        $_SESSION['click_beacons'] = $count + 1;
        // ເກັບສະເພາະ path ຂອງໜ້າ (ບໍ່ເກັບ query ທີ່ອາດມີຂໍ້ມູນລູກຄ້າ ຍົກເວັ້ນ slug/id ຂອງສິນຄ້າ)
        $page = (string)($_POST['page'] ?? '');
        $path = (string)parse_url($page, PHP_URL_PATH);
        parse_str((string)parse_url($page, PHP_URL_QUERY), $q);
        $keep = array_intersect_key($q, array_flip(['slug', 'id', 'cat']));
        $page = mb_substr($path . ($keep ? '?' . http_build_query($keep) : ''), 0, 190);
        try {
            $st = $pdo->prepare("INSERT INTO w_web_contact_clicks (channel, page, lang) VALUES (?, ?, ?)");
            $st->execute([$channel, $page, currentLang()]);
        } catch (Throwable $e) {
            error_log('frontend track_click: ' . $e->getMessage());
        }
    }
}
http_response_code(204);
header('Cache-Control: no-store');
