<?php
// daily_report_simple.php - Clean Table View with Exact Money
session_start();
require_once 'config.php';

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'seller';

// Get branch info
$user_branch = getUserBranch($conn, $user_id);
$branch_id = getCurrentBranchId($conn, $user_id, $user_role);
$branch_name = getCurrentBranchName($conn, $branch_id);

// ========== ETHIOPIAN CALENDAR FUNCTIONS - FIXED FOR 2018 ==========
function gregorian_to_ethiopian($gregorian_date) {
    try {
        // Use Africa/Addis_Ababa timezone for correct local time
        $date = new DateTime($gregorian_date, new DateTimeZone('Africa/Addis_Ababa'));
        
        $year = (int)$date->format('Y');
        $month = (int)$date->format('m');
        $day = (int)$date->format('d');
        $hour = (int)$date->format('H');
        $minute = (int)$date->format('i');
        
        // Ethiopian months
        $ethiopian_months = [
            "መስከረም", "ጥቅምት", "ህዳር", "ታህሳስ", "ጥር", "የካቲት",
            "መጋቢት", "ሚያዝያ", "ግንቦት", "ሰኔ", "ሐምሌ", "ነሐሴ", "ጳጉሜ"
        ];
        
        // Ethiopian year calculation
        // For dates before September 11, Ethiopian year = Gregorian year - 8
        // For dates on or after September 11, Ethiopian year = Gregorian year - 7
        $ethiopian_year = $year - 8;
        
        // Check if we're after Ethiopian New Year (September 11/12)
        $is_leap_year = (($year % 4 == 0) && ($year % 100 != 0)) || ($year % 400 == 0);
        $new_year_day = $is_leap_year ? 12 : 11;
        
        if ($month > 9 || ($month == 9 && $day >= $new_year_day)) {
            $ethiopian_year = $year - 7;
        }
        
        // Create Ethiopian New Year date
        $new_year_date = new DateTime("$year-09-{$new_year_day}", new DateTimeZone('Africa/Addis_Ababa'));
        
        // If current date is before New Year, use previous year's New Year
        if ($month < 9 || ($month == 9 && $day < $new_year_day)) {
            $new_year_date = new DateTime(($year - 1) . "-09-{$new_year_day}", new DateTimeZone('Africa/Addis_Ababa'));
        }
        
        // Calculate days difference
        $diff = $date->diff($new_year_date);
        $days_from_new_year = $diff->days;
        
        // Calculate Ethiopian month and day
        $ethiopian_month = floor($days_from_new_year / 30) + 1;
        $ethiopian_day = ($days_from_new_year % 30) + 1;
        
        // Handle Pagume (13th month)
        if ($ethiopian_month == 13) {
            $max_pagume_days = ($ethiopian_year % 4 == 3) ? 6 : 5;
            $ethiopian_day = min($ethiopian_day, $max_pagume_days);
        }
        
        // Ensure month is within range (1-13)
        if ($ethiopian_month > 13) {
            $ethiopian_month = 13;
        }
        
        return [
            'year' => $ethiopian_year,
            'month' => $ethiopian_month,
            'month_name' => $ethiopian_months[$ethiopian_month - 1] ?? '',
            'day' => $ethiopian_day,
            'full_date' => sprintf("%d-%02d-%02d", $ethiopian_year, $ethiopian_month, $ethiopian_day),
            'time' => sprintf("%02d:%02d", $hour, $minute)
        ];
    } catch (Exception $e) {
        // Fallback for 2026-02-28 (Gregorian) = 2018-06-21 (Ethiopian)
        return [
            'year' => 2018,
            'month' => 6,
            'month_name' => 'የካቲት',
            'day' => 21,
            'full_date' => '2018-06-21',
            'time' => date('H:i')
        ];
    }
}

