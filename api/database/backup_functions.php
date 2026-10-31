<?php
/**
 * ສ່ວນ: ຟັງຊັນສຳຮອງ / ຟື້ນຟູຖານຂໍ້ມູນ
 * ແຍກອອກມາຈາກ api/database_backend.php (ໄຟລ໌ນັ້ນ require ໄຟລ໌ນີ້ກ່ອນຈັດການຄຳຂໍ).
 *
 * ສຳຮອງ 2 ແບບ:
 *   ທັງໝົດ (exportDatabaseSQL) — DROP + CREATE + INSERT ທຸກຕາຕະລາງ; ຟື້ນຟູ = ຖານຂໍ້ມູນກັບເປັນຄືຕອນສຳຮອງທຸກປະການ
 *   ສາຂາດຽວ (exportBranchSQL) — DELETE ແຖວຂອງສາຂາ ແລ້ວ INSERT IGNORE ຄືນ; ສາຂາອື່ນ ແລະ ຂໍ້ມູນໃຊ້ຮ່ວມບໍ່ຖືກແຕະ.
 *     ແຖວທຳອິດ = "-- StoreMini branch backup: store_id=N" (backupFileStoreId)
 * ທຸກໄຟລ໌ໃໝ່ມີແຖວ "-- rows `ຕາຕະລາງ` N" (ຈຳນວນແຖວຕອນສຳຮອງ) — ຫຼັງຟື້ນຟູ verifyRestoredCounts ນັບຄືນ
 * ເພື່ອຢືນຢັນວ່າຂໍ້ມູນກັບມາຄົບຄືເກົ່າ. ໄຟລ໌ເກົ່າທີ່ບໍ່ມີແຖວນີ້ ຟື້ນຟູໄດ້ຄືເກົ່າ ແຕ່ບໍ່ມີການກວດ.
 */

// ປ່ຽນ charset/collation ທຸກບ່ອນໃນ CREATE ໃຫ້ເປັນ utf8mb4_general_ci ຢ່າງດຽວ
// (ຖານຂໍ້ມູນເກົ່າມີປົນກັນ: utf8mb4_0900_ai_ci, utf8mb4_unicode_ci, utf8mb3_unicode_ci)
function normalizeCollationSQL($createSql) {
    $createSql = preg_replace('/\bCHARACTER SET (utf8mb3|utf8mb4|utf8)\b/i', 'CHARACTER SET utf8mb4', $createSql);
    $createSql = preg_replace('/\bCOLLATE([ =])(utf8mb3|utf8mb4|utf8)_\w+/i', 'COLLATE$1utf8mb4_general_ci', $createSql);
    $createSql = preg_replace(
        '/\bDEFAULT CHARSET=\w+(\s+COLLATE=\w+)?/i',
        'DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci',
        $createSql
    );
    return $createSql;
}

// ຂຽນແຖວຂອງຕາຕະລາງເປັນ INSERT (ຊຸດລະ 200 ແຖວ) ແລ້ວປິດທ້າຍດ້ວຍ "-- rows `t` N". $where ສ້າງຈາກ store_id (int) ເທົ່ານັ້ນ.
// ຂ້າມສະເພາະ generated column ແທ້ (VIRTUAL/STORED GENERATED) — ຖັນ DEFAULT CURRENT_TIMESTAMP ທີ່ MySQL 8 ໝາຍ
// "DEFAULT_GENERATED" (ເຊັ່ນ created_at) ຕ້ອງເກັບໄວ້ ບໍ່ດັ່ງນັ້ນຟື້ນຟູແລ້ວວັນທີກາຍເປັນເວລາຟື້ນຟູ.
// ເກັບ binary/blob ເປັນ hex ເພື່ອບໍ່ໃຫ້ໄຟລ໌ເສຍ
function dumpTableRows($pdo, $fh, $table, $where = '', $verb = 'INSERT INTO') {
    $insertCols = [];
    $binaryCols = [];
    foreach ($pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC) as $col) {
        if (preg_match('/\b(VIRTUAL|STORED) GENERATED\b/i', $col['Extra'] ?? '')) continue;
        $insertCols[] = $col['Field'];
        if (preg_match('/^(tiny|medium|long)?blob|^(var)?binary/i', $col['Type'])) {
            $binaryCols[$col['Field']] = true;
        }
    }
    if (empty($insertCols)) return;

    $colList = implode(', ', array_map(function ($c) { return "`{$c}`"; }, $insertCols));
    $stmt = $pdo->query("SELECT {$colList} FROM `{$table}`" . ($where !== '' ? " WHERE {$where}" : ''));

    $rows = 0;
    $batch = [];
    $flush = function () use (&$batch, $fh, $table, $colList, $verb) {
        if (empty($batch)) return;
        fwrite($fh, "{$verb} `{$table}` ({$colList}) VALUES\n" . implode(",\n", $batch) . ";\n");
        $batch = [];
    };
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $vals = [];
        foreach ($row as $field => $v) {
            if ($v === null) {
                $vals[] = 'NULL';
            } elseif (isset($binaryCols[$field])) {
                $vals[] = $v === '' ? "''" : '0x' . bin2hex($v);
            } else {
                $vals[] = $pdo->quote($v);
            }
        }
        $batch[] = '(' . implode(', ', $vals) . ')';
        $rows++;
        if (count($batch) >= 200) $flush();
    }
    $flush();
    $stmt->closeCursor();
    fwrite($fh, "-- rows `{$table}` {$rows}\n");
}

