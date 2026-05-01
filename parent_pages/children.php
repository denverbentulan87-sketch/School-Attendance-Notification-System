<?php
include 'includes/db.php';

if(!isset($_SESSION['email'])){
    echo "<p>Please login first.</p>"; exit;
}

$parent_email = $_SESSION['email'];

$stmt = $conn->prepare("SELECT id, name, email FROM user WHERE parent_email = ?");
$stmt->bind_param("s", $parent_email);
$stmt->execute();
$result = $stmt->get_result();

$children = [];
while($row = $result->fetch_assoc()) $children[] = $row;
?>

<style>
.section-title { font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 20px; }

/* EMPTY */
.empty-card {
    background: #fff; padding: 40px; border-radius: 14px;
    text-align: center; color: #9ca3af; font-size: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

/* GRID */
.children-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

/* CHILD CARD */
.child-card {
    background: #fff; padding: 22px;
    border-radius: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.child-header {
    display: flex; align-items: center;
    gap: 14px; margin-bottom: 18px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
}

.child-avatar {
    width: 46px; height: 46px;
    border-radius: 50%; background: #3b82f6;
    color: white; display: flex;
    align-items: center; justify-content: center;
    font-weight: 700; font-size: 18px; flex-shrink: 0;
}

.child-name { font-size: 15px; font-weight: 600; color: #1e293b; }
.child-email { font-size: 12px; color: #94a3b8; margin-top: 2px; }

/* STATS ROW */
.child-stats {
    display: flex; gap: 12px; margin-bottom: 16px;
}

.child-stat {
    flex: 1; background: #f8fafc;
    border-radius: 10px; padding: 12px;
    text-align: center;
}

.child-stat .cs-label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.child-stat .cs-value { font-size: 22px; font-weight: 700; }
.child-stat.green .cs-value { color: #16a34a; }
.child-stat.red   .cs-value { color: #dc2626; }

/* CHART */
.child-chart-label { font-size: 12px; font-weight: 600; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
</style>

<div class="section-title">👨‍👩‍👧 My Children</div>

<?php if(empty($children)): ?>
    <div class="empty-card">📭 No children linked to your account yet.</div>
<?php else: ?>

<div class="children-grid">
<?php foreach($children as $child):
    $present = 0; $absent = 0;
    $stmt2 = $conn->prepare("SELECT status FROM attendance WHERE student_id = ?");
    $stmt2->bind_param("i", $child['id']);
    $stmt2->execute();
    $att = $stmt2->get_result();
    while($a = $att->fetch_assoc()){
        if($a['status'] == 'present') $present++; else $absent++;
    }

    $weekly = [0,0,0,0,0,0,0];
    $stmt3 = $conn->prepare("SELECT DAYOFWEEK(created_at) as day, status FROM attendance WHERE student_id=? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $stmt3->bind_param("i", $child['id']);
    $stmt3->execute();
    $week = $stmt3->get_result();
    while($w = $week->fetch_assoc()){
        if($w['status'] == 'present') $weekly[$w['day']-1]++;
    }
?>
<div class="child-card">
    <div class="child-header">
        <div class="child-avatar"><?= strtoupper(substr($child['name'],0,1)) ?></div>
        <div>
            <div class="child-name"><?= htmlspecialchars($child['name']) ?></div>
            <div class="child-email"><?= htmlspecialchars($child['email']) ?></div>
        </div>
    </div>

    <div class="child-stats">
        <div class="child-stat green">
            <div class="cs-label">Present</div>
            <div class="cs-value"><?= $present ?></div>
        </div>
        <div class="child-stat red">
            <div class="cs-label">Absent</div>
            <div class="cs-value"><?= $absent ?></div>
        </div>
    </div>

    <div class="child-chart-label">📈 This Week</div>
    <canvas id="chart<?= $child['id'] ?>" style="max-height:120px;"></canvas>
</div>

<script>
new Chart(document.getElementById('chart<?= $child['id'] ?>'), {
    type: 'line',
    data: {
        labels: ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
        datasets: [{
            label: 'Present',
            data: <?= json_encode($weekly) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.08)',
            borderWidth: 2,
            pointBackgroundColor: '#3b82f6',
            tension: 0.4, fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } }
        }
    }
});
</script>

<?php endforeach; ?>
</div>

<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>