<?php
session_start();
require_once 'config.php';

// Check if user is admin/super_admin
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin';
$user_role = $_SESSION['role'] ?? '';


// ========== ETHIOPIAN DATE & TIME FUNCTIONS ==========
$ethiopian_months = array(
    1 => 'መስከረም',
    2 => 'ጥቅምት',
    3 => 'ህዳር',
    4 => 'ታህሳስ',
    5 => 'ጥር',
    6 => 'የካቲት',
    7 => 'መጋቢት',
    8 => 'ሚያዝያ',
    9 => 'ግንቦት',
    10 => 'ሰኔ',
    11 => 'ሐምሌ',
    12 => 'ነሐሴ',
    13 => 'ጳጉሜ'
);

// FIXED: Correct Ethiopian time conversion (Ethiopia is UTC+3)
function get_ethiopian_time_with_seconds() {
    // Get current UTC time and add 3 hours for Ethiopian time (UTC+3)
    $current_time = time();
    $utc_hour = gmdate('G', $current_time); // Get UTC hour
    $minute = gmdate('i', $current_time);
    $second = gmdate('s', $current_time);
    
    // Ethiopian time is UTC+3
    $eth_hour_24 = ($utc_hour + 3) % 24;
    
    // Convert to 12-hour Ethiopian format
    if ($eth_hour_24 == 0 || $eth_hour_24 == 12) {
        $eth_hour_12 = 12;
        $period = ($eth_hour_24 == 0) ? 'ጥዋት' : 'ከሰዓት';
    } elseif ($eth_hour_24 < 12) {
        $eth_hour_12 = $eth_hour_24;
        $period = 'ጥዋት';
    } else {
        $eth_hour_12 = $eth_hour_24 - 12;
        $period = 'ከሰዓት';
    }
    
    return sprintf('%d:%02d:%02d %s', $eth_hour_12, $minute, $second, $period);
}

function get_ethiopian_time_from_gregorian($gregorian_datetime) {
    $timestamp = strtotime($gregorian_datetime);
    $utc_hour = gmdate('G', $timestamp);
    $minute = gmdate('i', $timestamp);
    
    $eth_hour_24 = ($utc_hour + 3) % 24;
    
    if ($eth_hour_24 == 0 || $eth_hour_24 == 12) {
        $eth_hour_12 = 12;
        $period = ($eth_hour_24 == 0) ? 'ጥዋት' : 'ከሰዓት';
    } elseif ($eth_hour_24 < 12) {
        $eth_hour_12 = $eth_hour_24;
        $period = 'ጥዋት';
    } else {
        $eth_hour_12 = $eth_hour_24 - 12;
        $period = 'ከሰዓት';
    }
    
    return sprintf('%d:%02d %s', $eth_hour_12, $minute, $period);
}

function gregorian_to_ethiopian($gregorian_date) {
    global $ethiopian_months;
    
    $timestamp = strtotime($gregorian_date);
    $day = date('j', $timestamp);
    $month = date('n', $timestamp);
    $year = date('Y', $timestamp);
    
    $eth_year = $year - 7;
    
    if ($month < 9 || ($month == 9 && $day < 11)) {
        $eth_year--;
    }
    
    $days_since_sep11 = date('z', $timestamp) - date('z', strtotime("$year-09-11")) + 1;
    
    if ($days_since_sep11 < 0) {
        $days_since_sep11 += 365 + (date('L', $timestamp) ? 1 : 0);
    }
    
    $eth_month = ceil($days_since_sep11 / 30);
    if ($eth_month > 13) $eth_month = 13;
    
    $eth_day = $days_since_sep11 - (($eth_month - 1) * 30);
    if ($eth_day == 0) $eth_day = 30;
    
    return array(
        'year' => $eth_year,
        'month' => $eth_month,
        'month_name' => $ethiopian_months[$eth_month],
        'day' => $eth_day,
        'full_date' => $eth_year . ' ' . $ethiopian_months[$eth_month] . ' ' . $eth_day
    );
}

// Get current time and date
$current_eth_time = get_ethiopian_time_with_seconds();
$current_eth_date = gregorian_to_ethiopian(date('Y-m-d'))['full_date'];

