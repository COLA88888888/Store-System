<?php
/**
 * ການຕັ້ງຄ່າສົ່ງອີເມວແຈ້ງເຕືອນສະມາຊິກ (includes/mailer.php) — ສຳເນົາເປັນ config/mail.php ແລ້ວແກ້.
 * ບໍ່ມີໄຟລ໌ config/mail.php = ສົ່ງເຂົ້າ Mailpit ຂອງ Laragon (ທົດລອງ — ເບິ່ງອີເມວທີ່ http://localhost:8025, ບໍ່ອອກໄປຫາລູກຄ້າແທ້).
 *
 * ໃຊ້ Gmail ສົ່ງແທ້:
 *   1. ເປີດ 2-Step Verification ຂອງບັນຊີ Gmail ຂອງຮ້ານ
 *   2. https://myaccount.google.com/apppasswords → ສ້າງ App password (16 ຕົວ)
 *   3. ໃສ່ລຸ່ມນີ້: host smtp.gmail.com, port 587, secure tls, user = Gmail, pass = App password, from = Gmail ດຽວກັນ
 */
return [
    'enabled'   => true,
    'host'      => 'smtp.gmail.com',
    'port'      => 587,
    'secure'    => 'tls',              // 'tls' (587) | 'ssl' (465) | '' (ບໍ່ເຂົ້າລະຫັດ)
    'user'      => 'yourshop@gmail.com',
    'pass'      => 'xxxx xxxx xxxx xxxx',
    'from'      => 'yourshop@gmail.com',
    'from_name' => 'WlaoDev',
    'site_url'  => '',                 // ເຊັ່ນ 'https://wlaodev.com' (ລິ້ງໃນອີເມວ) — ວ່າງ = ຄິດຈາກ request
];
