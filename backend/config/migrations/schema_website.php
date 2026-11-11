<?php
/**
 * ອັບເດດໂຄງສ້າງ DB: ເວັບໄຊໜ້າບ້ານ (frontend/)
 * ຕາຕະລາງທີ່ frontend ຂຽນລົງ: ຄຳສັ່ງຊື້ອອນລາຍ ແລະ ຂໍ້ຄວາມຕິດຕໍ່. backend ອ່ານໄປສະແດງ/ຢືນຢັນ.
 *   - w_web_orders      : ຫົວຄຳສັ່ງຊື້ (ລູກຄ້າ, ຍອດລວມ, ສະຖານະ)
 *   - w_web_order_items : ລາຍການສິນຄ້າໃນຄຳສັ່ງຊື້ (ລາຄາ ແລະ ຊື່ ຖືກບັນທຶກໄວ້ ນັບແຕ່ຕອນສັ່ງ)
 *   - w_web_contacts    : ຂໍ້ຄວາມຈາກຟອມຕິດຕໍ່
 * ຄຳສັ່ງຊື້ບໍ່ຕັດສະຕັອກ — ພະນັກງານຢືນຢັນແລ້ວຈຶ່ງຂາຍຜ່ານ POS.
 * ຖືກ require ຈາກ config/db.php (ແລ່ນສະເພາະຕອນບໍ່ມີໄຟລ໌ .db_schema_vN).
 */