// Export database: ຕາຕະລາງ (DROP + CREATE) ແລະ ຂໍ້ມູນ (INSERT) ທຸກຕາຕະລາງ.
// ໂຄງສ້າງທຸກຕາຕະລາງເປັນ utf8mb4_general_ci (normalizeCollationSQL). CREATE ພາ AUTO_INCREMENT ເດີມໄປນຳ.
function exportDatabaseSQL($pdo, $dbname, $outputPath) {
    $fh = @fopen($outputPath, 'wb');
    if (!$fh) return false;

    try {
        $objects = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);

        foreach ($objects as [$table]) {
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
            if (!$createStmt) continue;

            fwrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($fh, normalizeCollationSQL($createStmt[1]) . ";\n\n");
            dumpTableRows($pdo, $fh, $table);
            fwrite($fh, "\n");
        }

        fclose($fh);
        return true;
    } catch (Throwable $e) {
        fclose($fh);
        @unlink($outputPath);
        return false;
    }
}

// ເງື່ອນໄຂ "ແຖວຂອງສາຂາ N" ຂອງແຕ່ລະຕາຕະລາງ. ຕາຕະລາງລູກທີ່ບໍ່ມີຖັນສາຂາ ຮູ້ສາຂາຜ່ານຕາຕະລາງແມ່ —
// ຢູ່ລຳດັບທຳອິດ ເພື່ອໃຫ້ DELETE ຂອງລູກແລ່ນກ່ອນແມ່ຖືກລຶບ. ຕາຕະລາງອື່ນ: store_id ກ່ອນ, ບໍ່ມີຈຶ່ງໃຊ້ branch_id.
// ບໍ່ມີຖັນສາຂາ ຫຼື ຢູ່ໃນລາຍການຍົກເວັ້ນ = ຂໍ້ມູນໃຊ້ຮ່ວມ → ບໍ່ຢູ່ໃນໄຟລ໌ສາຂາ.
function branchBackupConditions($pdo, $storeId) {
    $sid = (int)$storeId;
    $children = [
        'st_tbsale_save_detail'     => "`save_bill` IN (SELECT `sale_save_bill` FROM `st_tbsale_save` WHERE `store_id` = {$sid})",
        'st_import_details'         => "`import_id` IN (SELECT `import_id` FROM `st_imports` WHERE `store_id` = {$sid})",
        'st_stock_transfer_details' => "`transfer_id` IN (SELECT `transfer_id` FROM `st_stock_transfers` WHERE `from_store_id` = {$sid} OR `to_store_id` = {$sid})",
        'st_stock_check_items'      => "`check_id` IN (SELECT `id` FROM `st_stock_checks` WHERE `store_id` = {$sid})",
        'st_stock_check_count_log'  => "`check_id` IN (SELECT `id` FROM `st_stock_checks` WHERE `store_id` = {$sid})",
        'st_product_units'          => "`product_id` IN (SELECT `product_id` FROM `st_products` WHERE `store_id` = {$sid})",
        'st_stock_transfers'        => "(`from_store_id` = {$sid} OR `to_store_id` = {$sid})",
    ];
    // ບັນຊີຜູ້ໃຊ້, ສິດ, ສາຂາ ແລະ ການຕັ້ງຄ່າລະບົບ ເປັນຂອງທັງຮ້ານ
    $shared = ['st_tbstore', 'st_tbuser', 'st_user_permission_switch_states', 'st_system_settings'];

    $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    $conds = [];
    foreach ($children as $tb => $cond) {
        if (in_array($tb, $tables, true)) $conds[$tb] = $cond;
    }
    foreach ($tables as $tb) {
        if (isset($conds[$tb]) || in_array($tb, $shared, true)) continue;
        $cols = $pdo->query("SHOW COLUMNS FROM `{$tb}`")->fetchAll(PDO::FETCH_COLUMN);
        if (in_array('store_id', $cols, true)) {
            $conds[$tb] = "`store_id` = {$sid}";
        } elseif (in_array('branch_id', $cols, true)) {
            $conds[$tb] = "`branch_id` = {$sid}";
        }
    }
    return $conds;
}

