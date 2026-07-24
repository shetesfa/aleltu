<?php
session_start();
require_once 'config.php';

// Set charset for Amharic
mysqli_set_charset($conn, "utf8mb4");

// Check login
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'admin' && $_SESSION['role'] != 'super_admin')) {
    header("Location: index.php");
    exit();
}

// Get parameters
$view = $_GET['view'] ?? 'overview';
$period = $_GET['period'] ?? '1month';
$custom_start = $_GET['start_date'] ?? '';
$custom_end = $_GET['end_date'] ?? '';

// FORCE branch validation
$current_branch_id = getCurrentBranchId($conn, $_SESSION['user_id'], $_SESSION['role']);
if ($current_branch_id <= 0 && $_SESSION['role'] != 'super_admin') {
    die("Export failed: No branch access");
}
$current_branch_name = getCurrentBranchName($conn, $current_branch_id);

// Date ranges based on period
$today = date('Y-m-d');

// Define period options
$period_options = [
    '3days' => ['days' => 3, 'text' => 'ባለፉት 3 ቀናት'],
    '1week' => ['days' => 7, 'text' => 'ባለፉት 1 ሳምንት'],
    '2weeks' => ['days' => 14, 'text' => 'ባለፉት 2 ሳምንታት'],
    '3weeks' => ['days' => 21, 'text' => 'ባለፉት 3 ሳምንታት'],
    '1month' => ['days' => 30, 'text' => 'ባለፉት 1 ወር'],
    '2months' => ['days' => 60, 'text' => 'ባለፉት 2 ወራት'],
    '3months' => ['days' => 90, 'text' => 'ባለፉት 3 ወራት'],
    '6months' => ['days' => 180, 'text' => 'ባለፉት 6 ወራት'],
    '9months' => ['days' => 270, 'text' => 'ባለፉት 9 ወራት'],
    '1year' => ['days' => 365, 'text' => 'ባለፉት 1 አመት']
];

// Set date range
if ($period == 'custom' && !empty($custom_start) && !empty($custom_end)) {
    $date_from = $custom_start;
    $date_to = $custom_end;
    $period_text = "ከ $custom_start እስከ $custom_end";
} elseif (isset($period_options[$period])) {
    $days = $period_options[$period]['days'];
    $date_from = date('Y-m-d', strtotime("-{$days} days"));
    $date_to = $today;
    $period_text = $period_options[$period]['text'];
} else {
    $period = '1month';
    $date_from = date('Y-m-d', strtotime('-30 days'));
    $date_to = $today;
    $period_text = 'ባለፉት 1 ወር';
}

$date_from_esc = mysqli_real_escape_string($conn, $date_from);
$date_to_esc = mysqli_real_escape_string($conn, $date_to);

// Set filename
$filename = 'report_' . $view . '_' . date('Y-m-d') . '.xls';

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Start output
echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
echo '<style>';
echo 'table { border-collapse: collapse; width: 100%; }';
echo 'th { background: #f0f0f0; font-weight: bold; }';
echo 'td, th { border: 1px solid #000; padding: 5px; }';
echo '.summary-row { background: #e0e0e0; font-weight: bold; }';
echo '</style>';
echo '<title>' . $period_text . ' - ሪፖርት</title>';
echo '</head>';
echo '<body>';

