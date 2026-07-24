<?php
// filter_today.php - Filter today's items (SELLER ONLY)
require_once 'config.php';
require_once 'includes/auth_check.php';

// Only sellers allowed
if($_SESSION['role'] != 'seller') {
    header("Location: index.php");
    exit();
}

$location_id = $_SESSION['location_id'];
$today = date('Y-m-d');

// Filter by item if provided
$item_filter = isset($_GET['item']) ? mysqli_real_escape_string($conn, $_GET['item']) : '';

// Get today's item sales
$where = "WHERE t.location_id = $location_id AND DATE(t.transaction_date) = '$today'";
if(!empty($item_filter)) {
    $where .= " AND ti.product_name LIKE '%$item_filter%'";
}

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

// Get all unique items for today
$items_query = "SELECT DISTINCT ti.product_name 
                FROM transaction_items ti
                JOIN transactions t ON ti.transaction_id = t.id
                WHERE t.location_id = $location_id AND DATE(t.transaction_date) = '$today'
                ORDER BY ti.product_name";
$items_result = mysqli_query($conn, $items_query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Filter Today's Items</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial; background: #f5f5f5; padding: 20px; }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .back-btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .filter-form {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .search-box {
            display: flex;
            gap: 10px;
        }
        
        .search-box input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .search-box button {
            background: #27ae60;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .item-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 15px 0;
        }
        
        .item-tag {
            background: #e3f2fd;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .item-tag:hover {
            background: #bbdefb;
        }
        
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        th {
            background: #27ae60;
            color: white;
            padding: 15px;
            text-align: left;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        
        .total-row {
            background: #2c3e50;
            color: white;
            font-weight: bold;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 10px;
            color: #7f8c8d;
        }
        
        .date-info {
            background: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <a href="seller_pos.php" class="back-btn">← Back to POS</a>
    <a href="today_report.php" style="background:#f39c12;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;">📊 Full Report</a>
    
    <div class="header">
        <h1>🔍 Filter Today's Items</h1>
        <p>Place <?php echo $location_id; ?> - <?php echo date('F j, Y'); ?></p>
        <div class="date-info">
            <strong>Note:</strong> You can only filter today's sales data.
        </div>
    </div>
    
    <!-- Filter Form -->
    <form method="GET" action="" class="filter-form">
        <h3>Search Items:</h3>
        <div class="search-box">
            <input type="text" name="item" value="<?php echo htmlspecialchars($item_filter); ?>" 
                   placeholder="Enter item name (e.g., Coffee, Tea)">
            <button type="submit">🔍 Search</button>
            <a href="filter_today.php" style="padding:12px 20px;background:#e74c3c;color:white;text-decoration:none;border-radius:5px;">Clear</a>
        </div>
        
        <?php if(mysqli_num_rows($items_result) > 0): ?>
            <div style="margin-top: 15px;">
                <p><strong>Quick filter:</strong> Click any item below</p>
                <div class="item-list">
                    <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                        <a href="?item=<?php echo urlencode($item['product_name']); ?>" 
                           class="item-tag">
                            <?php echo $item['product_name']; ?>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </form>
    
    <!-- Results -->
    <?php if(!empty($item_filter)): ?>
        <h3>Results for "<?php echo htmlspecialchars($item_filter); ?>"</h3>
    <?php else: ?>
        <h3>All Items Sold Today</h3>
    <?php endif; ?>
    
    <?php if(mysqli_num_rows($result) > 0): ?>
        <?php
        $total_qty = 0;
        $total_amount = 0;
        ?>
        <table>
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Quantity Sold</th>
                    <th>Total Amount</th>
                    <th>Sales Count</th>
                    <th>Average per Sale</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($result)): 
                    $total_qty += $row['total_qty'];
                    $total_amount += $row['total_amount'];
                    $average = $row['transaction_count'] > 0 ? $row['total_amount'] / $row['transaction_count'] : 0;
                ?>
                <tr>
                    <td><strong><?php echo $row['product_name']; ?></strong></td>
                    <td><?php echo $row['total_qty']; ?> units</td>
                    <td style="font-weight:bold;color:#27ae60;"><?php echo number_format($row['total_amount'], 2); ?> ETB</td>
                    <td><?php echo $row['transaction_count']; ?> sales</td>
                    <td><?php echo number_format($average, 2); ?> ETB</td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td><strong>TOTAL</strong></td>
                    <td><?php echo $total_qty; ?> units</td>
                    <td><?php echo number_format($total_amount, 2); ?> ETB</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
        
        <div style="text-align:center;margin-top:20px;">
            <button onclick="window.print()" style="background:#3498db;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;margin-right:10px;">
                🖨️ Print Report
            </button>
            <button onclick="exportData()" style="background:#27ae60;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer;">
                📊 Export to Excel
            </button>
        </div>
    <?php else: ?>
        <div class="no-data">
            <h3>No items found</h3>
            <p>
                <?php if(!empty($item_filter)): ?>
                    No sales of "<?php echo htmlspecialchars($item_filter); ?>" today.
                <?php else: ?>
                    No items sold today yet.
                <?php endif; ?>
            </p>
            <a href="seller_pos.php" style="background:#3498db;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-top:15px;display:inline-block;">
                Make a Sale
            </a>
        </div>
    <?php endif; ?>

    <script>
        function exportData() {
            let item = "<?php echo urlencode($item_filter); ?>";
            window.open('export_today_items.php?item=' + item, '_blank');
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>