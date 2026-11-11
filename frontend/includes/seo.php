<?php
/**
 * SEO ຂອງເວັບໄຊ: URL ເຕັມ, canonical ແລະ ຂໍ້ມູນໂຄງສ້າງ (JSON-LD) — ໃຊ້ໂດຍ layouts/seo_head.php, sitemap.php, robots.php.
 * ໜ້າໃດຢາກປັບ SEO ຕັ້ງຕົວແປເຫຼົ່ານີ້ກ່ອນ require layouts/header.php:
 *   $pageDescription  ຄຳອະທິບາຍ (≈ 150 ຕົວ) — ບໍ່ຕັ້ງ = config 'seo_description'
 *   $pageImage        ຮູບສຳລັບແຊຣ໌ (Facebook/LINE/WhatsApp) — ບໍ່ຕັ້ງ = ໂລໂກ້
 *   $pageCanonical    path ຂອງໜ້າ ພ້ອມ query ທີ່ສຳຄັນ (ເຊັ່ນ 'pages/product.php?id=5') — ບໍ່ຕັ້ງ = path ປັດຈຸບັນ ບໍ່ມີ query
 *   $pageNoIndex      true = ບໍ່ໃຫ້ Google ເກັບ (ກະຕ່າ, ສັ່ງຊື້, ຜົນຄົ້ນຫາ)
 *   $pageJsonLd       array ຂອງ JSON-LD ເພີ່ມເຕີມ (Product, BreadcrumbList, …)
 */

// ຕົ້ນທາງຂອງເວັບ (https://domain) — ຕັ້ງ 'site_url' ໃນ config/app.php ເມື່ອຂຶ້ນ server ແທ້; ບໍ່ຕັ້ງ = ຄິດຈາກ request
function siteOrigin(): string
{
    global $config;
    $fixed = rtrim((string)($config['site_url'] ?? ''), '/');
    if ($fixed !== '') {
        return $fixed;
    }
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
}

// '/websizewlaodev/pages/x.php' ຫຼື 'pages/x.php' → 'https://domain/…'
function absUrl(string $pathOrUrl): string
{
    if (preg_match('~^https?://~i', $pathOrUrl)) {
        return $pathOrUrl;
    }
    $path = str_starts_with($pathOrUrl, '/') ? $pathOrUrl : url($pathOrUrl);
    return siteOrigin() . $path;
}

function canonicalUrl(?string $path): string
{
    if ($path !== null) {
        return absUrl($path); // '' = ໜ້າຫຼັກ (root)
    }
    return siteOrigin() . strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
}

// ຂໍ້ມູນທຸລະກິດ (ທຸກໜ້າ): ຊື່, ໂລໂກ້, ຕິດຕໍ່ ແລະ ກ່ອງຄົ້ນຫາ (sitelinks search box → pages/search.php)
function siteJsonLd(array $company): array
{
    $org = [
        '@context' => 'https://schema.org',
        '@type'    => 'LocalBusiness',
        '@id'      => absUrl('') . '#business',
        'name'     => $company['name'],
        'description' => $company['tagline'],
        'url'      => absUrl(''),
        'logo'     => absUrl(asset('img/logo.png')),
        'image'    => absUrl(asset('img/logo.png')),
        'areaServed' => 'LA',
        'currenciesAccepted' => 'LAK',
    ];
    if ($company['tel'] !== '') {
        $org['telephone'] = $company['tel'];
    }
    if ($company['email'] !== '') {
        $org['email'] = $company['email'];
    }
    if ($company['address'] !== '') {
        $org['address'] = ['@type' => 'PostalAddress', 'streetAddress' => $company['address'], 'addressCountry' => 'LA'];
    }
    if ($company['lat'] && $company['lng']) {
        $org['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float)$company['lat'], 'longitude' => (float)$company['lng']];
    }
    $site = [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => $company['name'],
        'url'      => absUrl(''),
        'inLanguage' => 'lo',
        'potentialAction' => [
            '@type'  => 'SearchAction',
            'target' => absUrl('pages/search.php') . '?q={search_term_string}',
            'query-input' => 'required name=search_term_string',
        ],
    ];
    return [$org, $site];
}

// ສິນຄ້າ / ອຸປະກອນ — $price ເປັນກີບ (0 = ບໍ່ໃສ່ offers)
function productJsonLd(string $name, string $description, ?string $image, string $path, float $price, bool $inStock = true): array
{
    $p = [
        '@context' => 'https://schema.org',
        '@type'    => 'Product',
        'name'     => $name,
        'description' => $description,
        'url'      => absUrl($path),
        'brand'    => ['@type' => 'Brand', 'name' => $GLOBALS['company']['name'] ?? ''],
    ];
    if ($image) {
        $p['image'] = absUrl($image);
    }
    if ($price > 0) {
        $p['offers'] = [
            '@type' => 'Offer',
            'price' => (string)round($price),
            'priceCurrency' => 'LAK',
            'availability' => $inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url'   => absUrl($path),
        ];
    }
    return $p;
}

// [[ຊື່, path], …] → BreadcrumbList
function breadcrumbJsonLd(array $items): array
{
    $list = [];
    foreach (array_values($items) as $i => [$name, $path]) {
        $list[] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $name, 'item' => absUrl($path)];
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $list];
}
