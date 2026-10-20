<?php
// pages/permissions/* + api/permissions_backend.php strings - English
return [
    // permissions_header.php
    'permissions.header_title' => 'Permissions',
    'permissions.btn_manage_users' => 'Manage Users',

    // shared badges / labels
    'permissions.badge_super_admin' => 'Admin',
    'permissions.role_admin_badge' => 'Admin',
    'permissions.role_employee_default' => 'Staff',
    'permissions.branch_prefix' => 'Branch',

    // matrix_header_component.php - quick preset dropdown
    'permissions.quick_preset_btn' => 'Quick Presets',
    'permissions.quick_preset_header' => 'Choose a Preset',
    'permissions.preset_cashier' => 'POS Cashier',
    'permissions.preset_accountant' => 'Accountant',
    'permissions.preset_stock_keeper' => 'Stock Keeper',
    'permissions.preset_auditor' => 'Auditor',
    'permissions.preset_manager' => 'Manager / Full Access',
    'permissions.preset_all_on' => 'Enable All',
    'permissions.preset_all_off' => 'Disable All',
    'permissions.admin_full_badge' => '100% Access (Admin)',

    // user_list_sidebar.php
    'permissions.users_list_title' => 'User List',
    'permissions.badge_account_suffix' => 'accounts',
    'permissions.search_placeholder' => 'Search by name or role...',
    'permissions.no_users_found' => 'No users found in the system',

    // permissions_matrix_table.php
    'permissions.select_user_prompt' => 'Select a user from the list on the left to set permissions',
    'permissions.col_menu' => 'System Menu (Sidebar Menu)',
    'permissions.action_view' => 'View',
    'permissions.action_add' => 'Add',
    'permissions.action_edit' => 'Edit',
    'permissions.action_delete' => 'Delete',
    'permissions.na' => 'N/A',

    // section_main_menu.php
    'permissions.section_main_menu' => '1. Menu',
    'permissions.module_dashboard_title' => 'Dashboard',
    'permissions.module_dashboard_desc' => 'Access and view the main dashboard statistics',

    // section_pos_sales.php
    'permissions.section_pos_sales' => '2. POS Sales',
    'permissions.module_sale_title' => 'Sales',
    'permissions.module_sale_desc' => 'Access the checkout page and POS sales',
    'permissions.module_item_sales_title' => '↳ Sales List',
    'permissions.module_item_sales_desc' => 'View sales bills and sales history',

    // section_customers.php
    'permissions.section_customers' => '3. Customer Management',
    'permissions.module_customers_title' => 'Customer Management',
    'permissions.module_customers_desc' => 'Add, edit, and manage customer/member data',

    // section_customer_debt.php
    'permissions.section_customer_debt' => 'Customer Debts',
    'permissions.module_customer_debt_title' => 'Customer Debts',
    'permissions.module_customer_debt_desc' => 'Sell on credit at the POS, collect repayments and manage customer debts',
    'permissions.module_debt_report_title' => 'Customer Debt Report',

    // section_inventory.php
    'permissions.section_inventory' => '4. Products & Inventory',
    'permissions.module_categories_title' => 'Product Categories',
    'permissions.module_categories_desc' => 'Manage product categories and types',
    'permissions.module_units_title' => 'Units Management',
    'permissions.module_units_desc' => 'Manage product units',
    'permissions.module_products_title' => 'Products',
    'permissions.module_products_desc' => 'Add, edit, adjust stock, and manage products',
    'permissions.module_import_stock_title' => 'Import Stock',
    'permissions.module_import_stock_desc' => 'Record receiving and importing new stock',
    'permissions.module_import_list_title' => 'Import List',
    'permissions.module_import_list_desc' => 'View stock receipt history',
    'permissions.module_stock_check_title' => 'Stock Check (Manager)',
    'permissions.module_stock_check_desc' => 'Add = start count, Edit = confirm & adjust stock, Delete = delete sheet',
    'permissions.module_stock_count_title' => 'Stock Count (Counter)',
    'permissions.module_stock_count_desc' => 'Enter actual counts, export/print reports only',
    'permissions.module_stock_transfer_title' => 'Stock Transfer',
    'permissions.module_stock_transfer_desc' => 'Transfer stock to another branch',
    'permissions.module_transfer_history_title' => 'Transfer History',
    'permissions.module_transfer_history_desc' => 'Track and cancel stock transfer notes',

    // section_accounting.php
    'permissions.section_accounting' => '5. Banking & Accounting',
    'permissions.module_accounting_title' => 'Bank Management',
    'permissions.module_accounting_desc' => 'Set up bank accounts, transfer accounts, and QR codes',

    // section_reports.php
    'permissions.section_reports' => '6. Reports',
    'permissions.module_daily_report_title' => 'Daily Report',
    'permissions.module_daily_report_desc' => 'View daily sales summary and history',
    'permissions.module_all_sales_title' => 'All Sales Report',
    'permissions.module_all_sales_desc' => 'View overall sales reports by date range',
    'permissions.module_best_seller_title' => 'Best Seller Report',
    'permissions.module_best_seller_desc' => 'View top-selling product rankings',
    'permissions.module_profit_cost_title' => 'Profit & Cost Report',
    'permissions.module_profit_cost_desc' => 'Analyze cost, revenue, and net profit',
    'permissions.module_financial_title' => 'Financial Report',
    'permissions.module_financial_desc' => 'View cash/transfer receipts summary by channel',
    'permissions.module_category_sales_title' => 'Sales by Category Report',
    'permissions.module_category_sales_desc' => 'View sales statistics by category/type',
    'permissions.module_delete_bills_title' => '↳ Deleted Bills History',

    // section_setup.php
    'permissions.section_setup' => '7. System Settings & User Management',
    'permissions.module_users_title' => 'User Management',
    'permissions.module_permissions_title' => 'Permissions',
    'permissions.module_branches_title' => 'Branch Management',
    'permissions.module_branches_desc' => 'Add, edit, and manage all branches',
    'permissions.module_stores_title' => 'Store Information',
    'permissions.module_stores_desc' => 'Manage store info, tax ID, logo, and address',
    'permissions.module_print_barcode_title' => 'Print Barcode',
    'permissions.module_print_barcode_desc' => 'Print product barcodes and price labels',
    'permissions.module_exchange_rate_title' => 'Exchange Rate',
    'permissions.module_exchange_rate_desc' => 'Set and adjust foreign currency exchange rates',
    'permissions.module_promotions_title' => 'Promotions',
    'permissions.module_promotions_desc' => 'Create, edit, and manage product discount promotions',
    'permissions.module_price_adjustment_title' => 'Price Adjustment',
    'permissions.module_price_adjustment_desc' => 'Bulk adjust selling and purchase prices',
    'permissions.module_printers_title' => 'Printer Settings',
    'permissions.module_printers_desc' => 'Configure receipt and barcode printers',
    'permissions.module_database_title' => 'Database Management',
    'permissions.module_database_desc' => 'Access, view statistics, and manage the system database',

    // permissions_js.php
    'permissions.js_alert_title' => 'Notice',
    'permissions.js_error_title' => 'Error',
    'permissions.js_update_perm_fail' => 'Unable to update permission!',
    'permissions.js_connection_error' => 'A server connection error occurred!',
    'permissions.js_confirm_preset_title' => 'Apply this quick preset?',
    'permissions.js_confirm_preset_text' => 'This will update all module permissions for this user according to the selected preset!',
    'permissions.js_confirm_apply' => 'Confirm',
    'permissions.js_cancel' => 'Cancel',
    'permissions.js_preset_fail' => 'Unable to apply the preset!',

    // api/permissions_backend.php - AJAX response messages
    'permissions.msg_invalid_data' => 'Invalid data',
    'permissions.msg_admin_full_rights' => 'Admin already has 100% access in the system and cannot be changed!',
    'permissions.status_enabled' => 'Enabled',
    'permissions.status_disabled' => 'Disabled',
    'permissions.msg_toggle_success_fmt' => '%s "%s" for %s successfully!',
    'permissions.msg_error_prefix' => 'Error: ',
    'permissions.msg_user_not_found' => 'User not found',
    'permissions.msg_preset_not_found' => 'Selected preset not found',
    'permissions.msg_admin_cannot_change' => 'Admin permissions cannot be changed!',
    'permissions.msg_preset_success_fmt' => 'Applied preset "%s" to %s successfully!',

    // api/permissions_backend.php - $perm_names_lao map (used in success message + activity log)
    'permissions.perm_name_dashboard' => 'Dashboard permission',
    'permissions.perm_name_sale' => 'POS Sales permission',
    'permissions.perm_name_item_sales' => 'Sales List view permission',
    'permissions.perm_name_customers' => 'Customer Management permission',
    'permissions.perm_name_stock' => 'Products & Inventory permission',
    'permissions.perm_name_accounting' => 'Accounting Management permission',
    'permissions.perm_name_report' => 'Reports view permission',
    'permissions.perm_name_daily_report' => 'Daily Report permission',
    'permissions.perm_name_all_sales' => 'All Sales Report permission',
    'permissions.perm_name_best_seller' => 'Best Seller Report permission',
    'permissions.perm_name_profit_cost' => 'Profit & Cost Report permission',
    'permissions.perm_name_financial' => 'Financial Report permission',
    'permissions.perm_name_category_sales' => 'Sales by Category Report permission',
    'permissions.perm_name_delete_bills' => 'Deleted Bills History permission',
    'permissions.perm_name_users' => 'User Management permission',
    'permissions.perm_name_permissions' => 'Permissions Management permission',
    'permissions.perm_name_branches' => 'Branch Management permission',
    'permissions.perm_name_setup' => 'System Settings permission',
    'permissions.perm_name_edit' => 'Edit & Delete Data permission',
    'permissions.perm_name_database' => 'Database Management permission',

    // Previously untranslated labels
    'permissions.module_loyalty_title' => 'Customer Loyalty Points',
    'permissions.module_loyalty_desc' => 'Configure rules and manage customer loyalty points',
    'permissions.module_suppliers_title' => 'Manage Suppliers',
    'permissions.module_suppliers_desc' => 'Add, edit and manage product supplier information',

    // Admin-account edit lock
    'permissions.admin_locked_badge' => 'Only the first admin can edit this',
];
