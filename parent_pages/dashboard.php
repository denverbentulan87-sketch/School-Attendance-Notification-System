<?php
include 'includes/db.php';

$parent_email = $_SESSION['email'] ?? '';

$children_result = $conn->query("SELECT id, name FROM user WHERE parent_email='$parent_email'");
$children_data = []; $child_ids = [];
while($row = $children_result->fetch_assoc()){
    $children_data[] = $row;
    $child_ids[] = $row['id'];
}

$total_children = count($children_data);
$ids = !empty($child_ids) ? implode(',', $child_ids) : "0";

$present_today = 0; $absent_today = 0;
foreach($children_data as $child){
    $att = $conn->query("SELECT status FROM attendance WHERE student_id='{$child['id']}' AND DATE(created_at)=CURDATE()");
    if($att && $att->num_rows > 0){
        $r = $att->fetch_assoc();
        if($r['status'] == 'present') $present_today++;
        else $absent_today++;
    }
}

$notif = ($ids !== "0")
    ? $conn->query("SELECT COUNT(*) as total FROM notifications WHERE student_id IN ($ids)")->fetch_assoc()['total']
    : 0;
?>

<style>
.section-title { font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 20px; }

/* STAT CARDS */
.stat-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px; margin-bottom: 24px;
}

.stat-card {
    background: #fff; border-radius: 14px;
    padding: 22px 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.stat-card h4 { font-size: 13px; color: #6b7280; font-weight: 500; margin-bottom: 10px; }
.stat-card .stat-value { font-size: 32px; font-weight: 700; line-height: 1; }
.stat-card.green .stat-value { color: #16a34a; }
.stat-card.red   .stat-value { color: #dc2626; }
.stat-card.blue  .stat-value { color: #2563eb; }
.stat-card.amber .stat-value { color: #d97706; }

/* CHARTS GRID */
.charts-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px; margin-bottom: 24px;
}

.chart-card {
    background: #fff; border-radius: 14px;
    padding: 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.chart-card h3 { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 16px; }

/* TABLE CARD */
.table-card {
    background: #fff; border-radius: 14px;
    padding: 22px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.table-card h3 { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 16px; }

.data-table { width: 100%; border-collapse: collapse; }

.data-table th {
    background: #f8fafc; padding: 11px 14px;
    font-size: 12px; font-weight: 600;
    text-transform: uppercase; letter-spacing: 0.5px;
    color: #64748b; text-align: left;
}

.data-table td {
    padding: 12px 14px; font-size: 14px;
    color: #374151; border-bottom: 1px solid #f1f5f9;
}

.data-table tr:last-child td { border-bottom: none; }

.badge { display: inline-block; padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 600; }
.badge.present  { background: #dcfce7; color: #16a34a; }
.badge.absent   { background: #fee2e2; color: #dc2626; }
.badge.no-record { background: #f1f5f9; color: #64748b; }
</style>

<div class="section-title">📊 Parent Dashboard</div>

<!-- STAT CARDS -->
<div class="stat-cards">
    <div class="stat-card">
        <h4>👨‍👩‍👧 Children Enrolled</h4>
        <div class="stat-value"><?= $total_children ?></div>
    </div>
    <div class="stat-card green">
        <h4>Present Today</h4>
        <div class="stat-value"><?= $present_today ?></div>
    </div>
    <div class="stat-card red">
        <h4>Absent Today</h4>
        <div class="stat-value"><?= $absent_today ?></div>
    </div>
    <div class="stat-card amber">
        <h4>Notifications</h4>
        <div class="stat-value"><?= $notif ?></div>
    </div>
</div>

<!-- CHARTS -->
<div class="charts-grid">
    <div class="chart-card">
        <h3>📈 Attendance Overview (Today)</h3>
        <canvas id="attendanceChart" style="max-height:220px;"></canvas>
    </div>
    <div class="chart-card">
        <h3>📊 Children Status</h3>
        <canvas id="childrenChart" style="max-height:220px;"></canvas>
    </div>
</div>

<!-- CHILDREN TABLE -->
<div class="table-card">
    <h3>👨‍👩‍👧 My Children — Today's Status</h3>
    <table class="data-table">
        <thead>
            <tr><th>Name</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php foreach($children_data as $child):
            $att = $conn->query("SELECT status FROM attendance WHERE student_id='{$child['id']}' AND DATE(created_at)=CURDATE()");
            $status = "no-record"; $label = "No Record";
            if($att && $att->num_rows > 0){
                $r = $att->fetch_assoc();
                $status = $r['status'];
                $label  = ucfirst($status);
            }
        ?>
        <tr>
            <td><?= htmlspecialchars($child['name']) ?></td>
            <td><span class="badge <?= $status ?>"><?= $label ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($children_data)): ?>
            <tr><td colspan="2" style="text-align:center;color:#9ca3af;padding:20px;">No children linked to your account</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const chartOpts = {
    responsive: true,
    plugins: { legend: { position: 'bottom' } }
};

new Chart(document.getElementById('attendanceChart'), {
    type: 'doughnut',
    data: {
        labels: ['Present', 'Absent'],
        datasets: [{ data: [<?= $present_today ?>, <?= $absent_today ?>], backgroundColor: ['#86efac','#fca5a5'], borderWidth: 0 }]
    },
    options: chartOpts
});

new Chart(document.getElementById('childrenChart'), {
    type: 'bar',
    data: {
        labels: ['Present', 'Absent', 'No Record'],
        datasets: [{ data: [<?= $present_today ?>, <?= $absent_today ?>, <?= max(0, $total_children - $present_today - $absent_today) ?>], backgroundColor: ['#86efac','#fca5a5','#e2e8f0'], borderRadius: 8 }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { x: { grid: { display: false } }, y: { beginAtZero: true, grid: { color: '#f1f5f9' } } }
    }
});
</script>