<?php
// login_check.php - Handle login
session_start();
require_once 'config.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        // Simple password check (or use password_verify if hashed)
        if($password == $user['password'] || password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            if($user['role'] == 'seller') {
                header("Location: seller_pos.php");
            } else if($user['role'] == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: super_admin.php");
            }
            exit();
        } else {
            header("Location: index.php?error=Invalid password");
            exit();
        }
    } else {
        header("Location: index.php?error=User not found");
        exit();
    }
} else {
    header("Location: index.php");
    exit();
}
?>