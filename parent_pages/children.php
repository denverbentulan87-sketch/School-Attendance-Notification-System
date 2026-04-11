<?php

include 'includes/db.php';

// ✅ FIX 2: Check login properly
if(!isset($_SESSION['email'])){
    echo "<p>Please login first.</p>";
    exit;
}

$parent_email = $_SESSION['email'];

// ✅ FIX 3: 
$stmt = $conn->prepare("
    SELECT id, name, email 
    FROM user 
    WHERE parent_email = ?
");
$stmt->bind_param("s", $parent_email);
$stmt->execute();
$result = $stmt->get_result();

$children = [];
while($row = $result->fetch_assoc()){
    $children[] = $row;
}
?>

<h2>👨‍👩‍👧 My Children</h2>

<?php if(empty($children)): ?>
    <div class="empty-box">
        <p>No children linked to your account yet.</p>
    </div>
<?php else: ?>

<div class="children-grid">

<?php foreach($children as $child):

    // ✅ Attendance summary
    $present = 0;
    $absent = 0;

    $stmt2 = $conn->prepare("
        SELECT status 
        FROM attendance 
        WHERE student_id = ?
    ");
    $stmt2->bind_param("i", $child['id']);
    $stmt2->execute();
    $att = $stmt2->get_result();

    while($a = $att->fetch_assoc()){
        if($a['status'] == 'present') $present++;
        else $absent++;
    }

    // ✅ Weekly chart data
    $weekly = [0,0,0,0,0,0,0];

    $stmt3 = $conn->prepare("
        SELECT DAYOFWEEK(created_at) as day, status
        FROM attendance
        WHERE student_id = ?
        AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt3->bind_param("i", $child['id']);
    $stmt3->execute();
    $week = $stmt3->get_result();

    while($w = $week->fetch_assoc()){
        if($w['status'] == 'present'){
            $weekly[$w['day']-1]++;
        }
    }

?>

<div class="child-card">

    <!-- CHILD INFO -->
    <div class="child-header">
        <div class="avatar">
            <?= strtoupper(substr($child['name'],0,1)) ?>
        </div>
        <div>
            <h3><?= htmlspecialchars($child['name']) ?></h3>
            <p><?= htmlspecialchars($child['email']) ?></p>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats">
        <div>
            <span>Present</span>
            <strong class="green"><?= $present ?></strong>
        </div>
        <div>
            <span>Absent</span>
            <strong class="red"><?= $absent ?></strong>
        </div>
    </div>

    <!-- CHART -->
    <canvas id="chart<?= $child['id'] ?>"></canvas>

</div>

<script>
new Chart(document.getElementById('chart<?= $child['id'] ?>'), {
    type: 'line',
    data: {
        labels: ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
        datasets: [{
            label: 'Attendance',
            data: <?= json_encode($weekly) ?>,
            tension: 0.3
        }]
    },
    options: {
        plugins: { legend: { display:false } },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>

<?php endforeach; ?>

</div>

<?php endif; ?>

<!-- CHART JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

/* PAGE TITLE */
h2{
    margin-bottom:15px;
}

/* EMPTY STATE */
.empty-box{
    background:white;
    padding:30px;
    border-radius:12px;
    text-align:center;
    color:#777;
    box-shadow:0 2px 6px rgba(0,0,0,0.05);
}

/* GRID */
.children-grid{
    display:grid;
    grid-template-columns: repeat(auto-fit, minmax(300px,1fr));
    gap:20px;
}

/* CARD */
.child-card{
    background:white;
    padding:20px;
    border-radius:14px;
    box-shadow:0 4px 10px rgba(0,0,0,0.06);
}

/* HEADER */
.child-header{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:15px;
}

.avatar{
    width:45px;
    height:45px;
    border-radius:50%;
    background:#2563eb;
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:bold;
    font-size:18px;
}

/* STATS */
.stats{
    display:flex;
    justify-content:space-between;
    margin-bottom:15px;
}

.stats span{
    display:block;
    font-size:13px;
    color:#777;
}

.stats strong{
    font-size:18px;
}

.green{ color:#16a34a; }
.red{ color:#dc2626; }

/* CANVAS */
canvas{
    margin-top:10px;
}

</style>