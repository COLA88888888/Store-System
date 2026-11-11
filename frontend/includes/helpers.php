<?php
/**
 * ຟັງຊັນຊ່ວຍທົ່ວໄປຂອງ frontend: ສ້າງ URL, ລິ້ງ asset ພ້ອມ cache-buster, escape HTML,
 * ຮູບແບບເງິນ, CSRF token ແລະ ສົ່ງ JSON. ຖືກ require ຈາກ includes/bootstrap.php.
 */

// URL ຂອງໂຟນເດີໂປຣເຈັກ (ບ່ອນທີ່ມີ frontend/ ແລະ backend/, ເຊັ່ນ /websizewlaodev/) — ຄິດຈາກ SCRIPT_NAME
// (…/frontend/…) ຈຶ່ງບໍ່ຂຶ້ນກັບຊື່ໂຟນເດີທີ່ຕິດຕັ້ງ
function siteRoot(): string
{
    static $root = null;
    if ($root === null) {
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/frontend/index.php');
        $pos = strpos($script, '/frontend/');
        $root = $pos === false ? '/' : substr($script, 0, $pos) . '/';
    }
    return $root;
}

// URL ສາທາລະນະຂອງເວັບໄຊ = root ຂອງໂປຣເຈັກ: .htaccess ທີ່ root ສົ່ງທຸກ request (ຍົກເວັ້ນ backend/)
// ເຂົ້າ frontend/ ພາຍໃນ, ສະນັ້ນລິ້ງທັງໝົດບໍ່ມີ /frontend/ (ເຊັ່ນ /websizewlaodev/pages/shop.php)
function frontBase(): string
{
    return siteRoot();
}

// ໜ້າເວັບສະແດງໂດຍບໍ່ມີ .php: 'pages/about.php?x=1' → /websizewlaodev/pages/about?x=1, 'index.php' → root.
// .htaccess ທີ່ root ສົ່ງ pages/about ເຂົ້າ frontend/pages/about.php ພາຍໃນ (api/*.php ບໍ່ປ່ຽນ)
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $path = preg_replace('#^(pages/[A-Za-z0-9_-]+)\.php(?=$|[?\#])#', '$1', $path);
    $path = preg_replace('#^index\.php(?=$|[?\#])#', '', $path);
    return frontBase() . $path;
}

function backendUrl(string $path = ''): string
{
    return siteRoot() . 'backend/' . ltrim($path, '/');
}

// ລິ້ງໄຟລ໌ໃນ assets/ ພ້ອມ ?v=filemtime ເພື່ອໃຫ້ browser ໂຫຼດໃໝ່ເມື່ອແກ້ໄຟລ໌
function asset(string $path): string
{
    $file = dirname(__DIR__) . '/assets/' . ltrim($path, '/');
    $ver = is_file($file) ? filemtime($file) : 1;
    $url = url('assets/' . ltrim($path, '/')) . '?v=' . $ver;
    // ຮູບໃຫຍ່ (png/jpg > 120KB ໃນ assets/img/) → ສົ່ງເປັນ WebP ທີ່ຫຍໍ້ແລ້ວ (optimizedImageUrl)
    return preg_match('#^img/.+\.(png|jpe?g)$#i', ltrim($path, '/')) ? optimizedImageUrl($file, $url) : $url;
}

/**
 * ຫຍໍ້ຮູບໃຫຍ່ເປັນ WebP ໃຫ້ໂຫຼດໄວ (ສະເພາະ png/jpg ທີ່ໃຫຍ່ກວ່າ 120KB) — cache ທີ່ frontend/assets/img/opt/
 * ຊື່ໄຟລ໌ cache = ຊື່ເດີມ + hash ຂອງ path + filemtime + ຄວາມກວ້າງ → ປ່ຽນຮູບຕົ້ນສະບັບ = ສ້າງໃໝ່ເອງ. ໄຟລ໌ຕົ້ນສະບັບບໍ່ຖືກແຕະ.
 * ສ້າງບໍ່ໄດ້ (ບໍ່ມີ GD/WebP, ຮູບເສຍ) → ສົ່ງ URL ເດີມ. ໃຊ້ໃນ asset(), solutionImage(), portfolioImage(), productImage().
 */
// ລິ້ງ Facebook ທີ່ເປີດ Messenger ແຊັດກັບເພຈທັນທີ (m.me/<page id>) — $text = ຂໍ້ຄວາມທີ່ພິມໄວ້ໃຫ້ (ເຊັ່ນ ຊື່ສິນຄ້າ)
function fbChatUrl(string $text = ''): string
{
    return 'https://m.me/100067585680024' . ($text !== '' ? '?text=' . rawurlencode($text) : '');
}

