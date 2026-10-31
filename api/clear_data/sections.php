<?php
/**
 * ສ່ວນ: ລາຍການສ່ວນຂໍ້ມູນທີ່ລຶບໄດ້ (ໜ້າ ລຶບ/ລ້າງຂໍ້ມູນລະບົບ)
 * ໃຊ້ຮ່ວມກັນລະຫວ່າງ api/clear_data_backend.php (ນັບ + ລຶບ) ແລະ pages/settings/clear_data/ (ສະແດງຕົວເລືອກ)
 * ເພື່ອໃຫ້ສິ່ງທີ່ເຫັນໃນໜ້າ ກັບ ສິ່ງທີ່ຖືກລຶບແທ້ ເປັນລາຍການດຽວກັນສະເໝີ.
 *
 * ໂຄງສ້າງ: group => [icon, color, items => [key => item]]
 *   ລຶບທຸກສາຂາ:
 *     tables  ຕາຕະລາງທີ່ຖືກລຶບທຸກແຖວ (DELETE FROM) — ຕາຕະລາງທີ່ບໍ່ມີໃນຖານຂໍ້ມູນຈະຖືກຂ້າມ
 *     updates SQL ເພີ່ມເຕີມ (ບໍ່ລຶບແຖວ, ເຊັ່ນ ປັບສະຕັອກເປັນ 0)
 *     count   SQL ນັບຈຳນວນທີ່ສະແດງໃນປ້າຍ
 *   ລຶບສະເພາະສາຂາດຽວ (ທຸກ ? = store_id ຂອງສາຂາທີ່ເລືອກ):
 *     branch        SQL ລຶບ/ປັບ ຕາມລຳດັບ (ຕາຕະລາງລູກກ່ອນ ແລ້ວຈຶ່ງຕາຕະລາງແມ່ ເພາະລູກອ້າງແມ່ເພື່ອຮູ້ສາຂາ)
 *     branch_count  SQL ນັບຈຳນວນຂອງສາຂານັ້ນ
 *     ບໍ່ມີ branch → ຂໍ້ມູນນີ້ບໍ່ແຍກສາຂາ, ລຶບໄດ້ສະເພາະເມື່ອເລືອກ "ທຸກສາຂາ"
 *   unit  ໜ່ວຍຂອງຈຳນວນ (clear_data.unit_<unit>)
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
                'branch' => [
                    // ເລກບິນບໍ່ຊ້ຳກັນ → ລາຍການໃນບິນຮູ້ສາຂາຜ່ານບິນແມ່
                    'DELETE d FROM st_tbsale_save_detail d JOIN st_tbsale_save s ON s.sale_save_bill = d.save_bill WHERE s.store_id = ?',
                    'DELETE FROM st_sale_batch_allocations WHERE store_id = ?',
                    'DELETE FROM st_sales WHERE store_id = ?',
                    'DELETE FROM st_tbsale_save WHERE store_id = ?',
                ],
                'branch_count' => 'SELECT COUNT(*) FROM st_tbsale_save WHERE store_id = ?',
            ],
            'held_orders' => [
                'icon' => 'fa-pause-circle', 'unit' => 'bills',
                'tables' => ['st_held_orders'],
                'count' => 'SELECT COUNT(*) FROM st_held_orders',
                'branch' => ['DELETE FROM st_held_orders WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_held_orders WHERE store_id = ?',
            ],
            'print_jobs' => [
                'icon' => 'fa-print', 'unit' => 'items',
                'tables' => ['st_print_jobs'],
                'count' => 'SELECT COUNT(*) FROM st_print_jobs',
                'branch' => ['DELETE FROM st_print_jobs WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_print_jobs WHERE store_id = ?',
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
                'branch' => ['DELETE FROM st_shift_sessions WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_shift_sessions WHERE store_id = ?',
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
                'branch' => [
                    'DELETE d FROM st_import_details d JOIN st_imports i ON i.import_id = d.import_id WHERE i.store_id = ?',
                    'DELETE FROM st_imports WHERE store_id = ?',
                ],
                'branch_count' => 'SELECT COUNT(*) FROM st_imports WHERE store_id = ?',
            ],
            'transfers' => [
                'icon' => 'fa-exchange-alt', 'unit' => 'docs',
                'tables' => ['st_stock_transfers', 'st_stock_transfer_details'],
                'count' => 'SELECT COUNT(*) FROM st_stock_transfers',
                // ໃບໂອນທີ່ສາຂານີ້ເປັນຜູ້ສົ່ງ ຫຼື ຜູ້ຮັບ
                'branch' => [
                    'DELETE d FROM st_stock_transfer_details d JOIN st_stock_transfers t ON t.transfer_id = d.transfer_id WHERE t.from_store_id = ? OR t.to_store_id = ?',
                    'DELETE FROM st_stock_transfers WHERE from_store_id = ? OR to_store_id = ?',
                ],
                'branch_count' => 'SELECT COUNT(*) FROM st_stock_transfers WHERE from_store_id = ? OR to_store_id = ?',
            ],
            'stock_checks' => [
                'icon' => 'fa-clipboard-check', 'unit' => 'docs',
                'tables' => ['st_stock_checks', 'st_stock_check_items', 'st_stock_check_count_log'],
                'count' => 'SELECT COUNT(*) FROM st_stock_checks',
                'branch' => [
                    'DELETE l FROM st_stock_check_count_log l JOIN st_stock_checks c ON c.id = l.check_id WHERE c.store_id = ?',
                    'DELETE i FROM st_stock_check_items i JOIN st_stock_checks c ON c.id = i.check_id WHERE c.store_id = ?',
                    'DELETE FROM st_stock_checks WHERE store_id = ?',
                ],
                'branch_count' => 'SELECT COUNT(*) FROM st_stock_checks WHERE store_id = ?',
            ],
            'writeoffs' => [
                'icon' => 'fa-trash-restore', 'unit' => 'items',
                'tables' => ['st_stock_writeoffs'],
                'count' => 'SELECT COUNT(*) FROM st_stock_writeoffs',
                'branch' => ['DELETE FROM st_stock_writeoffs WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_stock_writeoffs WHERE store_id = ?',
            ],
            'price_adjustments' => [
                'icon' => 'fa-tags', 'unit' => 'items',
                'tables' => ['st_price_adjustments'],
                'count' => 'SELECT COUNT(*) FROM st_price_adjustments',
                'branch' => ['DELETE FROM st_price_adjustments WHERE branch_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_price_adjustments WHERE branch_id = ?',
            ],
            'reset_stock' => [
                'icon' => 'fa-undo-alt', 'unit' => 'products',
                'tables' => [],
                'updates' => ['UPDATE st_products SET qty = 0', 'UPDATE st_product_batches SET quantity = 0'],
                'count' => 'SELECT COUNT(*) FROM st_products WHERE qty <> 0',
                'branch' => ['UPDATE st_products SET qty = 0 WHERE store_id = ?', 'UPDATE st_product_batches SET quantity = 0 WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_products WHERE qty <> 0 AND store_id = ?',
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
                'branch' => [
                    // ຫົວໜ່ວຍຂາຍຜູກກັບ product_id (ບໍ່ມີ store_id) — ຖ້າສາຂາອື່ນໃຊ້ product_id ດຽວກັນ ໃຫ້ເກັບໄວ້
                    'DELETE u FROM st_product_units u JOIN st_products p ON p.product_id = u.product_id AND p.store_id = ? WHERE NOT EXISTS (SELECT 1 FROM st_products p2 WHERE p2.product_id = u.product_id AND p2.store_id <> ?)',
                    'DELETE FROM st_product_batches WHERE store_id = ?',
                    'DELETE FROM st_products WHERE store_id = ?',
                ],
                'branch_count' => 'SELECT COUNT(*) FROM st_products WHERE store_id = ?',
            ],
            'categories' => [
                'icon' => 'fa-sitemap', 'unit' => 'items',
                'tables' => ['st_categories'],
                'count' => 'SELECT COUNT(*) FROM st_categories',
                'branch' => ['DELETE FROM st_categories WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_categories WHERE store_id = ?',
            ],
            'units' => [
                'icon' => 'fa-balance-scale', 'unit' => 'items',
                'tables' => ['st_units'],
                'count' => 'SELECT COUNT(*) FROM st_units',
                'branch' => ['DELETE FROM st_units WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_units WHERE store_id = ?',
            ],
            'zones' => [
                'icon' => 'fa-map-marked-alt', 'unit' => 'items',
                'tables' => ['st_zones', 'st_shelves'],
                'count' => 'SELECT (SELECT COUNT(*) FROM st_zones) + (SELECT COUNT(*) FROM st_shelves)',
                'branch' => ['DELETE FROM st_shelves WHERE store_id = ?', 'DELETE FROM st_zones WHERE store_id = ?'],
                'branch_count' => 'SELECT (SELECT COUNT(*) FROM st_zones WHERE store_id = ?) + (SELECT COUNT(*) FROM st_shelves WHERE store_id = ?)',
            ],
            'suppliers' => [
                'icon' => 'fa-truck', 'unit' => 'items',
                'tables' => ['st_suppliers'],
                'count' => 'SELECT COUNT(*) FROM st_suppliers',
                'branch' => ['DELETE FROM st_suppliers WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_suppliers WHERE store_id = ?',
            ],
            'promotions' => [
                'icon' => 'fa-percent', 'unit' => 'items',
                'tables' => ['st_promotions'],
                'count' => 'SELECT COUNT(*) FROM st_promotions',
                // ໂປຣ "ທຸກສາຂາ" (branch_id = 0) ບໍ່ຖືກລຶບເມື່ອເລືອກສາຂາດຽວ
                'branch' => ['DELETE FROM st_promotions WHERE branch_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_promotions WHERE branch_id = ?',
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
                'branch' => ['DELETE FROM st_loyalty_points_log WHERE store_id = ?', 'DELETE FROM st_customers WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_customers WHERE store_id = ?',
            ],
            'loyalty_points' => [
                'icon' => 'fa-star', 'unit' => 'records',
                'tables' => ['st_loyalty_points_log'],
                'updates' => ['UPDATE st_customers SET points = 0'],
                'count' => 'SELECT COUNT(*) FROM st_loyalty_points_log',
                'branch' => ['DELETE FROM st_loyalty_points_log WHERE store_id = ?', 'UPDATE st_customers SET points = 0 WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_loyalty_points_log WHERE store_id = ?',
            ],
            'loyalty_rewards' => [
                'icon' => 'fa-gift', 'unit' => 'items',
                'tables' => ['st_loyalty_rewards'],
                'count' => 'SELECT COUNT(*) FROM st_loyalty_rewards',
                'branch' => ['DELETE FROM st_loyalty_rewards WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_loyalty_rewards WHERE store_id = ?',
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
                'branch' => ['DELETE FROM st_shifts WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM st_shifts WHERE store_id = ?',
            ],
        ],
    ],
];
