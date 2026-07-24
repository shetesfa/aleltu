<?php
// export_today.php - Export today's sales to Excel
require_once 'config.php';
require_once 'includes/auth_check.php';

if($_SESSION['role'] != 'seller') {
    header("Location: index.php");
    exit();
}

$location_id = $_SESSION['location_id'];
$today = date('Y-m-d');

// Set headers for Excel file
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="sales_report_' . $today . '_place' . $location_id . '.xls"');

// Get today's data
$query = "SELECT t.*, u.full_name as seller 
          FROM transactions t 
          JOIN users u ON t.user_id = u.id 
          WHERE t.location_id = $location_id 
          AND DATE(t.transaction_date) = '$today'
          ORDER BY t.transaction_date DESC";
$result = mysqli_query($conn, $query);
?>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpg" href="image\photo_2026-01-12_07-44-10.jpg">
</head>
<body>
    <table border="1">
        <tr>
            <th colspan="7" style="background:#3498db;color:white;font-size:18px;padding:15px;">
                Today's Sales Report - Place <?php echo $location_id; ?> - <?php echo $today; ?>
            </th>
        </tr>
        <tr style="background:#2c3e50;color:white;">
            <th>Time</th>
            <th>Transaction ID</th>
            <th>Seller</th>
            <th>Total Amount</th>
            <th>Paid Amount</th>
            <th>Change</th>
            <th>Payment Method</th>
        </tr>
        <?php 
        $total_sales = 0;
        while($row = mysqli_fetch_assoc($result)): 
            $total_sales += $row['total_amount'];
        ?>
        <tr>
            <td><?php echo date('H:i:s', strtotime($row['transaction_date'])); ?></td>
            <td>#<?php echo $row['id']; ?></td>
            <td><?php echo $row['seller']; ?></td>
            <td><?php echo number_format($row['total_amount'], 2); ?> ETB</td>
            <td><?php echo number_format($row['paid_amount'], 2); ?> ETB</td>
            <td><?php echo number_format($row['change_amount'], 2); ?> ETB</td>
            <td><?php echo $row['payment_method']; ?></td>
        </tr>
        <?php endwhile; ?>
        <tr style="background:#27ae60;color:white;font-weight:bold;">
            <td colspan="3">TOTAL SALES</td>
            <td colspan="4"><?php echo number_format($total_sales, 2); ?> ETB</td>
        </tr>
    </table>
</body>
</html>
<?php mysqli_close($conn); ?>