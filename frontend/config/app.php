<?php
/**
 * ການຕັ້ງຄ່າຂອງເວັບໄຊໜ້າບ້ານ (frontend/)
 * ແຍກຈາກ backend/ ທັງໝົດ: session ຂອງຕົນເອງ (WLAOWEBSSID) ເພື່ອບໍ່ໃຫ້ການເຂົ້າເວັບຂອງລູກຄ້າ
 * ປົນກັບການເຂົ້າລະບົບຂອງພະນັກງານ (WLAODEVSSID). ໃຊ້ຖານຂໍ້ມູນ webwlaodev ດຽວກັບ backend.
 * ຖືກອ່ານໂດຍ includes/bootstrap.php.
 */
return [
    'site_name'    => 'Wlaodev',
    'tagline'      => 'ພັດທະນາລະບົບ POS ແລະ ເວັບໄຊ ສຳລັບທຸລະກິດລາວ',
    'timezone'     => 'Asia/Vientiane',
    'session_name' => 'WLAOWEBSSID',
    'currency'     => 'Kip',
    'per_page'     => 12,
    // IP ທີ່ບໍ່ນັບເປັນຜູ້ເຂົ້າຊົມ (ເຄື່ອງຂອງຜູ້ສ້າງ/ພະນັກງານ). 127.0.0.1, ::1 ແລະ IP ຂອງ server ບໍ່ນັບຢູ່ແລ້ວ (models/visitors.php)
    'visit_exclude_ips' => ['192.168.1.9'],

    // SEO (includes/seo.php, layouts/seo_head.php, sitemap.php, robots.php)
    // site_url: domain ແທ້ເມື່ອຂຶ້ນ server (ເຊັ່ນ 'https://wlaodev.com') — ວ່າງ = ຄິດຈາກ request (ໃຊ້ໄດ້ຕອນທົດລອງ)
    'site_url'        => '',
    // ຄຳອະທິບາຍທີ່ Google ສະແດງໃຕ້ຊື່ເວັບ (ໜ້າທີ່ບໍ່ໄດ້ຕັ້ງ $pageDescription ເອງ)
    'seo_description' => 'ລະບົບ POS ແລະ ໂປຣແກຣມຂາຍໜ້າຮ້ານ ສຳລັບຮ້ານອາຫານ, ຮ້ານກາເຟ, ມິນິມາດ ແລະ ຮ້ານຄ້າໃນລາວ — ພ້ອມອຸປະກອນ POS ຄົບຊຸດ: ເຄື່ອງຄິດເງິນຈໍສຳຜັດ, ປິ້ນເຕີໃບບິນ, ເຄື່ອງສະແກນບາໂຄດ, ລິ້ນຊັກເກັບເງິນ. ຕິດຕັ້ງ ແລະ ສອນໃຊ້ຟຣີ ທີ່ວຽງຈັນ.',
    'seo_keywords'    => 'ລະບົບ POS, ໂປຣແກຣມຂາຍໜ້າຮ້ານ, ເຄື່ອງຄິດເງິນ, ອຸປະກອນ POS, ປິ້ນເຕີໃບບິນ, ເຄື່ອງສະແກນບາໂຄດ, POS ລາວ, POS ວຽງຈັນ, ໂປຣແກຣມຮ້ານອາຫານ, ໂປຣແກຣມມິນິມາດ, Wlaodev',
    // ລະຫັດຢືນຢັນຈາກ Google Search Console (ແບບ HTML tag: ຄ່າໃນ content="…")
    'google_site_verification' => '',

    // TODO ກ່ອນຂຶ້ນ server ແທ້: ປ່ຽນເປັນ host/user/password ຂອງ production
    'db' => [
        'host'  => '127.0.0.1',
        'ports' => [3306, 3336],
        'name'  => 'webwlaodev',
        'user'  => 'root',
        'pass'  => '',
    ],
];
