<?php
// lang/translator.php
// Core multi-language (i18n) loader for MiniPos.
//
// Each supported locale has its own folder under lang/<locale>/ containing any
// number of "domain" files (lang/lo/products.php, lang/lo/pos.php, ...), each
// returning a flat associative array of translation_key => translated_string.
// All domain files for a locale are merged together at runtime.
//
// Usage in any page/partial (available everywhere config/db.php is included):
//   echo t('products.title', 'ລາຍການສິນຄ້າ');
// The second argument is the fallback (original Lao text) used if the key is
// missing from every locale, so a missing translation never breaks a page.

if (!defined('POS_SUPPORTED_LANGS')) {
    define('POS_SUPPORTED_LANGS', ['lo', 'th', 'zh', 'en', 'vi']);
}

// ພາສາທີ່ແປບໍ່ຄົບທຸກ domain: key ທີ່ຍັງບໍ່ມີ ໃຫ້ໃຊ້ຂອງພາສານີ້ແທນ (ກ່ອນຈະຕົກໄປພາສາລາວ)
if (!defined('POS_LANG_FALLBACK')) {
    define('POS_LANG_FALLBACK', ['vi' => 'en']);
}

if (!defined('POS_LANG_META')) {
    define('POS_LANG_META', [
        'lo' => ['label' => 'ລາວ', 'flag' => '🇱🇦', 'img' => 'Flag_of_Laos.webp'],
        'th' => ['label' => 'ไทย', 'flag' => '🇹🇭', 'img' => 'Flag_of_Thailand.webp'],
        'zh' => ['label' => '中文', 'flag' => '🇨🇳', 'img' => 'Flag-chaina.webp'],
        'en' => ['label' => 'English', 'flag' => '🇺🇸', 'img' => 'flag-Stars.webp'],
        'vi' => ['label' => 'Tiếng Việt', 'flag' => '🇻🇳', 'img' => 'Flag_of_Vietnam.svg.webp'],
    ]);
}

if (!function_exists('getCurrentLang')) {
    function getCurrentLang(): string {
        $lang = $_SESSION['lang'] ?? ($_COOKIE['minipos_lang'] ?? 'lo');
        return in_array($lang, POS_SUPPORTED_LANGS, true) ? $lang : 'lo';
    }
}

if (!function_exists('loadLangDomain')) {
    // Load a single domain file (e.g. 'layout') for one locale, without merging every domain.
    // Used to embed multi-language dictionaries client-side (e.g. sidebar/navbar instant switch).
    function loadLangDomain(string $locale, string $domain): array {
        $base = isset(POS_LANG_FALLBACK[$locale]) ? loadLangDomain(POS_LANG_FALLBACK[$locale], $domain) : [];
        $file = __DIR__ . '/' . $locale . '/' . $domain . '.php';
        if (is_file($file)) {
            $data = require $file;
            return is_array($data) ? array_merge($base, $data) : $base;
        }
        return $base;
    }
}

if (!function_exists('loadLangDictionary')) {
    function loadLangDictionary(string $locale): array {
        static $cache = [];
        if (isset($cache[$locale])) {
            return $cache[$locale];
        }
        $merged = isset(POS_LANG_FALLBACK[$locale]) ? loadLangDictionary(POS_LANG_FALLBACK[$locale]) : [];
        $dir = __DIR__ . '/' . $locale;
        if (is_dir($dir)) {
            $files = glob($dir . '/*.php');
            if ($files) {
                sort($files);
                foreach ($files as $file) {
                    $part = require $file;
                    if (is_array($part)) {
                        $merged = array_merge($merged, $part);
                    }
                }
            }
        }
        $cache[$locale] = $merged;
        return $merged;
    }
}

if (!function_exists('t')) {
    function t(string $key, ?string $default = null): string {
        static $dict = null;
        static $dictLocale = null;

        $locale = getCurrentLang();
        if ($dict === null || $dictLocale !== $locale) {
            $dict = loadLangDictionary($locale);
            $dictLocale = $locale;
        }

        if (isset($dict[$key]) && $dict[$key] !== '') {
            return $dict[$key];
        }

        if ($locale !== 'lo') {
            $lo = loadLangDictionary('lo');
            if (isset($lo[$key]) && $lo[$key] !== '') {
                return $lo[$key];
            }
        }

        return $default ?? $key;
    }
}

// ຂໍ້ຄວາມລາວທີ່ຂຽນຝັງໃນໂຄດ (ບໍ່ມີ key): tl('ລາວ') ຊອກຄຳແປດ້ວຍ key ທີ່ສ້າງຈາກຂໍ້ຄວາມເອງ (lang/<locale>/lx.php,
// 'lx.' + md5 10 ຕົວ) — ບໍ່ມີຄຳແປ → ສະແດງພາສາລາວຄືເກົ່າ. ໃຊ້ຕາມບ່ອນທີ່ຂໍ້ຄວາມຢູ່:
//   tl()   ໃນໂຄດ PHP (ຄ່າດິບ)            tlh()  ໃນ HTML / attribute (escape HTML)
//   tlj()  ໃນ string ຂອງ JavaScript       tlja() ໃນ JS ຂອງ attribute onclick="..."
if (!function_exists('lxKey')) {
    function lxKey(string $text): string { return 'lx.' . substr(md5($text), 0, 10); }
    function tl(string $text): string { return t(lxKey($text), $text); }
    function tlh(string $text): string { return htmlspecialchars(tl($text), ENT_QUOTES, 'UTF-8', false); }
    function tlj(string $text): string {
        return str_replace(['\\', "'", '"', '`', '${', "\r", "\n", '</'], ['\\\\', "\\'", '\\"', '\\`', '\\${', '', '\\n', '<\\/'], tl($text));
    }
    function tlja(string $text): string { return htmlspecialchars(tlj($text), ENT_QUOTES, 'UTF-8', false); }
    // ໄຟລ໌ .js ລ້ວນ (ບໍ່ຜ່ານ PHP) ເອີ້ນ (window.__tl||String)('ລາວ') — ແຜນທີ່ ລາວ → ຄຳແປ ຂອງພາສາປັດຈຸບັນ
    // ສະເພາະຂໍ້ຄວາມໃນໄຟລ໌ .js (lang/<locale>/lxjs.php). ພິມອອກໃນ layouts/header.php.
    function lxJsMapScript(): string {
        $map = [];
        if (getCurrentLang() !== 'lo') {
            foreach (loadLangDomain('lo', 'lxjs') as $key => $lao) {
                $tr = t($key, $lao);
                if ($tr !== $lao) $map[$lao] = $tr;
            }
        }
        return '<script>window.__tl=(function(m){return function(s){return Object.prototype.hasOwnProperty.call(m,s)?m[s]:s;};})('
            . json_encode((object)$map, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ');</script>';
    }
}

// Build a JSON-encodable map of keys for embedding into <script> blocks, e.g.
//   var I18N_POS = [[ tjson(['pos.add_success' => 'ເພີ່ມສຳເລັດ']) ]];
if (!function_exists('tjson')) {
    function tjson(array $keysWithDefaults): string {
        $out = [];
        foreach ($keysWithDefaults as $key => $default) {
            $out[$key] = t($key, $default);
        }
        return json_encode($out, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}