function optimizedImageUrl(string $file, string $origUrl, int $maxW = 1600): string
{
    if (!function_exists('imagewebp') || !is_file($file) || filesize($file) < 122880 || !preg_match('/\.(png|jpe?g)$/i', $file)) {
        return $origUrl;
    }
    $dir = dirname(__DIR__) . '/assets/img/opt';
    $name = preg_replace('/[^a-z0-9_-]+/i', '-', pathinfo($file, PATHINFO_FILENAME)) . '-' . substr(md5($file), 0, 6) . '-' . filemtime($file) . '-' . $maxW . '.webp';
    $target = "$dir/$name";
    if (!is_file($target)) {
        $size = @getimagesize($file);
        if (!$size || $size[0] < 1 || (!is_dir($dir) && !@mkdir($dir, 0775, true))) {
            return $origUrl;
        }
        $src = $size[2] === IMAGETYPE_PNG ? @imagecreatefrompng($file) : @imagecreatefromjpeg($file);
        if (!$src) {
            return $origUrl;
        }
        [$w, $h] = [(int)$size[0], (int)$size[1]];
        $nw = min($w, $maxW);
        $nh = max(1, (int)round($h * $nw / $w));
        $im = imagecreatetruecolor($nw, $nh);
        imagealphablending($im, false);
        imagesavealpha($im, true);
        imagefill($im, 0, 0, imagecolorallocatealpha($im, 0, 0, 0, 127));
        imagecopyresampled($im, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);
        $ok = @imagewebp($im, "$target.tmp", 82) && @rename("$target.tmp", $target);
        imagedestroy($im);
        if (!$ok) {
            @unlink("$target.tmp");
            return $origUrl;
        }
        foreach (glob($dir . '/' . preg_replace('/[^a-z0-9_-]+/i', '-', pathinfo($file, PATHINFO_FILENAME)) . '-' . substr(md5($file), 0, 6) . '-*.webp') ?: [] as $old) {
            if ($old !== $target) @unlink($old);   // ລຶບ cache ເກົ່າຂອງຮູບນີ້
        }
    }
    return url('assets/img/opt/' . rawurlencode($name));
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function money($amount): string
{
    global $config;
    return number_format((float)$amount, 0, '.', ',') . ' ' . ($config['currency'] ?? 'Kip');
}

// ໃສ່ class "active" ໃຫ້ເມນູຂອງໜ້າປັດຈຸບັນ ($currentPage ຕັ້ງຢູ່ຫົວແຕ່ລະໜ້າ)
function navActive(string $page): string
{
    return (($GLOBALS['currentPage'] ?? '') === $page) ? ' active' : '';
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function checkCsrf(): bool
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return !empty($_SESSION['csrf']) && is_string($sent) && hash_equals($_SESSION['csrf'], $sent);
}

function jsonOut(array $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit();
}

// ຂໍ້ຄວາມແຈ້ງເຕືອນຄັ້ງດຽວ (ສະແດງໃນໜ້າຖັດໄປ ແລ້ວຫາຍ)
function flash(?string $message = null, string $type = 'success')
{
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// ຮູບຂອງກ່ອງປະເພດທຸລະກິດໃນໜ້າຫຼັກ: backend/assets/img/solutions/<slug>.jpg|jpeg|png|webp — ບໍ່ມີ → null (ສະແດງໄອຄອນແທນ)
function solutionImage(string $slug): ?string
{
    $dir = dirname(__DIR__, 2) . '/backend/assets/img/solutions/';
    foreach (['png', 'webp', 'jpg', 'jpeg'] as $ext) {
        if (is_file($dir . $slug . '.' . $ext)) {
            return optimizedImageUrl($dir . $slug . '.' . $ext, backendUrl('assets/img/solutions/' . rawurlencode($slug) . '.' . $ext) . '?v=' . filemtime($dir . $slug . '.' . $ext));
        }
    }
    return null;
}

// ຮູບດຽວຂອງຜົນງານ (models/works.php ໃຊ້ເມື່ອບໍ່ມີໂຟນເດີຮູບ): backend/assets/img/portfolio/<slug>.* → ຮູບລະບົບ solutionImage($slug) → null
function portfolioImage(string $slug): ?string
{
    $dir = dirname(__DIR__, 2) . '/backend/assets/img/portfolio/';
    foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
        if (is_file($dir . $slug . '.' . $ext)) {
            return optimizedImageUrl($dir . $slug . '.' . $ext, backendUrl('assets/img/portfolio/' . rawurlencode($slug) . '.' . $ext) . '?v=' . filemtime($dir . $slug . '.' . $ext));
        }
    }
    return solutionImage($slug);
}

// ຮູບສິນຄ້າເກັບຢູ່ backend/assets/product_img/ (ອັບໂຫຼດຈາກໜ້າຈັດການສິນຄ້າຂອງ POS)
function productImage(?string $img): ?string
{
    if (empty($img)) {
        return null;
    }
    $file = basename(trim($img));
    $path = dirname(__DIR__, 2) . '/backend/assets/product_img/' . $file;
    return is_file($path) ? optimizedImageUrl($path, backendUrl('assets/product_img/' . rawurlencode($file)), 1000) : null;   // ຮູບສິນຄ້າ: WebP ກວ້າງສູງສຸດ 1000px
}

/**
 * ຂະຫຍາຍຄຳຄົ້ນຫາ (Synonyms & Aliases) ເພື່ອໃຫ້ຄົ້ນຫາງ່າຍ
 * ເຊັ່ນ: ຄົ້ນ "ຄອມພິວເຕີ" ຈະພົບ "ເຄື່ອງຄອມ", "ຈໍສຳພັດ", "POS" ທັນທີ
 */
function expandSearchTerms(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return [];
    }
    $terms = [$q];
    $lower = mb_strtolower($q);

    // ກຸ່ມຄຳສັບທີ່ມີຄວາມໝາຍດຽວກັນ ຫຼື ກ່ຽວຂ້ອງກັນ
    $synonymGroups = [
        [
            'ລະບົບ', 'ລະບົບ pos', 'ລະບົບpos', 'ລະບົບຂາຍໜ້າຮ້ານ', 'ໂປຣແກຣມ', 'ໂປຣແກຣມ pos', 'ໂປຣແກມ', 'ໂປແກມ',
            'system', 'software', 'pos system'
        ],
        [
            'ຄອມພິວເຕີ', 'ຄອມ', 'ເຄື່ອງຄອມ', 'ຈໍສຳພັດ', 'ຈໍສຳຜັດ',
            'pos', 'computer', 'pc', 'touchscreen', 'touch screen', 'touch'
        ],
        [
            'ປິ້ນເຕີ', 'ປິນເຕີ', 'ເຄື່ອງພິມ', 'ເຄື່ອງພິມໃບບິນ', 'printer', 'receipt printer'
        ],
        [
            'ສະແກນ', 'ສະແກນເນີ', 'ເຄື່ອງສະແກນ', 'ບາໂຄດ', 'ຍິງບາໂຄດ', 'scanner', 'barcode'
        ],
        [
            'ລິ້ນຊັກ', 'ລີ້ນຊັກ', 'ລິ້ນຊັກເກັບເງິນ', 'drawer', 'cash drawer'
        ],
        [
            'ເຈ້ຍ', 'ເຈ້ຍພິມບິນ', 'ເຈ້ຍຄວາມຮ້ອນ', 'ເຈ້ຍບິນ', 'paper', 'thermal paper'
        ],
        [
            'ສະຕິກເກີ', 'ສະຕິກເກິ', 'ລາເບວ', 'label', 'sticker'
        ],
        [
            'ມືຖື', 'ແທັບເລັດ', 'mobile', 'handheld'
        ],
        [
            'ຈໍລູກຄ້າ', 'ຈໍສະແດງລາຄາ', 'customer display', 'display'
        ],
        [
            'ເຄື່ອງຊັ່ງ', 'ຊັ່ງ', 'ຕາຊັ່ງ', 'scale'
        ],
        [
            'ປິ້ນເຕີຄົວ', 'ຄົວ', 'kitchen'
        ],
    ];

    foreach ($synonymGroups as $group) {
        $match = false;
        foreach ($group as $syn) {
            $synLower = mb_strtolower($syn);
            if (mb_strpos($lower, $synLower) !== false || mb_strpos($synLower, $lower) !== false) {
                $match = true;
                break;
            }
        }
        if ($match) {
            foreach ($group as $syn) {
                if (!in_array($syn, $terms, true)) {
                    $terms[] = $syn;
                }
            }
        }
    }

    return $terms;
}
