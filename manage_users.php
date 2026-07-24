<?php
// manage_users.php - Manage Users (Admin Only)
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'] ?? '';
$current_username = $_SESSION['username'] ?? '';

// Load read-only flag for this user
loadReadOnlyFlag($conn, $current_user_id);

// ========== PROTECTED USERS (OWNERS - CANNOT BE DELETED) ==========
$protected_user_ids = [1002, 1016]; // Teklu (1002) and Biniyam (1007)

// ========== PROPER BRANCH DETECTION ==========
if ($current_user_role == 'super_admin') {
    $current_branch_id = 0;
    $current_branch_name = 'ሁሉም ቅርንጫፎች';
} else {
    $branch_query = "SELECT branch_id FROM users WHERE id = $current_user_id";
    $branch_result = mysqli_query($conn, $branch_query);
    
    if ($branch_result && mysqli_num_rows($branch_result) > 0) {
        $branch_data = mysqli_fetch_assoc($branch_result);
        $current_branch_id = $branch_data['branch_id'];
        
        $branch_name_query = "SELECT place_name FROM places WHERE id = $current_branch_id";
        $branch_name_result = mysqli_query($conn, $branch_name_query);
        if ($branch_name_result && mysqli_num_rows($branch_name_result) > 0) {
            $branch_name_data = mysqli_fetch_assoc($branch_name_result);
            $current_branch_name = $branch_name_data['place_name'];
        } else {
            $current_branch_name = 'ቅርንጫፍ ' . $current_branch_id;
        }
    } else {
        $current_branch_id = 0;
        $current_branch_name = 'ሁሉም ቅርንጫፎች';
    }
}

// Build users query (using places table, NOT locations)
if ($current_user_role == 'super_admin') {
    $users_query = "SELECT u.*, p.place_name as branch_name 
                    FROM users u 
                    LEFT JOIN places p ON u.branch_id = p.id
                    WHERE u.is_active = 1 
                    AND u.id != 3
                    ORDER BY u.branch_id, u.role, u.username";
} else {
    $users_query = "SELECT u.*, p.place_name as branch_name 
                    FROM users u 
                    LEFT JOIN places p ON u.branch_id = p.id
                    WHERE u.is_active = 1 
                    AND u.id != 3
                    AND u.branch_id = $current_branch_id
                    AND u.role != 'super_admin'
                    ORDER BY u.role, u.username";
}

$users_result = mysqli_query($conn, $users_query);

if(!$users_result) {
    die("Query failed: " . mysqli_error($conn));
}

// Handle user actions — blocked for read-only users
if(isset($_GET['action']) && isset($_GET['id'])) {
    // Read-only users cannot perform any write actions
    if (isReadOnly()) {
        header("Location: manage_users.php?msg=readonly");
        exit();
    }
    $action = $_GET['action'];
    $user_id = intval($_GET['id']);
    
    // Prevent actions on protected users (Teklu and Biniyam)
    if(in_array($user_id, $protected_user_ids)) {
        header("Location: manage_users.php?msg=protected");
        exit();
    }
    
    // Prevent actions on id=3 (tesfa account)
    if($user_id == 3) {
        header("Location: manage_users.php?msg=no_permission");
        exit();
    }
    
    // Check if user exists and get their branch
    $check_query = "SELECT id, role, branch_id FROM users WHERE id = $user_id AND is_active = 1";
    $check_result = mysqli_query($conn, $check_query);
    
    if(mysqli_num_rows($check_result) > 0) {
        $target_user = mysqli_fetch_assoc($check_result);
        
        $has_permission = false;
        
        if ($current_user_role == 'super_admin') {
            if ($target_user['id'] != $current_user_id) {
                $has_permission = true;
            }
        } else {
            if ($target_user['branch_id'] == $current_branch_id && 
                $target_user['role'] == 'seller' && 
                $target_user['id'] != $current_user_id) {
                $has_permission = true;
            }
        }
        
        if (!$has_permission) {
            header("Location: manage_users.php?msg=no_permission");
            exit();
        }
        
        if($action == 'delete') {
            $delete_query = "UPDATE users SET is_active = 0 WHERE id = $user_id";
            if(mysqli_query($conn, $delete_query)) {
                header("Location: manage_users.php?msg=deleted");
                exit();
            }
        }
        
        if($action == 'upgrade' && $current_user_role == 'super_admin') {
            if($target_user['role'] == 'seller') {
                $upgrade_query = "UPDATE users SET role = 'admin' WHERE id = $user_id";
                if(mysqli_query($conn, $upgrade_query)) {
                    header("Location: manage_users.php?msg=upgraded");
                    exit();
                }
            }
        }
        
        if($action == 'downgrade' && $current_user_role == 'super_admin') {
            if($target_user['role'] == 'admin') {
                $downgrade_query = "UPDATE users SET role = 'seller' WHERE id = $user_id";
                if(mysqli_query($conn, $downgrade_query)) {
                    header("Location: manage_users.php?msg=downgraded");
                    exit();
                }
            }
        }
    }
    
    header("Location: manage_users.php?msg=error");
    exit();
}