// Export ສາຂາດຽວ: DELETE ແຖວຂອງສາຂາ (ລູກກ່ອນ) ແລ້ວ INSERT IGNORE ຄືນ — ຟື້ນຟູແລ້ວບໍ່ກະທົບສາຂາອື່ນ.
// INSERT IGNORE: ຖ້າ id ໃດຖືກສາຂາອື່ນໃຊ້ໄປແລ້ວຫຼັງສຳຮອງ ແຖວນັ້ນຖືກຂ້າມ (ບໍ່ຂຽນທັບຂໍ້ມູນສາຂາອື່ນ) —
// verifyRestoredCounts ຈະລາຍງານວ່າຂາດ. (ໜ້າລ້າງຂໍ້ມູນບໍ່ຕັ້ງ id ກັບໄປ 1 ອີກແລ້ວ ສະນັ້ນ id ບໍ່ຊ້ຳກັນ)
function exportBranchSQL($pdo, $storeId, $storeName, $outputPath) {
    $fh = @fopen($outputPath, 'wb');
    if (!$fh) return false;

    try {
        $conds = branchBackupConditions($pdo, $storeId);
        fwrite($fh, "-- StoreMini branch backup: store_id=" . (int)$storeId . "\n");
        fwrite($fh, "-- " . str_replace(["\r", "\n"], ' ', $storeName) . " · " . date('Y-m-d H:i:s') . "\n");
        foreach ($conds as $tb => $cond) {
            fwrite($fh, "DELETE FROM `{$tb}` WHERE {$cond};\n");
        }
        fwrite($fh, "\n");
        foreach ($conds as $tb => $cond) {
            dumpTableRows($pdo, $fh, $tb, $cond, 'INSERT IGNORE INTO');
        }
        fclose($fh);
        return true;
    } catch (Throwable $e) {
        fclose($fh);
        @unlink($outputPath);
        return false;
    }
}

// ໄຟລ໌ສຳຮອງເປັນຂອງສາຂາໃດ: 0 = ສຳຮອງທັງໝົດ
function backupFileStoreId($path) {
    $fh = @fopen($path, 'rb');
    if (!$fh) return 0;
    $first = (string)fgets($fh, 256);
    fclose($fh);
    return preg_match('/^-- StoreMini branch backup: store_id=(\d+)/', $first, $m) ? (int)$m[1] : 0;
}

// ແຍກໄຟລ໌ SQL ເປັນຄຳສັ່ງ: ຕັດທີ່ ";" ທີ່ຢູ່ນອກ string ເທົ່ານັ້ນ — ຂໍ້ຄວາມທີ່ມີ ";" + ຂຶ້ນແຖວໃໝ່
// (ເຊັ່ນ ໝາຍເຫດ, cart_json) ບໍ່ຖືກຕັດກາງ. ແຖວ comment "-- ..." ນອກ string ຖືກຂ້າມ.
function splitSqlStatements($sql) {
    $out = [];
    $buf = '';      // ຄຳສັ່ງທີ່ກຳລັງສະສົມ (ບໍ່ລວມແຖວ comment)
    $len = strlen($sql);
    $start = 0;
    $i = 0;
    while ($i < $len) {
        $c = $sql[$i];
        if ($c === "'" || $c === '"' || $c === '`') {
            // ຂ້າມທັງ string: \x ແລະ '' ເປັນ escape
            $q = $c;
            $i++;
            while ($i < $len) {
                $d = $sql[$i];
                if ($d === '\\' && $q !== '`') { $i += 2; continue; }
                if ($d === $q) {
                    if ($i + 1 < $len && $sql[$i + 1] === $q) { $i += 2; continue; }
                    break;
                }
                $i++;
            }
            $i++;
            continue;
        }
        if ($c === '-' && $i + 1 < $len && $sql[$i + 1] === '-' && ($i === 0 || $sql[$i - 1] === "\n")) {
            // ແຖວ comment: ເກັບສ່ວນກ່ອນໜ້າໄວ້ໃນ $buf ແລ້ວຂ້າມຮອດທ້າຍແຖວ
            $buf .= substr($sql, $start, $i - $start);
            $nl = strpos($sql, "\n", $i);
            $i = $nl === false ? $len : $nl + 1;
            $start = $i;
            continue;
        }
        if ($c === ';') {
            $stmt = trim($buf . substr($sql, $start, $i - $start));
            if ($stmt !== '') $out[] = $stmt;
            $buf = '';
            $start = $i + 1;
        }
        $i++;
    }
    $tail = trim($buf . substr($sql, $start));
    if ($tail !== '') $out[] = $tail;
    return $out;
}

