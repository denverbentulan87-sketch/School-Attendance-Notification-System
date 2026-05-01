<?php
include 'includes/db.php';

$user_id = $_SESSION['user_id'];
$filter  = $_GET['filter'] ?? 'all';

if(isset($_GET['read'])){
    $id = intval($_GET['read']);
    $conn->query("UPDATE notifications SET is_read=1 WHERE id='$id'");
}

if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM notifications WHERE id='$id'");
}

$where = "(student_id='$user_id' OR student_id IS NULL)";
if($filter === 'unread') $where .= " AND is_read=0";

$data = $conn->query("
    SELECT id, message, created_at, sender, is_read
    FROM notifications 
    WHERE $where
    ORDER BY created_at DESC
");

$total = $conn->query("
    SELECT COUNT(*) as total FROM notifications 
    WHERE student_id='$user_id' OR student_id IS NULL
")->fetch_assoc()['total'];

$unread = $conn->query("
    SELECT COUNT(*) as total FROM notifications 
    WHERE (student_id='$user_id' OR student_id IS NULL) AND is_read=0
")->fetch_assoc()['total'];

function timeAgo($datetime){
    $diff = time() - strtotime($datetime);
    if($diff < 60)    return "Just now";
    if($diff < 3600)  return floor($diff/60) . " mins ago";
    if($diff < 86400) return floor($diff/3600) . " hrs ago";
    return floor($diff/86400) . " days ago";
}
?>

<style>
/* ── SUMMARY CARDS ── */
.notif-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.summary-card {
    background: #fff;
    border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.summary-card h4 {
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
    margin-bottom: 8px;
}

.summary-card .summary-value {
    font-size: 30px;
    font-weight: 700;
    color: #1e293b;
}

.summary-card .summary-value.red { color: #dc2626; }

/* ── FILTER TABS ── */
.filter-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 18px;
}

.filter-tabs a {
    padding: 8px 18px;
    border-radius: 99px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    background: #f1f5f9;
    color: #64748b;
    transition: all 0.2s;
}

.filter-tabs a:hover { background: #e2e8f0; }

.filter-tabs a.active {
    background: #3b82f6;
    color: #fff;
}

/* ── NOTIFICATION LIST ── */
.notif-list {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.notif-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}

.notif-item:last-child { border-bottom: none; }

.notif-item:hover { background: #f8fafc; }

.notif-item.unread {
    background: #eff6ff;
    border-left: 4px solid #3b82f6;
}

.notif-item.read { opacity: 0.72; }

.notif-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    color: #fff;
}

.notif-body { flex: 1; }

.notif-body .notif-msg {
    font-size: 14px;
    color: #1e293b;
    margin-bottom: 4px;
    line-height: 1.4;
}

.notif-body .notif-meta {
    font-size: 12px;
    color: #94a3b8;
}

.notif-actions {
    display: flex;
    gap: 8px;
    flex-shrink: 0;
}

.notif-actions a {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 15px;
    transition: background 0.15s;
    background: #f1f5f9;
}

.notif-actions a:hover { background: #e2e8f0; }

.notif-empty {
    text-align: center;
    padding: 40px 20px;
    color: #94a3b8;
    font-size: 14px;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>

<div class="section-title">🔔 Notifications</div>

<!-- SUMMARY -->
<div class="notif-summary">
    <div class="summary-card">
        <h4>Total</h4>
        <div class="summary-value"><?= $total ?></div>
    </div>
    <div class="summary-card">
        <h4>Unread</h4>
        <div class="summary-value red"><?= $unread ?></div>
    </div>
</div>

<!-- FILTER TABS -->
<div class="filter-tabs">
    <a href="?page=notifications&filter=all"    class="<?= $filter=='all'   ?'active':'' ?>">All</a>
    <a href="?page=notifications&filter=unread" class="<?= $filter=='unread'?'active':'' ?>">Unread</a>
</div>

<!-- NOTIFICATION LIST -->
<div class="notif-list">

<?php if($data->num_rows > 0): ?>
<?php while($n = $data->fetch_assoc()):
    $type = $n['type'] ?? 'info';
    $icon  = "ℹ️"; $bg = "#3b82f6";
    if($type == 'success'){ $icon="✅"; $bg="#22c55e"; }
    if($type == 'warning'){ $icon="⚠️"; $bg="#f59e0b"; }
    if($type == 'danger') { $icon="❌"; $bg="#ef4444"; }
?>

<div class="notif-item <?= $n['is_read'] ? 'read' : 'unread' ?>">

    <div class="notif-icon" style="background:<?= $bg ?>;"><?= $icon ?></div>

    <div class="notif-body">
        <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
        <div class="notif-meta">
            <?= timeAgo($n['created_at']) ?> &bull; <?= ucfirst(htmlspecialchars($n['sender'])) ?>
        </div>
    </div>

    <div class="notif-actions">
        <?php if(!$n['is_read']): ?>
            <a href="?page=notifications&filter=<?= $filter ?>&read=<?= $n['id'] ?>" title="Mark as read">✔</a>
        <?php endif; ?>
        <a href="?page=notifications&filter=<?= $filter ?>&delete=<?= $n['id'] ?>"
           onclick="return confirm('Delete this notification?')" title="Delete">🗑</a>
    </div>

</div>

<?php endwhile; ?>
<?php else: ?>
<div class="notif-empty">📭 No notifications yet</div>
<?php endif; ?>

</div>