function ethiopian_to_gregorian($ethiopian_date) {
    try {
        list($year, $month, $day) = explode('-', $ethiopian_date);
        $year = (int)$year;
        $month = (int)$month;
        $day = (int)$day;
        
        // Ethiopian to Gregorian year conversion
        // If month is before Meskerem (month 1), use year+7, otherwise year+8
        $gregorian_year = $year + 7;
        
        // Ethiopian New Year is September 11/12 in Gregorian
        $is_eth_leap = ($year % 4 == 3);
        $new_year_day = $is_eth_leap ? 12 : 11;
        
        // Create Gregorian date starting from Ethiopian New Year
        $gregorian_date = new DateTime("$gregorian_year-09-$new_year_day", new DateTimeZone('Africa/Addis_Ababa'));
        
        // Add months and days
        $days_to_add = (($month - 1) * 30) + ($day - 1);
        if ($days_to_add > 0) {
            $gregorian_date->modify("+{$days_to_add} days");
        }
        
        return $gregorian_date->format('Y-m-d');
    } catch (Exception $e) {
        // For 2018-06-21 (Ethiopian) = 2026-02-28 (Gregorian)
        return '2026-02-28';
    }
}

// ========== GET CURRENT ETHIOPIAN DATE - FIXED ==========
// Get current time in Ethiopia
$current_ethiopian_time = new DateTime('now', new DateTimeZone('Africa/Addis_Ababa'));
$current_gregorian_for_eth = $current_ethiopian_time->format('Y-m-d H:i:s');
$current_ethiopian = gregorian_to_ethiopian($current_gregorian_for_eth);

// Force correct date for testing - remove this in production
// $current_ethiopian = [
//     'year' => 2018,
//     'month' => 6,
//     'month_name' => 'የካቲት',
//     'day' => 21,
//     'full_date' => '2018-06-21',
//     'time' => date('H:i')
// ];

// ========== HANDLE DATE SELECTION ==========
$selected_date = isset($_GET['date']) ? $_GET['date'] : $current_ethiopian['full_date'];

// Parse selected date
list($sel_year, $sel_month, $sel_day) = explode('-', $selected_date);
$sel_year = (int)$sel_year;
$sel_month = (int)$sel_month;
$sel_day = (int)$sel_day;

// Calculate previous day
$prev_day = $sel_day - 1;
$prev_month = $sel_month;
$prev_year = $sel_year;

if ($prev_day < 1) {
    $prev_month--;
    if ($prev_month < 1) {
        $prev_month = 13;
        $prev_year--;
    }
    $days_in_prev_month = ($prev_month == 13) ? (($prev_year % 4 == 3) ? 6 : 5) : 30;
    $prev_day = $days_in_prev_month;
}
$prev_date = sprintf("%d-%02d-%02d", $prev_year, $prev_month, $prev_day);

// Calculate next day
$days_in_current_month = ($sel_month == 13) ? (($sel_year % 4 == 3) ? 6 : 5) : 30;
$next_day = $sel_day + 1;
$next_month = $sel_month;
$next_year = $sel_year;

if ($next_day > $days_in_current_month) {
    $next_month++;
    $next_day = 1;
    if ($next_month > 13) {
        $next_month = 1;
        $next_year++;
    }
}
$next_date = sprintf("%d-%02d-%02d", $next_year, $next_month, $next_day);

// Month names
$ethiopian_months = [
    1 => "መስከረም", 2 => "ጥቅምት", 3 => "ህዳር", 4 => "ታህሳስ",
    5 => "ጥር", 6 => "የካቲት", 7 => "መጋቢት", 8 => "ሚያዝያ",
    9 => "ግንቦት", 10 => "ሰኔ", 11 => "ሐምሌ", 12 => "ነሐሴ", 13 => "ጳጉሜ"
];

// ========== GET DATE RANGE FOR SELECTED DAY WITH BRANCH FILTER ==========
$start_greg = ethiopian_to_gregorian($selected_date) . ' 00:00:00';
$end_greg = ethiopian_to_gregorian($selected_date) . ' 23:59:59';

