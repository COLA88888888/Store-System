<?php
/**
 * ຄົ້ນຫາທົ່ວເວັບ — ໃຊ້ຮ່ວມກັນໂດຍ api/search.php (ຜົນລັບ real-time ລຸ່ມຊ່ອງຄົ້ນຫາ) ແລະ pages/search.php (ໜ້າຜົນລັບ ເມື່ອກົດປຸ່ມຄົ້ນຫາ).
 * ຄົ້ນ 2 ບ່ອນ: (1) ລະບົບ ແລະ ອຸປະກອນໃນ $siteSolutions (includes/content.php) (2) ສິນຄ້າໃນ w_products (ສາຂາໃຫຍ່).
 * ຜົນລັບ 1 ລາຍການ: type, title, sub, url, image, icon, price, old (ລາຄາກ່ອນຫຼຸດ ຂີດຂ້າ; '' = ບໍ່ມີ).
 */

function searchSite(?PDO $pdo, int $storeId, string $q, int $limit = 8): array
{
    global $siteSolutions;
    $q = trim($q);
    if ($q === '') {
        return [];
    }
    $terms = function_exists('expandSearchTerms') ? expandSearchTerms($q) : [$q];
    $qLower = mb_strtolower($q);
    $isSystemQuery = (bool)preg_match('/(ລະບົບ|ໂປຣແກຣມ|ໂປແກມ|system|software|solution)/ui', $q);
    $candidates = [];

    // 1. ຄົ້ນຫາລະບົບ ແລະ ໂຊລູຊັ່ນ ($siteSolutions)
    if (!empty($siteSolutions)) {
        foreach ($siteSolutions as $tabKey => $tab) {
            $isPosTab = ($tabKey === 'pos');
            $tabLabel = $tab['label'] ?? '';
            foreach ($tab['items'] as $it) {
                $rawTitle = $it['title'];
                // ຖ້າເປັນໝວດລະບົບ POS: ໃຫ້ຊື່ສະແດງມີຄຳວ່າ "ລະບົບ POS" ຢູ່ທາງໜ້າ ເພື່ອຄວາມຊັດເຈນ
                $displayTitle = ($isPosTab && mb_strpos($rawTitle, 'ລະບົບ') === false) ? ('ລະບົບ POS ' . $rawTitle) : $rawTitle;
                $slug = $it['slug'] ?? '';
                $hint = $it['hint'] ?? '';

                // ສ້າງຂໍ້ຄວາມສຳລັບກວດສອບການຄົ້ນຫາ
                $searchable = mb_strtolower($displayTitle . ' ' . $rawTitle . ' ' . $slug . ' ' . $hint . ' ' . $tabLabel);
                if ($isPosTab) {
                    $searchable .= ' ລະບົບ ລະບົບpos ລະບົບ pos pos software system ໂປຣແກຣມ ໂປຣແກມ';
                }

                $match = false;
                $score = 0;

                // ຖ້າຄົ້ນຫາຄຳວ່າ "ລະບົບ" ຫຼື "pos" ຕົງໆ: ໃຫ້ລະບົບ POS ທຸກອັນຖືກເລືອກທັນທີ
                if (($qLower === 'ລະບົບ' || $qLower === 'pos' || $qLower === 'ລະບົບ pos' || $qLower === 'ລະບົບpos' || $qLower === 'ໂປຣແກຣມ') && $isPosTab) {
                    $match = true;
                    $score = 1600;
                } else {
                    foreach ($terms as $t) {
                        $tLower = mb_strtolower($t);
                        if ($tLower === '') continue;
                        if (mb_strpos($searchable, $tLower) !== false) {
                            $match = true;
                            if (mb_strpos(mb_strtolower($displayTitle), $tLower) === 0) {
                                $score = max($score, 900);
                            } elseif (mb_strpos(mb_strtolower($displayTitle), $tLower) !== false) {
                                $score = max($score, 700);
                            } elseif (mb_strpos(mb_strtolower($rawTitle), $tLower) !== false) {
                                $score = max($score, 600);
                            } else {
                                $score = max($score, 350);
                            }
                        }
                    }
                }

                if ($match) {
                    if ($isSystemQuery && $isPosTab) {
                        $score += 1000; // ຄົ້ນຫາ "ລະບົບ" ໃຫ້ລະບົບຂຶ້ນກ່ອນອຸປະກອນສະເໝີ
                    }
                    // ລະບົບຍອດນິຍົມ
                    if (in_array($slug, ['retail', 'minimart', 'restaurant', 'drinks'])) {
                        $score += 60;
                    }
                    $candidates[] = [
                        'type'  => 'solution',
                        'title' => $displayTitle,
                        'sub'   => $tabLabel,
                        'url'   => url('pages/solution.php?slug=' . rawurlencode($slug)),
                        'image' => solutionImage($slug),
                        'icon'  => $it['icon'] ?? '💡',
                        'price' => '',
                        'old'   => '',
                        'score' => $score,
                    ];
                }
            }
        }
    }

    // 2. ຄົ້ນຫາສິນຄ້າ ແລະ ອຸປະກອນຕົວຈິງຈາກຖານຂໍ້ມູນ
    if ($pdo) {
        $prodLimit = max(15, $limit * 2);
        $prods = getProducts($pdo, $storeId, ['q' => $q, 'per_page' => $prodLimit])['items'] ?? [];
        foreach ($prods as $p) {
            $name = $p['product_name'];
            $nameLower = mb_strtolower($name);
            $score = 100;

            if (mb_strpos($nameLower, $qLower) === 0) {
                $score = 750; // ເລີ່ມຕົ້ນດ້ວຍຄຳຄົ້ນ
            } elseif (mb_strpos($nameLower, $qLower) !== false) {
                $score = 450; // ມີຄຳຄົ້ນໃນຊື່
            }

            // ຖ້າຄົ້ນຫາ "ລະບົບ": ອຸປະກອນທີ່ພຽງແຕ່ໃສ່ຂໍ້ຄວາມ "ສຳລັບລະບົບ POS" ຈະໄດ້ຄະແນນຕ່ຳກວ່າລະບົບແທ້ໆ
            if ($isSystemQuery && mb_strpos($nameLower, 'ສຳລັບລະບົບ') !== false) {
                $score = 250;
            }

            $candidates[] = [
                'type'  => 'product',
                'title' => $name,
                'sub'   => (string)($p['category_name'] ?? t('ອຸປະກອນ POS')),
                'url'   => url('pages/product.php?id=' . (int)$p['product_id']),
                'image' => productImage($p['img_url'] ?? null),
                'icon'  => '🖥️',
                'price' => function_exists('canSeePrices') && canSeePrices() && (float)$p['price'] > 0 ? money((float)$p['price']) : '',   // ລາຄາສະເພາະສະມາຊິກ
                'old'   => '',
                'score' => $score,
            ];
        }
    }

    // ຮຽງລຳດັບຕາມຄະແນນຄວາມຕົງເປະ (Relevance Score) ສູງສຸດຂຶ້ນກ່ອນ
    usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);

    // ຕັດລາຍການ URL ຊ້ຳກັນອອກ
    $seenUrls = [];
    $items = [];
    foreach ($candidates as $c) {
        if (!isset($seenUrls[$c['url']])) {
            $seenUrls[$c['url']] = true;
            unset($c['score']); // ບໍ່ຕ້ອງສົ່ງ score ອອກໄປ
            $items[] = $c;
            if (count($items) >= $limit) {
                break;
            }
        }
    }

    return $items;
}
