<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// ເກັບປະຫວັດການອອກຈາກລະບົບກ່ອນການທຳລາຍ session
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    if (isset($pdo)) {
        try {
            $pdo->exec("UPDATE w_tbuser SET last_activity = NULL WHERE Id = '$uid'");
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
        sessionStorage.removeItem('WlaoDev.currentIframeSrc');
        sessionStorage.removeItem('WlaoDev.sidebarScrollTop');

        // ແຈ້ງເຕືອນຈໍລູກຄ້າ Real-Time ວ່າພະນັກງານອອກຈາກລະບົບ (ຍ່າງອອກຈາກຮ້ານ)
        try {
            var exitMsg = {
                action: 'cashier_exit',
                name: <?php echo json_encode($user_name ?? '', JSON_UNESCAPED_UNICODE); ?>,
                id: <?php echo (int)($uid ?? 0); ?>,
                time: Date.now()
            };
            try {
                var bc1 = new BroadcastChannel('WlaoDev.customer_display_channel');
                bc1.postMessage(exitMsg);
                setTimeout(function() { bc1.close(); }, 300);
            } catch(e) {}
            try {
                var bc2 = new BroadcastChannel('WlaoDev.cashier_channel');
                bc2.postMessage(exitMsg);
                setTimeout(function() { bc2.close(); }, 300);
            } catch(e) {}
            localStorage.setItem('WlaoDev.cashier_exited', JSON.stringify(exitMsg));
        } catch(e) {}
        
        var redirectUrl = 'login.php<?php echo isset($_GET['expired']) ? "?expired=1" : ""; ?>';
        if (window.top) {
            window.top.location.href = redirectUrl;
        } else {
            window.location.href = redirectUrl;
        }
    </script>
</head>
<body>
    <p><?php echo tlh('ກຳລັງອອກຈາກລະບົບ...'); ?></p>
</body>
</html>
