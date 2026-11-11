<?php
/**
 * ຈຸດເລີ່ມຕົ້ນຂອງທຸກໜ້າ ແລະ ທຸກ API ໃນ frontend/ — require ໄຟລ໌ນີ້ໄຟລ໌ດຽວ.
 * ລຳດັບ require ຂ້າງລຸ່ມສຳຄັນ (ໄຟລ໌ຫຼັງໃຊ້ຕົວແປ/ຟັງຊັນຂອງໄຟລ໌ກ່ອນ):
 *   - config/app.php        : ການຕັ້ງຄ່າ ($config)
 *   - includes/helpers.php  : ຟັງຊັນທົ່ວໄປ (url, asset, e, money, csrf, jsonOut)
 *   - config/database.php   : ເຊື່ອມຖານຂໍ້ມູນ ($pdo)
 *   - config/session.php    : ເລີ່ມ session ຂອງເວັບໄຊ
 *   - includes/i18n.php     : ແປພາສາ t()/te() + ຮັບ ?lang=xx (ຕ້ອງຢູ່ກ່ອນທຸກໄຟລ໌ທີ່ມີຂໍ້ຄວາມ)
 *   - includes/content.php  : ເນື້ອຫາຄົງທີ່ (ບໍລິການ, ຜົນງານ) — ແກ້ຂໍ້ຄວາມໜ້າເວັບທີ່ນີ້
 *   - includes/cart.php     : ກະຕ່າສິນຄ້າ (ເກັບໃນ session)
 *   - models/*.php          : ອ່ານ/ຂຽນ ຖານຂໍ້ມູນ (ຮ້ານ, ສິນຄ້າ, ຄຳສັ່ງຊື້, ຂໍ້ຄວາມ) ແລະ ແບນເນີ
 * ສ້າງ $config, $pdo, $company, $shopStoreId ໃຫ້ທຸກໜ້າໃຊ້.
 */
$config = require dirname(__DIR__) . '/config/app.php';
date_default_timezone_set($config['timezone']);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/helpers.php';
require __DIR__ . '/icons.php';      // icon('receipt') — ໄອຄອນເສັ້ນ SVG ແທນ emoji
require __DIR__ . '/seo.php';
require dirname(__DIR__) . '/config/database.php';
require dirname(__DIR__) . '/config/session.php';
require __DIR__ . '/i18n.php';
handleLangSwitch();
require __DIR__ . '/content.php';
require __DIR__ . '/cart.php';
require dirname(__DIR__) . '/models/company.php';
require dirname(__DIR__) . '/models/products.php';
require dirname(__DIR__) . '/models/orders.php';
require dirname(__DIR__) . '/models/contacts.php';
require dirname(__DIR__) . '/models/banners.php';
require dirname(__DIR__) . '/models/solutions.php';
require dirname(__DIR__) . '/models/equipment.php';
require dirname(__DIR__) . '/models/search.php';
require dirname(__DIR__) . '/models/works.php';
require dirname(__DIR__) . '/models/clients.php';
require dirname(__DIR__) . '/models/visitors.php';
require dirname(__DIR__) . '/models/email_check.php';   // ກວດອີເມວແທ້ (MX, ອີເມວຊົ່ວຄາວ, ພິມຜິດ)
require dirname(__DIR__) . '/models/members.php';
require __DIR__ . '/member_notify.php';                    // ຂຽນແຈ້ງເຕືອນສະມາຊິກ (ກະດິ່ງ) — ໃຊ້ຮ່ວມກັບ backend
require dirname(__DIR__) . '/models/member_notifications.php';   // ອ່ານ / ແປ ແຈ້ງເຕືອນ
require dirname(__DIR__) . '/models/member_otp.php';    // ລະຫັດຢືນຢັນ 6 ຕົວທາງອີເມວ (ລົງທະບຽນ / ລືມລະຫັດຜ່ານ)
require dirname(__DIR__) . '/models/member_google.php';   // ລົງທະບຽນ / login ດ້ວຍ Google
require dirname(__DIR__) . '/models/member_orders.php';
require_once __DIR__ . '/staff_notify.php';               // ແຈ້ງພະນັກງານ: ອໍເດີໃໝ່ / ສະລິບ (Telegram, ອີເມວ — config/notify.php)
require_once __DIR__ . '/mailer.php';                    // ສົ່ງອີເມວ (SMTP + ແຖວລໍຖ້າ w_web_email_queue)
require dirname(__DIR__) . '/models/member_mail.php';     // ອີເມວແຈ້ງເຕືອນສະມາຊິກ: ຍິນດີຕ້ອນຮັບ, ໄດ້ຮັບອໍເດີ   // ລາຄາ, ສັ່ງຊື້, ປະຫວັດ ແລະ ສະລິບ ຂອງສະມາຊິກ
require __DIR__ . '/translate.php';
require dirname(__DIR__) . '/models/translations.php';   // ຄຳແປຊື່ສິນຄ້າ/ໝວດ (w_translations) — ໃຊ້ chatTranslate() ຂ້າງເທິງ
require __DIR__ . '/chat_audio.php';
require __DIR__ . '/chat_file.php';   // ໄຟລ໌ແນບໃນແຊັດ (ຮູບ / ວິດີໂອ / ເອກະສານ)
require dirname(__DIR__) . '/models/chat.php';
require dirname(__DIR__) . '/models/chat_stickers.php';   // ສະຕິກເກີແຊັດ (emoji ໃຫຍ່)

$company     = getCompany($pdo, $config);
$shopStoreId = shopStoreId($pdo);
if ($pdo) {
    trackVisit($pdo);
    currentMember($pdo);   // ສະມາຊິກທີ່ຈື່ການເຂົ້າສູ່ລະບົບໄວ້ (cookie WLAOWEBREM) → login ໃຫ້ເອງ ກ່ອນສົ່ງ HTML (models/members.php)
}
