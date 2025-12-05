<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    require_once 'config/database.php';
    $admin_id = $_SESSION['admin_id'];
    $conn->query("UPDATE login_logs SET logout_time = NOW() 
                  WHERE admin_id = $admin_id AND logout_time IS NULL 
                  ORDER BY login_time DESC LIMIT 1");
}

session_unset();
session_destroy();
header('Location: login.php');
exit;
?>