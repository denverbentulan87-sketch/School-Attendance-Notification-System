<?php
include 'includes/db.php';

$date = $_GET['date'] ?? date('Y-m-d');

// Fixed: FROM users instead of FROM user
$total = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='student'")->fetch_assoc()['total'];

$present = $conn->query("
    SELECT COUNT(*) as total FROM attendance
    WHERE status='present' AND DATE(date_added)='$date'
")->fetch_assoc()['total'];

$absent = $conn->query("
    SELECT COUNT(*) as total FROM attendance
    WHERE status='absent' AND DATE(date_added)='$date'
")->fetch_assoc()['total'];

$percentage = $total > 0 ? round(($present / $total) * 100) : 0;

// Fixed: JOIN users, use fullname
$records = $conn->query("
    SELECT users.fullname, attendance.status, attendance.date_added
    FROM attendance
    JOIN users ON users.id = attendance.student_id
    WHERE DATE(attendance.date_added)='$date'
    ORDER BY attendance.date_added DESC
");

$formatted_date = date('F d, Y', strtotime($date));
?>

<style>
.section-title {
    font-size: 20px; font-weight: 600;
    color: #1e293b; margin-bottom: 20px;
    display: flex; align-items: center; gap: 8px;
}

.filter-bar {
    display: flex; align-items: center;
    gap: 10px; margin-bottom: 24px; flex-wrap: wrap;
}

.filter-bar input[type="date"] {
    padding: 9px 13px; border: 1px solid #e5e7eb;
    border-radius: 8px; font-size: 13px;
    font-family: 'Poppins', sans-serif;
    color: #374151; background: #fff; outline: none;
}

.filter-bar input[type="date"]:focus { border-color: #3b82f6; }

.filter-btn {
    background: #3b82f6; color: white; border: none;
    padding: 9px 20px; border-radius: 8px; font-size: 13px;
    font-family: 'Poppins', sans-serif; font-weight: 500;
    cursor: pointer; transition: background 0.2s;
}

.filter-btn:hover { background: #2563eb; }
.filter-label { font-size: 13px; color: #64748b; margin-left: 4px; }

.stat-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 16px; margin-bottom: 24px;
}

.stat-card {
    border-radius: 14px; padding: 20px 22px;
    color: white; position: relative; overflow: hidden;
}

.stat-card::after {
    content: ''; position: absolute;
    right: -10px; bottom: -10px;
    width: 70px; height: 70px; border-radius: 50%;
    background: rgba(255,255,255,0.12);
}

.stat-card.indigo { background: linear-gradient(135deg, #6366f1, #4f46e5); }
.stat-card.green  { background: linear-gradient(135deg, #22c55e, #16a34a); }
.stat-card.red    { background: linear-gradient(135deg, #ef4444, #dc2626); }
.stat-card.amber  { background: linear-gradient(135deg, #f59e0b, #d97706); }

.stat-card h4 {
    font-size: 12px; font-weight: 500; opacity: 0.85;
    text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;
}

.stat-card .stat-value { font-size: 34px; font-weight: 700; line-height: 1; }

.progress-section {
    background: #fff; border-radius: 14px;
    padding: 20px 22px; margin-bottom: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.progress-section .ps-label {
    display: flex; justify-content: space-between;
    font-size: 13px; font-weight: 500; color: #374151; margin-bottom: 10px;
}

.progress-wrap { background: #e5e7eb; border-radius: 99px; overflow: hidden; height: 10px; }
.progress-fill { background: linear-gradient(90deg, #22c55e, #16a34a); height: 100%; border-radius: 99px; transition: width 0.6s ease; }

.table-card { background: #fff; border-radius: 14px; padding: 22px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.table-card .card-header { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }

.data-table { width: 100%; border-collapse: collapse; }
.data-table th { background: #f8fafc; padding: 11px 14px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; text-align: left; }
.data-table td { padding: 13px 14px; font-size: 14px; color: #374151; border-bottom: 1px solid #f1f5f9; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #f8fafc; }

.badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 600; }
.badge.present { background: #dcfce7; color: #16a34a; }
.badge.absent  { background: #fee2e2; color: #dc2626; }
</style>

<div class="section-title">📊 Reports Dashboard</div>

<form method="GET" class="filter-bar">
    <input type="hidden" name="page" value="reports">
    <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
    <button type="submit" class="filter-btn">Filter</button>
    <span class="filter-label">Showing results for <strong><?= $formatted_date ?></strong></span>
</form>

<div class="stat-cards">
    <div class="stat-card indigo">
        <h4>Total Students</h4>
        <div class="stat-value"><?= $total ?></div>
    </div>
    <div class="stat-card green">
        <h4>Present</h4>
        <div class="stat-value"><?= $present ?></div>
    </div>
    <div class="stat-card red">
        <h4>Absent</h4>
        <div class="stat-value"><?= $absent ?></div>
    </div>
    <div class="stat-card amber">
        <h4>Attendance %</h4>
        <div class="stat-value"><?= $percentage ?>%</div>
    </div>
</div>

<div class="progress-section">
    <div class="ps-label">
        <span>📈 Attendance Rate</span>
        <span><?= $present ?> / <?= $total ?> students present</span>
    </div>
    <div class="progress-wrap">
        <div class="progress-fill" style="width:<?= $percentage ?>%;"></div>
    </div>
</div>

<div class="table-card">
    <div class="card-header">📋 Attendance Records — <?= $formatted_date ?></div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php if($records && $records->num_rows > 0): ?>
            <?php while($r = $records->fetch_assoc()): ?>
            <tr>
                <!-- Fixed: fullname instead of name -->
                <td><?= htmlspecialchars($r['fullname']) ?></td>
                <td>
                    <span class="badge <?= $r['status'] ?>">
                        <?= $r['status'] == 'present' ? '✔' : '✖' ?>
                        <?= ucfirst($r['status']) ?>
                    </span>
                </td>
                <td style="color:#64748b;font-size:13px;"><?= date('M d, Y', strtotime($r['date_added'])) ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="3" style="text-align:center;color:#9ca3af;padding:28px;">
                    No records found for <?= $formatted_date ?>
                </td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>