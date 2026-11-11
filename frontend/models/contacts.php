<?php
/**
 * ບັນທຶກຂໍ້ຄວາມຈາກຟອມຕິດຕໍ່ ລົງ w_web_contacts (ສ້າງໂດຍ backend/config/migrations/schema_website.php).
 */

function validateContact(array $in): array
{
    $c = [
        'name'    => trim($in['name'] ?? ''),
        'tel'     => trim($in['tel'] ?? ''),
        'email'   => trim($in['email'] ?? ''),
        'subject' => trim($in['subject'] ?? ''),
        'message' => trim($in['message'] ?? ''),
    ];
    $errors = [];
    if ($c['name'] === '' || mb_strlen($c['name']) > 150) {
        $errors['name'] = t('ກະລຸນາປ້ອນຊື່');
    }
    if ($c['tel'] === '' && $c['email'] === '') {
        $errors['tel'] = t('ກະລຸນາປ້ອນເບີໂທ ຫຼື ອີເມວ ຢ່າງໃດຢ່າງໜຶ່ງ');
    }
    if ($c['email'] !== '' && !filter_var($c['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = t('ອີເມວບໍ່ຖືກຕ້ອງ');
    }
    if (mb_strlen($c['message']) < 5 || mb_strlen($c['message']) > 3000) {
        $errors['message'] = t('ກະລຸນາຂຽນຂໍ້ຄວາມ (5–3000 ຕົວອັກສອນ)');
    }
    return [$c, $errors];
}

function saveContact(PDO $pdo, array $c): bool
{
    try {
        $st = $pdo->prepare("INSERT INTO w_web_contacts (name, tel, email, subject, message) VALUES (?, ?, ?, ?, ?)");
        return $st->execute([$c['name'], $c['tel'] ?: null, $c['email'] ?: null, mb_substr($c['subject'], 0, 200) ?: null, $c['message']]);
    } catch (Throwable $e) {
        error_log('frontend saveContact: ' . $e->getMessage());
        return false;
    }
}
