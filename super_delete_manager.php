<?php
session_start();
require_once 'config.php';

// ---- Auth: super_admin ONLY ----
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header("Location: index.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$branch_id_filter = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? intval($_GET['branch_id']) : 0; // 0 = all branches
$branches = getAllBranches($conn);

$tab       = $_GET['tab'] ?? 'transactions';
$allowed_tabs = ['transactions', 'stock_logs', 'product_returns', 'withdrawals'];
if (!in_array($tab, $allowed_tabs, true)) $tab = 'transactions';

$search    = trim($_GET['search'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to   = trim($_GET['date_to'] ?? '');
$page      = max(1, intval($_GET['page'] ?? 1));
$per_page  = 50;
$offset    = ($page - 1) * $per_page;

function buildQueryString($overrides = []) {
    $params = array_merge($_GET, $overrides);
    return http_build_query($params);
}

// Safe UTF-8 truncation that does NOT depend on the mbstring extension
// (some hosts don't have it enabled; PCRE 'u' mode is core PHP).
function safe_truncate($str, $len = 40) {
    if ($str === null || $str === '') return '';
    $chars = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
    if ($chars === false) return $str; // non-UTF8 input, just return as-is
    if (count($chars) <= $len) return $str;
    return implode('', array_slice($chars, 0, $len)) . '...';
}

$rows = [];
$total_rows = 0;
$item_map = []; // transaction_id => [items]

if ($tab === 'transactions') {
    $where = [];
    $types = '';
    $vals = [];
    if ($branch_id_filter > 0) { $where[] = "t.branch_id = ?"; $types .= 'i'; $vals[] = $branch_id_filter; }
    if ($search !== '') { $where[] = "t.seller_name LIKE ?"; $types .= 's'; $vals[] = "%$search%"; }
    if ($date_from !== '') { $where[] = "t.transaction_date >= ?"; $types .= 's'; $vals[] = $date_from . ' 00:00:00'; }
    if ($date_to !== '') { $where[] = "t.transaction_date <= ?"; $types .= 's'; $vals[] = $date_to . ' 23:59:59'; }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $count_sql = "SELECT COUNT(*) c FROM transactions t $where_sql";
    $stmt = mysqli_prepare($conn, $count_sql);
    if ($types) mysqli_stmt_bind_param($stmt, $types, ...$vals);
    mysqli_stmt_execute($stmt);
    $total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    mysqli_stmt_close($stmt);

    $sql = "SELECT t.* FROM transactions t $where_sql ORDER BY t.transaction_date DESC LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    $types2 = $types . 'ii';
    $vals2 = array_merge($vals, [$per_page, $offset]);
    mysqli_stmt_bind_param($stmt, $types2, ...$vals2);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    mysqli_stmt_close($stmt);

    if ($rows) {
        $ids = array_column($rows, 'id');
        $in = implode(',', array_map('intval', $ids));
        $ires = mysqli_query($conn, "SELECT * FROM transaction_items WHERE transaction_id IN ($in)");
        while ($ir = mysqli_fetch_assoc($ires)) {
            $item_map[$ir['transaction_id']][] = $ir;
        }
    }

} elseif ($tab === 'stock_logs') {
    $where = [];
    $types = '';
    $vals = [];
    if ($branch_id_filter > 0) { $where[] = "branch_id = ?"; $types .= 'i'; $vals[] = $branch_id_filter; }
    if ($search !== '') { $where[] = "(item_name LIKE ? OR seller_name LIKE ?)"; $types .= 'ss'; $vals[] = "%$search%"; $vals[] = "%$search%"; }
    if ($date_from !== '') { $where[] = "date_added >= ?"; $types .= 's'; $vals[] = $date_from . ' 00:00:00'; }
    if ($date_to !== '') { $where[] = "date_added <= ?"; $types .= 's'; $vals[] = $date_to . ' 23:59:59'; }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM stock_logs $where_sql");
    if ($types) mysqli_stmt_bind_param($stmt, $types, ...$vals);
    mysqli_stmt_execute($stmt);
    $total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT * FROM stock_logs $where_sql ORDER BY date_added DESC LIMIT ? OFFSET ?");
    $types2 = $types . 'ii';
    $vals2 = array_merge($vals, [$per_page, $offset]);
    mysqli_stmt_bind_param($stmt, $types2, ...$vals2);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    mysqli_stmt_close($stmt);

} elseif ($tab === 'product_returns') {
    $where = [];
    $types = '';
    $vals = [];
    if ($branch_id_filter > 0) { $where[] = "branch_id = ?"; $types .= 'i'; $vals[] = $branch_id_filter; }
    if ($search !== '') { $where[] = "(product_name LIKE ? OR seller_name LIKE ?)"; $types .= 'ss'; $vals[] = "%$search%"; $vals[] = "%$search%"; }
    if ($date_from !== '') { $where[] = "gregorian_date >= ?"; $types .= 's'; $vals[] = $date_from . ' 00:00:00'; }
    if ($date_to !== '') { $where[] = "gregorian_date <= ?"; $types .= 's'; $vals[] = $date_to . ' 23:59:59'; }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM product_returns $where_sql");
    if ($types) mysqli_stmt_bind_param($stmt, $types, ...$vals);
    mysqli_stmt_execute($stmt);
    $total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT * FROM product_returns $where_sql ORDER BY gregorian_date DESC LIMIT ? OFFSET ?");
    $types2 = $types . 'ii';
    $vals2 = array_merge($vals, [$per_page, $offset]);
    mysqli_stmt_bind_param($stmt, $types2, ...$vals2);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    mysqli_stmt_close($stmt);

} elseif ($tab === 'withdrawals') {
    $where = [];
    $types = '';
    $vals = [];
    if ($branch_id_filter > 0) { $where[] = "branch_id = ?"; $types .= 'i'; $vals[] = $branch_id_filter; }
    if ($search !== '') { $where[] = "(username LIKE ? OR reason LIKE ?)"; $types .= 'ss'; $vals[] = "%$search%"; $vals[] = "%$search%"; }
    if ($date_from !== '') { $where[] = "created_at >= ?"; $types .= 's'; $vals[] = $date_from . ' 00:00:00'; }
    if ($date_to !== '') { $where[] = "created_at <= ?"; $types .= 's'; $vals[] = $date_to . ' 23:59:59'; }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM daily_withdrawals $where_sql");
    if ($types) mysqli_stmt_bind_param($stmt, $types, ...$vals);
    mysqli_stmt_execute($stmt);
    $total_rows = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "SELECT * FROM daily_withdrawals $where_sql ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $types2 = $types . 'ii';
    $vals2 = array_merge($vals, [$per_page, $offset]);
    mysqli_stmt_bind_param($stmt, $types2, ...$vals2);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($r = mysqli_fetch_assoc($res)) $rows[] = $r;
    mysqli_stmt_close($stmt);
}

$total_pages = max(1, ceil($total_rows / $per_page));
?>
<!DOCTYPE html>
<html lang="am">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Super Admin - Data Delete Manager</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { box-sizing: border-box; margin:0; padding:0; font-family: 'Segoe UI', Tahoma, sans-serif; }
body { background:#f4f5f7; color:#222; }
.header { background: linear-gradient(135deg,#8b0000,#c0392b); color:#fff; padding:20px 25px; }
.header h1 { font-size:20px; display:flex; align-items:center; gap:10px; }
.header p { margin-top:6px; font-size:13px; opacity:.9; }
.warning-banner { background:#fff3cd; border:1px solid #ffe08a; color:#7a5b00; padding:12px 20px; font-size:13px; display:flex; gap:10px; align-items:center; }
.container { padding:20px; max-width:1300px; margin:0 auto; }
.controls { background:#fff; border-radius:10px; padding:16px; margin-bottom:16px; box-shadow:0 1px 4px rgba(0,0,0,.08); }
.controls form { display:flex; flex-wrap:wrap; gap:10px; align-items:end; }
.controls label { font-size:12px; color:#666; display:block; margin-bottom:4px; }
.controls input, .controls select { padding:8px 10px; border:1px solid #ddd; border-radius:6px; font-size:13px; }
.controls button { background:#333; color:#fff; border:none; padding:9px 16px; border-radius:6px; cursor:pointer; font-size:13px; }
.tabs { display:flex; gap:6px; margin-bottom:16px; flex-wrap:wrap; }
.tabs a { padding:10px 18px; background:#fff; border-radius:8px; text-decoration:none; color:#444; font-size:13px; font-weight:600; box-shadow:0 1px 3px rgba(0,0,0,.06); }
.tabs a.active { background:#8b0000; color:#fff; }
table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.08); }
th, td { padding:10px 12px; text-align:left; font-size:13px; border-bottom:1px solid #eee; }
th { background:#fafafa; color:#555; font-weight:600; }
tr:hover { background:#fff8f8; }
.badge { padding:3px 8px; border-radius:20px; font-size:11px; font-weight:600; }
.badge-return { background:#ffe3e3; color:#a10000; }
.badge-normal { background:#e3f5e6; color:#0a7d29; }
.del-btn { background:#dc3545; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; display:inline-flex; align-items:center; gap:5px; }
.del-btn:hover { background:#b02a37; }
.del-btn:disabled { background:#ccc; cursor:not-allowed; }
.empty { padding:40px; text-align:center; color:#999; }
.pagination { display:flex; gap:6px; justify-content:center; margin-top:16px; flex-wrap:wrap; }
.pagination a, .pagination span { padding:7px 12px; background:#fff; border-radius:6px; text-decoration:none; color:#444; font-size:13px; box-shadow:0 1px 3px rgba(0,0,0,.06); }
.pagination .current { background:#8b0000; color:#fff; }
.item-list { font-size:11px; color:#777; margin-top:3px; }
.back-link { color:#fff; text-decoration:none; font-size:13px; opacity:.9; }
.small { font-size:11px; color:#888; }
</style>
</head>
<body>

<div class="header">
    <h1><i class="fas fa-user-shield"></i> Super Admin Delete Manager</h1>
    <p><a href="admin_dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to dashboard</a> &nbsp;|&nbsp; Logged in as <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Super Admin'); ?></p>
</div>

<div class="warning-banner">
    <i class="fas fa-triangle-exclamation"></i>
    Deletions here are permanent and cannot be undone from this page. Stock is automatically adjusted where relevant. Every delete is recorded in the audit log with a full snapshot, who deleted it, and when.
</div>

<div class="container">

    <div class="tabs">
        <a class="<?php echo $tab==='transactions'?'active':''; ?>" href="?<?php echo buildQueryString(['tab'=>'transactions','page'=>1]); ?>"><i class="fas fa-receipt"></i> Transactions</a>
        <a class="<?php echo $tab==='stock_logs'?'active':''; ?>" href="?<?php echo buildQueryString(['tab'=>'stock_logs','page'=>1]); ?>"><i class="fas fa-boxes-stacked"></i> Stock Logs</a>
        <a class="<?php echo $tab==='product_returns'?'active':''; ?>" href="?<?php echo buildQueryString(['tab'=>'product_returns','page'=>1]); ?>"><i class="fas fa-rotate-left"></i> Product Returns</a>
        <a class="<?php echo $tab==='withdrawals'?'active':''; ?>" href="?<?php echo buildQueryString(['tab'=>'withdrawals','page'=>1]); ?>"><i class="fas fa-money-bill-wave"></i> Withdrawals</a>
    </div>

    <div class="controls">
        <form method="GET">
            <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
            <div>
                <label>Branch</label>
                <select name="branch_id">
                    <option value="0">All Branches</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?php echo $b['id']; ?>" <?php echo $branch_id_filter==$b['id']?'selected':''; ?>><?php echo htmlspecialchars($b['place_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="seller / item name">
            </div>
            <div>
                <label>From</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            <div>
                <label>To</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            <button type="submit"><i class="fas fa-filter"></i> Filter</button>
            <button type="button" onclick="window.location='?tab=<?php echo $tab; ?>'"><i class="fas fa-rotate"></i> Reset</button>
        </form>
    </div>

    <table>
        <thead>
        <?php if ($tab === 'transactions'): ?>
            <tr><th>ID</th><th>Date</th><th>Seller</th><th>Branch</th><th>Items</th><th>Total</th><th>Payment</th><th></th></tr>
        <?php elseif ($tab === 'stock_logs'): ?>
            <tr><th>ID</th><th>Date</th><th>Seller</th><th>Item</th><th>Qty</th><th>Source</th><th>Branch</th><th></th></tr>
        <?php elseif ($tab === 'product_returns'): ?>
            <tr><th>ID</th><th>Date</th><th>Seller</th><th>Product</th><th>Qty</th><th>Reason</th><th>Branch</th><th></th></tr>
        <?php else: ?>
            <tr><th>ID</th><th>Date</th><th>User</th><th>Amount</th><th>Reason</th><th>Branch</th><th></th></tr>
        <?php endif; ?>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="empty">No records found.</td></tr>
        <?php else: ?>

            <?php if ($tab === 'transactions'): foreach ($rows as $r):
                $items = $item_map[$r['id']] ?? [];
                $item_summary = implode(', ', array_map(fn($i) => $i['product_name'].' x'.$i['quantity'], $items));
                $confirm_msg = "Delete Transaction #{$r['id']}?\n\nTotal: {$r['total_amount']} | Seller: {$r['seller_name']}\n\nThis will RESTORE stock for:\n" .
                    implode("\n", array_map(fn($i) => "  + {$i['product_name']}: +{$i['quantity']}", $items)) .
                    "\n\nThis cannot be undone.";
            ?>
                <tr>
                    <td>#<?php echo $r['id']; ?></td>
                    <td><?php echo htmlspecialchars($r['transaction_date']); ?></td>
                    <td><?php echo htmlspecialchars($r['seller_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['branch_id']); ?></td>
                    <td><?php echo count($items); ?> item(s)<div class="item-list"><?php echo htmlspecialchars($item_summary); ?></div></td>
                    <td><?php echo number_format($r['total_amount'],2); ?></td>
                    <td><?php echo htmlspecialchars($r['payment_method']); ?></td>
                    <td><button class="del-btn" onclick='confirmDelete("transaction", <?php echo $r['id']; ?>, <?php echo json_encode($confirm_msg); ?>, this)'><i class="fas fa-trash"></i> Delete</button></td>
                </tr>
            <?php endforeach; ?>

            <?php elseif ($tab === 'stock_logs'): foreach ($rows as $r):
                $confirm_msg = "Delete Stock Log #{$r['id']}?\n\nItem: {$r['item_name']}\nQuantity: {$r['quantity']}\nSource: {$r['source']}\n\nThis will reverse the stock change (subtract {$r['quantity']} from current stock).\n\nThis cannot be undone.";
            ?>
                <tr>
                    <td>#<?php echo $r['id']; ?></td>
                    <td><?php echo htmlspecialchars($r['date_added']); ?></td>
                    <td><?php echo htmlspecialchars($r['seller_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['item_name']); ?></td>
                    <td><?php echo $r['quantity']; ?> <?php echo htmlspecialchars($r['unit']); ?></td>
                    <td><span class="badge <?php echo $r['source']==='return'?'badge-return':'badge-normal'; ?>"><?php echo htmlspecialchars($r['source']); ?></span></td>
                    <td><?php echo htmlspecialchars($r['branch_id']); ?></td>
                    <td><button class="del-btn" onclick='confirmDelete("stock_log", <?php echo $r['id']; ?>, <?php echo json_encode($confirm_msg); ?>, this)'><i class="fas fa-trash"></i> Delete</button></td>
                </tr>
            <?php endforeach; ?>

            <?php elseif ($tab === 'product_returns'): foreach ($rows as $r):
                $confirm_msg = "Delete Product Return #{$r['id']}?\n\nProduct: {$r['product_name']}\nQuantity: {$r['quantity']}\n\nThis will RESTORE {$r['quantity']} back into current stock (undoing the return).\n\nThis cannot be undone.";
            ?>
                <tr>
                    <td>#<?php echo $r['id']; ?></td>
                    <td><?php echo htmlspecialchars($r['gregorian_date']); ?></td>
                    <td><?php echo htmlspecialchars($r['seller_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['product_name']); ?></td>
                    <td><?php echo $r['quantity']; ?> <?php echo htmlspecialchars($r['unit']); ?></td>
                    <td class="small"><?php echo htmlspecialchars(safe_truncate($r['reason'] ?? '', 40)); ?></td>
                    <td><?php echo htmlspecialchars($r['branch_id']); ?></td>
                    <td><button class="del-btn" onclick='confirmDelete("product_return", <?php echo $r['id']; ?>, <?php echo json_encode($confirm_msg); ?>, this)'><i class="fas fa-trash"></i> Delete</button></td>
                </tr>
            <?php endforeach; ?>

            <?php else: foreach ($rows as $r):
                $confirm_msg = "Delete Withdrawal #{$r['id']}?\n\nUser: {$r['username']}\nAmount: {$r['amount']}\n\nNo stock impact. This cannot be undone.";
            ?>
                <tr>
                    <td>#<?php echo $r['id']; ?></td>
                    <td><?php echo htmlspecialchars($r['created_at']); ?></td>
                    <td><?php echo htmlspecialchars($r['username']); ?></td>
                    <td><?php echo number_format($r['amount'],2); ?></td>
                    <td class="small"><?php echo htmlspecialchars(safe_truncate($r['reason'] ?? '', 40)); ?></td>
                    <td><?php echo htmlspecialchars($r['branch_id']); ?></td>
                    <td><button class="del-btn" onclick='confirmDelete("withdrawal", <?php echo $r['id']; ?>, <?php echo json_encode($confirm_msg); ?>, this)'><i class="fas fa-trash"></i> Delete</button></td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>

        <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php if ($page > 1): ?><a href="?<?php echo buildQueryString(['page'=>$page-1]); ?>">&laquo; Prev</a><?php endif; ?>
        <span class="current"><?php echo $page; ?> / <?php echo $total_pages; ?></span>
        <?php if ($page < $total_pages): ?><a href="?<?php echo buildQueryString(['page'=>$page+1]); ?>">Next &raquo;</a><?php endif; ?>
    </div>

</div>

<script>
function confirmDelete(type, id, message, btnEl) {
    if (!confirm(message)) return;
    btnEl.disabled = true;
    btnEl.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';

    fetch('delete_record.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const row = btnEl.closest('tr');
            row.style.background = '#ffe3e3';
            row.style.transition = 'opacity 0.4s';
            setTimeout(() => { row.style.opacity = '0'; setTimeout(() => row.remove(), 400); }, 300);
        } else {
            alert('Delete failed: ' + data.message);
            btnEl.disabled = false;
            btnEl.innerHTML = '<i class="fas fa-trash"></i> Delete';
        }
    })
    .catch(err => {
        alert('Network/server error: ' + err);
        btnEl.disabled = false;
        btnEl.innerHTML = '<i class="fas fa-trash"></i> Delete';
    });
}
</script>

</body>
</html>
