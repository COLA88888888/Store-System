<?php
/**
 * ສ່ວນ: ລາຍການສ່ວນຂໍ້ມູນທີ່ລຶບໄດ້ (ໜ້າ ລຶບ/ລ້າງຂໍ້ມູນລະບົບ)
 * ໃຊ້ຮ່ວມກັນລະຫວ່າງ api/clear_data_backend.php (ນັບ + ລຶບ) ແລະ pages/settings/clear_data/ (ສະແດງຕົວເລືອກ)
 * ເພື່ອໃຫ້ສິ່ງທີ່ເຫັນໃນໜ້າ ກັບ ສິ່ງທີ່ຖືກລຶບແທ້ ເປັນລາຍການດຽວກັນສະເໝີ.
 *
 * ໂຄງສ້າງ: group => [icon, color, items => [key => item]]
 *   tables  ຕາຕະລາງທີ່ຖືກລຶບທຸກແຖວ (DELETE FROM) — ຕາຕະລາງທີ່ບໍ່ມີໃນຖານຂໍ້ມູນຈະຖືກຂ້າມ
 *   updates SQL ເພີ່ມເຕີມ (ບໍ່ລຶບແຖວ, ເຊັ່ນ ປັບສະຕັອກເປັນ 0)
 *   count   SQL ນັບຈຳນວນທີ່ສະແດງໃນປ້າຍ;  unit  ໜ່ວຍຂອງຈຳນວນ (clear_data.unit_<unit>)
 * ຊື່/ຄຳອະທິບາຍ: clear_data.group_<group>, clear_data.item_<key>, clear_data.item_<key>_desc (lang/<locale>/clear_data.php)
 *
 * ຂໍ້ມູນທີ່ບໍ່ຢູ່ໃນລາຍການນີ້ ຈະບໍ່ຖືກລຶບເດັດຂາດ: ຜູ້ໃຊ້ ແລະ ສິດ, ສາຂາ, ຂໍ້ມູນຮ້ານ, ຕັ້ງຄ່າລະບົບ,
 * ເຄື່ອງພິມ, ບັນຊີທະນາຄານ, ອັດຕາແລກປ່ຽນ.
 */
