<?php
// add_bulk_stock.php
require_once 'config.php';
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

// Get current user from session
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user = $_SESSION['full_name'] ?? $_SESSION['username'] ?? "Seller";
$seller_id = $_SESSION['user_id'];

// Check what columns exist in seller_inventory table
$check_table_query = "SHOW COLUMNS FROM seller_inventory";
$table_result = mysqli_query($conn, $check_table_query);
$columns = [];
while($col = mysqli_fetch_assoc($table_result)) {
    $columns[] = $col['Field'];
}

// Determine item column name
if (in_array('item_name', $columns)) {
    $item_column = 'item_name';
} elseif (in_array('name', $columns)) {
    $item_column = 'name';
} elseif (in_array('product_name', $columns)) {
    $item_column = 'product_name';
} else {
    $item_column = 'name';
}

// Process bulk stock addition
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $source = mysqli_real_escape_string($conn, $_POST['source']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    $items = $_POST['item_name'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $units = $_POST['unit'] ?? [];
    
    $success_count = 0;
    $error_count = 0;
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        for ($i = 0; $i < count($items); $i++) {
            if (!empty($items[$i]) && !empty($quantities[$i])) {
                $item_name = mysqli_real_escape_string($conn, $items[$i]);
                $quantity = floatval($quantities[$i]);
                $unit = mysqli_real_escape_string($conn, $units[$i] ?? 'pcs');
                
                // Check if item exists in seller inventory
                $check_query = "SELECT * FROM seller_inventory WHERE seller_id = $seller_id AND $item_column = '$item_name'";
                $check_result = mysqli_query($conn, $check_query);
                
                // Get price from products table
                $get_price_query = "SELECT unit_price FROM products WHERE name = '$item_name' LIMIT 1";
                $price_result = mysqli_query($conn, $get_price_query);
                $price = 0;
                if ($price_row = mysqli_fetch_assoc($price_result)) {
                    $price = $price_row['unit_price'];
                }
                
                if (mysqli_num_rows($check_result) > 0) {
                    // Update existing stock using ON DUPLICATE KEY UPDATE
                    $update_stock = "INSERT INTO seller_inventory (seller_id, $item_column, current_stock, unit, price) 
                                    VALUES ($seller_id, '$item_name', $quantity, '$unit', $price)
                                    ON DUPLICATE KEY UPDATE current_stock = current_stock + VALUES(current_stock),
                                    unit = VALUES(unit),
                                    price = VALUES(price)";
                } else {
                    // Insert new item
                    $update_stock = "INSERT INTO seller_inventory (seller_id, $item_column, current_stock, unit, price) 
                                    VALUES ($seller_id, '$item_name', $quantity, '$unit', $price)";
                }
                
                if (mysqli_query($conn, $update_stock)) {
                    // Log the transaction
                    $log_query = "INSERT INTO inventory_transactions (transaction_type, item_name, quantity, notes, created_by) 
                                 VALUES ('$source', '$item_name', $quantity, '$notes', '$current_user')";
                    mysqli_query($conn, $log_query);
                    
                    $success_count++;
                } else {
                    $error_count++;
                }
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true,
            'message' => "Successfully added $success_count items. Errors: $error_count",
            'added' => $success_count,
            'errors' => $error_count
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        mysqli_rollback($conn);
        
        echo json_encode([
            'success' => false,
            'message' => "Error: " . $e->getMessage()
        ]);
    }
    
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add Bulk Stock</title>
</head>
<body>
    <h1>Bulk Stock Addition</h1>
    <p>This page handles bulk stock addition. Use the form in the main POS.</p>
</body>
</html>