// Generate report based on view
if ($view == 'overview') {
    // Recent Activity Report - FIXED with COLLATE
    echo '<h2>የቅርብ ጊዜ እንቅስቃሴ - ' . $period_text . '</h2>';
    echo '<p>ቅርንጫፍ: ' . htmlspecialchars($current_branch_name) . '</p>';
    echo '<table border="1">';
    echo '<tr><th>ዓይነት</th><th>ሻጭ</th><th>ዝርዝር</th><th>ቀን</th></tr>';
    
    // FIXED: Added COLLATE to each SELECT to fix collation issue
    $query = "SELECT 
        'ሽያጭ' COLLATE utf8mb4_unicode_ci as type,
        seller_name COLLATE utf8mb4_unicode_ci as seller_name,
        CONCAT(total_amount, ' ብር') COLLATE utf8mb4_unicode_ci as details,
        transaction_date as date
        FROM transactions 
        WHERE DATE(transaction_date) BETWEEN '$date_from_esc' AND '$date_to_esc'
        AND branch_id = $current_branch_id
        UNION ALL
        SELECT 
        'ክምችት' COLLATE utf8mb4_unicode_ci as type,
        seller_name COLLATE utf8mb4_unicode_ci as seller_name,
        CONCAT(quantity, ' ', unit, ' - ', item_name) COLLATE utf8mb4_unicode_ci as details,
        date_added as date
        FROM stock_logs 
        WHERE DATE(date_added) BETWEEN '$date_from_esc' AND '$date_to_esc'
        AND branch_id = $current_branch_id
        UNION ALL
        SELECT 
        'ተመላሽ' COLLATE utf8mb4_unicode_ci as type,
        seller_name COLLATE utf8mb4_unicode_ci as seller_name,
        CONCAT(quantity, ' ', unit, ' - ', product_name) COLLATE utf8mb4_unicode_ci as details,
        gregorian_date as date
        FROM product_returns 
        WHERE DATE(gregorian_date) BETWEEN '$date_from_esc' AND '$date_to_esc'
        AND branch_id = $current_branch_id
        ORDER BY date DESC
        LIMIT 500";
    
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<tr>';
            echo '<td>' . $row['type'] . '</td>';
            echo '<td>' . htmlspecialchars($row['seller_name'] ?? 'ሥርዓት') . '</td>';
            echo '<td>' . $row['details'] . '</td>';
            echo '<td>' . date('Y-m-d H:i', strtotime($row['date'])) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4" style="text-align: center; color: red;">ስህተት: ' . mysqli_error($conn) . '</td></tr>';
    }
    
    if (mysqli_num_rows($result) == 0) {
        echo '<tr><td colspan="4" style="text-align: center;">ምንም እንቅስቃሴ አልተገኘም</td></tr>';
    }
    echo '</table>';
}
elseif ($view == 'products') {
    // Top Products Report
    echo '<h2>ከፍተኛ ሽያጭ ምርቶች - ' . $period_text . '</h2>';
    echo '<p>ቅርንጫፍ: ' . htmlspecialchars($current_branch_name) . '</p>';
    echo '<table border="1">';
    echo '<tr><th>ደረጃ</th><th>ምርት</th><th>የተሸጠበት ጊዜ</th><th>ጠቅላላ ጊዜ</th><th>ጠቅላላ ገቢ</th><th>ዋጋ</th></tr>';
    
    $query = "SELECT 
        ti.product_name,
        COUNT(DISTINCT t.id) as times_sold,
        SUM(ti.quantity) as total_quantity,
        SUM(ti.subtotal) as total_revenue,
        AVG(ti.unit_price) as avg_price,
        GROUP_CONCAT(DISTINCT ti.unit_price ORDER BY ti.unit_price SEPARATOR ', ') as all_prices
        FROM transactions t
        JOIN transaction_items ti ON t.id = ti.transaction_id
        WHERE DATE(t.transaction_date) BETWEEN '$date_from_esc' AND '$date_to_esc'
        AND t.branch_id = $current_branch_id
        GROUP BY ti.product_name
        ORDER BY total_revenue DESC
        LIMIT 100";
    
    $result = mysqli_query($conn, $query);
    $rank = 1;
    while ($row = mysqli_fetch_assoc($result)) {
        $prices = explode(', ', $row['all_prices']);
        $unique_prices = array_unique($prices);
        $price_display = implode(' → ', array_slice($unique_prices, 0, 3));
        if (count($unique_prices) > 3) $price_display .= ' ...';
        
        echo '<tr>';
        echo '<td>' . $rank++ . '</td>';
        echo '<td>' . htmlspecialchars($row['product_name']) . '</td>';
        echo '<td>' . $row['times_sold'] . ' ጊዜ ተሸጧል</td>';
        echo '<td>' . number_format($row['total_quantity'], 2) . ' ክፍል</td>';
        echo '<td>' . number_format($row['total_revenue'], 2) . ' ብር</td>';
        echo '<td>' . $price_display . ' ብር</td>';
        echo '</tr>';
    }
    
    if (mysqli_num_rows($result) == 0) {
        echo '<tr><td colspan="6" style="text-align: center;">ምንም ውሂብ አልተገኘም</td></tr>';
    }
    echo '</table>';
}
elseif ($view == 'product_performance') {
    // Product Performance Report - WITHOUT return rate
    echo '<h2>የምርቶች አፈጻጸም - ' . $period_text . '</h2>';
    echo '<p>ቅርንጫፍ: ' . htmlspecialchars($current_branch_name) . '</p>';
    echo '<table border="1">';
    echo '<tr><th>ምርት</th><th>የተሸጠው ብዛት</th><th>የሽያጭ ጊዜ</th><th>ጠቅላላ ገቢ</th><th>አማካይ ዋጋ</th><th>የዋጋ ልዩነት</th></tr>';
    
    // Get products with sales
    $query = "SELECT 
        p.name as product_name,
        si.unit,
        COALESCE(SUM(ti.quantity), 0) as total_sold,
        COUNT(DISTINCT t.id) as times_sold,
        COALESCE(SUM(ti.subtotal), 0) as total_revenue,
        COALESCE(AVG(ti.unit_price), 0) as avg_price,
        COALESCE(MIN(ti.unit_price), 0) as min_price,
        COALESCE(MAX(ti.unit_price), 0) as max_price,
        p.unit_price as base_price
        FROM products p
        LEFT JOIN seller_inventory si ON si.item_name = p.name AND si.branch_id = $current_branch_id
        LEFT JOIN transaction_items ti ON ti.product_name = p.name
        LEFT JOIN transactions t ON ti.transaction_id = t.id 
            AND DATE(t.transaction_date) BETWEEN '$date_from_esc' AND '$date_to_esc'
            AND t.branch_id = $current_branch_id
        GROUP BY p.id, p.name, p.unit_price, si.unit
        HAVING total_sold > 0
        ORDER BY total_revenue DESC";
    
    $result = mysqli_query($conn, $query);
    $total_sales_all = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $price_variation = $row['max_price'] - $row['min_price'];
        $total_sales_all += $row['total_revenue'];
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['product_name']) . '</td>';
        echo '<td>' . number_format($row['total_sold'], 2) . ' ' . ($row['unit'] ?? 'ክፍል') . '</td>';
        echo '<td>' . $row['times_sold'] . ' ጊዜ</td>';
        echo '<td>' . number_format($row['total_revenue'], 2) . ' ብር</td>';
        echo '<td>' . number_format($row['avg_price'], 2) . ' ብር';
        if (!empty($row['base_price']) && $row['base_price'] > 0) {
            echo ' (የተቀመጠ: ' . number_format($row['base_price'], 2) . ' ብር)';
        }
        echo '</td>';
        echo '<td>';
        if ($price_variation > 0) {
            echo number_format($row['min_price'], 2) . ' - ' . number_format($row['max_price'], 2) . ' ብር';
        } else {
            echo 'ቋሚ';
        }
        echo '</td>';
        echo '</tr>';
    }
    
    if (mysqli_num_rows($result) > 0) {
        echo '<tr class="summary-row">';
        echo '<td><strong>ድምር</strong></td>';
        echo '<td><strong>' . mysqli_num_rows($result) . ' ምርቶች</strong></td>';
        echo '<td>-</td>';
        echo '<td><strong>' . number_format($total_sales_all, 2) . ' ብር</strong></td>';
        echo '<td>-</td>';
        echo '<td>-</td>';
        echo '</tr>';
    } else {
        echo '<tr><td colspan="6" style="text-align: center;">ምንም ውሂብ አልተገኘም</td></tr>';
    }
    echo '</table>';
}
elseif ($view == 'sellers') {
    // Seller Performance Report - SIMPLIFIED
    echo '<h2>የሻጮች አፈጻጸም - ' . $period_text . '</h2>';
    echo '<p>ቅርንጫፍ: ' . htmlspecialchars($current_branch_name) . '</p>';
    echo '<table border="1">';
    echo '<tr><th>ሻጭ</th><th>ጠቅላላ ገቢ</th></tr>';
    
    $query = "SELECT 
        t.seller_name,
        COALESCE(SUM(ti.subtotal), 0) as revenue
        FROM transactions t
        JOIN transaction_items ti ON t.id = ti.transaction_id
        WHERE DATE(t.transaction_date) BETWEEN '$date_from_esc' AND '$date_to_esc'
        AND t.branch_id = $current_branch_id
        GROUP BY t.seller_id, t.seller_name
        ORDER BY revenue DESC";
    
    $result = mysqli_query($conn, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['seller_name']) . '</td>';
        echo '<td>' . number_format($row['revenue'], 2) . ' ብር</td>';
        echo '</tr>';
    }
    
    if (mysqli_num_rows($result) == 0) {
        echo '<tr><td colspan="2" style="text-align: center;">ምንም ውሂብ አልተገኘም</td></tr>';
    }
    echo '</table>';
}

// Add summary info
echo '<br><hr>';
echo '<p><strong>ሪፖርት የተዘጋጀበት ቀን:</strong> ' . date('Y-m-d H:i:s') . '</p>';
echo '<p><strong>ጊዜ:</strong> ' . $period_text . '</p>';
echo '<p><strong>ቅርንጫፍ:</strong> ' . htmlspecialchars($current_branch_name) . '</p>';

echo '</body></html>';

mysqli_close($conn);
exit();
?>