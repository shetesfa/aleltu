<?php
require_once 'config.php';
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$seller_id = $_SESSION['user_id'];
$notification_id = mysqli_real_escape_string($conn, $_GET['id']);
mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE id = '$notification_id' AND user_id = '$seller_id'");
echo json_encode(['success' => true]);
?>