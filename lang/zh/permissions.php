<?php
// pages/permissions/* + api/permissions_backend.php strings - Chinese
return [
    // permissions_header.php
    'permissions.header_title' => '权限设置',
    'permissions.btn_manage_users' => '用户管理',

    // shared badges / labels
    'permissions.badge_super_admin' => '管理员',
    'permissions.role_admin_badge' => '管理员',
    'permissions.role_employee_default' => '员工',
    'permissions.branch_prefix' => '分店',

    // matrix_header_component.php - quick preset dropdown
    'permissions.quick_preset_btn' => '快速设置权限',
    'permissions.quick_preset_header' => '选择权限模板 (Presets)',
    'permissions.preset_cashier' => 'POS 收银员',
    'permissions.preset_accountant' => '会计人员',
    'permissions.preset_stock_keeper' => '仓库管理员',
    'permissions.preset_auditor' => '审计人员',
    'permissions.preset_manager' => '管理员 / 全部管理',
    'permissions.preset_all_on' => '开启全部权限',
    'permissions.preset_all_off' => '关闭全部权限',
    'permissions.admin_full_badge' => '拥有 100% 权限（管理员）',

    // user_list_sidebar.php
    'permissions.users_list_title' => '用户列表',
    'permissions.badge_account_suffix' => '个账户',
    'permissions.search_placeholder' => '搜索用户名或职位...',
    'permissions.no_users_found' => '系统中未找到用户数据',

    // permissions_matrix_table.php
    'permissions.select_user_prompt' => '请从左侧列表选择用户以设置权限',
    'permissions.col_menu' => '系统菜单 (Sidebar Menu)',
    'permissions.action_view' => '查看',
    'permissions.action_add' => '添加',
    'permissions.action_edit' => '编辑',
    'permissions.action_delete' => '删除',
    'permissions.na' => '无',

    // section_main_menu.php
    'permissions.section_main_menu' => '1. 菜单',
    'permissions.module_dashboard_title' => '仪表盘',
    'permissions.module_dashboard_desc' => '访问并查看主仪表盘统计数据',

    // section_pos_sales.php
    'permissions.section_pos_sales' => '2. POS 销售',
    'permissions.module_sale_title' => '销售',
    'permissions.module_sale_desc' => '访问收银台并进行 POS 销售',
    'permissions.module_item_sales_title' => '↳ 销售清单 (Sales List)',
    'permissions.module_item_sales_desc' => '查看销售单据与销售历史记录',

    // section_customers.php
    'permissions.section_customers' => '3. 客户管理',
    'permissions.module_customers_title' => '客户管理',
    'permissions.module_customers_desc' => '添加、编辑和管理客户/会员信息',


    // section_inventory.php
    'permissions.section_inventory' => '4. 商品与库存',
    'permissions.module_categories_title' => '商品分类',
    'permissions.module_categories_desc' => '管理商品分类与种类',
    'permissions.module_units_title' => '单位管理',
    'permissions.module_units_desc' => '管理商品单位',
    'permissions.module_products_title' => '商品列表',
    'permissions.module_products_desc' => '添加、编辑、调整库存并管理商品',
    'permissions.module_import_stock_title' => '进货',
    'permissions.module_import_stock_desc' => '记录商品入库与新商品进货',
    'permissions.module_import_list_title' => '进货记录',
    'permissions.module_import_list_desc' => '查看商品入库单历史记录',
    'permissions.module_stock_check_title' => '库存盘点（经理）',
    'permissions.module_stock_check_desc' => '新增 = 开始盘点，编辑 = 确认并调整库存，删除 = 删除盘点单',
    'permissions.module_stock_count_title' => '库存清点（盘点员）',
    'permissions.module_stock_count_desc' => '仅录入实盘数量、导出/打印报表',
    'permissions.module_stock_transfer_title' => '分店间调货',
    'permissions.module_stock_transfer_desc' => '将商品调拨至其他分店',
    'permissions.module_transfer_history_title' => '调货历史记录',
    'permissions.module_transfer_history_desc' => '跟踪并取消调货单',

    // section_accounting.php
    'permissions.section_accounting' => '5. 银行与账务管理',
    'permissions.module_accounting_title' => '银行管理',
    'permissions.module_accounting_desc' => '设置银行账户、转账账户及二维码',

    // section_reports.php
    'permissions.section_reports' => '6. 报表',
    'permissions.module_daily_report_title' => '每日报表',
    'permissions.module_daily_report_desc' => '查看每日销售汇总与历史记录',
    'permissions.module_all_sales_title' => '全部销售报表',
    'permissions.module_all_sales_desc' => '按时间段查看整体销售报表',
    'permissions.module_best_seller_title' => '畅销商品报表',
    'permissions.module_best_seller_desc' => '查看最畅销商品排行',
    'permissions.module_profit_cost_title' => '利润-成本报表',
    'permissions.module_profit_cost_desc' => '分析成本、收入与净利润',
    'permissions.module_financial_title' => '财务报表',
    'permissions.module_financial_desc' => '按渠道查看现金/转账收款汇总',
    'permissions.module_category_sales_title' => '按商品类别报表',
    'permissions.module_category_sales_desc' => '查看按分类/种类划分的销售统计',
    'permissions.module_delete_bills_title' => '↳ 销售单删除记录',

    // section_setup.php
    'permissions.section_setup' => '7. 系统设置与用户管理',
    'permissions.module_users_title' => '用户管理',
    'permissions.module_permissions_title' => '权限设置',
    'permissions.module_branches_title' => '分店管理',
    'permissions.module_branches_desc' => '添加、编辑并管理所有分店',
    'permissions.module_stores_title' => '店铺信息',
    'permissions.module_stores_desc' => '管理店铺信息、税号、Logo 及地址',
    'permissions.module_print_barcode_title' => '打印条码',
    'permissions.module_print_barcode_desc' => '打印商品条码与价格标签',
    'permissions.module_exchange_rate_title' => '汇率设置',
    'permissions.module_exchange_rate_desc' => '设置并调整外币汇率',
    'permissions.module_promotions_title' => '促销活动',
    'permissions.module_promotions_desc' => '创建、编辑并管理商品折扣促销活动',
    'permissions.module_price_adjustment_title' => '商品调价',
    'permissions.module_price_adjustment_desc' => '批量调整商品的销售价与进货价',
    'permissions.module_printers_title' => '打印机设置',
    'permissions.module_printers_desc' => '设置小票打印机与条码打印机',
    'permissions.module_database_title' => '数据库管理',
    'permissions.module_database_desc' => '访问、查看统计并管理系统数据库',

    // permissions_js.php
    'permissions.js_alert_title' => '提示',
    'permissions.js_error_title' => '错误',
    'permissions.js_update_perm_fail' => '无法更新权限！',
    'permissions.js_connection_error' => '连接服务器时发生错误！',
    'permissions.js_confirm_preset_title' => '确认快速设置权限？',
    'permissions.js_confirm_preset_text' => '系统将根据所选模板更新此用户的所有模块权限！',
    'permissions.js_confirm_apply' => '确认应用',
    'permissions.js_cancel' => '取消',
    'permissions.js_preset_fail' => '无法快速设置权限！',

    // api/permissions_backend.php - AJAX response messages
    'permissions.msg_invalid_data' => '数据不正确',
    'permissions.msg_admin_full_rights' => '管理员在系统中已拥有 100% 权限，无法修改！',
    'permissions.status_enabled' => '已开启',
    'permissions.status_disabled' => '已关闭',
    'permissions.msg_toggle_success_fmt' => '已将「%s」为 %s %s 成功！',
    'permissions.msg_error_prefix' => '错误：',
    'permissions.msg_user_not_found' => '未找到用户',
    'permissions.msg_preset_not_found' => '未找到所选权限模板',
    'permissions.msg_admin_cannot_change' => '管理员 (Admin) 无法修改权限！',
    'permissions.msg_preset_success_fmt' => '已为 %s 应用「%s」模板成功！',

    // api/permissions_backend.php - $perm_names_lao map (used in success message + activity log)
    'permissions.perm_name_dashboard' => '仪表盘权限',
    'permissions.perm_name_sale' => 'POS 销售权限',
    'permissions.perm_name_item_sales' => '查看销售清单权限',
    'permissions.perm_name_customers' => '客户管理权限',
    'permissions.perm_name_stock' => '商品与库存权限',
    'permissions.perm_name_accounting' => '账务管理权限',
    'permissions.perm_name_report' => '查看报表权限',
    'permissions.perm_name_daily_report' => '每日报表权限',
    'permissions.perm_name_all_sales' => '全部销售报表权限',
    'permissions.perm_name_best_seller' => '畅销商品报表权限',
    'permissions.perm_name_profit_cost' => '利润-成本报表权限',
    'permissions.perm_name_financial' => '财务报表权限',
    'permissions.perm_name_category_sales' => '按商品类别报表权限',
    'permissions.perm_name_delete_bills' => '销售单删除记录权限',
    'permissions.perm_name_users' => '用户管理权限',
    'permissions.perm_name_permissions' => '权限设置权限',
    'permissions.perm_name_branches' => '分店管理权限',
    'permissions.perm_name_setup' => '系统设置权限',
    'permissions.perm_name_edit' => '编辑与删除数据权限',
    'permissions.perm_name_database' => '数据库管理权限',

    // Previously untranslated labels
    'permissions.module_loyalty_title' => '客户积分',
    'permissions.module_loyalty_desc' => '设置规则并管理客户积分',
    'permissions.module_suppliers_title' => '供应商管理',
    'permissions.module_suppliers_desc' => '添加、编辑和管理商品供应商信息',

    // Admin-account edit lock
    'permissions.admin_locked_badge' => '只有第一位管理员可以编辑此账号',
    'permissions.module_shift_close_report_title' => '↳ 交班报表',
    'permissions.module_shift_revenue_report_title' => '↳ 班次营业额报表',
    'permissions.module_clear_data_title' => '清除系统数据',
    'permissions.module_clear_data_desc' => '清除销售单据、交班记录、库存明细或完全重置系统',
    'permissions.module_shifts_title' => '班次管理',
    'permissions.module_shifts_desc' => '设置班次、开关班时间及当班员工',
    'permissions.module_expiry_report_title' => '↳ 过期商品报表',
    'permissions.module_tts_title' => '语音设置',
    'permissions.module_tts_desc' => '开关语音播报、选择声音及编辑发音词典',
];
