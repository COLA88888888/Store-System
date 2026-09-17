<?php require 'config/db.php'; print_r($pdo->query('SHOW COLUMNS FROM sales')->fetchAll(PDO::FETCH_COLUMN)); ?>
