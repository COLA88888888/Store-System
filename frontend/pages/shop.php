<?php
/**
 * ໜ້າຮ້ານຄ້າເກົ່າ — ຍ້າຍ ແລະ redirect ໄປໜ້າອຸປະກອນ POS ທັງໝົດ (pages/equipment_list.php).
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
header('Location: ' . url('pages/equipment_list.php'), true, 301);
exit;

