<?php
include 'includes/db.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'parent') {
    echo "<p>Access denied.</p>"; exit;
}

$parent_email = $_SESSION['email'];

// Get all children linked to this parent
$stmt = $conn->prepare("SELECT id, fullname FROM users WHERE parent_email = ?");
$stmt->bind_param("s", $parent_email);
$stmt->execute();
$childRes = $stmt->get_result();
$children = [];
while ($row = $childRes->fetch_assoc()) $children[$row['id']] = $row['fullname'];
$child_ids = !empty($children) ? implode(',', array_keys($children)) : '0';

// Filter
$filter = $_GET['filter'] ?? 'all';
$status_sql = '';
if ($filter === 'absent') $status_sql = "AND a.status = 'absent'";
if ($filter === 'late')   $status_sql = "AND a.status = 'late'";
if ($filter === 'present') $status_sql = "AND a.status = 'present'";

// Fetch attendance records as notification feed
$records = $conn->query("
    SELECT a.student_id, a.status, a.scan_date, a.scan_time, u.fullname
    FROM attendance a
    JOIN users u ON u.id = a.student_id
    WHERE a.student_id IN ($child_ids)
    $status_sql
    ORDER BY a.scan_date DESC, a.scan_time DESC
    LIMIT 80
");

// Stats
$statsRes = $conn->query("
    SELECT a.status, COUNT(*) as cnt
    FROM attendance a
    WHERE a.student_id IN ($child_ids)
    GROUP BY a.status
");
$counts = ['present' => 0, 'late' => 0, 'absent' => 0];
while ($s = $statsRes->fetch_assoc()) {
    if (isset($counts[$s['status']])) $counts[$s['status']] = (int)$s['cnt'];
}
$total = array_sum($counts);

// Child name for subtitle
$childName = !empty($children) ? implode(', ', array_values($children)) : 'your child';

function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . ' mins ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hrs ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', strtotime($datetime));
}
?>

<style>
/* ── PAGE ─────────────────────────────────────────────────── */
.pn-page { display: flex; flex-direction: column; gap: 22px; }

.pn-header-row {
    display: flex; flex-direction: column; gap: 4px;
}

.pn-title {
    font-size: 20px; font-weight: 700; color: #0f1923;
    display: flex; align-items: center; gap: 8px;
}

