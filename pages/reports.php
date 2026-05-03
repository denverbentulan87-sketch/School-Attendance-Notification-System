<?php
include 'includes/db.php';

$date        = $_GET['date']          ?? date('Y-m-d');
$statusFilter = $_GET['status_filter'] ?? 'all';

$total = $conn->query("
    SELECT COUNT(*) AS total FROM users WHERE role='student'
")->fetch_assoc()['total'];

$present = $conn->query("
    SELECT COUNT(DISTINCT student_id) AS total
    FROM attendance
    WHERE status IN ('present', 'on_time', 'late')
      AND scan_date = '$date'
")->fetch_assoc()['total'];

$late = $conn->query("
    SELECT COUNT(DISTINCT student_id) AS total
    FROM attendance
    WHERE status = 'late'
      AND scan_date = '$date'
")->fetch_assoc()['total'];

$absent = $conn->query("
    SELECT COUNT(*) AS total FROM users
    WHERE role = 'student'
      AND id NOT IN (
          SELECT DISTINCT student_id
          FROM attendance
          WHERE scan_date = '$date'
            AND status IN ('present', 'on_time', 'late')
      )
")->fetch_assoc()['total'];

$percentage     = $total > 0 ? round(($present / $total) * 100) : 0;
$formatted_date = date('F d, Y', strtotime($date));

// Build status WHERE clause for table
$statusWhere = '';
if ($statusFilter === 'present') {
    $statusWhere = "AND attendance.status IN ('present', 'on_time')";
} elseif ($statusFilter === 'late') {
    $statusWhere = "AND attendance.status = 'late'";
} elseif ($statusFilter === 'absent') {
    // Only show students who have no present/late/on_time record
    $statusWhere = '__absent__'; // handled separately below
}

if ($statusFilter === 'absent') {
    $records = $conn->query("
        SELECT users.fullname,
               'absent' AS status,
               '$date'  AS scan_date,
               NULL     AS scan_time
        FROM users
        WHERE users.role = 'student'
          AND users.id NOT IN (
              SELECT DISTINCT student_id
              FROM attendance
              WHERE scan_date = '$date'
                AND status IN ('present', 'on_time', 'late')
          )
        ORDER BY users.fullname ASC
    ");
} elseif ($statusFilter === 'all') {
    $records = $conn->query("
        SELECT users.fullname,
               COALESCE(attendance.status, 'absent') AS status,
               COALESCE(attendance.scan_date, '$date') AS scan_date,
               attendance.scan_time
        FROM users
        LEFT JOIN attendance
               ON attendance.student_id = users.id
              AND attendance.scan_date  = '$date'
        WHERE users.role = 'student'
        ORDER BY
            FIELD(COALESCE(attendance.status,'absent'), 'present','on_time','late','absent'),
            attendance.scan_time DESC
    ");
} else {
    $records = $conn->query("
        SELECT users.fullname,
               attendance.status,
               attendance.scan_date,
               attendance.scan_time
        FROM users
        INNER JOIN attendance
               ON attendance.student_id = users.id
              AND attendance.scan_date  = '$date'
              $statusWhere
        WHERE users.role = 'student'
        ORDER BY attendance.scan_time DESC
    ");
}
?>

<style>
.section-title {
    font-size:20px; font-weight:600; color:#1e293b;
    margin-bottom:20px; display:flex; align-items:center; gap:8px;
}
.filter-bar {
    display:flex; align-items:center; gap:10px;
    margin-bottom:24px; flex-wrap:wrap;
}
.filter-bar input[type="date"] {
    padding:9px 13px; border:1px solid #e5e7eb;
    border-radius:8px; font-size:13px; color:#374151;
    background:#fff; outline:none;
}
.filter-bar input[type="date"]:focus { border-color:#3b82f6; }
.filter-btn {
    background:#3b82f6; color:white; border:none;
    padding:9px 20px; border-radius:8px; font-size:13px;
    font-weight:500; cursor:pointer; transition:background 0.2s;
}
.filter-btn:hover { background:#2563eb; }
.filter-label { font-size:13px; color:#64748b; margin-left:4px; }

.stat-cards {
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(170px,1fr));
    gap:16px; margin-bottom:24px;
}
.stat-card {
    border-radius:14px; padding:20px 22px;
    color:white; position:relative; overflow:hidden;
    cursor:pointer; transition:transform 0.18s, box-shadow 0.18s;
}
.stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,0.13); }
.stat-card.active-filter { outline: 3px solid #fff; box-shadow:0 0 0 5px rgba(0,0,0,0.18); }
.stat-card::after {
    content:''; position:absolute; right:-10px; bottom:-10px;
    width:70px; height:70px; border-radius:50%;
    background:rgba(255,255,255,0.12);
}
.stat-card.indigo { background:linear-gradient(135deg,#6366f1,#4f46e5); }
.stat-card.green  { background:linear-gradient(135deg,#22c55e,#16a34a); }
.stat-card.red    { background:linear-gradient(135deg,#ef4444,#dc2626); }
.stat-card.amber  { background:linear-gradient(135deg,#f59e0b,#d97706); }
.stat-card.yellow { background:linear-gradient(135deg,#eab308,#ca8a04); }
.stat-card h4 {
    font-size:12px; font-weight:500; opacity:0.85;
    text-transform:uppercase; letter-spacing:0.5px; margin-bottom:10px;
}
.stat-card .stat-value { font-size:34px; font-weight:700; line-height:1; }

.progress-section {
    background:#fff; border-radius:14px;
    padding:20px 22px; margin-bottom:24px;
    box-shadow:0 2px 12px rgba(0,0,0,0.06);
}
.ps-label {
    display:flex; justify-content:space-between;
    font-size:13px; font-weight:500; color:#374151; margin-bottom:10px;
}
.progress-wrap { background:#e5e7eb; border-radius:99px; overflow:hidden; height:10px; }
.progress-fill {
    background:linear-gradient(90deg,#22c55e,#16a34a);
    height:100%; border-radius:99px; transition:width 0.6s ease;
}

.table-card {
    background:#fff; border-radius:14px;
    padding:22px; box-shadow:0 2px 12px rgba(0,0,0,0.06);
}
.card-header {
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:10px;
    margin-bottom:16px;
}
.card-header-left {
    font-size:15px; font-weight:600; color:#1e293b;
    display:flex; align-items:center; gap:8px;
}
.card-header-right { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

/* Status filter tabs */
.status-tabs { display:flex; gap:6px; flex-wrap:wrap; }

.stab {
    padding:6px 14px; border-radius:99px; font-size:12px;
    font-weight:600; border:2px solid #e5e7eb; cursor:pointer;
    background:#f1f5f9; color:#64748b; text-decoration:none;
    transition:all 0.15s; display:inline-block;
}
.stab:hover { opacity:0.8; }
.stab.s-all     { background:#6366f1; color:#fff; border-color:#6366f1; }
.stab.s-present { background:#dcfce7; color:#16a34a; border-color:#16a34a; }
.stab.s-late    { background:#fef3c7; color:#d97706; border-color:#d97706; }
.stab.s-absent  { background:#fee2e2; color:#dc2626; border-color:#dc2626; }

.record-count { font-size:12px; color:#94a3b8; font-weight:500; }

.data-table { width:100%; border-collapse:collapse; }
.data-table th {
    background:#f8fafc; padding:11px 14px;
    font-size:12px; font-weight:600;
    text-transform:uppercase; letter-spacing:0.5px;
    color:#64748b; text-align:left;
}
.data-table td {
    padding:13px 14px; font-size:14px;
    color:#374151; border-bottom:1px solid #f1f5f9;
}
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:#f8fafc; }

.badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:4px 12px; border-radius:99px;
    font-size:12px; font-weight:600;
}
.badge.present { background:#dcfce7; color:#16a34a; }
.badge.on_time { background:#dcfce7; color:#16a34a; }
.badge.late    { background:#fef3c7; color:#92400e; }
.badge.absent  { background:#fee2e2; color:#dc2626; }
</style>

<div class="section-title">📊 Reports Dashboard</div>

<form method="GET" class="filter-bar">
    <input type="hidden" name="page" value="reports">
    <input type="hidden" name="status_filter" value="<?= htmlspecialchars($statusFilter) ?>">
    <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
    <button type="submit" class="filter-btn">Filter</button>
    <span class="filter-label">Showing results for <strong><?= $formatted_date ?></strong></span>
</form>

<!-- Stat cards — clicking filters the table -->
<div class="stat-cards">
    <div class="stat-card indigo <?= $statusFilter==='all'?'active-filter':'' ?>"
         onclick="applyStatusFilter('all')">
        <h4>Total Students</h4>
        <div class="stat-value"><?= $total ?></div>
    </div>
    <div class="stat-card green <?= $statusFilter==='present'?'active-filter':'' ?>"
         onclick="applyStatusFilter('present')">
        <h4>Present</h4>
        <div class="stat-value"><?= $present ?></div>
    </div>
    <div class="stat-card yellow <?= $statusFilter==='late'?'active-filter':'' ?>"
         onclick="applyStatusFilter('late')">
        <h4>Late</h4>
        <div class="stat-value"><?= $late ?></div>
    </div>
    <div class="stat-card red <?= $statusFilter==='absent'?'active-filter':'' ?>"
         onclick="applyStatusFilter('absent')">
        <h4>Absent</h4>
        <div class="stat-value"><?= $absent ?></div>
    </div>
    <div class="stat-card amber">
        <h4>Attendance %</h4>
        <div class="stat-value"><?= $percentage ?>%</div>
    </div>
</div>

<div class="progress-section">
    <div class="ps-label">
        <span>📈 Attendance Rate</span>
        <span><?= $present ?> / <?= $total ?> students present</span>
    </div>
    <div class="progress-wrap">
        <div class="progress-fill" style="width:<?= $percentage ?>%;"></div>
    </div>
</div>

<div class="table-card">
    <div class="card-header">
        <div class="card-header-left">
            📋 Attendance Records — <?= $formatted_date ?>
            <?php if($statusFilter !== 'all'): ?>
                <span class="badge <?= $statusFilter === 'present' ? 'present' : ($statusFilter === 'late' ? 'late' : 'absent') ?>">
                    <?= $statusFilter === 'present' ? '✔ Present' : ($statusFilter === 'late' ? '⏰ Late' : '✖ Absent') ?>
                </span>
            <?php endif; ?>
        </div>
        <div class="card-header-right">
            <span class="record-count"><?= $records ? $records->num_rows : 0 ?> record(s)</span>
            <div class="status-tabs">
                <a class="stab <?= $statusFilter==='all'    ?'s-all':''     ?>"
                   href="?page=reports&date=<?= $date ?>&status_filter=all">All</a>
                <a class="stab <?= $statusFilter==='present'?'s-present':'' ?>"
                   href="?page=reports&date=<?= $date ?>&status_filter=present">✅ Present</a>
                <a class="stab <?= $statusFilter==='late'   ?'s-late':''    ?>"
                   href="?page=reports&date=<?= $date ?>&status_filter=late">⏰ Late</a>
                <a class="stab <?= $statusFilter==='absent' ?'s-absent':''  ?>"
                   href="?page=reports&date=<?= $date ?>&status_filter=absent">❌ Absent</a>
            </div>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Status</th>
                <th>Date</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($records && $records->num_rows > 0):
            $row = 1;
            while ($r = $records->fetch_assoc()):
                $s = $r['status'];
        ?>
            <tr>
                <td style="color:#94a3b8;font-size:13px;"><?= $row++ ?></td>
                <td><strong><?= htmlspecialchars($r['fullname']) ?></strong></td>
                <td>
                    <?php if ($s === 'present' || $s === 'on_time'): ?>
                        <span class="badge present">✔ Present</span>
                    <?php elseif ($s === 'late'): ?>
                        <span class="badge late">⏰ Late</span>
                    <?php else: ?>
                        <span class="badge absent">✖ Absent</span>
                    <?php endif; ?>
                </td>
                <td style="color:#64748b;font-size:13px;">
                    <?= date('M d, Y', strtotime($r['scan_date'])) ?>
                </td>
                <td style="color:#64748b;font-size:13px;">
                    <?= ($s === 'absent' || empty($r['scan_time']) || $r['scan_time'] === '00:00:00')
                        ? '—'
                        : date('g:i A', strtotime($r['scan_time'])); ?>
                </td>
            </tr>
        <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align:center;color:#9ca3af;padding:28px;">
                    No <?= $statusFilter !== 'all' ? $statusFilter : '' ?> records found for <?= $formatted_date ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function applyStatusFilter(status) {
    const url = new URL(window.location.href);
    url.searchParams.set('status_filter', status);
    url.searchParams.set('date', '<?= $date ?>');
    url.searchParams.set('page', 'reports');
    window.location.href = url.toString();
}
</script>