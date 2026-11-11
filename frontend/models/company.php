<?php
/**
 * ຂໍ້ມູນຮ້ານ/ບໍລິສັດ — ອ່ານຈາກ w_tbcompanyinfo ຂອງສາຂາໃຫຍ່ (ແກ້ໄດ້ໃນ backend: ຕັ້ງຄ່າ → ຂໍ້ມູນຮ້ານ).
 * ຖ້າຍັງບໍ່ມີຂໍ້ມູນ ໃຊ້ຊື່ຈາກ config/app.php ແທນ.
 */

// store_id ຂອງສາຂາໃຫຍ່ — ເວັບໄຊສະແດງສິນຄ້າ ແລະ ຮັບຄຳສັ່ງຊື້ໃຫ້ສາຂານີ້
function shopStoreId(?PDO $pdo): int
{
    if (!$pdo) {
        return 1;
    }
    try {
        $id = $pdo->query("SELECT store_id FROM w_tbstore WHERE is_main = 1 ORDER BY store_id ASC LIMIT 1")->fetchColumn();
        return $id ? (int)$id : 1;
    } catch (Throwable $e) {
        return 1;
    }
}

function getCompany(?PDO $pdo, array $config): array
{
    $company = [
        'name'          => 'Wlaodev',
        'store_name'    => 'WlaoDev POS',
        'tagline'       => t($config['tagline'] ?? 'ພັດທະນາລະບົບ POS ແລະ ເວັບໄຊ ສຳລັບທຸລະກິດລາວ'),
        'address'       => 'ບ້ານ ໂນນຄໍ້ເໜືອ ເມືອງ ໄຊເສດຖາ ນະຄອນຫຼວງວຽງຈັນ',
        'tel'           => '020 29 777 302',
        'tel_href'      => 'tel:+8562029777302',
        'whatsapp'      => '+856 20 55 949 007',
        'whatsapp_href' => 'https://wa.me/8562055949007',
        'facebook'      => 'https://www.facebook.com/profile.php?id=100067585680024&locale=th_TH',
        'email'         => '',
        'lat'           => '17.9693056',
        'lng'           => '102.6574093',
        'maps_url'      => 'https://www.google.com/maps/place/WlaoDev+POS/@17.9693056,102.6574093,17z/data=!3m1!4b1!4m6!3m5!1s0x3124673533f68bdf:0xdc72b4c7beda28ad!8m2!3d17.9693056!4d102.6574093!16s%2Fg%2F11p1gk_4jk',
    ];
    if (!$pdo) {
        return $company;
    }
    try {
        $row = $pdo->prepare("SELECT * FROM w_tbcompanyinfo WHERE branch_id = ? OR branch_id IS NULL ORDER BY (branch_id = ?) DESC, Id ASC LIMIT 1");
        $mainId = shopStoreId($pdo);
        $row->execute([$mainId, $mainId]);
        $r = $row->fetch();
    } catch (Throwable $e) {
        $r = null;
    }
    if ($r) {
        $company['name']    = trim($r['com_name_la'] ?? '') ?: $company['name'];
        $company['address'] = trim($r['com_address'] ?? '') ?: $company['address'];
        $company['email']   = trim($r['com_email'] ?? '');
        $company['lat']     = trim($r['latitude'] ?? '') ?: $company['lat'];
        $company['lng']     = trim($r['longitude'] ?? '') ?: $company['lng'];
    }
    // ຕຳແໜ່ງຮ້ານທີ່ປັກໝຸດໃນ backend (pages/web_location/) — ສຳຄັນກວ່າຄ່າອື່ນ: ແຜນທີ່ footer, ໜ້າຕິດຕໍ່, ນຳທາງໃນແຊັດ
    try {
        $st = $pdo->query("SELECT setting_key, setting_value FROM w_system_settings WHERE setting_key IN ('web_shop_lat', 'web_shop_lng', 'web_shop_address', 'web_shop_maps_url', 'web_shop_name')");
        $set = $st ? $st->fetchAll(PDO::FETCH_KEY_PAIR) : [];
        if (is_numeric($set['web_shop_lat'] ?? null) && is_numeric($set['web_shop_lng'] ?? null)) {
            $company['lat'] = $set['web_shop_lat'];
            $company['lng'] = $set['web_shop_lng'];
        }
        if (trim((string)($set['web_shop_address'] ?? '')) !== '') {
            $company['address'] = trim($set['web_shop_address']);
        }
        if (!empty($set['web_shop_maps_url'])) {
            $company['maps_url'] = trim($set['web_shop_maps_url']);
        }
        if (!empty($set['web_shop_name'])) {
            $company['name'] = trim($set['web_shop_name']);
        }
    } catch (Throwable $e) {
    }
    return $company;
}
