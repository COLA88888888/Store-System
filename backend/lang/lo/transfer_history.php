<?php
// pages/import_stock/transfer_history.php + components/history_*.php
// strings - Lao baseline (original text)
return [
    'transfer_history.page_title' => 'ປະຫວັດການໂອນສິນຄ້າ',
    'transfer_history.page_subtitle' => 'ຕິດຕາມ ແລະ ກວດສອບລາຍການໂອນສິນຄ້າລະຫວ່າງສາຂາ',
    'transfer_history.new_transfer_btn' => 'ໂອນສິນຄ້າໃໝ່',
    'transfer_history.msg_success_title' => 'ສຳເລັດ',
    'transfer_history.msg_error_title' => 'ແຈ້ງເຕືອນ',

    // Filter form
    'transfer_history.from_date' => 'ຕັ້ງແຕ່ວັນທີ:',
    'transfer_history.to_date' => 'ຫາວັນທີ:',
    'transfer_history.from_store' => 'ສາຂາຕົ້ນທາງ:',
    'transfer_history.to_store' => 'ສາຂາປາຍທາງ:',
    'transfer_history.all_opt' => '-- ທັງໝົດ --',
    'transfer_history.search_label' => 'ຄົ້ນຫາລະຫັດ/ໝາຍເຫດ:',
    'transfer_history.search_placeholder' => 'ລະຫັດໂອນ, ໝາຍເຫດ...',
    'transfer_history.search_btn' => 'ຄົ້ນຫາ',
    'transfer_history.clear_btn' => 'ລ້າງຄ່າ',

    // Table
    'transfer_history.col_no' => 'ລຳດັບ',
    'transfer_history.col_code' => 'ລະຫັດໃບໂອນ',
    'transfer_history.col_date' => 'ວັນທີໂອນ',
    'transfer_history.col_creator' => 'ຜູ້ໂອນ',
    'transfer_history.col_from_store' => 'ສາຂາຕົ້ນທາງ',
    'transfer_history.col_to_store' => 'ສາຂາປາຍທາງ',
    'transfer_history.col_items' => 'ລາຍການສິນຄ້າ',
    'transfer_history.col_total_qty' => 'ຈຳນວນລວມ',
    'transfer_history.col_status' => 'ສະຖານະ',
    'transfer_history.col_view' => 'ເບິ່ງ',
    'transfer_history.empty_state' => 'ບໍ່ພົບປະຫວັດການໂອນສິນຄ້າ',
    'transfer_history.view_detail_title' => 'ກົດເພື່ອເບິ່ງລາຍລະອຽດ',
    'transfer_history.items_unit' => 'ລາຍການ',
    'transfer_history.status_completed' => 'ສຳເລັດ',
    'transfer_history.status_cancelled' => 'ຍົກເລີກ',
    'transfer_history.view_detail_btn_title' => 'ເບິ່ງລາຍລະອຽດ',

    // Detail modal
    'transfer_history.modal_title' => 'ລາຍລະອຽດໃບໂອນສິນຄ້າ',
    'transfer_history.modal_from_store' => 'ສາຂາຕົ້ນທາງ:',
    'transfer_history.modal_to_store' => 'ສາຂາປາຍທາງ:',
    'transfer_history.modal_date' => 'ວັນທີໂອນ:',
    'transfer_history.modal_creator' => 'ຜູ້ດຳເນີນການ:',
    'transfer_history.modal_status' => 'ສະຖານະ:',
    'transfer_history.modal_items_heading' => 'ລາຍການສິນຄ້າທີ່ໂອນ:',
    'transfer_history.modal_col_product' => 'ຊື່ສິນຄ້າ',
    'transfer_history.modal_col_barcode' => 'ບາໂຄ້ດ',
    'transfer_history.modal_col_qty' => 'ຈຳນວນໂອນ',
    'transfer_history.modal_col_unit' => 'ຫົວໜ່ວຍ',
    'transfer_history.modal_notes_label' => 'ໝາຍເຫດ (Notes):',
    'transfer_history.modal_close_btn' => 'ປິດ',
    'transfer_history.default_unit' => 'ອັນ',

    // JS
    'transfer_history.js_error_title' => 'ຜິດພາດ',
    'transfer_history.js_load_failed' => 'ບໍ່ສາມາດໂຫຼດຂໍ້ມູນໄດ້',
    'transfer_history.js_connection_error' => 'ເກີດຂໍ້ຜິດພາດໃນການເຊື່ອມຕໍ່',
    'transfer_history.js_cancel_confirm_title' => 'ຢືນຢັນການຍົກເລີກໃບໂອນ?',
    'transfer_history.js_transfer_code_label' => 'ເລກທີໃບໂອນ:',
    'transfer_history.js_cancel_confirm_hint' => 'ລະບົບຈະຫັກສະຕັອກຄືນຈາກສາຂາປາຍທາງ ແລະ ເພີ່ມຄືນໃຫ້ສາຂາຕົ້ນທາງ.',
    'transfer_history.js_confirm_cancel_btn' => 'ຢືນຢັນຍົກເລີກ',
    'transfer_history.js_close_btn' => 'ປິດ',
    'transfer_history.js_cancel_success' => 'ຍົກເລີກໃບໂອນສຳເລັດ!',
    'transfer_history.js_generic_error' => 'ຜິດພາດ!',

    // Backend $message (transfer_history.php)
    'transfer_history.err_not_found' => 'ບໍ່ພົບຂໍ້ມູນໃບໂອນນີ້ໃນລະບົບ!',
    'transfer_history.err_already_cancelled' => 'ໃບໂອນນີ້ຖືກຍົກເລີກໄປແລ້ວ!',
    'transfer_history.err_stock_used' => 'ບໍ່ສາມາດຍົກເລີກໃບໂອນໄດ້ ເນື່ອງຈາກສິນຄ້າ "%s" ໃນສາຂາປາຍທາງຖືກໃຊ້ ຫຼື ຂາຍໄປແລ້ວ (ສະຕັອກປັດຈຸບັນມີ: %s, ຕ້ອງການຫັກຄືນ: %s)!',
    'transfer_history.msg_cancel_success' => 'ຍົກເລີກໃບໂອນເລກທີ %s ແລະ ຄືນສະຕັອກເຂົ້າຕົ້ນທາງສຳເລັດ!',
    'transfer_history.err_prefix' => 'ຜິດພາດ:',
];
