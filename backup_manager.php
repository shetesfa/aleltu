<?php
// backup_manager.php - Backup Management (Admin Only)
require_once 'config.php';
session_start();

// Check if user is logged in
if(!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

// Only admin and super_admin allowed
if($_SESSION['role'] == 'seller') {
    header("Location: seller_pos.php");
    exit();
}

// Ethiopian time function (UTC+3)
function ethiopian_time($format = 'Y-m-d H:i:s') {
    $timestamp = time() + (3 * 3600); // UTC+3 for Ethiopia
    return date($format, $timestamp);
}

// Convert to Ethiopian 12-hour time
function ethiopian_time_12hr($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time() + (3 * 3600);
    }
    $time = date('g:i:s A', $timestamp);
    $hour = date('g', $timestamp);
    $ampm = date('A', $timestamp);
    return $hour . ':' . date('i:s', $timestamp) . ' ' . $ampm;
}

// Define backup intervals (in seconds)
$two_hours = 2 * 60 * 60; // 2 hours in seconds

// Check for automatic backup trigger (every 2 hours)
$auto_backup_triggered = false;
$last_auto_backup_file = null;

// Get last backup time from database or session
$last_backup_time = null;
$auto_backup_query = "SELECT MAX(backup_time) as last_backup FROM backup_logs WHERE status='CREATED' AND filename LIKE '%(by System)%'";
$auto_backup_result = mysqli_query($conn, $auto_backup_query);
if($auto_backup_result && mysqli_num_rows($auto_backup_result) > 0) {
    $row = mysqli_fetch_assoc($auto_backup_result);
    $last_backup_time = strtotime($row['last_backup'] ?? '2000-01-01');
} else {
    // If no backup found in logs, check session
    $last_backup_time = $_SESSION['last_auto_backup'] ?? 0;
}

$current_time = time() + (3 * 3600); // Ethiopian time (UTC+3)

// If 2 hours have passed since last auto-backup OR never backed up
if(($current_time - $last_backup_time) >= $two_hours) {
    // Create automatic backup
    $backup_file = createBackup($conn, true); // true = system backup
    if($backup_file) {
        // Update last backup time
        $_SESSION['last_auto_backup'] = $current_time;
        $auto_backup_triggered = true;
        $last_auto_backup_file = basename($backup_file);
        
        // Log system backup
        $status = 'CREATED';
        $log_query = "INSERT INTO backup_logs (backup_time, filename, status) 
                      VALUES (NOW(), '$last_auto_backup_file (by System)', '$status')";
        mysqli_query($conn, $log_query);
        
        // Auto-download backup using JavaScript method for Chrome compatibility
        $_SESSION['auto_download_file'] = $last_auto_backup_file;
        $_SESSION['auto_download_path'] = $backup_file;
    }
}

// Handle backup actions with security improvements
if(isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Validate action
    $allowed_actions = ['create', 'delete', 'download'];
    if(!in_array($action, $allowed_actions)) {
        header("Location: backup_manager.php?error=invalid_action");
        exit();
    }
    
    if($action == 'create') {
        // Create manual backup
        $backup_file = createBackup($conn, false); // false = manual backup
        if($backup_file) {
            header("Location: backup_manager.php?msg=created");
        } else {
            header("Location: backup_manager.php?error=create_failed");
        }
        exit();
    }
    
    if($action == 'delete' && isset($_GET['file'])) {
        $file = basename($_GET['file']);
        // Validate file extension
        if(pathinfo($file, PATHINFO_EXTENSION) !== 'sql') {
            header("Location: backup_manager.php?error=invalid_file");
            exit();
        }
        
        $backup_dir = dirname(__FILE__) . '/backups/';
        $file_path = realpath($backup_dir . $file);
        
        // Security check to prevent directory traversal
        if($file_path && strpos($file_path, realpath($backup_dir)) === 0 && 
           file_exists($file_path) && is_file($file_path)) {
            
            if(unlink($file_path)) {
                // Log deletion
                $log_msg = "Deleted backup: $file";
                $status = 'DELETED';
                
                // Try to get username from session
                $username = $_SESSION['username'] ?? 'System';
                $log_msg_with_user = "$log_msg (by $username)";
                
                $log_query = "INSERT INTO backup_logs (backup_time, filename, status) 
                              VALUES (NOW(), '$log_msg_with_user', '$status')";
                
                if(!mysqli_query($conn, $log_query)) {
                    // If backup_logs table doesn't exist, create it
                    if(mysqli_errno($conn) == 1146) {
                        createBackupLogsTable($conn);
                        mysqli_query($conn, $log_query);
                    }
                }
                
                header("Location: backup_manager.php?msg=deleted");
            } else {
                header("Location: backup_manager.php?error=delete_failed");
            }
            exit();
        } else {
            header("Location: backup_manager.php?error=file_not_found");
            exit();
        }
    }
    
    if($action == 'download' && isset($_GET['file'])) {
        $file = basename($_GET['file']);
        $backup_dir = dirname(__FILE__) . '/backups/';
        
        // Check if it's in system directory
        if(strpos($file, 'auto_backup_') === 0) {
            $file_path = $backup_dir . 'system/' . $file;
        } else {
            $file_path = $backup_dir . $file;
        }
        
        if(file_exists($file_path) && is_file($file_path)) {
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $file . '"');
            header('Content-Length: ' . filesize($file_path));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($file_path);
            exit();
        }
    }
}

