<?php
/**
 * ໄອຄອນເສັ້ນ SVG ຊຸດດຽວຂອງເວັບໄຊ (ແທນ emoji ທີ່ໃຊ້ເປັນໄອຄອນ) — icon('receipt'), icon('monitor', 28).
 * ສີ = currentColor (ຕາມສີຕົວໜັງສືຂອງບ່ອນທີ່ວາງ). ຖືກ require ຈາກ includes/bootstrap.php ຫຼັງ helpers.php.
 * ເພີ່ມໄອຄອນ: ໃສ່ path ຂອງ SVG 24×24 (stroke) ໃນ ICON_PATHS.
 */
const ICON_PATHS = [
    'receipt'  => '<path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/>',
    'monitor'  => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
    'printer'  => '<path d="M6 9V3h12v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><path d="M7 17v4h10v-4"/>',
    'scan'     => '<path d="M3 7V4h3M21 7V4h-3M3 17v3h3M21 17v3h-3"/><path d="M7 8v8M10 8v8M13 8v8M17 8v8"/>',
    'drawer'   => '<rect x="3" y="6" width="18" height="12" rx="1.5"/><path d="M3 12h18M10 15h4"/>',
    'paper'    => '<circle cx="8" cy="9" r="5"/><circle cx="8" cy="9" r="1.5"/><path d="M8 14h11v-5"/>',
    'box'      => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
    'gift'     => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M5 12v9h14v-9M12 8v13"/><path d="M12 8S10 3 7.5 4.5 9 8 12 8zm0 0s2-5 4.5-3.5S15 8 12 8z"/>',
    'hash'     => '<path d="M5 9h14M5 15h14M10 3 8 21M16 3l-2 18"/>',
    'zap'      => '<path d="M13 2 3 14h9l-1 8 10-12h-9z"/>',
    'chat'     => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12z"/>',
    'note'     => '<path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8z"/><path d="M14 3v5h5M9 13h6M9 17h4"/>',
    'card'     => '<rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/>',
    'truck'    => '<path d="M3 6h11v10H3zM14 9h4l3 3v4h-7"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
    'pin'      => '<path d="M12 21s-7-6.1-7-11.5a7 7 0 0 1 14 0C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
    'check'    => '<circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/>',
    'x'        => '<circle cx="12" cy="12" r="9"/><path d="m9 9 6 6M15 9l-6 6"/>',
];

/** SVG ໄອຄອນເສັ້ນ (aria-hidden). ບໍ່ພົບຊື່ → ສະຕຣິງຫວ່າງ */
function icon(string $name, int $size = 20, float $stroke = 2): string
{
    $p = ICON_PATHS[$name] ?? '';
    if ($p === '') {
        return '';
    }
    return '<svg class="ico ico-' . $name . '" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="' . $stroke
        . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}
