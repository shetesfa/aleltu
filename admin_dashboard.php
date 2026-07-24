<?php
require_once 'config.php';

// ─── LOGIN CHECK ─────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}
// Block sellers from admin dashboard
if (($_SESSION['role'] ?? '') == 'seller') {
    header('Location: seller_pos.php');
    exit();
}
// ─────────────────────────────────────────────────────────────────────────────

// Load read-only flag for this user
loadReadOnlyFlag($conn, $_SESSION['user_id']);

// Users to exclude from counts (add IDs here)
$excluded_user_ids = [3]; // Only hide user ID 3 (owner)

// Get branch info
$user_branch = getUserBranch($conn, $_SESSION['user_id']);
$current_branch_id = getCurrentBranchId($conn, $_SESSION['user_id'], $_SESSION['role']);
$current_branch_name = getCurrentBranchName($conn, $current_branch_id);
if ($current_branch_id <= 0 && $user_role != 'super_admin') {
    die("Dashboard error: No branch access.");
}
// ETHIOPIAN TIME CONVERSION FUNCTIONS
function getEthiopianDateTime() {
    // Ethiopian time is UTC+3 (EAT - East Africa Time)
    $ethiopianTime = new DateTime('now', new DateTimeZone('Africa/Addis_Ababa'));
    return $ethiopianTime;
}

function formatEthiopianTime12Hour($dateTime) {
    // Get hour in 12-hour format
    $hour = $dateTime->format('g'); // 12-hour format without leading zeros
    $minute = $dateTime->format('i');
    $second = $dateTime->format('s');
    $ampm = $dateTime->format('A'); // AM or PM
    
    // Convert to Amharic period indicators
    $period_amharic = ($ampm == 'AM') ? 'ጥዋት' : 'ከሰዓት';
    
    return "{$hour}:{$minute}:{$second} {$period_amharic}";
}

function formatEthiopianDate($dateTime) {
    // Ethiopian date formatting
    $year = $dateTime->format('Y');
    $month = $dateTime->format('m');
    $day = $dateTime->format('d');
    
    // You could convert to Ethiopian calendar here if needed
    // For now, using Gregorian but in Ethiopian format
    $months_amharic = [
        '01' => 'ጃንዩወሪ', '02' => 'ፌብሩወሪ', '03' => 'ማርች', 
        '04' => 'ኤፕሪል', '05' => 'ሜይ', '06' => 'ጁን', 
        '07' => 'ጁላይ', '08' => 'ኦገስት', '09' => 'ሴፕቴምበር', 
        '10' => 'ኦክቶበር', '11' => 'ኖቬምበር', '12' => 'ዲሴምበር'
    ];
    
    $month_amharic = $months_amharic[$month] ?? $month;
    return "{$year} {$month_amharic} {$day}";
}

// Get current Ethiopian date and time
$ethiopianDateTime = getEthiopianDateTime();
$ethiopianDate = formatEthiopianDate($ethiopianDateTime);
$ethiopianTime12Hour = formatEthiopianTime12Hour($ethiopianDateTime);

// Get statistics - OPTIMIZED: 1 query instead of 7 subqueries = much faster
// Uses BETWEEN with date range instead of DATE() function so index is used
$today_start = date('Y-m-d 00:00:00');
$today_end   = date('Y-m-d 23:59:59');
$excl        = implode(',', $excluded_user_ids);

$stats_query = "
    SELECT
        COUNT(DISTINCT CASE WHEN DATE(transaction_date) >= CURDATE() THEN id END)          AS today_transactions,
        COALESCE(SUM(CASE WHEN transaction_date BETWEEN '$today_start' AND '$today_end' THEN total_amount ELSE 0 END), 0) AS today_sales,
        COUNT(*)                                                                            AS total_transactions,
        COALESCE(SUM(total_amount), 0)                                                     AS total_sales,
        COUNT(DISTINCT CASE WHEN seller_id NOT IN ($excl) THEN seller_id END)              AS active_sellers
    FROM transactions
    WHERE branch_id = $current_branch_id
