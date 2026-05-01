<?php
include 'includes/db.php';

if(!isset($_SESSION['email']) || $_SESSION['role'] !== 'parent'){
    echo "<p>Access denied.</p>"; exit;
}

$parent_email = $_SESSION['email'];

// Fix: table is 'users', column is 'fullname' not 'name'
$stmt = $conn->prepare("SELECT id, fullname FROM users WHERE parent_email = ?");
$stmt->bind_param("s", $parent_email);
$stmt->execute();
$children_result = $stmt->get_result();
$children = [];
while($row = $children_result->fetch_assoc()) $children[] = $row;

$selected_child = $_GET['child'] ?? '';
$selected_date  = $_GET['date']  ?? '';

$where = []; $params = []; $types = "";
if($selected_child){ $where[] = "a.student_id = ?"; $params[] = $selected_child; $types .= "i"; }
if($selected_date) { $where[] = "DATE(a.date_added) = ?"; $params[] = $selected_date; $types .= "s"; }
$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Fix: table is 'users', column is 'fullname', order by 'date_added'
$sql = "SELECT a.*, u.fullname 
        FROM attendance a 
        JOIN users u ON a.student_id = u.id 
        $where_sql 
        ORDER BY a.date_added DESC";

$stmt2 = $conn->prepare($sql);
if($params) $stmt2->bind_param($types, ...$params);
$stmt2->execute();
$result = $stmt2->get_result();

$present = 0; $absent = 0; $data_rows = [];
while($row = $result->fetch_assoc()){
    $data_rows[] = $row;
    if($row['status'] == 'present') $present++; else $absent++;
}
$total      = count($data_rows);
$percentage = $total > 0 ? round(($present/$total)*100) : 0;
?>

<style>
.section-title { font-size: 20px; font-weight: 600; color: #1e293b; margin-bottom: 20px; }

.filter-bar {
    display: flex; gap: 10px; align-items: center;
    margin-bottom: 20px; flex-wrap: wrap;
}

.filter-bar select,
.filter-bar input[type="date"] {
    padding: 9px 13px; border: 1px solid #e5e7eb;
    border-radius: 8px; font-size: 13px;
    font-family: 'Poppins', sans-serif;
    color: #374151; background: #fff; outline: none;
}

.filter-bar select:focus,
.filter-bar input:focus { border-color: #3b82f6; }

.filter-bar button {
    background: #22c55e; color: #fff; border: none;
    padding: 9px 20px; border-radius: 8px;
    font-size: 13px; font-family: 'Poppins', sans-serif;
    font-weight: 500; cursor: pointer;
}

.filter-bar button:hover { background: #16a34a; }

.progress-wrap {
    background: #e5e7eb; border-radius: 99px;
    overflow: hidden; margin-bottom: 20px; height: 28px;
}

.progress-fill {
    background: linear-gradient(90deg, #22c55e, #16a34a);
    height: 100%; border-radius: 99px;
    display: flex; align-items: center;
    justify-content: center; color: white;
    font-size: 12px; font-weight: 600;
    min-width: 40px; transition: width 0.6s ease;
}

.stat-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
    gap: 16px; margin-bottom: 24px;
}

.stat-card { background: #fff; border-radius: 14px; padding: 20px 22px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.stat-card h4 { font-size: 13px; color: #6b7280; font-weight: 500; margin-bottom: 8px; }
.stat-card .stat-value { font-size: 28px; font-weight: 700; }
.stat-card.green .stat-value { color: #16a34a; }
.stat-card.red   .stat-value { color: #dc2626; }
.stat-card.blue  .stat-value { color: #2563eb; }

.table-card { background: #fff; border-radius: 14px; padding: 22px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.table-card h3 { font-size: 15px; font-weight: 600; color: #1e293b; margin-bottom: 16px; }

.data-table { width: 100%; border-collapse: collapse; }
.data-table th { background: #f8fafc; padding: 11px 14px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; text-align: left; }
.data-table td { padding: 12px 14px; font-size: 14px; color: #374151; border-bottom: 1px solid #f1f5f9; }
.data-table tr:last-child td { border-bottom: none; }

.badge { display: inline-block; padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 600; }
.badge.present { background: #dcfce7; color: #16a34a; }
.badge.absent  { background: #fee2e2; color: #dc2626; }
</style>

<div class="section-title">📅 Attendance Dashboard</div>

<!-- FILTER -->
<form method="GET" class="filter-bar">
    <input type="hidden" name="page" value="attendance">
    <select name="child">
        <option value="">All Children</option>
        <?php foreach($children as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($selected_child == $c['id'])?'selected':'' ?>>
                <?= htmlspecialchars($c['fullname']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>">
    <button type="submit">Filter</button>
</form>

<!-- PROGRESS BAR -->
<div class="progress-wrap">
    <div class="progress-fill" style="width:<?= $percentage ?>%;"><?= $percentage ?>% Attendance Rate</div>
</div>

<!-- STAT CARDS -->
<div class="stat-cards">
    <div class="stat-card">
        <h4>Total Records</h4>
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
    <div class="stat-card blue">
        <h4>Attendance Rate</h4>
        <div class="stat-value"><?= $percentage ?>%</div>
    </div>
</div>

<!-- TABLE -->
<div class="table-card">
    <h3>📋 Attendance Records</h3>

    <?php if(empty($data_rows)): ?>
        <p style="text-align:center;color:#9ca3af;padding:20px;">📭 No attendance records found. Try a different filter.</p>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Status</th>
                <th>Date</th>
                <th>Time</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($data_rows as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['fullname']) ?></td>
            <td><span class="badge <?= $row['status'] ?>"><?= ucfirst($row['status']) ?></span></td>
            <td><?= date("M d, Y", strtotime($row['date_added'])) ?></td>
            <td><?= date("h:i A", strtotime($row['date_added'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>