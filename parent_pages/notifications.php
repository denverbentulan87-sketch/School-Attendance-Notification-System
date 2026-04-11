<?php
include 'includes/db.php';

if(!isset($_SESSION['email']) || $_SESSION['role'] !== 'parent'){
    echo "<p>Access denied.</p>";
    exit;
}

$parent_email = $_SESSION['email'];

// Get children IDs
$stmt = $conn->prepare("SELECT id FROM user WHERE parent_email=?");
$stmt->bind_param("s", $parent_email);
$stmt->execute();
$res = $stmt->get_result();

$children_ids = [];
while($row = $res->fetch_assoc()){
    $children_ids[] = $row['id'];
}

if(empty($children_ids)){
    $children_ids[] = 0; // prevents SQL error
}

$ids = implode(',', $children_ids);

// Mark as read
if(isset($_GET['read'])){
    $notif_id = intval($_GET['read']);
    $conn->query("UPDATE notifications SET status='read' WHERE id=$notif_id");
}

// Fetch notifications
$sql = "
    SELECT n.*, u.name 
    FROM notifications n
    JOIN user u ON n.student_id = u.id
    WHERE n.student_id IN ($ids)
    ORDER BY n.created_at DESC
";

$result = $conn->query($sql);

// Stats
$total = 0;
$unread = 0;
$read = 0;

$temp = $conn->query($sql);
while($r = $temp->fetch_assoc()){
    $total++;
    if($r['status'] == 'unread') $unread++;
    else $read++;
}

$percent = $total > 0 ? round(($read/$total)*100) : 0;

// Reload result
$result = $conn->query($sql);
?>

<h2>🔔 Notifications</h2>

<div class="layout">

    <!-- LEFT -->
    <div class="main">

        <!-- SUMMARY -->
        <div class="cards">
            <div class="card total">
                <h4>Total</h4>
                <p><?= $total ?></p>
            </div>
            <div class="card unread">
                <h4>Unread</h4>
                <p><?= $unread ?></p>
            </div>
            <div class="card read">
                <h4>Read</h4>
                <p><?= $read ?></p>
            </div>
        </div>

        <!-- PROGRESS -->
        <div class="progress-box">
            <div class="progress-bar" style="width: <?= $percent ?>%">
                <?= $percent ?>%
            </div>
        </div>

        <!-- TABLE -->
        <div class="table-box">
            <table>
                <tr>
                    <th>Student</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>

                <?php if($result->num_rows == 0): ?>
                    <tr>
                        <td colspan="4" class="empty-row">
                            📭 No notification records yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['message']) ?></td>
                        <td class="<?= $row['status'] ?>">
                            <?= ucfirst($row['status']) ?>
                        </td>
                        <td><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>

        <!-- CARD LIST VIEW -->
        <div class="notif-container">

        <?php if($result->num_rows > 0): ?>
            <?php 
            $result = $conn->query($sql);
            while($row = $result->fetch_assoc()): 
            ?>
                <div class="notif <?= $row['status'] ?>">
                    <div class="icon">
                        <?= ($row['status'] == 'unread') ? '🔵' : '⚪' ?>
                    </div>

                    <div class="body">
                        <p>
                            <strong><?= htmlspecialchars($row['name']) ?></strong>
                            <?= htmlspecialchars($row['message']) ?>
                        </p>
                        <span><?= date("M d, Y h:i A", strtotime($row['created_at'])) ?></span>
                    </div>

                    <?php if($row['status'] == 'unread'): ?>
                        <a href="?page=notifications&read=<?= $row['id'] ?>" class="btn">
                            Mark
                        </a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>

        </div>

    </div>

    <!-- RIGHT SIDEBAR -->
    <div class="side">
        <div class="box">
            <h4>📌 About</h4>
            <p>See your child’s attendance updates here.</p>
        </div>

        <div class="box">
            <h4>📊 Guide</h4>
            <ul>
                <li>🔵 Unread</li>
                <li>⚪ Read</li>
            </ul>
        </div>

        <div class="box">
            <h4>💡 Tip</h4>
            <p>Check daily to stay updated.</p>
        </div>
    </div>

</div>

<style>

/* LAYOUT */
.layout{
    display:grid;
    grid-template-columns: 2fr 1fr;
    gap:20px;
}

/* CARDS */
.cards{
    display:grid;
    grid-template-columns: repeat(3,1fr);
    gap:10px;
}

.card{
    background:white;
    padding:15px;
    border-radius:12px;
    text-align:center;
    box-shadow:0 3px 10px rgba(0,0,0,0.05);
}

.card p{
    font-size:20px;
    font-weight:bold;
}

.total p{ color:#6b7280; }
.unread p{ color:#2563eb; }
.read p{ color:#16a34a; }

/* PROGRESS */
.progress-box{
    background:#e5e7eb;
    border-radius:20px;
    margin:15px 0;
    overflow:hidden;
}

.progress-bar{
    background:#16a34a;
    color:white;
    padding:5px;
    text-align:center;
}

/* TABLE */
.table-box{
    background:white;
    padding:15px;
    border-radius:12px;
}

table{
    width:100%;
}

th, td{
    padding:8px;
    border-bottom:1px solid #eee;
}

.empty-row{
    text-align:center;
    color:#777;
}

/* LIST */
.notif{
    display:flex;
    align-items:center;
    gap:10px;
    background:white;
    padding:10px;
    border-radius:10px;
    margin-top:10px;
}

.icon{
    font-size:18px;
}

.body{
    flex:1;
}

.btn{
    background:#2563eb;
    color:white;
    padding:5px 8px;
    border-radius:6px;
    text-decoration:none;
}

/* SIDEBAR */
.side{
    display:flex;
    flex-direction:column;
    gap:10px;
}

.box{
    background:white;
    padding:15px;
    border-radius:12px;
}

</style>