<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cereals_inventory');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8
$conn->set_charset("utf8");

// Function to sanitize input
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Function to format currency
function format_currency($amount) {
    return 'KSh ' . number_format($amount, 2);
}

// Function to check low stock
function get_low_stock_count() {
    global $conn;
    $sql = "SELECT COUNT(*) as count FROM products WHERE current_stock <= reorder_level";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['count'];
}
?>