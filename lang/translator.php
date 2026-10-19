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
    define('POS_SUPPORTED_LANGS', ['lo', 'th', 'zh', 'en']);
}

if (!defined('POS_LANG_META')) {
    define('POS_LANG_META', [
        'lo' => ['label' => 'ລາວ', 'flag' => '🇱🇦', 'img' => 'Flag_of_Laos.webp'],
        'th' => ['label' => 'ไทย', 'flag' => '🇹🇭', 'img' => 'Flag_of_Thailand.webp'],
        'zh' => ['label' => '中文', 'flag' => '🇨🇳', 'img' => 'Flag-chaina.webp'],
        'en' => ['label' => 'English', 'flag' => '🇺🇸', 'img' => 'flag-Stars.webp'],
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
        $file = __DIR__ . '/' . $locale . '/' . $domain . '.php';
        if (is_file($file)) {
            $data = require $file;
            return is_array($data) ? $data : [];
        }
        return [];
    }
}

if (!function_exists('loadLangDictionary')) {
    function loadLangDictionary(string $locale): array {
        static $cache = [];
        if (isset($cache[$locale])) {
            return $cache[$locale];
        }
        $merged = [];
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
