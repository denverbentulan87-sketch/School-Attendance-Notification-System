<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include 'includes/db.php';
include 'includes/mailer.php';

date_default_timezone_set('Asia/Manila');

$today = date('Y-m-d');

// ── Stats ──────────────────────────────────────────────────────────────
$r = $conn->query("
    SELECT status, COUNT(*) as cnt
    FROM attendance
    WHERE scan_date = '$today' AND status IN ('absent','late')
    GROUP BY status
");
$counts = ['absent' => 0, 'late' => 0];
while ($row = $r->fetch_assoc()) $counts[$row['status']] = (int)$row['cnt'];

// Total emails sent = total rows in notifications table
$totalRow  = $conn->query("SELECT COUNT(*) as total FROM notifications")->fetch_assoc();
$totalSent = $totalRow['total'] ?? 0;

// ── Manual "Send Now" — emails absent students for today ───────────────
$manualMsg = '';
if (isset($_POST['run_notify'])) {

    // Find all students who have no attendance record today (absent)
    $absentQ = $conn->query("
        SELECT u.id, u.fullname, u.parent_email
        FROM users u
        WHERE u.role = 'student'
          AND (u.parent_email IS NOT NULL AND u.parent_email != '')
          AND u.id NOT IN (
              SELECT student_id FROM attendance WHERE scan_date = '$today'
          )
    ");

    $sentCount = 0;
    $now_full  = date('Y-m-d H:i:s');

    while ($s = $absentQ->fetch_assoc()) {

        // Avoid duplicate notification for same student same day
        $dupCheck = $conn->prepare("
            SELECT id FROM notifications
            WHERE student_id = ? AND DATE(created_at) = ? AND message LIKE '%absent%'
            LIMIT 1
        ");
        $dupCheck->bind_param("is", $s['id'], $today);
        $dupCheck->execute();
        $dupCheck->store_result();
        if ($dupCheck->num_rows > 0) { $dupCheck->close(); continue; }
        $dupCheck->close();

        // Send absent email using the dedicated absent function
        send_absent_notification(
            $s['parent_email'],
            $s['fullname']
        );

        // Save to notifications table
        $msg      = "Your child {$s['fullname']} was marked ABSENT on " . date('F j, Y') . ".";
        $ins      = $conn->prepare("
            INSERT INTO notifications (student_id, message, created_at, sender, is_read)
            VALUES (?, ?, ?, 'system', 0)
        ");
        $ins->bind_param("iss", $s['id'], $msg, $now_full);
        $ins->execute();
        $ins->close();

        $sentCount++;
    }

    $manualMsg = $sentCount > 0
        ? "Absent notifications sent to $sentCount parent(s) for today."
        : "No new absent notifications to send for today (all already sent or no absent students).";
}

// ── Notification log from notifications table (last 100) ───────────────
$log = $conn->query("
    SELECT
        n.id,
        n.message,
        n.created_at,
        n.sender,
        n.is_read,
        u.fullname   AS student_name,
        u.parent_email
    FROM notifications n
    JOIN users u ON u.id = n.student_id
    ORDER BY n.created_at DESC
    LIMIT 100
");

// ── Helpers ────────────────────────────────────────────────────────────
function getInitials($name) {
    $parts    = array_filter(explode(' ', trim($name)));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part)
        $initials .= strtoupper(mb_substr($part, 0, 1));
    return $initials ?: '?';
}

function avatarColor($name) {
    $colors = [
        ['#dbeafe','#1d4ed8'],
        ['#dcfce7','#15803d'],
        ['#ede9fe','#6d28d9'],
        ['#fef3c7','#92400e'],
        ['#fee2e2','#b91c1c'],
        ['#e0f2fe','#0369a1'],
        ['#fce7f3','#9d174d'],
        ['#f0fdf4','#166534'],
    ];
    return $colors[abs(crc32($name)) % count($colors)];
}

// Detect status from the notification message
function detectStatus($message) {
    $msg = strtolower($message);
    if (str_contains($msg, 'absent'))  return 'absent';
    if (str_contains($msg, 'late'))    return 'late';
    if (str_contains($msg, 'present')) return 'present';
    return 'unknown';
}
?>

<style>
.notif-page { display: flex; flex-direction: column; gap: 24px; }

.section-title {
    font-size: 20px; font-weight: 700;
    color: #0f1923; display: flex; align-items: center; gap: 8px;
    margin-bottom: 4px;
}
.section-sub { font-size: 13px; color: #64748b; margin-bottom: 20px; }

.stat-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 16px;
}
.stat-card {
    background: #fff; border-radius: 14px; padding: 20px 22px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    display: flex; flex-direction: column; gap: 6px;
    position: relative; overflow: hidden;
}
.stat-card::before {
    content: ''; position: absolute; top: 0; left: 0;
    width: 4px; height: 100%; border-radius: 14px 0 0 14px;
}
.stat-card.blue::before  { background: #3b82f6; }
.stat-card.red::before   { background: #ef4444; }
.stat-card.amber::before { background: #f59e0b; }
.stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: #94a3b8; }
.stat-val   { font-size: 32px; font-weight: 800; line-height: 1; color: #0f1923; }
.stat-card.blue  .stat-val { color: #3b82f6; }
.stat-card.red   .stat-val { color: #ef4444; }
.stat-card.amber .stat-val { color: #f59e0b; }

.info-banner {
    background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px;
    padding: 16px 20px; display: flex; align-items: flex-start; gap: 12px;
    font-size: 14px; color: #1e40af;
}
.info-banner .icon { font-size: 20px; flex-shrink: 0; margin-top: 1px; }
.info-banner strong { display: block; margin-bottom: 4px; font-size: 14px; }
.info-banner p { margin: 0; color: #3b82f6; font-size: 13px; line-height: 1.5; }

.trigger-card {
    background: #fff; border-radius: 14px; padding: 20px 22px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    display: flex; align-items: center; justify-content: space-between;
    gap: 16px; flex-wrap: wrap;
}
.trigger-info strong { display: block; font-size: 14px; color: #0f1923; margin-bottom: 3px; }
.trigger-info span   { font-size: 13px; color: #64748b; }
.btn-trigger {
    background: #16a34a; color: #fff; border: none; padding: 10px 22px;
    border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; gap: 8px; transition: background 0.2s; white-space: nowrap;
}
.btn-trigger:hover { background: #15803d; }
.alert-success {
    background: #dcfce7; color: #15803d;
    border-left: 4px solid #22c55e;
    border-radius: 10px; padding: 12px 16px;
    font-size: 14px; font-weight: 500;
}
.alert-info {
    background: #eff6ff; color: #1d4ed8;
    border-left: 4px solid #3b82f6;
    border-radius: 10px; padding: 12px 16px;
    font-size: 14px; font-weight: 500;
}

.log-card { background: #fff; border-radius: 14px; padding: 22px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
.log-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 18px; flex-wrap: wrap; gap: 10px;
}
.log-title { font-size: 15px; font-weight: 700; color: #0f1923; display: flex; align-items: center; gap: 8px; }

.data-table { width: 100%; border-collapse: collapse; }
.data-table th {
    background: #f8fafc; padding: 11px 14px; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.6px; color: #64748b; text-align: left;
}
.data-table td {
    padding: 11px 14px; font-size: 14px; color: #374151;
    border-bottom: 1px solid #f1f5f9; vertical-align: middle;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #f8fafc; }

.student-cell { display: flex; align-items: center; gap: 10px; }
.avatar-circle {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; flex-shrink: 0;
    font-family: 'Sora', sans-serif; letter-spacing: 0.3px;
}
.student-cell-name { font-weight: 600; font-size: 13.5px; color: #1e293b; }

.badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 99px; font-size: 12px; font-weight: 700; }
.badge.absent  { background: #fee2e2; color: #dc2626; }
.badge.late    { background: #fef3c7; color: #d97706; }
.badge.present { background: #dcfce7; color: #16a34a; }
.badge.unknown { background: #f1f5f9; color: #64748b; }

.email-pill { font-size: 12px; color: #3b82f6; background: #eff6ff; padding: 3px 9px; border-radius: 6px; font-family: monospace; }
.date-cell  { color: #64748b; font-size: 13px; white-space: nowrap; }
.notif-sent-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 12px; color: #16a34a; font-weight: 600; }

.msg-cell { font-size: 12.5px; color: #475569; max-width: 260px; line-height: 1.4; }

.empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; font-size: 14px; }
.empty-state .empty-icon { font-size: 36px; margin-bottom: 8px; }
</style>

<div class="notif-page">

    <div>
        <div class="section-title">🔔 Notifications</div>
        <div class="section-sub">Email alerts sent to parents for absent and late students.</div>
    </div>

    <div class="stat-row">
        <div class="stat-card blue">
            <div class="stat-label">Total Emails Sent</div>
            <div class="stat-val"><?= number_format($totalSent) ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-label">Absent Today</div>
            <div class="stat-val"><?= $counts['absent'] ?></div>
        </div>
        <div class="stat-card amber">
            <div class="stat-label">Late Today</div>
            <div class="stat-val"><?= $counts['late'] ?></div>
        </div>
    </div>

    <div class="info-banner">
        <div class="icon">ℹ️</div>
        <div>
            <strong>How notifications work</strong>
            <p>
                ✅ <strong>Present / Late</strong> — Parent is emailed instantly when the student scans their QR at the gate.<br>
                ❌ <strong>Absent</strong> — Click <strong>Send Now</strong> below to email all parents whose child has no scan record for today.
                Duplicate notifications for the same student are automatically skipped.
            </p>
        </div>
    </div>

    <div class="trigger-card">
        <div class="trigger-info">
            <strong>📤 Send Today's Absent Notifications Now</strong>
            <span>Email all parents of students with no scan record for <?= date('F j, Y') ?>.</span>
        </div>
        <form method="POST">
            <button class="btn-trigger" name="run_notify">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
                </svg>
                Send Now
            </button>
        </form>
    </div>

    <?php if ($manualMsg): ?>
        <div class="<?= str_contains($manualMsg, 'No new') ? 'alert-info' : 'alert-success' ?>">
            <?= str_contains($manualMsg, 'No new') ? 'ℹ️' : '✅' ?> <?= htmlspecialchars($manualMsg) ?>
        </div>
    <?php endif; ?>

    <div class="log-card">
        <div class="log-header">
            <div class="log-title">
                📋 Email Notification Log
                <span style="font-size:12px;font-weight:500;color:#94a3b8;">(latest 100)</span>
            </div>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Status</th>
                    <th>Parent Email</th>
                    <th>Message</th>
                    <th>Date &amp; Time Sent</th>
                    <th>Email Sent</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($log && $log->num_rows > 0): ?>
                <?php while ($n = $log->fetch_assoc()):
                    $initials  = getInitials($n['student_name']);
                    [$bgColor, $textColor] = avatarColor($n['student_name']);
                    $status    = detectStatus($n['message']);
                    $statusIcon = $status === 'absent' ? '❌' : ($status === 'late' ? '⚠️' : '✅');
                ?>
                <tr>
                    <td>
                        <div class="student-cell">
                            <div class="avatar-circle" style="background:<?= $bgColor ?>;color:<?= $textColor ?>;">
                                <?= htmlspecialchars($initials) ?>
                            </div>
                            <span class="student-cell-name"><?= htmlspecialchars($n['student_name']) ?></span>
                        </div>
                    </td>
                    <td>
                        <span class="badge <?= $status ?>">
                            <?= $statusIcon ?> <?= ucfirst($status) ?>
                        </span>
                    </td>
                    <td>
                        <span class="email-pill">
                            <?= htmlspecialchars($n['parent_email'] ?? '—') ?>
                        </span>
                    </td>
                    <td class="msg-cell"><?= htmlspecialchars($n['message']) ?></td>
                    <td class="date-cell">
                        <?= date('M d, Y', strtotime($n['created_at'])) ?>
                        <br>
                        <span style="color:#94a3b8;font-size:12px;">
                            <?= date('h:i A', strtotime($n['created_at'])) ?>
                        </span>
                    </td>
                    <td><span class="notif-sent-badge">✅ Sent</span></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            No notifications sent yet. They will appear here once students are marked absent or late.
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>