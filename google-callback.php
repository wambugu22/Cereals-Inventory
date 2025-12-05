<?php
session_start();
require_once 'config/database.php';

// Get authorization code
$code = isset($_GET['code']) ? $_GET['code'] : '';

if (!$code) {
    header('Location: login.php?error=google_auth_failed');
    exit;
}

// Exchange code for access token
$clientId = 'YOUR_GOOGLE_CLIENT_ID';
$clientSecret = 'YOUR_GOOGLE_CLIENT_SECRET';
$redirectUri = 'http://localhost/cereals-inventory/google-callback.php';

$tokenUrl = 'https://oauth2.googleapis.com/token';
$postData = [
    'code' => $code,
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);

if (!isset($tokenData['access_token'])) {
    header('Location: login.php?error=token_error');
    exit;
}

// Get user info from Google
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($userInfoUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$userInfoResponse = curl_exec($ch);
curl_close($ch);

$userInfo = json_decode($userInfoResponse, true);

if (!isset($userInfo['email'])) {
    header('Location: login.php?error=user_info_error');
    exit;
}

// Check if user exists
$email = $conn->real_escape_string($userInfo['email']);
$google_id = $conn->real_escape_string($userInfo['id']);
$full_name = $conn->real_escape_string($userInfo['name']);

$checkSql = "SELECT * FROM admin_users WHERE email = '$email' OR google_id = '$google_id'";
$result = $conn->query($checkSql);

if ($result && $result->num_rows > 0) {
    // User exists - login
    $user = $result->fetch_assoc();
    
    // Update Google ID if not set
    if (!$user['google_id']) {
        $conn->query("UPDATE admin_users SET google_id = '$google_id' WHERE id = " . $user['id']);
    }
} else {
    // Create new user
    $username = strtolower(str_replace(' ', '', $userInfo['name'])) . rand(100, 999);
    $insertSql = "INSERT INTO admin_users (username, email, full_name, google_id, auth_method, status) 
                  VALUES ('$username', '$email', '$full_name', '$google_id', 'google', 'Active')";
    
    if ($conn->query($insertSql)) {
        $user_id = $conn->insert_id;
        $result = $conn->query("SELECT * FROM admin_users WHERE id = $user_id");
        $user = $result->fetch_assoc();
    } else {
        header('Location: login.php?error=signup_failed');
        exit;
    }
}

// Login user (skip 2FA for Google login)
$_SESSION['admin_logged_in'] = true;
$_SESSION['admin_id'] = $user['id'];
$_SESSION['admin_name'] = $user['full_name'];
$_SESSION['last_activity'] = time();

// Log login
$conn->query("INSERT INTO login_logs (admin_id, login_time) VALUES (" . $user['id'] . ", NOW())");

header('Location: index.php');
exit;
?>