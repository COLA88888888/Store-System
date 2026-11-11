<?php
/**
 * ລະບົບແປພາສາຂອງເວັບໄຊ — 5 ພາສາຄືກັບ backend (ລາວ, ໄທ, ອັງກິດ, ຈີນ, ຫວຽດ).
 * ຂໍ້ຄວາມພາສາລາວໃນໂຄ້ດ = key: t('ໜ້າຫຼັກ') → ຊອກໃນ lang/<code>.php, ບໍ່ພົບ → ສະແດງພາສາລາວ.
 * ຂໍ້ຄວາມມີຕົວແປ ໃຊ້ %s / %d: t('ມີສິນຄ້າພຽງ %d %s', $max, $unit).
 * ພາສາທີ່ເລືອກ ເກັບໃນ session ຂອງເວັບໄຊ ແລະ cookie wlaoweb_lang (ແຍກຈາກ backend).
 * ປ່ຽນພາສາ = ລິ້ງ ?lang=xx (langUrl) — includes/bootstrap.php ບັນທຶກແລ້ວ redirect ກັບໜ້າເດີມ.
 * ຕ້ອງຢູ່ຫຼັງ config/session.php.
 */

const SITE_LANGS = [
    'lo' => ['label' => 'ພາສາລາວ',   'flag' => 'lo.webp'],
    'th' => ['label' => 'ภาษาไทย',    'flag' => 'th.webp'],
    'en' => ['label' => 'English',    'flag' => 'en.webp'],
    'zh' => ['label' => '中文',        'flag' => 'zh.webp'],
    'vi' => ['label' => 'Tiếng Việt', 'flag' => 'vi.webp'],
];
const LANG_COOKIE = 'wlaoweb_lang';

function currentLang(): string
{
    $lang = $_SESSION['lang'] ?? ($_COOKIE[LANG_COOKIE] ?? 'lo');
    return isset(SITE_LANGS[$lang]) ? $lang : 'lo';
}

function setLang(string $lang): void
{
    if (!isset(SITE_LANGS[$lang])) {
        return;
    }
    $_SESSION['lang'] = $lang;
    setcookie(LANG_COOKIE, $lang, [
        'expires'  => time() + 31536000,
        'path'     => siteRoot(),
        'samesite' => 'Lax',
    ]);
}

function langDict(): array
{
    static $dict = null;
    if ($dict === null) {
        $lang = currentLang();
        $file = dirname(__DIR__) . '/lang/' . $lang . '.php';
        $dict = ($lang !== 'lo' && is_file($file)) ? (require $file) : [];
    }
    return $dict;
}

function t(string $lao, ...$args): string
{
    $text = langDict()[$lao] ?? $lao;
    return $args ? vsprintf($text, $args) : $text;
}

// ແປເປັນພາສາທີ່ລະບຸ (ບໍ່ອີງພາສາຂອງໜ້າເວັບ) — ໃຊ້ໃນແຊັດ: ຕອບລູກຄ້າເປັນພາສາທີ່ລູກຄ້າພິມ (models/chat.php)
function tl(string $lang, string $lao, ...$args): string
{
    static $dicts = [];
    if (!isset($dicts[$lang])) {
        $file = dirname(__DIR__) . '/lang/' . $lang . '.php';
        $dicts[$lang] = ($lang !== 'lo' && isset(SITE_LANGS[$lang]) && is_file($file)) ? (require $file) : [];
    }
    $text = $dicts[$lang][$lao] ?? $lao;
    return $args ? vsprintf($text, $args) : $text;
}

// ແປ ແລ້ວ escape HTML — ໃຊ້ໃນ echo ທົ່ວໄປ
function te(string $lao, ...$args): string
{
    return e(t($lao, ...$args));
}

// URL ຂອງໜ້າປັດຈຸບັນ + ?lang=xx (ເກັບ query ອື່ນໄວ້)
function langUrl(string $lang): string
{
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    $query = $_GET;
    $query['lang'] = $lang;
    return $path . '?' . http_build_query($query);
}

// ຮັບ ?lang=xx: ບັນທຶກ ແລ້ວ redirect ໄປ URL ເດີມທີ່ບໍ່ມີ lang (ບໍ່ໃຫ້ຕິດຢູ່ໃນ URL / bookmark)
function handleLangSwitch(): void
{
    if (!isset($_GET['lang']) || !is_string($_GET['lang'])) {
        return;
    }
    setLang($_GET['lang']);
    $query = $_GET;
    unset($query['lang']);
    $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    header('Location: ' . $path . ($query ? '?' . http_build_query($query) : ''));
    exit();
}
