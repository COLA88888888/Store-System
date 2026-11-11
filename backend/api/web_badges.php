<?php
/**
 * ຕົວເລກແຈ້ງເຕືອນໃນເມນູຂ້າງ (layouts/sidebar.php) — JSON, ດຶງທຸກ 20 ວິ (ຂ້າມແທັບທີ່ເຊື່ອງ):
 *   orders = ອໍເດີເວັບໄຊໃໝ່ທີ່ຍັງບໍ່ຢືນຢັນ (status pending), slips = ໃນນັ້ນມີສະລິບລໍຖ້າກວດ,
 *   chats  = ຂໍ້ຄວາມແຊັດຈາກລູກຄ້າທີ່ພະນັກງານຍັງບໍ່ອ່ານ (w_web_chats.unread_staff)
 * ສິດ: ເຫັນສະເພາະຕົວເລກຂອງເມນູທີ່ມີສິດ (sale/customers). ໃຊ້ຮ່ວມ webBadgeCounts() ຕອນວາດເມນູ.
 */
session_start();
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit();
}
echo json_encode(['success' => true] + webBadgeCounts($pdo));
