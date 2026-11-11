<?php
/**
 * ແຈ້ງເຕືອນຂອງສະມາຊິກ (JSON) — ກະດິ່ງໃນເມນູ (components/member_bell.php ↔ assets/js/member_bell.js)
 *   GET  ?unread=1          → {success, unread}                  (ດຶງທຸກ 30 ວິ ຕອນແທັບເປີດຢູ່)
 *   GET                     → {success, unread, items: [...]}    (ເປີດກະດິ່ງ)
 *   POST action=read [id]   → ໝາຍອ່ານແລ້ວ 1 ລາຍການ (id) ຫຼື ທັງໝົດ (ບໍ່ມີ id) — ຕ້ອງມີ csrf
 * ສະເພາະສະມາຊິກທີ່ເຂົ້າສູ່ລະບົບ. ກົດການ: models/member_notifications.php
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
header('Cache-Control: no-store');

$member = $pdo ? currentMember($pdo) : null;
if (!$member) {
    jsonOut(['success' => false, 'unread' => 0, 'items' => []], 401);
}
$mid = (int)$member['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!checkCsrf()) {
        jsonOut(['success' => false], 419);
    }
    if (($_POST['action'] ?? '') === 'read') {
        memberNotifMarkRead($pdo, $mid, (int)($_POST['id'] ?? 0));
    }
    jsonOut(['success' => true, 'unread' => memberNotifUnread($pdo, $mid)]);
}

if (!empty($_GET['unread'])) {
    jsonOut(['success' => true, 'unread' => memberNotifUnread($pdo, $mid)]);
}
jsonOut(['success' => true, 'unread' => memberNotifUnread($pdo, $mid), 'items' => memberNotifList($pdo, $mid, 20)]);
