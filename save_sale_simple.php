<?php
session_start();
require_once 'config.php';

// Just log the sale, don't worry about errors
$total = $_POST['total'] ?? 0;
$seller_id = $_POST['seller_id'] ?? 0;

// Simple insert - ignore errors
$query = "INSERT INTO sales (seller_id, total_amount, created_at) 
          VALUES ('$seller_id', '$total', NOW())";
@mysqli_query($conn, $query);

echo "OK";
?>