";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Users and products are tiny tables - 2 fast queries, no performance issue
$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE id NOT IN ($excl) AND branch_id = $current_branch_id");
$stats['total_users'] = mysqli_fetch_assoc($r)['c'] ?? 0;

$r = mysqli_query($conn, "SELECT COUNT(*) as c FROM products WHERE branch_id = $current_branch_id AND (is_active IS NULL OR is_active = 1)");
$stats['total_products'] = mysqli_fetch_assoc($r)['c'] ?? 0;

// Get recent transactions - WITH BRANCH FILTER
$recent_query = "SELECT * FROM transactions WHERE branch_id = $current_branch_id ORDER BY transaction_date DESC LIMIT 10";
$recent_result = mysqli_query($conn, $recent_query);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="image\photo_2026-01-12_07-44-10.jpg">
    <title>Admin Dashboard - Aleltu POS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            height: 100vh;
            position: fixed;
            padding: 20px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .main-content {
            margin-left: 250px;
            padding: 25px;
            min-height: 100vh;
        }
        
        .logo {
            text-align: center;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .logo h2 {
            color: white;
            font-size: 24px;
            margin: 0;
        }
        
        .logo small {
            color: #bdc3c7;
            font-size: 12px;
        }
        
        .branch-badge-sidebar {
            background: rgba(52, 152, 219, 0.3);
            color: #ecf0f1;
            padding: 8px 12px;
            margin: 10px 15px;
            border-radius: 20px;
            font-size: 13px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .branch-badge-sidebar i {
            color: #f1c40f;
            margin-right: 5px;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0 15px;
        }
        
        .nav-menu li {
            margin-bottom: 5px;
        }
        
        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 15px;
            color: #ecf0f1;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 15px;
        }
        
        .nav-menu a:hover, .nav-menu a.active {
            background: rgba(52, 152, 219, 0.2);
            color: white;
            transform: translateX(5px);
        }
        
        .nav-menu a i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }
        
        .user-info {
            padding: 20px;
            text-align: center;
            border-top: 1px solid rgba(255,255,255,0.2);
            position: absolute;
            bottom: 0;
            width: 100%;
            background: rgba(0,0,0,0.1);
        }
        
        .user-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .user-role {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .logout-btn {
            display: inline-block;
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: #c0392b;
        }
        
        /* Ethiopian Clock Styles */
        .ethiopian-clock {
            background: rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .ethiopian-clock .date {
            font-size: 13px;
            color: #bdc3c7;
            margin-bottom: 5px;
        }
        
        .ethiopian-clock .time {
            font-size: 20px;
            font-weight: bold;
            color: #fff;
            font-family: 'Segoe UI', Arial, sans-serif;
            direction: ltr;
        }
        
        .ethiopian-clock .period {
            font-size: 12px;
            color: #f1c40f;
            margin-left: 5px;
        }
        
        /* Header */
        .header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 5px solid #3498db;
        }
        
        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .header p {
            color: #7f8c8d;
            font-size: 16px;
            margin: 0;
        }
        
        .branch-badge-header {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-top: 10px;
        }
        
        .branch-badge-header i {
            margin-right: 5px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            border-top: 4px solid;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .stat-card.sales { border-top-color: #27ae60; }
        .stat-card.total-sales { border-top-color: #3498db; }
        .stat-card.users { border-top-color: #9b59b6; }
        .stat-card.products { border-top-color: #f39c12; }
        .stat-card.transactions { border-top-color: #e74c3c; }
        .stat-card.sellers { border-top-color: #1abc9c; }
        
        .stat-card h3 {
            color: #7f8c8d;
            margin-bottom: 15px;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card .number {
            font-size: 42px;
            font-weight: 800;
            margin: 15px 0;
            color: #2c3e50;
        }
        
        .stat-card.sales .number { color: #27ae60; }
        .stat-card.total-sales .number { color: #3498db; }
        .stat-card.users .number { color: #9b59b6; }
        .stat-card.products .number { color: #f39c12; }
        .stat-card.transactions .number { color: #e74c3c; }
        .stat-card.sellers .number { color: #1abc9c; }
        
        .stat-card p {
            color: #95a5a6;
            font-size: 14px;
            margin: 0;
        }
        
        /* Quick Actions */
        .quick-actions {
            margin-bottom: 35px;
        }
        
        .quick-actions h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            text-decoration: none;
            color: #2c3e50;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            border-color: #3498db;
            background: #f8f9fa;
        }
        
        .action-card i {
            font-size: 32px;
            margin-bottom: 15px;
            color: #3498db;
        }
        
        .action-card h3 {
            font-size: 16px;
            margin: 0;
            font-weight: 600;
        }
        
        /* Recent Transactions */
        .recent-transactions {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h2 {
            color: #2c3e50;
            font-size: 22px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #2c3e50;
            color: white;
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        th:first-child { border-radius: 8px 0 0 0; }
        th:last-child { border-radius: 0 8px 0 0; }
        
        td {
            padding: 16px 20px;
            border-bottom: 1px solid #eee;
            color: #2c3e50;
            font-size: 14px;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .payment-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .amount {
            font-weight: 700;
            color: #27ae60;
            font-size: 16px;
        }
        
        .delete-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            transition: background 0.3s;
        }
        
        .delete-btn:hover {
            background: #c0392b;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            color: #95a5a6;
        }
        
        .no-data i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #bdc3c7;
        }
        
        .no-data h3 {
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        /* Welcome Box */
        .welcome-box {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        
        .welcome-box h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        
        .welcome-box p {
            margin: 0;
            opacity: 0.9;
        }
        
        /* Mobile top bar - hidden on desktop */
        .mobile-topbar {
            display: none;
        }
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.55);
            z-index: 1999;
        }
        .sidebar-overlay.active { display: block; }
        .sidebar-close-btn { display: none; }

        /* Responsive */
        @media (max-width: 768px) {
            .mobile-topbar {
                display: flex;
                align-items: center;
                gap: 12px;
                position: fixed;
                top: 0; left: 0; right: 0;
                height: 56px;
                background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
                padding: 0 15px;
                z-index: 1500;
                box-shadow: 0 2px 10px rgba(0,0,0,0.15);
            }
            .mobile-topbar h2 {
                color: white;
                font-size: 17px;
                margin: 0;
            }
            .mobile-menu-btn {
                background: rgba(255,255,255,0.12);
                border: none;
                color: white;
                width: 38px;
                height: 38px;
                border-radius: 8px;
                font-size: 18px;
                cursor: pointer;
                flex-shrink: 0;
            }

            .sidebar {
                width: 280px;
                height: 100vh;
                position: fixed;
                left: -300px;
                top: 0;
                overflow-y: auto;
                transition: left 0.3s ease;
                z-index: 2000;
                box-shadow: 5px 0 30px rgba(0,0,0,0.4);
            }
            .sidebar.mobile-open { left: 0; }
            .sidebar-close-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                position: absolute;
                top: 12px;
                right: 12px;
                width: 32px;
                height: 32px;
                background: rgba(255,255,255,0.1);
                border: none;
                color: white;
                border-radius: 8px;
                font-size: 16px;
                cursor: pointer;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
                padding-top: 72px;
            }

            .welcome-box { padding: 18px; margin-bottom: 15px; }
            .welcome-box h1 { font-size: 19px; }
            .welcome-box p { font-size: 13px; }

            .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 20px; }
            .stat-card { padding: 15px; }
            .stat-card h3 { font-size: 11px; margin-bottom: 8px; }
            .stat-card .number { font-size: 24px; margin: 8px 0; }
            .stat-card p { font-size: 11px; }

            .quick-actions { margin-bottom: 20px; }
            .quick-actions h2 { font-size: 18px; margin-bottom: 12px; }
            .actions-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .action-card { padding: 15px 10px; }
            .action-card i { font-size: 24px; margin-bottom: 8px; }
            .action-card h3 { font-size: 13px; }

            .recent-transactions { padding: 15px; overflow: visible; }
            .section-header { flex-wrap: wrap; gap: 8px; }
            .section-header h2 { font-size: 17px; }

            /* Recent transactions table -> stacked cards */
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            table { border: none; }
            tr {
                background: white;
                border: 1px solid #eee;
                border-radius: 10px;
                margin-bottom: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
                overflow: hidden;
            }
            td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 10px;
                padding: 10px 14px;
                text-align: right;
                border-bottom: 1px solid #f1f1f1;
            }
            td:last-child { border-bottom: none; }
            td::before {
                content: attr(data-label);
                font-weight: 700;
                color: #7f8c8d;
                font-size: 10px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                text-align: left;
            }
            td[data-label="Date & Time"] { font-size: 12px; }
        }

        @media (max-width: 420px) {
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Mobile top bar (hidden on desktop) -->
    <div class="mobile-topbar">
        <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h2>ALELTU POS</h2>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <button class="sidebar-close-btn" onclick="closeSidebar()"><i class="fas fa-times"></i></button>
        <div class="logo">
            <h2>ALELTU POS</h2>
            <small>Admin Panel</small>
        </div>
        
        <!-- Branch Badge in Sidebar -->
        <div class="branch-badge-sidebar">
            <i class="fas fa-store"></i> <?php echo htmlspecialchars($current_branch_name); ?>
        </div>
        
        <ul class="nav-menu">
        <li><a href="seller_pos.php">
                <i class="fas fa-shopping-cart"></i> መሸጫ (POS)
            </a></li>
           
           
            <li><a href="manage_users.php">
                <i class="fas fa-users"></i> ተጠቃሚ ለማስተዳደር
            </a></li>
            <li><a href="register_user.php">
                <i class="fas fa-user-plus"></i> አዲስ ሰው ለመመዝገብ
            </a></li>
            
             <li><a href="history.php">
                <i class="fas fa-list-alt"></i> የሽያጭ መዝገብ
            </a></li>
        </ul>
        
        <div class="user-info">
            <p><strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin User'); ?></strong></p>
            <p><span class="user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Admin'); ?></span></p>
            
            <!-- Ethiopian Clock in Sidebar -->
            <div class="ethiopian-clock">
                <div class="date" id="ethiopian-date-sidebar">
                    <?php echo $ethiopianDate; ?>
                </div>
                <div class="time" id="ethiopian-time-sidebar">
                    <?php 
                    echo explode(' ', $ethiopianTime12Hour)[0]; // Time part only
                    ?>
                    <span class="period" id="ethiopian-period-sidebar">
                        <?php 
                        echo explode(' ', $ethiopianTime12Hour)[1]; // Period part only
                        ?>
                    </span>
                </div>
            </div>
            
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Box -->
        <div class="welcome-box" style="text-align: center;">
            <h1>እንኳን ደህና መጣህ <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></h1>
            <p>የአሌልቱ መቆጣጠሪያ - <i class="fas fa-store"></i> <?php echo htmlspecialchars($current_branch_name); ?></p>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card sales">
                <h3>የዛሬ ሽያጭ</h3>
                <div class="number"><?php echo number_format($stats['today_sales'] ?? 0, 2); ?> ETB</div>
                <p><?php echo $stats['today_transactions'] ?? 0; ?> transactions today</p>
            </div>
            
            <div class="stat-card total-sales">
                <h3>ጠቅላላ ሽያጭ</h3>
                <div class="number"><?php echo number_format($stats['total_sales'] ?? 0, 2); ?> ETB</div>
                <p>All time revenue</p>
            </div>
            
            <div class="stat-card users">
                <h3>ጠቅላላ ተጠቃሚ</h3>
                <div class="number"><?php echo $stats['total_users'] ?? 0; ?></div>
                <p>Registered users</p>
            </div>
            
            <div class="stat-card products">
                <h3>ምርቶቻችን</h3>
                <div class="number"><?php echo $stats['total_products'] ?? 0; ?></div>
                <p>Available items</p>
            </div>
            
            <div class="stat-card transactions">
                <h3>ያለቁ ሽያጮች</h3>
                <div class="number"><?php echo $stats['total_transactions'] ?? 0; ?></div>
                <p>ተፈጽመዋል</p>
            </div>
            
            <div class="stat-card sellers">
                <h3>በስራ ላይ ያሉ ሻጮች</h3>
                <div class="number"><?php echo $stats['active_sellers'] ?? 0; ?></div>
                <p>የዛሬ ሻጮች</p>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="actions-grid">
                <a href="seller_pos.php" class="action-card">
                    <i class="fas fa-list-alt"></i>
                    <h3>መሸጫ (POS)</h3>
                </a>
                <a href="history.php" class="action-card">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>የሽያጭ መዝገብ</h3>
                </a>
                <a href="manage_users.php" class="action-card">
                    <i class="fas fa-users"></i>
                    <h3>ተጠቃሚ ለማስተዳደር</h3>
                </a>
                <?php if (!isReadOnly()): ?>
                <a href="register_user.php" class="action-card">
                    <i class="fas fa-user-plus"></i>
                    <h3>አዲስ ሰው ለመመዝገብ</h3>
                </a>
                
                <a href="daily_cashier.php" class="action-card">
                    <i class="fas fa-user-plus"></i>
                    <h3>ወጪ ለመመዝገብ</h3>
                </a>
                <?php endif; ?>
                                             
                <a href="admin_view_stock.php" class="action-card">
                    <i class="fas fa-boxes"></i>
                    <h3>ክምችት ማየት</h3>
                </a>
                <a href="excel_all_in_one.php" class="action-card">
                    <i class="fas fa-boxes"></i>
                    <h3>መዝገብ መጻፊያ</h3>
                </a>
                <a href="admin_dashboard_new.php" class="action-card">
                    <i class="fas fa-undo-alt"></i>
                    <h3>መቆጣጠሪያ</h3>
                </a>
                
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="recent-transactions">
            <div class="section-header">
                <h2><i class="fas fa-history"></i> Recent Transactions - <?php echo htmlspecialchars($current_branch_name); ?></h2>
                <a href="history.php" style="color:#3498db;text-decoration:none;font-size:14px;">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <?php if(mysqli_num_rows($recent_result) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date & Time</th>
                            <th>Seller</th>
                            <th>Amount</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($recent_result)): ?>
                        <tr>
                            <td data-label="ID">#<?php echo str_pad($row['id'], 6, '0', STR_PAD_LEFT); ?></td>
                            <td data-label="Date & Time"><?php echo date('Y-m-d H:i', strtotime($row['transaction_date'])); ?></td>
                            <td data-label="Seller"><?php echo htmlspecialchars($row['seller_name'] ?? 'N/A'); ?></td>
                            <td class="amount" data-label="Amount"><?php echo number_format($row['total_amount'], 2); ?> ETB</td>
                            <td data-label="Payment">
                                <span class="payment-badge">
                                    <?php 
                                    $payment_names = [
                                        'cash' => '💵 Cash',
                                        'telebirr' => '📱 Telebirr',
                                        'cbe' => '🏦 CBE',
                                        'abyssinia' => '🏛️ Abyssinia',
                                        ];
                                    echo $payment_names[$row['payment_method']] ?? htmlspecialchars($row['payment_method']);
                                    ?>
                                </span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-receipt"></i>
                    <h3>No transactions found</h3>
                    <p>Start making sales to see transaction history</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- System Status -->
        <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 8px; border: 1px solid #ffeaa7;">
            <h3 style="color:#856404; margin:0 0 10px 0;"><i class="fas fa-info-circle"></i> System Status</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <div style="background:white;padding:10px;border-radius:5px;">
                    <small style="color:#7f8c8d;">Database</small>
                    <div style="color:#27ae60;font-weight:bold;">✅ Connected</div>
                </div>
                <div style="background:white;padding:10px;border-radius:5px;">
                    <small style="color:#7f8c8d;">System Time</small>
                    <div style="color:#3498db;font-weight:bold;" id="system-time-status">
                        <?php echo $ethiopianTime12Hour; ?>
                    </div>
                </div>
                <div style="background:white;padding:10px;border-radius:5px;">
                    <small style="color:#7f8c8d;">Current Branch</small>
                    <div style="color:#9b59b6;font-weight:bold;"><?php echo htmlspecialchars($current_branch_name); ?></div>
                </div>
                <div style="background:white;padding:10px;border-radius:5px;">
                    <small style="color:#7f8c8d;">Total Records</small>
                    <div style="color:#9b59b6;font-weight:bold;"><?php echo $stats['total_transactions'] ?? 0; ?> transactions</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar) sidebar.classList.toggle('mobile-open');
            if (overlay) overlay.classList.toggle('active');
        }

        function closeSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebarOverlay');
            if (sidebar) sidebar.classList.remove('mobile-open');
            if (overlay) overlay.classList.remove('active');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeSidebar();
        });

        // Function to update Ethiopian time every second
        function updateEthiopianTime() {
            const now = new Date();
            
            // Convert to Ethiopian time (UTC+3)
            const ethiopianTime = new Date(now.getTime() + (3 * 60 * 60 * 1000));
            
            // Get hours in 12-hour format
            let hours = ethiopianTime.getUTCHours();
            let minutes = ethiopianTime.getUTCMinutes();
            let seconds = ethiopianTime.getUTCSeconds();
            
            // Convert to 12-hour format
            let period = hours >= 12 ? 'ከሰዓት' : 'ጥዋት';
            hours = hours % 12;
            hours = hours ? hours : 12; // Convert 0 to 12
            
            // Add leading zeros
            hours = hours < 10 ? '0' + hours : hours;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            seconds = seconds < 10 ? '0' + seconds : seconds;
            
            const timeString = `${hours}:${minutes}:${seconds}`;
            
            // Update sidebar clock elements
            const timeElements = document.querySelectorAll('#ethiopian-time-sidebar');
            const periodElements = document.querySelectorAll('#ethiopian-period-sidebar');
            const systemTimeElement = document.getElementById('system-time-status');
            
            timeElements.forEach(el => {
                el.innerHTML = timeString;
            });
            
            periodElements.forEach(el => {
                el.innerHTML = period;
            });
            
            if (systemTimeElement) {
                systemTimeElement.innerHTML = `${timeString} ${period}`;
            }
        }
        
        // Initial call
        updateEthiopianTime();
        
        // Update every second
        setInterval(updateEthiopianTime, 1000);
        
        // Original functions
        function viewTransaction(id) {
            window.location.href = 'transaction_details.php?id=' + id;
        }
        
        function deleteTransaction(id) {
            if(confirm('Are you sure you want to delete this transaction?\nThis action cannot be undone.')) {
                window.location.href = 'delete_transaction.php?id=' + id;
            }
        }
        
        // Auto-refresh dashboard every 30 seconds
        setInterval(function() {
            // Refresh stats without reloading page (optional enhancement)
            console.log('Dashboard auto-refreshed at ' + new Date().toLocaleTimeString());
        }, 30000);
    </script>
</body>
</html>
<?php 
mysqli_close($conn); 
?>