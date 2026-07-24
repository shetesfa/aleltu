<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "aleltu_pos");

// Get last 30 days data
$days_query = "SELECT 
    DATE(created_at) as sale_date,
    COUNT(*) as transactions,
    SUM(total_amount) as total_sales,
    GROUP_CONCAT(DISTINCT seller_name) as sellers
    FROM transactions 
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY sale_date DESC";
$days_result = mysqli_query($conn, $days_query);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Daily Sales Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: Arial; }
        body { background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(to right, #2c3e50, #4a6491); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 2rem; margin-bottom: 10px; }
        .back-btn { position: absolute; top: 30px; left: 30px; background: rgba(255,255,255,0.2); color: white; padding: 10px 20px; border-radius: 50px; text-decoration: none; font-weight: bold; }
        .days-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; padding: 30px; }
        .day-card { background: white; border-radius: 10px; padding: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-left: 5px solid #3498db; transition: transform 0.3s; }
        .day-card:hover { transform: translateY(-5px); }
        .day-date { font-size: 1.2rem; font-weight: bold; color: #2c3e50; margin-bottom: 10px; }
        .day-sales { font-size: 1.8rem; color: #27ae60; font-weight: bold; margin-bottom: 5px; }
        .day-info { color: #7f8c8d; font-size: 0.9rem; }
        .today { border-left-color: #e74c3c; background: #fff9f9; }
        .high-sales { border-left-color: #27ae60; background: #f9fff9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="history.php" class="back-btn">← Back to History</a>
            <h1>📅 Daily Sales Report</h1>
            <p>Last 30 Days Performance</p>
        </div>
        
        <div class="days-grid">
            <?php
            if (mysqli_num_rows($days_result) > 0) {
                while($day = mysqli_fetch_assoc($days_result)) {
                    $date = date('D, M j', strtotime($day['sale_date']));
                    $is_today = date('Y-m-d') == $day['sale_date'] ? 'today' : '';
                    $is_high_sales = $day['total_sales'] > 1000 ? 'high-sales' : '';
                    
                    echo "<div class='day-card $is_today $is_high_sales'>";
                    echo "<div class='day-date'>$date</div>";
                    echo "<div class='day-sales'>" . number_format($day['total_sales'], 2) . " ETB</div>";
                    echo "<div class='day-info'>" . $day['transactions'] . " transactions";
                    if ($day['sellers']) echo " • " . $day['sellers'];
                    echo "</div>";
                    echo "</div>";
                }
            } else {
                echo "<div style='padding: 40px; text-align: center; color: #999;'>No sales data available</div>";
            }
            ?>
        </div>
    </div>
</body>
</html>
<?php mysqli_close($conn); ?>