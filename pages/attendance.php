<?php
include 'includes/db.php';

$totalStudents = $conn->query("SELECT COUNT(*) as total FROM user WHERE role='student'")
                      ->fetch_assoc()['total'];

$today = date('Y-m-d');

$presentToday = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE status='present' AND date_added='$today'
")->fetch_assoc()['total'];

$absentToday = $conn->query("
    SELECT COUNT(*) as total 
    FROM attendance 
    WHERE status='absent' AND date_added='$today'
")->fetch_assoc()['total'];

$students = $conn->query("SELECT * FROM user WHERE role='student'");
// FILTER FIX
$where = "";

if(isset($_GET['filter_date']) && !empty($_GET['filter_date'])){
    $filter = $_GET['filter_date'];
    $where = "WHERE attendance.date_added = '$filter'";
}

$records = $conn->query("
    SELECT user.name, attendance.date_added, attendance.status
    FROM attendance
    JOIN user ON user.id = attendance.student_id
    $where
    ORDER BY attendance.date_added DESC
");
?>

<div class="table-container">

    <h2>📅 Attendance Overview</h2>

    <!-- ✅ AUTO SAVE FORM -->
    <div class="attendance-form">

        <input type="date" id="attendance-date" value="<?= date('Y-m-d') ?>">

        <?php while($s = $students->fetch_assoc()): ?>
            <div class="student-row" id="row-<?= $s['id'] ?>">
                <span><?= htmlspecialchars($s['name']) ?></span>

                <select onchange="saveAttendance(this, <?= $s['id'] ?>)">
                    <option value="present">✅ Present</option>
                    <option value="absent">❌ Absent</option>
                </select>
            </div>
        <?php endwhile; ?>

    </div>

    <!-- SUMMARY -->
    <h2 style="margin-top:30px;">📊 Attendance Summary</h2>

    <div class="summary-cards">
        <div class="card total">
            <h3>Total Students</h3>
            <p><?= $totalStudents ?></p>
        </div>

        <div class="card present">
            <h3>Present Today</h3>
            <p><?= $presentToday ?></p>
        </div>

        <div class="card absent">
            <h3>Absent Today</h3>
            <p><?= $absentToday ?></p>
        </div>
    </div>

    <!-- EXPORT -->
    <a href="export_attendance.php" class="btn-add" style="margin-top:15px; display:inline-block;">
        📥 Export PDF
    </a>

    <!-- FILTER -->
    <h2 style="margin-top:30px;">🔍 Filter by Date</h2>

    <form method="GET" class="attendance-form" style="flex-direction:row; align-items:center; gap:10px;">
        
        <input type="hidden" name="page" value="attendance">

        <input type="date" name="filter_date" value="<?= $_GET['filter_date'] ?? '' ?>">

        <button class="btn-add">Filter</button>

        <a href="admin_dashboard.php?page=attendance" class="btn-add" style="background:#6b7280;">
            Reset
        </a>

    </form>

    <!-- RECORDS -->
    <h2 style="margin-top:30px;">📊 Attendance Records</h2>

    <table>
        <tr>
            <th>Name</th>
            <th>Date</th>
            <th>Status</th>
        </tr>

        <?php if($records && $records->num_rows > 0): ?>
            <?php while($r = $records->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td><?= $r['date_added'] ?></td>
                    <td>
                        <?php if($r['status'] == 'present'): ?>
                            <span style="color:green;">✔ Present</span>
                        <?php else: ?>
                            <span style="color:red;">✖ Absent</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3" style="text-align:center;">No records yet</td>
            </tr>
        <?php endif; ?>
    </table>

</div>

<!-- ✅ AUTO SAVE SCRIPT -->
<script>
function saveAttendance(select, studentId){

    let status = select.value;
    let date = document.getElementById('attendance-date').value;

    fetch('save_attendance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `student_id=${studentId}&status=${status}&date=${date}`
    })
    .then(res => res.text())
    .then(data => {

        // ✅ highlight row based on status
        let row = document.getElementById('row-' + studentId);

        if(status === 'present'){
            row.style.background = "#dcfce7"; // light green
        } else {
            row.style.background = "#fee2e2"; // light red
        }

        // ✅ quick save feedback
        select.style.border = "2px solid green";

        setTimeout(() => {
            select.style.border = "";
        }, 800);

    });
}
</script>

<style>
.attendance-form {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 20px;
}

.student-row {
    display: flex;
    justify-content: space-between;
    background: #f9fafb;
    padding: 10px;
    border-radius: 8px;
    transition: 0.3s;
}

.student-row select {
    padding: 5px;
    border-radius: 6px;
}

input[type="date"]{
    padding:8px;
    border-radius:6px;
    border:1px solid #ccc;
}

.btn-add {
    background: #3b82f6;
    color: white;
    padding: 10px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.btn-add:hover {
    background: #2563eb;
}

/* SUMMARY CARDS */
.summary-cards {
    display: flex;
    gap: 10px;
    margin-top: 10px;
    flex-wrap: wrap;
}

.card {
    flex: 1;
    min-width: 120px;
    padding: 12px;
    border-radius: 10px;
    color: white;
    text-align: center;
}

.card h3 {
    font-size: 13px;
    margin-bottom: 5px;
}

.card p {
    font-size: 20px;
}

.card.total { background: #6366f1; }
.card.present { background: #22c55e; }
.card.absent { background: #ef4444; }
</style>