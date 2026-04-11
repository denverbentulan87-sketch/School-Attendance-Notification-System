<?php
include 'includes/db.php';

$date = $_GET['date'] ?? date('Y-m-d');

$total = $conn->query("SELECT COUNT(*) as total FROM user WHERE role='student'")
              ->fetch_assoc()['total'];

$present = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE status='present' AND DATE(date_added)='$date'
")->fetch_assoc()['total'];

$absent = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE status='absent' AND DATE(date_added)='$date'
")->fetch_assoc()['total'];

$percentage = $total > 0 ? round(($present / $total) * 100) : 0;


$records = $conn->query("
    SELECT user.name, attendance.status, attendance.date_added
    FROM attendance
    JOIN user ON user.id = attendance.student_id
    WHERE DATE(attendance.date_added)='$date'
    ORDER BY attendance.date_added DESC
");
?>

<div class="table-container">

<h2>📊 Reports Dashboard</h2>

<!-- DATE FILTER -->
<form method="GET" style="margin-bottom:20px; display:flex; gap:10px;">
    <input type="hidden" name="page" value="reports">
    <input type="date" name="date" value="<?= $date ?>">
    <button class="btn">Filter</button>
</form>

<!-- SUMMARY CARDS -->
<div class="cards">
    <div class="card total">
        <h3>Total Students</h3>
        <p><?= $total ?></p>
    </div>

    <div class="card present">
        <h3>Present</h3>
        <p><?= $present ?></p>
    </div>

    <div class="card absent">
        <h3>Absent</h3>
        <p><?= $absent ?></p>
    </div>

    <div class="card percent">
        <h3>Attendance %</h3>
        <p><?= $percentage ?>%</p>
    </div>
</div>

<!-- RECORDS -->
<h3 style="margin-top:30px;">📋 Attendance Records (<?= $date ?>)</h3>

<table>
<tr>
    <th>Name</th>
    <th>Status</th>
    <th>Date</th>
</tr>

<?php if($records->num_rows > 0): ?>
    <?php while($r = $records->fetch_assoc()): ?>
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
        <td colspan="3" style="text-align:center;">No records found</td>
    </tr>
<?php endif; ?>

</table>

</div>

<!-- STYLE -->
<style>
.cards {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.card {
    flex: 1;
    min-width: 150px;
    padding: 15px;
    border-radius: 10px;
    color: white;
    text-align: center;
}

.card h3 {
    font-size: 14px;
}

.card p {
    font-size: 24px;
    font-weight: bold;
}

.card.total { background: #6366f1; }
.card.present { background: #22c55e; }
.card.absent { background: #ef4444; }
.card.percent { background: #f59e0b; }

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
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

.btn {
    background: #3b82f6;
    color: white;
    padding: 8px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}
</style>