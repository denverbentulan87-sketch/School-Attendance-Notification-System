<?php
include 'includes/db.php';

$today = date('Y-m-d');


$total = $conn->query("SELECT COUNT(*) as total FROM user WHERE role='student'")
              ->fetch_assoc()['total'];

$present = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE status='present' AND DATE(date_added)='$today'
")->fetch_assoc()['total'];

$absent = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE status='absent' AND DATE(date_added)='$today'
")->fetch_assoc()['total'];


$notifications = $conn->query("
    SELECT COUNT(*) as total FROM notifications
")->fetch_assoc()['total'];

$recent = $conn->query("
    SELECT user.name, attendance.status, attendance.date_added
    FROM attendance
    JOIN user ON user.id = attendance.student_id
    ORDER BY attendance.date_added DESC
    LIMIT 5
");

$weeklyData = ['present' => [], 'absent' => []];
$dates = [];

for($i = 6; $i >= 0; $i--){
    $d = date('Y-m-d', strtotime("-$i days"));

    $p = $conn->query("
        SELECT COUNT(*) as total FROM attendance 
        WHERE status='present' AND DATE(date_added)='$d'
    ")->fetch_assoc()['total'];

    $a = $conn->query("
        SELECT COUNT(*) as total FROM attendance 
        WHERE status='absent' AND DATE(date_added)='$d'
    ")->fetch_assoc()['total'];

    $dates[] = date('M d', strtotime($d));
    $weeklyData['present'][] = $p;
    $weeklyData['absent'][] = $a;
}
?>

<div class="cards">
    <div class="card total">
        <h3>Total Students</h3>
        <p><?= $total ?></p>
    </div>

    <div class="card present">
        <h3>Present Today</h3>
        <p><?= $present ?></p>
    </div>

    <div class="card absent">
        <h3>Absent Today</h3>
        <p><?= $absent ?></p>
    </div>

    <div class="card notify">
        <h3>Notifications Sent</h3>
        <p><?= $notifications ?></p>
    </div>
</div>

<div class="table">
    <h3>📋 Recent Attendance</h3>

    <table>
        <tr>
            <th>Name</th>
            <th>Status</th>
            <th>Date</th>
        </tr>

        <?php if($recent->num_rows > 0): ?>
            <?php while($r = $recent->fetch_assoc()): ?>
                <tr>
                    <td><?= $r['name'] ?></td>
                    <td>
                        <?php if($r['status'] == 'present'): ?>
                            <span style="color:green;">✔ Present</span>
                        <?php else: ?>
                            <span style="color:red;">✖ Absent</span>
                        <?php endif; ?>
                    </td>
                    <td><?= date('M d, Y', strtotime($r['date_added'])) ?></td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3" style="text-align:center;">No data available</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

<!-- ===================== -->
<!-- CHARTS -->
<!-- ===================== -->
<div class="charts">

    <div class="chart-box">
        <h3>📊 Today Attendance</h3>
        <canvas id="todayChart"></canvas>
    </div>

    <div class="chart-box">
        <h3>📈 Weekly Attendance Trend</h3>
        <canvas id="weeklyChart"></canvas>
    </div>

</div>

<!-- ===================== -->
<!-- CHART SCRIPT -->
<!-- ===================== -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// TODAY CHART
new Chart(document.getElementById('todayChart'), {
    type: 'bar',
    data: {
        labels: ['Present', 'Absent'],
        datasets: [{
            label: 'Today',
            data: [<?= $present ?>, <?= $absent ?>]
        }]
    }
});

// WEEKLY CHART
new Chart(document.getElementById('weeklyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [
            {
                label: 'Present',
                data: <?= json_encode($weeklyData['present']) ?>,
                fill: false
            },
            {
                label: 'Absent',
                data: <?= json_encode($weeklyData['absent']) ?>,
                fill: false
            }
        ]
    }
});
</script>

<!-- ===================== -->
<!-- STYLE -->
<!-- ===================== -->
<style>
.cards {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.card {
    flex: 1;
    min-width: 180px;
    padding: 15px;
    border-radius: 12px;
    background: #f9fafb;
    box-shadow: 0 2px 6px rgba(0,0,0,0.05);
}

.card h3 {
    font-size: 14px;
    color: #555;
}

.card p {
    font-size: 26px;
    font-weight: bold;
}

.card.present p { color: green; }
.card.absent p { color: red; }
.card.total p { color: #6366f1; }
.card.notify p { color: #f59e0b; }

.table {
    background: white;
    padding: 15px;
    border-radius: 12px;
}

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

/* CHARTS */
.charts {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.chart-box {
    flex: 1;
    min-width: 300px;
    background: white;
    padding: 15px;
    border-radius: 12px;
}
</style>