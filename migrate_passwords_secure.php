<?php
// migrate_passwords_secure.php
//
// ONE-TIME USE. Upload this file next to your other PHP files, open it once
// in your browser while logged in as a super_admin, click the confirm
// button, and it converts every plaintext password in the `users` table
// into a proper one-way hash (bcrypt, via PHP's password_hash()).
//
// Safe to run more than once by accident — any password that is already
// hashed is detected and skipped, so nothing gets double-hashed or broken.
//
// IMPORTANT: delete this file from your server once it reports success.
// Anyone who could reach it while logged in as super_admin could re-run it
// (harmlessly, since it's idempotent) — but there's no reason to leave it
// sitting on the server after it's done its one job.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    echo "Only a super_admin can run this, and only while logged in. Please log in as a super_admin first.";
    exit();
}

function looks_already_hashed($password) {
    // bcrypt hashes always start with one of these prefixes.
    return (bool) preg_match('/^\$2[axy]\$/', $password);
}

$report = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && $_POST['confirm'] === 'YES_MIGRATE_NOW') {
    $migrated = 0;
    $already_hashed = 0;
    $skipped_empty = 0;

    $result = mysqli_query($conn, "SELECT id, username, password FROM users");

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $id = intval($row['id']);
            $password = $row['password'];

            if ($password === null || $password === '') {
                $skipped_empty++;
                continue;
            }

            if (looks_already_hashed($password)) {
                $already_hashed++;
                continue;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            // Escape defensively even though password_hash() output never
            // contains a quote character.
            $hash_safe = mysqli_real_escape_string($conn, $hash);
            mysqli_query($conn, "UPDATE users SET password = '$hash_safe' WHERE id = $id");
            $migrated++;
        }
    }

    $report = [
        'migrated' => $migrated,
        'already_hashed' => $already_hashed,
        'skipped_empty' => $skipped_empty,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Password Migration</title>
<style>
  body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 640px; margin: 60px auto; padding: 0 20px; color: #2c3e50; }
  .box { background: #fff; border: 1px solid #e0e0e0; border-radius: 10px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
  h1 { font-size: 22px; margin-bottom: 10px; }
  .warn { background: #fff8e1; border-left: 4px solid #f39c12; padding: 12px 16px; border-radius: 6px; margin: 16px 0; font-size: 14px; }
  .ok { background: #e8f8f0; border-left: 4px solid #16a34a; padding: 12px 16px; border-radius: 6px; margin: 16px 0; }
  button { background: #667eea; color: #fff; border: none; padding: 12px 22px; border-radius: 8px; font-size: 15px; cursor: pointer; }
  button:hover { background: #5568d3; }
  code { background: #f4f4f4; padding: 2px 6px; border-radius: 4px; }
</style>
</head>
<body>
<div class="box">
<h1>🔐 Password Migration</h1>

<?php if ($report): ?>
    <div class="ok">
        <strong>Done.</strong><br>
        Passwords hashed just now: <strong><?php echo $report['migrated']; ?></strong><br>
        Already hashed (skipped, no change made): <strong><?php echo $report['already_hashed']; ?></strong><br>
        Empty passwords (skipped): <strong><?php echo $report['skipped_empty']; ?></strong>
    </div>
    <div class="warn">
        Please delete <code>migrate_passwords_secure.php</code> from your server now — it has done its job and doesn't need to stay.
    </div>
<?php else: ?>
    <p>This will convert every plaintext password currently in your <code>users</code> table into a secure one-way hash. Logins keep working exactly the same — people type the same password they always have.</p>
    <div class="warn">
        This changes real data. Make sure you have a database backup before continuing (you should already have one from before the file fixes).
    </div>
    <form method="POST">
        <input type="hidden" name="confirm" value="YES_MIGRATE_NOW">
        <button type="submit">Yes, hash all passwords now</button>
    </form>
<?php endif; ?>
</div>
</body>
</html>
