<?php
/**
 * ບັນທຶກໄຟລ໌ສຽງຂອງແຊັດ — ໄຟລ໌ນີ້ບໍ່ອີງໄຟລ໌ອື່ນ (ໃຊ້ທັງ frontend/api/chat.php ຂອງລູກຄ້າ ແລະ backend/api/web_chats_backend.php ຂອງພະນັກງານ).
 * ໄຟລ໌ເກັບໄວ້ backend/assets/web_chat_audio/<ປີເດືອນ>/<ສຸ່ມ>.<ext> (ຢູ່ໃນ .gitignore); w_web_chat_messages.audio_path = path ຈາກ root ຂອງໂປຣເຈັກ.
 */

const CHAT_AUDIO_DIR = 'backend/assets/web_chat_audio';
const CHAT_AUDIO_MAX_BYTES = 3 * 1024 * 1024;

/** ບັນທຶກໄຟລ໌ສຽງທີ່ອັບໂຫຼດ → path ຈາກ root ຂອງໂປຣເຈັກ (null = ໄຟລ໌ບໍ່ຖືກຕ້ອງ) */
function chatSaveAudio(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || $file['size'] <= 0 || $file['size'] > CHAT_AUDIO_MAX_BYTES) {
        return null;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    $ext = match (true) {
        str_contains($mime, 'webm')                         => 'webm',
        str_contains($mime, 'ogg')                          => 'ogg',
        str_contains($mime, 'mp4'), str_contains($mime, 'm4a'), str_contains($mime, 'aac') => 'm4a',
        str_contains($mime, 'mpeg')                         => 'mp3',
        str_contains($mime, 'wav')                          => 'wav',
        default                                             => null,
    };
    if ($ext === null) {
        return null;
    }
    $rel = CHAT_AUDIO_DIR . '/' . date('Ym') . '/' . bin2hex(random_bytes(12)) . '.' . $ext;
    $abs = dirname(__DIR__, 2) . '/' . $rel;   // frontend/includes → root ຂອງໂປຣເຈັກ
    if (!is_dir(dirname($abs)) && !@mkdir(dirname($abs), 0775, true)) {
        return null;
    }
    $guard = dirname(__DIR__, 2) . '/' . CHAT_AUDIO_DIR . '/.htaccess';   // ບໍ່ສະແດງລາຍຊື່ໄຟລ໌ ແລະ ບໍ່ແລ່ນ script ໃນໂຟນເດີນີ້
    if (!is_file($guard)) {
        @file_put_contents($guard, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|html?)$\">\n    Require all denied\n</FilesMatch>\n");
    }
    return move_uploaded_file($file['tmp_name'], $abs) ? $rel : null;
}
