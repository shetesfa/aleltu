<?php
session_start();
require_once 'config.php';

set_time_limit(120);
ini_set('max_execution_time', 120);
mysqli_set_charset($conn, "utf8mb4");
header('Content-Type: application/json');

$user_id      = $_SESSION['user_id'] ?? 0;
$user_role    = $_SESSION['role'] ?? 'seller';
$seller_name  = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Seller';

if ($user_id == 0) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$total          = floatval($_POST['total'] ?? 0);
$paid           = floatval($_POST['paid'] ?? 0);
$change         = floatval($_POST['change'] ?? 0);
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method'] ?? 'cash');
$seller_id      = intval($_POST['seller_id'] ?? $user_id);
$seller_name_db = mysqli_real_escape_string($conn, $_POST['seller_name'] ?? $seller_name);
$items_json     = $_POST['items'] ?? '[]';
$items          = json_decode($items_json, true);
$branch_id      = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : getCurrentBranchId($conn, $user_id, $user_role);

if ($total <= 0 || empty($items) || $branch_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid transaction data']);
    exit();
}

mysqli_begin_transaction($conn);

try {
    date_default_timezone_set('Africa/Addis_Ababa');
    $transaction_date = date('Y-m-d H:i:s');

    // FIXED: Removed SHOW COLUMNS queries — branch_id column already confirmed to exist in DB
    $transaction_query = "INSERT INTO transactions 
        (seller_id, seller_name, total_amount, paid_amount, change_amount, payment_method, transaction_date, branch_id)
        VALUES ($seller_id, '$seller_name_db', $total, $paid, $change, '$payment_method', '$transaction_date', $branch_id)";

    if (!mysqli_query($conn, $transaction_query)) {
        throw new Exception("Failed to save transaction: " . mysqli_error($conn));
    }

    $transaction_id = mysqli_insert_id($conn);

    foreach ($items as $item) {
        $item_id   = intval($item['id'] ?? 0);
        $item_name = mysqli_real_escape_string($conn, $item['name'] ?? '');
        $quantity  = floatval($item['quantity'] ?? 0);
        $price     = floatval($item['price'] ?? 0);
        $subtotal  = floatval($item['subtotal'] ?? 0);

        // FIXED: Removed SHOW COLUMNS — branch_id column confirmed to exist
        $item_query = "INSERT INTO transaction_items 
            (transaction_id, product_id, product_name, quantity, unit_price, subtotal, branch_id)
            VALUES ($transaction_id, $item_id, '$item_name', $quantity, $price, $subtotal, $branch_id)";

        if (!mysqli_query($conn, $item_query)) {
            throw new Exception("Failed to save transaction item: " . mysqli_error($conn));
        }

        // Update stock — use GREATEST to avoid negative stock
        $update_query = "UPDATE seller_inventory
            SET current_stock = GREATEST(0, current_stock - $quantity)
            WHERE item_name = '$item_name' AND branch_id = $branch_id";
        mysqli_query($conn, $update_query);
    }

    mysqli_commit($conn);

    echo json_encode([
        'success'          => true,
        'transaction_id'   => $transaction_id,
        'message'          => 'Transaction saved successfully',
        'ethiopian_date'   => $transaction_date
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_close($conn);
?>
