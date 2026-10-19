<?php
// pages/categories/* + api/categories_backend.php + pages/categories/api_category.php
// strings - English
return [
    'categories.page_title' => 'Manage Categories',
    'categories.btn_add' => 'Add Category',
    'categories.table_card_title' => 'All Categories Report',
    'categories.col_no' => 'No.',
    'categories.col_code' => 'Code',
    'categories.col_name' => 'Category Name',
    'categories.col_desc' => 'Description',
    'categories.col_printer' => 'Printer',
    'categories.no_printer' => 'No printer',
    'categories.col_created_at' => 'Date Created',
    'categories.col_action' => 'Actions',
    'categories.view_only' => 'View Only',
    'categories.empty_state' => 'No categories found in the system',
    'categories.title_edit' => 'Edit',
    'categories.title_delete' => 'Delete',
    'categories.footer_total' => 'Total categories: %d',

    // SweetAlert / JS strings
    'categories.msg_success_title' => 'Success',
    'categories.msg_error_title' => 'Notice',
    'categories.btn_ok' => 'OK',
    'categories.btn_cancel' => 'Cancel',
    'categories.cannot_delete_title' => 'Cannot Delete!',
    'categories.cannot_delete_msg' => 'Category "{name}" still has {count} product(s) in it.',
    'categories.cannot_delete_hint' => 'Please move or remove the products in this category before deleting it!',
    'categories.confirm_delete_title' => 'Confirm Deletion?',
    'categories.confirm_delete_text' => 'Are you sure you want to delete category "{name}"?',
    'categories.btn_delete_confirm' => 'Delete',

    // Add modal
    'categories.modal_add_title' => 'Add Category',
    'categories.label_code' => 'Category Code',
    'categories.label_name' => 'Category Name',
    'categories.placeholder_name' => 'Enter category name',
    'categories.label_desc' => 'Description',
    'categories.placeholder_desc' => 'Enter description (optional)',
    'categories.btn_save' => 'Save',
    'categories.warn_enter_code_title' => 'Please Enter a Code',
    'categories.warn_code_empty' => 'Category code cannot be empty!',
    'categories.warn_enter_name_title' => 'Please Enter a Name',
    'categories.warn_name_empty' => 'Category name cannot be empty!',

    // Edit modal
    'categories.modal_edit_title' => 'Edit Category',
    'categories.btn_update' => 'Update',

    // api/categories_backend.php messages
    'categories.msg_add_success' => 'Category added successfully!',
    'categories.msg_add_error_prefix' => 'Error: this code may already exist, or ',
    'categories.msg_add_incomplete' => 'Please fill in both the code and the category name!',
    'categories.msg_edit_success' => 'Category updated successfully!',
    'categories.msg_error_prefix' => 'Error: ',
    'categories.msg_delete_blocked_prefix' => 'This category cannot be deleted! It still has ',
    'categories.msg_delete_blocked_suffix' => ' product(s) in it.',
    'categories.msg_delete_success' => 'Category deleted successfully!',

    // pages/categories/api_category.php messages
    'categories.access_denied' => 'You do not have permission to access this data!',
    'categories.invalid_action' => 'Invalid action',

    // Previously untranslated labels
    'categories.label_printer' => 'Kitchen ticket printer',
    'categories.printer_none' => '— No kitchen ticket —',

    // Bulk-set printer modal
    'categories.btn_bulk_printer' => 'Set printer for all',
    'categories.bulk_printer_title' => 'Set Printer for All Categories',
    'categories.bulk_printer_hint' => 'Pick a printer and confirm — every category in this branch will be set to print its kitchen ticket on this printer (overwrites the current setting).',
    'categories.bulk_printer_confirm_title' => 'Set printer for all categories?',
    'categories.bulk_printer_confirm_body' => 'This will overwrite the current printer of every category in this branch.',
    'categories.btn_confirm' => 'Confirm',

    // Pagination
    'categories.page_showing' => 'Showing',
    'categories.page_of' => 'of',
    'categories.page_page' => 'page',
    'categories.page_all' => 'All',
    'categories.page_items' => 'items',

    // Search
    'categories.count_label' => 'items',
    'categories.search_ph' => 'Search category name...',
    'categories.clear' => 'Clear',
    'categories.no_result' => 'No matching categories found',
];
