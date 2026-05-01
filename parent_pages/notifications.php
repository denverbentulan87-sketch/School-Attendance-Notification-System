<?php
include 'includes/db.php';

if(!isset($_SESSION['email']) || $_SESSION['role'] !== 'parent'){
    echo "<p>Access denied.</p>"; exit;
}

$parent_email = $_SESSION['email'];

$stmt = $conn->prepare("SELECT id FROM user WHERE parent_email=?");
$stmt->bind_param("s", $parent_email);
$stmt->execute();
$res = $stmt->get_result();
$children_ids = [];
while($row = $res->fetch_assoc()) $children_ids[] = $row['id'];
if(empty($children_ids)) $children_ids[] = 0;
$ids = implode(',', $children_ids);

if(isset($_GET['read'])){
    $id = intval($_GET['read']);
    $conn->query("UPDATE notifications SET status='read' WHERE id=$id");
}

$sql = "SELECT n.*, u.name FROM notifications n JOIN user u ON n.student_id = u.id WHERE n.student_id IN ($ids) ORDER BY n.created_at DESC";

$temp = $conn->query($sql);
$total = 0; $unread = 0; $read_count = 0;
while($r = $temp->fetch_assoc()){
    $total++;
    if($r['status'] == 'unread') $unread++; else $read_count++;
}
$percent = $total > 0 ? round(($read_count/$total)*100) : 0;

$result = $conn->query($sql);

function timeAgo($datetime){
    $diff = time() - strtotime($datetime);
    if($diff < 60)    return "Just now";
    if($diff < 3600)  return floor($diff/60) . " mins ago";
    if($diff < 86400) return floor($diff/3600) . " hrs ago";
    return floor($diff/86400) . " days ago";
}
?>

<style>
.section-title { font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 20px; }

/* SUMMARY CARDS */
.notif-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px; margin-bottom: 20px;
}

.summary-card { background: #fff; border-radius: 14px; padding: 20px 22px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.summary-card h4 { font-size: 13px; color: #6b7280; font-weight: 500; margin-bottom: 8px; }
.summary-card .sv { font-size: 28px; font-weight: 700; color: #1e293b; }
.summary-card .sv.blue  { color: #2563eb; }
.summary-card .sv.green { color: #16a34a; }

/* PROGRESS */
.progress-wrap { background: #e5e7eb; border-radius: 99px; overflow: hidden; margin-bottom: 24px; height: 28px; }
.progress-fill {
    background: linear-gradient(90deg, #22c55e, #16a34a);
    height: 100%; min-width: 40px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 12px; font-weight: 600;
    border-radius: 99px; transition: width 0.6s ease;
}

/* NOTIFICATION LIST */
.notif-list { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }

.notif-item {
    display: flex; align-items: center; gap: 14px;
    padding: 16px 20px; border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}

.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: #f8fafc; }
.notif-item.unread { background: #eff6ff; border-left: 4px solid #3b82f6; }
.notif-item.read   { opacity: 0.72; }

.notif-icon {
    width: 38px; height: 38px; border-radius: 50%;
    background: #3b82f6; color: white;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; flex-shrink: 0;
}

.notif-body { flex: 1; }
.notif-body .n-student { font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 2px; }
.notif-body .n-msg     { font-size: 13px; color: #374151; margin-bottom: 4px; }
.notif-body .n-meta    { font-size: 12px; color: #94a3b8; }

.notif-actions { display: flex; gap: 8px; flex-shrink: 0; }

.mark-btn {
    padding: 6px 14px; background: #3b82f6; color: white;
    border-radius: 8px; font-size: 12px; font-weight: 500;
    text-decoration: none; white-space: nowrap;
    transition: background 0.2s;
}

.mark-btn:hover { background: #2563eb; }

.notif-empty { text-align: center; padding: 40px 20px; color: #94a3b8; font-size: 14px; }

/* INFO BOXES */
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 24px; }

.info-box { background: #fff; border-radius: 14px; padding: 18px 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.info-box h4 { font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 8px; }
.info-box p, .info-box li { font-size: 13px; color: #64748b; line-height: 1.6; }
.info-box ul { padding-left: 16px; }
</style>

<div class="section-title">🔔 Notifications</div>

<!-- SUMMARY -->
<div class="notif-summary">
    <div class="summary-card">
        <h4>Total</h4>
        <div class="sv"><?= $total ?></div>
    </div>
    <div class="summary-card">
        <h4>Unread</h4>
        <div class="sv blue"><?= $unread ?></div>
    </div>
    <div class="summary-card">
        <h4>Read</h4>
        <div class="sv green"><?= $read_count ?></div>
    </div>
</div>

<!-- PROGRESS -->
<div class="progress-wrap">
    <div class="progress-fill" style="width:<?= $percent ?>%;"><?= $percent ?>% Read</div>
</div>

<!-- LIST -->
<div class="notif-list">
<?php if($result->num_rows > 0): ?>
<?php while($row = $result->fetch_assoc()): ?>
<div class="notif-item <?= $row['status'] ?>">
    <div class="notif-icon"><?= strtoupper(substr($row['name'],0,1)) ?></div>
    <div class="notif-body">
        <div class="n-student"><?= htmlspecialchars($row['name']) ?></div>
        <div class="n-msg"><?= htmlspecialchars($row['message']) ?></div>
        <div class="n-meta"><?= timeAgo($row['created_at']) ?></div>
    </div>
    <div class="notif-actions">
        <?php if($row['status'] == 'unread'): ?>
            <a href="?page=notifications&read=<?= $row['id'] ?>" class="mark-btn">✔ Mark Read</a>
        <?php endif; ?>
    </div>
</div>
<?php endwhile; ?>
<?php else: ?>
    <div class="notif-empty">📭 No notifications yet</div>
<?php endif; ?>
</div>

<!-- INFO BOXES -->
<div class="info-grid">
    <div class="info-box">
        <h4>📌 About</h4>
        <p>View your child's attendance updates and school notifications here in real time.</p>
    </div>
    <div class="info-box">
        <h4>📊 Legend</h4>
        <ul>
            <li>🔵 Unread — new notification</li>
            <li>✅ Read — already viewed</li>
        </ul>
    </div>
    <div class="info-box">
        <h4>💡 Tip</h4>
        <p>Check daily to stay updated on your child's attendance status and school alerts.</p>
    </div>
</div>