<?php
session_start();
require_once 'admin_config.php'; // Different config file for admin database

// Check if user is admin
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin';
$admin_role = $_SESSION['role'] ?? '';

// Only admin can access
if (!$admin_id || $admin_role !== 'admin') {
    header("Location: admin_login.php");
    exit();
}

// DEBUG: Show database info
echo "<!-- DEBUG: Admin ID: $admin_id, Admin Name: $admin_name -->";

// ========== CHECK IF ADMIN TABLES EXIST ==========
$check_admin_logs = "SHOW TABLES LIKE 'admin_stock_logs'";
$admin_logs_result = mysqli_query($conn, $check_admin_logs);
$admin_logs_exists = mysqli_num_rows($admin_logs_result) > 0;

$check_admin_inventory = "SHOW TABLES LIKE 'admin_inventory'";
$admin_inventory_result = mysqli_query($conn, $check_admin_inventory);
$admin_inventory_exists = mysqli_num_rows($admin_inventory_result) > 0;

echo "<!-- DEBUG: admin_stock_logs exists: " . ($admin_logs_exists ? 'YES' : 'NO') . " -->";
echo "<!-- DEBUG: admin_inventory exists: " . ($admin_inventory_exists ? 'YES' : 'NO') . " -->";

// ========== SIMPLE ETHIOPIAN DATE ==========
function get_ethiopian_today() {
    $gregorian = date('Y-m-d');
    $timestamp = strtotime($gregorian);
    $day = date('j', $timestamp);
    $month = date('n', $timestamp);
    $year = date('Y', $timestamp);
    
    $eth_year = $year - 8;
    if ($month < 9 || ($month == 9 && $day < 11)) {
        $eth_year--;
    }
    
    return $eth_year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
}

$today_ethiopian = get_ethiopian_today();

// ========== HANDLE ADDING RECEIVED STOCK ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_stock'])) {
    $item_name = mysqli_real_escape_string($conn, $_POST['item_name']);
    $quantity = floatval($_POST['quantity']);
    $unit = mysqli_real_escape_string($conn, $_POST['unit']);
    $source = mysqli_real_escape_string($conn, $_POST['source']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    // If source is "other", get custom source
    if ($source == 'other' && !empty($_POST['custom_source'])) {
        $source = mysqli_real_escape_string($conn, $_POST['custom_source']);
    }
    
    echo "<!-- DEBUG: Adding admin stock - Item: $item_name, Qty: $quantity, Unit: $unit, Source: $source -->";
    
    if ($admin_inventory_exists) {
        // Add to admin inventory (separate table from sellers)
        $inventory_query = "INSERT INTO admin_inventory (admin_id, item_name, current_stock, unit, last_updated) 
                           VALUES ('$admin_id', '$item_name', '$quantity', '$unit', NOW())
                           ON DUPLICATE KEY UPDATE 
                           current_stock = current_stock + VALUES(current_stock),
                           unit = VALUES(unit),
                           last_updated = NOW()";
        
        $inventory_success = mysqli_query($conn, $inventory_query);
        echo "<!-- DEBUG: Admin inventory update: " . ($inventory_success ? 'SUCCESS' : 'FAILED - ' . mysqli_error($conn)) . " -->";
    } else {
        echo "<!-- DEBUG: admin_inventory table doesn't exist -->";
        $inventory_success = false;
    }
    
    if ($admin_logs_exists && $inventory_success) {
        // Add to admin_stock_logs (separate table from sellers)
        $current_datetime = date('Y-m-d H:i:s');
        $log_query = "INSERT INTO admin_stock_logs (admin_id, admin_name, item_name, quantity, unit, source, added_by, date_added, notes) 
                     VALUES ('$admin_id', '$admin_name', '$item_name', '$quantity', '$unit', '$source', '$admin_name', '$current_datetime', '$notes')";
        $log_success = mysqli_query($conn, $log_query);
        echo "<!-- DEBUG: Admin stock log: " . ($log_success ? 'SUCCESS' : 'FAILED - ' . mysqli_error($conn)) . " -->";
        
        if ($log_success) {
            $_SESSION['message'] = "✅ Stock received successfully! Added to admin inventory.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "❌ Error logging admin stock: " . mysqli_error($conn);
            $_SESSION['message_type'] = "danger";
        }
    } elseif (!$admin_logs_exists) {
        $_SESSION['message'] = "⚠️ Stock added but admin log table not found. Please contact system administrator.";
        $_SESSION['message_type'] = "warning";
    }
    
    header("Location: admin_receive_stock.php");
    exit();
}

