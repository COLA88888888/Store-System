<?php
/**
 * API ຫ້ອງແຊັດໜ້າຕິດຕໍ່ (JSON) — ເອີ້ນຈາກ assets/js/chat.js. ຫ້ອງ = session ນີ້ (models/chat.php).
 *   GET  ?action=stickers                 → {success, version, groups} (ລາຍການສະຕິກເກີ — models/chat_stickers.php)
 *   GET  ?after=ID                         → {success, messages[], need_contact, has_chat, staff_read}  (ດຶງຂໍ້ຄວາມໃໝ່ທຸກໆ 4 ວິ)
 *   ທຸກຄຳຕອບ: ບັນທຶກວ່າລູກຄ້າເຫັນຂໍ້ຄວາມແລ້ວ (visitor_read_id) + ສົ່ງ staff_read ກັບ (ອ່ານແລ້ວ / ຍັງບໍ່ອ່ານ ໃຕ້ຂໍ້ຄວາມລູກຄ້າ)
 *   POST action=send    text               → ບັນທຶກ + ບັອດຕອບ → ຂໍ້ຄວາມໃໝ່ທັງໝົດຫຼັງ after
 *   POST action=voice   audio (file), secs → ບັນທຶກໄຟລ໌ສຽງ + ບັອດແຈ້ງວ່າພະນັກງານຈະຟັງ
 *   POST action=file    file (ຮູບ/ວິດີໂອ/ເອກະສານ) → ບັນທຶກໄຟລ໌ແນບ (includes/chat_file.php) — ເກັບໄວ້ເບິ່ງຄືນໃນ "ມີເດຍ ແລະ ໄຟລ໌"
 *   POST action=location lat, lng          → ຂໍ້ຄວາມຕຳແໜ່ງ (ລິ້ງແຜນທີ່) + ຄຳຕອບນຳທາງມາຮ້ານ ພ້ອມໄລຍະທາງ
 *   POST action=contact name, tel          → ບັນທຶກຊື່ ແລະ ເບີໂທຂອງຫ້ອງ
 * ທຸກ POST ຕ້ອງມີ CSRF token (header X-CSRF-Token) ແລະ ຕ້ອງເປັນສະມາຊິກ (ຄົນທົ່ວໄປ → 401 need_member).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

if (!$pdo) {
    jsonOut(['success' => false, 'message' => t('ລະບົບບໍ່ພ້ອມໃຊ້ງານ')], 503);
}
$after = max(0, (int)($_REQUEST['after'] ?? 0));
$reply = function (?array $chat) use ($pdo, $after) {
    $staffRead = 0;
    if ($chat) {
        // ລູກຄ້າເປີດແຊັດຢູ່ (JS ດຶງສະເພາະຕອນແທັບເປີດ) → ຂໍ້ຄວາມທັງໝົດໃນຫ້ອງ = ລູກຄ້າເຫັນແລ້ວ (ພະນັກງານເຫັນ "ອ່ານແລ້ວ")
        $pdo->prepare("UPDATE w_web_chats SET visitor_read_id = GREATEST(visitor_read_id,
                          (SELECT COALESCE(MAX(id), 0) FROM w_web_chat_messages WHERE chat_id = ?)) WHERE id = ?")
            ->execute([$chat['id'], $chat['id']]);
        $st = $pdo->prepare("SELECT staff_read_id FROM w_web_chats WHERE id = ?");
        $st->execute([$chat['id']]);
        $staffRead = (int)$st->fetchColumn();
    }
    jsonOut([
        'success'      => true,
        'messages'     => $chat ? chatMessages($pdo, (int)$chat['id'], $after) : [],
        'need_contact' => !$chat || empty($chat['tel']),
        'has_chat'     => (bool)$chat,
        'staff_read'   => $staffRead,   // ຂໍ້ຄວາມຂອງລູກຄ້າທີ່ id ≤ ນີ້ = ພະນັກງານອ່ານແລ້ວ
    ]);
};

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        if (($_GET['action'] ?? '') === 'stickers') {
            jsonOut(['success' => true, 'version' => CHAT_STICKERS_VERSION, 'groups' => chatStickers()]);
        }
        $reply(chatCurrent($pdo));
    }
    if (!checkCsrf()) {
        jsonOut(['success' => false, 'message' => t('ໝົດເວລາ ກະລຸນາໂຫຼດໜ້າໃໝ່')], 419);
    }
    if (!currentMember($pdo)) {   // ພິມແຊັດ / ສົ່ງສຽງ / ຕຳແໜ່ງ ໄດ້ສະເພາະສະມາຊິກ (ໜ້າເວັບສະແດງແຖບລົງທະບຽນແທນ)
        jsonOut(['success' => false, 'need_member' => true, 'message' => t('ກະລຸນາລົງທະບຽນ ຫຼື ເຂົ້າສູ່ລະບົບ ກ່ອນພິມແຊັດ — ສະມາຊິກແຊັດ, ເບິ່ງລາຄາ ແລະ ສັ່ງຊື້ໄດ້ເລີຍ')], 401);
    }
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'contact') {
        $name = mb_substr(trim((string)($_POST['name'] ?? '')), 0, 150);
        $tel = mb_substr(trim((string)($_POST['tel'] ?? '')), 0, 50);
        if ($tel === '' || !preg_match('/\d{6,}/', preg_replace('/\D/', '', $tel))) {
            jsonOut(['success' => false, 'message' => t('ກະລຸນາປ້ອນເບີໂທໃຫ້ຖືກຕ້ອງ')], 422);
        }
        $chat = chatCurrent($pdo, true);
        $pdo->prepare("UPDATE w_web_chats SET name = COALESCE(NULLIF(?, ''), name), tel = ? WHERE id = ?")->execute([$name, $tel, $chat['id']]);
        // ບັດເບີໂທ (ບໍ່ມີ emoji): "ຊື່: … · ເບີໂທ: …" — backend/api/web_chats_backend.php ຮູ້ຈັກຄຳນຳໜ້ານີ້ ແລະ ບໍ່ສົ່ງໄປແປ
        chatAdd($pdo, (int)$chat['id'], 'visitor', ($name !== '' ? 'ຊື່: ' . $name . ' · ' : '') . 'ເບີໂທ: ' . $tel);
        chatAdd($pdo, (int)$chat['id'], 'bot', chatT($chat, 'ຂອບໃຈ%s 🙏 ໄດ້ບັນທຶກເບີໂທແລ້ວ ທີມງານຈະໂທກັບພາຍໃນ 24 ຊົ່ວໂມງ. ມີຫຍັງຖາມຕໍ່ໄດ້ເລີຍ.', $name !== '' ? ' ' . $name : ''));
        $chat['tel'] = $tel;
        $reply($chat);
    }

    if ($action === 'send') {
        $text = trim((string)($_POST['text'] ?? ''));
        if ($text === '' || mb_strlen($text) > 1000) {
            jsonOut(['success' => false, 'message' => t('ຂໍ້ຄວາມຕ້ອງມີ 1–1000 ຕົວອັກສອນ')], 422);
        }
        $chat = chatCurrent($pdo, true);
        if (chatTooFast($pdo, (int)$chat['id'])) {
            jsonOut(['success' => false, 'message' => t('ສົ່ງຖີ່ເກີນໄປ ກະລຸນາລໍຖ້າຈັກໜ່ອຍ')], 429);
        }
        chatAdd($pdo, (int)$chat['id'], 'visitor', $text);
        if (chatIsSticker($text)) {   // ສະຕິກເກີ: ບັນທຶກໃຫ້ພະນັກງານເຫັນ, ບັອດບໍ່ຕອບ
            $reply($chat);
        }
        chatUpdateLang($pdo, $chat, $text);   // ລູກຄ້າພິມພາສາໃດ ບັອດຕອບພາສານັ້ນ
        chatCaptureTel($pdo, $chat, $text);
        @set_time_limit(60);
        chatBotRespond($pdo, $chat, $text);
        $reply($chat);
    }

    if ($action === 'location') {
        $lat = filter_var($_POST['lat'] ?? null, FILTER_VALIDATE_FLOAT);
        $lng = filter_var($_POST['lng'] ?? null, FILTER_VALIDATE_FLOAT);
        if ($lat === false || $lng === false || abs($lat) > 90 || abs($lng) > 180) {
            jsonOut(['success' => false, 'message' => t('ຕຳແໜ່ງບໍ່ຖືກຕ້ອງ')], 422);
        }
        $chat = chatCurrent($pdo, true);
        if (chatTooFast($pdo, (int)$chat['id'])) {
            jsonOut(['success' => false, 'message' => t('ສົ່ງຖີ່ເກີນໄປ ກະລຸນາລໍຖ້າຈັກໜ່ອຍ')], 429);
        }
        chatShareLocation($pdo, $chat, $lat, $lng);
        $reply($chat);
    }

    if ($action === 'file') {
        $chat = chatCurrent($pdo, true);
        if (chatTooFast($pdo, (int)$chat['id'])) {
            jsonOut(['success' => false, 'message' => t('ສົ່ງຖີ່ເກີນໄປ ກະລຸນາລໍຖ້າຈັກໜ່ອຍ')], 429);
        }
        $f = chatSaveFile($_FILES['file'] ?? []);
        if (isset($f['error'])) {
            jsonOut(['success' => false, 'message' => isset($f['mb']) ? t($f['error'], $f['mb']) : t($f['error'])], 422);
        }
        $id = chatAdd($pdo, (int)$chat['id'], 'visitor', null);
        $pdo->prepare("UPDATE w_web_chat_messages SET file_path = ?, file_name = ?, file_kind = ?, file_size = ? WHERE id = ?")
            ->execute([$f['path'], $f['name'], $f['kind'], $f['size'], $id]);
        $reply($chat);   // ບັອດບໍ່ຕອບ — ພະນັກງານເປີດເບິ່ງໄຟລ໌ເອງ
    }

    if ($action === 'voice') {
        $chat = chatCurrent($pdo, true);
        if (chatTooFast($pdo, (int)$chat['id'])) {
            jsonOut(['success' => false, 'message' => t('ສົ່ງຖີ່ເກີນໄປ ກະລຸນາລໍຖ້າຈັກໜ່ອຍ')], 429);
        }
        $path = chatSaveAudio($_FILES['audio'] ?? []);
        if ($path === null) {
            jsonOut(['success' => false, 'message' => t('ສົ່ງສຽງບໍ່ສຳເລັດ (ໄຟລ໌ໃຫຍ່ເກີນ ຫຼື ບໍ່ຮອງຮັບ)')], 422);
        }
        $secs = max(1, min(600, (int)($_POST['secs'] ?? 0)));
        chatAdd($pdo, (int)$chat['id'], 'visitor', null, $path, $secs);
        if (chatBotActive($pdo, $chat)) {
            $msg = chatT($chat, 'ໄດ້ຮັບຂໍ້ຄວາມສຽງແລ້ວ ພະນັກງານຈະຟັງ ແລະ ຕອບກັບໃນແຊັດນີ້ໄວໆ.');
            if (empty($chat['tel'])) {
                $msg .= ' ' . chatT($chat, 'ລົບກວນຝາກຊື່ ແລະ ເບີໂທໄວ້ ເພື່ອໃຫ້ພວກເຮົາໂທກັບໄດ້.');
            }
            chatAdd($pdo, (int)$chat['id'], 'bot', $msg);
        }
        $reply($chat);
    }

    jsonOut(['success' => false, 'message' => 'unknown action'], 400);
} catch (Throwable $e) {
    error_log('frontend api/chat: ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => t('ເກີດຂໍ້ຜິດພາດ ກະລຸນາລອງໃໝ່')], 500);
}
