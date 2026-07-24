<?php
// create_test_data.php - Create sample transactions
session_start();
$conn = mysqli_connect("localhost", "root", "", "aleltu_pos");

// Get a seller user
$seller = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, location_id FROM users WHERE role='seller' LIMIT 1"));

if($seller) {
    // Create test transaction
    $sql = "INSERT INTO transactions (location_id, user_id, total_amount, paid_amount, change_amount, payment_method) 
            VALUES ({$seller['location_id']}, {$seller['id']}, 100, 120, 20, 'Cash')";
    
    if(mysqli_query($conn, $sql)) {
        $transaction_id = mysqli_insert_id($conn);
        
        // Add items
        mysqli_query($conn, "INSERT INTO transaction_items (transaction_id, product_name, quantity, unit_price, subtotal) 
                             VALUES ($transaction_id, 'Coffee', 2, 25, 50)");
        mysqli_query($conn, "INSERT INTO transaction_items (transaction_id, product_name, quantity, unit_price, subtotal) 
                             VALUES ($transaction_id, 'Tea', 3, 15, 45)");
        mysqli_query($conn, "INSERT INTO transaction_items (transaction_id, product_name, quantity, unit_price, subtotal) 
                             VALUES ($transaction_id, 'Bread', 1, 10, 10)");
        
        echo "<h2 style='color:green;'>✅ Test transaction created!</h2>";
        echo "<p>Transaction ID: $transaction_id</p>";
        echo "<p>Total: 100 ETB</p>";
        echo "<a href='admin_dashboard.php'>Go to Dashboard</a>";
    }
} else {
    echo "<h2 style='color:red;'>❌ No seller found</h2>";
    echo "<p>Create a seller user first in User Management</p>";
}
?>