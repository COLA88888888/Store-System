<?php
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/frontend/api/search.php?q=' . urlencode('ລະບົບ');
$_GET['q'] = 'ລະບົບ';
ob_start();
include 'frontend/api/search.php';
$json = ob_get_clean();
$data = json_decode($json, true);
echo "Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
echo "Items returned: " . count($data['items']) . "\n";
foreach ($data['items'] as $i => $item) {
    echo ($i + 1) . ". {$item['title']} ({$item['sub']}) -> {$item['url']}\n";
}
unlink(__FILE__);
