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
 *   - migrations/schema_stock_check.php : ອັບເດດໂຄງສ້າງ DB: ເຊັກສະຕັອກ
 *   - helpers/theme_css.php : ຟັງຊັນຊ່ວຍ: ລິ້ງໄຟລ໌ CSS ໃນ themes/ (themeCssLink)
 *   - helpers/money_settings.php : ຟັງຊັນຊ່ວຍ: ການຕັ້ງຄ່າ, ເງິນ, ບັນທຶກ
 *   - helpers/permissions.php : ຟັງຊັນຊ່ວຍ: ກວດສິດ
 *   - helpers/bank.php : ຟັງຊັນຊ່ວຍ: ທະນາຄານ
 *   - helpers/branches.php : ຟັງຊັນຊ່ວຍ: ສາຂາ ແລະ ລະຫັດສິນຄ້າ
 *   - helpers/company_alerts.php : ຟັງຊັນຊ່ວຍ: ຂໍ້ມູນຮ້ານ ແລະ ແຈ້ງເຕືອນ
 *   - bootstrap/branch_switcher.php : ສະຫຼັບສາຂາທີ່ກຳລັງເບິ່ງ
 *   - helpers/expiry.php : ຟັງຊັນຊ່ວຍ: ສິນຄ້າໝົດອາຍຸ
 *   - helpers/stock_check_alerts.php : ຟັງຊັນຊ່ວຍ: ແຈ້ງເຕືອນເຊັກສະຕັອກ
 *   - helpers/shift_auto.php : ຟັງຊັນຊ່ວຍ: ກະເຮັດວຽກ (ໂມງປິດກະ, ການໃຊ້ງານ, ປິດກະອໍໂຕ້, ປິດກະເມື່ອອອກຈາກລະບົບ)
 *   - helpers/shift_alerts.php : ຟັງຊັນຊ່ວຍ: ແຈ້ງເຕືອນປິດກະ (ຜູ້ບໍລິຫານ/ຜູ້ຈັດການ)
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
    $schemaFlagFile = __DIR__ . '/.db_schema_v20';
    if (!file_exists($schemaFlagFile)) {
        // ແລ່ນຄັ້ງດຽວຕໍ່ເວີຊັນ: ອັບເດດໂຄງສ້າງ DB ທັງ 4 ສ່ວນ ຕາມລຳດັບ ແລ້ວຂຽນໄຟລ໌ flag ໄວ້
        require __DIR__ . '/migrations/schema_core.php';             // (1) ພື້ນຖານ
        require __DIR__ . '/migrations/schema_users_customers.php';   // (2) ຜູ້ໃຊ້ ແລະ ລູກຄ້າ
        require __DIR__ . '/migrations/schema_company_banks.php';     // (3) ຮ້ານ, ທະນາຄານ, ໂປຣໂມຊັ່ນ
        require __DIR__ . '/migrations/schema_settings_sales.php';    // (4) ການຕັ້ງຄ່າ, ການຂາຍ, ໜີ້

        @file_put_contents($schemaFlagFile, date('Y-m-d H:i:s'));
    }
}

// ອັບເດດໂຄງສ້າງ DB: ຄະແນນສະສົມ
require __DIR__ . '/migrations/schema_loyalty.php';
// ອັບເດດໂຄງສ້າງ DB: ເຊັກສະຕັອກ
require __DIR__ . '/migrations/schema_stock_check.php';
// ອັບເດດໂຄງສ້າງ DB: ຈັດການກະເຮັດວຽກ
require __DIR__ . '/migrations/schema_shifts.php';
// ອັບເດດໂຄງສ້າງ DB: ບິນທີ່ພັກໄວ້ (ຄ້າງໄວ້ບໍ່ໃຫ້ຫາຍ)
require __DIR__ . '/migrations/schema_held_orders.php';
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
// ຟັງຊັນຊ່ວຍ: ສິນຄ້າໝົດອາຍຸ
require __DIR__ . '/helpers/expiry.php';
// ຟັງຊັນຊ່ວຍ: ແຈ້ງເຕືອນເຊັກສະຕັອກ (ສົ່ງກວດ / ອະນຸມັດແລ້ວ)
require __DIR__ . '/helpers/stock_check_alerts.php';
// ຟັງຊັນຊ່ວຍ: ກະເຮັດວຽກ (ໂມງປິດກະ, ການໃຊ້ງານ, ປິດກະອໍໂຕ້)
require __DIR__ . '/helpers/shift_auto.php';
// ຟັງຊັນຊ່ວຍ: ແຈ້ງເຕືອນປິດກະ (ຕ້ອງຢູ່ຫຼັງ shift_auto.php — ໃຊ້ SHIFT_LOGOUT_NOTE)
require __DIR__ . '/helpers/shift_alerts.php';
// ຟັງຊັນຊ່ວຍ: ຕົວກັ່ນຕອງກະ ຂອງໜ້າລາຍງານ
require __DIR__ . '/helpers/report_shift_filter.php';