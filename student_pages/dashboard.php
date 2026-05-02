<?php
include 'includes/db.php';

$user_id = $_SESSION['user_id'];

// Use scan_date (not date_added) — same column the notifications page uses
// Count 'late' as present for the attendance rate (student was physically there)
$present = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE student_id='$user_id' AND status IN ('present','late')
")->fetch_assoc()['total'];

$late = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE student_id='$user_id' AND status='late'
")->fetch_assoc()['total'];

$absent = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE student_id='$user_id' AND status='absent'
")->fetch_assoc()['total'];

$total = $present + $absent; // late is already inside $present
$rate  = $total > 0 ? round(($present / $total) * 100) : 0;

// Recent records — use scan_date + scan_time, same as notifications page
$recent = $conn->query("
    SELECT scan_date, scan_time, status 
    FROM attendance 
    WHERE student_id='$user_id'
    ORDER BY scan_date DESC, scan_time DESC
    LIMIT 5
");

// Monthly — group by scan_date
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

// Weekly — group by scan_date day of week
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
?>

<style>
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

.table-card {
    background: #fff; border-radius: 14px;
    padding: 22px; box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.table-card h3 {
    font-size: 15px; font-weight: 600;
    color: #1e293b; margin-bottom: 16px;
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
</style>

<div class="section-title">📊 Student Dashboard</div>

<!-- STAT CARDS -->
<div class="stat-cards">
    <div class="stat-card green">
        <h4>Days Present</h4>
        <div class="stat-value"><?= $present ?></div>
        <div class="stat-sub">includes <?= $late ?> late arrival<?= $late !== 1 ? 's' : '' ?></div>
    </div>

    <div class="stat-card amber">
        <h4>Days Late</h4>
        <div class="stat-value"><?= $late ?></div>
    </div>

    <div class="stat-card red">
        <h4>Days Absent</h4>
        <div class="stat-value"><?= $absent ?></div>
    </div>

    <div class="stat-card blue">
        <h4>Attendance Rate</h4>
        <div class="stat-value"><?= $rate ?>%</div>
        <div class="progress">
            <div class="progress-bar" style="width:<?= $rate ?>%"></div>
        </div>
    </div>
</div>

<!-- INSIGHT -->
<?php
if ($total === 0):
?>
<div class="insight-box ok">📭 No attendance records yet. Records will appear once you start scanning your QR code.</div>
<?php elseif ($rate >= 90): ?>
<div class="insight-box good">🌟 Excellent! You have a <?= $rate ?>% attendance rate — keep it up!</div>
<?php elseif ($rate >= 75): ?>
<div class="insight-box ok">👍 Good — <?= $rate ?>% attendance. Try to be more consistent to stay on track.</div>
<?php elseif ($rate >= 50): ?>
<div class="insight-box warn">⚠️ Your attendance is at <?= $rate ?>%. Improvement is needed to avoid academic issues.</div>
<?php else: ?>
<div class="insight-box danger">🚨 Critical: Only <?= $rate ?>% attendance. Please contact your teacher or adviser immediately.</div>
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

<!-- RECENT TABLE -->
<div class="table-card">
    <h3>📋 Recent Attendance</h3>
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
                <td><?= $r['status'] === 'absent' ? '—' : date('h:i A', strtotime($r['scan_time'])) ?></td>
                <td>
                    <span class="badge <?= $r['status'] ?>">
                        <?= $r['status'] === 'present' ? '✅' : ($r['status'] === 'late' ? '⚠️' : '❌') ?>
                        <?= ucfirst($r['status']) ?>
                    </span>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="3" style="text-align:center;color:#9ca3af;padding:24px;">No records yet</td></tr>
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