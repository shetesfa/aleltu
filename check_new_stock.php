<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "aleltu_pos");
$seller_id = $_SESSION['user_id'];

// Check for new stock
$query = "SELECT COUNT(*) as count FROM stock_transfers WHERE seller_id = '$seller_id' AND status = 'pending'";
$result = mysqli_query($conn, $query);
$count = mysqli_fetch_assoc($result)['count'];

echo json_encode(['has_new_stock' => $count > 0, 'count' => $count]);
?>