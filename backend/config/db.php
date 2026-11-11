<?php
/**
 * ໂຄງສ້າງໄຟລ໌ (ແຍກເປັນໄຟລ໌ຍ່ອຍເພື່ອໃຫ້ອ່ານງ່າຍ — ແຕ່ລະໄຟລ໌ບໍ່ເກີນ 500 ແຖວ):
 * config/db.php ແມ່ນຈຸດເລີ່ມຕົ້ນທີ່ທຸກໜ້າ require: ເຊື່ອມຖານຂໍ້ມູນ, ເລີ່ມ session, ກວດສິດ, ອັບເດດໂຄງສ້າງ DB ແລະ ໂຫຼດຟັງຊັນຊ່ວຍທັງໝົດ. ລຳດັບການ require ຂ້າງລຸ່ມສຳຄັນ — ຫ້າມສະຫຼັບ.
 *   - bootstrap/connection.php : ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
 *   - bootstrap/session_auth.php : Session ແລະ ສິດຜູ້ໃຊ້
 *   - migrations/schema_core.php : ອັບເດດໂຄງສ້າງ DB (1): ພື້ນຖານ
 *   - migrations/schema_users_customers.php : ອັບເດດໂຄງສ້າງ DB (2): ຜູ້ໃຊ້ ແລະ ລູກຄ້າ
 *   - migrations/schema_company_banks.php : ອັບເດດໂຄງສ້າງ DB (3): ຮ້ານ, ທະນາຄານ, ໂປຣໂມຊັ່ນ
 *   - migrations/schema_settings_sales.php : ອັບເດດໂຄງສ້າງ DB (4): ການຕັ້ງຄ່າ, ການຂາຍ, ໜີ້
 *   - migrations/schema_loyalty.php : ອັບເດດໂຄງສ້າງ DB: ຄະແນນສະສົມ
 *   - migrations/schema_website.php : ອັບເດດໂຄງສ້າງ DB: ເວັບໄຊໜ້າບ້ານ (ຄຳສັ່ງຊື້, ຂໍ້ຄວາມຕິດຕໍ່)
 *   - helpers/theme_css.php : ຟັງຊັນຊ່ວຍ: ລິ້ງໄຟລ໌ CSS ໃນ themes/ (themeCssLink)
 *   - helpers/money_settings.php : ຟັງຊັນຊ່ວຍ: ການຕັ້ງຄ່າ, ເງິນ, ບັນທຶກ
 *   - helpers/permissions.php : ຟັງຊັນຊ່ວຍ: ກວດສິດ
 *   - helpers/bank.php : ຟັງຊັນຊ່ວຍ: ທະນາຄານ
 *   - helpers/branches.php : ຟັງຊັນຊ່ວຍ: ສາຂາ ແລະ ລະຫັດສິນຄ້າ
 *   - helpers/company_alerts.php : ຟັງຊັນຊ່ວຍ: ຂໍ້ມູນຮ້ານ ແລະ ແຈ້ງເຕືອນ
 *   - bootstrap/branch_switcher.php : ສະຫຼັບສາຂາທີ່ກຳລັງເບິ່ງ
 *   - helpers/report_shift_filter.php : ຟັງຊັນຊ່ວຍ: ຕົວກັ່ນຕອງກະ ຂອງໜ້າລາຍງານ
 */
// ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
require __DIR__ . '/bootstrap/connection.php';
// Session ແລະ ສິດຜູ້ໃຊ້
require __DIR__ . '/bootstrap/session_auth.php';
if (!$pdo) {
    try {
        $pdo = new PDO(
            "mysql:host={$server};port={$port};dbname={$database};charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } catch (Throwable $e) {}
}

if ($pdo) {

    // Migration for database tables & schema (runs once per installation or when flag is missing for extreme performance)
    // Bump the version suffix whenever a new guarded ALTER is added below so every install re-runs the block once.
    // v2: paid_currency / paid_rate / paid_amount_foreign on tbsale_save + sales (multi-currency receipts).
    // v5: tbl_printer created here (it only existed in old dumps) + paper_width for LAN kitchen tickets.
    // v10: tbl_printer.is_receipt_printer + print_jobs table (print agent / off-site server mode).
    // v11: bank_accounts.store_id — each branch now only sees/manages its own bank accounts.
    // v12: tbsale_save.cash_received / qr_received — persist the split-payment breakdown so
    // bill reprints and the reports "bill details" modal show the actual amount received per
    // channel instead of 0 (checkout already computed both, they just weren't saved).
    // v14: tbl_printer.type gains 'usb' — direct RAW printing to a USB printer (api/usb_printer.php).
    // v15: quotations module removed (quotations / quotation_details dropped, tbuser.quotations
    // dropped); customer_debts + customer_debt_payments + tbuser.customer_debt added.
    // v16: categories.branch_no / units.branch_no — per-branch code "<store_code>-<branch_no>".
    // v17: branch_no also on customers, suppliers, tbuser, bank_accounts, promotions, products.
    // v18: customer-debt module removed — customer_debts + customer_debt_payments dropped,
    // tbuser.customer_debt dropped (backup: backups/debts_before_removal_*.sql).
    // v19: tbuser.account_active — ປິດໃຊ້ງານບັນຊີພະນັກງານທີ່ລາອອກ (ເຂົ້າລະບົບບໍ່ໄດ້).
    // v20: tbcompanyinfo.receipt_footer_{en,th,zh} — ຂໍ້ຄວາມທ້າຍໃບບິນຕາມພາສາ.
    // v21: loyalty_rewards.store_id — ລາງວັນແລກຄະແນນແຍກແຕ່ລະສາຂາ.
    // v22: w_web_orders / w_web_order_items / w_web_contacts — ຄຳສັ່ງຊື້ ແລະ ຂໍ້ຄວາມ ຈາກເວັບໄຊ frontend/.
    // v23: w_products.old_price — ລາຄາກ່ອນຫຼຸດ (ຂີດຂ້າ) ສະແດງໃນເວັບໄຊ.
    // v24: w_web_visits — ນັບຜູ້ເຂົ້າຊົມເວັບໄຊລາຍວັນ (footer).
    // v25: w_web_chats / w_web_chat_messages — ຫ້ອງແຊັດໜ້າຕິດຕໍ່ ເວັບໄຊ.
    // v26: w_web_chats.lang + w_web_chat_messages.body_tr — ແຊັດຫຼາຍພາສາ (ແປໃຫ້ພະນັກງານ / ລູກຄ້າ).
    // v27: w_web_visit_ips — ນັບຜູ້ເຂົ້າຊົມເວັບໄຊຕາມ IP (1 IP/ມື້, ບໍ່ນັບເຄື່ອງ server ເອງ).
    // v28: w_web_members — ສະມາຊິກເວັບໄຊ (ເລກສະມາຊິກ 1, 2, 3…), ແຍກຈາກ w_customers.
    // v29: w_web_chats.member_id — ແຊັດຜູກກັບສະມາຊິກເວັບໄຊ.
    // v30: w_web_chats.staff_read_id / visitor_read_id — ສະຖານະ ອ່ານແລ້ວ / ຍັງບໍ່ອ່ານ ໃນແຊັດ.
    // v31: w_web_orders ເພີ່ມຂໍ້ມູນຂົນສົ່ງໃນລາວ + ສາຂາປາຍທາງ, ທະນາຄານ, ຮູບສະລິບໂອນເງິນ ແລະ ເລກໃບຝາກເຄື່ອງ.
    // v32: w_web_orders.status + packing/arrived, w_web_order_status_log — ຕິດຕາມອໍເດີ 6 ຂັ້ນ ພ້ອມເວລາແຕ່ລະຂັ້ນ.
    // v33: w_web_contact_clicks — ນັບການກົດປຸ່ມຕິດຕໍ່ໃນເວັບໄຊ (ໂທ / WhatsApp / Facebook / ແຊັດ / ແຜນທີ່) ຕາມໜ້າ.
    // v34: w_web_works — ຜົນງານການຕິດຕັ້ງລະບົບ, ໂລໂກ້ລູກຄ້າ ແລະ ຊື່ຮ້ານລູກຄ້າ (ອັບໂຫຼດຮູບຫຼາຍຮູບພ້ອມກັນຈາກຫຼັງບ້ານ).
    // v35: w_translations — ຄຳແປຊື່ສິນຄ້າ / ໝວດ ເປັນ th / en / zh / vi (ແປອັດຕະໂນມັດເມື່ອເພີ່ມ ຫຼື ແກ້ໃນຫຼັງບ້ານ).
    // v36: ລຶບໂມດູນ ສຽງ, ສິນຄ້າໝົດອາຍຸ, ກະເຮັດວຽກ, ເຊັກສະຕັອກ — ຕາຕະລາງ w_shifts, w_shift_sessions, w_stock_check*,
    //      w_zones, w_shelves, ຖັນສິດໃນ w_tbuser ແລະ ສະວິດສິດລາຍຄົນ (ຖັນ w_products.expiry_date ຍັງຄົງໄວ້).
    // v37: ລຶບລະບົບແພັກເກັດໝົດອາຍຸ — ຖັນ w_tbcompanyinfo.license_start_date / license_expire_date.
    // v38: ສະມາຊິກເວັບລົງທະບຽນລະອຽດ + Google (w_web_members ຂໍ້ມູນຮ້ານ/ທີ່ຢູ່/google_id), w_web_orders.member_id + slip_uploaded_at.
    // v39: w_product_images — ຮູບສິນຄ້າຫຼາຍມຸມ (ຮູບຫຼັກຍັງເປັນ w_products.img_url).
    // v40: w_web_member_tokens — ສະມາຊິກເວັບໄຊ ຈື່ການເຂົ້າສູ່ລະບົບ 180 ມື້ (ບໍ່ຕ້ອງ login ອີກ).
    // v41: w_web_email_queue + w_web_members.email_notify — ແຈ້ງເຕືອນສະມາຊິກທາງອີເມວ (ອໍເດີ, ລົດລາຄາ).
    // v42: w_web_rate_limits — ຈຳກັດການລົງທະບຽນ / login ຕາມ IP (ກັນ bot spam).
    // v43: w_web_chat_messages.file_* — ລູກຄ້າ / ພະນັກງານ ສົ່ງຮູບ, ວິດີໂອ, ໄຟລ໌ ໃນແຊັດ.
    // v44: w_web_member_notifications — ກະດິ່ງແຈ້ງເຕືອນສະມາຊິກ (ອໍເດີ, ລົດລາຄາ, ທີມງານຕອບແຊັດ).
    // v45: w_web_orders.receipt_img / receipt_at — ຮູບບິນຮັບເຄື່ອງຂອງຂົນສົ່ງ (ແທນເລກໃບຝາກເຄື່ອງ).
    // v46: w_web_members.birth_date — ວັນເດືອນປີເກີດ (ລົງທະບຽນ / ໜ້າບັນຊີ).
    $schemaFlagFile = __DIR__ . '/.db_schema_v46';
    if (!file_exists($schemaFlagFile)) {
        // ແລ່ນຄັ້ງດຽວຕໍ່ເວີຊັນ: ອັບເດດໂຄງສ້າງ DB ທັງ 4 ສ່ວນ ຕາມລຳດັບ ແລ້ວຂຽນໄຟລ໌ flag ໄວ້
        require __DIR__ . '/migrations/schema_core.php';             // (1) ພື້ນຖານ
        require __DIR__ . '/migrations/schema_users_customers.php';   // (2) ຜູ້ໃຊ້ ແລະ ລູກຄ້າ
        require __DIR__ . '/migrations/schema_company_banks.php';     // (3) ຮ້ານ, ທະນາຄານ, ໂປຣໂມຊັ່ນ
        require __DIR__ . '/migrations/schema_settings_sales.php';    // (4) ການຕັ້ງຄ່າ, ການຂາຍ, ໜີ້
        // ເມື່ອກ່ອນ 4 ໄຟລ໌ລຸ່ມນີ້ແລ່ນທຸກ session ໃໝ່ (ນອກ flag) → ຕາຕະລາງທີ່ຖືກລຶບກັບມາເອງ. ດຽວນີ້ແລ່ນສະເພາະເມື່ອ
        // ປ່ຽນເລກເວີຊັນ (ສັ່ງປ່ຽນໂຄງສ້າງເອງ) — ບໍ່ມີຕາຕະລາງ ຫຼື ຂໍ້ມູນໃດເກີດຂຶ້ນເອງ (31/10/2026)
        require __DIR__ . '/migrations/schema_loyalty.php';           // ຄະແນນສະສົມ
        require __DIR__ . '/migrations/schema_held_orders.php';       // ບິນທີ່ພັກໄວ້
        require __DIR__ . '/migrations/schema_website.php';           // ເວັບໄຊໜ້າບ້ານ (frontend/)

        @file_put_contents($schemaFlagFile, date('Y-m-d H:i:s'));
    }
}
// ຟັງຊັນຊ່ວຍ: ລິ້ງໄຟລ໌ CSS ໃນ themes/ (themeCssLink)
require __DIR__ . '/helpers/theme_css.php';
// ຟັງຊັນຊ່ວຍ: ການຕັ້ງຄ່າ, ເງິນ, ບັນທຶກ
require __DIR__ . '/helpers/money_settings.php';
// ຟັງຊັນຊ່ວຍ: ກວດສິດ
require __DIR__ . '/helpers/permissions.php';
// ຟັງຊັນຊ່ວຍ: ທະນາຄານ
require __DIR__ . '/helpers/bank.php';
// ຟັງຊັນຊ່ວຍ: ສາຂາ ແລະ ລະຫັດສິນຄ້າ
require __DIR__ . '/helpers/branches.php';
// ຟັງຊັນຊ່ວຍ: ຂໍ້ມູນຮ້ານ ແລະ ແຈ້ງເຕືອນ
require __DIR__ . '/helpers/company_alerts.php';
// ສະຫຼັບສາຂາທີ່ກຳລັງເບິ່ງ
require __DIR__ . '/bootstrap/branch_switcher.php';
// ຟັງຊັນຊ່ວຍ: ຕົວກັ່ນຕອງກະ ຂອງໜ້າລາຍງານ
require __DIR__ . '/helpers/report_shift_filter.php';
// ຟັງຊັນຊ່ວຍ: ບັນທຶກສິນຄ້າ / ໝວດ → ສັ່ງເວັບໄຊແປຊື່ເປັນ th/en/zh/vi ອັດຕະໂນມັດ (w_translations)
require __DIR__ . '/helpers/web_translate.php';
// ເລກສະມາຊິກເວັບໄຊ 1 → WL-0001 (memberCode)
require __DIR__ . '/helpers/web_member.php';
// ອີເມວແຈ້ງເຕືອນສະມາຊິກ (ສະຖານະອໍເດີ, ລົດລາຄາ) → ແຖວ w_web_email_queue
require __DIR__ . '/helpers/web_mail.php';