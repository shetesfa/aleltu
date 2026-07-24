<?php
// index.php - Login Page (FIXED VERSION)

require_once 'config.php';

// Check if user is already logged in
if(isset($_SESSION['user_id'])) {
    // Redirect based on role
    if($_SESSION['role'] == 'seller') {
        header("Location: seller_pos.php");
    } else if($_SESSION['role'] == 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: super_admin.php");
    }
    exit();
}

$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE username = '$username' AND is_active = 1";
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if(password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['location_id'] = $user['location_id'];
            
            // Redirect based on role
            if($user['role'] == 'seller') {
                header("Location: seller_pos.php");
            } else if($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: super_admin.php");
            }
            exit();
        } else {
            $error = "Invalid password!";
        }
    } else {
        $error = "User not found or inactive!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aleltu POS - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 900px;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
        }
        
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, #4a00e0 0%, #8e2de2 100%);
            color: white;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-right {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .brand-logo {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 10px;
            color: white;
        }
        
        .brand-tagline {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 40px;
        }
        
        .feature-list {
            list-style: none;
            margin-top: 30px;
        }
        
        .feature-list li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            font-size: 15px;
        }
        
        .feature-list i {
            margin-right: 10px;
            color: #8e2de2;
            background: white;
            padding: 5px;
            border-radius: 50%;
            font-size: 14px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .login-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .login-subtitle {
            color: #666;
            font-size: 15px;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e1e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f8fafc;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4f46e5;
            background: white;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 42px;
            color: #94a3b8;
            font-size: 18px;
        }
        
        .error-message {
            background: #fee;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #dc2626;
            font-size: 14px;
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .role-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            background: #f8fafc;
            padding: 10px;
            border-radius: 10px;
        }
        
        .role-option {
            flex: 1;
            text-align: center;
            padding: 12px;
            border: 2px solid #e1e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .role-option:hover {
            border-color: #4f46e5;
            background: white;
        }
        
        .role-option.active {
            background: #4f46e5;
            color: white;
            border-color: #4f46e5;
        }
        
        .demo-credentials {
            margin-top: 25px;
            padding: 15px;
            background: #f0f9ff;
            border-radius: 10px;
            border-left: 4px solid #0ea5e9;
        }
        
        .demo-title {
            font-weight: 600;
            color: #0369a1;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .demo-list {
            list-style: none;
            font-size: 13px;
            color: #0c4a6e;
        }
        
        .demo-list li {
            margin-bottom: 5px;
            padding: 5px 0;
            border-bottom: 1px dashed #bae6fd;
        }
        
        .demo-list li:last-child {
            border-bottom: none;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 42px;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column;
                max-width: 500px;
            }
            
            .login-left, .login-right {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-left">
            <div class="brand-logo">
                <i class="fas fa-store"></i> ALELTU POS
            </div>
            <p class="brand-tagline">
                Professional Point of Sale System for Retail Business
            </p>
            
            <ul class="feature-list">
                <li><i class="fas fa-check"></i> Fast & Easy Transactions</li>
                <li><i class="fas fa-check"></i> Inventory Management</li>
                <li><i class="fas fa-check"></i> Sales Reports & Analytics</li>
                <li><i class="fas fa-check"></i> Multi-User Support</li>
                <li><i class="fas fa-check"></i> Cloud Backup</li>
            </ul>
        </div>
        
        <div class="login-right">
            <div class="login-header">
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to your account to continue</p>
            </div>
            
            <?php if($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" 
                           name="username" 
                           class="form-input" 
                           placeholder="Enter your username"
                           required
                           autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" 
                           name="password" 
                           id="passwordField"
                           class="form-input" 
                           placeholder="Enter your password"
                           required>
                    <button type="button" class="password-toggle" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login to Dashboard
                </button>
            </form>
            
            <div class="demo-credentials">
                <div class="demo-title">Demo Accounts</div>
                <ul class="demo-list">
                    <li><strong>Super Admin:</strong> superadmin / 123456</li>
                    <li><strong>Admin:</strong> admin / admin123</li>
                    <li><strong>Seller:</strong> seller / seller123</li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('passwordField');
            const icon = this.querySelector('i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Auto-focus username field
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="username"]').focus();
        });
        
        // Role selector functionality
        document.querySelectorAll('.role-option').forEach(option => {
            option.addEventListener('click', function() {
                // Remove active class from all
                document.querySelectorAll('.role-option').forEach(opt => {
                    opt.classList.remove('active');
                });
                // Add active class to clicked
                this.classList.add('active');
                
                // Auto-fill demo credentials
                const role = this.dataset.role;
                const demoAccounts = {
                    'superadmin': {user: 'superadmin', pass: '123456'},
                    'admin': {user: 'admin', pass: 'admin123'},
                    'seller': {user: 'seller', pass: 'seller123'}
                };
                
                if (demoAccounts[role]) {
                    document.querySelector('input[name="username"]').value = demoAccounts[role].user;
                    document.getElementById('passwordField').value = demoAccounts[role].pass;
                }
            });
        });
    </script>
</body>
</html>