// Check if table exists
$check_table = "SHOW TABLES LIKE 'admin_stock_receiving'";
$table_result = mysqli_query($conn, $check_table);
if (mysqli_num_rows($table_result) == 0) {
    $create_table = "CREATE TABLE `admin_stock_receiving` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `seller_id` INT(11) NOT NULL,
        `seller_name` VARCHAR(100) NOT NULL,
        `item_name` VARCHAR(255) NOT NULL,
        `quantity` DECIMAL(10,2) NOT NULL,
        `unit` VARCHAR(20) NOT NULL DEFAULT 'pcs',
        `source` VARCHAR(100) NOT NULL,
        `added_by` VARCHAR(100) NOT NULL,
        `date_added` DATETIME NOT NULL,
        `notes` TEXT DEFAULT NULL,
        PRIMARY KEY (`id`),
        INDEX `idx_date` (`date_added`),
        INDEX `idx_seller` (`seller_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    mysqli_query($conn, $create_table);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // FIXED: Save Single button now works properly
    if (isset($_POST['add_stock']) && !isset($_POST['add_multiple_stock'])) {
        // Get values from the FIRST row only for single save
        $item_name = isset($_POST['item_name'][0]) ? mysqli_real_escape_string($conn, $_POST['item_name'][0]) : '';
        $quantity = isset($_POST['quantity'][0]) ? floatval($_POST['quantity'][0]) : 0;
        $unit = isset($_POST['unit'][0]) ? mysqli_real_escape_string($conn, $_POST['unit'][0]) : 'pcs';
        $source = isset($_POST['source']) ? mysqli_real_escape_string($conn, $_POST['source']) : 'admin';
        $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
        
        if ($item_name && $quantity > 0) {
            $seller_id = isset($_POST['seller_id']) ? mysqli_real_escape_string($conn, $_POST['seller_id']) : $user_id;
            $seller_name = 'Unknown Seller';
            $seller_query = "SELECT full_name, username FROM users WHERE id = '$seller_id'";
            $seller_result = mysqli_query($conn, $seller_query);
            if ($seller_result && mysqli_num_rows($seller_result) > 0) {
                $seller_row = mysqli_fetch_assoc($seller_result);
                $seller_name = $seller_row['full_name'] ?: $seller_row['username'];
            }
            
            if ($source == 'other' && isset($_POST['custom_source']) && !empty($_POST['custom_source'])) {
                $source = mysqli_real_escape_string($conn, $_POST['custom_source']);
            }
            
            $added_by = $user_name . " (Admin)";
            $current_datetime = date('Y-m-d H:i:s');
            
            $insert_query = "INSERT INTO admin_stock_receiving (seller_id, seller_name, item_name, quantity, unit, source, added_by, date_added, notes) 
                             VALUES ('$seller_id', '$seller_name', '$item_name', '$quantity', '$unit', '$source', '$added_by', '$current_datetime', '$notes')";
            
            if (mysqli_query($conn, $insert_query)) {
                $_SESSION['message'] = "✅ Single stock item added successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "❌ Error adding stock: " . mysqli_error($conn);
                $_SESSION['message_type'] = "error";
            }
        } else {
            $_SESSION['message'] = "❌ Please fill all required fields for the first product";
            $_SESSION['message_type'] = "error";
        }
    }
    
    // FIXED: Save All Items button
    if (isset($_POST['add_multiple_stock'])) {
        $seller_id = isset($_POST['seller_id']) ? mysqli_real_escape_string($conn, $_POST['seller_id']) : $user_id;
        $seller_name = 'Unknown Seller';
        $seller_query = "SELECT full_name, username FROM users WHERE id = '$seller_id'";
        $seller_result = mysqli_query($conn, $seller_query);
        if ($seller_result && mysqli_num_rows($seller_result) > 0) {
            $seller_row = mysqli_fetch_assoc($seller_result);
            $seller_name = $seller_row['full_name'] ?: $seller_row['username'];
        }
        
        $source = isset($_POST['source']) ? mysqli_real_escape_string($conn, $_POST['source']) : 'admin';
        $notes = isset($_POST['notes']) ? mysqli_real_escape_string($conn, $_POST['notes']) : '';
        
        if ($source == 'other' && isset($_POST['custom_source']) && !empty($_POST['custom_source'])) {
            $source = mysqli_real_escape_string($conn, $_POST['custom_source']);
        }
        
        $item_names = isset($_POST['item_name']) ? $_POST['item_name'] : [];
        $quantities = isset($_POST['quantity']) ? $_POST['quantity'] : [];
        $units = isset($_POST['unit']) ? $_POST['unit'] : [];
        
        $success_count = 0;
        $total_items = count($item_names);
        
        for ($i = 0; $i < $total_items; $i++) {
            if (!empty($item_names[$i]) && isset($quantities[$i]) && floatval($quantities[$i]) > 0) {
                $item_name = mysqli_real_escape_string($conn, $item_names[$i]);
                $quantity = floatval($quantities[$i]);
                $unit = isset($units[$i]) ? mysqli_real_escape_string($conn, $units[$i]) : 'pcs';
                
                $added_by = $user_name . " (Admin)";
                $current_datetime = date('Y-m-d H:i:s');
                
                $insert_query = "INSERT INTO admin_stock_receiving (seller_id, seller_name, item_name, quantity, unit, source, added_by, date_added, notes) 
                                 VALUES ('$seller_id', '$seller_name', '$item_name', '$quantity', '$unit', '$source', '$added_by', '$current_datetime', '$notes')";
                
                if (mysqli_query($conn, $insert_query)) {
                    $success_count++;
                }
            }
        }
        
        if ($success_count > 0) {
            $_SESSION['message'] = "✅ $success_count በትክክል ተመዥግቧል";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "❌ No items added - please check your inputs";
            $_SESSION['message_type'] = "error";
        }
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get data
$products_list = [];
$products_query = "SELECT DISTINCT name FROM products WHERE is_active = 1 ORDER BY name";
$products_result = mysqli_query($conn, $products_query);
if ($products_result) {
    while($product = mysqli_fetch_assoc($products_result)) {
        $products_list[] = $product['name'];
    }
}

$sellers_list = [];
$sellers_query = "SELECT id, full_name, username FROM users WHERE role = 'seller' AND is_active = 1 ORDER BY full_name";
$sellers_result = mysqli_query($conn, $sellers_query);
if ($sellers_result) {
    while($seller = mysqli_fetch_assoc($sellers_result)) {
        $sellers_list[] = $seller;
    }
}

// Get stock data
$today_stock = [];
$yesterday_stock = [];
$all_stock = [];

$today_query = "SELECT * FROM admin_stock_receiving WHERE DATE(date_added) = CURDATE() ORDER BY date_added DESC";
$today_result = mysqli_query($conn, $today_query);
if ($today_result) {
    while($stock = mysqli_fetch_assoc($today_result)) {
        $stock['ethiopian_time'] = get_ethiopian_time_from_gregorian($stock['date_added']);
        $today_stock[] = $stock;
    }
}

$yesterday_query = "SELECT * FROM admin_stock_receiving WHERE DATE(date_added) = DATE(NOW() - INTERVAL 1 DAY) ORDER BY date_added DESC";
$yesterday_result = mysqli_query($conn, $yesterday_query);
if ($yesterday_result) {
    while($stock = mysqli_fetch_assoc($yesterday_result)) {
        $stock['ethiopian_time'] = get_ethiopian_time_from_gregorian($stock['date_added']);
        $yesterday_stock[] = $stock;
    }
}

$all_query = "SELECT * FROM admin_stock_receiving ORDER BY date_added DESC LIMIT 30";
$all_result = mysqli_query($conn, $all_query);
if ($all_result) {
    while($stock = mysqli_fetch_assoc($all_result)) {
        $eth_date = gregorian_to_ethiopian(date('Y-m-d', strtotime($stock['date_added'])));
        $stock['ethiopian_date'] = $eth_date['full_date'];
        $stock['ethiopian_time'] = get_ethiopian_time_from_gregorian($stock['date_added']);
        $all_stock[] = $stock;
    }
}

$count_query = "SELECT COUNT(*) as count FROM admin_stock_receiving";
$count_result = mysqli_query($conn, $count_query);
$total_count = 0;
if ($count_result) {
    $count_row = mysqli_fetch_assoc($count_result);
    $total_count = $count_row['count'] ?? 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" type="image/jpg" href="image/photo_2026-01-12_07-44-10.jpg">
    <title>Stock Receiving | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a56d4;
            --secondary: #7209b7;
            --success: #4cc9f0;
            --danger: #f72585;
            --warning: #f8961e;
            --info: #4895ef;
            --purple: #9d4edd;
            --bg-dark: #0f172a;
            --card-dark: #1e293b;
            --text-dark: #f1f5f9;
            --text-muted-dark: #94a3b8;
            --border-dark: #334155;
            --radius: 16px;
            --radius-sm: 8px;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--bg-dark) 0%, #1e293b 100%);
            color: var(--text-dark);
            min-height: 100vh;
            padding: 16px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--purple) 100%);
            border-radius: var(--radius);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo-text h1 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(to right, #fff, #e0e7ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo-text p {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 2px;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 50px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            color: white;
        }

        .user-details h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 2px;
        }

        .user-details .role {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 8px;
            border-radius: 10px;
        }

        .time-display {
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius);
            padding: 20px;
            text-align: center;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            z-index: 1;
        }

        .current-time {
            font-size: 36px;
            font-weight: 700;
            font-family: 'SF Mono', monospace;
            background: linear-gradient(to right, #fff, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 4px;
        }

        .current-date {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 8px;
        }

        .time-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
        }

        .nav {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }

        .nav-btn {
            flex: 1;
            padding: 14px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            transition: var(--transition);
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .nav-btn.logout {
            background: rgba(244, 63, 94, 0.2);
            border-color: rgba(244, 63, 94, 0.3);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card-dark);
            border-radius: var(--radius);
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            border: 1px solid var(--border-dark);
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-icon.total { background: linear-gradient(135deg, var(--primary), var(--purple)); }
        .stat-icon.today { background: linear-gradient(135deg, #4cc9f0, var(--info)); }
        .stat-icon.users { background: linear-gradient(135deg, var(--warning), #f9c74f); }
        .stat-icon.recent { background: linear-gradient(135deg, var(--danger), #ff6b6b); }

        .stat-content h3 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 4px;
            color: white;
        }

        .stat-content p {
            font-size: 13px;
            color: var(--text-muted-dark);
        }

        .alert {
            background: var(--card-dark);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid var(--border-dark);
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert.success {
            background: rgba(76, 201, 240, 0.1);
            border-color: rgba(76, 201, 240, 0.3);
        }

        .alert.error {
            background: rgba(247, 37, 133, 0.1);
            border-color: rgba(247, 37, 133, 0.3);
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .panel {
            background: var(--card-dark);
            border-radius: var(--radius);
            padding: 24px;
            border: 1px solid var(--border-dark);
        }

        .panel-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: white;
        }

        .panel-title i {
            color: var(--primary);
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: green
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background:black;
            border: 2px solid var(--border-dark);
            border-radius: var(--radius-sm);
            color: green;
            font-size: 14px;
            transition: var(--transition);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
            font-family: inherit;
            color: green ;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(245, 33, 26, 0.05);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .products-container {
            max-height: 300px;
            overflow-y: auto;
            border: 2px solid var(--border-dark);
            border-radius: var(--radius-sm);
            margin: 16px 0;
            background: rgba(255, 255, 255, 0.02);
        }

        .product-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 40px;
            gap: 12px;
            padding: 12px;
            border-bottom: 1px solid var(--border-dark);
            align-items: center;
        }

        .product-row:last-child {
            border-bottom: none;
        }

        .product-row input,
        .product-row select {
            width: 100%;
            padding: 10px 12px;
            background: var(--card-dark);
            border: 2px solid var(--border-dark);
            border-radius: 6px;
            color: var(--text-dark);
            font-size: 14px;
            transition: var(--transition);
        }

        .product-row input:focus,
        .product-row select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.1);
        }

        .btn-remove {
            width: 36px;
            height: 36px;
            background: rgba(247, 37, 133, 0.1);
            border: 2px solid rgba(247, 37, 133, 0.3);
            border-radius: 8px;
            color: var(--danger);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }

        .btn-remove:hover {
            background: rgba(247, 37, 133, 0.2);
            border-color: var(--danger);
        }

        .btn-add {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, var(--primary), var(--purple));
            border: none;
            border-radius: var(--radius-sm);
            color: white;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 12px;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, var(--primary-dark), var(--purple));
            box-shadow: 0 8px 16px rgba(67, 97, 238, 0.3);
        }

        .submit-btns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 24px;
        }

        .submit-btn {
            padding: 14px;
            border: none;
            border-radius: var(--radius-sm);
            color: white;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            transition: var(--transition);
        }

        .submit-btn.single {
            background: linear-gradient(135deg, #4cc9f0, var(--info));
        }

        .submit-btn.multiple {
            background: linear-gradient(135deg, var(--purple), var(--secondary));
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .tabs {
            display: flex;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--radius-sm);
            padding: 4px;
            margin-bottom: 20px;
        }

        .tab {
            flex: 1;
            padding: 12px 16px;
            background: transparent;
            border: none;
            border-radius: 8px;
            color: var(--text-muted-dark);
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: var(--transition);
        }

        .tab.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        }

        .table-container {
            max-height: 400px;
            overflow-y: auto;
            border: 2px solid var(--border-dark);
            border-radius: var(--radius-sm);
            background: rgba(255, 255, 255, 0.02);
        }

        .stock-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .stock-table th {
            background: var(--card-dark);
            padding: 16px 12px;
            text-align: left;
            font-weight: 600;
            color: var(--text-muted-dark);
            border-bottom: 2px solid var(--border-dark);
            position: sticky;
            top: 0;
        }

        .stock-table td {
            padding: 16px 12px;
            border-bottom: 1px solid var(--border-dark);
        }

        .stock-table tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .quantity-badge {
            background: rgba(76, 201, 240, 0.1);
            color: var(--success);
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            border: 1px solid rgba(76, 201, 240, 0.2);
        }

        .time-badge {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-dark);
            padding: 6px 12px;
            border-radius: 6px;
            font-family: 'SF Mono', monospace;
            font-size: 12px;
            border: 1px solid var(--border-dark);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted-dark);
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .footer {
            text-align: center;
            padding: 24px 0;
            margin-top: 32px;
            color: var(--text-muted-dark);
            font-size: 12px;
            border-top: 1px solid var(--border-dark);
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Debug time info */
        .debug-time {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.5);
            margin-top: 4px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-boxes"></i>
                    </div>
                    <div class="logo-text">
                        <h1>ምርት መመዝገቢያ</h1>
                        <p>Admin Management System</p>
                    </div>
                </div>
                
                <div class="user-profile">
                    <div class="avatar">
                        <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <h3><?php echo htmlspecialchars(substr($user_name, 0, 20)); ?></h3>
                        <span class="role"><?php echo strtoupper($user_role); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Current Time Display -->
            <div class="time-display">
                <div class="current-time" id="currentTime">
                    <?php echo $current_eth_time; ?>
                </div>
                <div class="current-date">
                    <?php echo $current_eth_date; ?> ዓ.ም
                </div>
               
            
            <!-- Navigation -->
            <div class="nav">
                <a href="admin_dashboard.php" class="nav-btn">
                    <i class="fas fa-home"></i>
                   ወደዋና ገጽ ለመመለስ 
                </a>
                
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert <?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                <i class="fas fa-<?php echo ($_SESSION['message_type'] ?? 'success') == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo $_SESSION['message']; ?></span>
                <?php unset($_SESSION['message']); ?>
                <?php unset($_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-database"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_count; ?></h3>
                    <p>Total Stock Entries</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon today">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($today_stock); ?></h3>
                    <p>Today's Entries</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($sellers_list); ?></h3>
                    <p>Active Sellers</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon recent">
                    <i class="fas fa-history"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($all_stock); ?></h3>
                    <p>Recent Entries</p>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="content-grid">
            <!-- Form Panel -->
            <div class="panel">
                <div class="panel-title">
                    <i class="fas fa-plus-circle"></i>
                    <span>አዲስ አቃ ለመመዝገብ</span>
                </div>
                
                <form method="POST" id="stockForm">
                    <div class="form-group">
                        <label class="form-label">ለማን እንደሚላክ ጸፍ</label>
                        <select name="seller_id" class="form-control" required>
                            <option value="">-- ምረጥ --</option>
                            <?php if(count($sellers_list) > 0): ?>
                                <?php foreach($sellers_list as $seller): ?>
                                    <option value="<?php echo $seller['id']; ?>">
                                        <?php echo htmlspecialchars($seller['username'] ?: $seller['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">No sellers found</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">ምንጭ</label>
                        <select name="source" class="form-control" required onchange="toggleSource(this)">
                            <option value="">--  ምረጥ --</option>
                            <option value="supplier">ከሻጮች</option>
                            <option value="farmer">ከፍርም</option>
                           
                            <option value="other">ሌላ</option>
                        </select>
                        <div id="customSource" style="display: none; margin-top: 12px;">
                            <input type="text" name="custom_source" class="form-control" placeholder="Enter source name">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">ማስታወሻ</label>
                        <textarea name="notes" class="form-control" rows="4" placeholder="Add notes..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">የእቃው ስም</label>
                        <div class="products-container" id="productContainer">
                            <div class="product-row">
                                <div>
                                    <input type="text" name="item_name[]" placeholder="የምርቱ ስም" list="ምርት" required>
                                </div>
                                <div>
                                    <input type="number" name="quantity[]" step="0.1" min="0.1" placeholder="ብዛት" required>
                                </div>
                                <div>
                                    <select name="unit[]">
                                        <option value="pcs">በፍሬ</option>
                                        <option value="kg">በኪሎግራም</option>
                                        <option value="g">በግራም</option>
                                        <option value="l">በሊትር</option>
                                    </select>
                                </div>
                                <div>
                                    <button type="button" class="btn-remove" onclick="removeRow(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <datalist id="products">
                            <?php foreach($products_list as $product): ?>
                                <option value="<?php echo htmlspecialchars($product); ?>">
                            <?php endforeach; ?>
                        </datalist>
                        <button type="button" class="btn-add" onclick="addRow()">
                            <i class="fas fa-plus"></i>
                            Add Product Row
                        </button>
                    </div>
                    
                    <div class="submit-btns">
                        <button type="submit" name="add_stock" class="submit-btn single">
                            <i class="fas fa-save"></i>
                            Save አንዷን እቃ ብቻ ለመመዝገብ 
                        </button>
                        <button type="submit" name="add_multiple_stock" class="submit-btn multiple">
                            <i class="fas fa-list"></i>
                              ሁሉንም በአንድ ይመዝግቡ
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- History Panel -->
            <div class="panel">
                <div class="panel-title">
                    <i class="fas fa-history"></i>
                    <span>Stock History</span>
                </div>
                
                <div class="tabs">
                    <button class="tab active" onclick="showTab('today')">
                        <i class="fas fa-sun"></i>
                        ዛሬ
                    </button>
                    <button class="tab" onclick="showTab('yesterday')">
                        <i class="fas fa-calendar"></i>
                        ትላንት
                    </button>
                    <button class="tab" onclick="showTab('all')">
                        <i class="fas fa-database"></i>
                        ሁሉም
                    </button>
                </div>
                
                <!-- Today Tab -->
                <div id="todayTab">
                    <?php if(!empty($today_stock)): ?>
                        <div class="table-container">
                            <table class="stock-table">
                                <thead>
                                    <tr>
                                        <th>ሻጭ</th>
                                        <th>የእቃው ስም</th>
                                        <th>ብዛት</th>
                                        <th>ሰዓት</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($today_stock as $stock): ?>
                                        <tr>
                                            <td>
                                                <div><?php echo htmlspecialchars(substr($stock['seller_name'], 0, 15)); ?></div>
                                                <small style="color: var(--text-muted-dark); font-size: 11px;">
                                                    ID: <?php echo $stock['seller_id']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars(substr($stock['item_name'], 0, 20)); ?></div>
                                                <small style="color: var(--text-muted-dark); font-size: 11px;">
                                                    <?php echo $stock['unit']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="quantity-badge">
                                                    <i class="fas fa-plus"></i>
                                                    <?php echo $stock['quantity']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="time-badge"><?php echo $stock['ethiopian_time']; ?></div>
                                                <small style="color: var(--text-muted-dark); font-size: 11px; display: block; margin-top: 2px;">
                                                    <?php echo date('H:i:s', strtotime($stock['date_added'])); ?> (Server)
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <div class="empty-text">ዛሬ ምንም አልተመዘገበም</div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Yesterday Tab -->
                <div id="yesterdayTab" style="display: none;">
                    <?php if(!empty($yesterday_stock)): ?>
                        <div class="table-container">
                            <table class="stock-table">
                                <thead>
                                    <tr>
                                        <th>Seller</th>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($yesterday_stock as $stock): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(substr($stock['seller_name'], 0, 15)); ?></td>
                                            <td>
                                                <div><?php echo htmlspecialchars(substr($stock['item_name'], 0, 20)); ?></div>
                                                <small style="color: var(--text-muted-dark); font-size: 11px;">
                                                    <?php echo $stock['unit']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="quantity-badge">
                                                    <i class="fas fa-plus"></i>
                                                    <?php echo $stock['quantity']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="time-badge"><?php echo $stock['ethiopian_time']; ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <div class="empty-text">No stock entries for yesterday</div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- All Tab -->
                <div id="allTab" style="display: none;">
                    <?php if(!empty($all_stock)): ?>
                        <div class="table-container">
                            <table class="stock-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Time</th>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($all_stock as $stock): ?>
                                        <tr>
                                            <td>
                                                <div style="font-size: 12px; font-weight: 500;"><?php echo $stock['ethiopian_date']; ?></div>
                                                <small style="color: var(--text-muted-dark); font-size: 11px;">
                                                    <?php echo htmlspecialchars(substr($stock['seller_name'], 0, 10)); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="time-badge"><?php echo $stock['ethiopian_time']; ?></div>
                                                <small style="color: var(--text-muted-dark); font-size: 10px; display: block; margin-top: 2px;">
                                                    <?php echo date('H:i', strtotime($stock['date_added'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars(substr($stock['item_name'], 0, 18)); ?></div>
                                                <small style="color: var(--text-muted-dark); font-size: 11px;">
                                                    <?php echo $stock['unit']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="quantity-badge">
                                                    <i class="fas fa-plus"></i>
                                                    <?php echo $stock['quantity']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon">
                                <i class="fas fa-inbox"></i>
                            </div>
                            <div class="empty-text">No stock entries found</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>አሌልቱ የእንስሳት ተዋጽኦ• © <?php echo date('Y'); ?></p>
        </div>
    </div>

    <script>
        // Tab system
        function showTab(tabName) {
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');
            
            document.getElementById('todayTab').style.display = 'none';
            document.getElementById('yesterdayTab').style.display = 'none';
            document.getElementById('allTab').style.display = 'none';
            document.getElementById(tabName + 'Tab').style.display = 'block';
        }
        
        // Toggle custom source field
        function toggleSource(select) {
            const customSource = document.getElementById('customSource');
            if (select.value === 'other') {
                customSource.style.display = 'block';
                customSource.querySelector('input').focus();
            } else {
                customSource.style.display = 'none';
            }
        }
        
        // Add product row
        function addRow() {
            const container = document.getElementById('productContainer');
            const row = document.createElement('div');
            row.className = 'product-row';
            row.innerHTML = `
                <div>
                    <input type="text" name="item_name[]" placeholder="Product name" list="products" required>
                </div>
                <div>
                    <input type="number" name="quantity[]" step="0.1" min="0.1" placeholder="Quantity" required>
                </div>
                <div>
                    <select name="unit[]">
                        <option value="pcs">Pieces</option>
                        <option value="kg">Kilograms</option>
                        <option value="g">Grams</option>
                        <option value="l">Liters</option>
                        <option value="box">Box</option>
                        <option value="bag">Bag</option>
                    </select>
                </div>
                <div>
                    <button type="button" class="btn-remove" onclick="removeRow(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            container.appendChild(row);
            
            // Focus on the new input
            const newInput = row.querySelector('input[name="item_name[]"]');
            setTimeout(() => newInput.focus(), 100);
        }
        
        // Remove product row
        function removeRow(button) {
            const rows = document.querySelectorAll('.product-row');
            if (rows.length > 1) {
                button.closest('.product-row').remove();
            } else {
                const row = button.closest('.product-row');
                row.querySelector('input[name="item_name[]"]').value = '';
                row.querySelector('input[name="quantity[]"]').value = '';
                row.querySelector('select[name="unit[]"]').value = 'pcs';
            }
        }
        
        // FIXED: Live Ethiopian time update (UTC+3)
        function updateEthiopianTime() {
            const now = new Date();
            const utcHour = now.getUTCHours();
            const minute = now.getMinutes();
            const second = now.getSeconds();
            
            // Ethiopian time is UTC+3
            let ethHour = utcHour + 3;
            if (ethHour >= 24) {
                ethHour = ethHour - 24;
            }
            
            let ethHour12, period;
            
            if (ethHour === 0) {
                ethHour12 = 12;
                period = 'ጥዋት';
            } else if (ethHour === 12) {
                ethHour12 = 12;
                period = 'ከሰዓት';
            } else if (ethHour < 12) {
                ethHour12 = ethHour;
                period = 'ጥዋት';
            } else {
                ethHour12 = ethHour - 12;
                period = 'ከሰዓት';
            }
            
            const timeStr = `${ethHour12}:${minute.toString().padStart(2,'0')}:${second.toString().padStart(2,'0')} ${period}`;
            document.getElementById('currentTime').textContent = timeStr;
            
            // Debug info
            const localHour = now.getHours();
            const debugInfo = `UTC: ${utcHour}:${minute.toString().padStart(2,'0')} → Ethiopian: ${timeStr} (Local: ${localHour}:${minute.toString().padStart(2,'0')})`;
            document.getElementById('debugTime').textContent = debugInfo;
        }
        
        // Update time every second
        setInterval(updateEthiopianTime, 1000);
        updateEthiopianTime();
        
        // Form validation
        document.getElementById('stockForm').addEventListener('submit', function(e) {
            const seller = document.querySelector('select[name="seller_id"]');
            if (!seller.value) {
                e.preventDefault();
                alert('Please select a seller');
                seller.focus();
                return false;
            }
            
            const source = document.querySelector('select[name="source"]');
            if (!source.value) {
                e.preventDefault();
                alert('Please select a source');
                source.focus();
                return false;
            }
            
            if (source.value === 'other') {
                const customSource = document.querySelector('input[name="custom_source"]');
                if (!customSource || !customSource.value.trim()) {
                    e.preventDefault();
                    alert('Please enter custom source name');
                    if (customSource) customSource.focus();
                    return false;
                }
            }
            
            // Check if at least one product is filled
            const items = document.querySelectorAll('input[name="item_name[]"]');
            const quantities = document.querySelectorAll('input[name="quantity[]"]');
            
            let hasValid = false;
            for (let i = 0; i < items.length; i++) {
                if (items[i].value.trim() && parseFloat(quantities[i].value) > 0) {
                    hasValid = true;
                    break;
                }
            }
            
            if (!hasValid) {
                e.preventDefault();
                alert('Please add at least one product with quantity');
                return false;
            }
            
            // Check which button was clicked
            const submitter = e.submitter || document.activeElement;
            if (submitter && submitter.name === 'add_stock') {
                // For single save, validate first row
                const firstItem = items[0].value.trim();
                const firstQty = parseFloat(quantities[0].value);
                
                if (!firstItem || firstQty <= 0) {
                    e.preventDefault();
                    alert('For single save, please fill the first product row');
                    items[0].focus();
                    return false;
                }
            }
        });
        
        // Auto-focus first product input when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const firstItemInput = document.querySelector('input[name="item_name[]"]');
            if (firstItemInput) {
                setTimeout(() => firstItemInput.focus(), 100);
            }
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>