.pn-sub { font-size: 13px; color: #64748b; }
.pn-sub strong { color: #0f1923; }

/* ── STAT CARDS ────────────────────────────────────────────── */
.pn-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 14px;
}

.pn-stat {
    background: #fff; border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    position: relative; overflow: hidden;
}

.pn-stat::before {
    content: ''; position: absolute;
    top: 0; left: 0; width: 4px; height: 100%;
    border-radius: 14px 0 0 14px;
}

.pn-stat.slate::before  { background: #64748b; }
.pn-stat.green::before  { background: #16a34a; }
.pn-stat.amber::before  { background: #f59e0b; }
.pn-stat.red::before    { background: #ef4444; }

.pn-stat-label {
    font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.7px;
    color: #94a3b8; margin-bottom: 6px;
}

.pn-stat-val {
    font-size: 30px; font-weight: 800; line-height: 1;
    color: #0f1923;
}

.pn-stat.green .pn-stat-val { color: #16a34a; }
.pn-stat.amber .pn-stat-val { color: #f59e0b; }
.pn-stat.red   .pn-stat-val { color: #ef4444; }

/* ── BANNER ────────────────────────────────────────────────── */
.email-banner {
    background: #f0fdf4; border: 1px solid #bbf7d0;
    border-radius: 12px; padding: 14px 18px;
    display: flex; align-items: flex-start; gap: 12px;
    font-size: 13px; color: #15803d;
}

.email-banner .eb-icon { font-size: 20px; flex-shrink: 0; margin-top: 1px; }
.email-banner strong { color: #14532d; }
.email-banner p { margin: 4px 0 0; color: #16a34a; font-size: 12px; line-height: 1.5; }

/* ── FILTER TABS ───────────────────────────────────────────── */
.pn-filters { display: flex; gap: 8px; flex-wrap: wrap; }

.pn-filters a {
    padding: 7px 16px; border-radius: 99px;
    text-decoration: none; font-size: 13px; font-weight: 500;
    background: #f1f5f9; color: #64748b;
    border: 1px solid transparent;
    transition: all 0.15s;
}

.pn-filters a:hover { background: #e2e8f0; }
.pn-filters a.active { background: #0f1923; color: #fff; border-color: #0f1923; }

/* ── FEED ──────────────────────────────────────────────────── */
.pn-feed {
    background: #fff; border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.feed-header {
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
}

.feed-header-title {
    font-size: 14px; font-weight: 700; color: #0f1923;
}

.feed-count {
    font-size: 12px; color: #94a3b8; font-weight: 500;
}

/* Feed item */
.feed-item {
    display: flex; align-items: center; gap: 16px;
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s;
}

.feed-item:last-child { border-bottom: none; }
.feed-item:hover { background: #f8fafc; }

.feed-item.present { border-left: 4px solid #16a34a; }
.feed-item.late    { border-left: 4px solid #f59e0b; }
.feed-item.absent  { border-left: 4px solid #ef4444; }

/* Avatar */
.feed-avatar {
    width: 42px; height: 42px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; font-weight: 800; flex-shrink: 0;
    color: #fff;
}

.feed-avatar.present { background: #16a34a; }
.feed-avatar.late    { background: #f59e0b; }
.feed-avatar.absent  { background: #ef4444; }

/* Content */
.feed-content { flex: 1; min-width: 0; }

.feed-name {
    font-size: 13px; font-weight: 700;
    color: #0f1923; margin-bottom: 3px;
}

.feed-msg {
    font-size: 13px; color: #374151;
    margin-bottom: 5px; line-height: 1.4;
}

.feed-meta {
    display: flex; align-items: center; gap: 8px;
    flex-wrap: wrap;
}

/* Status pill */
.spill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 9px; border-radius: 99px;
    font-size: 11px; font-weight: 700;
}

.spill.present { background: #dcfce7; color: #16a34a; }
.spill.late    { background: #fef3c7; color: #d97706; }
.spill.absent  { background: #fee2e2; color: #dc2626; }

.feed-time { font-size: 11px; color: #94a3b8; }
.feed-dot  { color: #d1d5db; font-size: 10px; }

/* Email sent badge */
.notif-sent {
    margin-left: auto; flex-shrink: 0;
    font-size: 11px; font-weight: 600;
    color: #16a34a; background: #dcfce7;
    padding: 4px 10px; border-radius: 99px;
    white-space: nowrap;
}

.notif-sent.na {
    color: #94a3b8; background: #f1f5f9;
}

/* Empty state */
.feed-empty {
    padding: 60px 20px; text-align: center;
}

.feed-empty .fe-icon { font-size: 48px; margin-bottom: 14px; }

.feed-empty h3 {
    font-size: 16px; font-weight: 700;
    color: #1e293b; margin-bottom: 8px;
}

.feed-empty p { font-size: 13px; color: #94a3b8; }

/* ── TIPS ──────────────────────────────────────────────────── */
.tips-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 14px;
}

.tip-card {
    background: #fff; border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.tip-card h4 {
    font-size: 13px; font-weight: 700;
    color: #0f1923; margin-bottom: 6px;
    display: flex; align-items: center; gap: 6px;
}

.tip-card p, .tip-card li {
    font-size: 13px; color: #64748b; line-height: 1.6;
}

.tip-card ul { padding-left: 16px; margin: 0; }
</style>

<div class="pn-page">

    <!-- Header -->
    <div class="pn-header-row">
        <div class="pn-title">🔔 Notifications</div>
        <div class="pn-sub">Attendance alerts for <strong><?= htmlspecialchars($childName) ?></strong></div>
    </div>

    <!-- Stats -->
    <div class="pn-stats">
        <div class="pn-stat slate">
            <div class="pn-stat-label">Total Records</div>
            <div class="pn-stat-val"><?= $total ?></div>
        </div>
        <div class="pn-stat green">
            <div class="pn-stat-label">Present</div>
            <div class="pn-stat-val"><?= $counts['present'] ?></div>
        </div>
        <div class="pn-stat amber">
            <div class="pn-stat-label">Late</div>
            <div class="pn-stat-val"><?= $counts['late'] ?></div>
        </div>
        <div class="pn-stat red">
            <div class="pn-stat-label">Absent</div>
            <div class="pn-stat-val"><?= $counts['absent'] ?></div>
        </div>
    </div>

    <!-- Email confirmation banner -->
    <div class="email-banner">
        <div class="eb-icon">📧</div>
        <div>
            <strong>You are receiving email alerts at <?= htmlspecialchars($parent_email) ?></strong>
            <p>
                Emails are sent automatically: ✅ <strong>Present/Late</strong> — instantly when your child scans their QR code &nbsp;|&nbsp;
                ❌ <strong>Absent</strong> — at 9:01 AM if no scan is recorded.
            </p>
        </div>
    </div>

    <!-- Filters -->
    <div class="pn-filters">
        <a href="?page=notifications&filter=all"      class="<?= $filter==='all'     ?'active':'' ?>">All</a>
        <a href="?page=notifications&filter=present"  class="<?= $filter==='present' ?'active':'' ?>">✅ Present</a>
        <a href="?page=notifications&filter=late"     class="<?= $filter==='late'    ?'active':'' ?>">⚠️ Late</a>
        <a href="?page=notifications&filter=absent"   class="<?= $filter==='absent'  ?'active':'' ?>">❌ Absent</a>
    </div>

    <!-- Feed -->
    <div class="pn-feed">
        <div class="feed-header">
            <div class="feed-header-title">📋 Attendance Notification Log</div>
            <div class="feed-count">Latest 80 records</div>
        </div>

        <?php if ($records && $records->num_rows > 0): ?>
            <?php while ($n = $records->fetch_assoc()):
                $s      = $n['status'];
                $icons  = ['present' => '✅', 'late' => '⚠️', 'absent' => '❌'];
                $emojis = ['present' => '✓',  'late' => '!',   'absent' => '✕'];
                $msgs   = [
                    'present' => '<strong>' . htmlspecialchars($n['fullname']) . '</strong> arrived at school and was marked <strong>Present</strong>.',
                    'late'    => '<strong>' . htmlspecialchars($n['fullname']) . '</strong> arrived <strong>late</strong>. A parent email alert was sent.',
                    'absent'  => '<strong>' . htmlspecialchars($n['fullname']) . '</strong> was recorded as <strong>Absent</strong> today. A parent email alert was sent.',
                ];
                $timeStr = $s === 'absent'
                    ? date('F j, Y', strtotime($n['scan_date']))
                    : date('h:i A', strtotime($n['scan_time'])) . ' · ' . date('F j, Y', strtotime($n['scan_date']));
                $emailSent = in_array($s, ['late', 'absent']);
                $initials = strtoupper(substr($n['fullname'], 0, 1));
            ?>
            <div class="feed-item <?= $s ?>">
                <div class="feed-avatar <?= $s ?>"><?= $initials ?></div>
                <div class="feed-content">
                    <div class="feed-name"><?= htmlspecialchars($n['fullname']) ?></div>
                    <div class="feed-msg"><?= $msgs[$s] ?></div>
                    <div class="feed-meta">
                        <span class="spill <?= $s ?>"><?= $icons[$s] ?> <?= ucfirst($s) ?></span>
                        <span class="feed-dot">•</span>
                        <span class="feed-time"><?= $timeStr ?></span>
                    </div>
                </div>
                <?php if ($emailSent): ?>
                    <span class="notif-sent">📧 Email sent</span>
                <?php else: ?>
                    <span class="notif-sent na">No email</span>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="feed-empty">
                <div class="fe-icon">📭</div>
                <h3>No notifications yet</h3>
                <p>Attendance alerts will appear here once your child starts scanning their QR code at school.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tips -->
    <div class="tips-row">
        <div class="tip-card">
            <h4>📌 About This Page</h4>
            <p>This shows a real-time log of every attendance event recorded for your child, along with whether an email alert was sent to you.</p>
        </div>
        <div class="tip-card">
            <h4>📊 Status Guide</h4>
            <ul>
                <li>✅ <strong>Present</strong> — scanned before 8:10 AM</li>
                <li>⚠️ <strong>Late</strong> — scanned after 8:10 AM</li>
                <li>❌ <strong>Absent</strong> — no scan recorded by 9:01 AM</li>
            </ul>
        </div>
        <div class="tip-card">
            <h4>💡 Tip</h4>
            <p>Check this page daily alongside your email inbox to stay fully updated on your child's attendance.</p>
        </div>
    </div>

</div>