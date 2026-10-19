<?php
/**
 * ສ່ວນ: ເຊື່ອມຕໍ່ຖານຂໍ້ມູນ
 * ຕັ້ງເຂດເວລາ Asia/Vientiane, ໂຫຼດລະບົບແປພາສາ, ປິດການສະແດງ error ໃຫ້ຜູ້ໃຊ້ເຫັນ, ກຳນົດຂໍ້ມູນເຂົ້າ
 * MySQL ແລ້ວເປີດການເຊື່ອມຕໍ່ທັງ $conn (mysqli) ແລະ $pdo (PDO) ໂດຍລອງພອດ 3306 ກ່ອນ ແລ້ວ 3336.
 * ໄຟລ໌ນີ້ຖືກແຍກອອກມາຈາກ config/db.php ແລະ ຖືກດຶງເຂົ້າໄປໃຊ້ດ້ວຍ require ຢູ່ຈຸດເດີມ (ໃຊ້ຕົວແປຮ່ວມກັນ).
 */

date_default_timezone_set('Asia/Vientiane');

require_once dirname(__DIR__) . '/../lang/translator.php';

// Don't leak PHP errors/warnings to visitors in production; still log them to the
// server's error log so `tail error.log` keeps working the same way it does in dev.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

// TODO before going live: replace with the production MySQL host/user/password.
$server = "127.0.0.1";
$username = "root";
$password = "";
$database = "storepos"; // ຕາຕະລາງທັງໝົດມີ prefix st_ (st_products, st_tbuser, …)

$conn = null;
$pdo = null;
$port = 3306;
$portsToTry = [3306, 3336];
foreach ($portsToTry as $p) {
    try {
        $testConn = @mysqli_connect($server, $username, $password, $database, $p);
        if ($testConn) {
            $conn = $testConn;
            $port = $p;
            mysqli_set_charset($conn, "utf8");
            try {
                $pdo = new PDO(
                    "mysql:host={$server};port={$port};dbname={$database};charset=utf8mb4",
                    $username,
                    $password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
                $pdo->exec("SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            } catch (Throwable $pe) {}
            @mysqli_query($conn, "SET SESSION sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            break;
        }
    } catch (Throwable $ex) {}
}


