<?php
// pages/categories/* + api/categories_backend.php + api/categories_api.php
// strings - Chinese (Simplified)
return [
    'categories.page_title' => '商品分类管理',
    'categories.btn_add' => '新增分类',
    'categories.table_card_title' => '全部商品分类报表',
    'categories.col_no' => '序号',
    'categories.col_code' => '编号',
    'categories.col_name' => '分类名称',
    'categories.col_desc' => '说明',
    'categories.col_printer' => '打印机',
    'categories.no_printer' => '未设置',
    'categories.col_created_at' => '创建日期',
    'categories.col_action' => '操作',
    'categories.view_only' => '仅查看',
    'categories.empty_state' => '系统中暂无分类数据',
    'categories.title_edit' => '编辑',
    'categories.title_delete' => '删除',
    'categories.footer_total' => '共有商品分类 %d 个',

    // SweetAlert / JS strings
    'categories.msg_success_title' => '成功',
    'categories.msg_error_title' => '提示',
    'categories.btn_ok' => '确定',
    'categories.btn_cancel' => '取消',
    'categories.cannot_delete_title' => '无法删除！',
    'categories.cannot_delete_msg' => '分类 "{name}" 下还有 {count} 项商品。',
    'categories.cannot_delete_hint' => '请先移动或删除该分类下的商品，才能删除此分类！',
    'categories.confirm_delete_title' => '确认删除？',
    'categories.confirm_delete_text' => '您确定要删除分类 "{name}" 吗？',
    'categories.btn_delete_confirm' => '立即删除',

    // Add modal
    'categories.modal_add_title' => '新增分类',
    'categories.label_code' => '分类编号',
    'categories.label_name' => '分类名称',
    'categories.placeholder_name' => '请输入分类名称',
    'categories.label_desc' => '说明',
    'categories.placeholder_desc' => '请输入说明（可选）',
    'categories.btn_save' => '保存',
    'categories.warn_enter_code_title' => '请输入编号',
    'categories.warn_code_empty' => '分类编号不能为空！',
    'categories.warn_enter_name_title' => '请输入名称',
    'categories.warn_name_empty' => '分类名称不能为空！',

    // Edit modal
    'categories.modal_edit_title' => '编辑分类',
    'categories.btn_update' => '更新',

    // api/categories_backend.php messages
    'categories.msg_add_success' => '新增分类成功！',
    'categories.msg_add_error_prefix' => '错误：该编号可能已存在，或 ',
    'categories.msg_add_incomplete' => '请完整填写编号和分类名称！',
    'categories.msg_edit_success' => '编辑分类成功！',
    'categories.msg_error_prefix' => '错误：',
    'categories.msg_delete_blocked_prefix' => '无法删除此分类！该分类下还有 ',
    'categories.msg_delete_blocked_suffix' => ' 项商品。',
    'categories.msg_delete_success' => '删除分类成功！',

    // api/categories_api.php messages
    'categories.access_denied' => '您无权访问此数据！',
    'categories.invalid_action' => '无效的操作',

    // Previously untranslated labels
    'categories.label_printer' => '厨房小票打印机',
    'categories.printer_none' => '— 不打印厨房小票 —',

    // Bulk-set printer modal
    'categories.btn_bulk_printer' => '批量设置打印机',
    'categories.bulk_printer_title' => '为所有分类设置打印机',
    'categories.bulk_printer_hint' => '选择打印机后确认 — 本分店的所有商品分类都会设置为打印小票到此打印机（覆盖原有设置）。',
    'categories.bulk_printer_confirm_title' => '确认为所有分类设置打印机？',
    'categories.bulk_printer_confirm_body' => '这将覆盖本分店所有分类目前的打印机设置。',
    'categories.btn_confirm' => '确认',

    // Pagination
    'categories.page_showing' => '显示',
    'categories.page_of' => '共',
    'categories.page_page' => '页',
    'categories.page_all' => '全部',
    'categories.page_items' => '项',

    // Search
    'categories.count_label' => '项',
    'categories.search_ph' => '搜索分类名称...',
    'categories.clear' => '清除',
    'categories.no_result' => '未找到符合搜索条件的分类',
];