// Get statistics
$total_users = mysqli_num_rows($users_result);
$total_admins = 0;
$total_sellers = 0;

if($users_result) {
    mysqli_data_seek($users_result, 0);
    while($user = mysqli_fetch_assoc($users_result)) {
        if($user['role'] == 'admin') $total_admins++;
        if($user['role'] == 'seller') $total_sellers++;
    }
    mysqli_data_seek($users_result, 0);
}
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="icon" type="image/jpg" href="image/photo_2026-01-12_07-44-10.jpg">
    <title>Manage Users - <?php echo htmlspecialchars($current_branch_name); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', 'Nyala', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .role-header {
            background: <?php echo $current_user_role == 'super_admin' ? 'linear-gradient(135deg, #e74c3c, #c0392b)' : 'linear-gradient(135deg, #3498db, #2980b9)'; ?>;
            color: white;
            padding: 20px 30px;
            border-radius: 15px 15px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .role-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .header {
            background: white;
            padding: 25px;
            border-radius: 0 0 15px 15px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .branch-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 15px;
        }
        .stats-bar {
            display: flex;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .stat-item {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .stat-icon.total { background: #3498db; color: white; }
        .stat-icon.admins { background: #9b59b6; color: white; }
        .stat-icon.sellers { background: #2ecc71; color: white; }
        .stat-info h3 { font-size: 14px; color: #7f8c8d; margin-bottom: 5px; }
        .stat-info .number { font-size: 24px; font-weight: 700; color: #2c3e50; }
        .back-btn {
            background: #3498db;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-btn:hover { background: #2980b9; transform: translateY(-2px); }
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
            color: #2c3e50;
        }
        tr:hover { background-color: #f8f9fa; }
        .role-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .role-super_admin { background: #e74c3c; color: white; }
        .role-admin { background: #3498db; color: white; }
        .role-seller { background: #2ecc71; color: white; }
        .owner-badge {
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 0 3px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        .action-btn:hover { transform: translateY(-2px); }
        .delete-btn { background: #e74c3c; color: white; }
        .delete-btn:hover { background: #c0392b; }
        .upgrade-btn { background: #9b59b6; color: white; }
        .upgrade-btn:hover { background: #8e44ad; }
        .downgrade-btn { background: #f39c12; color: white; }
        .downgrade-btn:hover { background: #e67e22; }
        .reset-password-btn { background: #34495e; color: white; }
        .reset-password-btn:hover { background: #2c3e50; }
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        .warning { background: #fff3cd; color: #856404; border: 2px solid #ffeaa7; }
        .add-btn {
            background: #27ae60;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            margin-top: 30px;
        }
        .add-btn:hover { background: #2ecc71; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3); }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            position: relative;
            animation: slideDown 0.3s ease;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close-btn {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 28px;
            font-weight: bold;
            color: #7f8c8d;
            cursor: pointer;
            transition: color 0.3s;
        }
        .close-btn:hover { color: #e74c3c; }
        .password-display {
            background: #f8f9fa;
            border: 2px solid #3498db;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: center;
            font-family: monospace;
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            letter-spacing: 2px;
        }
        .user-info { font-size: 16px; color: #7f8c8d; margin-bottom: 5px; }
        .user-name { font-size: 20px; font-weight: 700; color: #2c3e50; margin-bottom: 15px; }
        .copy-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            font-size: 16px;
            transition: all 0.3s;
            margin-top: 15px;
        }
        .copy-btn:hover { background: #2980b9; transform: translateY(-2px); }
        .copy-btn.copied { background: #27ae60; }
        @media (max-width: 768px) {
            .role-header { flex-direction: column; text-align: center; }
            .header h1 { flex-direction: column; align-items: flex-start; }
            .stats-bar { flex-direction: column; }
            table { display: block; overflow-x: auto; }
            td, th { white-space: nowrap; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="role-header">
            <div class="role-badge">
                <i class="fas <?php echo $current_user_role == 'super_admin' ? 'fa-crown' : 'fa-user-shield'; ?>"></i>
                <?php echo $current_user_role == 'super_admin' ? 'ሱፐር አድሚን' : 'አድሚን'; ?> Access
            </div>
            <div class="role-badge">
                <i class="fas fa-store"></i>
                <?php echo htmlspecialchars($current_branch_name); ?>
                <?php if($current_user_role == 'super_admin'): ?>
                    <span style="margin-left: 10px; background: rgba(255,255,255,0.3); padding: 3px 8px; border-radius: 12px; font-size: 11px;">
                        <i class="fas fa-globe"></i> ሁሉም ቅርንጫፎች
                    </span>
                <?php endif; ?>
            </div>
            <div class="role-badge">
                <i class="fas fa-key"></i>
                የይለፍ ቃል ዳግም ማስጀመር ይችላሉ
            </div>
        </div>
        
        <div class="header">
            <div>
                <h1>
                    <i class="fas fa-users-cog"></i> ተጠቃሚዎችን መቆጣጠሪያ
                    <?php if($current_user_role != 'super_admin'): ?>
                        <span class="branch-badge">
                            <i class="fas fa-store"></i> <?php echo htmlspecialchars($current_branch_name); ?>
                        </span>
                    <?php endif; ?>
                </h1>
                <p>
                    <?php if($current_user_role == 'super_admin'): ?>
                        እንኳን ደህና መጡ <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></strong> - ሁሉንም ቅርንጫፎች ማየት ይችላሉ
                    <?php else: ?>
                        እንኳን ደህና መጡ <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></strong> - የሚተዳደረው ቅርንጫፍ: <?php echo htmlspecialchars($current_branch_name); ?>
                    <?php endif; ?>
                </p>
            </div>
            <a href="<?php echo $current_user_role == 'super_admin' ? 'super_admin.php' : 'admin_dashboard.php'; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> ወደ ዳሽቦርድ ተመለስ
            </a>
        </div>
        
        <div class="stats-bar">
            <div class="stat-item">
                <div class="stat-icon total"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h3>ጠቅላላ ተጠቃሚዎች</h3>
                    <div class="number"><?php echo $total_users; ?></div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon admins"><i class="fas fa-user-shield"></i></div>
                <div class="stat-info">
                    <h3>አስተዳዳሪዎች</h3>
                    <div class="number"><?php echo $total_admins; ?></div>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon sellers"><i class="fas fa-user-tie"></i></div>
                <div class="stat-info">
                    <h3>ሻጮች</h3>
                    <div class="number"><?php echo $total_sellers; ?></div>
                </div>
            </div>
        </div>
        
        <?php if(isset($_GET['msg'])): ?>
            <?php
            $messages = [
                'deleted' => ['text' => 'ተጠቃሚ በተሳካ ሁኔታ ተሰርዟል', 'type' => 'success'],
                'upgraded' => ['text' => 'ተጠቃሚ ወደ አስተዳዳሪነት ከፍ ብሏል', 'type' => 'success'],
                'downgraded' => ['text' => 'ተጠቃሚ ወደ ሻጭነት ዝቅ ብሏል', 'type' => 'success'],
                'added' => ['text' => 'አዲስ ተጠቃሚ ተመዝግቧል', 'type' => 'success'],
                'no_permission' => ['text' => 'ይህን እርምጃ ለማከናወን ፈቃድ የለዎትም', 'type' => 'error'],
                'protected' => ['text' => 'ይህ ተጠቃሚ ባለቤት ነው! መሰረዝ አይቻልም።', 'type' => 'error'],
                'error' => ['text' => 'ስህተት ተከስቷል። እባክዎ እንደገና ይሞክሩ', 'type' => 'error'],
                'readonly' => ['text' => '🔒 እርስዎ ማንበብ ብቻ ፈቃድ አላቸው። ምንም ለውጥ ማድረግ አይቻልም።', 'type' => 'error']
            ];
            $msg_type = $messages[$_GET['msg']]['type'] ?? 'warning';
            $msg_text = $messages[$_GET['msg']]['text'] ?? 'እርምጃው ተጠናቋል';
            ?>
            <div class="message <?php echo $msg_type; ?>">
                <i class="fas fa-<?php echo $msg_type == 'success' ? 'check-circle' : ($msg_type == 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                <?php echo htmlspecialchars($msg_text); ?>
            </div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <?php if($current_user_role == 'super_admin'): ?>
                        <th>ቅርንጫፍ</th>
                    <?php endif; ?>
                    <th>Username</th>
                    <th>ሙሉ ስም</th>
                    <th>ሚና</th>
                    <th>የይለፍ ቃል</th>
                    <th>የተመዘገበበት</th>
                    <th>የተመዘገበበት ቀን</th>
                    <th>እርምጃዎች</th>
                </tr>
            </thead>
            <tbody>
                <?php if($users_result && mysqli_num_rows($users_result) > 0): ?>
                    <?php $counter = 1; while($user = mysqli_fetch_assoc($users_result)): 
                        $is_protected = in_array($user['id'], $protected_user_ids);
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <?php if($current_user_role == 'super_admin'): ?>
                            <td>
                                <span style="background: #e3f2fd; color: #1976d2; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                    <i class="fas fa-store"></i> <?php echo htmlspecialchars($user['branch_name'] ?? 'Main'); ?>
                                </span>
                            </span>
                            </td>
                        <?php endif; ?>
                        <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>">
                                <?php 
                                $role_names = [
                                    'super_admin' => 'ሱፐር አድሚን',
                                    'admin' => 'አድሚን',
                                    'seller' => 'ሻጭ'
                                ];
                                echo $role_names[$user['role']] ?? ucfirst($user['role']); 
                                ?>
                            </span>
                            <?php if($is_protected): ?>
                                <span class="owner-badge" style="margin-left: 8px;">
                                    <i class="fas fa-crown"></i> ባለቤት
                                </span>
                            <?php endif; ?>
                         </span>
                        <td>
                            <?php if($user['id'] != $_SESSION['user_id'] && !isReadOnly()): ?>
                                <button class="action-btn reset-password-btn" onclick="resetPassword(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>', '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                    <i class="fas fa-key"></i> የይለፍ ቃል ዳግም አስጀምር
                                </button>
                            <?php elseif($user['id'] == $_SESSION['user_id']): ?>
                                <span style="color: #7f8c8d; font-size: 12px;">የራስዎ</span>
                            <?php else: ?>
                                <span style="color: #7f8c8d; font-size: 12px;">🔒</span>
                            <?php endif; ?>
                         </span>
                        <td>
                            <?php 
                            if($user['created_by']) {
                                $creator_query = mysqli_query($conn, "SELECT username FROM users WHERE id = " . intval($user['created_by']));
                                if($creator_query && mysqli_num_rows($creator_query) > 0) {
                                    $creator = mysqli_fetch_assoc($creator_query);
                                    echo htmlspecialchars($creator['username'] ?? 'System');
                                } else {
                                    echo 'System';
                                }
                            } else {
                                echo 'System';
                            }
                            ?>
                         </span>
                        <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></span>
                        <td>
                            <?php if($user['id'] != $_SESSION['user_id'] && !$is_protected && !isReadOnly()): ?>
                                <?php if($current_user_role == 'super_admin'): ?>
                                    <?php if($user['role'] == 'seller'): ?>
                                        <button class="action-btn upgrade-btn" onclick="upgradeUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-arrow-up"></i> ከፍ ከፍ ማድረግ
                                        </button>
                                    <?php elseif($user['role'] == 'admin'): ?>
                                        <button class="action-btn downgrade-btn" onclick="downgradeUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-arrow-down"></i> ዝቅ ማድረግ
                                        </button>
                                    <?php endif; ?>
                                    <button class="action-btn delete-btn" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                        <i class="fas fa-trash"></i> ሰርዝ
                                    </button>
                                <?php elseif($current_user_role == 'admin'): ?>
                                    <?php if($user['role'] == 'seller'): ?>
                                        <button class="action-btn delete-btn" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-trash"></i> ሰርዝ
                                        </button>
                                    <?php elseif($user['role'] == 'admin'): ?>
                                        <span style="color: #7f8c8d; font-size: 12px; font-style: italic;">
                                            <i class="fas fa-shield-alt"></i> አስተዳዳሪ
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            <?php elseif(isReadOnly()): ?>
                                <span style="background: #fef3cd; color: #856404; padding: 6px 10px; border-radius: 5px; font-size: 12px; display: inline-block;">
                                    🔒 ማንበብ ብቻ
                                </span>
                            <?php elseif($is_protected && $user['id'] != $_SESSION['user_id']): ?>
                                <span class="owner-badge">
                                    <i class="fas fa-crown"></i> መሰረዝ አይቻልም
                                </span>
                            <?php elseif($user['id'] == $_SESSION['user_id']): ?>
                                <span style="color: #7f8c8d; font-size: 12px;">(እርስዎ)</span>
                            <?php endif; ?>
                         </span>
                     </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $current_user_role == 'super_admin' ? '9' : '8'; ?>" style="text-align:center;padding:60px;color:#7f8c8d;">
                            <i class="fas fa-users" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                            <h3>ምንም ተጠቃሚ አልተገኘም</h3>
                            <p>አዲስ ተጠቃሚ ለመመዝገብ ከታች ያለውን ቁልፍ ይጫኑ</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <?php if (!isReadOnly()): ?>
        <div style="text-align: center; margin-top: 30px;">
            <a href="register_user.php" class="add-btn">
                <i class="fas fa-user-plus"></i> አዲስ ተጠቃሚ መዝግብ
            </a>
        </div>
        <?php else: ?>
        <div style="text-align: center; margin-top: 30px; padding: 30px; background: #fef3cd; border-radius: 8px; border: 2px solid #ffc107;">
            <i class="fas fa-lock" style="font-size: 32px; color: #856404; margin-bottom: 15px; display: block;"></i>
            <h3 style="color: #856404; margin-bottom: 10px;">🔒 ማንበብ ብቻ ምክንያት</h3>
            <p style="color: #856404; margin: 0;">እርስዎ ማንበብ ብቻ ፈቃድ አላቸው። አዲስ ተጠቃሚ ማመዝገብ ወይም ተጠቃሚዎችን ማቀየር አይቻልም።</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Password Reset Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h2 style="color: #2c3e50; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                <i class="fas fa-key" style="color: #3498db;"></i> አዲስ የይለፍ ቃል
            </h2>
            <div class="user-info" id="modalUserInfo"><i class="fas fa-user"></i> ተጠቃሚ:</div>
            <div class="user-name" id="modalUserName"></div>
            <div class="password-display" id="modalPassword">********</div>
            <button class="copy-btn" id="copyPasswordBtn" onclick="copyPassword()">
                <i class="fas fa-copy"></i> ይለፍ ቃሉን ቅዳ
            </button>
            <p style="text-align: center; margin-top: 15px; color: #7f8c8d; font-size: 12px;">
                <i class="fas fa-shield-alt"></i> ይህን አዲስ የይለፍ ቃል ለተጠቃሚው ይንገሩት
            </p>
        </div>
    </div>

    <script>
        let currentUserId = null;
        let currentPassword = '';
        
        function deleteUser(id) {
            if(confirm('እርግጠኛ ነዎት ይህን ተጠቃሚ መሰረዝ ይፈልጋሉ?\n\nይህ እርምጃ መቀልበስ አይቻልም!')) {
                window.location.href = 'manage_users.php?action=delete&id=' + id;
            }
        }
        
        function upgradeUser(id) {
            if(confirm('ይህን ተጠቃሚ ወደ አስተዳዳሪነት ከፍ ማድረግ ይፈልጋሉ?')) {
                window.location.href = 'manage_users.php?action=upgrade&id=' + id;
            }
        }
        
        function downgradeUser(id) {
            if(confirm('ይህን ተጠቃሚ ወደ ሻጭነት ዝቅ ማድረግ ይፈልጋሉ?')) {
                window.location.href = 'manage_users.php?action=downgrade&id=' + id;
            }
        }
        
        function resetPassword(id, username, fullName) {
            if (!confirm('የ "' + fullName + '" የይለፍ ቃል ወደ 123 ይቀየራል። ይቀጥሉ?')) {
                return;
            }
            currentUserId = id;
            document.getElementById('modalUserInfo').innerHTML = '<i class="fas fa-user"></i> ተጠቃሚ: ' + username;
            document.getElementById('modalUserName').innerHTML = fullName;
            document.getElementById('modalPassword').innerHTML = '<i class="fas fa-spinner fa-spin"></i> በማስጀመር ላይ...';
            document.getElementById('copyPasswordBtn').disabled = true;
            document.getElementById('passwordModal').style.display = 'block';
            
            fetch('get_user_password.php?id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentPassword = data.password;
                        document.getElementById('modalPassword').innerHTML = currentPassword;
                        document.getElementById('copyPasswordBtn').disabled = false;
                    } else {
                        document.getElementById('modalPassword').innerHTML = 'ማስጀመር አልተቻለም';
                        document.getElementById('modalPassword').style.color = '#e74c3c';
                    }
                })
                .catch(error => {
                    document.getElementById('modalPassword').innerHTML = 'ስህተት ተከስቷል';
                    document.getElementById('modalPassword').style.color = '#e74c3c';
                });
        }
        
        function closeModal() {
            document.getElementById('passwordModal').style.display = 'none';
            currentPassword = '';
            document.getElementById('copyPasswordBtn').disabled = false;
            document.getElementById('copyPasswordBtn').innerHTML = '<i class="fas fa-copy"></i> ይለፍ ቃሉን ቅዳ';
            document.getElementById('copyPasswordBtn').className = 'copy-btn';
        }
        
        function copyPassword() {
            if (!currentPassword) return;
            navigator.clipboard.writeText(currentPassword).then(() => {
                const btn = document.getElementById('copyPasswordBtn');
                btn.innerHTML = '<i class="fas fa-check"></i> ተቀድቷል!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.innerHTML = '<i class="fas fa-copy"></i> ይለፍ ቃሉን ቅዳ';
                    btn.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                alert('ማስተካከል አልተቻለም');
            });
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('passwordModal');
            if (event.target == modal) closeModal();
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>
<?php 
if($users_result) mysqli_free_result($users_result);
mysqli_close($conn); 
?>