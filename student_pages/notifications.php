<?php
include 'includes/db.php';

$user_id = $_SESSION['user_id'];
$filter = $_GET['filter'] ?? 'all';

if(isset($_GET['read'])){
    $id = intval($_GET['read']);
    $conn->query("UPDATE notifications SET is_read=1 WHERE id='$id'");
}

if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM notifications WHERE id='$id'");
}


$where = "(student_id='$user_id' OR student_id IS NULL)";

if($filter === 'unread'){
    $where .= " AND is_read=0";
}

$data = $conn->query("
    SELECT id, message, created_at, sender, is_read
    FROM notifications 
    WHERE $where
    ORDER BY created_at DESC
");

$total = $conn->query("
    SELECT COUNT(*) as total 
    FROM notifications 
    WHERE student_id='$user_id' OR student_id IS NULL
")->fetch_assoc()['total'];

$unread = $conn->query("
    SELECT COUNT(*) as total 
    FROM notifications 
    WHERE (student_id='$user_id' OR student_id IS NULL) AND is_read=0
")->fetch_assoc()['total'];

function timeAgo($datetime){
    $time = strtotime($datetime);
    $diff = time() - $time;

    if($diff < 60) return "Just now";
    if($diff < 3600) return floor($diff/60) . " mins ago";
    if($diff < 86400) return floor($diff/3600) . " hrs ago";
    return floor($diff/86400) . " days ago";
}
?>

<h2>🔔Notifications</h2>

<!-- SUMMARY -->
<div class="notif-summary">
    <div class="card">
        <h4>Total</h4>
        <p><?= $total ?></p>
    </div>

    <div class="card">
        <h4>Unread</h4>
        <p style="color:red;"><?= $unread ?></p>
    </div>
</div>

<!-- FILTER -->
<div class="filters">
    <a href="?page=notifications&filter=all" class="<?= $filter=='all'?'active':'' ?>">All</a>
    <a href="?page=notifications&filter=unread" class="<?= $filter=='unread'?'active':'' ?>">Unread</a>
</div>

<!-- LIST -->
<div class="notif-container">

<?php if($data->num_rows > 0): ?>
<?php while($n = $data->fetch_assoc()): 

    // TYPE STYLE
    $type = $n['type'] ?? 'info';

    $icon = "ℹ️";
    $color = "#3b82f6";

    if($type == 'success'){ $icon="✅"; $color="#22c55e"; }
    if($type == 'warning'){ $icon="⚠️"; $color="#f59e0b"; }
    if($type == 'danger'){ $icon="❌"; $color="#ef4444"; }

?>

<div class="notif-item <?= $n['is_read'] ? 'read' : 'unread' ?>">

    <div class="icon" style="background:<?= $color ?>;">
        <?= $icon ?>
    </div>

    <div class="notif-content">
        <p><?= htmlspecialchars($n['message']) ?></p>

        <small>
            <?= timeAgo($n['created_at']) ?> • <?= ucfirst($n['sender']) ?>
        </small>
    </div>

    <!-- ACTIONS -->
    <div class="actions">
        <?php if(!$n['is_read']): ?>
            <a href="?page=notifications&read=<?= $n['id'] ?>">✔</a>
        <?php endif; ?>
        <a href="?page=notifications&delete=<?= $n['id'] ?>" onclick="return confirm('Delete notification?')">🗑</a>
    </div>

</div>

<?php endwhile; ?>
<?php else: ?>

<div class="empty">
    📭 No notifications yet
</div>

<?php endif; ?>

</div>

<!-- STYLE -->
<style>
.notif-summary{
    display:flex;
    gap:15px;
    margin-bottom:15px;
}

.card{
    background:white;
    padding:15px;
    border-radius:10px;
    box-shadow:0 2px 5px rgba(0,0,0,0.05);
}

.card p{
    font-size:20px;
    font-weight:bold;
}

.filters a{
    margin-right:10px;
    padding:6px 12px;
    background:#e5e7eb;
    border-radius:6px;
    text-decoration:none;
    color:#333;
}

.filters a.active{
    background:#3b82f6;
    color:white;
}

.notif-container{
    background:white;
    border-radius:10px;
    padding:10px;
}

.notif-item{
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px;
    border-bottom:1px solid #eee;
    transition:0.3s;
}

.notif-item:hover{
    background:#f9fafb;
}

.notif-item.unread{
    background:#eff6ff;
    border-left:4px solid #3b82f6;
}

.notif-item.read{
    opacity:0.7;
}

.icon{
    width:35px;
    height:35px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-size:16px;
}

.notif-content{
    flex:1;
}

.notif-content p{
    margin-bottom:5px;
}

.notif-content small{
    color:#666;
}

.actions a{
    margin-left:8px;
    text-decoration:none;
    font-size:16px;
}

.empty{
    text-align:center;
    padding:20px;
    color:#999;
}
</style>