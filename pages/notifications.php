<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include 'includes/db.php';

$success = "";

// SEND MESSAGE
if(isset($_POST['send'])){
    $msg = trim($_POST['message']);

    if(!empty($msg)){
        $stmt = $conn->prepare("INSERT INTO notifications (message, created_at) VALUES (?, NOW())");
        $stmt->bind_param("s", $msg);
        $stmt->execute();

        $success = "✅ Notification sent successfully!";
    }
}

// FETCH
$data = $conn->query("SELECT * FROM notifications ORDER BY id DESC");
?>

<div class="table-container">

    <h2>📩Send Message</h2>

    <!-- SUCCESS MESSAGE -->
    <?php if($success): ?>
        <div class="success-msg"><?= $success ?></div>
    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" class="notify-form">
        <textarea name="message" placeholder="Type your message here..." required></textarea>
        <button class="btn-send" name="send">Send Notification</button>
    </form>

    <!-- LIST -->
    <h2 style="margin-top:30px;">📢Sent Notifications</h2>

    <table>
        <tr>
            <th>Message</th>
            <th>Date</th>
        </tr>

        <?php if($data && $data->num_rows > 0): ?>
            <?php while($n = $data->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($n['message']) ?></td>
                    <td><?= date('M d, Y h:i A', strtotime($n['created_at'])) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="2" style="text-align:center;">No notifications yet</td>
            </tr>
        <?php endif; ?>
    </table>

</div>

<style>
.notify-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.notify-form textarea {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    min-height: 80px;
    resize: none;
}

.btn-send {
    background: #3b82f6;
    color: white;
    padding: 10px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.btn-send:hover {
    background: #2563eb;
}

.success-msg {
    background: #d1fae5;
    color: #065f46;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 10px;
}

/* TABLE STYLE */
table {
    width: 100%;
    border-collapse: collapse;
}

table th {
    background: #f3f4f6;
    padding: 10px;
    text-align: left;
}

table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}
</style>