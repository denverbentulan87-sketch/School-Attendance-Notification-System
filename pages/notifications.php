<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include 'includes/db.php';

$success = "";
$error   = "";

if(isset($_POST['send'])){
    $msg = trim($_POST['message']);
    if(!empty($msg)){
        $stmt = $conn->prepare("INSERT INTO notifications (message, created_at) VALUES (?, NOW())");
        $stmt->bind_param("s", $msg);
        $stmt->execute();
        $success = "Notification sent successfully!";
    } else {
        $error = "Message cannot be empty.";
    }
}

$data = $conn->query("SELECT * FROM notifications ORDER BY id DESC");
$total_sent = $data ? $data->num_rows : 0;
?>

<style>
.section-title {
    font-size: 20px; font-weight: 600;
    color: #1e293b; margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px;
}

/* ALERT MESSAGES */
.alert {
    display: flex; align-items: center; gap: 10px;
    padding: 13px 16px; border-radius: 10px;
    font-size: 14px; font-weight: 500; margin-bottom: 20px;
}

.alert.success { background: #dcfce7; color: #15803d; border-left: 4px solid #22c55e; }
.alert.error   { background: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }

/* COMPOSE CARD */
.compose-card {
    background: #fff; border-radius: 14px;
    padding: 24px; margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.compose-card .card-header {
    font-size: 15px; font-weight: 600;
    color: #1e293b; margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
}

.compose-card textarea {
    width: 100%; padding: 13px 14px;
    border: 1px solid #e5e7eb; border-radius: 10px;
    font-size: 14px; font-family: 'Poppins', sans-serif;
    color: #374151; resize: none; min-height: 100px;
    outline: none; transition: border-color 0.2s;
    box-sizing: border-box;
}

.compose-card textarea:focus { border-color: #3b82f6; }
.compose-card textarea::placeholder { color: #9ca3af; }

.btn-send {
    width: 100%; margin-top: 12px;
    background: #3b82f6; color: white;
    border: none; padding: 12px;
    border-radius: 10px; font-size: 14px;
    font-family: 'Poppins', sans-serif;
    font-weight: 500; cursor: pointer;
    transition: background 0.2s;
}

.btn-send:hover { background: #2563eb; }

/* STATS ROW */
.notif-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px; margin-bottom: 24px;
}

.notif-stat {
    background: #fff; border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.notif-stat h4 { font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
.notif-stat .ns-val { font-size: 28px; font-weight: 700; color: #1e293b; }
.notif-stat.blue .ns-val { color: #3b82f6; }

/* TABLE CARD */
.table-card {
    background: #fff; border-radius: 14px;
    padding: 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.table-card .card-header {
    font-size: 15px; font-weight: 600;
    color: #1e293b; margin-bottom: 16px;
    display: flex; align-items: center; gap: 8px;
}

.data-table { width: 100%; border-collapse: collapse; }

.data-table th {
    background: #f8fafc; padding: 11px 14px;
    font-size: 12px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.5px;
    color: #64748b; text-align: left;
}

.data-table td {
    padding: 13px 14px; font-size: 14px;
    color: #374151; border-bottom: 1px solid #f1f5f9;
}

.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #f8fafc; }

.msg-text {
    max-width: 500px; white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis;
}

.date-text { color: #64748b; font-size: 13px; white-space: nowrap; }
</style>

<div class="section-title">🔔 Notifications</div>

<!-- ALERTS -->
<?php if($success): ?>
    <div class="alert success">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if($error): ?>
    <div class="alert error">⚠️ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- STATS -->
<div class="notif-stats">
    <div class="notif-stat blue">
        <h4>Total Sent</h4>
        <div class="ns-val"><?= $total_sent ?></div>
    </div>
</div>

<!-- COMPOSE -->
<div class="compose-card">
    <div class="card-header">📩 Send Message</div>
    <form method="POST">
        <textarea name="message" placeholder="Type your message here..." required></textarea>
        <button class="btn-send" name="send">Send Notification</button>
    </form>
</div>

<!-- SENT TABLE -->
<div class="table-card">
    <div class="card-header">📢 Sent Notifications</div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Message</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Re-fetch since num_rows consumed the pointer
        $data = $conn->query("SELECT * FROM notifications ORDER BY id DESC");
        if($data && $data->num_rows > 0):
            while($n = $data->fetch_assoc()):
        ?>
            <tr>
                <td><span class="msg-text"><?= htmlspecialchars($n['message']) ?></span></td>
                <td><span class="date-text"><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></span></td>
            </tr>
        <?php
            endwhile;
        else:
        ?>
            <tr>
                <td colspan="2" style="text-align:center;color:#9ca3af;padding:24px;">No notifications sent yet</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>