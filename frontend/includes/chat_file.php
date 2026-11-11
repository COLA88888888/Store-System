<?php
/**
 * ໄຟລ໌ແນບໃນແຊັດ (ຮູບ / ວິດີໂອ / ເອກະສານ) — ໄຟລ໌ນີ້ບໍ່ອີງໄຟລ໌ອື່ນ: ໃຊ້ທັງ frontend/api/chat.php (ລູກຄ້າ)
 * ແລະ backend/api/web_chats_backend.php (ພະນັກງານ), ຄືກັບ includes/chat_audio.php.
 * ເກັບແຍກໂຟນເດີ (ບໍ່ປົນກັນ): backend/assets/web_chat_files/<customer|staff>/<images|videos|files>/<ປີເດືອນ>/<ສຸ່ມ>.<ext>
 *   customer = ລູກຄ້າສົ່ງ (api/chat.php), staff = ພະນັກງານສົ່ງ (backend/api/web_chats_backend.php) — ຢູ່ໃນ .gitignore; ຂໍ້ມູນຢູ່ w_web_chat_messages
 * file_path (path ຈາກ root ຂອງໂປຣເຈັກ) / file_name (ຊື່ເດີມ) / file_kind (image|video|file) / file_size (bytes) — migration v43.
 * ກວດຊະນິດຈາກເນື້ອໄຟລ໌ (finfo) ບໍ່ເຊື່ອນາມສະກຸນ; ບໍ່ຮັບ SVG / HTML / script.
 */

const CHAT_FILE_DIR = 'backend/assets/web_chat_files';

/** mime → [kind, ext, ຂະໜາດສູງສຸດ (MB)] */
function chatFileTypes(): array
{
    return [
        'image/jpeg' => ['image', 'jpg', 10], 'image/png' => ['image', 'png', 10],
        'image/gif'  => ['image', 'gif', 10], 'image/webp' => ['image', 'webp', 10],
        'video/mp4'  => ['video', 'mp4', 50], 'video/webm' => ['video', 'webm', 50], 'video/quicktime' => ['video', 'mov', 50],
        'application/pdf' => ['file', 'pdf', 20],
        'application/msword' => ['file', 'doc', 20],
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['file', 'docx', 20],
        'application/vnd.ms-excel' => ['file', 'xls', 20],
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['file', 'xlsx', 20],
        'application/vnd.ms-powerpoint' => ['file', 'ppt', 20],
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => ['file', 'pptx', 20],
        'text/plain' => ['file', 'txt', 5], 'text/csv' => ['file', 'csv', 5],
        'application/zip' => ['file', 'zip', 20], 'application/x-rar' => ['file', 'rar', 20], 'application/vnd.rar' => ['file', 'rar', 20],
        // ເພີ່ມ (11/2026): ຮູບອື່ນ, ວິດີໂອອື່ນ, ສຽງ, OpenDocument, RTF, 7z — ຮູບ/ວິດີໂອທີ່ browser ສະແດງບໍ່ໄດ້ ເປັນ 'file' (ດາວໂຫຼດ)
        'image/bmp' => ['image', 'bmp', 10], 'image/x-ms-bmp' => ['image', 'bmp', 10], 'image/avif' => ['image', 'avif', 10],
        'image/heic' => ['file', 'heic', 15], 'image/heif' => ['file', 'heif', 15], 'image/tiff' => ['file', 'tif', 15],
        'video/x-msvideo' => ['file', 'avi', 50], 'video/x-matroska' => ['file', 'mkv', 50], 'video/3gpp' => ['video', '3gp', 50],
        'video/mpeg' => ['file', 'mpg', 50], 'video/x-m4v' => ['video', 'm4v', 50],
        'audio/mpeg' => ['file', 'mp3', 20], 'audio/mp4' => ['file', 'm4a', 20], 'audio/x-m4a' => ['file', 'm4a', 20],
        'audio/wav' => ['file', 'wav', 20], 'audio/x-wav' => ['file', 'wav', 20], 'audio/ogg' => ['file', 'ogg', 20], 'audio/aac' => ['file', 'aac', 20],
        'application/vnd.oasis.opendocument.text' => ['file', 'odt', 20],
        'application/vnd.oasis.opendocument.spreadsheet' => ['file', 'ods', 20],
        'application/vnd.oasis.opendocument.presentation' => ['file', 'odp', 20],
        'application/rtf' => ['file', 'rtf', 20], 'text/rtf' => ['file', 'rtf', 20],
        'application/x-7z-compressed' => ['file', '7z', 20], 'application/x-zip-compressed' => ['file', 'zip', 20],
        'application/x-rar-compressed' => ['file', 'rar', 20], 'application/csv' => ['file', 'csv', 5],
    ];
}