// ========== GET ADMIN PRODUCTS FOR DROPDOWN ==========
$products_list = [];
$products_query = "SELECT name FROM admin_products WHERE is_active = 1 ORDER BY name";
$products_result = mysqli_query($conn, $products_query);
if ($products_result) {
    while($product = mysqli_fetch_assoc($products_result)) {
        $products_list[] = $product['name'];
    }
}

// ========== GET ADMIN STOCK DATA ==========
$today_stock = [];
$yesterday_stock = [];
$all_stock = [];
$inventory_count = 0;

if ($admin_logs_exists) {
    // Get today's admin stock
    $today_query = "SELECT * FROM admin_stock_logs 
                   WHERE admin_id = '$admin_id' 
                   AND DATE(date_added) = CURDATE()
                   ORDER BY date_added DESC";
    $today_result = mysqli_query($conn, $today_query);
    if ($today_result) {
        while($stock = mysqli_fetch_assoc($today_result)) {
            $today_stock[] = $stock;
        }
    }
    
    // Get yesterday's admin stock
    $yesterday_query = "SELECT * FROM admin_stock_logs 
                       WHERE admin_id = '$admin_id' 
                       AND DATE(date_added) = DATE(NOW() - INTERVAL 1 DAY)
                       ORDER BY date_added DESC";
    $yesterday_result = mysqli_query($conn, $yesterday_query);
    if ($yesterday_result) {
        while($stock = mysqli_fetch_assoc($yesterday_result)) {
            $yesterday_stock[] = $stock;
        }
    }
    
    // Get all admin stock history
    $all_query = "SELECT * FROM admin_stock_logs 
                 WHERE admin_id = '$admin_id' 
                 ORDER BY date_added DESC 
                 LIMIT 100";
    $all_result = mysqli_query($conn, $all_query);
    if ($all_result) {
        while($stock = mysqli_fetch_assoc($all_result)) {
            $all_stock[] = $stock;
        }
    }
}

