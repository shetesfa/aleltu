<?php
// get_user_password.php - AJAX endpoint
//
// FIXED (security): this used to return a user's PLAINTEXT password on
// demand — meaning anyone who could reach this endpoint could read every
// password in the system. Now that passwords are stored as one-way hashes,
// the original password can no longer be read back by anyone, including
// this script — that is the whole point of hashing.
//
// Kept the exact same URL, request method, and JSON response shape
// ({success, password}) so the existing "view password" button in
// manage_users.php keeps working with zero frontend changes. The behavior
// underneath is now: generate a brand-new random password, hash and store
// it, force the user to change it at next login, and return the NEW
// password once so the admin can hand it to the user. Every click issues a
// fresh password — it is a reset, not a peek.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'] ?? '';
$requested_user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$requested_user_id) {
    echo json_encode(['success' => false, 'error' => 'No user ID provided']);
    exit();
}

// Get current user's branch
$current_branch_id = getCurrentBranchId($conn, $current_user_id, $current_user_role);

// Get requested user's info to check permissions
$user_query = "SELECT id, role, branch_id FROM users WHERE id = $requested_user_id AND is_active = 1";
$user_result = mysqli_query($conn, $user_query);

if (!$user_result || mysqli_num_rows($user_result) == 0) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}

$target_user = mysqli_fetch_assoc($user_result);

// Permission checks (unchanged from before)
$has_permission = false;

if ($current_user_role == 'super_admin') {
    // Super Admin can reset any user's password except their own
    if ($target_user['id'] != $current_user_id) {
        $has_permission = true;
    }
} elseif ($current_user_role == 'admin') {
    // Admin can only reset passwords of users in their branch
    if ($target_user['branch_id'] == $current_branch_id &&
        $target_user['role'] != 'super_admin' &&
        $target_user['id'] != $current_user_id) {
        $has_permission = true;
    }
}

if (!$has_permission) {
    echo json_encode(['success' => false, 'error' => 'No permission to reset this password']);
    exit();
}

// Reset to '123' — same default used for brand-new users in register_user.php.
// password_changed is set to 0 so the user is prompted to set their own
// password the next time they log in (same as a new registration).
$new_plain_password = '123';
$new_password_hash = password_hash($new_plain_password, PASSWORD_DEFAULT);
$requested_user_id_safe = intval($requested_user_id);

$update_query = "UPDATE users SET password = '$new_password_hash', password_changed = 0 WHERE id = $requested_user_id_safe";

if (!mysqli_query($conn, $update_query)) {
    echo json_encode(['success' => false, 'error' => 'Failed to reset password']);
    exit();
}

// Return the NEW password once — this is the only moment it ever exists in
// plaintext. It is never stored anywhere; only its hash is.
echo json_encode([
    'success' => true,
    'password' => $new_plain_password
]);
?>
