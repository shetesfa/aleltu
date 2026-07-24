<?php
session_start();
require_once 'config.php';

$total = floatval($_POST['total'] ?? 0);
$paid = floatval($_POST['paid'] ?? $total);
$change = floatval($_POST['change'] ?? 0);
$payment_method = $_POST['payment_method'] ?? 'cash';
$seller_id = $_POST['seller_id'] ?? 0;
$seller_name = $_POST['seller_name'] ?? '';
$items = json_decode($_POST['items'] ?? '[]', true);

// Start transaction
mysqli_begin_transaction($conn);

try {
    // 1. SAVE SALE
    $ethiopian_date = date('Y-m-d');
    
    $sale_query = "INSERT INTO sales (seller_id, seller_name, total_amount, amount_paid, change_amount, 
                   payment_method, sale_date, created_at) 
                   VALUES ('$seller_id', '$seller_name', '$total', '$paid', '$change', 
                   '$payment_method', '$ethiopian_date', NOW())";
                   
    $sale_result = mysqli_query($conn, $sale_query);
    $sale_id = mysqli_insert_id($conn);
    
    if (!$sale_result) {
        throw new Exception("Failed to save sale");
    }
    
    // 2. SAVE SALE ITEMS AND UPDATE INVENTORY
    foreach ($items as $item) {
        $item_name = mysqli_real_escape_string($conn, $item['name']);
        $quantity = floatval($item['quantity']);
        $price = floatval($item['price']);
        $subtotal = floatval($item['subtotal']);
        
        // Save sale item
        $item_query = "INSERT INTO sale_items (sale_id, item_name, quantity, unit_price, subtotal) 
                      VALUES ('$sale_id', '$item_name', '$quantity', '$price', '$subtotal')";
        mysqli_query($conn, $item_query);
        
        // UPDATE INVENTORY - Decrease stock
        $quantity_change = -abs($quantity);
        
        // Update inventory
        $update_query = "UPDATE seller_inventory 
                        SET current_stock = current_stock + ($quantity_change) 
                        WHERE name = '$item_name'";
        mysqli_query($conn, $update_query);
    }
    
    // Commit transaction
    mysqli_commit($conn);
    
    $response = [
        'success' => true,
        'sale_id' => $sale_id,
        'message' => 'Sale processed successfully'
    ];
    
} catch (Exception $e) {
    // Rollback on error
    mysqli_rollback($conn);
    
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response);

mysqli_close($conn);
?>