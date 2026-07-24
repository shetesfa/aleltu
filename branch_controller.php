<?php
session_start();

/* =========================
   DATABASE CONNECTION
========================= */

include 'config.php';

/*
|--------------------------------------------------------------------------
| BRANCH CONTROLLER
|--------------------------------------------------------------------------
| Compatible with OLD places table
|--------------------------------------------------------------------------
*/

/* =========================
   SECURITY
========================= */

if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION['role'] != 'super_admin') {
    die("Access Denied!");
}

/* =========================
   FIX AUTO_INCREMENT - Always set to max(id) + 1 (or 1 if empty)
========================= */

function fixAutoIncrement($conn) {
    // Check if table has any records
    $count_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM places");
    $count_row = mysqli_fetch_assoc($count_query);
    $total_records = $count_row['total'];
    
    if ($total_records == 0) {
        // No records exist, set auto_increment to 1
        mysqli_query($conn, "ALTER TABLE places AUTO_INCREMENT = 1");
    } else {
        // Get the maximum ID
        $max_query = mysqli_query($conn, "SELECT MAX(id) as max_id FROM places");
        $max_row = mysqli_fetch_assoc($max_query);
        $max_id = $max_row['max_id'];
        
        if ($max_id !== null && $max_id > 0) {
            // Set auto_increment to max_id + 1
            $next_id = $max_id + 1;
            mysqli_query($conn, "ALTER TABLE places AUTO_INCREMENT = $next_id");
        } else {
            // Fallback - set to 1
            mysqli_query($conn, "ALTER TABLE places AUTO_INCREMENT = 1");
        }
    }
}

/* =========================
   ADD BRANCH
========================= */

if (isset($_POST['add_branch'])) {

    $place_name = mysqli_real_escape_string($conn, $_POST['place_name']);

    // Check if branch already exists (case insensitive)
    $check = mysqli_query($conn,
        "SELECT * FROM places WHERE LOWER(place_name) = LOWER('$place_name')"
    );

    if (mysqli_num_rows($check) > 0) {
        $msg = "Branch already exists!";
        $msg_type = "error";
    } else {
        // Fix auto increment before insert
        fixAutoIncrement($conn);
        
        // Insert new branch
        $insert = mysqli_query($conn,
            "INSERT INTO places(place_name) VALUES('$place_name')"
        );

        if ($insert) {
            $new_id = mysqli_insert_id($conn);
            $msg = "Branch Added Successfully (ID: $new_id)";
            $msg_type = "success";
        } else {
            $msg = "Failed To Add Branch: " . mysqli_error($conn);
            $msg_type = "error";
        }
    }
}

/* =========================
   DELETE BRANCH
========================= */

if (isset($_GET['delete'])) {

    $id = intval($_GET['delete']);
    
    // First, check if branch has any users
    $check_users = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE branch_id = '$id'");
    $user_count = mysqli_fetch_assoc($check_users);
    
    if ($user_count['total'] > 0) {
        // Cannot delete branch with associated users
        $msg = "Cannot delete branch! This branch has " . $user_count['total'] . " user(s) assigned. Please reassign or delete them first.";
        $msg_type = "error";
    } else {
        // Delete associated data
        mysqli_query($conn, "DELETE FROM products WHERE branch_id = '$id'");
        mysqli_query($conn, "DELETE FROM excel_data WHERE branch_id = '$id'");
        mysqli_query($conn, "DELETE FROM seller_inventory WHERE branch_id = '$id'");
        mysqli_query($conn, "DELETE FROM stock_logs WHERE branch_id = '$id'");
        mysqli_query($conn, "DELETE FROM transactions WHERE branch_id = '$id'");
        mysqli_query($conn, "DELETE FROM boss_daily WHERE branch_id = '$id'");
        mysqli_query($conn, "DELETE FROM boss_sessions WHERE branch_id = '$id'");
        mysqli_query($conn, "DELETE FROM boss_transactions WHERE branch_id = '$id'");
        
        // Delete the branch
        $delete = mysqli_query($conn, "DELETE FROM places WHERE id='$id'");
        
        if ($delete) {
            $msg = "Branch Deleted Successfully";
            $msg_type = "success";
            // Fix auto increment after delete
            fixAutoIncrement($conn);
        } else {
            $msg = "Failed To Delete Branch";
            $msg_type = "error";
        }
    }
}

/* =========================
   UPDATE BRANCH
========================= */

