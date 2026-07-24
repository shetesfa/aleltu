<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "aleltu_pos");

$seller_id = isset($_GET['seller_id']) ? intval($_GET['seller_id']) : 1;

// Get all received stock for this seller
$query = "SELECT st.*, u.username as admin_name 
          FROM stock_transfers st 
          LEFT JOIN users u ON st.transferred_by = u.username 
          WHERE st.seller_id = '$seller_id' 
          ORDER BY st.transferred_at DESC 
          LIMIT 20";

$result = mysqli_query($conn, $query);

$stock = [];
if(mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        $stock[] = [
            'item_name' => $row['product_name'],
            'quantity' => $row['quantity'],
            'unit' => 'pcs',
            'transferred_by' => $row['admin_name'] ?: $row['transferred_by'],
            'transferred_at' => date('M d, Y h:i A', strtotime($row['transferred_at']))
        ];
    }
}

echo json_encode([
    'success' => true,
    'stock' => $stock
]);

mysqli_close($conn);
?>