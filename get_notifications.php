<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "aleltu_pos");

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['notifications' => []]);
    exit();
}

$seller_id = $_SESSION['user_id'];
$query = "SELECT * FROM notifications WHERE user_id = '$seller_id' ORDER BY created_at DESC LIMIT 10";
$result = mysqli_query($conn, $query);

$notifications = [];
while($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}

echo json_encode(['notifications' => $notifications]);
?>