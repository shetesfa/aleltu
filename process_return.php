<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

mysqli_set_charset($conn, "utf8mb4");

// Get branch info
$user_branch = getUserBranch($conn, $_SESSION['user_id']);
$branch_id = getCurrentBranchId($conn, $_SESSION['user_id'], $_SESSION['role']);

$seller_id = intval($_POST['seller_id'] ?? 0);
$product_name = mysqli_real_escape_string($conn, $_POST['product_name'] ?? '');
$quantity = floatval($_POST['quantity'] ?? 0);
$reason = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');

if (!$seller_id || !$product_name || $quantity <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

// Get seller name
$seller_query = "SELECT full_name, username FROM users WHERE id = $seller_id";
$seller_result = mysqli_query($conn, $seller_query);
$seller_row = mysqli_fetch_assoc($seller_result);
$seller_name = $seller_row['full_name'] ?: $seller_row['username'] ?: 'Seller';

// FIXED: Stock is ONE shared pot per item per branch, not one pot per seller.
// We no longer filter by seller_id when checking/changing the stock number —
// seller_id is only used below to record WHO filed the return (for the log),
// never to pick which stock row to touch.
$check_stock = "SELECT COALESCE(SUM(current_stock), 0) as current_stock, MAX(unit) as unit
                FROM seller_inventory 
                WHERE item_name = '$product_name' AND branch_id = $branch_id";
$stock_result = mysqli_query($conn, $check_stock);
$stock_row = mysqli_fetch_assoc($stock_result);

if (!$stock_row || $stock_row['unit'] === null) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit();
}

if ($stock_row['current_stock'] < $quantity) {
    echo json_encode([
        'success' => false, 
        'error' => 'Not enough stock. Available: ' . $stock_row['current_stock'] . ' ' . $stock_row['unit']
    ]);
    exit();
}

mysqli_begin_transaction($conn);

try {
    // FIXED: subtract from the shared branch+item pot, not one seller's row.
    // (LIMIT 1 protects against old duplicate rows left over from before the
    // fix — it only ever touches one row per call, so it can never double-count.)
    $new_stock = $stock_row['current_stock'] - $quantity;
    $update_query = "UPDATE seller_inventory 
                     SET current_stock = GREATEST(0, current_stock - $quantity), last_updated = NOW() 
                     WHERE item_name = '$product_name' AND branch_id = $branch_id
                     ORDER BY current_stock DESC LIMIT 1";
    mysqli_query($conn, $update_query);
    
    // Ethiopian date/time
    $gregorian_date = date('Y-m-d H:i:s');
    
    // Simple Ethiopian date (you can replace with your function)
    $eth_year = date('Y') - 8;
    if (date('n') >= 9 || (date('n') == 9 && date('j') >= 11)) {
        $eth_year++;
    }
    $ethiopian_date = $eth_year . '-' . str_pad(date('n'), 2, '0', STR_PAD_LEFT) . '-' . str_pad(date('j'), 2, '0', STR_PAD_LEFT);
    
    // Ethiopian time (UTC+3)
    $utc_hour = gmdate('G');
    $minute = gmdate('i');
    $second = gmdate('s');
    $eth_hour = ($utc_hour + 3) % 24;
    $period = $eth_hour < 12 ? 'ጥዋት' : 'ከሰዓት';
    $eth_hour_12 = $eth_hour % 12;
    if ($eth_hour_12 == 0) $eth_hour_12 = 12;
    $ethiopian_time = sprintf('%d:%02d:%02d %s', $eth_hour_12, $minute, $second, $period);
    
    // Insert into returns table with branch
    $insert_return = "INSERT INTO product_returns 
                      (seller_id, seller_name, product_name, quantity, unit, reason, 
                       ethiopian_date, ethiopian_time, gregorian_date, branch_id) 
                      VALUES 
                      ($seller_id, '$seller_name', '$product_name', $quantity, '{$stock_row['unit']}', 
                       '$reason', '$ethiopian_date', '$ethiopian_time', '$gregorian_date', $branch_id)";
    mysqli_query($conn, $insert_return);
    
    // Insert into stock_logs (negative) with branch
    $insert_log = "INSERT INTO stock_logs 
                   (seller_id, seller_name, item_name, quantity, unit, source, added_by, date_added, notes, branch_id) 
                   VALUES 
                   ($seller_id, '$seller_name', '$product_name', -$quantity, '{$stock_row['unit']}', 
                    'return', '$seller_name', '$gregorian_date', 'Return: $reason', $branch_id)";
    mysqli_query($conn, $insert_log);
    
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true, 
        'message' => "$quantity {$stock_row['unit']} returned",
        'new_stock' => $new_stock
    ]);
    
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}

mysqli_close($conn);
?>