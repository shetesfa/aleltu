<?php
// save_transaction_simple.php
session_start();
$conn = mysqli_connect("localhost", "root", "", "aleltu_pos");

if (!$conn) {
    die("Database connection failed");
}

// Get data
$total = floatval($_POST['total']);
$seller_id = intval($_POST['seller_id']);
$items_json = $_POST['items'];
$items = json_decode($items_json, true);

// Save to transactions table
$query = "INSERT INTO transactions (total_amount, paid_amount, change_amount, payment_method, seller_name) 
          VALUES ($total, $total, 0, 'Cash', 'Seller_$seller_id')";
mysqli_query($conn, $query);

$transaction_id = mysqli_insert_id($conn);

// Save items
foreach ($items as $item) {
    $name = mysqli_real_escape_string($conn, $item['name']);
    $qty = floatval($item['quantity']);
    $price = floatval($item['price']);
    $subtotal = $qty * $price;
    
    $item_query = "INSERT INTO transaction_items 
                  (transaction_id, item_name, quantity, unit_price, total_price) 
                  VALUES ($transaction_id, '$name', $qty, $price, $subtotal)";
    mysqli_query($conn, $item_query);
    
    // Update inventory
    $update_query = "UPDATE seller_inventory 
                    SET current_stock = current_stock - $qty 
                    WHERE seller_id = $seller_id 
                    AND item_name = '$name'";
    mysqli_query($conn, $update_query);
}

// Redirect back to POS with success message
header("Location: seller_pos.php?success=1");
exit;
?>