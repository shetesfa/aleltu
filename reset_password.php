<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    
    // Reset password to 123456
    $query = "UPDATE users SET password = '123456' WHERE username = '$username'";
    if (mysqli_query($conn, $query)) {
        $message = "Password reset to '123456' for user: $username";
    } else {
        $error = "Error resetting password: " . mysqli_error($conn);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .container { max-width: 500px; margin: 0 auto; }
        input, button { padding: 10px; margin: 5px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <form method="POST">
            <input type="text" name="username" placeholder="Enter username" required>
            <button type="submit">Reset to 123456</button>
        </form>
        <?php if(isset($message)) echo "<p class='success'>$message</p>"; ?>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        <p><a href="index.php">Back to Login</a></p>
    </div>
</body>
</html>