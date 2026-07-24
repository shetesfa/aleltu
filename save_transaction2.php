<?php
session_start();
require_once 'config.php';
$total = floatval($_POST['total']);
$paid = floatval($_POST['paid']);
$change = floatval($_POST['change']);
$payment_method = $_POST['payment_method'];
$seller_id = $_POST['seller_id'];
$seller_name = $_POST['seller_name'];
$items = json_decode($_POST['items'], true);
$transaction_id = $_POST['transaction_id'] ?? 'TXN_' . time();

// Save transaction
$ethiopian_date = date('Y-m-d'); // Use actual Ethiopian date function

$transaction_query = "INSERT INTO sales (transaction_id, seller_id, seller_name, total_amount, amount_paid, change_amount, 
                     payment_method, sale_date, created_at) 
                     VALUES ('$transaction_id', '$seller_id', '$seller_name', '$total', '$paid', '$change', 
                     '$payment_method', '$ethiopian_date', NOW())";

$success = mysqli_query($conn, $transaction_query);
$sale_id = mysqli_insert_id($conn);

// Save sale items
if ($success) {
    foreach ($items as $item) {
        $item_name = mysqli_real_escape_string($conn, $item['name']);
        $quantity = floatval($item['quantity']);
        $price = floatval($item['price']);
        $subtotal = floatval($item['subtotal']);
        
        $item_query = "INSERT INTO sale_items (sale_id, item_name, quantity, unit_price, subtotal) 
                      VALUES ('$sale_id', '$item_name', '$quantity', '$price', '$subtotal')";
        mysqli_query($conn, $item_query);
    }
}

header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'transaction_id' => $transaction_id,
    'sale_id' => $sale_id,
    'message' => $success ? 'Transaction saved successfully' : 'Error saving transaction'
]);

mysqli_close($conn);
?>