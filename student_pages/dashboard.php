<?php
include 'includes/db.php';

$user_id = $_SESSION['user_id'];

// ── Date filter ──────────────────────────────────────────────
$filter_date  = isset($_GET['filter_date']) && $_GET['filter_date'] !== '' 
                ? $_GET['filter_date'] : null;

// Safe-escape the date for SQL
$filter_date_sql = $filter_date ? $conn->real_escape_string($filter_date) : null;

// ── Stats (filtered by date if set, otherwise all-time) ──────
$date_where = $filter_date_sql ? "AND scan_date = '$filter_date_sql'" : "";

$present = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE student_id='$user_id' AND status IN ('present','late') $date_where
")->fetch_assoc()['total'];

$late = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE student_id='$user_id' AND status='late' $date_where
")->fetch_assoc()['total'];

$absent = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE student_id='$user_id' AND status='absent' $date_where
")->fetch_assoc()['total'];

$total = $present + $absent;
$rate  = $total > 0 ? round(($present / $total) * 100) : 0;

// ── Recent records (filtered by date if set) ─────────────────
if ($filter_date_sql) {
    $recent = $conn->query("
        SELECT scan_date, scan_time, status 
        FROM attendance 
        WHERE student_id='$user_id' AND scan_date = '$filter_date_sql'
        ORDER BY scan_time ASC
    ");
} else {
    $recent = $conn->query("
        SELECT scan_date, scan_time, status 
        FROM attendance 
        WHERE student_id='$user_id'
        ORDER BY scan_date DESC, scan_time DESC
        LIMIT 5
    ");
}

// ── Monthly chart data ────────────────────────────────────────
$monthly = $conn->query("
    SELECT 
        DATE_FORMAT(scan_date, '%b') as month,
        MONTH(scan_date) as month_num,
        SUM(status IN ('present','late')) as present,
        SUM(status='absent') as absent
    FROM attendance
    WHERE student_id='$user_id'
    GROUP BY MONTH(scan_date), DATE_FORMAT(scan_date, '%b')
    ORDER BY month_num
");

$months = []; $presentData = []; $absentData = [];
while ($m = $monthly->fetch_assoc()) {
    $months[]      = $m['month'];
    $presentData[] = (int)$m['present'];
    $absentData[]  = (int)$m['absent'];
}

// ── Weekly chart data ─────────────────────────────────────────
$weekly = $conn->query("
    SELECT 
        DATE_FORMAT(scan_date, '%a') as day,
        DAYOFWEEK(scan_date) as dow,
        SUM(status IN ('present','late')) as present
    FROM attendance
    WHERE student_id='$user_id'
    GROUP BY DAYOFWEEK(scan_date), DATE_FORMAT(scan_date, '%a')
    ORDER BY dow
");

$days = []; $weekData = [];
while ($w = $weekly->fetch_assoc()) {
    $days[]     = $w['day'];
    $weekData[] = (int)$w['present'];
}

// ── Available dates for the date picker dropdown ──────────────
$available_dates = $conn->query("
    SELECT DISTINCT scan_date
    FROM attendance
    WHERE student_id='$user_id'
    ORDER BY scan_date DESC
");
?>

<style>
/* ── Base ── */
.stat-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    position: relative; overflow: hidden;
}

.stat-card::before {
    content: ''; position: absolute;
    top: 0; left: 0; width: 4px; height: 100%;
    border-radius: 14px 0 0 14px;
}

.stat-card.green::before { background: #16a34a; }
.stat-card.amber::before { background: #f59e0b; }
.stat-card.red::before   { background: #ef4444; }
.stat-card.blue::before  { background: #2563eb; }

.stat-card h4 {
    font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.7px;
    color: #94a3b8; margin-bottom: 10px;
}

.stat-card .stat-value {
    font-size: 32px; font-weight: 800; line-height: 1; margin-bottom: 4px;
}

.stat-card.green .stat-value { color: #16a34a; }
.stat-card.amber .stat-value { color: #f59e0b; }
.stat-card.red   .stat-value { color: #dc2626; }
.stat-card.blue  .stat-value { color: #2563eb; }

.stat-card .stat-sub {
    font-size: 12px; color: #94a3b8; margin-top: 4px;
}

.progress {
    background: #e5e7eb; height: 6px;
    border-radius: 99px; margin-top: 12px; overflow: hidden;
}

.progress-bar {
    background: #2563eb; height: 100%;
    border-radius: 99px; transition: width 0.6s ease;
}

.insight-box {
    background: #fff; padding: 14px 18px;
    border-radius: 10px; margin-bottom: 24px;
    font-size: 14px; color: #374151;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    display: flex; align-items: center; gap: 10px;
}

.insight-box.good   { border-left: 4px solid #16a34a; }
.insight-box.ok     { border-left: 4px solid #2563eb; }
.insight-box.warn   { border-left: 4px solid #f59e0b; }
.insight-box.danger { border-left: 4px solid #ef4444; }

.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px; margin-bottom: 24px;
}

.chart-card {
    background: #fff; border-radius: 14px;
    padding: 22px; box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.chart-card h3 {
    font-size: 15px; font-weight: 600;
    color: #1e293b; margin-bottom: 16px;
}

/* ── Date Filter Bar ── */
.filter-bar {
    background: #fff;
    border-radius: 14px;
    padding: 18px 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.filter-bar label {
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    white-space: nowrap;
}

.filter-bar input[type="date"] {
    padding: 8px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    color: #1e293b;
    background: #f8fafc;
    cursor: pointer;
    transition: border-color 0.2s;
    outline: none;
}

.filter-bar input[type="date"]:focus {
    border-color: #2563eb;
    background: #fff;
}

.filter-bar select {
    padding: 8px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    color: #1e293b;
    background: #f8fafc;
    cursor: pointer;
    outline: none;
    transition: border-color 0.2s;
}

.filter-bar select:focus {
    border-color: #2563eb;
    background: #fff;
}

.btn-filter {
    padding: 8px 20px;
    background: #2563eb;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-filter:hover { background: #1d4ed8; }

.btn-clear {
    padding: 8px 16px;
    background: #f1f5f9;
    color: #64748b;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-clear:hover { background: #e2e8f0; color: #374151; }

.filter-active-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 12px;
    background: #dbeafe;
    color: #1d4ed8;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 700;
}

/* ── Table Card ── */
.table-card {
    background: #fff; border-radius: 14px;
    padding: 22px; box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.table-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 16px;
}

.table-card h3 {
    font-size: 15px; font-weight: 600;
    color: #1e293b; margin: 0;
}

.data-table { width: 100%; border-collapse: collapse; }

.data-table th {
    background: #f8fafc; padding: 11px 14px;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.5px;
    color: #64748b; text-align: left;
}

.data-table td {
    padding: 12px 14px; font-size: 14px;
    color: #374151; border-bottom: 1px solid #f1f5f9;
}

.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #f8fafc; }

.badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 11px; border-radius: 99px;
    font-size: 12px; font-weight: 700;
}

.badge.present { background: #dcfce7; color: #16a34a; }
.badge.late    { background: #fef3c7; color: #d97706; }
.badge.absent  { background: #fee2e2; color: #dc2626; }

.section-title {
    font-size: 20px; font-weight: 700;
    color: #1e293b; margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px;
}

.no-records-msg {
    text-align: center; color: #9ca3af;
    padding: 32px 24px; font-size: 14px;
}

.no-records-msg .no-records-icon {
    font-size: 32px; display: block; margin-bottom: 8px;
}
</style>

<div class="section-title">📊 Student Dashboard</div>

<!-- DATE FILTER BAR -->
<form method="GET" action="" style="margin-bottom:0;">
    <input type="hidden" name="page" value="dashboard">
    <div class="filter-bar">
        <label for="filter_date">🔍 Filter by Date:</label>
        <input 
            type="date" 
            id="filter_date" 
            name="filter_date" 
            value="<?= htmlspecialchars($filter_date ?? '') ?>"
            max="<?= date('Y-m-d') ?>"
        >
        <select onchange="document.getElementById('filter_date').value = this.value;" title="Quick pick a date">
            <option value="">— Quick pick —</option>
            <?php 
            $available_dates->data_seek(0);
            while ($d = $available_dates->fetch_assoc()): 
                $val = $d['scan_date'];
                $label = date('D, M d, Y', strtotime($val));
                $selected = ($filter_date === $val) ? 'selected' : '';
            ?>
                <option value="<?= htmlspecialchars($val) ?>" <?= $selected ?>>
                    <?= htmlspecialchars($label) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="btn-filter">🔎 View</button>
        <?php if ($filter_date): ?>
            <a href="?page=dashboard" class="btn-clear">✕ Clear</a>
            <span class="filter-active-badge">
                📅 Showing: <?= date('D, M d, Y', strtotime($filter_date)) ?>
            </span>
        <?php endif; ?>
    </div>
</form>

<!-- STAT CARDS -->
<div class="stat-cards">
    <div class="stat-card green">
        <h4><?= $filter_date ? 'Present on This Day' : 'Days Present' ?></h4>
        <div class="stat-value"><?= $present ?></div>
        <div class="stat-sub">includes <?= $late ?> late arrival<?= $late !== 1 ? 's' : '' ?></div>
    </div>

    <div class="stat-card amber">
        <h4><?= $filter_date ? 'Late on This Day' : 'Days Late' ?></h4>
        <div class="stat-value"><?= $late ?></div>
    </div>

    <div class="stat-card red">
        <h4><?= $filter_date ? 'Absent on This Day' : 'Days Absent' ?></h4>
        <div class="stat-value"><?= $absent ?></div>
    </div>

    <div class="stat-card blue">
        <h4><?= $filter_date ? 'Rate on This Day' : 'Attendance Rate' ?></h4>
        <div class="stat-value"><?= $rate ?>%</div>
        <div class="progress">
            <div class="progress-bar" style="width:<?= $rate ?>%"></div>
        </div>
    </div>
</div>

<!-- INSIGHT -->
<?php if ($total === 0): ?>
<div class="insight-box ok">📭 <?= $filter_date ? 'No attendance record found for <strong>' . date('F d, Y', strtotime($filter_date)) . '</strong>.' : 'No attendance records yet. Records will appear once you start scanning your QR code.' ?></div>
<?php elseif ($rate >= 90): ?>
<div class="insight-box good">🌟 Excellent! <?= $filter_date ? date('M d', strtotime($filter_date)) . ' — ' . $rate . '% attendance rate' : 'You have a ' . $rate . '% attendance rate' ?> — keep it up!</div>
<?php elseif ($rate >= 75): ?>
<div class="insight-box ok">👍 Good — <?= $rate ?>% attendance<?= $filter_date ? ' on ' . date('M d, Y', strtotime($filter_date)) : '' ?>. Try to be more consistent to stay on track.</div>
<?php elseif ($rate >= 50): ?>
<div class="insight-box warn">⚠️ Attendance is at <?= $rate ?>%<?= $filter_date ? ' on ' . date('M d, Y', strtotime($filter_date)) : '' ?>. Improvement is needed to avoid academic issues.</div>
<?php else: ?>
<div class="insight-box danger">🚨 Critical: Only <?= $rate ?>% attendance<?= $filter_date ? ' on ' . date('M d, Y', strtotime($filter_date)) : '' ?>. Please contact your teacher or adviser immediately.</div>
<?php endif; ?>

<!-- CHARTS -->
<div class="charts-grid">
    <div class="chart-card">
        <h3>📈 Monthly Attendance</h3>
        <canvas id="monthlyChart"></canvas>
    </div>
    <div class="chart-card">
        <h3>📅 Weekly Attendance</h3>
        <canvas id="weeklyChart"></canvas>
    </div>
</div>

<!-- ATTENDANCE TABLE -->
<div class="table-card">
    <div class="table-card-header">
        <h3>
            <?php if ($filter_date): ?>
                📋 Attendance on <?= date('F d, Y', strtotime($filter_date)) ?>
            <?php else: ?>
                📋 Recent Attendance <span style="font-size:12px;color:#94a3b8;font-weight:400;">(last 5 records)</span>
            <?php endif; ?>
        </h3>
        <?php if ($filter_date && $recent->num_rows > 0): ?>
            <span style="font-size:13px;color:#64748b;"><?= $recent->num_rows ?> record<?= $recent->num_rows !== 1 ? 's' : '' ?> found</span>
        <?php endif; ?>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Time Scanned</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($recent->num_rows > 0): ?>
            <?php while ($r = $recent->fetch_assoc()): ?>
            <tr>
                <td><?= date('M d, Y', strtotime($r['scan_date'])) ?></td>
                <td><?= (!empty($r['scan_time']) && $r['scan_time'] !== '00:00:00') ? date('h:i A', strtotime($r['scan_time'])) : '—' ?></td>
                <td>
                    <span class="badge <?= $r['status'] ?>">
                        <?= $r['status'] === 'present' ? '✅' : ($r['status'] === 'late' ? '⚠️' : '❌') ?>
                        <?= ucfirst($r['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3">
                    <div class="no-records-msg">
                        <?php if ($filter_date): ?>
                            <span class="no-records-icon">📭</span>
                            No attendance record found for <strong><?= date('F d, Y', strtotime($filter_date)) ?></strong>.
                        <?php else: ?>
                            <span class="no-records-icon">📭</span>
                            No records yet.
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartDefaults = {
    responsive: true,
    plugins: { legend: { position: 'top' } },
    scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } }
    }
};

new Chart(document.getElementById("monthlyChart"), {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            { label: 'Present/Late', data: <?= json_encode($presentData) ?>, backgroundColor: '#86efac', borderRadius: 6 },
            { label: 'Absent',       data: <?= json_encode($absentData) ?>,  backgroundColor: '#fca5a5', borderRadius: 6 }
        ]
    },
    options: chartDefaults
});

new Chart(document.getElementById("weeklyChart"), {
    type: 'line',
    data: {
        labels: <?= json_encode($days) ?>,
        datasets: [{
            label: 'Days Present/Late',
            data: <?= json_encode($weekData) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.08)',
            borderWidth: 2,
            pointBackgroundColor: '#3b82f6',
            tension: 0.4,
            fill: true
        }]
    },
    options: chartDefaults
});
</script>