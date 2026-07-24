<?php
session_start();
require_once 'config.php';

// Set timezone
date_default_timezone_set('Africa/Addis_Ababa');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'export_excel') {
    
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $branch_id = intval($_POST['branch_id']);
    $branch_name = $_POST['branch_name'];
    
    // Get withdrawals for the date range
    $query = "SELECT dw.*, u.username as user_username 
              FROM daily_withdrawals dw
              LEFT JOIN users u ON dw.user_id = u.id
              WHERE DATE(dw.created_at) BETWEEN '$start_date' AND '$end_date'
              AND dw.branch_id = $branch_id
              ORDER BY dw.created_at DESC";
    
    $result = mysqli_query($conn, $query);
    
    $withdrawals = [];
    while($row = mysqli_fetch_assoc($result)) {
        $withdrawals[] = $row;
    }
    
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="withdrawals_report_' . $start_date . '_to_' . $end_date . '.xls"');
    header('Cache-Control: max-age=0');
    
    // Format dates for display
    $start_display = date('F j, Y', strtotime($start_date));
    $end_display = date('F j, Y', strtotime($end_date));
    if($start_date == $end_date) {
        $date_range_display = $start_display;
    } else {
        $date_range_display = $start_display . ' - ' . $end_display;
    }
    
    // Generate Excel HTML
    echo '<html>';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>የወጪ ሪፖርት - ' . htmlspecialchars($branch_name) . '</title>';
    echo '<style>';
    echo 'body { font-family: "Segoe UI", "Nyala", Arial, sans-serif; margin: 20px; }';
    echo '.header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #2c3e50; padding-bottom: 20px; }';
    echo '.title { font-size: 24px; font-weight: bold; color: #2c3e50; margin-bottom: 5px; }';
    echo '.subtitle { font-size: 14px; color: #7f8c8d; margin-bottom: 5px; }';
    echo '.branch-info { font-size: 16px; font-weight: 600; color: #3498db; margin: 5px 0; }';
    echo '.date-range { background: #e8f4fd; padding: 10px; border-radius: 5px; margin: 15px 0; font-size: 14px; }';
    echo '.data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    echo '.data-table th { background: #2c3e50; color: white; padding: 12px; text-align: left; font-size: 12px; border: 1px solid #1a252f; }';
    echo '.data-table td { border: 1px solid #ddd; padding: 10px; font-size: 11px; }';
    echo '.data-table tr:nth-child(even) { background: #f9f9f9; }';
    echo '.data-table tr:hover { background: #f1f1f1; }';
    echo '.temp-row { border-left: 3px solid #ff9800; }';
    echo '.perm-row { border-left: 3px solid #4CAF50; }';
    echo '.amount-temp { color: #e65100; font-weight: bold; }';
    echo '.amount-perm { color: #2e7d32; font-weight: bold; }';
    echo '.footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; font-size: 11px; color: #7f8c8d; }';
    echo '.badge { display: inline-block; padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; }';
    echo '.badge-temp { background: #fff3e0; color: #e65100; }';
    echo '.badge-perm { background: #e8f5e9; color: #2e7d32; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Header without Logo
    echo '<div class="header">';
    echo '<div class="title">አሌልቱ የእንስሳት ተዋጽኦ</div>';
    echo '<div class="subtitle">Animal Products Supplier</div>';
    echo '<div class="branch-info">' . htmlspecialchars($branch_name) . '</div>';
    echo '<div class="date-range">';
    echo '<strong>የሪፖርት ቀን ክልል:</strong> ' . $date_range_display;
    echo '</div>';
    echo '</div>';
    
    // Detailed Withdrawals Table
    echo '<table class="data-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>#</th>';
    echo '<th>የኢትዮጵያ ቀን</th>';
    echo '<th>ሰዓት</th>';
    echo '<th>ሰው</th>';
    echo '<th>የወጪ አይነት</th>';
    echo '<th>ክፍያ አይነት</th>';
    echo '<th>ምክንያት</th>';
    echo '<th>መጠን (ETB)</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    if(count($withdrawals) > 0) {
        $counter = 1;
        foreach($withdrawals as $w) {
            $row_class = ($w['withdrawal_type'] == 'ጊዜያዊ') ? 'temp-row' : 'perm-row';
            $amount_class = ($w['withdrawal_type'] == 'ጊዜያዊ') ? 'amount-temp' : 'amount-perm';
            $badge_class = ($w['withdrawal_type'] == 'ጊዜያዊ') ? 'badge-temp' : 'badge-perm';
            
            // Get Ethiopian date from stored value or calculate
            $eth_date = !empty($w['ethiopian_date']) ? $w['ethiopian_date'] : date('Y/m/d', strtotime($w['created_at']));
            
            // Format payment type for display
            $payment_display = ucwords(str_replace('_', ' ', $w['payment_type']));
            
            echo '<tr class="' . $row_class . '">';
            echo '<td>' . $counter++ . '</td>';
            echo '<td>' . htmlspecialchars($eth_date) . '</td>';
            echo '<td>' . date('h:i A', strtotime($w['created_at'])) . '</td>';
            echo '<td>' . htmlspecialchars($w['username'] ?: $w['username']) . '</td>';
            echo '<td><span class="badge ' . $badge_class . '">' . htmlspecialchars($w['withdrawal_type']) . '</span></td>';
            echo '<td>' . htmlspecialchars($payment_display) . '</td>';
            echo '<td>' . htmlspecialchars(substr($w['reason'], 0, 100)) . '</td>';
            echo '<td class="' . $amount_class . '">' . number_format($w['amount'], 2) . '</td>';
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="8" style="text-align: center; padding: 40px;">ምንም ወጪ አልተገኘም</strong></td></tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    
    // Footer
    echo '<div class="footer">';
    echo '<div>ሪፖርት የተዘጋጀበት ቀን: ' . date('Y-m-d H:i:s') . '</div>';
    echo '<div>© 2018 አሌልቱ የእንስሳት ተዋጽኦ - ሁሉም መብቶች ተጠብቀዋል</div>';
    echo '</div>';
    
    echo '</body>';
    echo '</html>';
    
} else {
    header("Location: daily_cashier.php");
    exit();
}

mysqli_close($conn);
?>