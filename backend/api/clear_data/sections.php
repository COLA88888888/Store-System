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
                'tables' => ['w_tbsale_save', 'w_tbsale_save_detail', 'w_sales', 'w_sale_batch_allocations'],
                'count' => 'SELECT COUNT(*) FROM w_tbsale_save',
                'branch' => [
                    // ເລກບິນບໍ່ຊ້ຳກັນ → ລາຍການໃນບິນຮູ້ສາຂາຜ່ານບິນແມ່
                    'DELETE d FROM w_tbsale_save_detail d JOIN w_tbsale_save s ON s.sale_save_bill = d.save_bill WHERE s.store_id = ?',
                    'DELETE FROM w_sale_batch_allocations WHERE store_id = ?',
                    'DELETE FROM w_sales WHERE store_id = ?',
                    'DELETE FROM w_tbsale_save WHERE store_id = ?',
                ],
                'branch_count' => 'SELECT COUNT(*) FROM w_tbsale_save WHERE store_id = ?',
            ],
            'held_orders' => [
                'icon' => 'fa-pause-circle', 'unit' => 'bills',
                'tables' => ['w_held_orders'],
                'count' => 'SELECT COUNT(*) FROM w_held_orders',
                'branch' => ['DELETE FROM w_held_orders WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM w_held_orders WHERE store_id = ?',
            ],
            'print_jobs' => [
                'icon' => 'fa-print', 'unit' => 'items',
                'tables' => ['w_print_jobs'],
                'count' => 'SELECT COUNT(*) FROM w_print_jobs',
                'branch' => ['DELETE FROM w_print_jobs WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM w_print_jobs WHERE store_id = ?',
            ],
        ],
    ],
    'stock' => [
        'icon' => 'fa-boxes', 'color' => '#d97706',
        'items' => [
            'imports' => [
                'icon' => 'fa-file-import', 'unit' => 'docs',
                'tables' => ['w_imports', 'w_import_details'],
                'count' => 'SELECT COUNT(*) FROM w_imports',
                'branch' => [
                    'DELETE d FROM w_import_details d JOIN w_imports i ON i.import_id = d.import_id WHERE i.store_id = ?',
                    'DELETE FROM w_imports WHERE store_id = ?',
                ],
                'branch_count' => 'SELECT COUNT(*) FROM w_imports WHERE store_id = ?',
            ],
            'transfers' => [
                'icon' => 'fa-exchange-alt', 'unit' => 'docs',
                'tables' => ['w_stock_transfers', 'w_stock_transfer_details'],
                'count' => 'SELECT COUNT(*) FROM w_stock_transfers',
                // ໃບໂອນທີ່ສາຂານີ້ເປັນຜູ້ສົ່ງ ຫຼື ຜູ້ຮັບ
                'branch' => [
                    'DELETE d FROM w_stock_transfer_details d JOIN w_stock_transfers t ON t.transfer_id = d.transfer_id WHERE t.from_store_id = ? OR t.to_store_id = ?',
                    'DELETE FROM w_stock_transfers WHERE from_store_id = ? OR to_store_id = ?',
                ],
                'branch_count' => 'SELECT COUNT(*) FROM w_stock_transfers WHERE from_store_id = ? OR to_store_id = ?',
            ],
            'writeoffs' => [
                'icon' => 'fa-trash-restore', 'unit' => 'items',
                'tables' => ['w_stock_writeoffs'],
                'count' => 'SELECT COUNT(*) FROM w_stock_writeoffs',
                'branch' => ['DELETE FROM w_stock_writeoffs WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM w_stock_writeoffs WHERE store_id = ?',
            ],
            'price_adjustments' => [
                'icon' => 'fa-tags', 'unit' => 'items',
                'tables' => ['w_price_adjustments'],
                'count' => 'SELECT COUNT(*) FROM w_price_adjustments',
                'branch' => ['DELETE FROM w_price_adjustments WHERE branch_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM w_price_adjustments WHERE branch_id = ?',
            ],
            'reset_stock' => [
                'icon' => 'fa-undo-alt', 'unit' => 'products',
                'tables' => [],
                'updates' => ['UPDATE w_products SET qty = 0', 'UPDATE w_product_batches SET quantity = 0'],
                'count' => 'SELECT COUNT(*) FROM w_products WHERE qty <> 0',
                'branch' => ['UPDATE w_products SET qty = 0 WHERE store_id = ?', 'UPDATE w_product_batches SET quantity = 0 WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM w_products WHERE qty <> 0 AND store_id = ?',
            ],
        ],
    ],
    'catalog' => [
        'icon' => 'fa-cubes', 'color' => '#7c3aed',
        'items' => [
            'products' => [
                'icon' => 'fa-box-open', 'unit' => 'products',
                'tables' => ['w_products', 'w_product_batches', 'w_product_units'],
                'count' => 'SELECT COUNT(*) FROM w_products',
                'branch' => [
                    // ຫົວໜ່ວຍຂາຍຜູກກັບ product_id (ບໍ່ມີ store_id) — ຖ້າສາຂາອື່ນໃຊ້ product_id ດຽວກັນ ໃຫ້ເກັບໄວ້
                    'DELETE u FROM w_product_units u JOIN w_products p ON p.product_id = u.product_id AND p.store_id = ? WHERE NOT EXISTS (SELECT 1 FROM w_products p2 WHERE p2.product_id = u.product_id AND p2.store_id <> ?)',
                    'DELETE FROM w_product_batches WHERE store_id = ?',
                    'DELETE FROM w_products WHERE store_id = ?',
                ],
                'branch_count' => 'SELECT COUNT(*) FROM w_products WHERE store_id = ?',
            ],
            'categories' => [
                'icon' => 'fa-sitemap', 'unit' => 'items',
                'tables' => ['w_categories'],
                'count' => 'SELECT COUNT(*) FROM w_categories',
                'branch' => ['DELETE FROM w_categories WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM w_categories WHERE store_id = ?',
            ],
            'units' => [
                'icon' => 'fa-balance-scale', 'unit' => 'items',
                'tables' => ['w_units'],
                'count' => 'SELECT COUNT(*) FROM w_units',
                'branch' => ['DELETE FROM w_units WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM w_units WHERE store_id = ?',
            ],
            'suppliers' => [
                'icon' => 'fa-truck', 'unit' => 'items',
                'tables' => ['w_suppliers'],
                'count' => 'SELECT COUNT(*) FROM w_suppliers',
                'branch' => ['DELETE FROM w_suppliers WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM w_suppliers WHERE store_id = ?',
            ],
            'promotions' => [
                'icon' => 'fa-percent', 'unit' => 'items',
                'tables' => ['w_promotions'],
                'count' => 'SELECT COUNT(*) FROM w_promotions',
                // ໂປຣ "ທຸກສາຂາ" (branch_id = 0) ບໍ່ຖືກລຶບເມື່ອເລືອກສາຂາດຽວ
                'branch' => ['DELETE FROM w_promotions WHERE branch_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM w_promotions WHERE branch_id = ?',
            ],
        ],
    ],
    'customers' => [
        'icon' => 'fa-user-friends', 'color' => '#0d9488',
        'items' => [
            'customers' => [
                'icon' => 'fa-users', 'unit' => 'people',
                'tables' => ['w_customers', 'w_loyalty_points_log'],
                'count' => 'SELECT COUNT(*) FROM w_customers',
                'branch' => ['DELETE FROM w_loyalty_points_log WHERE store_id = ?', 'DELETE FROM w_customers WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM w_customers WHERE store_id = ?',
            ],
            'loyalty_points' => [
                'icon' => 'fa-star', 'unit' => 'records',
                'tables' => ['w_loyalty_points_log'],
                'updates' => ['UPDATE w_customers SET points = 0'],
                'count' => 'SELECT COUNT(*) FROM w_loyalty_points_log',
                'branch' => ['DELETE FROM w_loyalty_points_log WHERE store_id = ?', 'UPDATE w_customers SET points = 0 WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM w_loyalty_points_log WHERE store_id = ?',
            ],
            'loyalty_rewards' => [
                'icon' => 'fa-gift', 'unit' => 'items',
                'tables' => ['w_loyalty_rewards'],
                'count' => 'SELECT COUNT(*) FROM w_loyalty_rewards',
                'branch' => ['DELETE FROM w_loyalty_rewards WHERE store_id = ?'],
                'branch_count' => 'SELECT COUNT(*) FROM w_loyalty_rewards WHERE store_id = ?',
            ],
        ],
    ],
];