/** ຄ່າ accept ຂອງ <input type=file> */
function chatFileAccept(): string
{
    return chatFileAcceptMedia() . ',' . chatFileAcceptDocs();
}

/** ປຸ່ມ "ຮູບ": ສະເພາະຮູບ ແລະ ວິດີໂອ */
function chatFileAcceptMedia(): string
{
    return 'image/*,video/*';
}

/** ປຸ່ມ "ໄຟລ໌": ສະເພາະເອກະສານ (ບໍ່ມີຮູບ / ວິດີໂອ) */
function chatFileAcceptDocs(): string
{
    // ນາມສະກຸນ + MIME ຂອງ Office/PDF — iPhone (ແອັບ Files) ບາງເທື່ອເລືອກ xlsx/docx ບໍ່ໄດ້ ຖ້າມີແຕ່ນາມສະກຸນ
    return '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.odp,.rtf,.txt,.csv,.zip,.rar,.7z,.mp3,.m4a,.wav,.ogg,.aac'
        . ',application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        . ',application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        . ',application/vnd.ms-powerpoint,application/vnd.openxmlformats-officedocument.presentationml.presentation'
        . ',text/csv,text/plain,application/zip';
}

/**
 * ບັນທຶກໄຟລ໌ທີ່ອັບໂຫຼດ → ['path','name','kind','size'] ຫຼື ['error' => ຂໍ້ຄວາມ (ລາວ)]
 */