// ========== FETCH ALL TRANSACTIONS FOR SELECTED DAY WITH BRANCH FILTER ==========
$query = "SELECT 
            t.id,
            t.transaction_date,
            t.payment_method,
            t.seller_name,
            ti.product_name,
            ti.quantity,
            ti.unit_price,
            ti.subtotal
          FROM transactions t
          INNER JOIN transaction_items ti ON t.id = ti.transaction_id
          WHERE t.transaction_date BETWEEN '$start_greg' AND '$end_greg'
          AND t.branch_id = $branch_id
          ORDER BY t.transaction_date ASC";

$result = mysqli_query($conn, $query);

$all_rows = [];
$daily_total = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $eth = gregorian_to_ethiopian($row['transaction_date']);
    $row['eth_time'] = $eth['time'];
    $all_rows[] = $row;
    $daily_total += $row['subtotal'];
}

// Handle Excel download
if (isset($_GET['export_excel'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="transactions_branch_' . $branch_id . '_' . $selected_date . '.xls"');
    
    echo '<html>';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body>';
    echo '<table border="1">';
    
    // Header with Branch Info
    echo '<tr><th colspan="8">ቅርንጫፍ: ' . htmlspecialchars($branch_name) . ' - የ' . $sel_day . ' ' . $ethiopian_months[$sel_month] . ' ' . $sel_year . ' ግብይቶች</th></tr>';
    echo '<tr>';
    echo '<th>ደረሰኝ #</th>';
    echo '<th>ሰዓት</th>';
    echo '<th>ሻጭ</th>';
    echo '<th>ክፍያ</th>';
    echo '<th>ዕቃ</th>';
    echo '<th>ብዛት</th>';
    echo '<th>ዋጋ</th>';
    echo '<th>ጠቅላላ</th>';
    echo '</tr>';
    
    foreach ($all_rows as $row) {
        echo '<tr>';
        echo '<td>#' . str_pad($row['id'], 6, '0', STR_PAD_LEFT) . '</td>';
        echo '<td>' . $row['eth_time'] . '</td>';
        echo '<td>' . $row['seller_name'] . '</td>';
        echo '<td>' . $row['payment_method'] . '</td>';
        echo '<td>' . $row['product_name'] . '</td>';
        echo '<td>' . number_format($row['quantity'], 2) . '</td>';
        echo '<td>' . number_format($row['unit_price'], 2) . '</td>';
        echo '<td>' . number_format($row['subtotal'], 2) . '</td>';
        echo '</tr>';
    }
    
    // Daily total
    echo '<tr><td colspan="7" align="right"><strong>የዕለቱ ድምር:</strong></td><td><strong>' . number_format($daily_total, 2) . '</strong></td></tr>';
    
    echo '</table>';
    echo '</body></html>';
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpg" href="image\photo_2026-01-12_07-44-10.jpg">
    <title>ዕለታዊ ግብይቶች - <?php echo htmlspecialchars($branch_name); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            margin: 10px;
            padding: 0;
            background: #fff;
        }
        .branch-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .branch-name {
            font-size: 18px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav {
            margin-bottom: 15px;
            padding: 8px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 5px;
        }
        .nav a, .nav button {
            padding: 6px 12px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        .nav a:hover, .nav button:hover {
            background: #45a049;
        }
        .nav .date {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .today-btn {
            background: #2196F3 !important;
        }
        .today-btn:hover {
            background: #0b7dda !important;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 13px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        th {
            background: #4CAF50;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 8px 10px;
            border: 1px solid #ddd;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        tr:hover {
            background: #f1f1f1;
        }
        .total-row {
            background: #e8f5e9 !important;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .branch-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 14px;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196F3;
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 3px;
            font-size: 14px;
        }
        @media print {
            .nav, .branch-header, .info-box, .today-btn { display: none; }
            body { margin: 0; padding: 10px; }
            th { background: #4CAF50 !important; color: white !important; }
        }
    </style>
</head>
<body>

<!-- Branch Header -->
<div class="branch-header">
    <div class="branch-name">
        <i class="fas fa-store"></i> ቅርንጫፍ: <?php echo htmlspecialchars($branch_name); ?>
    </div>
    <div class="branch-badge">
        <i class="fas fa-calendar-alt"></i> ዛሬ: <?php echo $current_ethiopian['day'] . ' ' . $current_ethiopian['month_name'] . ' ' . $current_ethiopian['year']; ?>
    </div>
</div>

<!-- Simple Navigation -->
<div class="nav">
    <a href="?date=<?php echo $prev_date; ?>">← ትናንት</a>
    <span class="date"><?php echo $sel_day . ' ' . $ethiopian_months[$sel_month] . ' ' . $sel_year; ?></span>
    <div>
        <?php if ($selected_date != $current_ethiopian['full_date']): ?>
        <a href="?date=<?php echo $current_ethiopian['full_date']; ?>" class="today-btn">📅 ዛሬ (<?php echo $current_ethiopian['day'] . ' ' . $current_ethiopian['month_name']; ?>)</a>
        <?php endif; ?>
        <a href="?date=<?php echo $next_date; ?>">ነገ →</a>
        <button onclick="window.print()">🖨️ ማተም</button>
        <a href="?export_excel=1&date=<?php echo $selected_date; ?>">📊 ኤክሴል</a>
        <a href="history.php">🔙 ተመለስ</a>
    </div>
</div>

<!-- Info message when viewing non-today date -->
<?php if ($selected_date != $current_ethiopian['full_date']): ?>
<div class="info-box">
    <i class="fas fa-info-circle"></i> 
    እየተመለከቱ ያሉት የቀን: <strong><?php echo $sel_day . ' ' . $ethiopian_months[$sel_month] . ' ' . $sel_year; ?></strong> ግብይቶች ነው። 
    ወደ ዛሬ ግብይቶች ለመመለስ "ዛሬ" የሚለውን ቁልፍ ይጫኑ።
</div>
<?php endif; ?>

<!-- Selected Day Transactions -->
<table>
    <thead>
        <tr>
            <th>ደረሰኝ #</th>
            <th>ሰዓት</th>
            <th>ሻጭ</th>
            <th>ክፍያ</th>
            <th>ዕቃ</th>
            <th class="text-right">ብዛት</th>
            <th class="text-right">ዋጋ</th>
            <th class="text-right">ጠቅላላ</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($all_rows)): ?>
        <tr>
            <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                በዚህ ቀን ምንም ግብይት የለም
                <br>
                <small style="font-size: 12px;"><?php echo $sel_day . ' ' . $ethiopian_months[$sel_month] . ' ' . $sel_year; ?></small>
            </td>
        </tr>
        <?php else: ?>
            <?php foreach ($all_rows as $row): ?>
            <tr>
                <td><strong>#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                <td><?php echo $row['eth_time']; ?></td>
                <td><?php echo htmlspecialchars($row['seller_name']); ?></td>
                <td><?php echo $row['payment_method']; ?></td>
                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                <td class="text-right"><?php echo number_format($row['quantity'], 2); ?></td>
                <td class="text-right"><?php echo number_format($row['unit_price'], 2); ?></td>
                <td class="text-right"><strong><?php echo number_format($row['subtotal'], 2); ?></strong></td>
            </tr>
            <?php endforeach; ?>
            <tr class="total-row">
                <td colspan="7" class="text-right"><strong>የዕለቱ ጠቅላላ ድምር:</strong></td>
                <td class="text-right"><strong><?php echo number_format($daily_total, 2); ?></strong></td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Add Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>
// Auto-refresh only if viewing today
<?php if ($selected_date == $current_ethiopian['full_date']): ?>
console.log('Auto-refresh enabled for today\'s view');
setTimeout(() => {
    location.reload();
}, 30000);
<?php endif; ?>
</script>

</body>
</html>
<?php 
mysqli_close($conn); 
?>