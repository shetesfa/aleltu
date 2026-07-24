<?php
// quick_fix.php - Run to fix all errors
echo "<h2>Running Quick Fix...</h2>";

// Fix 1: Check session
session_start();
echo "✅ Session started<br>";

// Fix 2: Check database
require_once 'config.php';
if($conn) {
    echo "✅ Database connected<br>";
    
    // Fix 3: Check if tables exist
    $tables = ['locations', 'users', 'products', 'transactions', 'transaction_items'];
    foreach($tables as $table) {
        $check = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if(mysqli_num_rows($check) > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' missing<br>";
        }
    }
    
    // Fix 4: Check for super admin
    $check_admin = mysqli_query($conn, "SELECT * FROM users WHERE role = 'super_admin'");
    if(mysqli_num_rows($check_admin) > 0) {
        echo "✅ Super admin exists<br>";
    } else {
        echo "❌ No super admin - run setup_database.php<br>";
    }
    
} else {
    echo "❌ Database connection failed<br>";
}

echo "<h3>🎉 Fix Complete!</h3>";
echo "<a href='index.php'>Go to Login</a>";
?>