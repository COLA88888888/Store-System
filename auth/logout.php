<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ເກັບປະຫວັດການອອກຈາກລະບົບກ່ອນການທຳລາຍ session
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    if (isset($pdo)) {
        try {
            $pdo->exec("UPDATE st_tbuser SET last_activity = NULL WHERE Id = '$uid'");
        } catch (Throwable $e) {}
    }
}
if (isset($_SESSION['fname'])) {
    $user_name = trim(($_SESSION['fname'] ?? '') . ' ' . ($_SESSION['lname'] ?? ''));
    logActivity($pdo, "ອອກຈາກລະບົບ", "ຜູ້ໃຊ້: " . $user_name);
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Logging out...</title>
    <script>
        sessionStorage.removeItem('StoreMini.currentIframeSrc');
        sessionStorage.removeItem('StoreMini.sidebarScrollTop');
        
        var redirectUrl = 'login.php<?php echo isset($_GET['expired']) ? "?expired=1" : ""; ?>';
        if (window.top) {
            window.top.location.href = redirectUrl;
        } else {
            window.location.href = redirectUrl;
        }
    </script>
</head>
<body>
    <p>ກຳລັງອອກຈາກລະບົບ...</p>
</body>
</html>
