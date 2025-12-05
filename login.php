<?php
session_start();
require_once 'config/database.php';
require_once 'config/email-config.php';

$error = '';
$success = '';
$show_2fa = false;
$show_signup = isset($_GET['signup']) ? true : false;

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

// Handle Signup
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'signup') {
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        $check_sql = "SELECT * FROM admin_users WHERE username = '$username' OR email = '$email'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            $error = "Username or email already exists!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO admin_users (username, password, full_name, email, auth_method, status) 
                          VALUES ('$username', '$hashed_password', '$full_name', '$email', 'password', 'Active')";
            
            if ($conn->query($insert_sql)) {
                // Send welcome email
                send_welcome_email($email, $full_name);
                $success = "Account created! Check your email for confirmation. You can now login.";
                $show_signup = false;
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}

// Handle Login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'login') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM admin_users WHERE username = '$username' AND status = 'Active'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        
        if (password_verify($password, $admin['password'])) {
            // Generate 2FA code
            $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['temp_admin_id'] = $admin['id'];
            $_SESSION['temp_admin_name'] = $admin['full_name'];
            $_SESSION['temp_admin_email'] = $admin['email'];
            $_SESSION['2fa_code'] = $code;
            $_SESSION['2fa_expires'] = time() + 300;
            
            // Send 2FA code via email
            if (send_2fa_email($admin['email'], $admin['full_name'], $code)) {
                $success = "2FA code sent to your email: " . substr($admin['email'], 0, 3) . "***";
                $show_2fa = true;
            } else {
                $error = "Failed to send 2FA code. Please try again.";
            }
        } else {
            $error = "Invalid username or password!";
        }
    } else {
        $error = "Invalid username or password!";
    }
}

