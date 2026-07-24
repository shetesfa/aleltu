<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "aleltu_pos");

if (!$conn) {
    die(json_encode(['error' => 'Database connection failed']));
}

$seller_id = $_SESSION['user_id'] ?? 0;

// Get unread count
$unread_query = "SELECT COUNT(*) as count FROM notifications WHERE user_id = '$seller_id' AND is_read = 0";
$unread_result = mysqli_query($conn, $unread_query);
$unread_count = 0;

if ($unread_result) {
    $unread_row = mysqli_fetch_assoc($unread_result);
    $unread_count = $unread_row['count'] ?? 0;
}

echo json_encode(['unread_count' => $unread_count]);
?>