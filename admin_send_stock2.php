<?php
session_start();
require_once 'config.php';

// Check if user is admin
if ($_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin';

// Handle sending stock
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_stock'])) {
    $seller_id = intval($_POST['seller_id']);
    $seller_name = mysqli_real_escape_string($conn, $_POST['seller_name']);
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $quantity = floatval($_POST['quantity']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    $query = "INSERT INTO addstock (seller_id, seller_name, item_name, quantity, unit, sent_by, notes) 
              VALUES ('$seller_id', '$seller_name', '$item_name', '$quantity', '$unit', '$admin_name', '$notes')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['message'] = "✅ Stock sent successfully to $seller_name!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "❌ Error: " . mysqli_error($conn);
        $_SESSION['message_type'] = "danger";
    }
    
    header("Location: admin_send_stock.php");
    exit();
}

// Get all sellers
$sellers = [];
$seller_query = "SELECT id, full_name, username FROM users WHERE user_type = 'seller' AND is_active = 1 ORDER BY full_name";
$seller_result = mysqli_query($conn, $seller_query);
while ($seller = mysqli_fetch_assoc($seller_result)) {
    $sellers[] = $seller;
}

// Get recent sent stocks
$sent_stocks = [];
$sent_query = "SELECT * FROM addstock ORDER BY date_sent DESC LIMIT 10";
$sent_result = mysqli_query($conn, $sent_query);
while ($stock = mysqli_fetch_assoc($sent_result)) {
    $sent_stocks[] = $stock;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Send Stock to Sellers</title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 50px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #4361ee 0%, #7209b7 100%);
            color: white;
            padding: 25px 30px;
            position: relative;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(to right, #4cc9f0, #3a86ff);
        }
        
        .header h1 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 30px;
        }
        
        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
        }
        
        .form-panel, .history-panel {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            border: 1px solid #e9ecef;
        }
        
        .panel-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: #212529;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #212529;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .btn {
            padding: 14px 25px;
            background: linear-gradient(to right, #4361ee, #7209b7);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
        }
        
        .alert {
            margin: 0 30px 20px;
            padding: 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d4edda, #c3e6cb);
            border: 2px solid #28a745;
            color: #155724;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #f8d7da, #f5c6cb);
            border: 2px solid #dc3545;
            color: #721c24;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending { background: rgba(248, 150, 30, 0.1); color: #f8961e; }
        .status-approved { background: rgba(0, 184, 148, 0.1); color: #00b894; }
        .status-rejected { background: rgba(247, 37, 133, 0.1); color: #f72585; }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th {
            background: linear-gradient(135deg, #4361ee 0%, #7209b7 100%);
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        table td {
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        
        table tr:hover {
            background: rgba(67, 97, 238, 0.05);
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.9);
            color: #4361ee;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            margin-top: 20px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-paper-plane"></i> Send Stock to Sellers</h1>
            <p>Admin: <?php echo htmlspecialchars($admin_name); ?></p>
        </div>
        
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                <i class="fas fa-<?php echo ($_SESSION['message_type'] ?? 'success') == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); ?>
                <?php unset($_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>
        
        <div class="content">
            <!-- Left Panel: Send Stock Form -->
            <div class="form-panel">
                <h2 class="panel-title">
                    <i class="fas fa-truck"></i> Send Stock to Seller
                </h2>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="seller_id"><i class="fas fa-user"></i> Select Seller</label>
                        <select id="seller_id" name="seller_id" class="form-control" required 
                                onchange="updateSellerName(this)">
                            <option value="">-- Select Seller --</option>
                            <?php foreach($sellers as $seller): ?>
                                <option value="<?php echo $seller['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($seller['full_name'] ?: $seller['username']); ?>">
                                    <?php echo htmlspecialchars($seller['full_name'] ?: $seller['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="seller_name"><i class="fas fa-user-tag"></i> Seller Name</label>
                        <input type="text" id="seller_name" name="seller_name" class="form-control" required readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="item_name"><i class="fas fa-tag"></i> Item Name</label>
                        <input type="text" id="item_name" name="item_name" class="form-control" required 
                               placeholder="Enter item name">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-balance-scale"></i> Quantity & Unit</label>
                        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px;">
                            <input type="number" name="quantity" class="form-control" step="0.1" min="0.1" required 
                                   placeholder="Quantity">
                            <select name="unit" class="form-control" required>
                                <option value="">Unit</option>
                                <option value="pcs">Pieces</option>
                                <option value="kg">Kilograms</option>
                                <option value="g">Grams</option>
                                <option value="l">Liters</option>
                                <option value="ml">Milliliters</option>
                                <option value="box">Box</option>
                                <option value="pack">Pack</option>
                                <option value="bundle">Bundle</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes"><i class="fas fa-sticky-note"></i> Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" 
                                  placeholder="Optional notes for the seller"></textarea>
                    </div>
                    
                    <button type="submit" name="send_stock" class="btn">
                        <i class="fas fa-paper-plane"></i> SEND STOCK TO SELLER
                    </button>
                </form>
            </div>
            
            <!-- Right Panel: Recent Sent Stocks -->
            <div class="history-panel">
                <h2 class="panel-title">
                    <i class="fas fa-history"></i> Recently Sent Stocks
                </h2>
                
                <?php if(!empty($sent_stocks)): ?>
                    <div style="overflow-x: auto;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Seller</th>
                                    <th>Item</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($sent_stocks as $stock): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($stock['seller_name']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($stock['item_name']); ?></strong>
                                            <div style="font-size: 0.8rem; color: #6c757d;">
                                                <?php echo $stock['quantity']; ?> <?php echo $stock['unit']; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $stock['status']; ?>">
                                                <?php echo ucfirst($stock['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M d, H:i', strtotime($stock['date_sent'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px 20px; color: #6c757d;">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 15px; color: #dee2e6;"></i>
                        <h3>No Stocks Sent Yet</h3>
                        <p>Send stock to sellers using the form</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="padding: 0 30px 30px;">
            <a href="admin_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
            </a>
        </div>
    </div>

    <script>
        function updateSellerName(select) {
            const selectedOption = select.options[select.selectedIndex];
            const sellerName = selectedOption.getAttribute('data-name');
            document.getElementById('seller_name').value = sellerName || '';
        }
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>