// Function to auto-download backup (for Chrome compatibility)
function triggerChromeDownload($filename, $filepath) {
    if(file_exists($filepath)) {
        // Store in session for JavaScript to handle
        $_SESSION['chrome_download_file'] = $filename;
        $_SESSION['chrome_download_path'] = $filepath;
        return true;
    }
    return false;
}

// Function to create backup logs table if it doesn't exist
function createBackupLogsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS backup_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        backup_time DATETIME NOT NULL,
        filename VARCHAR(500) NOT NULL,
        status VARCHAR(50) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    return mysqli_query($conn, $sql);
}

// Function to check if table exists and is accessible
function isTableAccessible($conn, $table_name) {
    // First check if table exists in information_schema
    $check_sql = "SELECT 1 FROM information_schema.tables 
                  WHERE table_schema = DATABASE() AND table_name = '$table_name'";
    $result = mysqli_query($conn, $check_sql);
    
    if(!$result || mysqli_num_rows($result) == 0) {
        return false;
    }
    
    // Try to get table status
    $status_result = @mysqli_query($conn, "CHECK TABLE `$table_name` QUICK");
    if($status_result) {
        $status = mysqli_fetch_assoc($status_result);
        if(isset($status['Msg_text']) && $status['Msg_text'] == 'OK') {
            return true;
        }
    }
    
    return false;
}

