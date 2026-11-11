<?php
/**
 * ລິ້ງເກົ່າ — ຜົນງານທັງໝົດຍ້າຍໄປໜ້າເມນູ "ຜົນງານ" (pages/portfolio.php) ແລ້ວ. 301 ເພື່ອໃຫ້ Google ຍ້າຍຕາມ.
 */
require dirname(__DIR__) . '/includes/bootstrap.php';
header('Location: ' . url('pages/portfolio.php'), true, 301);
exit;
