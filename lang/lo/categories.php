<?php
// pages/categories/* + api/categories_backend.php + pages/categories/api_category.php
// strings - Lao baseline (original text)
return [
    'categories.page_title' => 'ຈັດການປະເພດສິນຄ້າ',
    'categories.btn_add' => 'ເພີ່ມປະເພດສິນຄ້າ',
    'categories.table_card_title' => 'ລາຍງານປະເພດສິນຄ້າທັງໝົດ',
    'categories.col_no' => 'ລຳດັບ',
    'categories.col_code' => 'ລະຫັດ',
    'categories.col_name' => 'ຊື່ປະເພດສິນຄ້າ',
    'categories.col_desc' => 'ລາຍລະອຽດ',
    'categories.col_printer' => 'ເຄື່ອງພິມ',
    'categories.no_printer' => 'ບໍ່ໄດ້ຕັ້ງ',
    'categories.col_created_at' => 'ວັນທີບັນທຶກ',
    'categories.col_action' => 'ຈັດການ',
    'categories.view_only' => 'ເບິ່ງຢ່າງດຽວ',
    'categories.empty_state' => 'ບໍ່ພົບຂໍ້ມູນປະເພດສິນຄ້າໃນລະບົບ',
    'categories.title_edit' => 'ແກ້ໄຂ',
    'categories.title_delete' => 'ລົບ',
    'categories.footer_total' => 'ຂໍ້ມູນປະເພດສິນຄ້າທັງໝົດ %d ໝວດໝູ່',

    // SweetAlert / JS strings
    'categories.msg_success_title' => 'ສຳເລັດ',
    'categories.msg_error_title' => 'ແຈ້ງເຕືອນ',
    'categories.btn_ok' => 'ຕົກລົງ',
    'categories.btn_cancel' => 'ຍົກເລີກ',
    'categories.cannot_delete_title' => 'ບໍ່ສາມາດລົບໄດ້!',
    'categories.cannot_delete_msg' => 'ໝວດໝູ່ "{name}" ມີລາຍການສິນຄ້າຢູ່ {count} ລາຍການ.',
    'categories.cannot_delete_hint' => 'ກະລຸນາຍ້າຍ ຫຼື ລົບລາຍການສິນຄ້າໃນໝວດໝູ່ນີ້ອອກກ່ອນ ຈຶ່ງຈະສາມາດລົບໄດ້!',
    'categories.confirm_delete_title' => 'ຢືນຢັນການລົບ?',
    'categories.confirm_delete_text' => 'ທ່ານຕ້ອງການລົບປະເພດສິນຄ້າ "{name}" ແທ້ຫຼືບໍ່?',
    'categories.btn_delete_confirm' => 'ລົບເລີຍ',

    // Add modal
    'categories.modal_add_title' => 'ເພີ່ມປະເພດສິນຄ້າ',
    'categories.label_code' => 'ລະຫັດປະເພດສິນຄ້າ',
    'categories.placeholder_code' => 'ປ້ອນລະຫັດ (ເຊັ່ນ: 1, 2, 3...)',
    'categories.hint_code' => 'ລະຫັດຈະລຽນລຳດັບອັດຕະໂນມັດ ຫຼື ທ່ານສາມາດປ້ອນລະຫັດເອງໄດ້',
    'categories.label_name' => 'ຊື່ປະເພດສິນຄ້າ',
    'categories.placeholder_name' => 'ປ້ອນຊື່ປະເພດສິນຄ້າ',
    'categories.label_desc' => 'ລາຍລະອຽດ',
    'categories.placeholder_desc' => 'ປ້ອນລາຍລະອຽດ(ບໍ່ບັງຄັບ)',
    'categories.btn_save' => 'ບັນທຶກ',
    'categories.warn_enter_code_title' => 'ກະລຸນາປ້ອນລະຫັດ',
    'categories.warn_code_empty' => 'ລະຫັດປະເພດສິນຄ້າບໍ່ສາມາດຫວ່າງໄດ້!',
    'categories.warn_enter_name_title' => 'ກະລຸນາປ້ອນຊື່',
    'categories.warn_name_empty' => 'ຊື່ປະເພດສິນຄ້າບໍ່ສາມາດຫວ່າງໄດ້!',

    // Edit modal
    'categories.modal_edit_title' => 'ແກ້ໄຂປະເພດສິນຄ້າ',
    'categories.btn_update' => 'ອັບເດດ',

    // api/categories_backend.php messages
    'categories.msg_add_success' => 'ເພີ່ມປະເພດສິນຄ້າສຳເລັດ!',
    'categories.msg_add_error_prefix' => 'ຜິດພາດ: ລະຫັດນີ້ອາດຊ້ຳກັນ ຫຼື ',
    'categories.msg_add_incomplete' => 'ກະລຸນາປ້ອນລະຫັດ ແລະ ຊື່ປະເພດສິນຄ້າໃຫ້ຄົບ!',
    'categories.msg_edit_success' => 'ແກ້ໄຂປະເພດສິນຄ້າສຳເລັດ!',
    'categories.msg_error_prefix' => 'ຜິດພາດ: ',
    'categories.msg_delete_blocked_prefix' => 'ບໍ່ສາມາດລົບປະເພດສິນຄ້ານີ້ໄດ້! ເພາະມີລາຍການສິນຄ້າໃນໝວດໝູ່ນີ້ ',
    'categories.msg_delete_blocked_suffix' => ' ລາຍການ.',
    'categories.msg_delete_success' => 'ລົບປະເພດສິນຄ້າສຳເລັດ!',

    // pages/categories/api_category.php messages
    'categories.access_denied' => 'ທ່ານບໍ່ມີສິດເຂົ້າເຖິງຂໍ້ມູນນີ້!',
    'categories.invalid_action' => 'ຄຳສັ່ງບໍ່ຖືກຕ້ອງ',

    // Previously untranslated labels
    'categories.label_printer' => 'ເຄື່ອງພິມໃບຍ່ອຍ (kitchen ticket)',
    'categories.printer_none' => '— ບໍ່ພິມໃບຍ່ອຍ —',

    // Bulk-set printer modal
    'categories.btn_bulk_printer' => 'ຕັ້ງເຄື່ອງພິມທຸກໝວດ',
    'categories.bulk_printer_title' => 'ຕັ້ງເຄື່ອງພິມໃຫ້ທຸກໝວດໝູ່',
    'categories.bulk_printer_hint' => 'ເລືອກເຄື່ອງພິມແລ້ວກົດຢືນຢັນ — ທຸກໝວດໝູ່ສິນຄ້າໃນສາຂານີ້ຈະຖືກຕັ້ງໃຫ້ພິມໃບຍ່ອຍໄປເຄື່ອງນີ້ໝົດ (ທັບຄ່າເກົ່າ).',
    'categories.bulk_printer_confirm_title' => 'ຢືນຢັນຕັ້ງເຄື່ອງພິມທຸກໝວດ?',
    'categories.bulk_printer_confirm_body' => 'ຈະທັບຄ່າເຄື່ອງພິມເດີມຂອງທຸກໝວດໝູ່ໃນສາຂານີ້ດ້ວຍຄ່າໃໝ່ນີ້.',
    'categories.btn_confirm' => 'ຢືນຢັນ',

    // Pagination
    'categories.page_showing' => 'ສະແດງ',
    'categories.page_of' => 'ຈາກ',
    'categories.page_page' => 'ໜ້າ',
    'categories.page_all' => 'ທັງໝົດ',
    'categories.page_items' => 'ລາຍການ',

    // Search
    'categories.count_label' => 'ລາຍການ',
    'categories.search_ph' => 'ຄົ້ນຫາຊື່ໝວດໝູ່...',
    'categories.clear' => 'ລ້າງຄ່າ',
    'categories.no_result' => 'ບໍ່ພົບໝວດໝູ່ທີ່ຄົ້ນຫາ',
];
