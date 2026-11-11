<?php
/**
 * ຄົ້ນຫາ real-time ຂອງຊ່ອງຄົ້ນຫາໃນໜ້າຫຼັກ (components/hero.php → [data-live-search] ໃນ assets/js/main.js). JSON, ອ່ານຢ່າງດຽວ.
 *   GET ?q=ຄຳຄົ້ນ → { success, items: [{type, title, sub, url, image, icon, price, old}] } ສູງສຸດ 8 ລາຍການ (searchSite() ໃນ models/search.php)
 */
require dirname(__DIR__) . '/includes/bootstrap.php';

jsonOut(['success' => true, 'items' => searchSite($pdo, $shopStoreId, (string)($_GET['q'] ?? ''), 8)]);
