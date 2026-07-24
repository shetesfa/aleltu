<?php
// fix_admin_issues.php - Fix all admin dashboard issues
require_once 'config.php';

echo "<h2>Fixing Admin Dashboard Issues...</h2>";

// 1. Create missing data
echo "<h3>1. Creating Sample Data...</h3>";

// Check if we have transactions
$check = mysqli_query($conn, "SELECT COUNT(*) as count FROM transactions");
$row = mysqli_fetch_assoc($check);

if($row['count'] == 0) {
    echo "No transactions found. Creating sample data...<br>";
    
    // Create test transaction
    $sql = "INSERT INTO transactions (location_id, user_id, total_amount, paid_amount, change_amount, payment_method) 
            VALUES (1, 1, 250.50, 300.00, 49.50, 'Telebirr')";
    mysqli_query($conn, $sql);
    $tid = mysqli_insert_id($conn);
    
    // Add items
    mysqli_query($conn, "INSERT INTO transaction_items VALUES (NULL, $tid, 1, 'Coffee', 5, 25.00, 125.00)");
    mysqli_query($conn, "INSERT INTO transaction_items VALUES (NULL, $tid, 2, 'Tea', 3, 15.00, 45.00)");
    mysqli_query($conn, "INSERT INTO transaction_items VALUES (NULL, $tid, 3, 'Bread', 8, 10.00, 80.00)");
    
    echo "✅ Created sample transaction #$tid<br>";
} else {
    echo "✅ Already have {$row['count']} transactions<br>";
}

// 2. Check user count
echo "<h3>2. Checking Users...</h3>";
$users = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$urow = mysqli_fetch_assoc($users);
echo "Total users: {$urow['count']}<br>";

// 3. Check locations
echo "<h3>3. Checking Locations...</h3>";
$locs = mysqli_query($conn, "SELECT * FROM locations");
echo "Locations found: " . mysqli_num_rows($locs) . "<br>";

echo "<h2 style='color:green;'>✅ All checks completed!</h2>";
echo "<p><a href='admin_dashboard.php' style='background:#3498db;color:white;padding:15px 30px;text-decoration:none;border-radius:8px;font-size:18px;'>Go to Fixed Dashboard</a></p>";

echo "<h3>If still having issues:</h3>";
echo "<ol>
<li>Clear browser cache (Ctrl+F5)</li>
<li>Logout and login again</li>
<li>Run <a href='setup_database.php'>setup_database.php</a> again</li>
</ol>";
?>