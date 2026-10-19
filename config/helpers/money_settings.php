<?php
/**
 * ສ່ວນ: ຟັງຊັນຊ່ວຍ: ການຕັ້ງຄ່າ, ເງິນ, ບັນທຶກ
 * getSetting (ອ່ານການຕັ້ງຄ່າ), changeCustomerPoints (ປ່ຽນຄະແນນລູກຄ້າ), ຟັງຊັນສະກຸນເງິນ ແລະ
 * ການສະແດງເງິນ (formatCurrency, displayMoney, convertFromKip...), logDebug ແລະ logActivity
 * (ບັນທຶກການເຄື່ອນໄຫວ).
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

if (!function_exists('getSetting')) {
    function getSetting($pdo, $key, $default = '') {
        if (!$pdo) return $default;
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM st_system_settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return ($val !== false && $val !== null) ? $val : $default;
        } catch (Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('changeCustomerPoints')) {
    /**
     * Apply a points change to a customer and record it in loyalty_points_log. Runs inside
     * the caller's transaction (checkout / bill cancel), so a failure rolls both back.
     * Never lets the balance go below 0. Returns the balance after the change.
     */
    function changeCustomerPoints($pdo, $customerId, $delta, $logType, $saleBill = null, $note = null, $storeId = null) {
        $customerId = intval($customerId);
        $delta = intval($delta);
        if ($customerId <= 0 || $delta === 0) return null;
        $pdo->prepare("UPDATE st_customers SET points = GREATEST(0, points + ?) WHERE customer_id = ?")
            ->execute([$delta, $customerId]);
        $bStmt = $pdo->prepare("SELECT points FROM st_customers WHERE customer_id = ?");
        $bStmt->execute([$customerId]);
        $balance = (int)$bStmt->fetchColumn();
        $pdo->prepare("INSERT INTO st_loyalty_points_log (customer_id, store_id, change_points, log_type, sale_bill, note, balance_after, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$customerId, $storeId, $delta, $logType, $saleBill, $note, $balance, intval($_SESSION['user_id'] ?? 0) ?: null]);
        return $balance;
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount)
    {
        return number_format((float)$amount, 0, '.', ',') . ' ₭';
    }
}

if (!function_exists('getExchangeRates')) {
    /**
     * KIP value of 1 unit of each currency, from the newest Active tbexchange row.
     * KIP is always 1. A 0 means that rate has not been configured yet.
     */
    function getExchangeRates($pdo)
    {
        $rates = ['KIP' => 1.0, 'THB' => 0.0, 'USD' => 0.0, 'CNY' => 0.0];
        if (!$pdo) return $rates;
        try {
            // `ex_kip_cn` may be missing on a not-yet-migrated install — SELECT * so one
            // absent column can never wipe out the THB/USD rates too.
            $row = $pdo->query("SELECT * FROM st_tbexchange ORDER BY (ex_status = 'Active') DESC, Id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $rates['THB'] = (float) str_replace(',', '', (string)($row['ex_kip_bath'] ?? '0'));
                $rates['USD'] = (float) str_replace(',', '', (string)($row['ex_kip_us'] ?? '0'));
                $rates['CNY'] = (float) str_replace(',', '', (string)($row['ex_kip_cn'] ?? '0'));
            }
        } catch (Throwable $e) {}
        return $rates;
    }
}

if (!function_exists('getCurrencyMeta')) {
    function getCurrencyMeta()
    {
        // decimals 0 everywhere: amounts show whole numbers with a comma thousands
        // separator and no decimal point (e.g. "1,234 $").
        return [
            'KIP' => ['name' => 'ກີບ',  'symbol' => '₭', 'decimals' => 0],
            'THB' => ['name' => 'ບາດ',  'symbol' => '฿', 'decimals' => 0],
            'USD' => ['name' => 'ໂດລາ', 'symbol' => '$', 'decimals' => 0],
            'CNY' => ['name' => 'ຢວນ',  'symbol' => '¥', 'decimals' => 0],
        ];
    }
}