if (isset($pdo) && $pdo instanceof PDO) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_orders` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_no` VARCHAR(30) NULL,
            `store_id` INT NOT NULL DEFAULT 1,
            `customer_name` VARCHAR(150) NOT NULL,
            `customer_tel` VARCHAR(50) NOT NULL,
            `customer_email` VARCHAR(150) NULL,
            `customer_address` TEXT NULL,
            `note` TEXT NULL,
            `items_count` INT NOT NULL DEFAULT 0,
            `total_qty` INT NOT NULL DEFAULT 0,
            `total_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `status` ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_web_order_no` (`order_no`),
            INDEX `idx_web_orders_store_status` (`store_id`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_order_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `product_id` INT NOT NULL,
            `product_name` VARCHAR(255) NOT NULL,
            `price` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `qty` INT NOT NULL DEFAULT 1,
            `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0,
            INDEX `idx_web_order_items_order` (`order_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_contacts` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(150) NOT NULL,
            `tel` VARCHAR(50) NULL,
            `email` VARCHAR(150) NULL,
            `subject` VARCHAR(200) NULL,
            `message` TEXT NOT NULL,
            `is_read` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_web_contacts_read` (`is_read`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // v23: ລາຄາກ່ອນຫຼຸດ — ເວັບໄຊສະແດງຂີດຂ້າ ຖ້າ > ລາຄາຂາຍ (NULL/0 = ບໍ່ສະແດງ). ປ້ອນໃນຟອມເພີ່ມ/ແກ້ໄຂສິນຄ້າ
        $hasOldPrice = $pdo->query("SHOW COLUMNS FROM w_products LIKE 'old_price'")->fetch();
        if (!$hasOldPrice) {
            $pdo->exec("ALTER TABLE w_products ADD COLUMN `old_price` DECIMAL(10,2) NULL DEFAULT NULL AFTER `price`");
        }

        // v24: ນັບຜູ້ເຂົ້າຊົມເວັບໄຊ ລາຍວັນ (1 session = 1 ຄົນ/ມື້) — ສະແດງໃນແຖບທ້າຍ footer
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_visits` (
            `visit_date` DATE NOT NULL PRIMARY KEY,
            `visitors` INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // v25: ຫ້ອງແຊັດໜ້າຕິດຕໍ່ (frontend/pages/contact.php) — 1 session ຜູ້ຊົມ = 1 ຫ້ອງ. ບັອດ (AI/ຄຳສຳຄັນ) ຕອບອັດຕະໂນມັດ,
        // ພະນັກງານຕອບຈາກ backend/pages/web_chats/. ai_enabled = 0 → ບັອດຢຸດ (ພະນັກງານຮັບຕໍ່). unread_staff = ຂໍ້ຄວາມໃໝ່ທີ່ພະນັກງານຍັງບໍ່ເບິ່ງ
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_chats` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `token` CHAR(32) NOT NULL,
            `name` VARCHAR(150) NULL,
            `tel` VARCHAR(50) NULL,
            `status` ENUM('open','closed') NOT NULL DEFAULT 'open',
            `ai_enabled` TINYINT(1) NOT NULL DEFAULT 1,
            `unread_staff` INT NOT NULL DEFAULT 0,
            `last_message_at` DATETIME NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_web_chat_token` (`token`),
            INDEX `idx_web_chats_last` (`status`, `last_message_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // sender: visitor / bot / staff. audio_path = ໄຟລ໌ສຽງ (path ຈາກ root ຂອງໂປຣເຈັກ) ຖ້າເປັນຂໍ້ຄວາມສຽງ
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_chat_messages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `chat_id` INT NOT NULL,
            `sender` ENUM('visitor','bot','staff') NOT NULL,
            `body` TEXT NULL,
            `audio_path` VARCHAR(255) NULL,
            `audio_secs` SMALLINT NULL,
            `staff_name` VARCHAR(100) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_web_chat_msgs` (`chat_id`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // v30: ອ່ານແລ້ວ / ຍັງບໍ່ອ່ານ — id ຂໍ້ຄວາມສູງສຸດທີ່ພະນັກງານເປີດເບິ່ງແລ້ວ (staff_read_id) ແລະ ທີ່ລູກຄ້າເຫັນໃນແຊັດແລ້ວ (visitor_read_id)
        if ($pdo->query("SHOW TABLES LIKE 'w_web_chats'")->fetch() && !$pdo->query("SHOW COLUMNS FROM w_web_chats LIKE 'staff_read_id'")->fetch()) {
            $pdo->exec("ALTER TABLE w_web_chats ADD COLUMN `staff_read_id` INT NOT NULL DEFAULT 0, ADD COLUMN `visitor_read_id` INT NOT NULL DEFAULT 0");
        }

        // v29: w_web_chats.member_id — ແຊັດຂອງສະມາຊິກທີ່ login (ໜ້າ backend ສະແດງ "ສະມາຊິກ #N"; NULL = ລູກຄ້າທົ່ວໄປ)
        if ($pdo->query("SHOW TABLES LIKE 'w_web_chats'")->fetch() && !$pdo->query("SHOW COLUMNS FROM w_web_chats LIKE 'member_id'")->fetch()) {
            $pdo->exec("ALTER TABLE w_web_chats ADD COLUMN `member_id` INT NULL AFTER `token`, ADD INDEX `idx_web_chats_member` (`member_id`)");
        }

        // v28: ສະມາຊິກເວັບໄຊ (popup ລົງທະບຽນ frontend/components/member_modal.php, member_login.php, account.php) — ແຍກຈາກລູກຄ້າ POS (w_customers).
        // member_no = ເລກສະມາຊິກຕາມລຳດັບສະໝັກ 1, 2, 3… (frontend/models/members.php). ເຂົ້າລະບົບດ້ວຍເບີໂທ + ລະຫັດຜ່ານ (bcrypt)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_members` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `member_no` INT NOT NULL,
            `name` VARCHAR(150) NOT NULL,
            `tel` VARCHAR(30) NOT NULL,
            `email` VARCHAR(150) NULL,
            `password_hash` VARCHAR(255) NOT NULL,
            `status` ENUM('active','disabled') NOT NULL DEFAULT 'active',
            `last_login_at` DATETIME NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_web_member_no` (`member_no`),
            UNIQUE KEY `uniq_web_member_tel` (`tel`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // v27: ນັບຜູ້ເຂົ້າຊົມຕາມ IP — 1 IP ນັບ 1 ຄັ້ງ/ມື້ (ກັບມາມື້ໃໝ່ ນັບອີກ). ເກັບ hash ຂອງ IP ບໍ່ເກັບ IP ແທ້ (frontend/models/visitors.php)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_visit_ips` (
            `visit_date` DATE NOT NULL,
            `ip_hash` CHAR(64) NOT NULL,
            PRIMARY KEY (`visit_date`, `ip_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // v26: ແຊັດຫຼາຍພາສາ — w_web_chats.lang = ພາສາທີ່ລູກຄ້າພິມຫຼ້າສຸດ (lo/th/en/zh/vi, frontend/includes/translate.php),
        // w_web_chat_messages.body_tr = ຄຳແປ: ຂໍ້ຄວາມລູກຄ້າ → ລາວ (ໃຫ້ພະນັກງານອ່ານ), ຄຳຕອບພະນັກງານ → ຕົ້ນສະບັບພາສາລາວ
        if (!$pdo->query("SHOW COLUMNS FROM w_web_chats LIKE 'lang'")->fetch()) {
            $pdo->exec("ALTER TABLE w_web_chats ADD COLUMN `lang` VARCHAR(5) NULL AFTER `tel`");
        }
        if (!$pdo->query("SHOW COLUMNS FROM w_web_chat_messages LIKE 'body_tr'")->fetch()) {
            $pdo->exec("ALTER TABLE w_web_chat_messages ADD COLUMN `body_tr` TEXT NULL AFTER `body`");
        }

        // v31: ຂໍ້ມູນການຈັດສົ່ງ (ແຂວງ/ເມືອງ/ບ້ານ, ຂົນສົ່ງໃນລາວ + ສາຂາປາຍທາງ), ທະນາຄານທີ່ໂອນ, ຮູບສະລິບໂອນເງິນ ແລະ ເລກໃບຝາກເຄື່ອງ
        $orderCols = $pdo->query("SHOW COLUMNS FROM w_web_orders")->fetchAll(PDO::FETCH_COLUMN);
        $addOrderCols = [
            'province'        => "VARCHAR(100) NULL AFTER `customer_email`",
            'district'        => "VARCHAR(100) NULL AFTER `province`",
            'village'         => "VARCHAR(150) NULL AFTER `district`",
            'courier_code'    => "VARCHAR(50) NULL AFTER `customer_address`",
            'courier_name'    => "VARCHAR(120) NULL AFTER `courier_code`",
            'courier_branch'  => "VARCHAR(200) NULL AFTER `courier_name`",
            'bank_account_id' => "INT NULL AFTER `courier_branch`",
            'bank_name'       => "VARCHAR(120) NULL AFTER `bank_account_id`",
            'slip_img'        => "VARCHAR(255) NULL AFTER `bank_name`",
            'tracking_no'     => "VARCHAR(100) NULL AFTER `slip_img`",
        ];
        foreach ($addOrderCols as $col => $def) {
            if (!in_array($col, $orderCols, true)) {
                $pdo->exec("ALTER TABLE w_web_orders ADD COLUMN `$col` $def");
            }
        }
        // ສະຖານະ (v32 ເພີ່ມ packing / arrived): ລາຍການຄົບຢູ່ບ່ອນດຽວ — ຖ້າແລ່ນຊ້ຳ ບໍ່ຕັດຄ່າທີ່ໃຊ້ຢູ່ແລ້ວອອກ
        $pdo->exec("ALTER TABLE w_web_orders MODIFY COLUMN `status` ENUM('pending','confirmed','packing','shipped','arrived','completed','cancelled') NOT NULL DEFAULT 'pending'");

        if (!$pdo->query("SHOW COLUMNS FROM w_web_order_items LIKE 'item_slug'")->fetch()) {
            $pdo->exec("ALTER TABLE w_web_order_items ADD COLUMN `item_slug` VARCHAR(60) NULL AFTER `product_id`");
        }

        // v32: ຕິດຕາມອໍເດີ 6 ຂັ້ນ (pending → confirmed → packing → shipped → arrived → completed, + cancelled) ພ້ອມປະຫວັດ:
        // ທຸກຄັ້ງທີ່ພະນັກງານປ່ຽນສະຖານະ (backend/pages/web_orders/) ບັນທຶກ 1 ແຖວ → ລູກຄ້າເຫັນເວລາແຕ່ລະຂັ້ນ (frontend/pages/track_order.php)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_order_status_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `order_id` INT NOT NULL,
            `status` VARCHAR(20) NOT NULL,
            `note` VARCHAR(255) NULL,
            `staff_name` VARCHAR(100) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_web_order_log` (`order_id`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // ອໍເດີເກົ່າທີ່ບໍ່ແມ່ນ pending ແລະ ຍັງບໍ່ມີປະຫວັດ → ບັນທຶກສະຖານະປັດຈຸບັນ ດ້ວຍເວລາອັບເດດລ່າສຸດ
        $pdo->exec("INSERT INTO w_web_order_status_log (order_id, status, created_at)
                    SELECT o.id, o.status, COALESCE(o.updated_at, o.created_at) FROM w_web_orders o
                    WHERE o.status <> 'pending' AND NOT EXISTS (SELECT 1 FROM w_web_order_status_log l WHERE l.order_id = o.id)");

        // v33: ນັບການກົດປຸ່ມຕິດຕໍ່ໃນເວັບໄຊ (ໂທ / WhatsApp / Facebook / ແຊັດ / ແຜນທີ່) ແລະ ກົດຈາກໜ້າໃດ —
        // frontend/api/track_click.php ບັນທຶກ (assets/js/main.js ສົ່ງ beacon), ເບິ່ງສະຫຼຸບທີ່ backend/pages/web_chats/ (30 ມື້)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_contact_clicks` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `channel` VARCHAR(20) NOT NULL,
            `page` VARCHAR(190) NOT NULL DEFAULT '',
            `lang` CHAR(2) NOT NULL DEFAULT 'lo',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_web_click_date` (`created_at`),
            INDEX `idx_web_click_channel` (`channel`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // v34: ຜົນງານການຕິດຕັ້ງລະບົບ & ໂລໂກ້ລູກຄ້າ — ອັບໂຫຼດຈາກຫຼັງບ້ານ (backend/pages/web_works/index.php):
        // ຊື່ຮ້ານລູກຄ້າ (shop_name), ໂລໂກ້ລູກຄ້າ (logo_img → backend/assets/img/clients/), ຮູບຜົນງານຫຼາຍຮູບພ້ອມກັນ (photos_json → backend/assets/img/works/<slug>/)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_works` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `slug` VARCHAR(120) NOT NULL,
            `shop_name` VARCHAR(200) NOT NULL,
            `title` VARCHAR(255) NOT NULL DEFAULT '',
            `category` VARCHAR(100) NOT NULL DEFAULT 'POS',
            `work_date` DATE NULL,
            `description` TEXT NULL,
            `logo_img` VARCHAR(255) NULL,
            `photos_json` TEXT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_web_work_slug` (`slug`),
            INDEX `idx_web_work_date` (`work_date`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // v35: ຄຳແປຂໍ້ມູນຈາກຖານຂໍ້ມູນ (ຊື່ສິນຄ້າ, ຊື່ໝວດ…) ເປັນພາສາຂອງເວັບໄຊ (th / en / zh / vi) — ແປອັດຕະໂນມັດ:
        // ເພີ່ມ/ແກ້ສິນຄ້າ ຫຼື ໝວດໃນຫຼັງບ້ານ → backend/config/helpers/web_translate.php ສັ່ງ frontend/api/translate_missing.php ແປໃນເບື້ອງຫຼັງ.
        // entity = products / categories, field = ຖັນ (product_name / category_name), source_hash = md5 ຂອງຂໍ້ຄວາມລາວຕອນແປ
        // (ແກ້ຊື່ລາວ → hash ບໍ່ກົງ → ແປໃໝ່ເອງ). frontend/models/translations.php ອ່ານມາສະແດງຕາມພາສາທີ່ເລືອກ.
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_translations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `entity` VARCHAR(30) NOT NULL,
            `entity_id` INT NOT NULL,
            `field` VARCHAR(40) NOT NULL,
            `lang` CHAR(2) NOT NULL,
            `source_hash` CHAR(32) NOT NULL,
            `text` TEXT NOT NULL,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_web_tr` (`entity`, `entity_id`, `field`, `lang`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // v38: ລົງທະບຽນສະມາຊິກແບບລະອຽດ + ເຂົ້າສູ່ລະບົບດ້ວຍ Google (frontend/models/members.php, member_google.php):
        //   - tel / password_hash ເປັນ NULL ໄດ້ (ສະມາຊິກ Google ຍັງບໍ່ມີເບີ/ລະຫັດ — ຕ້ອງເພີ່ມເບີໂທຈິງກ່ອນສັ່ງຊື້)
        //   - ຂໍ້ມູນຮ້ານ + ທີ່ຢູ່ຈັດສົ່ງເລີ່ມຕົ້ນ (ຕື່ມໃຫ້ອັດຕະໂນມັດຕອນສັ່ງຊື້), google_id, ອີເມວຫ້າມຊ້ຳ
        //   - w_web_orders.member_id = ອໍເດີຂອງສະມາຊິກ (ປະຫວັດການສັ່ງຊື້ frontend/pages/my_orders.php), slip_uploaded_at
        $pdo->exec("ALTER TABLE w_web_members MODIFY COLUMN `tel` VARCHAR(30) NULL, MODIFY COLUMN `password_hash` VARCHAR(255) NULL");
        $memberCols = $pdo->query("SHOW COLUMNS FROM w_web_members")->fetchAll(PDO::FETCH_COLUMN);
        $addMemberCols = [
            'whatsapp'       => "VARCHAR(30) NULL AFTER `tel`",
            'shop_name'      => "VARCHAR(150) NULL AFTER `email`",
            'business_type'  => "VARCHAR(60) NULL AFTER `shop_name`",
            'province'       => "VARCHAR(100) NULL AFTER `business_type`",
            'district'       => "VARCHAR(100) NULL AFTER `province`",
            'village'        => "VARCHAR(150) NULL AFTER `district`",
            'address'        => "VARCHAR(255) NULL AFTER `village`",
            'courier_code'   => "VARCHAR(50) NULL AFTER `address`",
            'courier_branch' => "VARCHAR(200) NULL AFTER `courier_code`",
            'google_id'      => "VARCHAR(64) NULL AFTER `password_hash`",
            'avatar_url'     => "VARCHAR(500) NULL AFTER `google_id`",
            'updated_at'     => "DATETIME NULL ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`",
        ];
        foreach ($addMemberCols as $col => $def) {
            if (!in_array($col, $memberCols, true)) {
                $pdo->exec("ALTER TABLE w_web_members ADD COLUMN `$col` $def");
            }
        }
        $memberIdx = $pdo->query("SHOW INDEX FROM w_web_members")->fetchAll(PDO::FETCH_COLUMN, 2);
        if (!in_array('uniq_web_member_google', $memberIdx, true)) {
            $pdo->exec("ALTER TABLE w_web_members ADD UNIQUE KEY `uniq_web_member_google` (`google_id`)");
        }
        $pdo->exec("UPDATE w_web_members SET email = NULL WHERE email = ''");
        $dupEmail = $pdo->query("SELECT 1 FROM w_web_members WHERE email IS NOT NULL GROUP BY email HAVING COUNT(*) > 1 LIMIT 1")->fetch();
        if (!$dupEmail && !in_array('uniq_web_member_email', $memberIdx, true)) {
            $pdo->exec("ALTER TABLE w_web_members ADD UNIQUE KEY `uniq_web_member_email` (`email`)");
        }
        $orderCols = $pdo->query("SHOW COLUMNS FROM w_web_orders")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('member_id', $orderCols, true)) {
            $pdo->exec("ALTER TABLE w_web_orders ADD COLUMN `member_id` INT NULL AFTER `store_id`, ADD INDEX `idx_web_orders_member` (`member_id`, `id`)");
        }
        if (!in_array('slip_uploaded_at', $orderCols, true)) {
            $pdo->exec("ALTER TABLE w_web_orders ADD COLUMN `slip_uploaded_at` DATETIME NULL AFTER `slip_img`");
        }
        // v39: ຮູບສິນຄ້າຫຼາຍມຸມ — ຮູບຫຼັກຍັງຢູ່ w_products.img_url (POS, ໃບບິນ ໃຊ້); ຮູບເພີ່ມເຕີມຢູ່ນີ້ (ໄຟລ໌ໃນ assets/product_img/).
        // ອັບໂຫຼດຫຼາຍຮູບພ້ອມກັນໃນຟອມເພີ່ມ/ແກ້ໄຂສິນຄ້າ (pages/products/), ເວັບໄຊສະແດງເປັນແກເລີຣີ (frontend/pages/product.php)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_product_images` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `img_file` VARCHAR(255) NOT NULL,
            `sort_order` INT NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_product_images` (`product_id`, `sort_order`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // v40: ສະມາຊິກ "ຈື່ການເຂົ້າສູ່ລະບົບ" — ລົງທະບຽນ/login ແລ້ວ ບໍ່ຕ້ອງ login ອີກ 180 ມື້ (ຈົນກວ່າຈະກົດອອກຈາກລະບົບ).
        // 1 ແຖວ = 1 ເຄື່ອງ/browser; cookie WLAOWEBREM ເກັບ token ແທ້, DB ເກັບແຕ່ sha256 (frontend/models/members.php memberRemember)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_member_tokens` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `member_id` INT NOT NULL,
            `token_hash` CHAR(64) NOT NULL,
            `expires_at` DATETIME NOT NULL,
            `last_used_at` DATETIME NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uniq_web_member_token` (`token_hash`),
            INDEX `idx_web_member_tokens_member` (`member_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // v41: ແຈ້ງເຕືອນທາງອີເມວ — ແຖວລໍຖ້າສົ່ງ (ສົ່ງໃນເບື້ອງຫຼັງໂດຍ frontend/api/mail_queue.php ຜ່ານ SMTP frontend/config/mail.php)
        // kind: welcome / order_new / order_status / price_drop. w_web_members.email_notify = ສະມາຊິກຮັບອີເມວແຈ້ງເຕືອນ (ປິດໄດ້ໃນໜ້າບັນຊີ)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_email_queue` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `member_id` INT NULL,
            `to_email` VARCHAR(150) NOT NULL,
            `kind` VARCHAR(30) NOT NULL DEFAULT '',
            `subject` VARCHAR(255) NOT NULL,
            `body_html` MEDIUMTEXT NOT NULL,
            `status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
            `attempts` TINYINT NOT NULL DEFAULT 0,
            `error` VARCHAR(255) NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `sent_at` DATETIME NULL,
            INDEX `idx_web_email_queue` (`status`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        if ($pdo->query("SHOW TABLES LIKE 'w_web_members'")->fetch() && !$pdo->query("SHOW COLUMNS FROM w_web_members LIKE 'email_notify'")->fetch()) {
            $pdo->exec("ALTER TABLE w_web_members ADD COLUMN `email_notify` TINYINT(1) NOT NULL DEFAULT 1 AFTER `email`");
        }
        // v42: ຈຳກັດຄວາມຖີ່ຕາມ IP (ກັນ bot ລົງທະບຽນ spam / ເດົາລະຫັດຜ່ານ) — rkey = 'reg:<sha256 IP>' / 'login:<…>',
        // hits ນັບໃນຊ່ວງເວລາ ຈົນຮອດ reset_at (frontend/models/members.php rateLimitHit). ເກັບ hash ບໍ່ເກັບ IP ແທ້
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_rate_limits` (
            `rkey` VARCHAR(90) NOT NULL PRIMARY KEY,
            `hits` INT NOT NULL DEFAULT 0,
            `reset_at` DATETIME NOT NULL,
            INDEX `idx_web_rate_reset` (`reset_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // v43: ໄຟລ໌ແນບໃນແຊັດ (ຮູບ / ວິດີໂອ / ເອກະສານ) — frontend/includes/chat_file.php.
        // file_path = path ຈາກ root ຂອງໂປຣເຈັກ (backend/assets/web_chat_files/...), file_kind = image|video|file
        if ($pdo->query("SHOW TABLES LIKE 'w_web_chat_messages'")->fetch() && !$pdo->query("SHOW COLUMNS FROM w_web_chat_messages LIKE 'file_path'")->fetch()) {
            $pdo->exec("ALTER TABLE w_web_chat_messages ADD COLUMN `file_path` VARCHAR(255) NULL AFTER `audio_secs`,
                        ADD COLUMN `file_name` VARCHAR(200) NULL AFTER `file_path`,
                        ADD COLUMN `file_kind` VARCHAR(10) NULL AFTER `file_name`,
                        ADD COLUMN `file_size` INT NULL AFTER `file_kind`");
        }
        // v44: ແຈ້ງເຕືອນສະມາຊິກ (ກະດິ່ງໃນເມນູເວັບໄຊ) — kind: welcome / order_new / order_status / tracking / price_drop / chat,
        // data = JSON (ເລກອໍເດີ, ສະຖານະ, ຊື່ສິນຄ້າ…) ແປເປັນຂໍ້ຄວາມຕອນສະແດງ (frontend/models/member_notifications.php),
        // link = path ໃນເວັບໄຊ (pages/my_order.php?no=…). ຂຽນຜ່ານ frontend/includes/member_notify.php (ທັງ 2 ຝັ່ງ)
        $pdo->exec("CREATE TABLE IF NOT EXISTS `w_web_member_notifications` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `member_id` INT NOT NULL,
            `kind` VARCHAR(20) NOT NULL,
            `data` TEXT NULL,
            `link` VARCHAR(255) NULL,
            `is_read` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_web_member_notif` (`member_id`, `is_read`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        // v45: ຮູບບິນຮັບເຄື່ອງຂອງບໍລິສັດຂົນສົ່ງ (ແທນການພິມເລກໃບຝາກເຄື່ອງ) — ພະນັກງານອັບໂຫຼດໃນ backend/pages/web_orders/,
        // ເກັບໃນ backend/assets/web_receipts/ (ຫ້າມເປີດໂດຍກົງ — ແຍກຈາກສະລິບ web_slips/) ເບິ່ງຜ່ານ slip.php?type=receipt (ພະນັກງານ) / frontend/api/slip.php?type=receipt (ເຈົ້າຂອງອໍເດີ)
        if ($pdo->query("SHOW TABLES LIKE 'w_web_orders'")->fetch() && !$pdo->query("SHOW COLUMNS FROM w_web_orders LIKE 'receipt_img'")->fetch()) {
            $pdo->exec("ALTER TABLE w_web_orders ADD COLUMN `receipt_img` VARCHAR(255) NULL AFTER `tracking_no`, ADD COLUMN `receipt_at` DATETIME NULL AFTER `receipt_img`");
        }
        // v46: ວັນເດືອນປີເກີດຂອງສະມາຊິກ — ປ້ອນຕອນລົງທະບຽນ (ບັງຄັບ) ແລະ ແກ້ໄດ້ໃນໜ້າບັນຊີ (frontend/models/members.php MEMBER_PROFILE_FIELDS)
        if ($pdo->query("SHOW TABLES LIKE 'w_web_members'")->fetch() && !$pdo->query("SHOW COLUMNS FROM w_web_members LIKE 'birth_date'")->fetch()) {
            $pdo->exec("ALTER TABLE w_web_members ADD COLUMN `birth_date` DATE NULL AFTER `email`");
        }
    } catch (Throwable $e) {
        error_log("Website schema migration error: " . $e->getMessage());
    }
}
