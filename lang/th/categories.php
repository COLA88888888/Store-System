<?php
// pages/categories/* + api/categories_backend.php + pages/categories/api_category.php
// strings - Thai
return [
    'categories.page_title' => 'จัดการหมวดหมู่สินค้า',
    'categories.btn_add' => 'เพิ่มหมวดหมู่สินค้า',
    'categories.table_card_title' => 'รายงานหมวดหมู่สินค้าทั้งหมด',
    'categories.col_no' => 'ลำดับ',
    'categories.col_code' => 'รหัส',
    'categories.col_name' => 'ชื่อหมวดหมู่สินค้า',
    'categories.col_desc' => 'รายละเอียด',
    'categories.col_created_at' => 'วันที่บันทึก',
    'categories.col_action' => 'จัดการ',
    'categories.view_only' => 'ดูอย่างเดียว',
    'categories.empty_state' => 'ไม่พบข้อมูลหมวดหมู่สินค้าในระบบ',
    'categories.title_edit' => 'แก้ไข',
    'categories.title_delete' => 'ลบ',
    'categories.footer_total' => 'ข้อมูลหมวดหมู่สินค้าทั้งหมด %d หมวดหมู่',

    // SweetAlert / JS strings
    'categories.msg_success_title' => 'สำเร็จ',
    'categories.msg_error_title' => 'แจ้งเตือน',
    'categories.btn_ok' => 'ตกลง',
    'categories.btn_cancel' => 'ยกเลิก',
    'categories.cannot_delete_title' => 'ไม่สามารถลบได้!',
    'categories.cannot_delete_msg' => 'หมวดหมู่ "{name}" มีรายการสินค้าอยู่ {count} รายการ.',
    'categories.cannot_delete_hint' => 'กรุณาย้าย หรือ ลบรายการสินค้าในหมวดหมู่นี้ออกก่อน จึงจะสามารถลบได้!',
    'categories.confirm_delete_title' => 'ยืนยันการลบ?',
    'categories.confirm_delete_text' => 'ท่านต้องการลบหมวดหมู่สินค้า "{name}" ใช่หรือไม่?',
    'categories.btn_delete_confirm' => 'ลบเลย',

    // Add modal
    'categories.modal_add_title' => 'เพิ่มหมวดหมู่สินค้า',
    'categories.label_code' => 'รหัสหมวดหมู่สินค้า',
    'categories.label_name' => 'ชื่อหมวดหมู่สินค้า',
    'categories.placeholder_name' => 'ป้อนชื่อหมวดหมู่สินค้า',
    'categories.label_desc' => 'รายละเอียด',
    'categories.placeholder_desc' => 'ป้อนรายละเอียด (ไม่บังคับ)',
    'categories.btn_save' => 'บันทึก',
    'categories.warn_enter_code_title' => 'กรุณาป้อนรหัส',
    'categories.warn_code_empty' => 'รหัสหมวดหมู่สินค้าไม่สามารถเว้นว่างได้!',
    'categories.warn_enter_name_title' => 'กรุณาป้อนชื่อ',
    'categories.warn_name_empty' => 'ชื่อหมวดหมู่สินค้าไม่สามารถเว้นว่างได้!',

    // Edit modal
    'categories.modal_edit_title' => 'แก้ไขหมวดหมู่สินค้า',
    'categories.btn_update' => 'อัปเดต',

    // api/categories_backend.php messages
    'categories.msg_add_success' => 'เพิ่มหมวดหมู่สินค้าสำเร็จ!',
    'categories.msg_add_error_prefix' => 'ผิดพลาด: รหัสนี้อาจซ้ำกัน หรือ ',
    'categories.msg_add_incomplete' => 'กรุณาป้อนรหัส และ ชื่อหมวดหมู่สินค้าให้ครบ!',
    'categories.msg_edit_success' => 'แก้ไขหมวดหมู่สินค้าสำเร็จ!',
    'categories.msg_error_prefix' => 'ผิดพลาด: ',
    'categories.msg_delete_blocked_prefix' => 'ไม่สามารถลบหมวดหมู่สินค้านี้ได้! เพราะมีรายการสินค้าในหมวดหมู่นี้ ',
    'categories.msg_delete_blocked_suffix' => ' รายการ.',
    'categories.msg_delete_success' => 'ลบหมวดหมู่สินค้าสำเร็จ!',

    // pages/categories/api_category.php messages
    'categories.access_denied' => 'ท่านไม่มีสิทธิ์เข้าถึงข้อมูลนี้!',
    'categories.invalid_action' => 'คำสั่งไม่ถูกต้อง',

    // Previously untranslated labels
    'categories.label_printer' => 'เครื่องพิมพ์ใบย่อย (ใบสั่งครัว)',
    'categories.printer_none' => '— ไม่พิมพ์ใบย่อย —',

    // Pagination
    'categories.page_showing' => 'แสดง',
    'categories.page_of' => 'จาก',
    'categories.page_page' => 'หน้า',
    'categories.page_all' => 'ทั้งหมด',
    'categories.page_items' => 'รายการ',

    // Search
    'categories.count_label' => 'รายการ',
    'categories.search_ph' => 'ค้นหาชื่อหมวดหมู่...',
    'categories.clear' => 'ล้างค่า',
    'categories.no_result' => 'ไม่พบหมวดหมู่ที่ค้นหา',
];
