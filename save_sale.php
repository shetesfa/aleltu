<?php
require_once 'config.php';
mysqli_set_charset($conn, "utf8mb4");
header('Content-Type: application/json');

// FIXED: branch_id was hardcoded to 1 and never actually saved with the
// transaction — every sale through this file used to be attributed to no
// branch at all (and would silently vanish from any branch-filtered report).
// Now resolved the same way save_transaction.php does it.
$user_id_for_branch = $_SESSION['user_id'] ?? intval($_POST['seller_id'] ?? 0);
$role_for_branch = $_SESSION['role'] ?? 'seller';
$branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : getCurrentBranchId($conn, $user_id_for_branch, $role_for_branch);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST data
    $total = floatval($_POST['total'] ?? 0);
    $paid = floatval($_POST['paid'] ?? 0);
    $change = floatval($_POST['change'] ?? 0);
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'cash');
    $seller_id = intval($_POST['seller_id'] ?? 1);
    $seller_name = mysqli_real_escape_string($conn, $_POST['seller_name'] ?? 'Seller');
    $items_json = $_POST['items'] ?? '[]';
    $items = json_decode($items_json, true);
    
    // Validate data
    if ($total <= 0 || empty($items) || $branch_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid transaction data']);
        exit();
    }
    
    // Start transaction
    mysqli_begin_transaction($conn);
    
    try {
        // 1. Save the transaction
        $transaction_query = "INSERT INTO transactions 
            (seller_id, seller_name, total_amount, paid_amount, change_amount, payment_method, transaction_date, branch_id) 
            VALUES ($seller_id, '$seller_name', $total, $paid, $change, '$payment_method', NOW(), $branch_id)";
        
        if (!mysqli_query($conn, $transaction_query)) {
            throw new Exception("Failed to save transaction: " . mysqli_error($conn));
        }
        
        $transaction_id = mysqli_insert_id($conn);
        
        // 2. Process each item - SIMPLIFIED VERSION
        foreach ($items as $item) {
            // FIXED: transaction_items.product_id is NOT NULL with no default —
            // this insert never included it and would fail every time
            // (caught by testing against the real schema). Matches the same
            // fallback save_transaction.php already uses.
            $item_id = intval($item['id'] ?? 0);
            $item_name = mysqli_real_escape_string($conn, $item['name'] ?? '');
            $quantity = floatval($item['quantity'] ?? 0);
            $price = floatval($item['price'] ?? 0);
            $subtotal = floatval($item['subtotal'] ?? 0);
            
            // Save transaction item
            $item_query = "INSERT INTO transaction_items 
                (transaction_id, product_id, product_name, quantity, unit_price, subtotal, branch_id) 
                VALUES ($transaction_id, $item_id, '$item_name', $quantity, $price, $subtotal, $branch_id)";
            
            if (!mysqli_query($conn, $item_query)) {
                throw new Exception("Failed to save transaction item: " . mysqli_error($conn));
            }
            
            // FIXED: this used to update ANY seller's row for this item name
            // with no branch filter, so a sale in one branch could silently
            // decrement another branch's stock if item names matched. Now
            // scoped to this branch's shared stock pool, same as
            // save_transaction.php / decrease_stock.php.
            $update_query = "UPDATE seller_inventory 
                            SET current_stock = GREATEST(0, current_stock - $quantity)
                            WHERE item_name = '$item_name' AND branch_id = $branch_id";
            
            $update_result = mysqli_query($conn, $update_query);
            
            if (!$update_result) {
                // If update fails, just log it but don't stop the transaction
                error_log("Stock update warning for $item_name: " . mysqli_error($conn));
            }
            
            // Check if any row was affected
            if (mysqli_affected_rows($conn) == 0) {
                // No stock was updated - try to insert a zero stock record
                $check = mysqli_query($conn, "SELECT id FROM seller_inventory WHERE item_name = '$item_name' AND branch_id = $branch_id LIMIT 1");
                if ($check && mysqli_num_rows($check) == 0) {
                    // seller_id = 0 = shared branch-level stock row, not
                    // owned by whichever seller happened to make this sale.
                    $insert = "INSERT INTO seller_inventory (seller_id, item_name, current_stock, unit, price, branch_id) 
                              VALUES (0, '$item_name', 0, 'pcs', $price, $branch_id)";
                    mysqli_query($conn, $insert);
                }
            }
        }
        
        // Commit transaction
        mysqli_commit($conn);
        
        echo json_encode([
            'success' => true,
            'transaction_id' => $transaction_id,
            'message' => 'Transaction saved successfully'
        ]);
        
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($conn);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

mysqli_close($conn);
?>