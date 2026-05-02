<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include 'includes/db.php';

$user_id = $_SESSION['user_id'];
$filter  = $_GET['filter'] ?? 'all';

// ── Fetch student's attendance records as notification feed ──────────────
$status_filter = '';
if ($filter === 'absent') $status_filter = "AND a.status = 'absent'";
if ($filter === 'late')   $status_filter = "AND a.status = 'late'";
if ($filter === 'present') $status_filter = "AND a.status = 'present'";

$records = $conn->query("
    SELECT a.status, a.scan_date, a.scan_time, a.date_added
    FROM attendance a
    WHERE a.student_id = '$user_id'
    $status_filter
    ORDER BY a.scan_date DESC, a.scan_time DESC
    LIMIT 60
");

// ── Counts ───────────────────────────────────────────────────────────────
$counts = ['present' => 0, 'late' => 0, 'absent' => 0];
$countRes = $conn->query("
    SELECT status, COUNT(*) as cnt
    FROM attendance
    WHERE student_id = '$user_id'
    GROUP BY status
");
while ($c = $countRes->fetch_assoc()) {
    if (isset($counts[$c['status']])) $counts[$c['status']] = (int)$c['cnt'];
}
$total = array_sum($counts);

// ── Parent email notified? ────────────────────────────────────────────────
$parentRow = $conn->query("SELECT parent_email FROM users WHERE id='$user_id'")->fetch_assoc();
$parentEmail = $parentRow['parent_email'] ?? null;

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)    return "Just now";
    if ($diff < 3600)  return floor($diff / 60) . " mins ago";
    if ($diff < 86400) return floor($diff / 3600) . " hrs ago";
    if ($diff < 604800) return floor($diff / 86400) . " days ago";
    return date('M d, Y', strtotime($datetime));
}
?>

<style>
.notif-page { display: flex; flex-direction: column; gap: 22px; }

.section-title {
    font-size: 20px; font-weight: 700;
    color: #0f1923; display: flex; align-items: center; gap: 8px;
}
.section-sub { font-size: 13px; color: #64748b; margin-top: 3px; }

/* STAT ROW */
.stat-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 14px;
}

.stat-card {
    background: #fff; border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    position: relative; overflow: hidden;
}

.stat-card::before {
    content: ''; position: absolute;
    top: 0; left: 0; width: 4px; height: 100%;
    border-radius: 14px 0 0 14px;
}

