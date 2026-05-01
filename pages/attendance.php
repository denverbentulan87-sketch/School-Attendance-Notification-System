<?php
include 'includes/db.php';

$totalStudents = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'")
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

$students = $conn->query("SELECT * FROM users WHERE role='student'");

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

<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap');

.att-page { font-family: 'DM Sans', sans-serif; display: flex; flex-direction: column; gap: 24px; }

/* Section card */
.att-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    overflow: hidden;
}

.att-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.att-card-header h2 {
    font-family: 'Sora', sans-serif;
    font-size: 16px;
    font-weight: 700;
    color: #0f1923;
}

.att-card-body { padding: 20px 24px; }

/* Date picker row */
.date-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.date-row label {
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
}

.date-row input[type="date"] {
    padding: 9px 13px;
    border-radius: 9px;
    border: 1.5px solid #e2e8f0;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    color: #0f1923;
    background: #f8fafc;
    outline: none;
    transition: border 0.15s;
}

.date-row input[type="date"]:focus { border-color: #16a34a; background: #fff; }

/* Student rows */
.student-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #f8fafc;
    padding: 11px 16px;
    border-radius: 10px;
    margin-bottom: 8px;
    transition: background 0.25s;
    border: 1px solid #f1f5f9;
}

.student-row:last-child { margin-bottom: 0; }

.student-row .s-name {
    font-size: 14px;
    font-weight: 500;
    color: #1e293b;
}

.student-row select {
    padding: 7px 12px;
    border-radius: 8px;
    border: 1.5px solid #e2e8f0;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    color: #374151;
    background: #fff;
    outline: none;
    cursor: pointer;
    transition: border 0.15s;
}

.student-row select:focus { border-color: #16a34a; }

/* Summary cards */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 14px;
}

.summary-card {
    border-radius: 12px;
    padding: 18px 16px;
    text-align: center;
}

.summary-card .s-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.summary-card .s-value {
    font-family: 'Sora', sans-serif;
    font-size: 32px;
    font-weight: 700;
    line-height: 1;
}

