<?php
include 'includes/db.php';

$user_id = $_SESSION['user_id'];

$present = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE student_id='$user_id' AND status='present'
")->fetch_assoc()['total'];

$absent = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE student_id='$user_id' AND status='absent'
")->fetch_assoc()['total'];

$total = $present + $absent;
$rate = $total > 0 ? round(($present / $total) * 100) : 0;

$recent = $conn->query("
    SELECT date_added, status 
    FROM attendance 
    WHERE student_id='$user_id'
    ORDER BY date_added DESC
    LIMIT 5
");

$monthly = $conn->query("
    SELECT 
        DATE_FORMAT(date_added, '%b') as month,
        SUM(status='present') as present,
        SUM(status='absent') as absent
    FROM attendance
    WHERE student_id='$user_id'
    GROUP BY MONTH(date_added)
");

$months = []; $presentData = []; $absentData = [];
while($m = $monthly->fetch_assoc()){
    $months[] = $m['month'];
    $presentData[] = (int)$m['present'];
    $absentData[] = (int)$m['absent'];
}

$weekly = $conn->query("
    SELECT 
        DATE_FORMAT(date_added, '%a') as day,
        SUM(status='present') as present
    FROM attendance
    WHERE student_id='$user_id'
    GROUP BY DAYOFWEEK(date_added)
");

$days = []; $weekData = [];
while($w = $weekly->fetch_assoc()){
    $days[] = $w['day'];
    $weekData[] = (int)$w['present'];
}
?>

<style>
/* ── STAT CARDS ── */
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
}

.stat-card h4 {
    font-size: 13px;
    color: #6b7280;
    font-weight: 500;
    margin-bottom: 10px;
}

.stat-card .stat-value {
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 4px;
}

.stat-card.green .stat-value { color: #16a34a; }
.stat-card.red   .stat-value { color: #dc2626; }
.stat-card.blue  .stat-value { color: #2563eb; }

.progress {
    background: #e5e7eb;
    height: 6px;
    border-radius: 99px;
    margin-top: 12px;
    overflow: hidden;
}

.progress-bar {
    background: #2563eb;
    height: 100%;
    border-radius: 99px;
    transition: width 0.6s ease;
}

/* ── INSIGHT ── */
.insight-box {
    background: #fff;
    padding: 14px 18px;
    border-left: 4px solid #2563eb;
    border-radius: 10px;
    margin-bottom: 24px;
    font-size: 14px;
    color: #374151;
    box-shadow: 0 2px 12px rgba(0,0,0,0.05);
}

/* ── CHARTS ── */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.chart-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.chart-card h3 {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 16px;
}

/* ── TABLE ── */
.table-card {
    background: #fff;
    border-radius: 14px;
    padding: 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.table-card h3 {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 16px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8fafc;
    padding: 11px 14px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    text-align: left;
}

.data-table td {
    padding: 12px 14px;
    font-size: 14px;
    color: #374151;
    border-bottom: 1px solid #f1f5f9;
}

.data-table tr:last-child td { border-bottom: none; }

.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 99px;
    font-size: 12px;
    font-weight: 600;
}

.badge.present { background: #dcfce7; color: #16a34a; }
.badge.absent  { background: #fee2e2; color: #dc2626; }

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}
</style>

<div class="section-title">📊 Student Dashboard</div>

<!-- STAT CARDS -->
<div class="stat-cards">
    <div class="stat-card green">
        <h4>Days Present</h4>
        <div class="stat-value"><?= $present ?></div>
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
<div class="insight-box">
<?php if($rate >= 90): ?>
    🌟 Excellent! Keep up the great attendance!
<?php elseif($rate >= 75): ?>
    👍 Good job! Try to improve consistency.
<?php else: ?>
    ⚠️ Warning: Attendance is low. Improve to avoid issues.
<?php endif; ?>
</div>

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
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if($recent->num_rows > 0): ?>
            <?php while($r = $recent->fetch_assoc()): ?>
            <tr>
                <td><?= date('M d, Y', strtotime($r['date_added'])) ?></td>
                <td><span class="badge <?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="2" style="text-align:center;color:#9ca3af;">No records yet</td></tr>
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
        y: { beginAtZero: true, grid: { color: '#f1f5f9' } }
    }
};

new Chart(document.getElementById("monthlyChart"), {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            { label: 'Present', data: <?= json_encode($presentData) ?>, backgroundColor: '#86efac', borderRadius: 6 },
            { label: 'Absent',  data: <?= json_encode($absentData) ?>,  backgroundColor: '#fca5a5', borderRadius: 6 }
        ]
    },
    options: chartDefaults
});

new Chart(document.getElementById("weeklyChart"), {
    type: 'line',
    data: {
        labels: <?= json_encode($days) ?>,
        datasets: [{
            label: 'Weekly Present',
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