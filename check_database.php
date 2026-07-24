<?php
// check_database.php - Check database setup
session_start();
$conn = mysqli_connect("localhost", "root", "", "aleltu_pos");

echo "<h2>Database Check</h2>";

// Check tables
$tables = ['locations', 'users', 'products', 'transactions', 'transaction_items'];
foreach($tables as $table) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM $table");
    if($result) {
        $row = mysqli_fetch_assoc($result);
        echo "✅ $table: " . $row['count'] . " records<br>";
    } else {
        echo "❌ $table: Table doesn't exist<br>";
    }
}

echo "<h3>Sample Data to Test:</h3>";
echo "<a href='create_test_data.php'>Create Test Transactions</a>";
?>