if ($admin_inventory_exists) {
    $inventory_query = "SELECT COUNT(*) as count FROM admin_inventory WHERE admin_id = '$admin_id'";
    $inventory_count_result = mysqli_query($conn, $inventory_query);
    if ($inventory_count_result) {
        $count_row = mysqli_fetch_assoc($inventory_count_result);
        $inventory_count = $count_row['count'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="image/photo_2026-01-12_07-44-10.jpg">
    <title>Admin Stock Management - <?php echo $admin_name; ?></title>
    <style>
        :root {
            --admin-dark: #0f172a;
            --admin-darker: #020617;
            --admin-primary: #1e40af;
            --admin-accent: #7c3aed;
            --admin-secondary: #2563eb;
            --admin-success: #059669;
            --admin-warning: #d97706;
            --admin-danger: #dc2626;
            --admin-info: #0891b2;
            --admin-light: #f8fafc;
            --admin-gray: #475569;
            --admin-border: #334155;
            --admin-text: #f1f5f9;
            --admin-text-light: #94a3b8;
            --admin-card-bg: rgba(30, 41, 59, 0.7);
            --border-radius: 16px;
            --border-radius-sm: 10px;
            --shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 30px 60px rgba(0, 0, 0, 0.5);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            --glass-bg: rgba(15, 23, 42, 0.8);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            padding: 20px;
            color: var(--admin-text);
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(124, 58, 237, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(37, 99, 235, 0.1) 0%, transparent 50%);
            z-index: -1;
        }

        .admin-container {
            max-width: 1600px;
            margin: 0 auto;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--glass-border);
            overflow: hidden;
            position: relative;
        }

        .admin-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--admin-accent), transparent);
        }

        /* Admin Header */
        .admin-header {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.9) 100%);
            padding: 35px 40px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            position: relative;
        }

        .header-grid {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 30px;
            align-items: center;
        }

        @media (max-width: 1024px) {
            .header-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }

        .admin-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-logo {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, var(--admin-accent), var(--admin-secondary));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            font-weight: bold;
            box-shadow: 0 10px 20px rgba(124, 58, 237, 0.3);
        }

        .brand-text h1 {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .brand-text .tagline {
            font-size: 0.9rem;
            color: var(--admin-text-light);
            font-weight: 500;
        }

        .admin-date {
            text-align: center;
        }

        .date-badge {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.05);
            padding: 12px 24px;
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
            background: rgba(255, 255, 255, 0.05);
            padding: 15px 20px;
            border-radius: var(--border-radius-sm);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }

        .profile-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-accent));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 20px;
        }

        .profile-info h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .profile-info .role-tag {
            background: linear-gradient(135deg, var(--admin-success), #10b981);
            color: white;
            padding: 3px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        /* Admin Navigation */
        .admin-nav {
            display: flex;
            gap: 10px;
            padding: 0 40px 20px;
            flex-wrap: wrap;
        }

        .nav-btn {
            padding: 12px 24px;
            border-radius: var(--border-radius-sm);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(255, 255, 255, 0.05);
            color: var(--admin-text);
            transition: var(--transition);
            font-size: 0.9rem;
        }

        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .nav-btn-primary {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            border: none;
        }

        .nav-btn-primary:hover {
            background: linear-gradient(135deg, var(--admin-secondary), var(--admin-primary));
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }

        /* Alert Messages */
        .admin-alert {
            margin: 0 40px 25px;
            padding: 20px 25px;
            border-radius: var(--border-radius-sm);
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideDown 0.4s ease;
            backdrop-filter: blur(10px);
        }

        @keyframes slideDown {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .alert-success {
            background: rgba(5, 150, 105, 0.1);
            border-color: rgba(5, 150, 105, 0.3);
            color: #34d399;
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            border-color: rgba(220, 38, 38, 0.3);
            color: #f87171;
        }

        .alert-warning {
            background: rgba(217, 119, 6, 0.1);
            border-color: rgba(217, 119, 6, 0.3);
            color: #fbbf24;
        }

        /* Stats Dashboard */
        .stats-dashboard {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 0 40px 30px;
        }

        @media (max-width: 1200px) {
            .stats-dashboard {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .stats-dashboard {
                grid-template-columns: 1fr;
            }
        }

        .stat-card {
            background: var(--admin-card-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 25px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--admin-accent), var(--admin-primary));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--admin-accent), var(--admin-secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: 900;
            margin: 10px 0;
            background: linear-gradient(135deg, #fff, var(--admin-text-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--admin-text-light);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }

        /* Main Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 0 40px 40px;
        }

        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Form Panel */
        .form-panel {
            background: var(--admin-card-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 35px;
            transition: var(--transition);
        }

        .form-panel:hover {
            border-color: rgba(255, 255, 255, 0.15);
        }

        .panel-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .panel-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .panel-title {
            font-size: 1.6rem;
            font-weight: 700;
            color: white;
        }

        .panel-subtitle {
            font-size: 0.95rem;
            color: var(--admin-text-light);
            margin-top: 5px;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--admin-text);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-label i {
            color: var(--admin-accent);
            width: 20px;
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            color: var(--admin-text);
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--admin-accent);
            background: rgba(124, 58, 237, 0.05);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
        }

        .form-control::placeholder {
            color: var(--admin-text-light);
        }

        .quantity-unit-group {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 15px;
        }

        .custom-source-group {
            margin-top: 15px;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .custom-source-group.show {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, var(--admin-success), #10b981);
            color: white;
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .submit-btn:hover::before {
            left: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(5, 150, 105, 0.3);
        }

        .submit-btn:disabled {
            background: var(--admin-gray);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* History Panel */
        .history-panel {
            background: var(--admin-card-bg);
            border: 1px solid var(--glass-border);
            border-radius: var(--border-radius);
            padding: 35px;
            display: flex;
            flex-direction: column;
            max-height: 850px;
            overflow: hidden;
        }

        /* Tabs */
        .admin-tabs {
            display: flex;
            background: rgba(255, 255, 255, 0.03);
            border-radius: var(--border-radius-sm);
            padding: 5px;
            margin-bottom: 25px;
        }

        .admin-tab {
            flex: 1;
            padding: 18px;
            border: none;
            background: transparent;
            color: var(--admin-text-light);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border-radius: calc(var(--border-radius-sm) - 2px);
        }

        .admin-tab:hover {
            color: var(--admin-text);
        }

        .admin-tab.active {
            background: rgba(124, 58, 237, 0.1);
            color: white;
            box-shadow: 0 5px 15px rgba(124, 58, 237, 0.2);
        }

        .tab-content {
            display: none;
            flex: 1;
            overflow-y: auto;
            padding-right: 10px;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        /* Stock Tables */
        .table-container {
            overflow-y: auto;
            max-height: 650px;
            border-radius: var(--border-radius-sm);
            border: 1px solid rgba(255, 255, 255, 0.05);
            background: rgba(255, 255, 255, 0.02);
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .admin-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .admin-table th {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-darker));
            color: white;
            padding: 20px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            white-space: nowrap;
        }

        .admin-table td {
            padding: 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }

        .admin-table tr:hover {
            background: rgba(124, 58, 237, 0.05);
        }

        /* Badges */
        .badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-admin {
            background: rgba(37, 99, 235, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(37, 99, 235, 0.3);
        }

        .badge-supplier {
            background: rgba(5, 150, 105, 0.15);
            color: #34d399;
            border: 1px solid rgba(5, 150, 105, 0.3);
        }

        .badge-farmer {
            background: rgba(217, 119, 6, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(217, 119, 6, 0.3);
        }

        .badge-other {
            background: rgba(148, 163, 184, 0.15);
            color: #cbd5e1;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .quantity-cell {
            font-weight: 700;
            font-size: 1.1rem;
            color: #34d399;
        }

        /* Date Headers */
        .date-header {
            font-size: 1.3rem;
            font-weight: 700;
            color: white;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .date-tag {
            background: linear-gradient(135deg, var(--admin-accent), var(--admin-primary));
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--admin-text-light);
        }

        .empty-icon {
            font-size: 5rem;
            margin-bottom: 25px;
            opacity: 0.3;
            background: linear-gradient(135deg, var(--admin-accent), var(--admin-primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: white;
            font-weight: 600;
        }

        .empty-state p {
            font-size: 1rem;
            max-width: 300px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Warning Box */
        .warning-box {
            background: rgba(217, 119, 6, 0.1);
            border: 1px solid rgba(217, 119, 6, 0.3);
            border-radius: var(--border-radius-sm);
            padding: 20px;
            margin: 0 40px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            backdrop-filter: blur(10px);
        }

        .warning-icon {
            color: #fbbf24;
            font-size: 1.5rem;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.03);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-accent));
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--admin-accent), var(--admin-primary));
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .admin-header,
            .admin-nav,
            .content-grid,
            .stats-dashboard {
                padding-left: 20px;
                padding-right: 20px;
            }
            
            .form-panel,
            .history-panel {
                padding: 25px;
            }
            
            .quantity-unit-group {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Admin Header -->
        <div class="admin-header">
            <div class="header-grid">
                <div class="admin-brand">
                    <div class="admin-logo">A</div>
                    <div class="brand-text">
                        <h1>Admin Stock</h1>
                        <div class="tagline">Management Dashboard</div>
                    </div>
                </div>
                
                <div class="admin-date">
                    <div class="date-badge">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php echo $today_ethiopian; ?> E.C</span>
                    </div>
                </div>
                
                <div class="admin-profile">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h3><?php echo htmlspecialchars($admin_name); ?></h3>
                        <span class="role-tag">ADMINISTRATOR</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Admin Navigation -->
        <div class="admin-nav">
            <a href="admin_dashboard.php" class="nav-btn">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="admin_receive_stock.php" class="nav-btn nav-btn-primary">
                <i class="fas fa-truck-loading"></i> Receive Stock
            </a>
            <a href="admin_inventory.php" class="nav-btn">
                <i class="fas fa-warehouse"></i> Inventory
            </a>
            <a href="admin_sales.php" class="nav-btn">
                <i class="fas fa-chart-line"></i> Sales Report
            </a>
            <a href="admin_settings.php" class="nav-btn">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a href="admin_logout.php" class="nav-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <!-- Alert Messages -->
        <?php if(isset($_SESSION['message'])): ?>
            <div class="admin-alert alert-<?php echo $_SESSION['message_type'] ?? 'success'; ?>">
                <i class="fas fa-<?php echo ($_SESSION['message_type'] ?? 'success') == 'success' ? 'check-circle' : 
                                    ($_SESSION['message_type'] == 'warning' ? 'exclamation-triangle' : 'exclamation-circle'); ?> fa-lg"></i>
                <span style="font-weight: 600;"><?php echo $_SESSION['message']; ?></span>
                <?php unset($_SESSION['message']); ?>
                <?php unset($_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Database Warning -->
        <?php if(!$admin_logs_exists || !$admin_inventory_exists): ?>
            <div class="warning-box">
                <i class="fas fa-exclamation-triangle warning-icon"></i>
                <div>
                    <strong style="color: #fbbf24;">Database Setup Required</strong>
                    <p style="margin-top: 5px; color: #fbbf24; opacity: 0.9;">
                        <?php if(!$admin_logs_exists): ?>admin_stock_logs table missing. <?php endif; ?>
                        <?php if(!$admin_inventory_exists): ?>admin_inventory table missing. <?php endif; ?>
                        Please create these tables in the admin database.
                    </p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Stats Dashboard -->
        <div class="stats-dashboard">
            <div class="stat-card">
                <i class="fas fa-boxes stat-icon"></i>
                <div class="stat-number"><?php echo $inventory_count; ?></div>
                <div class="stat-label">Admin Inventory Items</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-inbox stat-icon"></i>
                <div class="stat-number"><?php echo count($today_stock); ?></div>
                <div class="stat-label">Today's Receipts</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-database stat-icon"></i>
                <div class="stat-number">
                    <?php echo $admin_logs_exists ? '✓' : '✗'; ?>
                </div>
                <div class="stat-label">Logs Table</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-history stat-icon"></i>
                <div class="stat-number"><?php echo count($all_stock); ?></div>
                <div class="stat-label">Total History</div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="content-grid">
            <!-- Left: Add Stock Form -->
            <div class="form-panel">
                <div class="panel-header">
                    <div class="panel-icon">
                        <i class="fas fa-plus-circle"></i>
                    </div>
                    <div>
                        <div class="panel-title">Add Stock to Admin Inventory</div>
                        <div class="panel-subtitle">Receive and record new stock for admin warehouse</div>
                    </div>
                </div>
                
                <form method="POST" action="" id="adminStockForm">
                    <div class="form-group">
                        <label for="item_name" class="form-label">
                            <i class="fas fa-tag"></i> Item Name
                        </label>
                        <input type="text" id="item_name" name="item_name" 
                               class="form-control" required 
                               placeholder="Enter item name" list="productList">
                        <datalist id="productList">
                            <?php foreach($products_list as $product): ?>
                                <option value="<?php echo htmlspecialchars($product); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-balance-scale"></i> Quantity & Unit
                        </label>
                        <div class="quantity-unit-group">
                            <input type="number" id="quantity" name="quantity" 
                                   class="form-control" step="0.1" min="0.1" required 
                                   placeholder="Quantity">
                            
                            <select id="unit" name="unit" class="form-control" required>
                                <option value="">Select Unit</option>
                                <option value="pcs">Pieces</option>
                                <option value="kg">Kilograms</option>
                                <option value="g">Grams</option>
                                <option value="l">Liters</option>
                                <option value="ml">Milliliters</option>
                                <option value="box">Box</option>
                                <option value="pack">Pack</option>
                                <option value="bundle">Bundle</option>
                                <option value="carton">Carton</option>
                                <option value="sack">Sack</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="source" class="form-label">
                            <i class="fas fa-truck"></i> Source
                        </label>
                        <select id="source" name="source" class="form-control" required 
                                onchange="toggleCustomSource(this.value)">
                            <option value="">Select Source</option>
                            <option value="supplier">Supplier Delivery</option>
                            <option value="purchase">Direct Purchase</option>
                            <option value="transfer">Stock Transfer</option>
                            <option value="manufacturer">Manufacturer</option>
                            <option value="import">Import</option>
                            <option value="other">Other Source...</option>
                        </select>
                        
                        <div id="customSourceGroup" class="custom-source-group">
                            <input type="text" id="custom_source" name="custom_source" 
                                   class="form-control" placeholder="Specify custom source">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes" class="form-label">
                            <i class="fas fa-sticky-note"></i> Notes
                        </label>
                        <textarea id="notes" name="notes" class="form-control" 
                                  rows="3" placeholder="Optional notes (invoice number, supplier details, quality remarks, etc.)"></textarea>
                    </div>
                    
                    <button type="submit" name="add_stock" class="submit-btn" 
                            <?php echo (!$admin_logs_exists || !$admin_inventory_exists) ? 'disabled' : ''; ?>>
                        <i class="fas fa-check-circle"></i> ADD TO ADMIN INVENTORY
                        <?php if(!$admin_logs_exists || !$admin_inventory_exists): ?>
                            <br><small style="font-size: 0.8rem; opacity: 0.8;">(Database setup required)</small>
                        <?php endif; ?>
                    </button>
                </form>
            </div>
            
            <!-- Right: Stock History -->
            <div class="history-panel">
                <div class="panel-header">
                    <div class="panel-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <div>
                        <div class="panel-title">Admin Stock History</div>
                        <div class="panel-subtitle">Track all stock receiving activities</div>
                    </div>
                </div>
                
                <?php if($admin_logs_exists): ?>
                <div class="admin-tabs">
                    <button class="admin-tab active" onclick="switchAdminTab('today')">
                        <i class="fas fa-calendar-day"></i> Today
                    </button>
                    <button class="admin-tab" onclick="switchAdminTab('yesterday')">
                        <i class="fas fa-calendar-minus"></i> Yesterday
                    </button>
                    <button class="admin-tab" onclick="switchAdminTab('all')">
                        <i class="fas fa-calendar-alt"></i> All History
                    </button>
                </div>
                
                <!-- Today Tab -->
                <div id="todayTab" class="tab-content active">
                    <div class="date-header">
                        <i class="fas fa-calendar-day"></i> Today's Stock Receipts
                        <span class="date-tag"><?php echo date('M d, Y'); ?></span>
                    </div>
                    
                    <?php if(!empty($today_stock)): ?>
                        <div class="table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                        <th>Source</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($today_stock as $stock): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: white;"><?php echo htmlspecialchars($stock['item_name']); ?></strong>
                                                <?php if(!empty($stock['notes'])): ?>
                                                    <div style="font-size: 0.8rem; color: var(--admin-text-light); margin-top: 5px;">
                                                        <i class="fas fa-comment"></i> <?php echo htmlspecialchars(substr($stock['notes'], 0, 30)); ?>
                                                        <?php if(strlen($stock['notes']) > 30): ?>...<?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="quantity-cell">
                                                +<?php echo $stock['quantity']; ?>
                                            </td>
                                            <td>
                                                <span style="color: var(--admin-text-light);"><?php echo htmlspecialchars($stock['unit']); ?></span>
                                            </td>
                                            <td>
                                                <?php 
                                                $source_class = 'badge-other';
                                                if ($stock['source'] == 'supplier') $source_class = 'badge-supplier';
                                                elseif ($stock['source'] == 'purchase') $source_class = 'badge-admin';
                                                ?>
                                                <span class="badge <?php echo $source_class; ?>">
                                                    <i class="fas fa-<?php echo $stock['source'] == 'supplier' ? 'truck' : 
                                                                    ($stock['source'] == 'purchase' ? 'shopping-cart' : 'exchange-alt'); ?>"></i>
                                                    <?php echo ucfirst($stock['source']); ?>
                                                </span>
                                            </td>
                                            <td style="color: var(--admin-text-light);">
                                                <?php echo date('H:i', strtotime($stock['date_added'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times empty-icon"></i>
                            <h3>No Stock Received Today</h3>
                            <p>Add stock using the form on the left</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Yesterday Tab -->
                <div id="yesterdayTab" class="tab-content">
                    <div class="date-header">
                        <i class="fas fa-calendar-minus"></i> Yesterday's Stock
                        <span class="date-tag"><?php echo date('M d, Y', strtotime('-1 day')); ?></span>
                    </div>
                    
                    <?php if(!empty($yesterday_stock)): ?>
                        <div class="table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Unit</th>
                                        <th>Source</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($yesterday_stock as $stock): ?>
                                        <tr>
                                            <td>
                                                <strong style="color: white;"><?php echo htmlspecialchars($stock['item_name']); ?></strong>
                                            </td>
                                            <td class="quantity-cell">
                                                +<?php echo $stock['quantity']; ?>
                                            </td>
                                            <td>
                                                <span style="color: var(--admin-text-light);"><?php echo htmlspecialchars($stock['unit']); ?></span>
                                            </td>
                                            <td>
                                                <?php 
                                                $source_class = 'badge-other';
                                                if ($stock['source'] == 'supplier') $source_class = 'badge-supplier';
                                                elseif ($stock['source'] == 'purchase') $source_class = 'badge-admin';
                                                ?>
                                                <span class="badge <?php echo $source_class; ?>">
                                                    <?php echo ucfirst($stock['source']); ?>
                                                </span>
                                            </td>
                                            <td style="color: var(--admin-text-light);">
                                                <?php echo date('H:i', strtotime($stock['date_added'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times empty-icon"></i>
                            <h3>No Stock Received Yesterday</h3>
                            <p>No stock was added yesterday</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- All History Tab -->
                <div id="allTab" class="tab-content">
                    <div class="date-header">
                        <i class="fas fa-history"></i> All Stock History
                        <span style="margin-left: auto; font-size: 0.9rem; color: var(--admin-text-light);">
                            Last <?php echo count($all_stock); ?> records
                        </span>
                    </div>
                    
                    <?php if(!empty($all_stock)): ?>
                        <div class="table-container">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Source</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($all_stock as $stock): ?>
                                        <tr>
                                            <td>
                                                <div style="color: white; font-weight: 600;"><?php echo date('M d, Y', strtotime($stock['date_added'])); ?></div>
                                                <div style="font-size: 0.8rem; color: var(--admin-text-light);">
                                                    <?php echo date('H:i', strtotime($stock['date_added'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong style="color: white;"><?php echo htmlspecialchars($stock['item_name']); ?></strong>
                                                <div style="font-size: 0.8rem; color: var(--admin-text-light);">
                                                    <?php echo htmlspecialchars($stock['unit']); ?>
                                                </div>
                                                <?php if(!empty($stock['notes'])): ?>
                                                    <div style="font-size: 0.75rem; color: var(--admin-text-light); margin-top: 3px;">
                                                        <i class="fas fa-comment"></i> <?php echo htmlspecialchars(substr($stock['notes'], 0, 40)); ?>
                                                        <?php if(strlen($stock['notes']) > 40): ?>...<?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="quantity-cell">
                                                +<?php echo $stock['quantity']; ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $source_class = 'badge-other';
                                                if ($stock['source'] == 'supplier') $source_class = 'badge-supplier';
                                                elseif ($stock['source'] == 'purchase') $source_class = 'badge-admin';
                                                ?>
                                                <span class="badge <?php echo $source_class; ?>">
                                                    <?php echo ucfirst($stock['source']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-history empty-icon"></i>
                            <h3>No Stock History</h3>
                            <p>Start adding stock to see history here</p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-database empty-icon"></i>
                        <h3>Admin Database Not Setup</h3>
                        <p>The admin stock tables are not found in the database.</p>
                        <p style="margin-top: 15px; font-size: 0.9rem;">
                            Please create <strong>admin_stock_logs</strong> and <strong>admin_inventory</strong> tables.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function switchAdminTab(tabName) {
            // Hide all tabs
            document.getElementById('todayTab').classList.remove('active');
            document.getElementById('yesterdayTab').classList.remove('active');
            document.getElementById('allTab').classList.remove('active');
            
            // Remove active class from all buttons
            document.querySelectorAll('.admin-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + 'Tab').classList.add('active');
            
            // Add active class to clicked button
            event.currentTarget.classList.add('active');
        }
        
        // Toggle custom source field
        function toggleCustomSource(source) {
            const customGroup = document.getElementById('customSourceGroup');
            if (source === 'other') {
                customGroup.classList.add('show');
                document.getElementById('custom_source').required = true;
            } else {
                customGroup.classList.remove('show');
                document.getElementById('custom_source').required = false;
            }
        }
        
        // Form validation
        document.getElementById('adminStockForm').addEventListener('submit', function(e) {
            const quantity = parseFloat(document.getElementById('quantity').value);
            if (quantity <= 0) {
                e.preventDefault();
                alert('Please enter a valid quantity greater than 0');
                document.getElementById('quantity').focus();
            }
        });
        
        // Add smooth scroll behavior
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>