<?php
require_once 'config.php';
if (!$conn) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

mysqli_set_charset($conn, "utf8mb4");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'error' => 'Not authenticated']));
}

$seller_id = $_SESSION['user_id'];

// Get branch info
$user_branch = getUserBranch($conn, $seller_id);
$branch_id = getCurrentBranchId($conn, $seller_id, $_SESSION['role']);

// Get POST data
$item_name = isset($_POST['item_name']) ? mysqli_real_escape_string($conn, $_POST['item_name']) : '';
$quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : 'decrease';

if (empty($item_name) || $quantity <= 0) {
    die(json_encode(['success' => false, 'error' => 'Invalid item name or quantity']));
}

// FIXED: Stock is ONE shared pot per item per branch, not one pot per seller.
// Check total stock across the branch (not just this seller's row).
$check_query = "SELECT COALESCE(SUM(current_stock), 0) as current_stock FROM seller_inventory WHERE item_name = '$item_name' AND branch_id = $branch_id";

$check_result = mysqli_query($conn, $check_query);

if (!$check_result || mysqli_num_rows($check_result) == 0) {
    die(json_encode(['success' => false, 'error' => 'Item not found in inventory']));
}

$current_stock = mysqli_fetch_assoc($check_result)['current_stock'];

// Validate if we have enough stock
if ($current_stock < $quantity) {
    die(json_encode(['success' => false, 'error' => 'Insufficient stock. Available: ' . $current_stock]));
}

// DECREASE stock (subtract from current stock)
$new_stock = $current_stock - $quantity;

// FIXED: update the shared row for this item+branch (LIMIT 1 so it can only
// ever touch one row, even if an old duplicate row still exists somewhere).
$update_query = "UPDATE seller_inventory SET current_stock = GREATEST(0, current_stock - $quantity) 
                 WHERE item_name = '$item_name' AND branch_id = $branch_id
                 ORDER BY current_stock DESC LIMIT 1";

if (mysqli_query($conn, $update_query)) {
    // Also log this sale in stock_logs table with branch
    $ethiopian_date = date('Y-m-d'); // You might want to use your Ethiopian date function
    $log_query = "INSERT INTO stock_logs (seller_id, seller_name, item_name, quantity, unit, source, added_by, date_added, notes, branch_id) 
                  VALUES ('$seller_id', '" . $_SESSION['full_name'] . "', '$item_name', -$quantity, 'pcs', 'sale', '" . $_SESSION['full_name'] . "', '$ethiopian_date', 'Sale transaction', $branch_id)";
    mysqli_query($conn, $log_query);
    
    echo json_encode(['success' => true, 'message' => 'Stock decreased successfully', 'new_stock' => $new_stock]);
} else {
    echo json_encode(['success' => false, 'error' => 'Update failed: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>