return [
    'sales' => [
        'icon' => 'fa-cash-register', 'color' => '#16a34a',
        'items' => [
            'sales_bills' => [
                'icon' => 'fa-receipt', 'unit' => 'bills',
                'tables' => ['st_tbsale_save', 'st_tbsale_save_detail', 'st_sales', 'st_sale_batch_allocations'],
                'count' => 'SELECT COUNT(*) FROM st_tbsale_save',
            ],
            'held_orders' => [
                'icon' => 'fa-pause-circle', 'unit' => 'bills',
                'tables' => ['st_held_orders'],
                'count' => 'SELECT COUNT(*) FROM st_held_orders',
            ],
            'print_jobs' => [
                'icon' => 'fa-print', 'unit' => 'items',
                'tables' => ['st_print_jobs'],
                'count' => 'SELECT COUNT(*) FROM st_print_jobs',
            ],
        ],
    ],
    'shifts' => [
        'icon' => 'fa-calendar-alt', 'color' => '#0284c7',
        'items' => [
            'shift_sessions' => [
                'icon' => 'fa-user-clock', 'unit' => 'shifts',
                'tables' => ['st_shift_sessions'],
                'count' => 'SELECT COUNT(*) FROM st_shift_sessions',
            ],
            'activity_logs' => [
                'icon' => 'fa-file-signature', 'unit' => 'records',
                'tables' => ['st_activity_logs'],
                'count' => 'SELECT COUNT(*) FROM st_activity_logs',
            ],
        ],
    ],
    'stock' => [
        'icon' => 'fa-boxes', 'color' => '#d97706',
        'items' => [
            'imports' => [
                'icon' => 'fa-file-import', 'unit' => 'docs',
                'tables' => ['st_imports', 'st_import_details'],
                'count' => 'SELECT COUNT(*) FROM st_imports',
            ],
            'transfers' => [
                'icon' => 'fa-exchange-alt', 'unit' => 'docs',
                'tables' => ['st_stock_transfers', 'st_stock_transfer_details'],
                'count' => 'SELECT COUNT(*) FROM st_stock_transfers',
            ],
            'stock_checks' => [
                'icon' => 'fa-clipboard-check', 'unit' => 'docs',
                'tables' => ['st_stock_checks', 'st_stock_check_items', 'st_stock_check_count_log'],
                'count' => 'SELECT COUNT(*) FROM st_stock_checks',
            ],
            'writeoffs' => [
                'icon' => 'fa-trash-restore', 'unit' => 'items',
                'tables' => ['st_stock_writeoffs'],
                'count' => 'SELECT COUNT(*) FROM st_stock_writeoffs',
            ],
            'price_adjustments' => [
                'icon' => 'fa-tags', 'unit' => 'items',
                'tables' => ['st_price_adjustments'],
                'count' => 'SELECT COUNT(*) FROM st_price_adjustments',
            ],
            'reset_stock' => [
                'icon' => 'fa-undo-alt', 'unit' => 'products',
                'tables' => [],
                'updates' => ['UPDATE st_products SET qty = 0', 'UPDATE st_product_batches SET quantity = 0'],
                'count' => 'SELECT COUNT(*) FROM st_products WHERE qty <> 0',
            ],
        ],
    ],
    'catalog' => [
        'icon' => 'fa-cubes', 'color' => '#7c3aed',
        'items' => [
            'products' => [
                'icon' => 'fa-box-open', 'unit' => 'products',
                'tables' => ['st_products', 'st_product_batches', 'st_product_units'],
                'count' => 'SELECT COUNT(*) FROM st_products',
            ],
            'categories' => [
                'icon' => 'fa-sitemap', 'unit' => 'items',
                'tables' => ['st_categories'],
                'count' => 'SELECT COUNT(*) FROM st_categories',
            ],
            'units' => [
                'icon' => 'fa-balance-scale', 'unit' => 'items',
                'tables' => ['st_units'],
                'count' => 'SELECT COUNT(*) FROM st_units',
            ],
            'zones' => [
                'icon' => 'fa-map-marked-alt', 'unit' => 'items',
                'tables' => ['st_zones', 'st_shelves'],
                'count' => 'SELECT (SELECT COUNT(*) FROM st_zones) + (SELECT COUNT(*) FROM st_shelves)',
            ],
            'suppliers' => [
                'icon' => 'fa-truck', 'unit' => 'items',
                'tables' => ['st_suppliers'],
                'count' => 'SELECT COUNT(*) FROM st_suppliers',
            ],
            'promotions' => [
                'icon' => 'fa-percent', 'unit' => 'items',
                'tables' => ['st_promotions'],
                'count' => 'SELECT COUNT(*) FROM st_promotions',
            ],
        ],
    ],
    'customers' => [
        'icon' => 'fa-user-friends', 'color' => '#0d9488',
        'items' => [
            'customers' => [
                'icon' => 'fa-users', 'unit' => 'people',
                'tables' => ['st_customers', 'st_loyalty_points_log'],
                'count' => 'SELECT COUNT(*) FROM st_customers',
            ],
            'loyalty_points' => [
                'icon' => 'fa-star', 'unit' => 'records',
                'tables' => ['st_loyalty_points_log'],
                'updates' => ['UPDATE st_customers SET points = 0'],
                'count' => 'SELECT COUNT(*) FROM st_loyalty_points_log',
            ],
            'loyalty_rewards' => [
                'icon' => 'fa-gift', 'unit' => 'items',
                'tables' => ['st_loyalty_rewards'],
                'count' => 'SELECT COUNT(*) FROM st_loyalty_rewards',
            ],
        ],
    ],
    'setup' => [
        'icon' => 'fa-cogs', 'color' => '#475569',
        'items' => [
            'shift_templates' => [
                'icon' => 'fa-clock', 'unit' => 'items',
                'tables' => ['st_shifts'],
                'count' => 'SELECT COUNT(*) FROM st_shifts',
            ],
        ],
    ],
];
