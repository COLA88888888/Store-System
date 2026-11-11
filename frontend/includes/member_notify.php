<?php
/**
 * ຂຽນແຈ້ງເຕືອນສະມາຊິກ (ກະດິ່ງໃນເມນູເວັບໄຊ) ລົງ w_web_member_notifications (migration v44 ໃນ backend/config/migrations/schema_website.php).
 * ບໍ່ອາໄສຟັງຊັນອື່ນ — ຖືກ require ທັງຈາກ frontend/includes/bootstrap.php ແລະ backend/config/helpers/web_mail.php.
 * ເກັບ "ປະເພດ + ຂໍ້ມູນ" (ບໍ່ເກັບຂໍ້ຄວາມ) → ແປເປັນພາສາຂອງເວັບຕອນສະແດງ (frontend/models/member_notifications.php).
 * ເອີ້ນຈາກ:
 *   welcome      frontend/api/member.php (ລົງທະບຽນ / Google ຄັ້ງທຳອິດ)
 *   order_new    frontend/models/orders.php (ສັ່ງຊື້ສຳເລັດ)
 *   order_status / tracking   backend/config/helpers/web_mail.php webMailOrderStatus() (ພະນັກງານປ່ຽນສະຖານະ / ໃສ່ເລກໃບຝາກເຄື່ອງ)
 *   price_drop   backend/config/helpers/web_mail.php webMailPriceDrop() → ສະມາຊິກທຸກຄົນ
 *   chat         backend/api/web_chats_backend.php (ພະນັກງານຕອບແຊັດ ຂໍ້ຄວາມ / ສຽງ / ໄຟລ໌)
 * ບໍ່ເຄີຍ throw — ແຈ້ງເຕືອນລົ້ມເຫຼວ ບໍ່ກະທົບວຽກຫຼັກ.
 */

if (!function_exists('memberNotify')) {
    /** ແຈ້ງເຕືອນສະມາຊິກ 1 ຄົນ */
    function memberNotify(PDO $pdo, int $memberId, string $kind, array $data = [], string $link = ''): void
    {
        if ($memberId <= 0) {
            return;
        }
        try {
            $pdo->prepare("INSERT INTO w_web_member_notifications (member_id, kind, data, link) VALUES (?, ?, ?, ?)")
                ->execute([$memberId, $kind, json_encode($data, JSON_UNESCAPED_UNICODE), $link !== '' ? $link : null]);
        } catch (Throwable $e) {
            error_log('memberNotify: ' . $e->getMessage());
        }
    }

    /** ແຈ້ງເຕືອນສະມາຊິກທຸກຄົນທີ່ຍັງໃຊ້ງານ (ເຊັ່ນ ລົດລາຄາ). ລິ້ງດຽວກັນ + ປະເພດດຽວກັນ ພາຍໃນ $dedupeHours ຊົ່ວໂມງ → ບໍ່ແຈ້ງຊ້ຳ */
    function memberNotifyAll(PDO $pdo, string $kind, array $data, string $link, int $dedupeHours = 12): void
    {
        try {
            $dup = $pdo->prepare("SELECT 1 FROM w_web_member_notifications WHERE kind = ? AND link = ? AND created_at > NOW() - INTERVAL ? HOUR LIMIT 1");
            $dup->execute([$kind, $link, $dedupeHours]);
            if ($dup->fetch()) {
                return;
            }
            $pdo->prepare("INSERT INTO w_web_member_notifications (member_id, kind, data, link)
                           SELECT id, ?, ?, ? FROM w_web_members WHERE status = 'active'")
                ->execute([$kind, json_encode($data, JSON_UNESCAPED_UNICODE), $link]);
        } catch (Throwable $e) {
            error_log('memberNotifyAll: ' . $e->getMessage());
        }
    }

    /** ພະນັກງານຕອບແຊັດ: ຖ້າມີແຈ້ງເຕືອນແຊັດທີ່ຍັງບໍ່ອ່ານຢູ່ແລ້ວ → ອັບເດດຂໍ້ຄວາມ + ເວລາ (ບໍ່ສ້າງແຖວໃໝ່ທຸກຂໍ້ຄວາມ) */
    function memberNotifyChat(PDO $pdo, int $chatId, string $preview): void
    {
        try {
            $st = $pdo->prepare("SELECT member_id FROM w_web_chats WHERE id = ?");
            $st->execute([$chatId]);
            $memberId = (int)$st->fetchColumn();
            if ($memberId <= 0) {
                return;
            }
            $data = json_encode(['preview' => mb_substr(trim($preview), 0, 80)], JSON_UNESCAPED_UNICODE);
            $up = $pdo->prepare("UPDATE w_web_member_notifications SET data = ?, created_at = NOW() WHERE member_id = ? AND kind = 'chat' AND is_read = 0");
            $up->execute([$data, $memberId]);
            if ($up->rowCount() === 0) {
                $pdo->prepare("INSERT INTO w_web_member_notifications (member_id, kind, data, link) VALUES (?, 'chat', ?, 'pages/contact.php')")
                    ->execute([$memberId, $data]);
            }
        } catch (Throwable $e) {
            error_log('memberNotifyChat: ' . $e->getMessage());
        }
    }
}
