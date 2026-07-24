<?php
require_once 'config.php';

// Check login (simple version - you should enhance security)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="image\photo_2026-01-12_07-44-10.jpg">
    <title>Inventory Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 15px 15px 0 0;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 32px;
        }
        .user-info {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        .card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #5a67d8;
            transform: translateY(-2px);
        }
        .btn-admin {
            background: #e53e3e;
        }
        .btn-seller {
            background: #38a169;
        }
        .btn-view {
            background: #4299e1;
        }
        .quick-actions {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .quick-actions h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .inventory-table {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background: #f7fafc;
            font-weight: 600;
            color: #4a5568;
        }
        .low-stock {
            background: #fff5f5;
            color: #c53030;
            font-weight: bold;
        }
        .good-stock {
            background: #f0fff4;
            color: #2f855a;
        }
        .navbar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .nav-btn {
            padding: 12px 25px;
            background: white;
            color: #667eea;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .nav-btn:hover {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="navbar">
            <a href="history.php" class="nav-btn">📊 Sales History</a>
            <a href="inventory.php" class="nav-btn" style="background: #667eea; color: white;">📦 Inventory</a>
            <a href="inventory_logs.php" class="nav-btn">📋 Inventory Logs</a>
            <a href="logout.php" class="nav-btn" style="background: #e53e3e; color: white;">🚪 Logout</a>
        </div>
        
        <div class="header">
            <h1>📦 Inventory Management System</h1>
            <div class="user-info">
                👤 <?php echo $_SESSION['full_name']; ?> (<?php echo ucfirst($_SESSION['role']); ?>)
            </div>
        </div>
        
        <div class="dashboard-cards">
            <?php if ($user_role == 'admin'): ?>
            <div class="card">
                <h3>Admin Actions</h3>
                <p>Send inventory to sellers, add new items, and manage stock levels.</p>
                <a href="admin_send.php" class="btn btn-admin">📤 Send Inventory</a>
                <a href="add_item.php" class="btn" style="margin-left: 10px;">➕ Add Item</a>
            </div>
            <?php endif; ?>
            
            <?php if ($user_role == 'seller'): ?>
            <div class="card">
                <h3>Seller Actions</h3>
                <p>Receive inventory from admin, view your current stock, and request items.</p>
                <a href="seller_receive.php" class="btn btn-seller">📥 Receive Inventory</a>
                <a href="my_stock.php" class="btn" style="margin-left: 10px;">📊 My Stock</a>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <h3>View All Inventory</h3>
                <p>See complete inventory status across all locations with stock levels.</p>
                <a href="#inventory-list" class="btn btn-view">📋 View Inventory</a>
            </div>
            
            <div class="card">
                <h3>Quick Stats</h3>
                <?php
                // Get stats
                $total_items = mysqli_query($conn, "SELECT COUNT(*) as total FROM inventory_items")->fetch_assoc()['total'];
                $low_stock = mysqli_query($conn, "SELECT COUNT(*) as total FROM inventory_items WHERE current_stock <= min_stock")->fetch_assoc()['total'];
                $total_value = mysqli_query($conn, "SELECT SUM(current_stock * price) as total FROM inventory_items")->fetch_assoc()['total'];
                ?>
                <p>📦 Total Items: <strong><?php echo $total_items; ?></strong></p>
                <p>⚠️ Low Stock Items: <strong><?php echo $low_stock; ?></strong></p>
                <p>💰 Total Value: <strong>ETB <?php echo number_format($total_value, 2); ?></strong></p>
            </div>
        </div>
        
        <div class="quick-actions">
            <h2>⚡ Quick Actions</h2>
            <div class="action-buttons">
                <a href="inventory_logs.php" class="btn">📜 View All Transactions</a>
                <a href="low_stock.php" class="btn" style="background: #ed8936;">⚠️ Low Stock Alert</a>
                <a href="generate_report.php" class="btn" style="background: #38a169;">📊 Generate Report</a>
                <a href="settings.php" class="btn" style="background: #718096;">⚙️ Settings</a>
            </div>
        </div>
        
        <div class="inventory-table" id="inventory-list">
            <h2>📋 Current Inventory</h2>
            <table>
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                        <th>Min Stock</th>
                        <th>Price</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $items_query = mysqli_query($conn, "SELECT * FROM inventory_items ORDER BY current_stock ASC");
                    while($item = mysqli_fetch_assoc($items_query)):
                        $stock_class = ($item['current_stock'] <= $item['min_stock']) ? 'low-stock' : 'good-stock';
                    ?>
                    <tr>
                        <td><strong><?php echo $item['item_code']; ?></strong></td>
                        <td><?php echo $item['item_name']; ?></td>
                        <td><?php echo $item['category']; ?></td>
                        <td class="<?php echo $stock_class; ?>"><?php echo $item['current_stock']; ?></td>
                        <td><?php echo $item['min_stock']; ?></td>
                        <td>ETB <?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['location']; ?></td>
                        <td>
                            <?php if($item['current_stock'] <= $item['min_stock']): ?>
                            <span style="color: #e53e3e; font-weight: bold;">⚠️ Low Stock</span>
                            <?php else: ?>
                            <span style="color: #38a169; font-weight: bold;">✅ Good</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($user_role == 'admin'): ?>
                            <a href="edit_item.php?id=<?php echo $item['id']; ?>" style="color: #4299e1; margin-right: 10px;">✏️ Edit</a>
                            <a href="send_item.php?id=<?php echo $item['id']; ?>" style="color: #667eea;">📤 Send</a>
                            <?php else: ?>
                            <a href="request_item.php?id=<?php echo $item['id']; ?>" style="color: #ed8936;">📝 Request</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>