if (!function_exists('getDisplayCurrency')) {
    /**
     * Returns the active display currency: ['code','name','symbol','decimals','rate','rates'].
     * `rate` = KIP per 1 unit of this currency (1 for KIP). Falls back to KIP when the
     * chosen currency has no configured exchange rate yet.
     */
    function getDisplayCurrency($pdo)
    {
        // A report that lets the user filter down to one tendered currency (e.g. the
        // currency_filter dropdown on reports/daily_report) overrides the global
        // display currency for the rest of the request — see setDisplayCurrencyOverride().
        if (!empty($GLOBALS['__display_currency_override'])) {
            return $GLOBALS['__display_currency_override'];
        }
        $code  = strtoupper(trim((string) getSetting($pdo, 'display_currency', 'KIP')));
        $meta  = getCurrencyMeta();
        $rates = getExchangeRates($pdo);
        if (!isset($meta[$code])) $code = 'KIP';
        if ($code !== 'KIP' && (empty($rates[$code]) || $rates[$code] <= 0)) $code = 'KIP';
        return array_merge(
            ['code' => $code, 'rate' => ($code === 'KIP' ? 1.0 : (float)$rates[$code]), 'rates' => $rates],
            $meta[$code]
        );
    }
}

if (!function_exists('convertFromKip')) {
    /** Convert a KIP amount into `$code`, rounded to that currency's decimals. */
    function convertFromKip($amountKip, $code, $rates = null, $pdo = null)
    {
        $code = strtoupper((string)$code);
        if ($code === 'KIP' || $code === '') return round((float)$amountKip);
        if ($rates === null) $rates = getExchangeRates($pdo);
        $rate = isset($rates[$code]) ? (float)$rates[$code] : 0.0;
        if ($rate <= 0) return (float)$amountKip;
        $meta = getCurrencyMeta();
        $dec  = $meta[$code]['decimals'] ?? 2;
        return round((float)$amountKip / $rate, $dec);
    }
}

if (!function_exists('formatMoneyIn')) {
    /** Format a KIP amount as a string in currency `$code` (e.g. "$ 12.50", "1,000 ₭"). */
    function formatMoneyIn($amountKip, $code, $rates = null, $pdo = null)
    {
        $code = strtoupper((string)$code) ?: 'KIP';
        $meta = getCurrencyMeta();
        if (!isset($meta[$code])) $code = 'KIP';
        $dec = $meta[$code]['decimals'];
        $val = convertFromKip($amountKip, $code, $rates, $pdo);
        return number_format((float)$val, $dec, '.', ',')
             . "\u{00A0}<span class=\"cur-sym\">" . $meta[$code]['symbol'] . '</span>';
    }
}

if (!function_exists('setDisplayCurrencyOverride')) {
    /**
     * Force displayMoney() to render in one specific currency for the rest of this
     * request, instead of the global system_settings 'display_currency'. Used when a
     * report page lets the user filter down to one tendered currency (e.g. the daily
     * report's currency_filter) — the amounts on screen should switch to match.
     */
    function setDisplayCurrencyOverride($dc)
    {
        $GLOBALS['__display_currency_override'] = $dc;
    }
}

if (!function_exists('displayMoney')) {
    /**
     * Format a KIP amount in the request's active display currency, with the
     * currency lookup cached for the whole request. Use everywhere a report shows
     * money: displayMoney($pdo, $kipAmount). Data stays KIP — this is display only.
     */
    function displayMoney($pdo, $amountKip, $withSymbol = true)
    {
        static $dc = null;
        if (!empty($GLOBALS['__display_currency_override'])) {
            $dc = $GLOBALS['__display_currency_override'];
        } elseif ($dc === null) {
            $dc = function_exists('getDisplayCurrency') ? getDisplayCurrency($pdo)
                : ['code' => 'KIP', 'rates' => ['KIP' => 1]];
        }
        if ($withSymbol) {
            return formatMoneyIn($amountKip, $dc['code'], $dc['rates']);
        }
        $meta = getCurrencyMeta();
        $code = $dc['code'];
        $val  = convertFromKip($amountKip, $code, $dc['rates']);
        return number_format((float)$val, $meta[$code]['decimals'], '.', ',');
    }
}

if (!function_exists('logDebug')) {
    function logDebug($message)
    {
        // debug logging helper
    }
}

if (!function_exists('logActivity')) {
    function logActivity($pdo, $action, $detail = '')
    {
        if (!$pdo) {
            return;
        }
        try {
            // Ensure table exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS `st_activity_logs` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `action` VARCHAR(255) NOT NULL,
                `detail` TEXT NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            $stmt = $pdo->prepare('INSERT INTO st_activity_logs (action, detail, created_at) VALUES (?, ?, NOW())');
            $stmt->execute([$action, $detail]);
        } catch (Throwable $e) {
            // Ignore activity log failures safely
        }
    }
}