function chatSaveFile(array $file, string $who = 'customer'): array
{
    $who = $who === 'staff' ? 'staff' : 'customer';
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) <= 0) {
        return ['error' => 'ອັບໂຫຼດໄຟລ໌ບໍ່ສຳເລັດ'];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    $ext0 = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
    // ໄຟລ໌ Office ແບບໃໝ່ (docx/xlsx/pptx) finfo ບາງເຄື່ອງເຫັນເປັນ zip → ໃຊ້ນາມສະກຸນຊ່ວຍ
    if ($mime === 'application/zip' && in_array($ext0, ['docx', 'xlsx', 'pptx'], true)) {
        $mime = ['docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                 'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'][$ext0];
    }
    $types = chatFileTypes();
    // finfo ບາງເຄື່ອງບອກຊະນິດກວ້າງໆ (octet-stream / zip / text) → ໃຊ້ນາມສະກຸນຊ່ວຍ (ສະເພາະນາມສະກຸນທີ່ຮອງຮັບ)
    $extMime = ['doc' => 'application/msword', 'xls' => 'application/vnd.ms-excel', 'ppt' => 'application/vnd.ms-powerpoint',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'rar' => 'application/vnd.rar', '7z' => 'application/x-7z-compressed', 'csv' => 'text/csv', 'rtf' => 'application/rtf',
                'odt' => 'application/vnd.oasis.opendocument.text', 'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
                'odp' => 'application/vnd.oasis.opendocument.presentation', 'heic' => 'image/heic', 'heif' => 'image/heif',
                'm4a' => 'audio/mp4', 'mp3' => 'audio/mpeg', 'aac' => 'audio/aac', 'mkv' => 'video/x-matroska', 'mov' => 'video/quicktime'];
    if ($mime === 'text/plain' && in_array($ext0, ['rtf', 'csv'], true)) {
        $mime = $extMime[$ext0];
    }
    if (!isset($types[$mime]) && isset($extMime[$ext0])
        && in_array($mime, ['application/octet-stream', 'application/zip', 'text/plain', 'application/x-empty', 'application/CDFV2', 'application/x-ole-storage',
                            'application/vnd.ms-office', 'application/encrypted', 'application/CDFV2-encrypted', 'application/vnd.ms-excel.sheet.macroEnabled.12', ''], true)) {
        $mime = $extMime[$ext0];
    }
    if (!isset($types[$mime])) {
        return ['error' => 'ບໍ່ຮອງຮັບໄຟລ໌ຊະນິດນີ້ (ຮູບ, ວິດີໂອ, ສຽງ, PDF, Word, Excel, PowerPoint, TXT, ZIP, RAR, 7Z)'];
    }
    [$kind, $ext, $maxMb] = $types[$mime];
    if ($file['size'] > $maxMb * 1024 * 1024) {
        return ['error' => 'ໄຟລ໌ໃຫຍ່ເກີນ %d MB', 'mb' => $maxMb];   // ຂໍ້ຄວາມ + ຕົວເລກແຍກກັນ (ແປໄດ້: t($error, $mb))
    }
    $sub = ['image' => 'images', 'video' => 'videos'][$kind] ?? 'files';   // ແຕ່ລະຊະນິດ ແຕ່ລະໂຟນເດີ
    $rel = CHAT_FILE_DIR . '/' . $who . '/' . $sub . '/' . date('Ym') . '/' . bin2hex(random_bytes(12)) . '.' . $ext;
    $root = dirname(__DIR__, 2);   // frontend/includes → root ຂອງໂປຣເຈັກ
    $abs = $root . '/' . $rel;
    if (!is_dir(dirname($abs)) && !@mkdir(dirname($abs), 0775, true)) {
        return ['error' => 'ບັນທຶກໄຟລ໌ບໍ່ໄດ້'];
    }
    // ໂຟນເດີນີ້: ບໍ່ສະແດງລາຍຊື່ໄຟລ໌, ບໍ່ແລ່ນ script, ບໍ່ໃຫ້ browser ເດົາຊະນິດໄຟລ໌
    $guard = $root . '/' . CHAT_FILE_DIR . '/.htaccess';
    if (!is_file($guard)) {
        @file_put_contents($guard, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar|html?|svg|js)$\">\n    Require all denied\n</FilesMatch>\n<IfModule mod_headers.c>\n    Header set X-Content-Type-Options nosniff\n</IfModule>\n");
    }
    if (!move_uploaded_file($file['tmp_name'], $abs)) {
        return ['error' => 'ບັນທຶກໄຟລ໌ບໍ່ໄດ້'];
    }
    $name = trim(preg_replace('/[\x00-\x1F\x7F\/\\\\]+/u', '', basename((string)($file['name'] ?? ''))));
    if ($name === '') {
        $name = 'file.' . $ext;
    }
    // ຊື່ທີ່ດາວໂຫຼດຕ້ອງລົງທ້າຍດ້ວຍຊະນິດແທ້ — ກັນໄຟລ໌ຂໍ້ຄວາມທີ່ຕັ້ງຊື່ .bat / .exe / .js ຖືກດາວໂຫຼດເປັນໄຟລ໌ແລ່ນໄດ້
    $same = ['jpg' => ['jpg', 'jpeg'], 'tif' => ['tif', 'tiff'], 'mpg' => ['mpg', 'mpeg'], 'heif' => ['heif', 'heic']];
    if (!in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), $same[$ext] ?? [$ext], true)) {
        $name .= '.' . $ext;
    }
    return ['path' => $rel, 'name' => mb_substr($name, 0, 200), 'kind' => $kind, 'size' => (int)$file['size']];
}

/** ຂໍ້ມູນໄຟລ໌ຂອງຂໍ້ຄວາມ → array ສຳລັບ JSON (null = ບໍ່ມີໄຟລ໌). $root = URL root ຂອງໂປຣເຈັກ (/websizewlaodev/) */
function chatFileJson(array $m, string $root): ?array
{
    if (empty($m['file_path'])) {
        return null;
    }
    return ['url' => $root . $m['file_path'], 'name' => (string)$m['file_name'], 'kind' => (string)$m['file_kind'], 'size' => (int)$m['file_size']];
}