if (isset($_POST['update_branch'])) {

    $id = intval($_POST['place_id']);
    $place_name = mysqli_real_escape_string($conn, $_POST['place_name']);
    
    // Check if another branch already has this name
    $check = mysqli_query($conn,
        "SELECT * FROM places WHERE LOWER(place_name) = LOWER('$place_name') AND id != '$id'"
    );

    if (mysqli_num_rows($check) > 0) {
        $msg = "Another branch already exists with this name!";
        $msg_type = "error";
    } else {
        $update = mysqli_query($conn,
            "UPDATE places SET place_name='$place_name' WHERE id='$id'"
        );

        if ($update) {
            $msg = "Branch Updated Successfully";
            $msg_type = "success";
        } else {
            $msg = "Update Failed";
            $msg_type = "error";
        }
    }
}

// Fix auto_increment on every page load
fixAutoIncrement($conn);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Branch Controller - Aleltu POS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Back Button */
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-3px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-card:hover {
            transform: translateY(-3px);
        }

        .stat-info h2 {
            font-size: 32px;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }

        .stat-info small {
            font-size: 11px;
            color: #94a3b8;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }

        /* Message Alert */
        .alert {
            padding: 12px 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
            font-size: 14px;
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

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Form Section */
        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            font-size: 20px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-title i {
            color: #667eea;
        }

        .branch-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .branch-form input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s ease;
            min-width: 200px;
        }

        .branch-form input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .branch-form button {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .branch-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Edit Section */
        .edit-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            border: 2px solid #667eea;
        }

        .edit-title {
            font-size: 18px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cancel-btn {
            padding: 12px 20px;
            background: #94a3b8;
            color: white;
            border-radius: 12px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .cancel-btn:hover {
            background: #64748b;
        }

        /* Table Section */
        .table-wrapper {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            overflow-x: auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 500px;
        }

        table thead {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        }

        table th {
            color: white;
            padding: 14px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
            font-size: 14px;
        }

        table tr:hover {
            background: #f8fafc;
        }

        .branch-id {
            background: #e2e8f0;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            display: inline-block;
        }

        .branch-name {
            font-weight: 600;
            color: #1e293b;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn-edit, .btn-delete {
            padding: 6px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-edit:hover {
            background: #2563eb;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .user-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
        }

        .user-badge i {
            font-size: 11px;
        }

        .empty-badge {
            background: #f1f5f9;
            color: #64748b;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .info-footer {
            margin-top: 20px;
            text-align: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 12px;
            padding: 10px;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 12px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-info h2 {
                font-size: 28px;
            }
            
            .branch-form {
                flex-direction: column;
            }
            
            .branch-form input {
                width: 100%;
            }
            
            .branch-form button {
                width: 100%;
                justify-content: center;
            }
            
            table th, table td {
                padding: 10px 8px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn-edit, .btn-delete {
                width: 100%;
                justify-content: center;
            }
            
            .form-title {
                font-size: 18px;
            }
            
            .back-btn {
                padding: 10px 16px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            table th, table td {
                font-size: 12px;
                padding: 8px 6px;
            }
            
            .branch-id, .user-badge, .empty-badge {
                font-size: 10px;
                padding: 3px 8px;
            }
        }
    </style>
</head>
<body>

<div class="container">

    <a href="super_admin.php" class="back-btn">
        <i class="fas fa-arrow-left"></i>
        Back to Dashboard
    </a>

    <?php
    // Get fresh stats after any operation
    $total_branch_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM places");
    $total_branch = mysqli_fetch_assoc($total_branch_query);
    
    $max_id_query = mysqli_query($conn, "SELECT MAX(id) as max_id FROM places");
    $max_id_row = mysqli_fetch_assoc($max_id_query);
    $highest_id = $max_id_row['max_id'] ?? 0;
    
    // Calculate next ID
    if ($total_branch['total'] == 0) {
        $next_id = 1;
    } else {
        $next_id = $highest_id + 1;
    }

    $total_users_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users");
    $total_users = mysqli_fetch_assoc($total_users_query);
    ?>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h2><?= $total_branch['total']; ?></h2>
                <p>Total Branches</p>
                <small>Next ID: <?= $next_id; ?></small>
            </div>
            <div class="stat-icon">
                <i class="fas fa-code-branch"></i>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-info">
                <h2><?= $total_users['total']; ?></h2>
                <p>System Users</p>
            </div>
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
    </div>

    <!-- Message Display -->
    <?php if(isset($msg)): ?>
        <div class="alert alert-<?= $msg_type; ?>">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
            <span><?= htmlspecialchars($msg); ?></span>
        </div>
    <?php endif; ?>

    <!-- Edit Branch Section -->
    <?php if (isset($_GET['edit'])): 
        $id = intval($_GET['edit']);
        $edit_query = mysqli_query($conn, "SELECT * FROM places WHERE id='$id'");
        $edit_data = mysqli_fetch_assoc($edit_query);
        if($edit_data):
    ?>
        <div class="edit-card">
            <div class="edit-title">
                <i class="fas fa-edit"></i>
                Edit Branch
            </div>
            <form method="POST" class="branch-form">
                <input type="hidden" name="place_id" value="<?= $edit_data['id']; ?>">
                <input type="text" name="place_name" value="<?= htmlspecialchars($edit_data['place_name']); ?>" required placeholder="Branch Name">
                <button type="submit" name="update_branch">
                    <i class="fas fa-save"></i>
                    Update Branch
                </button>
                <a href="branch_controller.php" class="cancel-btn">
                    <i class="fas fa-times"></i>
                    Cancel
                </a>
            </form>
        </div>
    <?php endif; endif; ?>

    <!-- Add Branch Section -->
    <div class="form-card">
        <div class="form-title">
            <i class="fas fa-plus-circle"></i>
            Add New Branch
        </div>
        <form method="POST" class="branch-form">
            <input type="text" name="place_name" placeholder="Enter branch name (e.g., North Branch, Addis Ababa)" required>
            <button type="submit" name="add_branch">
                <i class="fas fa-plus"></i>
                Add Branch
            </button>
        </form>
        <div style="margin-top: 12px; padding: 8px 12px; background: #f1f5f9; border-radius: 10px; font-size: 12px; color: #475569;">
            <i class="fas fa-info-circle"></i> 
            <?php if($total_branch['total'] == 0): ?>
                The first branch will get ID #1.
            <?php else: ?>
                Next branch will get ID #<?= $next_id; ?>.
            <?php endif; ?>
            IDs start from 1 and increase sequentially.
        </div>
    </div>

    <!-- Branches List Table -->
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Branch Name</th>
                    <th>Users</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = mysqli_query($conn, "SELECT * FROM places ORDER BY id ASC");
                
                if(mysqli_num_rows($query) > 0):
                    while($row = mysqli_fetch_assoc($query)):
                        $branch_id = $row['id'];
                        $users_query = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE branch_id='$branch_id'");
                        $user_total = mysqli_fetch_assoc($users_query);
                ?>
                    <tr>
                        <td><span class="branch-id">#<?= $row['id']; ?></span></td>
                        <td class="branch-name"><?= htmlspecialchars($row['place_name']); ?></td>
                        <td>
                            <?php if($user_total['total'] > 0): ?>
                                <span class="user-badge">
                                    <i class="fas fa-user"></i> <?= $user_total['total']; ?> user(s)
                                </span>
                            <?php else: ?>
                                <span class="empty-badge">
                                    <i class="fas fa-user-slash"></i> No users
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a class="btn-edit" href="branch_controller.php?edit=<?= $row['id']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a class="btn-delete" href="branch_controller.php?delete=<?= $row['id']; ?>" 
                                   onclick="return confirmDelete('<?= htmlspecialchars($row['place_name']); ?>', <?= $user_total['total']; ?>)">
                                    <i class="fas fa-trash-alt"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php 
                    endwhile;
                else: 
                ?>
                    <tr>
                        <td colspan="4">
                            <div class="empty-state">
                                <i class="fas fa-store-slash"></i>
                                <p>No branches found. Click "Add Branch" to create your first branch.</p>
                                <p style="margin-top: 10px; font-size: 12px;">The first branch will get ID #1</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Info Footer -->
    <div class="info-footer">
        <i class="fas fa-info-circle"></i> 
        Branch IDs are automatically assigned starting from 1. Branches with assigned users cannot be deleted.
    </div>
</div>

<script>
    function confirmDelete(branchName, userCount) {
        if (userCount > 0) {
            alert(`⚠️ Cannot delete branch "${branchName}"!\n\nThis branch has ${userCount} user(s) assigned.\nPlease reassign or delete the users first.`);
            return false;
        }
        return confirm(`Are you sure you want to delete branch "${branchName}"?\n\nThis action cannot be undone.`);
    }
</script>

</body>
</html>