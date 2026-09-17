<?php require 'config/db.php'; print_r($pdo->query('SHOW COLUMNS FROM products')->fetchAll(PDO::FETCH_COLUMN)); ?>
