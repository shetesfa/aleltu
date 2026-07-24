<?php
session_start();
require_once 'config.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === "" || $password === "") {
        $error = "Please enter username and password!";
    } else {
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) === 1) {
                mysqli_stmt_bind_result($stmt, $id, $uname, $dbpass, $role);
                mysqli_stmt_fetch($stmt);

                if ($password === $dbpass) { // plain text for now
                    $_SESSION['user_id'] = $id;
                    $_SESSION['username'] = $uname;
                    $_SESSION['role'] = $role;

                    // Redirect safely
                    $role = strtolower($role);
                    if ($role === "super_admin" || $role === "superadmin") {
                        header("Location: super_admin.php");
                    } elseif ($role === "admin") {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: seller_pos.php");
                    }
                    exit;
                } else {
                    $error = "Wrong password!";
                }
            } else {
                $error = "User not found!";
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = "SQL statement error!";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
<h2>Login</h2>
<?php if ($error !== "") echo "<p style='color:red;'>$error</p>"; ?>
<form method="post">
    Username: <input type="text" name="username"><br><br>
    Password: <input type="password" name="password"><br><br>
    <button type="submit">Login</button>
</form>
</body>
</html>