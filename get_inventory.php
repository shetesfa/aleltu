<?php
require_once 'config.php';

// Get branch info if user is logged in
if(isset($_SESSION['user_id'])) {
    $user_branch = getUserBranch($conn, $_SESSION['user_id']);
    $branch_id = getCurrentBranchId($conn, $_SESSION['user_id'], $_SESSION['role'] ?? 'seller');
} else {
    // FIXED: this used to reference $_SESSION['user_id']/['role'] right after
    // confirming they weren't set, which threw undefined-index warnings on
    // every anonymous request.
    $branch_id = getCurrentBranchId($conn, null, 'seller'); // Default branch
}

// FIXED: this used to default to seller_id = 1 when no seller_id was passed,
// but all real stock is kept under the shared seller_id = 0 branch pool (see
// save_transaction.php / seller_receive_stock.php), so the old default always
// returned an empty list. If a specific seller_id is requested, honor it;
// otherwise return the branch's shared inventory.
if (isset($_GET['seller_id'])) {
    $seller_id = intval($_GET['seller_id']);
    $query = "SELECT * FROM seller_inventory WHERE seller_id = $seller_id AND branch_id = $branch_id ORDER BY item_name";
} else {
    $query = "SELECT * FROM seller_inventory WHERE branch_id = $branch_id ORDER BY item_name";
}
$result = mysqli_query($conn, $query);

$inventory = [];
while($row = mysqli_fetch_assoc($result)) {
    $inventory[] = $row;
}

echo json_encode([
    'success' => true,
    'inventory' => $inventory
]);

mysqli_close($conn);
?>