<?php
/**
 * ສ່ວນ: ອັບເດດໂຄງສ້າງ DB: ຄະແນນສະສົມ
 * ກວດ/ສ້າງຕາຕະລາງ ແລະ ຄໍລຳຂອງລະບົບຄະແນນສະສົມ (loyalty) ຄັ້ງດຽວຕໍ່ session — ບໍ່ອີງໃສ່ໄຟລ໌ flag
 * ເພາະອາດຖືກສຳເນົາມາຈາກເຄື່ອງອື່ນ.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

// Loyalty points schema. Checked against the real database once per session rather than
// trusting the .db_schema_vNN flag file: a deploy that copies config/ from another install
// carries that flag along, the block above is then skipped, and every points save would fail
// on a missing table. Everything here is idempotent (IF NOT EXISTS / SHOW COLUMNS guards).
if (isset($pdo) && $pdo instanceof PDO && empty($_SESSION['loyalty_schema_v3'])) {
    try {
        // Redemption catalogue: what a customer can exchange points for. 'discount' takes
        // discount_amount (KIP) off the bill (only when the bill reaches min_spend); 'product'
        // adds product_qty of product_id (resolved in the selling branch) at price 0.
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_loyalty_rewards` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `reward_name` VARCHAR(255) NOT NULL,
            `points_cost` INT NOT NULL,
            `reward_type` VARCHAR(20) NOT NULL DEFAULT 'discount',
            `discount_amount` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `min_spend` DECIMAL(15,2) NOT NULL DEFAULT 0,
            `product_id` INT NULL,
            `product_qty` INT NOT NULL DEFAULT 1,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $lrCols = $pdo->query("SHOW COLUMNS FROM st_loyalty_rewards")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('min_spend', $lrCols)) {
            $pdo->exec("ALTER TABLE st_loyalty_rewards ADD COLUMN `min_spend` DECIMAL(15,2) NOT NULL DEFAULT 0 AFTER `discount_amount`");
        }

        // Every change to customers.points: earn (+), redeem (-), and their reversals when a
        // bill is cancelled. balance_after is the customer's points right after the change.
        $pdo->exec("CREATE TABLE IF NOT EXISTS `st_loyalty_points_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `customer_id` INT NOT NULL,
            `store_id` INT NULL,
            `change_points` INT NOT NULL,
            `log_type` VARCHAR(20) NOT NULL,
            `sale_bill` VARCHAR(50) NULL,
            `note` VARCHAR(255) NULL,
            `balance_after` INT NOT NULL DEFAULT 0,
            `user_id` INT NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_lpl_customer` (`customer_id`),
            INDEX `idx_lpl_bill` (`sale_bill`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $lpCustCols = $pdo->query("SHOW COLUMNS FROM st_customers")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('points', $lpCustCols)) {
            $pdo->exec("ALTER TABLE `st_customers` ADD COLUMN `points` INT NOT NULL DEFAULT 0");
        }

        // Points earned / spent on each bill, and the part of sale_discount_bill the spent
        // points paid for (a product reward costs points but no discount — it's a 0-price line).
        $lpSaleCols = $pdo->query("SHOW COLUMNS FROM st_tbsale_save")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('points_earned', $lpSaleCols)) {
            $pdo->exec("ALTER TABLE st_tbsale_save ADD COLUMN `points_earned` INT NOT NULL DEFAULT 0");
        }
        if (!in_array('points_redeemed', $lpSaleCols)) {
            $pdo->exec("ALTER TABLE st_tbsale_save ADD COLUMN `points_redeemed` INT NOT NULL DEFAULT 0");
        }
        if (!in_array('points_discount', $lpSaleCols)) {
            $pdo->exec("ALTER TABLE st_tbsale_save ADD COLUMN `points_discount` DECIMAL(15,2) NOT NULL DEFAULT 0");
        }

        // Per line, what a reprint needs to draw the line exactly like the checkout receipt:
        // the unit price before a promotion (struck through when higher) and whether the line
        // was a points reward.
        $lpDetCols = $pdo->query("SHOW COLUMNS FROM st_tbsale_save_detail")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('save_orig_price', $lpDetCols)) {
            $pdo->exec("ALTER TABLE st_tbsale_save_detail ADD COLUMN `save_orig_price` DECIMAL(15,2) NULL");
        }
        if (!in_array('is_reward', $lpDetCols)) {
            $pdo->exec("ALTER TABLE st_tbsale_save_detail ADD COLUMN `is_reward` TINYINT(1) NOT NULL DEFAULT 0");
        }

        $pdo->exec("INSERT IGNORE INTO st_system_settings (setting_key, setting_value) VALUES
            ('loyalty_enabled', '0'), ('loyalty_spend_amount', '100000'), ('loyalty_points_earned', '1')");

        $_SESSION['loyalty_schema_v3'] = 1;
    } catch (Throwable $ex) {
        // Leave the session flag unset so the next request tries again.
        error_log('MiniPos loyalty schema: ' . $ex->getMessage());
    }
}