// Function to create backup with improved error handling for corrupted tables
function createBackup($conn, $is_system_backup = false) {
    $backup_dir = dirname(__FILE__) . '/backups/';
    
    // Create backups directory if not exists
    if(!is_dir($backup_dir)) {
        if(!mkdir($backup_dir, 0755, true)) {
            error_log("Failed to create backup directory: $backup_dir");
            return false;
        }
    }
    
    // Check if directory is writable
    if(!is_writable($backup_dir)) {
        error_log("Backup directory not writable: $backup_dir");
        return false;
    }
    
    // Create system backups subdirectory
    $system_backup_dir = $backup_dir . 'system/';
    if(!is_dir($system_backup_dir)) {
        mkdir($system_backup_dir, 0755, true);
    }
    
    // Use Ethiopian time for filename
    $ethiopian_time = ethiopian_time('Y-m-d_H-i-s');
    
    if($is_system_backup) {
        $backup_file = $system_backup_dir . 'auto_backup_' . $ethiopian_time . '.sql';
    } else {
        $backup_file = $backup_dir . 'manual_backup_' . $ethiopian_time . '.sql';
    }
    
    // Get all tables - filter out system and corrupted tables
    $tables = [];
    $result = mysqli_query($conn, 'SHOW TABLES');
    if(!$result) {
        error_log("Failed to get tables: " . mysqli_error($conn));
        return false;
    }
    
    while($row = mysqli_fetch_row($result)) {
        $table_name = $row[0];
        
        // Skip system tables and temporary tables
        if(strpos($table_name, '#sql') === 0 || 
           strpos($table_name, 'temp_') === 0 ||
           strpos($table_name, 'innodb_temp') !== false ||
           $table_name == 'backup_logs' ||
           $table_name == 'backup_downloads') {
            continue;
        }
        
        // Check if table is accessible
        if(!isTableAccessible($conn, $table_name)) {
            error_log("Skipping inaccessible table: $table_name");
            continue;
        }
        
        $tables[] = $table_name;
    }
    
    $current_ethiopian_time = ethiopian_time('Y-m-d H:i:s');
    $sql = "-- Aleltu POS Backup\n";
    $sql .= "-- Generated: " . $current_ethiopian_time . " (Ethiopian Time)\n";
    $sql .= "-- Type: " . ($is_system_backup ? "Auto Backup (Every 2 hours)" : "Manual Backup") . "\n";
    $sql .= "-- Database: " . mysqli_get_host_info($conn) . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    
    foreach($tables as $table) {
        try {
            // Drop table
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            
            // Get create statement with error handling
            $create_result = mysqli_query($conn, "SHOW CREATE TABLE `$table`");
            if(!$create_result) {
                // Try alternative method
                $sql .= "-- Note: Could not get structure for table `$table`\n";
                error_log("Could not get create statement for table: $table - " . mysqli_error($conn));
                continue;
            }
            
            $create = mysqli_fetch_array($create_result);
            if(!isset($create['Create Table'])) {
                error_log("No create statement returned for table: $table");
                mysqli_free_result($create_result);
                continue;
            }
            
            $sql .= $create['Create Table'] . ";\n\n";
            mysqli_free_result($create_result);
            
            // Get table data
            $result = mysqli_query($conn, "SELECT * FROM `$table`");
            if($result && mysqli_num_rows($result) > 0) {
                $num_fields = mysqli_num_fields($result);
                $field_info = mysqli_fetch_fields($result);
                
                while($row = mysqli_fetch_row($result)) {
                    $sql .= "INSERT INTO `$table` VALUES(";
                    for($i = 0; $i < $num_fields; $i++) {
                        if(isset($row[$i]) && $row[$i] !== null) {
                            $row[$i] = mysqli_real_escape_string($conn, $row[$i]);
                            $row[$i] = str_replace("\n", "\\n", $row[$i]);
                            $row[$i] = str_replace("\r", "\\r", $row[$i]);
                            $row[$i] = str_replace("'", "\\'", $row[$i]);
                            $sql .= "'" . $row[$i] . "'";
                        } else {
                            $sql .= "NULL";
                        }
                        if($i < $num_fields - 1) {
                            $sql .= ',';
                        }
                    }
                    $sql .= ");\n";
                }
                $sql .= "\n";
            }
            
            if($result) {
                mysqli_free_result($result);
            }
            
        } catch(Exception $e) {
            error_log("Error processing table $table: " . $e->getMessage());
            $sql .= "-- Error processing table `$table`: " . $e->getMessage() . "\n";
            continue;
        }
    }
    
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
    
    // Save to file
    if(file_put_contents($backup_file, $sql) === false) {
        error_log("Failed to write backup file: $backup_file");
        return false;
    }
    
    // Log the backup
    $filename = basename($backup_file);
    $status = 'CREATED';
    
    if($is_system_backup) {
        $filename_with_user = "$filename (by System)";
    } else {
        $username = $_SESSION['username'] ?? 'Unknown';
        $filename_with_user = "$filename (by $username)";
    }
    
    $log_query = "INSERT INTO backup_logs (backup_time, filename, status) 
                  VALUES (NOW(), '$filename_with_user', '$status')";
    
    if(!mysqli_query($conn, $log_query)) {
        // If backup_logs table doesn't exist, create it
        if(mysqli_errno($conn) == 1146) {
            createBackupLogsTable($conn);
            mysqli_query($conn, $log_query);
        } else {
            error_log("Failed to log backup: " . mysqli_error($conn));
        }
    }
    
    return $backup_file;
}

// Get existing backups
$backup_dir = dirname(__FILE__) . '/backups/';
$system_backup_dir = $backup_dir . 'system/';
$backups = [];
$auto_backups = [];

