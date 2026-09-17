<?php require 'config/db.php'; print_r($pdo->query('SHOW COLUMNS FROM quotation_details')->fetchAll(PDO::FETCH_COLUMN)); ?>
