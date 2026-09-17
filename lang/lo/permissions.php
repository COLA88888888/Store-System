<?php
// pages/permissions/* + api/permissions_backend.php strings - Lao baseline
return [
    // permissions_header.php
    'permissions.header_title' => 'ກຳນົດສິດການໃຊ້ງານ',
    'permissions.btn_manage_users' => 'ຈັດການຜູ້ນຳໃຊ້',

    // shared badges / labels (matrix_header_component.php, user_list_sidebar.php)
    'permissions.badge_super_admin' => 'Admin',
    'permissions.role_admin_badge' => 'ຜູ້ບໍລິຫານ',
    'permissions.role_employee_default' => 'ພະນັກງານ',
    'permissions.branch_prefix' => 'ສາຂາ',

    // matrix_header_component.php - quick preset dropdown
    'permissions.quick_preset_btn' => 'ກຳນົດສິດດ່ວນ',
    'permissions.quick_preset_header' => 'ເລືອກຮູບແບບສິດ (Presets)',
    'permissions.preset_cashier' => 'ພະນັກງານຂາຍ POS',
    'permissions.preset_accountant' => 'ຄົນຈັດການບັນຊີ',
    'permissions.preset_stock_keeper' => 'ພະນັກງານຄັງສິນຄ້າ',
    'permissions.preset_auditor' => 'ຜູ້ກວດສອບບັນຊີ',
    'permissions.preset_manager' => 'ຜູ້ບໍລິຫານ / ຈັດການທັງໝົດ',
    'permissions.preset_all_on' => 'ເປີດທຸກສິດ',
    'permissions.preset_all_off' => 'ປິດທຸກສິດ',
    'permissions.admin_full_badge' => 'ເປີດສິດ 100% (ຜູ້ບໍລິຫານ)',

    // user_list_sidebar.php
    'permissions.users_list_title' => 'ລາຍຊື່ຜູ້ນຳໃຊ້',
    'permissions.badge_account_suffix' => 'ບັນຊີ',
    'permissions.search_placeholder' => 'ຄົ້ນຫາຊື່ຜູ້ໃຊ້ ຫຼື ບົດບາດ...',
    'permissions.no_users_found' => 'ບໍ່ພົບຂໍ້ມູນຜູ້ນຳໃຊ້ໃນລະບົບ',

    // permissions_matrix_table.php
    'permissions.select_user_prompt' => 'ກະລຸນາເລືອກຜູ້ນຳໃຊ້ຈາກລາຍຊື່ທາງຊ້າຍເພື່ອຕັ້ງຄ່າສິດ',
    'permissions.col_menu' => 'ເມນູລະບົບ (Sidebar Menu)',
    'permissions.action_view' => 'ເບິ່ງ',
    'permissions.action_add' => 'ເພີ່ມ',
    'permissions.action_edit' => 'ແກ້ໄຂ',
    'permissions.action_delete' => 'ລົບ',
    'permissions.na' => 'ບໍ່ມີ',

    // section_main_menu.php
    'permissions.section_main_menu' => '1. ເມນູ',
    'permissions.module_dashboard_title' => 'ດາດສ໌ບອດ',
    'permissions.module_dashboard_desc' => 'ເຂົ້າເຖິງ ແລະ ເບິ່ງສະຖິຕິໜ້າດາດສ໌ບອດຫຼັກ',

    // section_pos_sales.php
    'permissions.section_pos_sales' => '2. ຂາຍສິນຄ້າ POS',
    'permissions.module_sale_title' => 'ຂາຍສິນຄ້າ',
    'permissions.module_sale_desc' => 'ເຂົ້າເຖິງໜ້າຄິດເງິນ ແລະ ຂາຍສິນຄ້າ POS',
    'permissions.module_item_sales_title' => '↳ ລາຍການຂາຍສິນຄ້າ (Sales List)',
    'permissions.module_item_sales_desc' => 'ເບິ່ງລາຍການບິນຂາຍ ແລະ ປະຫວັດການຂາຍ',

    // section_customers.php
    'permissions.section_customers' => '3. ຈັດການລູກຄ້າ',
    'permissions.module_customers_title' => 'ຈັດການລູກຄ້າ',
    'permissions.module_customers_desc' => 'ເພີ່ມ, ແກ້ໄຂ ແລະ ຈັດການຂໍ້ມູນລູກຄ້າ/ສະມາຊິກ',

    // section_quotations.php
    'permissions.section_quotations' => 'ໃບສະເໜີລາຄາ',
    'permissions.module_quotations_title' => 'ໃບສະເໜີລາຄາ',
    'permissions.module_quotations_desc' => 'ສ້າງ, ແກ້ໄຂ ແລະ ຈັດການໃບສະເໜີລາຄາໃຫ້ລູກຄ້າ',

    // section_inventory.php
    'permissions.section_inventory' => '4. ຂໍ້ມູນສິນຄ້າ & ຄັງສິນຄ້າ',
    'permissions.module_categories_title' => 'ໝວດໝູ່ສິນຄ້າ',
    'permissions.module_categories_desc' => 'ຈັດການໝວດໝູ່ ແລະ ປະເພດສິນຄ້າ',
    'permissions.module_units_title' => 'ຈັດການຫົວໜ່ວຍ',
    'permissions.module_units_desc' => 'ຈັດການຫົວໜ່ວຍສິນຄ້າ',
    'permissions.module_products_title' => 'ລາຍການສິນຄ້າ',
    'permissions.module_products_desc' => 'ເພີ່ມ, ແກ້ໄຂ, ປັບສະຕັອກ ແລະ ຈັດການສິນຄ້າ',
    'permissions.module_import_stock_title' => 'ນຳເຂົ້າສິນຄ້າ',
    'permissions.module_import_stock_desc' => 'ບັນທຶກການຮັບ ແລະ ນຳເຂົ້າສິນຄ້າໃໝ່',
    'permissions.module_import_list_title' => 'ລາຍການສິນຄ້າຮັບເຂົ້າ',
    'permissions.module_import_list_desc' => 'ເບິ່ງປະຫວັດໃບບິນຮັບສິນຄ້າເຂົ້າ',
    'permissions.module_stock_transfer_title' => 'ໂອນສິນຄ້າລະຫວ່າງສາຂາ',
    'permissions.module_stock_transfer_desc' => 'ໂອນສິນຄ້າໄປສາຂາອື່ນ',
    'permissions.module_transfer_history_title' => 'ປະຫວັດການໂອນສິນຄ້າ',
    'permissions.module_transfer_history_desc' => 'ຕິດຕາມ ແລະ ຍົກເລີກໃບໂອນສິນຄ້າ',

    // section_accounting.php
    'permissions.section_accounting' => '5. ຈັດການທະນາຄານ & ບັນຊີ',
    'permissions.module_accounting_title' => 'ຈັດການທະນາຄານ',
    'permissions.module_accounting_desc' => 'ຕັ້ງຄ່າບັນຊີທະນາຄານ, ບັນຊີໂອນ ແລະ QR Code',

    // section_reports.php
    'permissions.section_reports' => '6. ລາຍງານ',
    'permissions.module_daily_report_title' => 'ລາຍງານປະຈຳວັນ',
    'permissions.module_daily_report_desc' => 'ເບິ່ງສະຫຼຸບຍອດຂາຍ ແລະ ປະຫວັດປະຈຳວັນ',
    'permissions.module_all_sales_title' => 'ລາຍງານການຂາຍທັງໝົດ',
    'permissions.module_all_sales_desc' => 'ເບິ່ງລາຍງານການຂາຍລວມທັງໝົດຕາມໄລຍະເວລາ',
    'permissions.module_best_seller_title' => 'ລາຍງານສິນຄ້າຂາຍດີ',
    'permissions.module_best_seller_desc' => 'ເບິ່ງອັນດັບສິນຄ້າທີ່ຂາຍດີທີ່ສຸດ',
    'permissions.module_profit_cost_title' => 'ລາຍງານກຳໄລ-ຕົ້ນທຶນ',
    'permissions.module_profit_cost_desc' => 'ວິເຄາະຕົ້ນທຶນ, ລາຍຮັບ ແລະ ກຳໄລສຸດທິ',
    'permissions.module_financial_title' => 'ລາຍງານການເງິນ',
    'permissions.module_financial_desc' => 'ເບິ່ງສະຫຼຸບການຮັບເງິນສົດ/ໂອນ ຕາມຊ່ອງທາງ',
    'permissions.module_category_sales_title' => 'ລາຍງານຕາມປະເພດສິນຄ້າ',
    'permissions.module_category_sales_desc' => 'ເບິ່ງສະຖິຕິຍອດຂາຍແຍກຕາມໝວດໝູ່/ປະເພດ',
    'permissions.module_delete_bills_title' => '↳ ປະຫວັດການລົບບິນຂາຍ',

    // section_setup.php
    'permissions.section_setup' => '7. ຕັ້ງຄ່າລະບົບ & ຈັດການຜູ້ນຳໃຊ້',
    'permissions.module_users_title' => 'ຈັດການຜູ້ນຳໃຊ້',
    'permissions.module_permissions_title' => 'ກຳນົດສິດ',
    'permissions.module_branches_title' => 'ຈັດການສາຂາ',
    'permissions.module_branches_desc' => 'ເພີ່ມ, ແກ້ໄຂ ແລະ ຈັດການສາຂາທັງໝົດ',
    'permissions.module_stores_title' => 'ຂໍ້ມູນຮ້ານ',
    'permissions.module_stores_desc' => 'ຈັດການຂໍ້ມູນຮ້ານ, ເລກຜູ້ເສຍອາກອນ, ໂລໂກ້ ແລະ ທີ່ຢູ່',
    'permissions.module_print_barcode_title' => 'ພິມບາໂຄ້ດ',
    'permissions.module_print_barcode_desc' => 'ພິມບາໂຄ້ດ ແລະ ລາຄາສິນຄ້າອອກເຈ້ຍ',
    'permissions.module_exchange_rate_title' => 'ອັດຕາແລກປ່ຽນເງິນ',
    'permissions.module_exchange_rate_desc' => 'ຕັ້ງຄ່າ ແລະ ປັບອັດຕາແລກປ່ຽນເງິນຕ່າງປະເທດ',
    'permissions.module_promotions_title' => 'ໂປຣໂມຊັ່ນ',
    'permissions.module_promotions_desc' => 'ສ້າງ, ແກ້ໄຂ ແລະ ຈັດການໂປຣໂມຊັ່ນສ່ວນຫຼຸດສິນຄ້າ',
    'permissions.module_price_adjustment_title' => 'ປັບລາຄາສິນຄ້າ',
    'permissions.module_price_adjustment_desc' => 'ປັບລາຄາຂາຍ ແລະ ລາຄາຊື້ສິນຄ້າເປັນຊຸດ',
    'permissions.module_printers_title' => 'ຕັ້ງຄ່າປິ່ນເຕີ',
    'permissions.module_printers_desc' => 'ຕັ້ງຄ່າເຄື່ອງພິມ ໃບບິນ ແລະ ບາໂຄ້ດ',
    'permissions.module_database_title' => 'ຈັດການຖານຂໍ້ມູນ',
    'permissions.module_database_desc' => 'ເຂົ້າເຖິງ ແລະ ເບິ່ງສະຖິຕິ/ຈັດການຖານຂໍ້ມູນລະບົບ',

    // permissions_js.php
    'permissions.js_alert_title' => 'ແຈ້ງເຕືອນ',
    'permissions.js_error_title' => 'ຜິດພາດ',
    'permissions.js_update_perm_fail' => 'ບໍ່ສາມາດອັບເດດສິດໄດ້!',
    'permissions.js_connection_error' => 'ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່ກັບເຊີເວີ!',
    'permissions.js_confirm_preset_title' => 'ຢືນຢັນການກຳນົດສິດດ່ວນ?',
    'permissions.js_confirm_preset_text' => 'ລະບົບຈະອັບເດດສິດທຸກໂມດູນຂອງຜູ້ນຳໃຊ້ນີ້ຕາມຮູບແບບທີ່ເລືອກ!',
    'permissions.js_confirm_apply' => 'ຢືນຢັນນຳໃຊ້',
    'permissions.js_cancel' => 'ຍົກເລີກ',
    'permissions.js_preset_fail' => 'ບໍ່ສາມາດກຳນົດສິດດ່ວນໄດ້!',

    // api/permissions_backend.php - AJAX response messages
    'permissions.msg_invalid_data' => 'ຂໍ້ມູນບໍ່ຖືກຕ້ອງ',
    'permissions.msg_admin_full_rights' => 'ຜູ້ບໍລິຫານ ມີສິດເຕັມ 100% ໃນລະບົບຢູ່ແລ້ວ ບໍ່ສາມາດປັບປ່ຽນສິດໄດ້!',
    'permissions.status_enabled' => 'ເປີດສິດ',
    'permissions.status_disabled' => 'ປິດສິດ',
    'permissions.msg_toggle_success_fmt' => '%s "%s" ໃຫ້ %s ສຳເລັດ!',
    'permissions.msg_error_prefix' => 'ຜິດພາດ: ',
    'permissions.msg_user_not_found' => 'ບໍ່ພົບຜູ້ໃຊ້',
    'permissions.msg_preset_not_found' => 'ບໍ່ພົບຮູບແບບສິດທີ່ເລືອກ',
    'permissions.msg_admin_cannot_change' => 'ຜູ້ບໍລິຫານ (Admin) ບໍ່ສາມາດປັບປ່ຽນສິດໄດ້!',
    'permissions.msg_preset_success_fmt' => 'ນຳໃຊ້ຮູບແບບ "%s" ໃຫ້ %s ສຳເລັດ!',

    // api/permissions_backend.php - $perm_names_lao map (used in success message + activity log)
    'permissions.perm_name_dashboard' => 'ສິດ ດາດຊ໌ບອດ',
    'permissions.perm_name_sale' => 'ສິດ ຂາຍສິນຄ້າ POS',
    'permissions.perm_name_item_sales' => 'ສິດ ເບິ່ງລາຍການຂາຍສິນຄ້າ',
    'permissions.perm_name_customers' => 'ສິດ ຈັດການລູກຄ້າ',
    'permissions.perm_name_stock' => 'ສິດ ຂໍ້ມູນສິນຄ້າ & ຄັງສິນຄ້າ',
    'permissions.perm_name_accounting' => 'ສິດ ຈັດການບັນຊີ',
    'permissions.perm_name_report' => 'ສິດ ເບິ່ງລາຍງານ',
    'permissions.perm_name_daily_report' => 'ສິດ ລາຍງານປະຈຳວັນ',
    'permissions.perm_name_all_sales' => 'ສິດ ລາຍງານການຂາຍທັງໝົດ',
    'permissions.perm_name_best_seller' => 'ສິດ ລາຍງານສິນຄ້າຂາຍດີ',
    'permissions.perm_name_profit_cost' => 'ສິດ ລາຍງານກຳໄລ-ຕົ້ນທຶນ',
    'permissions.perm_name_financial' => 'ສິດ ລາຍງານການເງິນ',
    'permissions.perm_name_category_sales' => 'ສິດ ລາຍງານຕາມປະເພດສິນຄ້າ',
    'permissions.perm_name_delete_bills' => 'ສິດ ປະຫວັດການລົບບິນຂາຍ',
    'permissions.perm_name_users' => 'ສິດ ຈັດການຜູ້ນຳໃຊ້',
    'permissions.perm_name_permissions' => 'ສິດ ກຳນົດສິດ',
    'permissions.perm_name_branches' => 'ສິດ ຈັດການສາຂາ',
    'permissions.perm_name_setup' => 'ສິດ ຕັ້ງຄ່າລະບົບ',
    'permissions.perm_name_edit' => 'ສິດ ແກ້ໄຂ & ລົບຂໍ້ມູນ',
    'permissions.perm_name_database' => 'ສິດ ຈັດການຖານຂໍ້ມູນ',

    // Previously untranslated labels
    'permissions.module_loyalty_title' => 'ຄະແນນສະສົມລູກຄ້າ',
    'permissions.module_loyalty_desc' => 'ຕັ້ງຄ່າກົດເກນ ແລະ ຈັດການຄະແນນສະສົມຂອງລູກຄ້າ',
    'permissions.module_suppliers_title' => 'ຈັດການຜູ້ສະໜອງ',
    'permissions.module_suppliers_desc' => 'ເພີ່ມ, ແກ້ໄຂ ແລະ ຈັດການຂໍ້ມູນຜູ້ສະໜອງສິນຄ້າ',

    // Admin-account edit lock
    'permissions.admin_locked_badge' => 'ມີແຕ່ຜູ້ບໍລິຫານຄົນທຳອິດເທົ່ານັ້ນທີ່ແກ້ໄຂໄດ້',
];
