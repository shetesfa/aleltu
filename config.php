<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host     = 'localhost';
$user     = 'root';
$password = '';
$database = 'aleltu';

$conn = mysqli_init();
mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
mysqli_real_connect($conn, $host, $user, $password, $database);

if (!$conn || mysqli_connect_errno()) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8mb4");

// ============================================
// BRANCH FUNCTIONS - SESSION-CACHED VERSION
// Every function checks session FIRST before any DB query
// ============================================

function getUserBranch($conn, $user_id) {
    if (!$user_id) return null;
    // Use session cache if available
    if (isset($_SESSION['branch_id'], $_SESSION['branch_name']) && $_SESSION['branch_id'] > 0) {
        return ['branch_id' => $_SESSION['branch_id'], 'branch_name' => $_SESSION['branch_name']];
    }
    $stmt = mysqli_prepare($conn, "SELECT u.branch_id, p.place_name as branch_name FROM users u LEFT JOIN places p ON u.branch_id = p.id WHERE u.id = ?");
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

function setBranchSession($branch_id, $branch_name) {
    $_SESSION['branch_id']           = $branch_id;
    $_SESSION['branch_name']         = $branch_name;
    $_SESSION['selected_branch_id']  = $branch_id;
    $_SESSION['selected_branch_name']= $branch_name;
    $_SESSION['current_branch_id']   = $branch_id;
    $_SESSION['current_branch_name'] = $branch_name;
}

function getCurrentBranchId($conn, $user_id, $user_role) {
    // SUPER ADMIN
    if ($user_role == 'super_admin') {
        if (isset($_GET['branch_id']) && $_GET['branch_id'] > 0) {
            $branch_id = intval($_GET['branch_id']);
            $name = getCurrentBranchName($conn, $branch_id);
            setBranchSession($branch_id, $name);
            return $branch_id;
        }
        foreach (['branch_id','selected_branch_id','current_branch_id'] as $key) {
            if (!empty($_SESSION[$key]) && $_SESSION[$key] > 0) return intval($_SESSION[$key]);
        }
        return 0;
    }

    // REGULAR USERS - check session first (avoids DB hit on every page)
    foreach (['branch_id','selected_branch_id','current_branch_id'] as $key) {
        if (!empty($_SESSION[$key]) && $_SESSION[$key] > 0) return intval($_SESSION[$key]);
    }

    // Only hit DB if session has nothing
    if ($user_id) {
        $stmt = mysqli_prepare($conn, "SELECT branch_id FROM users WHERE id = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                if ($row['branch_id'] > 0) {
                    $_SESSION['branch_id'] = $row['branch_id'];
                    return $row['branch_id'];
                }
            }
        }
    }

    // Last resort - first active branch
    $result = mysqli_query($conn, "SELECT id FROM places WHERE status = 'active' LIMIT 1");
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['id'];
    }
    return 1;
}

function getCurrentBranchName($conn, $branch_id) {
    if (!$branch_id || $branch_id <= 0) return 'No Branch Selected';
    // Session cache check
    if (isset($_SESSION['branch_name'], $_SESSION['branch_id']) && $_SESSION['branch_id'] == $branch_id) {
        return $_SESSION['branch_name'];
    }
    // Cache entire branch list in session to avoid repeated queries
    if (!isset($_SESSION['_branch_cache'])) {
        $_SESSION['_branch_cache'] = [];
    }
    if (isset($_SESSION['_branch_cache'][$branch_id])) {
        return $_SESSION['_branch_cache'][$branch_id];
    }
    $stmt = mysqli_prepare($conn, "SELECT place_name FROM places WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $branch_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($result)) {
            $_SESSION['_branch_cache'][$branch_id] = $row['place_name'];
            $_SESSION['branch_name'] = $row['place_name'];
            return $row['place_name'];
        }
    }
    return 'Unknown Branch';
}

function getAllBranches($conn) {
    // Cache in session - places table almost never changes
    if (isset($_SESSION['_all_branches']) && !empty($_SESSION['_all_branches'])) {
        return $_SESSION['_all_branches'];
    }
    $branches = [];
    $result = mysqli_query($conn, "SELECT id, place_name FROM places WHERE status = 'active' ORDER BY place_name");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $branches[] = $row;
        }
    }
    $_SESSION['_all_branches'] = $branches;
    return $branches;
}

function displayBranchSelector($conn, $current_branch_id) {
    $branches = getAllBranches($conn);
    ?>
    <div class="branch-selector" style="margin:10px 0;padding:10px;background:rgba(255,255,255,0.1);border-radius:8px;">
        <form method="GET" action="" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <i class="fas fa-store"></i>
            <span style="color:white;">ቅርንጫፍ ምረጥ:</span>
            <select name="branch_id" onchange="this.form.submit()" style="padding:8px;border-radius:5px;border:1px solid #ddd;background:white;color:#333;min-width:200px;">
                <option value="">-- ቅርንጫፍ ምረጥ --</option>
                <?php foreach ($branches as $branch): ?>
                    <option value="<?php echo $branch['id']; ?>" <?php echo ($current_branch_id == $branch['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($branch['place_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <noscript><button type="submit">Go</button></noscript>
        </form>
    </div>
    <?php
}

// ============================================================
// READ-ONLY USER SUPPORT
// Checks if the current logged-in user is marked as read_only
// ============================================================

function loadReadOnlyFlag($conn, $user_id) {
    // Cache in session so we don't query on every page load
    if (isset($_SESSION['read_only'])) {
        return;
    }
    if (!$user_id) {
        $_SESSION['read_only'] = 0;
        return;
    }
    $stmt = mysqli_prepare($conn, "SELECT read_only FROM users WHERE id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        $_SESSION['read_only'] = ($row && !empty($row['read_only'])) ? 1 : 0;
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['read_only'] = 0;
    }
}

function isReadOnly() {
    return !empty($_SESSION['read_only']);
}

function userHasBranchAccess($conn, $user_id, $branch_id) {
    $user_role = $_SESSION['role'] ?? '';
    if ($user_role == 'super_admin') {
        // Super admin can access all active branches
        $branches = getAllBranches($conn); // uses cache
        foreach ($branches as $b) {
            if ($b['id'] == $branch_id) return true;
        }
        return false;
    }
    $my_branch = getCurrentBranchId($conn, $user_id, $user_role); // uses session cache
    return ($my_branch == $branch_id);
}
?>
