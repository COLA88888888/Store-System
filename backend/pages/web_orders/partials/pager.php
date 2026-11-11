<?php
/*
 * ສ່ວນ: ຟັງຊັນແຖບແບ່ງໜ້າ ‹ 1 … 4 5 6 … 20 › — ໃຊ້ຮ່ວມ: pages/web_orders (partials/rows.php) ແລະ pages/web_members/index.php
 * ລິ້ງໃຊ້ data-page (JS ຂອງແຕ່ລະໜ້າດັກຄລິກ ແລ້ວດຶງໜ້ານັ້ນດ້ວຍ ?ajax=1). ສະໄຕລ໌: themes/web_orders.css (.wo-pager)
 */
if (!function_exists('woPagerHtml')) {
    // $always = true → ສະແດງສະເໝີ (ເຖິງມີໜ້າດຽວ) — ໃຊ້ໃນ pages/web_members/index.php
    function woPagerHtml(int $page, int $pages, int $total, string $unit = 'ລາຍການ', bool $always = false): string
    {
        if ($pages <= 1 && !$always) return '';
        $li = function ($label, $p, $state = '') {
            return '<li class="page-item' . ($state ? " $state" : '') . '">'
                . ($state === 'disabled' && $p === null
                    ? '<span class="page-link">' . $label . '</span>'
                    : '<a class="page-link" href="#" data-page="' . (int)$p . '">' . $label . '</a>')
                . '</li>';
        };
        $html = '<nav class="wo-pager" aria-label="pagination"><span class="wo-pager-info">ໜ້າ ' . $page . ' / ' . $pages
            . ' · ' . number_format($total) . ' ' . $unit . '</span><ul class="pagination pagination-sm mb-0">';
        $html .= $li('‹', $page - 1, $page <= 1 ? 'disabled' : '');
        $shown = array_unique(array_filter([1, $page - 1, $page, $page + 1, $pages], fn($p) => $p >= 1 && $p <= $pages));
        sort($shown);
        $prev = 0;
        foreach ($shown as $p) {
            if ($p - $prev > 1) $html .= $li('…', null, 'disabled');
            $html .= $li($p, $p, $p === $page ? 'active' : '');
            $prev = $p;
        }
        $html .= $li('›', $page + 1, $page >= $pages ? 'disabled' : '');
        return $html . '</ul></nav>';
    }
}
