<?php
// delete_transaction.php - Delete transaction (Admin Only)
require_once 'config.php';
require_once 'includes/auth_check.php';

// Only admin and super_admin allowed
if($_SESSION['role'] == 'seller') {
    header("Location: seller_pos.php");
    exit();
}

if(isset($_GET['id'])) {
    $transaction_id = intval($_GET['id']);
    
    // Get transaction details for logging
    $query = "SELECT * FROM transactions WHERE id = $transaction_id";
    $result = mysqli_query($conn, $query);
    
    if(mysqli_num_rows($result) > 0) {
        // Delete transaction (cascade will delete items)
        mysqli_query($conn, "DELETE FROM transactions WHERE id = $transaction_id");
        
        // Log the deletion
        $user_id = $_SESSION['user_id'];
        $log_query = "INSERT INTO backup_logs (backup_time, filename, status) 
                      VALUES (NOW(), 'Deleted Transaction #$transaction_id by User #$user_id', 'DELETED')";
        mysqli_query($conn, $log_query);
        
        header("Location: all_transactions.php?msg=deleted");
    } else {
        header("Location: all_transactions.php?error=notfound");
    }
} else {
    header("Location: all_transactions.php");
}

mysqli_close($conn);
exit();
?>