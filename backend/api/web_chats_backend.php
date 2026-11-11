<?php
/**
 * ແຊັດຈາກເວັບໄຊ (ໜ້າຕິດຕໍ່ frontend/pages/contact.php) — controller ຂອງ pages/web_chats/index.php ແລະ AJAX (JSON):
 *   GET  ajax=list                      → ລາຍການຫ້ອງ (ໃໝ່ສຸດກ່ອນ) + ຈຳນວນທີ່ຍັງບໍ່ອ່ານ
 *   GET  ajax=messages chat_id, after   → ຂໍ້ຄວາມໃໝ່ຂອງຫ້ອງ (ໝາຍວ່າພະນັກງານອ່ານແລ້ວ: staff_read_id) + chat.visitor_read_id (ລູກຄ້າເຫັນຮອດ id ໃດ)
 *   POST action=reply  chat_id, text, translate → ພະນັກງານຕອບ (ບັອດຢຸດຕອບຫ້ອງນີ້ 30 ນາທີ — frontend/models/chat.php);
 *                                       translate=1 → ແປເປັນພາສາຂອງລູກຄ້າ (w_web_chats.lang) ກ່ອນສົ່ງ
 * ຫຼາຍພາສາ: ຂໍ້ຄວາມລູກຄ້າທີ່ບໍ່ແມ່ນລາວ ຖືກແປເປັນລາວ (body_tr) ຕອນເປີດອ່ານ — frontend/includes/translate.php
 *   POST action=voice  chat_id, audio (file), secs → ພະນັກງານສົ່ງຂໍ້ຄວາມສຽງ (frontend/includes/chat_audio.php)
 *   POST action=file   chat_id, file      → ພະນັກງານສົ່ງຮູບ / ວິດີໂອ / ໄຟລ໌ (frontend/includes/chat_file.php)
 *   POST action=ai     chat_id, on      → ເປີດ/ປິດ ບັອດຂອງຫ້ອງ
 *   POST action=status chat_id, status  → open / closed
 * ສິດ: ໂມດູນ customers (ເມນູ "ຈັດການລູກຄ້າ"). ຕາຕະລາງ w_web_chats / w_web_chat_messages (config/migrations/schema_website.php v25).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($base_path)) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base_path = (basename($scriptDir) === 'pages') ? '../' : '../../';
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../../frontend/includes/translate.php';   // chatDetectLang / chatTranslate (ບໍ່ອີງໄຟລ໌ອື່ນຂອງ frontend)
require_once __DIR__ . '/../../frontend/includes/chat_audio.php';  // chatSaveAudio — ພະນັກງານສົ່ງສຽງ
require_once __DIR__ . '/../../frontend/includes/chat_file.php';   // chatSaveFile / chatFileJson — ໄຟລ໌ແນບໃນແຊັດ

$isAjax = isset($_GET['ajax']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']));
if (empty($_SESSION['user_id']) || !hasPermission('customers')) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'forbidden']);
        exit();
    }
    echo "<script>window.top.location.href = '" . $base_path . "index.php';</script>";
    exit();
}

$wcJson = function (array $data, int $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
};
// URL root ຂອງໂປຣເຈັກ (/websizewlaodev/) — ໄຟລ໌ສຽງເກັບເປັນ path ຈາກ root (backend/assets/web_chat_audio/...)
$wcScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/');
$wcRoot = ($p = strpos($wcScript, '/backend/')) !== false ? substr($wcScript, 0, $p + 1) : '/';

try {
    if (($_GET['ajax'] ?? '') === 'list') {
        // ສະມາຊິກ (member_id → w_web_members) ສະແດງ "WL-0001"; ບໍ່ແມ່ນ = ລູກຄ້າທົ່ວໄປ
        $rows = $pdo->query("SELECT c.id, c.name, c.tel, c.status, c.ai_enabled, c.unread_staff, c.last_message_at,
                wm.member_no, wm.name AS member_name, wm.avatar_url,
                (SELECT COALESCE(m.body, IF(m.file_path IS NOT NULL, CONCAT('ໄຟລ໌: ', m.file_name), 'ຂໍ້ຄວາມສຽງ')) FROM w_web_chat_messages m WHERE m.chat_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_body
            FROM w_web_chats c LEFT JOIN w_web_members wm ON wm.id = c.member_id
            WHERE c.last_message_at IS NOT NULL ORDER BY c.last_message_at DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['id'] = (int)$r['id'];
            $r['unread_staff'] = (int)$r['unread_staff'];
            $r['ai_enabled'] = (int)$r['ai_enabled'];
            $r['when'] = $r['last_message_at'] ? date('d/m H:i', strtotime($r['last_message_at'])) : '';
            $r['last_body'] = mb_substr((string)$r['last_body'], 0, 60);
            if (!empty($r['avatar_url']) && !preg_match('#^https?://#i', $r['avatar_url'])) {
                $r['avatar_url'] = $wcRoot . ltrim($r['avatar_url'], '/');   // ຮູບທີ່ສະມາຊິກອັບເອງ (backend/assets/img/members/)
            }
        }
        unset($r);
        $wcJson(['success' => true, 'chats' => $rows, 'unread' => array_sum(array_column($rows, 'unread_staff'))]);
    }

    if (($_GET['ajax'] ?? '') === 'messages') {
        $chatId = (int)($_GET['chat_id'] ?? 0);
        $after = (int)($_GET['after'] ?? 0);
        $st = $pdo->prepare("SELECT id, sender, body, body_tr, audio_path, audio_secs, file_path, file_name, file_kind, file_size, staff_name, created_at FROM w_web_chat_messages WHERE chat_id = ? AND id > ? ORDER BY id");
        $st->execute([$chatId, $after]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        // ຂໍ້ຄວາມລູກຄ້າທີ່ບໍ່ແມ່ນພາສາລາວ → ແປເປັນລາວໃຫ້ພະນັກງານອ່ານ (ຄັ້ງດຽວ ເກັບໄວ້ໃນ body_tr; ສູງສຸດ 8 ຂໍ້ຄວາມຕໍ່ request)
        $quota = 8;
        $saveTr = $pdo->prepare("UPDATE w_web_chat_messages SET body_tr = ? WHERE id = ?");
        foreach ($rows as &$m) {
            if ($quota > 0 && $m['sender'] === 'visitor' && $m['body_tr'] === null && trim((string)$m['body']) !== '' && !preg_match('/^(📇|ຊື່: |ເບີໂທ: )/u', (string)$m['body'])) {   // ບັດເບີໂທ (ແບບເກົ່າ 📇 / ແບບໃໝ່) ບໍ່ຕ້ອງແປ
                $from = chatDetectLang((string)$m['body']);
                if ($from !== null && $from !== 'lo') {
                    $quota--;
                    $tr = chatTranslate((string)$m['body'], $from, 'lo');
                    if ($tr !== null) {
                        $saveTr->execute([$tr, $m['id']]);
                        $m['body_tr'] = $tr;
                    }
                }
            }
        }
        unset($m);
        $msgs = array_map(fn($m) => [
            'id' => (int)$m['id'], 'sender' => $m['sender'], 'body' => (string)$m['body'], 'tr' => $m['body_tr'],
            'audio' => $m['audio_path'] ? $wcRoot . $m['audio_path'] : null, 'secs' => $m['audio_secs'] !== null ? (int)$m['audio_secs'] : null,
            'file' => chatFileJson($m, $wcRoot),
            'staff' => $m['staff_name'], 'time' => date('d/m H:i', strtotime($m['created_at'])),
        ], $rows);
        // ພະນັກງານເປີດຫ້ອງນີ້ຢູ່ → ຂໍ້ຄວາມທັງໝົດ = ອ່ານແລ້ວ (ລູກຄ້າເຫັນ ✓✓ ອ່ານແລ້ວ)
        $pdo->prepare("UPDATE w_web_chats SET unread_staff = 0, staff_read_id = GREATEST(staff_read_id,
                          (SELECT COALESCE(MAX(id), 0) FROM w_web_chat_messages WHERE chat_id = ?)) WHERE id = ?")->execute([$chatId, $chatId]);
        $st = $pdo->prepare("SELECT c.id, c.name, c.tel, c.lang, c.status, c.ai_enabled, c.created_at, c.visitor_read_id, wm.member_no, wm.name AS member_name
                             FROM w_web_chats c LEFT JOIN w_web_members wm ON wm.id = c.member_id WHERE c.id = ?");
        $st->execute([$chatId]);
        $chat = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($chat) {
            $chat['lang_name'] = CHAT_LANG_NAMES[$chat['lang'] ?? ''] ?? null;
        }
        $wcJson(['success' => true, 'messages' => $msgs, 'chat' => $chat]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $chatId = (int)($_POST['chat_id'] ?? 0);
        $exists = $pdo->prepare("SELECT COUNT(*) FROM w_web_chats WHERE id = ?");
        $exists->execute([$chatId]);
        if (!$exists->fetchColumn()) {
            $wcJson(['success' => false, 'message' => 'ບໍ່ພົບຫ້ອງແຊັດ'], 404);
        }
        switch ($_POST['action']) {
            case 'reply':
                $text = trim((string)($_POST['text'] ?? ''));
                if ($text === '' || mb_strlen($text) > 2000) {
                    $wcJson(['success' => false, 'message' => 'ຂໍ້ຄວາມຕ້ອງມີ 1–2000 ຕົວອັກສອນ'], 422);
                }
                $staff = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '')) ?: ($_SESSION['username'] ?? 'ພະນັກງານ');
                // ແປເປັນພາສາລູກຄ້າ (translate=1 ແລະ ລູກຄ້າບໍ່ໄດ້ໃຊ້ພາສາດຽວກັບທີ່ພະນັກງານພິມ): ລູກຄ້າເຫັນຄຳແປ, ພະນັກງານເຫັນຕົ້ນສະບັບ (body_tr)
                $st = $pdo->prepare("SELECT lang FROM w_web_chats WHERE id = ?");
                $st->execute([$chatId]);
                $to = (string)$st->fetchColumn();
                $from = chatDetectLang($text) ?? 'lo';
                $body = $text;
                $orig = null;
                $warn = null;
                if (!empty($_POST['translate']) && $to !== '' && $to !== $from) {
                    $tr = chatTranslate($text, $from, $to);
                    if ($tr !== null) {
                        $body = $tr;
                        $orig = $text;
                    } else {
                        $warn = 'ແປບໍ່ໄດ້ (ບໍລິການແປບໍ່ຕອບ) — ສົ່ງເປັນຂໍ້ຄວາມເດີມແລ້ວ';
                    }
                }
                $pdo->prepare("INSERT INTO w_web_chat_messages (chat_id, sender, body, body_tr, staff_name) VALUES (?, 'staff', ?, ?, ?)")->execute([$chatId, $body, $orig, $staff]);
                memberNotifyChat($pdo, $chatId, $body);   // ກະດິ່ງແຈ້ງເຕືອນສະມາຊິກ (frontend/includes/member_notify.php)
                $pdo->prepare("UPDATE w_web_chats SET last_message_at = NOW(), status = 'open', unread_staff = 0 WHERE id = ?")->execute([$chatId]);
                $wcJson(['success' => true, 'warning' => $warn]);
            case 'voice':
                // ພະນັກງານສົ່ງຂໍ້ຄວາມສຽງ (ອັດຈາກ pages/web_chats/index.php) — ລູກຄ້າເຫັນເປັນເຄື່ອງຫຼິ້ນສຽງ ຊື່ Wlaodev
                $path = chatSaveAudio($_FILES['audio'] ?? []);
                if ($path === null) {
                    $wcJson(['success' => false, 'message' => 'ສົ່ງສຽງບໍ່ສຳເລັດ (ໄຟລ໌ໃຫຍ່ເກີນ 3MB ຫຼື ບໍ່ຮອງຮັບ)'], 422);
                }
                $staff = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '')) ?: ($_SESSION['username'] ?? 'ພະນັກງານ');
                $secs = max(1, min(600, (int)($_POST['secs'] ?? 0)));
                $pdo->prepare("INSERT INTO w_web_chat_messages (chat_id, sender, audio_path, audio_secs, staff_name) VALUES (?, 'staff', ?, ?, ?)")
                    ->execute([$chatId, $path, $secs, $staff]);
                memberNotifyChat($pdo, $chatId, 'ຂໍ້ຄວາມສຽງ');
                $pdo->prepare("UPDATE w_web_chats SET last_message_at = NOW(), status = 'open', unread_staff = 0 WHERE id = ?")->execute([$chatId]);
                $wcJson(['success' => true]);
            case 'file':
                $f = chatSaveFile($_FILES['file'] ?? [], 'staff');   // ໄຟລ໌ພະນັກງານ → web_chat_files/staff/...
                if (isset($f['error'])) {
                    $wcJson(['success' => false, 'message' => sprintf($f['error'], $f['mb'] ?? 0)], 422);
                }
                $staff = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? '')) ?: ($_SESSION['username'] ?? 'ພະນັກງານ');
                $pdo->prepare("INSERT INTO w_web_chat_messages (chat_id, sender, file_path, file_name, file_kind, file_size, staff_name) VALUES (?, 'staff', ?, ?, ?, ?, ?)")
                    ->execute([$chatId, $f['path'], $f['name'], $f['kind'], $f['size'], $staff]);
                memberNotifyChat($pdo, $chatId, 'ໄຟລ໌: ' . $f['name']);
                $pdo->prepare("UPDATE w_web_chats SET last_message_at = NOW(), status = 'open', unread_staff = 0 WHERE id = ?")->execute([$chatId]);
                $wcJson(['success' => true]);
            case 'ai':
                $pdo->prepare("UPDATE w_web_chats SET ai_enabled = ? WHERE id = ?")->execute([!empty($_POST['on']) ? 1 : 0, $chatId]);
                $wcJson(['success' => true]);
            case 'status':
                $status = ($_POST['status'] ?? '') === 'closed' ? 'closed' : 'open';
                $pdo->prepare("UPDATE w_web_chats SET status = ? WHERE id = ?")->execute([$status, $chatId]);
                $wcJson(['success' => true]);
        }
        $wcJson(['success' => false, 'message' => 'unknown action'], 400);
    }
} catch (Throwable $e) {
    error_log('web_chats_backend: ' . $e->getMessage());
    $wcJson(['success' => false, 'message' => 'ເກີດຂໍ້ຜິດພາດ'], 500);
}