.stat-card.green::before  { background: #16a34a; }
.stat-card.amber::before  { background: #f59e0b; }
.stat-card.red::before    { background: #ef4444; }
.stat-card.slate::before  { background: #64748b; }

.stat-label {
    font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.7px;
    color: #94a3b8; margin-bottom: 6px;
}

.stat-val {
    font-size: 30px; font-weight: 800; line-height: 1;
    color: #0f1923;
}

.stat-card.green .stat-val { color: #16a34a; }
.stat-card.amber .stat-val { color: #f59e0b; }
.stat-card.red   .stat-val { color: #ef4444; }

/* PARENT EMAIL BANNER */
.parent-banner {
    background: #f0fdf4; border: 1px solid #bbf7d0;
    border-radius: 12px; padding: 14px 18px;
    display: flex; align-items: center; gap: 12px;
    font-size: 13px; color: #15803d;
}

.parent-banner .icon { font-size: 20px; flex-shrink: 0; }

.parent-banner span { color: #16a34a; font-weight: 600; }

.no-parent-banner {
    background: #fff7ed; border: 1px solid #fed7aa;
    border-radius: 12px; padding: 14px 18px;
    display: flex; align-items: center; gap: 12px;
    font-size: 13px; color: #c2410c;
}

/* FILTER TABS */
.filter-tabs {
    display: flex; gap: 8px; flex-wrap: wrap;
}

.filter-tabs a {
    padding: 7px 16px; border-radius: 99px;
    text-decoration: none; font-size: 13px; font-weight: 500;
    background: #f1f5f9; color: #64748b;
    transition: all 0.15s;
}

.filter-tabs a:hover { background: #e2e8f0; }
.filter-tabs a.active { background: #0f1923; color: #fff; }

/* NOTIFICATION LIST */
.notif-list {
    background: #fff; border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.notif-item {
    display: flex; align-items: center; gap: 14px;
    padding: 15px 20px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}

.notif-item:last-child { border-bottom: none; }
.notif-item:hover { background: #f8fafc; }

/* Left accent by status */
.notif-item.present { border-left: 4px solid #16a34a; }
.notif-item.late    { border-left: 4px solid #f59e0b; }
.notif-item.absent  { border-left: 4px solid #ef4444; }

.notif-icon {
    width: 40px; height: 40px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; flex-shrink: 0;
}

.notif-icon.present { background: #dcfce7; }
.notif-icon.late    { background: #fef3c7; }
.notif-icon.absent  { background: #fee2e2; }

.notif-body { flex: 1; }

.notif-msg {
    font-size: 14px; color: #1e293b; font-weight: 500;
    margin-bottom: 3px; line-height: 1.4;
}

.notif-meta {
    font-size: 12px; color: #94a3b8;
    display: flex; align-items: center; gap: 6px;
}

.notif-meta .dot { color: #d1d5db; }

/* Right: email sent badge */
.email-badge {
    font-size: 11px; font-weight: 600;
    color: #16a34a; background: #dcfce7;
    padding: 3px 9px; border-radius: 99px;
    white-space: nowrap; flex-shrink: 0;
}

.email-badge.no-email {
    color: #94a3b8; background: #f1f5f9;
}

/* Status badge inline */
.status-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 9px; border-radius: 99px;
    font-size: 11px; font-weight: 700;
}

.status-pill.present { background: #dcfce7; color: #16a34a; }
.status-pill.late    { background: #fef3c7; color: #d97706; }
.status-pill.absent  { background: #fee2e2; color: #dc2626; }

.notif-empty {
    text-align: center; padding: 48px 20px;
    color: #94a3b8;
}

.notif-empty .empty-icon { font-size: 40px; margin-bottom: 10px; }
.notif-empty p { font-size: 14px; }
</style>

<div class="notif-page">

    <div>
        <div class="section-title">🔔 My Notifications</div>
        <div class="section-sub">Your attendance activity and parent email alerts.</div>
    </div>

    <!-- Stats -->
    <div class="stat-row">
        <div class="stat-card slate">
            <div class="stat-label">Total Records</div>
            <div class="stat-val"><?= $total ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Present</div>
            <div class="stat-val"><?= $counts['present'] ?></div>
        </div>
        <div class="stat-card amber">
            <div class="stat-label">Late</div>
            <div class="stat-val"><?= $counts['late'] ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-label">Absent</div>
            <div class="stat-val"><?= $counts['absent'] ?></div>
        </div>
    </div>

    <!-- Parent email banner -->
    <?php if ($parentEmail): ?>
    <div class="parent-banner">
        <div class="icon">📧</div>
        <div>
            Your parent / guardian is notified at <span><?= htmlspecialchars($parentEmail) ?></span>
            automatically whenever you are marked <strong>late</strong> or <strong>absent</strong>.
        </div>
    </div>
    <?php else: ?>
    <div class="no-parent-banner">
        <div class="icon">⚠️</div>
        <div>No parent email is linked to your account. Please ask your admin to add one so absence/late alerts can be sent.</div>
    </div>
    <?php endif; ?>

    <!-- Filter tabs -->
    <div class="filter-tabs">
        <a href="?page=notifications&filter=all"     class="<?= $filter==='all'     ?'active':'' ?>">All</a>
        <a href="?page=notifications&filter=present" class="<?= $filter==='present' ?'active':'' ?>">✅ Present</a>
        <a href="?page=notifications&filter=late"    class="<?= $filter==='late'    ?'active':'' ?>">⚠️ Late</a>
        <a href="?page=notifications&filter=absent"  class="<?= $filter==='absent'  ?'active':'' ?>">❌ Absent</a>
    </div>

    <!-- Notification feed -->
    <div class="notif-list">
    <?php if ($records && $records->num_rows > 0): ?>
        <?php while ($n = $records->fetch_assoc()):
            $status = $n['status'];
            $icons  = ['present' => '✅', 'late' => '⚠️', 'absent' => '❌'];
            $icon   = $icons[$status] ?? 'ℹ️';

            $timeStr = $status === 'absent'
                ? date('F j, Y', strtotime($n['scan_date']))
                : date('h:i A', strtotime($n['scan_time'])) . ' · ' . date('F j, Y', strtotime($n['scan_date']));

            $messages = [
                'present' => 'You were marked <strong>Present</strong> — QR scanned successfully.',
                'late'    => 'You arrived <strong>Late</strong> — your parent has been notified by email.',
                'absent'  => 'You were recorded as <strong>Absent</strong> — your parent has been notified by email.',
            ];
            $msg = $messages[$status] ?? 'Attendance recorded.';

            $emailSent = ($status !== 'present') && $parentEmail;
        ?>
        <div class="notif-item <?= $status ?>">
            <div class="notif-icon <?= $status ?>"><?= $icon ?></div>
            <div class="notif-body">
                <div class="notif-msg"><?= $msg ?></div>
                <div class="notif-meta">
                    <span class="status-pill <?= $status ?>"><?= ucfirst($status) ?></span>
                    <span class="dot">•</span>
                    <span><?= $timeStr ?></span>
                </div>
            </div>
            <?php if ($status !== 'present'): ?>
                <span class="email-badge <?= $emailSent ? '' : 'no-email' ?>">
                    <?= $emailSent ? '📧 Parent notified' : '📧 No email set' ?>
                </span>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="notif-empty">
            <div class="empty-icon">📭</div>
            <p>No attendance records found<?= $filter !== 'all' ? ' for this filter' : '' ?>.</p>
        </div>
    <?php endif; ?>
    </div>

</div>