// Handle 2FA
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'verify_2fa') {
    $entered_code = clean_input($_POST['code']);
    
    if (!isset($_SESSION['2fa_code'])) {
        $error = "2FA session expired. Please login again.";
    } elseif (time() > $_SESSION['2fa_expires']) {
        $error = "2FA code expired. Please login again.";
        unset($_SESSION['2fa_code'], $_SESSION['2fa_expires'], $_SESSION['temp_admin_id']);
    } elseif ($entered_code == $_SESSION['2fa_code']) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $_SESSION['temp_admin_id'];
        $_SESSION['admin_name'] = $_SESSION['temp_admin_name'];
        $_SESSION['last_activity'] = time();
        
        unset($_SESSION['2fa_code'], $_SESSION['2fa_expires'], $_SESSION['temp_admin_id'], $_SESSION['temp_admin_name'], $_SESSION['temp_admin_email']);
        
        $admin_id = $_SESSION['admin_id'];
        $conn->query("INSERT INTO login_logs (admin_id, login_time) VALUES ($admin_id, NOW())");
        
        header('Location: index.php');
        exit;
    } else {
        $error = "Invalid 2FA code!";
        $show_2fa = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $show_signup ? 'Sign Up' : 'Login'; ?> - DevTech Partners</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
            padding: 40px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .logo p {
            color: #7f8c8d;
            font-size: 14px;
        }
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        .tab {
            flex: 1;
            padding: 12px;
            text-align: center;
            background: #f5f5f5;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            color: #7f8c8d;
            text-decoration: none;
            transition: all 0.3s;
        }
        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .google-btn {
            width: 100%;
            padding: 14px;
            background: white;
            color: #757575;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .google-btn:hover {
            border-color: #4285f4;
            background: #f8f9fa;
        }
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #ddd;
        }
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #7f8c8d;
            font-size: 14px;
        }
        .error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success {
            background: #efe;
            border: 1px solid #cfc;
            color: #3c3;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .info {
            background: #e3f2fd;
            border: 1px solid #90caf9;
            color: #1976d2;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .code-display {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .code-display .code {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            letter-spacing: 5px;
            font-family: 'Courier New', monospace;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: white;
            font-size: 12px;
        }
        .help-text {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🌾 DevTech Partners</h1>
            <p>Inventory Management System</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (!$show_2fa): ?>
        
        <!-- Tabs -->
        <div class="tabs">
            <a href="login.php" class="tab <?php echo !$show_signup ? 'active' : ''; ?>">Login</a>
            <a href="login.php?signup=1" class="tab <?php echo $show_signup ? 'active' : ''; ?>">Sign Up</a>
        </div>
        
        <?php if ($show_signup): ?>
        <!-- SIGNUP FORM -->
        <form method="POST" action="">
            <input type="hidden" name="action" value="signup">
            
            <div class="form-group">
                <label>👤 Full Name</label>
                <input type="text" name="full_name" required placeholder="Enter your full name">
            </div>
            
            <div class="form-group">
                <label>📧 Email</label>
                <input type="email" name="email" required placeholder="your.email@example.com">
            </div>
            
            <div class="form-group">
                <label>👤 Username</label>
                <input type="text" name="username" required placeholder="Choose a username">
            </div>
            
            <div class="form-group">
                <label>🔒 Password</label>
                <input type="password" name="password" required placeholder="Minimum 6 characters">
            </div>
            
            <div class="form-group">
                <label>🔒 Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Re-enter password">
            </div>
            
            <button type="submit" class="btn">Create Account</button>
        </form>
        
        <div class="divider"><span>OR</span></div>
        
        <button class="google-btn" onclick="loginWithGoogle()">
            <svg width="20" height="20" viewBox="0 0 20 20">
                <path fill="#4285F4" d="M19.6 10.23c0-.82-.1-1.42-.25-2.05H10v3.72h5.5c-.15.96-.74 2.31-2.04 3.22v2.45h3.16c1.89-1.73 2.98-4.3 2.98-7.34z"/>
                <path fill="#34A853" d="M13.46 15.13c-.83.59-1.96 1-3.46 1-2.64 0-4.88-1.74-5.68-4.15H1.07v2.52C2.72 17.75 6.09 20 10 20c2.7 0 4.96-.89 6.62-2.42l-3.16-2.45z"/>
                <path fill="#FBBC05" d="M3.99 10c0-.69.12-1.35.32-1.97V5.51H1.07A9.973 9.973 0 000 10c0 1.61.39 3.14 1.07 4.49l3.24-2.52c-.2-.62-.32-1.28-.32-1.97z"/>
                <path fill="#EA4335" d="M10 3.88c1.88 0 3.13.81 3.85 1.48l2.84-2.76C14.96.99 12.7 0 10 0 6.09 0 2.72 2.25 1.07 5.51l3.24 2.52C5.12 5.62 7.36 3.88 10 3.88z"/>
            </svg>
            Sign up with Google
        </button>
        
        <?php else: ?>
        <!-- LOGIN FORM -->
        <form method="POST" action="">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label>👤 Username</label>
                <input type="text" name="username" required autofocus placeholder="Enter your username">
            </div>
            
            <div class="form-group">
                <label>🔒 Password</label>
                <input type="password" name="password" required placeholder="Enter your password">
            </div>
            
            <button type="submit" class="btn">Login</button>
        </form>
        
        <div class="divider"><span>OR</span></div>
        
        <button class="google-btn" onclick="loginWithGoogle()">
            <svg width="20" height="20" viewBox="0 0 20 20">
                <path fill="#4285F4" d="M19.6 10.23c0-.82-.1-1.42-.25-2.05H10v3.72h5.5c-.15.96-.74 2.31-2.04 3.22v2.45h3.16c1.89-1.73 2.98-4.3 2.98-7.34z"/>
                <path fill="#34A853" d="M13.46 15.13c-.83.59-1.96 1-3.46 1-2.64 0-4.88-1.74-5.68-4.15H1.07v2.52C2.72 17.75 6.09 20 10 20c2.7 0 4.96-.89 6.62-2.42l-3.16-2.45z"/>
                <path fill="#FBBC05" d="M3.99 10c0-.69.12-1.35.32-1.97V5.51H1.07A9.973 9.973 0 000 10c0 1.61.39 3.14 1.07 4.49l3.24-2.52c-.2-.62-.32-1.28-.32-1.97z"/>
                <path fill="#EA4335" d="M10 3.88c1.88 0 3.13.81 3.85 1.48l2.84-2.76C14.96.99 12.7 0 10 0 6.09 0 2.72 2.25 1.07 5.51l3.24 2.52C5.12 5.62 7.36 3.88 10 3.88z"/>
            </svg>
            Sign in with Google
        </button>
        
        <div class="help-text">
            Don't have an account? <a href="login.php?signup=1" style="color: #667eea; text-decoration: none; font-weight: 500;">Sign up here</a>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <!-- 2FA VERIFICATION -->
        <div class="info">
            <strong>📧 Check Your Email</strong><br>
            We've sent a 6-digit code to your email address
        </div>
        
        <?php if (isset($_SESSION['temp_admin_email'])): ?>
        <p style="text-align: center; color: #7f8c8d; margin: 15px 0;">
            Code sent to: <strong><?php echo substr($_SESSION['temp_admin_email'], 0, 3) . str_repeat('*', strlen($_SESSION['temp_admin_email']) - 6) . substr($_SESSION['temp_admin_email'], -3); ?></strong>
        </p>
        <?php endif; ?>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="verify_2fa">
            
            <div class="form-group">
                <label>Enter 6-Digit Code from Email</label>
                <input type="text" name="code" required maxlength="6" pattern="[0-9]{6}" 
                       placeholder="000000" autofocus 
                       style="text-align: center; font-size: 24px; letter-spacing: 5px; font-family: 'Courier New', monospace;">
            </div>
            
            <button type="submit" class="btn">Verify & Login</button>
        </form>
        
        <p style="text-align: center; margin-top: 15px; color: #7f8c8d; font-size: 14px;">
            ⏰ Code expires in 5 minutes<br>
            📧 Check your spam folder if you don't see it
        </p>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> DevTech Partners - All Rights Reserved</p>
    </div>
    
    <script>
    function loginWithGoogle() {
        // Google OAuth URL
        const clientId = 'YOUR_GOOGLE_CLIENT_ID'; // You'll need to get this from Google Console
        const redirectUri = window.location.origin + '/cereals-inventory/google-callback.php';
        const scope = 'email profile';
        
        const googleAuthUrl = `https://accounts.google.com/o/oauth2/v2/auth?` +
            `client_id=${clientId}&` +
            `redirect_uri=${encodeURIComponent(redirectUri)}&` +
            `response_type=code&` +
            `scope=${encodeURIComponent(scope)}&` +
            `access_type=offline`;
        
        // For now, show setup instructions
        alert('Google OAuth Setup Required!\n\n' +
              '1. Go to Google Cloud Console\n' +
              '2. Create OAuth 2.0 credentials\n' +
              '3. Add your Client ID to this code\n' +
              '4. Enable Google+ API\n\n' +
              'See setup guide for details.');
        
        // window.location.href = googleAuthUrl; // Uncomment when ready
    }
    </script>
</body>
</html>