<?php
// export_today_items.php - Export today's filtered items
require_once 'config.php';
require_once 'includes/auth_check.php';

if($_SESSION['role'] != 'seller') {
    header("Location: index.php");
    exit();
}

$location_id = $_SESSION['location_id'];
$today = date('Y-m-d');
$item_filter = isset($_GET['item']) ? mysqli_real_escape_string($conn, $_GET['item']) : '';

// Build query
$where = "WHERE t.location_id = $location_id AND DATE(t.transaction_date) = '$today'";
if(!empty($item_filter)) {
    $where .= " AND ti.product_name LIKE '%$item_filter%'";
}

// Get data
$query = "SELECT 
          ti.product_name,
          SUM(ti.quantity) as total_qty,
          SUM(ti.subtotal) as total_amount,
          COUNT(DISTINCT ti.transaction_id) as transaction_count
          FROM transaction_items ti
          JOIN transactions t ON ti.transaction_id = t.id
          $where
          GROUP BY ti.product_name
          ORDER BY total_amount DESC";
$result = mysqli_query($conn, $query);

// Set headers for Excel file
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="item_report_' . $today . '_' . ($item_filter ?: 'all') . '.xls"');
?>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
    <table border="1">
        <tr>
            <th colspan="5" style="background:#27ae60;color:white;font-size:18px;padding:15px;">
                Today's Item Report - Place <?php echo $location_id; ?> - <?php echo $today; ?>
            </th>
        </tr>
        <?php if(!empty($item_filter)): ?>
        <tr>
            <td colspan="5" style="background:#fff3cd;">
                <strong>Filter:</strong> <?php echo htmlspecialchars($item_filter); ?>
            </td>
        </tr>
        <?php endif; ?>
        <tr style="background:#2c3e50;color:white;">
            <th>Item Name</th>
            <th>Quantity Sold</th>
            <th>Total Amount</th>
            <th>Sales Count</th>
            <th>Average per Sale</th>
        </tr>
        <?php 
        $total_qty = 0;
        $total_amount = 0;
        while($row = mysqli_fetch_assoc($result)): 
            $total_qty += $row['total_qty'];
            $total_amount += $row['total_amount'];
            $average = $row['transaction_count'] > 0 ? $row['total_amount'] / $row['transaction_count'] : 0;
        ?>
        <tr>
            <td><?php echo $row['product_name']; ?></td>
            <td><?php echo $row['total_qty']; ?></td>
            <td><?php echo number_format($row['total_amount'], 2); ?> ETB</td>
            <td><?php echo $row['transaction_count']; ?></td>
            <td><?php echo number_format($average, 2); ?> ETB</td>
        </tr>
        <?php endwhile; ?>
        <tr style="background:#27ae60;color:white;font-weight:bold;">
            <td>TOTAL</td>
            <td><?php echo $total_qty; ?></td>
            <td><?php echo number_format($total_amount, 2); ?> ETB</td>
            <td colspan="2"></td>
        </tr>
    </table>
</body>
</html>
<?php mysqli_close($conn); ?>