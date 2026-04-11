<?php
include 'includes/db.php';

$parent_email = $_SESSION['email'] ?? '';

$children_result = $conn->query("
    SELECT id, name 
    FROM user 
    WHERE parent_email='$parent_email'
");

$children_data = [];
$child_ids = [];

while($row = $children_result->fetch_assoc()){
    $children_data[] = $row;
    $child_ids[] = $row['id'];
}

$total_children = count($children_data);
$ids = !empty($child_ids) ? implode(',', $child_ids) : "0";

$present_today = 0;
$absent_today = 0;

foreach($children_data as $child){
    $attendance = $conn->query("
        SELECT status 
        FROM attendance 
        WHERE student_id='{$child['id']}'
        AND DATE(created_at)=CURDATE()
    ");

    if($attendance && $attendance->num_rows > 0){
        $row = $attendance->fetch_assoc();

        if($row['status'] == 'present'){
            $present_today++;
        } else {
            $absent_today++;
        }
    }
}

// Notifications
$notif = 0;
if($ids !== "0"){
    $notif = $conn->query("
        SELECT COUNT(*) as total 
        FROM notifications 
        WHERE student_id IN ($ids)
    ")->fetch_assoc()['total'];
}
?>

<!-- ================= UI ================= -->

<h2 class="title">📊 Parent Dashboard</h2>

<div class="cards">

    <div class="card">
        <span>👨‍👩‍👧 Children Enrolled</span>
        <h2><?= $total_children ?></h2>
    </div>

    <div class="card green">
        <span>Present Today</span>
        <h2><?= $present_today ?></h2>
    </div>

    <div class="card red">
        <span>Absent Today</span>
        <h2><?= $absent_today ?></h2>
    </div>

    <div class="card blue">
        <span>Notifications</span>
        <h2><?= $notif ?></h2>
    </div>

</div>

<!-- 📊 CHART -->
<div class="chart-box">
    <h3>📈 Attendance Overview (Today)</h3>
    <canvas id="attendanceChart"></canvas>
</div>

<hr>

<h3>👨‍👩‍👧 My Children</h3>

<table>
<tr>
    <th>Name</th>
    <th>Status</th>
</tr>

<?php foreach($children_data as $child): 

    $attendance = $conn->query("
        SELECT status 
        FROM attendance 
        WHERE student_id='{$child['id']}'
        AND DATE(created_at)=CURDATE()
    ");

    $status = "No Record";

    if($attendance && $attendance->num_rows > 0){
        $row = $attendance->fetch_assoc();
        $status = ucfirst($row['status']);
    }
?>

<tr>
    <td><?= htmlspecialchars($child['name']) ?></td>
    <td class="<?= strtolower($status) ?>"><?= $status ?></td>
</tr>

<?php endforeach; ?>

</table>


<!-- ================= STYLE ================= -->
<style>
body {
    background: #f4f6f9;
    font-family: Arial, sans-serif;
}

.title{
    margin-bottom: 15px;
}

/* Cards */
.cards{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:20px;
}

.card{
    background:white;
    padding:20px;
    border-radius:12px;
    flex:1;
    min-width:180px;
    box-shadow:0 4px 10px rgba(0,0,0,0.05);
    transition:0.2s;
}

.card:hover{
    transform:translateY(-5px);
}

.card span{
    font-size:14px;
    color:#666;
}

.card h2{
    margin-top:10px;
}

/* Colors */
.green h2{ color:green; }
.red h2{ color:red; }
.blue h2{ color:#2563eb; }

/* Chart */
.chart-box{
    background:white;
    padding:20px;
    border-radius:12px;
    margin-bottom:20px;
    box-shadow:0 4px 10px rgba(0,0,0,0.05);
}

/* Table */
table{
    width:100%;
    background:white;
    border-radius:10px;
    overflow:hidden;
}

table th{
    background:#f1f5f9;
    padding:12px;
    text-align:left;
}

table td{
    padding:12px;
    border-bottom:1px solid #eee;
}

.present{
    color:green;
    font-weight:bold;
}

.absent{
    color:red;
    font-weight:bold;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctx = document.getElementById('attendanceChart');

new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Present', 'Absent'],
        datasets: [{
            data: [<?= $present_today ?>, <?= $absent_today ?>],
            backgroundColor: ['green', 'red']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
</script>