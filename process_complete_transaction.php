<?php
require_once 'config.php';
mysqli_set_charset($conn, "utf8mb4");

// Get all data
$total = floatval($_POST['total']);
$paid = floatval($_POST['paid']);
$change = floatval($_POST['change']);
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
$seller_id = intval($_POST['seller_id']);
$seller_name = mysqli_real_escape_string($conn, $_POST['seller_name']);
$items = json_decode($_POST['items'], true);

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. Save sale transaction
    $date = date('Y-m-d');
    $sale_query = "INSERT INTO sales (seller_id, seller_name, total_amount, paid_amount, change_amount, 
                   payment_method, sale_date, items_sold) 
                   VALUES ('$seller_id', '$seller_name', '$total', '$paid', '$change', 
                   '$payment_method', '$date', '" . mysqli_real_escape_string($conn, json_encode($items)) . "')";
    
    if (!mysqli_query($conn, $sale_query)) {
        throw new Exception('Failed to save sale: ' . mysqli_error($conn));
    }
    
    $sale_id = mysqli_insert_id($conn);
    
    // 2. Update stock for each item
    foreach ($items as $item) {
        $item_name = mysqli_real_escape_string($conn, $item['name']);
        $quantity = floatval($item['quantity']);
        
        // Check table structure
        $columns_result = mysqli_query($conn, "SHOW COLUMNS FROM seller_inventory");
        $item_column = 'name';
        while($col = mysqli_fetch_assoc($columns_result)) {
            if (in_array($col['Field'], ['item_name', 'name', 'product_name'])) {
                $item_column = $col['Field'];
            }
        }
        
        // Update stock - ONE TIME ONLY
        $update_query = "UPDATE seller_inventory 
                        SET current_stock = GREATEST(0, current_stock - $quantity) 
                        WHERE seller_id = $seller_id AND $item_column = '$item_name'";
        
        mysqli_query($conn, $update_query);
        
        // Save sale item
        $price = floatval($item['price']);
        $subtotal = floatval($item['subtotal']);
        
        $item_query = "INSERT INTO sale_items (transaction_id, item_name, quantity, unit_price, subtotal, seller_id)
                      VALUES ('$sale_id', '$item_name', '$quantity', '$price', '$subtotal', '$seller_id')";
        mysqli_query($conn, $item_query);
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode(['success' => true, 'message' => 'Transaction completed']);
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_close($conn);
?>