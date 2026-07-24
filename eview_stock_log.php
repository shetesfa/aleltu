<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "aleltu_pos");
mysqli_set_charset($conn, "utf8mb4");

$log_id = intval($_GET['id']);
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? "Admin";

// Mark log as reviewed
$query = "INSERT INTO stock_log_reviews (log_id, admin_id, admin_name, reviewed_at) 
          VALUES ($log_id, $admin_id, '$admin_name', NOW())";

if(mysqli_query($conn, $query)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
}

mysqli_close($conn);
?>