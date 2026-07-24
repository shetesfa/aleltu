<?php
// update_stock_batch.php
// NOTE: this file previously contained only a loose code fragment (no <?php
// tag, undefined variables, no request handling) — if it was ever requested
// directly, the web server would have printed its raw comments/code as plain
// text instead of running anything. It was not linked from any page. Rebuilt
// here as a complete, safe endpoint using the same pattern as
// save_transaction.php / decrease_stock.php, in case it's wired up later.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
mysqli_set_charset($conn, "utf8mb4");
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'seller';
$branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : getCurrentBranchId($conn, $user_id, $user_role);

// Expects items as JSON: [{"name": "...", "quantity": 1.5}, ...]
$items_json = $_POST['items'] ?? '[]';
$items = json_decode($items_json, true);

if ($branch_id <= 0 || empty($items) || !is_array($items)) {
    echo json_encode(['success' => false, 'error' => 'Invalid batch update data']);
    exit();
}

mysqli_begin_transaction($conn);

try {
    $updated = [];

    foreach ($items as $item) {
        $item_name       = mysqli_real_escape_string($conn, $item['name'] ?? '');
        $quantity_sold   = floatval($item['quantity'] ?? 0);

        if ($item_name === '' || $quantity_sold <= 0) {
            continue;
        }

        // Lock the row first so concurrent sales of the same item can't race
        // each other into an inconsistent stock count.
        $check_query = "SELECT current_stock FROM seller_inventory
                         WHERE item_name = '$item_name' AND branch_id = $branch_id
                         FOR UPDATE";
        $check_result = mysqli_query($conn, $check_query);

        if (!$check_result || mysqli_num_rows($check_result) == 0) {
            throw new Exception("Item not found in inventory: $item_name");
        }

        $current_stock = floatval(mysqli_fetch_assoc($check_result)['current_stock']);

        if ($current_stock < $quantity_sold) {
            throw new Exception("Insufficient stock for $item_name. Available: $current_stock");
        }

        // Absolute decrement, floor at zero — same safety pattern used in
        // save_transaction.php / decrease_stock.php.
        $update_query = "UPDATE seller_inventory
                          SET current_stock = GREATEST(0, current_stock - $quantity_sold), last_updated = NOW()
                          WHERE item_name = '$item_name' AND branch_id = $branch_id";

        if (!mysqli_query($conn, $update_query)) {
            throw new Exception("Failed to update stock for $item_name: " . mysqli_error($conn));
        }

        $updated[] = ['item_name' => $item_name, 'new_stock' => max(0, $current_stock - $quantity_sold)];
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true, 'updated' => $updated]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_close($conn);
?>
