<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    
    $sql = "UPDATE users SET full_name = '$full_name', email = '$email', phone = '$phone' WHERE id = $user_id";
    
    if ($conn->query($sql)) {
        $_SESSION['full_name'] = $full_name;
        $_SESSION['email'] = $email;
        $success = "Profile updated successfully!";
    } else {
        $error = "Error updating profile: " . $conn->error;
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current password from database
    $sql = "SELECT password FROM users WHERE id = $user_id";
    $result = $conn->query($sql);
    $user = $result->fetch_assoc();
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
                
                if ($conn->query($sql)) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Error changing password: " . $conn->error;
                }
            } else {
                $error = "Password must be at least 6 characters long!";
            }
        } else {
            $error = "New passwords do not match!";
        }
    } else {
        $error = "Current password is incorrect!";
    }
}

// Get user details
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();

$avatar_letter = strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - DevTech Partners</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .profile-header {
            background: white;
            padding: 40px;
            border-radius: 12px 12px 0 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .profile-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: bold;
            color: white;
            margin: 0 auto 20px auto;
            border: 5px solid #f0f0f0;
        }
        
        .profile-header h1 {
            margin: 0 0 8px 0;
            color: #2c3e50;
            font-size: 32px;
        }
        
        .profile-header p {
            margin: 0;
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .profile-body {
            background: white;
            padding: 0;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .tab {
            flex: 1;
            padding: 20px;
            text-align: center;
            background: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #7f8c8d;
            transition: all 0.3s;
            position: relative;
        }
        
        .tab:hover {
            background: #f8f9fa;
            color: #2c3e50;
        }
        
        .tab.active {
            color: #667eea;
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .tab-content {
            display: none;
            padding: 40px;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-section {
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            margin: 0 0 20px 0;
            color: #2c3e50;
            font-size: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .info-card h4 {
            margin: 0 0 10px 0;
            color: #2c3e50;
        }
        
        .info-card p {
            margin: 0;
            color: #7f8c8d;
            line-height: 1.6;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            background: #f8f9fa;
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <a href="index.php" class="back-link">← Back to Dashboard</a>
        
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar-large"><?php echo $avatar_letter; ?></div>
            <h1><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h1>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
        </div>
        
        <!-- Profile Body with Tabs -->
        <div class="profile-body">
            <div class="tabs">
                <button class="tab active" onclick="openTab(event, 'profile')">
                    👤 Profile Information
                </button>
                <button class="tab" onclick="openTab(event, 'security')">
                    🔒 Security
                </button>
                <button class="tab" onclick="openTab(event, 'account')">
                    ⚙️ Account Details
                </button>
            </div>
            
            <!-- Profile Information Tab -->
            <div id="profile" class="tab-content active">
                <form method="POST">
                    <div class="form-section">
                        <h3>Personal Information</h3>
                        
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+254 700 000 000">
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            💾 Save Changes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Security Tab -->
            <div id="security" class="tab-content">
                <form method="POST">
                    <div class="form-section">
                        <h3>Change Password</h3>
                        
                        <div class="info-card" style="margin-bottom: 20px;">
                            <h4>🔐 Password Requirements</h4>
                            <p>• Minimum 6 characters<br>
                               • Mix of uppercase and lowercase letters recommended<br>
                               • Include numbers and special characters for better security</p>
                        </div>
                        
                        <div class="form-group">
                            <label>Current Password *</label>
                            <input type="password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>New Password *</label>
                            <input type="password" name="new_password" required minlength="6">
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm New Password *</label>
                            <input type="password" name="confirm_password" required minlength="6">
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">
                            🔒 Change Password
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Account Details Tab -->
            <div id="account" class="tab-content">
                <div class="form-section">
                    <h3>Account Information</h3>
                    
                    <div class="info-card" style="margin-bottom: 15px;">
                        <h4>Username</h4>
                        <p><?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                    
                    <div class="info-card" style="margin-bottom: 15px;">
                        <h4>User ID</h4>
                        <p>#<?php echo $user['id']; ?></p>
                    </div>
                    
                    <div class="info-card" style="margin-bottom: 15px;">
                        <h4>Account Created</h4>
                        <p><?php echo isset($user['created_at']) ? date('F d, Y', strtotime($user['created_at'])) : 'N/A'; ?></p>
                    </div>
                    
                    <div class="info-card" style="margin-bottom: 15px;">
                        <h4>Last Login</h4>
                        <p><?php echo isset($user['last_login']) ? date('F d, Y g:i A', strtotime($user['last_login'])) : 'N/A'; ?></p>
                    </div>
                    
                    <div class="info-card">
                        <h4>Account Role</h4>
                        <p>Administrator</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function openTab(evt, tabName) {
            // Hide all tab contents
            const tabContents = document.getElementsByClassName('tab-content');
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove('active');
            }
            
            // Remove active class from all tabs
            const tabs = document.getElementsByClassName('tab');
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            
            // Show current tab and mark as active
            document.getElementById(tabName).classList.add('active');
            evt.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>