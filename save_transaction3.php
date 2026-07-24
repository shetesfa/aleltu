<?php
session_start();
require_once 'config.php';
if (!$conn) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}
mysqli_set_charset($conn, "utf8mb4");

// Get POST data
$total = floatval($_POST['total'] ?? 0);
$paid = floatval($_POST['paid'] ?? 0);
$change = floatval($_POST['change'] ?? 0);
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'cash');
$seller_id = intval($_POST['seller_id'] ?? 0);
$seller_name = mysqli_real_escape_string($conn, $_POST['seller_name'] ?? '');
$items_json = $_POST['items'] ?? '[]';
$items = json_decode($items_json, true);

if ($total <= 0 || $seller_id <= 0) {
    die(json_encode(['success' => false, 'error' => 'Invalid transaction data']));
}

// Save transaction
$ethiopian_date = date('Y-m-d');
$transaction_query = "INSERT INTO sales (seller_id, seller_name, total_amount, paid_amount, change_amount, 
                     payment_method, sale_date, items_sold) 
                     VALUES ('$seller_id', '$seller_name', '$total', '$paid', '$change', 
                     '$payment_method', '$ethiopian_date', '" . mysqli_real_escape_string($conn, $items_json) . "')";

if (mysqli_query($conn, $transaction_query)) {
    // Also save individual items for reporting
    $transaction_id = mysqli_insert_id($conn);
    
    foreach ($items as $item) {
        $item_name = mysqli_real_escape_string($conn, $item['name'] ?? '');
        $quantity = floatval($item['quantity'] ?? 0);
        $price = floatval($item['price'] ?? 0);
        $subtotal = floatval($item['subtotal'] ?? 0);
        
        if ($item_name && $quantity > 0) {
            $item_query = "INSERT INTO sale_items (transaction_id, item_name, quantity, unit_price, subtotal, seller_id)
                          VALUES ('$transaction_id', '$item_name', '$quantity', '$price', '$subtotal', '$seller_id')";
            mysqli_query($conn, $item_query);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Transaction saved', 'transaction_id' => $transaction_id]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save transaction: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>