if(is_dir($backup_dir)) {
    // Get manual backups
    $files = scandir($backup_dir);
    foreach($files as $file) {
        if($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql' && strpos($file, 'manual_backup_') === 0) {
            $filepath = $backup_dir . $file;
            if(is_file($filepath)) {
                $backups[] = [
                    'name' => $file,
                    'size' => filesize($filepath),
                    'modified' => filemtime($filepath),
                    'path' => 'backups/' . $file,
                    'type' => 'manual'
                ];
            }
        }
    }
    
    // Get auto backups
    if(is_dir($system_backup_dir)) {
        $auto_files = scandir($system_backup_dir);
        foreach($auto_files as $file) {
            if($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'sql' && strpos($file, 'auto_backup_') === 0) {
                $filepath = $system_backup_dir . $file;
                if(is_file($filepath)) {
                    $auto_backups[] = [
                        'name' => 'system/' . $file,
                        'size' => filesize($filepath),
                        'modified' => filemtime($filepath),
                        'path' => 'backups/system/' . $file,
                        'type' => 'auto'
                    ];
                }
            }
        }
    }
    
    // Merge all backups
    $all_backups = array_merge($backups, $auto_backups);
    
    // Sort by modification time (newest first)
    usort($all_backups, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
}

// Get backup logs
$logs_query = "SELECT * FROM backup_logs ORDER BY backup_time DESC LIMIT 20";
$logs_result = mysqli_query($conn, $logs_query);

// Check if backup_logs table exists, if not create it
if(!$logs_result && mysqli_errno($conn) == 1146) {
    createBackupLogsTable($conn);
    $logs_result = mysqli_query($conn, $logs_query);
}

// Calculate next auto-backup time (using Ethiopian time)
$next_auto_backup_timestamp = $last_backup_time ? ($last_backup_time + $two_hours) : ($current_time + $two_hours);
$next_auto_backup_ethiopian = date('Y-m-d g:i:s A', $next_auto_backup_timestamp);
$time_until_next = $next_auto_backup_timestamp - $current_time;

// Current Ethiopian time for display
$current_ethiopian_display = ethiopian_time_12hr();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="image\photo_2026-01-12_07-44-10.jpg">
    <title>Backup Manager | Aleltu POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Keep all existing CSS styles */
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --success-color: #4cc9f0;
            --danger-color: #f72585;
            --warning-color: #f8961e;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --gray-color: #6c757d;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            color: var(--dark-color);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary-color);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            font-weight: 500;
            transition: var(--transition);
            border: none;
            cursor: button;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }
        
        .back-btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
            border-left: 6px solid var(--primary-color);
        }
        
        .header h1 {
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--secondary-color);
            margin-bottom: 10px;
            font-size: 2.2rem;
        }
        
        .header p {
            color: var(--gray-color);
            font-size: 1.1rem;
        }
        
        .card {
            background: white;
            padding: 30px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        
        .card:hover {
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
        }
        
        .card h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            color: var(--secondary-color);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        
        .action-card {
            background: linear-gradient(135deg, #f8f9ff 0%, #eef2ff 100%);
            padding: 25px;
            border-radius: var(--border-radius);
            border: 1px solid #e0e7ff;
            transition: var(--transition);
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
        }
        
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: var(--transition);
            width: 100%;
            margin-top: 15px;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }
        
        .danger-btn {
            background: linear-gradient(135deg, #f72585, #b5179e);
        }
        
        .warning-btn {
            background: linear-gradient(135deg, #f8961e, #f3722c);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            overflow: hidden;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        thead {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
        }
        
        th {
            padding: 18px 20px;
            text-align: left;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: var(--transition);
        }
        
        tbody tr:hover {
            background-color: #f8f9ff;
        }
        
        td {
            padding: 16px 20px;
            vertical-align: middle;
        }
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-success {
            background: rgba(76, 201, 240, 0.15);
            color: #4cc9f0;
        }
        
        .badge-danger {
            background: rgba(247, 37, 133, 0.15);
            color: #f72585;
        }
        
        .badge-warning {
            background: rgba(248, 150, 30, 0.15);
            color: #f8961e;
        }
        
        .badge-auto {
            background: rgba(67, 97, 238, 0.15);
            color: #4361ee;
        }
        
        .badge-manual {
            background: rgba(76, 201, 240, 0.15);
            color: #4cc9f0;
        }
        
        .file-size {
            font-family: 'Consolas', monospace;
            background: #f8f9fa;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 0.9rem;
            border: 1px solid #e9ecef;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: var(--transition);
            border: none;
            cursor: pointer;
        }
        
        .btn-download {
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
            border: 1px solid rgba(67, 97, 238, 0.2);
        }
        
        .btn-download:hover {
            background: rgba(67, 97, 238, 0.2);
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: rgba(247, 37, 133, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(247, 37, 133, 0.2);
        }
        
        .btn-delete:hover {
            background: rgba(247, 37, 133, 0.2);
            transform: translateY(-2px);
        }
        
        .message {
            padding: 18px 25px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .message-success {
            background: linear-gradient(135deg, rgba(76, 201, 240, 0.15), rgba(72, 187, 120, 0.15));
            border-left: 5px solid #4cc9f0;
            color: #0c5460;
        }
        
        .message-error {
            background: linear-gradient(135deg, rgba(247, 37, 133, 0.15), rgba(220, 53, 69, 0.15));
            border-left: 5px solid #f72585;
            color: #721c24;
        }
        
        .log-entry {
            padding: 18px;
            margin: 10px 0;
            border-radius: 10px;
            background: #f8f9fa;
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }
        
        .log-entry:hover {
            background: #eef2ff;
            transform: translateX(5px);
        }
        
        .log-status-CREATED { border-left-color: #4cc9f0; }
        .log-status-DELETED { border-left-color: #f72585; }
        .log-status-RESTORED { border-left-color: #f8961e; }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-color);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 25px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #f8f9ff, #eef2ff);
            padding: 25px;
            border-radius: var(--border-radius);
            text-align: center;
            border: 1px solid #e0e7ff;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: var(--gray-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .auto-backup-info {
            background: linear-gradient(135deg, rgba(76, 201, 240, 0.1), rgba(67, 97, 238, 0.1));
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #4cc9f0;
        }
        
        .auto-backup-timer {
            background: linear-gradient(135deg, rgba(248, 150, 30, 0.1), rgba(243, 114, 44, 0.1));
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 4px solid #f8961e;
        }
        
        .timer-countdown {
            font-family: 'Consolas', monospace;
            font-size: 1.2rem;
            font-weight: bold;
            color: #f8961e;
        }
        
        .time-info {
            font-size: 0.9rem;
            color: var(--gray-color);
            margin-top: 5px;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }
            
            .header, .card {
                padding: 20px;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
        
        /* Hidden iframe for Chrome downloads */
        .download-iframe {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="header">
            <h1><i class="fas fa-database"></i> Backup Manager</h1>
            <p>Manage database backups and recovery with security and ease</p>
            
            <?php if($auto_backup_triggered): ?>
                <div class="auto-backup-info">
                    <i class="fas fa-robot"></i> <strong>Auto-backup completed!</strong> 
                    A new backup was automatically created and saved (runs every 2 hours).
                    <div style="margin-top: 5px;">
                        <i class="fas fa-file-download"></i> Auto-downloading: <?php echo $last_auto_backup_file; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="auto-backup-timer">
                <i class="fas fa-clock"></i> <strong>Next Auto-Backup:</strong> 
                <span class="timer-countdown" id="nextBackupTimer"><?php echo date('g:i:s A', $next_auto_backup_timestamp); ?></span>
                <div class="time-info">
                    Current Ethiopian Time: <?php echo $current_ethiopian_display; ?><br>
                    Auto-backups run every 2 hours and are automatically downloaded
                </div>
            </div>
        </div>
        
        <?php if(isset($_GET['msg'])): ?>
            <div class="message message-success">
                <i class="fas fa-check-circle fa-2x"></i>
                <div>
                    <strong>Success!</strong>
                    <?php 
                    $messages = [
                        'created' => 'New backup created successfully!',
                        'deleted' => 'Backup file deleted successfully!',
                        'restored' => 'Database restored successfully!'
                    ];
                    echo $messages[$_GET['msg']] ?? 'Action completed successfully.';
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_GET['error'])): ?>
            <div class="message message-error">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
                <div>
                    <strong>Error!</strong>
                    <?php 
                    $errors = [
                        'invalid_action' => 'Invalid action requested.',
                        'create_failed' => 'Failed to create backup. Check server logs.',
                        'delete_failed' => 'Failed to delete backup file.',
                        'file_not_found' => 'Backup file not found.',
                        'invalid_file' => 'Invalid file type. Only .sql files allowed.'
                    ];
                    echo $errors[$_GET['error']] ?? 'An error occurred.';
                    ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Backup Stats -->
        <div class="card">
            <h2><i class="fas fa-chart-bar"></i> Backup Statistics</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($all_backups); ?></div>
                    <div class="stat-label">Total Backups</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php 
                        $total_size = array_sum(array_column($all_backups, 'size'));
                        if($total_size < 1048576) {
                            echo round($total_size/1024, 1) . ' KB';
                        } else {
                            echo round($total_size/1048576, 1) . ' MB';
                        }
                        ?>
                    </div>
                    <div class="stat-label">Total Size</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($auto_backups); ?></div>
                    <div class="stat-label">Auto Backups</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?php echo date('g:i A', $next_auto_backup_timestamp); ?>
                    </div>
                    <div class="stat-label">Next Auto-Backup</div>
                </div>
            </div>
        </div>
        
        <!-- Backup Actions -->
        <div class="card">
            <h2><i class="fas fa-cogs"></i> Backup Actions</h2>
            <p>Automatic backups run every 2 hours and are downloaded automatically. You can also create manual backups.</p>
            
            <div class="actions-grid">
                <div class="action-card">
                    <h3><i class="fas fa-plus-circle"></i> Create Manual Backup</h3>
                    <p>Create a new manual backup of the entire database.</p>
                    <button class="action-btn" onclick="createBackup()">
                        <i class="fas fa-database"></i> Create Manual Backup
                    </button>
                </div>
                
                <div class="action-card">
                    <h3><i class="fas fa-history"></i> View Auto Backups</h3>
                    <p>View all automatic backups saved in the last 7 days.</p>
                    <button class="action-btn warning-btn" onclick="viewAutoBackups()">
                        <i class="fas fa-robot"></i> View Auto Backups
                    </button>
                </div>
                
                <div class="action-card">
                    <h3><i class="fas fa-trash-alt"></i> Clean Up Old Backups</h3>
                    <p>Remove backups older than 7 days to save storage space.</p>
                    <button class="action-btn danger-btn" onclick="clearOldBackups()">
                        <i class="fas fa-broom"></i> Clean Up
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Backup Files List -->
        <div class="card">
            <h2><i class="fas fa-archive"></i> Available Backups (<?php echo count($all_backups); ?>)</h2>
            
            <?php if(count($all_backups) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Backup File</th>
                                <th>Size</th>
                                <th>Created (Ethiopian Time)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($all_backups as $backup): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-<?php echo $backup['type']; ?>">
                                        <?php echo $backup['type'] == 'auto' ? 'Auto' : 'Manual'; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($backup['name']); ?></strong>
                                </td>
                                <td>
                                    <span class="file-size">
                                        <?php 
                                        $size = $backup['size'];
                                        if($size < 1024) {
                                            echo $size . ' B';
                                        } elseif($size < 1048576) {
                                            echo round($size/1024, 2) . ' KB';
                                        } else {
                                            echo round($size/1048576, 2) . ' MB';
                                        }
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    // Convert file modified time to Ethiopian time
                                    $ethiopian_time = date('Y-m-d g:i:s A', $backup['modified'] + (3 * 3600));
                                    echo $ethiopian_time;
                                    ?>
                                    <br>
                                    <small style="color: var(--gray-color);">
                                        <?php echo time_ago($backup['modified']); ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="backup_manager.php?action=download&file=<?php echo urlencode($backup['name']); ?>" class="btn btn-download">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                        <button class="btn btn-delete" onclick="deleteBackup('<?php echo htmlspecialchars($backup['name']); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Backup Files Found</h3>
                    <p>Create your first backup to get started</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Backup Logs -->
        <div class="card">
            <h2><i class="fas fa-history"></i> Recent Backup Logs</h2>
            
            <?php if($logs_result && mysqli_num_rows($logs_result) > 0): ?>
                <div style="max-height: 400px; overflow-y: auto; padding-right: 10px;">
                    <?php while($log = mysqli_fetch_assoc($logs_result)): ?>
                    <div class="log-entry log-status-<?php echo htmlspecialchars($log['status']); ?>">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?php 
                                // Convert log time to Ethiopian time
                                $log_time = strtotime($log['backup_time']) + (3 * 3600);
                                echo date('Y-m-d g:i:s A', $log_time);
                                ?></strong>
                            </div>
                            <span class="badge badge-<?php echo strtolower($log['status']); ?>">
                                <?php echo htmlspecialchars($log['status']); ?>
                            </span>
                        </div>
                        <p style="margin-top: 10px; color: #555;">
                            <?php echo htmlspecialchars($log['filename']); ?>
                        </p>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No Backup Logs</h3>
                    <p>Backup activity will appear here</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hidden iframe for Chrome auto-downloads -->
    <iframe id="downloadFrame" class="download-iframe" name="downloadFrame"></iframe>

    <script>
        function createBackup() {
            if(confirm('Create a new manual backup?\n\nThis may take a moment depending on your database size.')) {
                // Show loading state
                const btn = event.target;
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Backup...';
                btn.disabled = true;
                
                window.location.href = 'backup_manager.php?action=create';
            }
        }
        
        function deleteBackup(filename) {
            if(confirm('⚠️ Are you sure you want to delete this backup?\n\nFile: ' + filename + '\n\nThis action cannot be undone!')) {
                window.location.href = 'backup_manager.php?action=delete&file=' + encodeURIComponent(filename);
            }
        }
        
        function viewAutoBackups() {
            // Filter to show only auto backups
            const autoBackups = document.querySelectorAll('tbody tr');
            autoBackups.forEach(row => {
                const badge = row.querySelector('.badge-auto');
                if(badge && badge.textContent.includes('Auto')) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            alert('Showing only auto backups. Refresh page to show all.');
        }
        
        function clearOldBackups() {
            if(confirm('🗑️ Delete backup files older than 7 days?\n\nThis will keep only recent backups to save storage space.')) {
                window.location.href = 'backup_manager.php?action=clear_old';
            }
        }
        
        // Chrome-compatible auto-download function
        function triggerAutoDownload(filename) {
            console.log('Triggering auto-download for:', filename);
            
            // Method 1: Create invisible link and click it
            const downloadLink = document.createElement('a');
            downloadLink.href = 'backup_manager.php?action=download&file=' + encodeURIComponent(filename);
            downloadLink.target = '_blank';
            downloadLink.download = filename;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            
            // Method 2: Use iframe as fallback
            setTimeout(function() {
                const iframe = document.getElementById('downloadFrame');
                iframe.src = 'backup_manager.php?action=download&file=' + encodeURIComponent(filename);
            }, 1000);
            
            // Show notification
            setTimeout(function() {
                alert('Backup automatically downloaded: ' + filename);
            }, 2000);
        }
        
        // Update countdown timer for next auto-backup
        function updateCountdown() {
            const nextBackupTime = new Date('<?php echo date('Y-m-d H:i:s', $next_auto_backup_timestamp); ?>').getTime();
            const now = new Date().getTime() + (3 * 3600 * 1000); // Ethiopian time
            const distance = nextBackupTime - now;
            
            if (distance < 0) {
                document.getElementById("nextBackupTimer").innerHTML = "Now";
                // Refresh page to trigger backup
                setTimeout(() => {
                    window.location.reload();
                }, 3000);
                return;
            }
            
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            // Format with AM/PM for Ethiopian time
            const nextDate = new Date(nextBackupTime);
            const ethiopianHours = nextDate.getHours() % 12 || 12;
            const ampm = nextDate.getHours() >= 12 ? 'PM' : 'AM';
            
            document.getElementById("nextBackupTimer").innerHTML = 
                ethiopianHours + ":" + 
                nextDate.getMinutes().toString().padStart(2, '0') + " " + ampm + 
                " (" + hours.toString().padStart(2, '0') + ":" + 
                minutes.toString().padStart(2, '0') + ":" + 
                seconds.toString().padStart(2, '0') + ")";
        }
        
        // Update countdown every second
        setInterval(updateCountdown, 1000);
        updateCountdown(); // Initial call
        
        // Auto-dismiss messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s ease';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);
        
        // Check if we need to trigger auto-download
        <?php if(isset($_SESSION['auto_download_file'])): ?>
            window.onload = function() {
                setTimeout(function() {
                    triggerAutoDownload('<?php echo $_SESSION['auto_download_file']; ?>');
                }, 2000); // Wait 2 seconds for page to load
            };
            <?php unset($_SESSION['auto_download_file'], $_SESSION['auto_download_path']); ?>
        <?php endif; ?>
    </script>
</body>
</html>
<?php 
mysqli_close($conn);

// Helper function for time ago
function time_ago($timestamp) {
    $difference = time() - $timestamp;
    $periods = ["second", "minute", "hour", "day", "week", "month", "year"];
    $lengths = ["60","60","24","7","4.35","12"];
    
    for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
        $difference /= $lengths[$j];
    }
    
    $difference = round($difference);
    if($difference != 1) {
        $periods[$j] .= "s";
    }
    
    return "$difference $periods[$j] ago";
}
?>