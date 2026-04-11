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

$months = [];
$presentData = [];
$absentData = [];

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

$days = [];
$weekData = [];

while($w = $weekly->fetch_assoc()){
    $days[] = $w['day'];
    $weekData[] = (int)$w['present'];
}
?>

<h2>📊 Student Dashboard</h2>

<div class="cards">

    <div class="card green">
        <h4>Days Present</h4>
        <p><?= $present ?></p>
    </div>

    <div class="card red">
        <h4>Days Absent</h4>
        <p><?= $absent ?></p>
    </div>

    <div class="card blue">
        <h4>Attendance Rate</h4>
        <p><?= $rate ?>%</p>
        <div class="progress">
            <div class="progress-bar" style="width:<?= $rate ?>%"></div>
        </div>
    </div>

</div>

<!-- ===================== -->
<!-- PERFORMANCE MESSAGE -->
<!-- ===================== -->
<div class="insight-box">
<?php if($rate >= 90): ?>
    🌟 Excellent! Keep up the great attendance!
<?php elseif($rate >= 75): ?>
    👍 Good job! Try to improve consistency.
<?php else: ?>
    ⚠️ Warning: Attendance is low. Improve to avoid issues.
<?php endif; ?>
</div>

<!-- ===================== -->
<!-- CHARTS -->
<!-- ===================== -->
<div class="charts">

    <div class="chart-box">
        <h3>📈 Monthly Attendance</h3>
        <canvas id="monthlyChart"></canvas>
    </div>

    <div class="chart-box">
        <h3>📅 Weekly Attendance</h3>
        <canvas id="weeklyChart"></canvas>
    </div>

</div>

<!-- ===================== -->
<!-- RECENT TABLE -->
<!-- ===================== -->
<div class="table-box">
    <h3>📋 Recent Attendance</h3>

    <table>
        <tr>
            <th>Date</th>
            <th>Status</th>
        </tr>

        <?php if($recent->num_rows > 0): ?>
            <?php while($r = $recent->fetch_assoc()): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($r['date_added'])) ?></td>
                    <td class="<?= $r['status'] ?>">
                        <?= ucfirst($r['status']) ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="2">No records yet</td></tr>
        <?php endif; ?>
    </table>
</div>

<!-- ===================== -->
<!-- STYLE -->
<!-- ===================== -->
<style>

body {
    background:#f5f7fb;
    font-family: 'Segoe UI', sans-serif;
}

.cards{
    display:flex;
    gap:20px;
    margin-bottom:20px;
    flex-wrap:wrap;
}

.card{
    flex:1;
    min-width:200px;
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

.card h4{
    font-size:14px;
    color:#6b7280;
}

.card p{
    font-size:28px;
    font-weight:bold;
}

.green p{ color:#16a34a; }
.red p{ color:#dc2626; }
.blue p{ color:#2563eb; }

.progress{
    background:#eee;
    height:8px;
    border-radius:10px;
    margin-top:10px;
}

.progress-bar{
    background:#2563eb;
    height:100%;
    border-radius:10px;
}

/* INSIGHT */
.insight-box{
    background:white;
    padding:15px;
    border-left:5px solid #2563eb;
    margin-bottom:20px;
    border-radius:8px;
}

/* CHARTS */
.charts{
    display:flex;
    gap:20px;
    flex-wrap:wrap;
}

.chart-box{
    flex:1;
    min-width:300px;
    background:white;
    padding:20px;
    border-radius:12px;
    box-shadow:0 5px 15px rgba(0,0,0,0.05);
}

/* TABLE */
.table-box{
    background:white;
    padding:20px;
    border-radius:12px;
    margin-top:20px;
}

table{
    width:100%;
    border-collapse:collapse;
}

table th{
    background:#f1f5f9;
    padding:12px;
}

table td{
    padding:12px;
    border-bottom:1px solid #eee;
}

.present{
    color:#16a34a;
    font-weight:bold;
}

.absent{
    color:#dc2626;
    font-weight:bold;
}

</style>

<!-- ===================== -->
<!-- CHARTS -->
<!-- ===================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
new Chart(document.getElementById("monthlyChart"), {
    type: 'bar',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [
            {
                label: 'Present',
                data: <?= json_encode($presentData) ?>
            },
            {
                label: 'Absent',
                data: <?= json_encode($absentData) ?>
            }
        ]
    }
});

new Chart(document.getElementById("weeklyChart"), {
    type: 'line',
    data: {
        labels: <?= json_encode($days) ?>,
        datasets: [
            {
                label: 'Weekly Present',
                data: <?= json_encode($weekData) ?>
            }
        ]
    }
});
</script>