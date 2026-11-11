<?php
// pages/permissions/* + api/permissions_backend.php strings - Thai
return [
    // permissions_header.php
    'permissions.header_title' => 'กำหนดสิทธิ์การใช้งาน',
    'permissions.btn_manage_users' => 'จัดการผู้ใช้งาน',

    // shared badges / labels
    'permissions.badge_super_admin' => 'Admin',
    'permissions.role_admin_badge' => 'ผู้บริหาร',
    'permissions.role_employee_default' => 'พนักงาน',
    'permissions.branch_prefix' => 'สาขา',

    // matrix_header_component.php - quick preset dropdown
    'permissions.quick_preset_btn' => 'กำหนดสิทธิ์ด่วน',
    'permissions.quick_preset_header' => 'เลือกรูปแบบสิทธิ์ (Presets)',
    'permissions.preset_cashier' => 'พนักงานขาย POS',
    'permissions.preset_accountant' => 'เจ้าหน้าที่บัญชี',
    'permissions.preset_stock_keeper' => 'พนักงานคลังสินค้า',
    'permissions.preset_auditor' => 'ผู้ตรวจสอบบัญชี',
    'permissions.preset_manager' => 'ผู้บริหาร / จัดการทั้งหมด',
    'permissions.preset_all_on' => 'เปิดสิทธิ์ทั้งหมด',
    'permissions.preset_all_off' => 'ปิดสิทธิ์ทั้งหมด',
    'permissions.admin_full_badge' => 'เปิดสิทธิ์ 100% (ผู้บริหาร)',

    // user_list_sidebar.php
    'permissions.users_list_title' => 'รายชื่อผู้ใช้งาน',
    'permissions.badge_account_suffix' => 'บัญชี',
    'permissions.search_placeholder' => 'ค้นหาชื่อผู้ใช้ หรือ ตำแหน่ง...',
    'permissions.no_users_found' => 'ไม่พบข้อมูลผู้ใช้งานในระบบ',

    // permissions_matrix_table.php
    'permissions.select_user_prompt' => 'กรุณาเลือกผู้ใช้งานจากรายชื่อทางซ้ายเพื่อตั้งค่าสิทธิ์',
    'permissions.col_menu' => 'เมนูระบบ (Sidebar Menu)',
    'permissions.action_view' => 'ดู',
    'permissions.action_add' => 'เพิ่ม',
    'permissions.action_edit' => 'แก้ไข',
    'permissions.action_delete' => 'ลบ',
    'permissions.na' => 'ไม่มี',

    // section_main_menu.php
    'permissions.section_main_menu' => '1. เมนู',
    'permissions.module_dashboard_title' => 'แดชบอร์ด',
    'permissions.module_dashboard_desc' => 'เข้าถึงและดูสถิติหน้าแดชบอร์ดหลัก',

    // section_pos_sales.php
    'permissions.section_pos_sales' => '2. ขายสินค้า POS',
    'permissions.module_sale_title' => 'ขายสินค้า',
    'permissions.module_sale_desc' => 'เข้าถึงหน้าคิดเงินและขายสินค้า POS',
    'permissions.module_item_sales_title' => '↳ รายการขายสินค้า (Sales List)',
    'permissions.module_item_sales_desc' => 'ดูรายการบิลขายและประวัติการขาย',

    // section_customers.php
    'permissions.section_customers' => '3. จัดการลูกค้า',
    'permissions.module_customers_title' => 'จัดการลูกค้า',
    'permissions.module_customers_desc' => 'เพิ่ม แก้ไข และจัดการข้อมูลลูกค้า/สมาชิก',


    // section_inventory.php
    'permissions.section_inventory' => '4. ข้อมูลสินค้า & คลังสินค้า',
    'permissions.module_categories_title' => 'หมวดหมู่สินค้า',
    'permissions.module_categories_desc' => 'จัดการหมวดหมู่และประเภทสินค้า',
    'permissions.module_units_title' => 'จัดการหน่วยนับ',
    'permissions.module_units_desc' => 'จัดการหน่วยนับสินค้า',
    'permissions.module_products_title' => 'รายการสินค้า',
    'permissions.module_products_desc' => 'เพิ่ม แก้ไข ปรับสต๊อก และจัดการสินค้า',
    'permissions.module_import_stock_title' => 'นำเข้าสินค้า',
    'permissions.module_import_stock_desc' => 'บันทึกการรับและนำเข้าสินค้าใหม่',
    'permissions.module_import_list_title' => 'รายการสินค้ารับเข้า',
    'permissions.module_import_list_desc' => 'ดูประวัติใบรับสินค้าเข้า',
    'permissions.module_stock_check_title' => 'เช็กสต็อก (ผู้จัดการ)',
    'permissions.module_stock_check_desc' => 'เพิ่ม = เริ่มนับ, แก้ไข = ยืนยันปรับสต็อก, ลบ = ลบใบ',
    'permissions.module_stock_count_title' => 'นับสต็อก (พนักงานนับ)',
    'permissions.module_stock_count_desc' => 'ป้อนจำนวนจริง, ส่งออก/พิมพ์รายงาน เท่านั้น',
    'permissions.module_stock_transfer_title' => 'โอนสินค้าระหว่างสาขา',
    'permissions.module_stock_transfer_desc' => 'โอนสินค้าไปสาขาอื่น',
    'permissions.module_transfer_history_title' => 'ประวัติการโอนสินค้า',
    'permissions.module_transfer_history_desc' => 'ติดตามและยกเลิกใบโอนสินค้า',

    // section_accounting.php
    'permissions.section_accounting' => '5. จัดการธนาคาร & บัญชี',
    'permissions.module_accounting_title' => 'จัดการธนาคาร',
    'permissions.module_accounting_desc' => 'ตั้งค่าบัญชีธนาคาร บัญชีโอน และ QR Code',

    // section_reports.php
    'permissions.section_reports' => '6. รายงาน',
    'permissions.module_daily_report_title' => 'รายงานประจำวัน',
    'permissions.module_daily_report_desc' => 'ดูสรุปยอดขายและประวัติประจำวัน',
    'permissions.module_all_sales_title' => 'รายงานการขายทั้งหมด',
    'permissions.module_all_sales_desc' => 'ดูรายงานการขายรวมทั้งหมดตามช่วงเวลา',
    'permissions.module_best_seller_title' => 'รายงานสินค้าขายดี',
    'permissions.module_best_seller_desc' => 'ดูอันดับสินค้าที่ขายดีที่สุด',
    'permissions.module_profit_cost_title' => 'รายงานกำไร-ต้นทุน',
    'permissions.module_profit_cost_desc' => 'วิเคราะห์ต้นทุน รายรับ และกำไรสุทธิ',
    'permissions.module_financial_title' => 'รายงานการเงิน',
    'permissions.module_financial_desc' => 'ดูสรุปการรับเงินสด/โอน ตามช่องทาง',
    'permissions.module_category_sales_title' => 'รายงานตามประเภทสินค้า',
    'permissions.module_category_sales_desc' => 'ดูสถิติยอดขายแยกตามหมวดหมู่/ประเภท',
    'permissions.module_delete_bills_title' => '↳ ประวัติการลบบิลขาย',

    // section_setup.php
    'permissions.section_setup' => '7. ตั้งค่าระบบ & จัดการผู้ใช้งาน',
    'permissions.module_users_title' => 'จัดการผู้ใช้งาน',
    'permissions.module_permissions_title' => 'กำหนดสิทธิ์',
    'permissions.module_branches_title' => 'จัดการสาขา',
    'permissions.module_branches_desc' => 'เพิ่ม แก้ไข และจัดการสาขาทั้งหมด',
    'permissions.module_stores_title' => 'ข้อมูลร้าน',
    'permissions.module_stores_desc' => 'จัดการข้อมูลร้าน เลขผู้เสียภาษี โลโก้ และที่อยู่',
    'permissions.module_print_barcode_title' => 'พิมพ์บาร์โค้ด',
    'permissions.module_print_barcode_desc' => 'พิมพ์บาร์โค้ดและราคาสินค้าออกกระดาษ',
    'permissions.module_exchange_rate_title' => 'อัตราแลกเปลี่ยนเงิน',
    'permissions.module_exchange_rate_desc' => 'ตั้งค่าและปรับอัตราแลกเปลี่ยนเงินตราต่างประเทศ',
    'permissions.module_promotions_title' => 'โปรโมชั่น',
    'permissions.module_promotions_desc' => 'สร้าง แก้ไข และจัดการโปรโมชั่นส่วนลดสินค้า',
    'permissions.module_price_adjustment_title' => 'ปรับราคาสินค้า',
    'permissions.module_price_adjustment_desc' => 'ปรับราคาขายและราคาซื้อสินค้าเป็นชุด',
    'permissions.module_printers_title' => 'ตั้งค่าเครื่องพิมพ์',
    'permissions.module_printers_desc' => 'ตั้งค่าเครื่องพิมพ์ใบเสร็จและบาร์โค้ด',
    'permissions.module_database_title' => 'จัดการฐานข้อมูล',
    'permissions.module_database_desc' => 'เข้าถึงและดูสถิติ/จัดการฐานข้อมูลระบบ',

    // permissions_js.php
    'permissions.js_alert_title' => 'แจ้งเตือน',
    'permissions.js_error_title' => 'ข้อผิดพลาด',
    'permissions.js_update_perm_fail' => 'ไม่สามารถอัปเดตสิทธิ์ได้!',
    'permissions.js_connection_error' => 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์!',
    'permissions.js_confirm_preset_title' => 'ยืนยันการกำหนดสิทธิ์ด่วน?',
    'permissions.js_confirm_preset_text' => 'ระบบจะอัปเดตสิทธิ์ทุกโมดูลของผู้ใช้งานนี้ตามรูปแบบที่เลือก!',
    'permissions.js_confirm_apply' => 'ยืนยันการใช้งาน',
    'permissions.js_cancel' => 'ยกเลิก',
    'permissions.js_preset_fail' => 'ไม่สามารถกำหนดสิทธิ์ด่วนได้!',

    // api/permissions_backend.php - AJAX response messages
    'permissions.msg_invalid_data' => 'ข้อมูลไม่ถูกต้อง',
    'permissions.msg_admin_full_rights' => 'ผู้บริหารมีสิทธิ์เต็ม 100% ในระบบอยู่แล้ว ไม่สามารถปรับเปลี่ยนสิทธิ์ได้!',
    'permissions.status_enabled' => 'เปิดสิทธิ์',
    'permissions.status_disabled' => 'ปิดสิทธิ์',
    'permissions.msg_toggle_success_fmt' => '%s "%s" ให้ %s สำเร็จ!',
    'permissions.msg_error_prefix' => 'ข้อผิดพลาด: ',
    'permissions.msg_user_not_found' => 'ไม่พบผู้ใช้งาน',
    'permissions.msg_preset_not_found' => 'ไม่พบรูปแบบสิทธิ์ที่เลือก',
    'permissions.msg_admin_cannot_change' => 'ผู้บริหาร (Admin) ไม่สามารถปรับเปลี่ยนสิทธิ์ได้!',
    'permissions.msg_preset_success_fmt' => 'ใช้รูปแบบ "%s" ให้ %s สำเร็จ!',

    // api/permissions_backend.php - $perm_names_lao map (used in success message + activity log)
    'permissions.perm_name_dashboard' => 'สิทธิ์ แดชบอร์ด',
    'permissions.perm_name_sale' => 'สิทธิ์ ขายสินค้า POS',
    'permissions.perm_name_item_sales' => 'สิทธิ์ ดูรายการขายสินค้า',
    'permissions.perm_name_customers' => 'สิทธิ์ จัดการลูกค้า',
    'permissions.perm_name_stock' => 'สิทธิ์ ข้อมูลสินค้า & คลังสินค้า',
    'permissions.perm_name_accounting' => 'สิทธิ์ จัดการบัญชี',
    'permissions.perm_name_report' => 'สิทธิ์ ดูรายงาน',
    'permissions.perm_name_daily_report' => 'สิทธิ์ รายงานประจำวัน',
    'permissions.perm_name_all_sales' => 'สิทธิ์ รายงานการขายทั้งหมด',
    'permissions.perm_name_best_seller' => 'สิทธิ์ รายงานสินค้าขายดี',
    'permissions.perm_name_profit_cost' => 'สิทธิ์ รายงานกำไร-ต้นทุน',
    'permissions.perm_name_financial' => 'สิทธิ์ รายงานการเงิน',
    'permissions.perm_name_category_sales' => 'สิทธิ์ รายงานตามประเภทสินค้า',
    'permissions.perm_name_delete_bills' => 'สิทธิ์ ประวัติการลบบิลขาย',
    'permissions.perm_name_users' => 'สิทธิ์ จัดการผู้ใช้งาน',
    'permissions.perm_name_permissions' => 'สิทธิ์ กำหนดสิทธิ์',
    'permissions.perm_name_branches' => 'สิทธิ์ จัดการสาขา',
    'permissions.perm_name_setup' => 'สิทธิ์ ตั้งค่าระบบ',
    'permissions.perm_name_edit' => 'สิทธิ์ แก้ไข & ลบข้อมูล',
    'permissions.perm_name_database' => 'สิทธิ์ จัดการฐานข้อมูล',

    // Previously untranslated labels
    'permissions.module_loyalty_title' => 'คะแนนสะสมลูกค้า',
    'permissions.module_loyalty_desc' => 'ตั้งค่ากฎเกณฑ์และจัดการคะแนนสะสมของลูกค้า',
    'permissions.module_suppliers_title' => 'จัดการผู้จัดจำหน่าย',
    'permissions.module_suppliers_desc' => 'เพิ่ม แก้ไข และจัดการข้อมูลผู้จัดจำหน่ายสินค้า',

    // Admin-account edit lock
    'permissions.admin_locked_badge' => 'มีเฉพาะผู้บริหารคนแรกเท่านั้นที่แก้ไขได้',
    'permissions.module_shift_close_report_title' => '↳ รายงานปิดกะ',
    'permissions.module_shift_revenue_report_title' => '↳ รายงานรายได้ตามกะ',
    'permissions.module_clear_data_title' => 'ลบ/ล้างข้อมูลระบบ',
    'permissions.module_clear_data_desc' => 'ลบข้อมูลการขาย, ประวัติกะ, สต็อก หรือล้างข้อมูลระบบทั้งหมด',
    'permissions.module_shifts_title' => 'จัดการกะทำงาน',
    'permissions.module_shifts_desc' => 'ตั้งค่ากะ เวลาเปิด-ปิด และพนักงานประจำกะ',
    'permissions.module_expiry_report_title' => '↳ รายงานสินค้าหมดอายุ',
    'permissions.module_tts_title' => 'ตั้งค่าระบบเสียง',
    'permissions.module_tts_desc' => 'เปิด/ปิดเสียงพูด เลือกเสียง และพจนานุกรมการออกเสียง',
];