.summary-card.total   { background: #ede9fe; }
.summary-card.total   .s-label { color: #6d28d9; }
.summary-card.total   .s-value { color: #4c1d95; }

.summary-card.present { background: #dcfce7; }
.summary-card.present .s-label { color: #15803d; }
.summary-card.present .s-value { color: #14532d; }

.summary-card.absent  { background: #fee2e2; }
.summary-card.absent  .s-label { color: #b91c1c; }
.summary-card.absent  .s-value { color: #7f1d1d; }

/* Export button */
.btn-export {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: #0f1923;
    color: #fff;
    padding: 10px 18px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    text-decoration: none;
    transition: background 0.15s, transform 0.1s;
}

.btn-export:hover { background: #1e2d3d; transform: translateY(-1px); }

/* Filter form */
.filter-row {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-row input[type="date"] {
    padding: 9px 13px;
    border-radius: 9px;
    border: 1.5px solid #e2e8f0;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    color: #0f1923;
    background: #f8fafc;
    outline: none;
    transition: border 0.15s;
}

.filter-row input[type="date"]:focus { border-color: #16a34a; background: #fff; }

.btn-filter {
    padding: 9px 18px;
    border-radius: 9px;
    border: none;
    font-size: 13px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    background: #16a34a;
    color: #fff;
    box-shadow: 0 3px 10px rgba(22,163,74,0.3);
    transition: background 0.15s, transform 0.1s;
}

.btn-filter:hover { background: #15803d; transform: translateY(-1px); }

.btn-reset {
    padding: 9px 18px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    text-decoration: none;
    background: #f1f5f9;
    color: #475569;
    border: 1.5px solid #e2e8f0;
    transition: background 0.15s;
}

.btn-reset:hover { background: #e2e8f0; }

/* Table */
.att-table { width: 100%; border-collapse: collapse; }

.att-table thead tr { background: #f8fafc; border-bottom: 1px solid #e8eef4; }

.att-table th {
    padding: 12px 16px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.7px;
    text-transform: uppercase;
    color: #64748b;
    text-align: left;
}

.att-table td {
    padding: 12px 16px;
    font-size: 13.5px;
    color: #374151;
    border-bottom: 1px solid #f1f5f9;
}

.att-table tbody tr:hover { background: #fafcff; }
.att-table tbody tr:last-child td { border-bottom: none; }

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
}

.status-present { background: #dcfce7; color: #15803d; }
.status-absent  { background: #fee2e2; color: #b91c1c; }

.empty-row td {
    text-align: center;
    color: #94a3b8;
    font-style: italic;
    padding: 24px;
}
</style>

<div class="att-page">

    <!-- ── MARK ATTENDANCE ── -->
    <div class="att-card">
        <div class="att-card-header">
            <h2>Mark Attendance</h2>
        </div>
        <div class="att-card-body">
            <div class="date-row">
                <label for="attendance-date">Date</label>
                <input type="date" id="attendance-date" value="<?= date('Y-m-d') ?>">
            </div>

            <?php while($s = $students->fetch_assoc()): ?>
            <div class="student-row" id="row-<?= $s['id'] ?>">
                <span class="s-name"><?= htmlspecialchars($s['fullname']) ?></span>
                <select onchange="saveAttendance(this, <?= $s['id'] ?>)">
                    <option value="present">&#10004; Present</option>
                    <option value="absent">&#10006; Absent</option>
                </select>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- ── SUMMARY ── -->
    <div class="att-card">
        <div class="att-card-header">
            <h2>Today's Summary</h2>
            <a href="export_attendance.php" class="btn-export">
                &#8595; Export PDF
            </a>
        </div>
        <div class="att-card-body">
            <div class="summary-grid">
                <div class="summary-card total">
                    <div class="s-label">Total Students</div>
                    <div class="s-value"><?= $totalStudents ?></div>
                </div>
                <div class="summary-card present">
                    <div class="s-label">Present Today</div>
                    <div class="s-value"><?= $presentToday ?></div>
                </div>
                <div class="summary-card absent">
                    <div class="s-label">Absent Today</div>
                    <div class="s-value"><?= $absentToday ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── FILTER + RECORDS ── -->
    <div class="att-card">
        <div class="att-card-header">
            <h2>Attendance Records</h2>
        </div>
        <div class="att-card-body">

            <form method="GET" class="filter-row" style="margin-bottom:20px;">
                <input type="hidden" name="page" value="attendance">
                <input type="date" name="filter_date" value="<?= htmlspecialchars($_GET['filter_date'] ?? '') ?>">
                <button class="btn-filter" type="submit">Filter</button>
                <a href="admin_dashboard.php?page=attendance" class="btn-reset">Reset</a>
            </form>

            <table class="att-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php if($records && $records->num_rows > 0): ?>
                    <?php while($r = $records->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                        <td style="color:#64748b;"><?= $r['date_added'] ?></td>
                        <td>
                            <?php if($r['status'] === 'present'): ?>
                                <span class="status-badge status-present">&#10004; Present</span>
                            <?php else: ?>
                                <span class="status-badge status-absent">&#10006; Absent</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr class="empty-row">
                        <td colspan="3">No records found</td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>

</div>

<script>
function saveAttendance(select, studentId){
    let status = select.value;
    let date   = document.getElementById('attendance-date').value;

    fetch('save_attendance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `student_id=${studentId}&status=${status}&date=${date}`
    })
    .then(res => res.text())
    .then(() => {
        let row = document.getElementById('row-' + studentId);
        row.style.background = status === 'present' ? '#dcfce7' : '#fee2e2';
        row.style.borderColor = status === 'present' ? '#bbf7d0' : '#fecaca';

        select.style.borderColor = '#16a34a';
        setTimeout(() => { select.style.borderColor = ''; }, 800);
    });
}
</script>