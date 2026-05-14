<?php
if (session_status() == PHP_SESSION_NONE) session_start();
include 'includes/db.php';
include 'includes/mailer.php';

date_default_timezone_set('Asia/Manila');

$today = date('Y-m-d');

// ── Date filter for log ────────────────────────────────────────────────
$log_date = !empty($_GET['log_date']) ? $conn->real_escape_string($_GET['log_date']) : '';
$log_date_where = $log_date ? "AND DATE(n.created_at) = '$log_date'" : "";

// ── Stat date: use filter date if set, else today ──────────────────────
$stat_date = $log_date ?: $today;

// ── Stats ──────────────────────────────────────────────────────────────
$counts = ['absent' => 0, 'late' => 0, 'present' => 0];

$lateRow = $conn->query("
    SELECT COUNT(*) as cnt FROM attendance
    WHERE scan_date = '$stat_date' AND status = 'late'
")->fetch_assoc();
$counts['late'] = (int)$lateRow['cnt'];

$presentRow = $conn->query("
    SELECT COUNT(*) as cnt FROM attendance
    WHERE scan_date = '$stat_date' AND status IN ('present','on_time')
")->fetch_assoc();
$counts['present'] = (int)$presentRow['cnt'];

$absentRow = $conn->query("
    SELECT COUNT(*) as cnt FROM users
    WHERE role = 'student'
      AND id NOT IN (
          SELECT DISTINCT student_id FROM attendance
          WHERE scan_date = '$stat_date'
            AND status IN ('present', 'on_time', 'late')
      )
")->fetch_assoc();
$counts['absent'] = (int)$absentRow['cnt'];

$totalRow  = $conn->query("SELECT COUNT(*) as total FROM notifications")->fetch_assoc();
$totalSent = $totalRow['total'] ?? 0;

$todaySentRow = $conn->query("SELECT COUNT(*) as cnt FROM notifications WHERE DATE(created_at) = '$stat_date'")->fetch_assoc();
$todaySent = (int)$todaySentRow['cnt'];

$lastRow = $conn->query("SELECT created_at FROM notifications ORDER BY created_at DESC LIMIT 1")->fetch_assoc();
$lastSentTime = $lastRow ? date('M d, Y h:i A', strtotime($lastRow['created_at'])) : 'No notifications yet';

// ── Notification log (last 100, filtered by date if set) ───────────────
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
    WHERE 1=1 $log_date_where
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

function detectStatus($message) {
    $msg = strtolower(strip_tags($message));
    if (str_contains($msg, 'absent'))  return 'absent';
    if (str_contains($msg, 'late'))    return 'late';
    if (str_contains($msg, 'present')) return 'present';
    return 'unknown';
}

function isHtmlMessage($message) {
    return $message !== strip_tags($message);
}
?>

<style>
.notif-page { display: flex; flex-direction: column; gap: 24px; font-family: 'Sora', sans-serif; }

.section-title {
    font-size: 20px; font-weight: 700;
    color: #0f1923; display: flex; align-items: center; gap: 8px;
    margin-bottom: 4px;
}
.section-sub { font-size: 13px; color: #64748b; margin-bottom: 0; }

.stat-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(155px, 1fr));
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
.stat-card.green::before { background: #22c55e; }
.stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; color: #94a3b8; }
.stat-val   { font-size: 32px; font-weight: 800; line-height: 1; color: #0f1923; }
.stat-card.blue  .stat-val { color: #3b82f6; }
.stat-card.red   .stat-val { color: #ef4444; }
.stat-card.amber .stat-val { color: #f59e0b; }
.stat-card.green .stat-val { color: #22c55e; }
.stat-sub { font-size: 11.5px; color: #94a3b8; margin-top: 2px; }

.auto-status-card {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    border-radius: 16px; padding: 22px 24px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 20px; flex-wrap: wrap;
    box-shadow: 0 4px 20px rgba(15,23,42,0.18);
}
.auto-status-left { display: flex; align-items: center; gap: 16px; }
.auto-pulse-wrap { position: relative; flex-shrink: 0; }
.auto-pulse-dot { width: 14px; height: 14px; background: #22c55e; border-radius: 50%; position: relative; z-index: 1; }
.auto-pulse-ring {
    position: absolute; top: -5px; left: -5px;
    width: 24px; height: 24px; border-radius: 50%;
    border: 2px solid #22c55e;
    animation: pulse-ring 1.8s ease-out infinite; opacity: 0;
}
@keyframes pulse-ring {
    0%   { transform: scale(0.7); opacity: 0.8; }
    100% { transform: scale(1.5); opacity: 0; }
}
.auto-status-text strong { display: block; font-size: 14px; font-weight: 700; color: #f1f5f9; margin-bottom: 3px; }
.auto-status-text span { font-size: 12.5px; color: #94a3b8; }
.auto-status-triggers { display: flex; gap: 10px; flex-wrap: wrap; }
.trigger-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);
    border-radius: 20px; padding: 6px 13px;
    font-size: 12px; font-weight: 600; color: #cbd5e1;
}
.trigger-pill .dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.trigger-pill .dot.green  { background: #22c55e; }
.trigger-pill .dot.amber  { background: #f59e0b; }
.trigger-pill .dot.red    { background: #ef4444; }

.today-chip {
    display: inline-flex; align-items: center; gap: 5px;
    background: #eff6ff; color: #1d4ed8; font-size: 12px; font-weight: 600;
    padding: 4px 11px; border-radius: 20px;
}

.feed-card { background: #fff; border-radius: 14px; padding: 22px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
.feed-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 18px; flex-wrap: wrap; gap: 12px;
}
.feed-title { font-size: 15px; font-weight: 700; color: #0f1923; display: flex; align-items: center; gap: 8px; }
.feed-filters { display: flex; gap: 6px; flex-wrap: wrap; }
.filter-btn {
    padding: 5px 13px; border-radius: 20px; font-size: 12px; font-weight: 600; cursor: pointer;
    border: 1.5px solid #e2e8f0; background: #fff; color: #64748b; transition: all 0.15s;
}
.filter-btn.active, .filter-btn:hover { border-color: #3b82f6; background: #eff6ff; color: #1d4ed8; }
.filter-btn[data-f="absent"].active   { border-color: #ef4444; background: #fee2e2; color: #dc2626; }
.filter-btn[data-f="late"].active     { border-color: #f59e0b; background: #fef3c7; color: #d97706; }
.filter-btn[data-f="present"].active  { border-color: #22c55e; background: #dcfce7; color: #15803d; }

/* Date filter bar */
.date-filter-bar {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    margin-bottom: 16px; padding: 14px 16px;
    background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9;
}
.date-filter-bar label { font-size: 12px; font-weight: 600; color: #64748b; white-space: nowrap; }
.date-filter-bar input[type="date"] {
    padding: 7px 12px; border-radius: 9px; border: 1.5px solid #e2e8f0;
    font-size: 13px; font-family: 'DM Sans', sans-serif; color: #0f1923;
    background: #fff; outline: none; transition: border 0.15s;
}
.date-filter-bar input[type="date"]:focus { border-color: #16a34a; }
.btn-df-view {
    padding: 8px 18px; background: #0f1923; color: #fff;
    border: none; border-radius: 9px; font-size: 13px; font-weight: 600;
    font-family: 'DM Sans', sans-serif; cursor: pointer; transition: background 0.15s;
}
.btn-df-view:hover { background: #1e2d3d; }
.btn-df-clear {
    padding: 8px 14px; background: #f1f5f9; color: #64748b;
    border-radius: 9px; font-size: 13px; font-weight: 600;
    text-decoration: none; transition: background 0.15s;
}
.btn-df-clear:hover { background: #e2e8f0; }
.df-active-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: #dbeafe; color: #1d4ed8; font-size: 12px; font-weight: 600;
    padding: 4px 12px; border-radius: 99px;
}

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

.msg-cell { font-size: 12.5px; color: #475569; line-height: 1.4; }
.msg-snippet { max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; color: #64748b; font-size: 12px; }
.btn-view-email {
    margin-top: 5px; display: inline-flex; align-items: center; gap: 4px;
    font-size: 11.5px; font-weight: 600; color: #3b82f6;
    background: #eff6ff; border: none; border-radius: 6px;
    padding: 3px 9px; cursor: pointer; transition: background 0.15s;
}
.btn-view-email:hover { background: #dbeafe; }

/* Modal */
.email-modal-overlay {
    display: none; position: fixed; inset: 0;
    background: rgba(15,23,42,0.55); z-index: 9999;
    align-items: center; justify-content: center;
    padding: 20px;
}
.email-modal-overlay.open { display: flex; }
.email-modal {
    background: #fff; border-radius: 16px; width: 100%; max-width: 580px;
    max-height: 90vh; display: flex; flex-direction: column;
    box-shadow: 0 20px 60px rgba(0,0,0,0.25); overflow: hidden;
}
.email-modal-header {
    padding: 18px 22px; border-bottom: 1px solid #e2e8f0;
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    background: #f8fafc;
}
.email-modal-header-info strong { display: block; font-size: 14px; color: #0f1923; margin-bottom: 3px; }
.email-modal-header-info span  { font-size: 12px; color: #64748b; font-family: monospace; }
.btn-modal-close {
    background: #e2e8f0; border: none; border-radius: 8px;
    width: 32px; height: 32px; cursor: pointer; font-size: 16px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    color: #64748b; transition: background 0.15s;
}
.btn-modal-close:hover { background: #cbd5e1; }
.email-modal-body { flex: 1; overflow-y: auto; padding: 20px; background: #f1f5f9; }
.email-iframe {
    width: 100%; border: none; border-radius: 10px;
    background: #fff; min-height: 340px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.08);
}

.empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; font-size: 14px; }
.empty-state .empty-icon { font-size: 36px; margin-bottom: 8px; }
</style>

<div class="notif-page">

    <!-- Header -->
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:10px;">
        <div>
            <div class="section-title">🔔 Notifications</div>
            <div class="section-sub">Automated email alerts sent to parents for attendance events.</div>
        </div>
        <div class="today-chip">📅 <?= date('F j, Y') ?></div>
    </div>

    <!-- Stats -->
    <div class="stat-row">
        <div class="stat-card blue">
            <div class="stat-label">Total Emails Sent</div>
            <div class="stat-val"><?= number_format($totalSent) ?></div>
            <div class="stat-sub">All time</div>
        </div>
        <div class="stat-card green">
            <div class="stat-label"><?= $log_date ? 'Sent on Date' : 'Sent Today' ?></div>
            <div class="stat-val"><?= number_format($todaySent) ?></div>
            <div class="stat-sub"><?= date('M d, Y', strtotime($stat_date)) ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-label"><?= $log_date ? 'Absent on Date' : 'Absent Today' ?></div>
            <div class="stat-val"><?= $counts['absent'] ?></div>
            <div class="stat-sub">No scan recorded</div>
        </div>
        <div class="stat-card amber">
            <div class="stat-label"><?= $log_date ? 'Late on Date' : 'Late Today' ?></div>
            <div class="stat-val"><?= $counts['late'] ?></div>
            <div class="stat-sub">Arrived after cutoff</div>
        </div>
    </div>

    <!-- Auto Status -->
    <div class="auto-status-card">
        <div class="auto-status-left">
            <div class="auto-pulse-wrap">
                <div class="auto-pulse-dot"></div>
                <div class="auto-pulse-ring"></div>
            </div>
            <div class="auto-status-text">
                <strong>Automatic Email Notifications Active</strong>
                <span>Last notification sent: <?= htmlspecialchars($lastSentTime) ?></span>
            </div>
        </div>
        <div class="auto-status-triggers">
            <div class="trigger-pill"><span class="dot green"></span> On QR Scan → Present</div>
            <div class="trigger-pill"><span class="dot amber"></span> Late Scan → Late</div>
            <div class="trigger-pill"><span class="dot red"></span> No Scan → Absent</div>
        </div>
    </div>

    <!-- Notification Log -->
    <div class="feed-card">
        <div class="feed-header">
            <div class="feed-title">
                📋 Email Notification Log
                <span style="font-size:12px;font-weight:500;color:#94a3b8;">(latest 100)</span>
            </div>
            <div class="feed-filters">
                <button class="filter-btn active" data-f="all" onclick="filterLog(this)">All</button>
                <button class="filter-btn" data-f="absent" onclick="filterLog(this)">Absent</button>
                <button class="filter-btn" data-f="late" onclick="filterLog(this)">Late</button>
                <button class="filter-btn" data-f="present" onclick="filterLog(this)">Present</button>
            </div>
        </div>

        <!-- Date Filter Bar -->
        <form method="GET" class="date-filter-bar">
            <input type="hidden" name="page" value="notifications">
            <label>📅 Filter by Date:</label>
            <input type="date" name="log_date"
                value="<?= htmlspecialchars($log_date) ?>"
                max="<?= $today ?>">
            <button type="submit" class="btn-df-view">View</button>
            <?php if ($log_date): ?>
                <a href="?page=notifications" class="btn-df-clear">✕ Clear</a>
                <span class="df-active-badge">
                    📅 <?= date('F d, Y', strtotime($log_date)) ?>
                </span>
            <?php endif; ?>
        </form>

        <div style="overflow-x:auto;">
        <table class="data-table" id="notif-log-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Status</th>
                    <th>Parent Email</th>
                    <th>Email Message</th>
                    <th>Date &amp; Time Sent</th>
                    <th>Email Sent</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($log && $log->num_rows > 0): ?>
                <?php while ($n = $log->fetch_assoc()):
                    $initials  = getInitials($n['student_name']);
                    [$bgColor, $textColor] = avatarColor($n['student_name']);
                    $status     = detectStatus($n['message']);
                    $statusIcon = $status === 'absent' ? '❌' : ($status === 'late' ? '⚠️' : '✅');
                    $isHtml     = isHtmlMessage($n['message']);
                    $snippet    = htmlspecialchars(mb_substr(strip_tags($n['message']), 0, 75)) . '…';
                    $encodedMsg = base64_encode($n['message']);
                    $modalTitle = 'Email to parent of ' . htmlspecialchars($n['student_name']);
                    $modalMeta  = htmlspecialchars($n['parent_email'] ?? '') . ' · ' . date('M d, Y h:i A', strtotime($n['created_at']));
                ?>
                <tr data-status="<?= $status ?>">
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
                    <td class="msg-cell">
                        <span class="msg-snippet"><?= $snippet ?></span>
                        <?php if ($isHtml): ?>
                            <button class="btn-view-email"
                                onclick="openEmailModal(
                                    <?= json_encode($modalTitle) ?>,
                                    <?= json_encode($modalMeta) ?>,
                                    <?= json_encode($encodedMsg) ?>
                                )">
                                👁 View Email
                            </button>
                        <?php endif; ?>
                    </td>
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
                            <?= $log_date
                                ? 'No notifications found for <strong>' . date('F d, Y', strtotime($log_date)) . '</strong>.'
                                : 'No notifications sent yet. They will appear here automatically once students are marked absent or late.'
                            ?>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>

<!-- Email Preview Modal -->
<div class="email-modal-overlay" id="emailModal" onclick="closeEmailModal(event)">
    <div class="email-modal">
        <div class="email-modal-header">
            <div class="email-modal-header-info">
                <strong id="modalTitle">Email Preview</strong>
                <span id="modalMeta"></span>
            </div>
            <button class="btn-modal-close" onclick="closeModal()">✕</button>
        </div>
        <div class="email-modal-body">
            <iframe class="email-iframe" id="emailIframe" title="Email Preview"></iframe>
        </div>
    </div>
</div>

<script>
function filterLog(btn) {
    document.querySelectorAll('.feed-filters .filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const f = btn.dataset.f;
    document.querySelectorAll('#notif-log-table tbody tr[data-status]').forEach(row => {
        row.style.display = (f === 'all' || row.dataset.status === f) ? '' : 'none';
    });
}

function openEmailModal(title, meta, encodedHtml) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalMeta').textContent  = meta;

    const html   = atob(encodedHtml);
    const iframe = document.getElementById('emailIframe');
    iframe.srcdoc = html;

    iframe.onload = function() {
        try {
            const h = iframe.contentDocument.body.scrollHeight;
            iframe.style.height = Math.max(h + 24, 340) + 'px';
        } catch(e) {
            iframe.style.height = '420px';
        }
    };

    document.getElementById('emailModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('emailModal').classList.remove('open');
    document.body.style.overflow = '';
    document.getElementById('emailIframe').srcdoc = '';
}

function closeEmailModal(e) {
    if (e.target === document.getElementById('emailModal')) closeModal();
}
</script>