// ຈຳນວນແຖວທີ່ໄຟລ໌ບັນທຶກໄວ້ ("-- rows `t` N")
function backupExpectedCounts($sql) {
    preg_match_all('/^-- rows `(\w+)` (\d+)\s*$/m', $sql, $m, PREG_SET_ORDER);
    $counts = [];
    foreach ($m as $row) $counts[$row[1]] = (int)$row[2];
    return $counts;
}

// ນັບແຖວຫຼັງຟື້ນຟູ ທຽບກັບຕອນສຳຮອງ → [ຕາຕະລາງ => [expected, actual]] ສະເພາະທີ່ບໍ່ກົງກັນ
function verifyRestoredCounts($pdo, $expected, $storeId) {
    $conds = $storeId > 0 ? branchBackupConditions($pdo, $storeId) : [];
    $bad = [];
    foreach ($expected as $tb => $n) {
        try {
            $where = $storeId > 0 ? ($conds[$tb] ?? null) : '';
            if ($where === null) continue;
            $actual = (int)$pdo->query("SELECT COUNT(*) FROM `{$tb}`" . ($where !== '' ? " WHERE {$where}" : ''))->fetchColumn();
        } catch (Throwable $e) {
            $actual = -1;
        }
        if ($actual !== $n) $bad[$tb] = [$n, $actual];
    }
    return $bad;
}

// ຟື້ນຟູຈາກໄຟລ໌ → ['ok', 'statements', 'failed', 'first_error', 'mismatch' => [ຕາຕະລາງ => [ສຳຮອງ, ຫຼັງຟື້ນຟູ]]]
// sql_mode ຖືກປິດ strict ສະເພາະການຟື້ນຟູ (ຄືກັບ mysqldump) ເພື່ອໃຫ້ຄ່າເກົ່າ ເຊັ່ນ ວັນທີ 0000-00-00 ເຂົ້າໄດ້ຄືເດີມ.
function restoreDatabaseSQL($pdo, $sqlFilePath) {
    $result = ['ok' => false, 'statements' => 0, 'failed' => 0, 'first_error' => '', 'mismatch' => [], 'tables' => []];
    if (!file_exists($sqlFilePath)) return $result;
    $sql = file_get_contents($sqlFilePath);
    if ($sql === false || trim($sql) === '') return $result;
    $sql = str_replace("\r\n", "\n", $sql);

    @set_time_limit(0);
    @ignore_user_abort(true);   // ປິດໜ້າເວັບກາງທາງ ການຟື້ນຟູຍັງແລ່ນຈົນແລ້ວ
    $oldMode = (string)$pdo->query('SELECT @@SESSION.sql_mode')->fetchColumn();
    $pdo->exec("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

    foreach (splitSqlStatements($sql) as $q) {
        $result['statements']++;
        try {
            // query + closeCursor: ຄຳສັ່ງທີ່ສົ່ງຜົນລັບ (SELECT/SHOW ໃນໄຟລ໌ທີ່ອັບໂຫຼດ) ບໍ່ຄ້າງການເຊື່ອມຕໍ່ໄວ້
            $st = $pdo->query($q);
            if ($st) $st->closeCursor();
        } catch (Throwable $e) {
            $result['failed']++;
            if ($result['first_error'] === '') $result['first_error'] = $e->getMessage();
        }
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    try { $pdo->exec('SET SESSION sql_mode = ' . $pdo->quote($oldMode)); } catch (Throwable $e) {}

    $expected = backupExpectedCounts($sql);
    $result['tables'] = array_keys($expected);
    if ($expected) {
        $storeId = preg_match('/^-- StoreMini branch backup: store_id=(\d+)/', $sql, $m) ? (int)$m[1] : 0;
        $result['mismatch'] = verifyRestoredCounts($pdo, $expected, $storeId);
    }
    $result['ok'] = $result['failed'] === 0 && empty($result['mismatch']);
    return $result;
}
