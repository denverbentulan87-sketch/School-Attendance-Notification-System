<?php
include 'includes/db.php';

if (!isset($_SESSION['email']) || $_SESSION['role'] !== 'parent') {
    echo "<p>Access denied.</p>"; exit;
}

$parent_email = $_SESSION['email'];

$stmt = $conn->prepare("SELECT id, fullname FROM users WHERE parent_email = ?");
$stmt->bind_param("s", $parent_email);
$stmt->execute();
$childRes = $stmt->get_result();
$children = [];
while ($row = $childRes->fetch_assoc()) $children[] = $row;

$selected_child  = $_GET['child']  ?? '';
$selected_status = $_GET['status'] ?? '';
$selected_date   = $_GET['date']   ?? '';

// Build query using scan_date / scan_time (correct columns)
$where = []; $params = []; $types = '';

if ($selected_child) {
    $where[] = 'a.student_id = ?';
    $params[] = (int)$selected_child;
    $types .= 'i';
} else {
    // Restrict to this parent's children
    $child_ids = array_column($children, 'id');
    $ids_sql   = !empty($child_ids) ? implode(',', $child_ids) : '0';
    $where[]   = "a.student_id IN ($ids_sql)";
}

if ($selected_status) {
    $where[] = 'a.status = ?';
    $params[] = $selected_status;
    $types .= 's';
}

if ($selected_date) {
    $where[] = 'a.scan_date = ?';
    $params[] = $selected_date;
    $types .= 's';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT a.status, a.scan_date, a.scan_time, u.fullname
    FROM attendance a
    JOIN users u ON u.id = a.student_id
    $where_sql
    ORDER BY a.scan_date DESC, a.scan_time DESC
";

$stmt2 = $conn->prepare($sql);
if ($params) $stmt2->bind_param($types, ...$params);
$stmt2->execute();
$result = $stmt2->get_result();

$data_rows = []; $counts = ['present' => 0, 'late' => 0, 'absent' => 0];
while ($row = $result->fetch_assoc()) {
    $data_rows[] = $row;
    if (isset($counts[$row['status']])) $counts[$row['status']]++;
}
$total      = count($data_rows);
$attended   = $counts['present'] + $counts['late'];
$rate       = $total > 0 ? round(($attended / $total) * 100) : 0;
$barClass   = $rate >= 90 ? 'high' : ($rate >= 75 ? 'mid' : 'low');
?>

<style>
.ah-page { display: flex; flex-direction: column; gap: 22px; }

.section-title {
    font-size: 20px; font-weight: 700; color: #0f1923;
    display: flex; align-items: center; gap: 8px; margin-bottom: 4px;
}
.section-sub { font-size: 13px; color: #64748b; }

/* FILTER BAR */
.filter-card {
    background: #fff; border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

.filter-card-title {
    font-size: 13px; font-weight: 700;
    color: #64748b; margin-bottom: 12px;
    text-transform: uppercase; letter-spacing: 0.5px;
}

.filter-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

.filter-row select,
.filter-row input[type="date"] {
    padding: 9px 13px; border: 1px solid #e5e7eb;
    border-radius: 10px; font-size: 13px;
    color: #374151; background: #fff; outline: none;
    transition: border-color 0.2s; min-width: 160px;
}

.filter-row select:focus,
.filter-row input:focus { border-color: #3b82f6; }

.btn-filter {
    background: #0f1923; color: #fff; border: none;
    padding: 9px 22px; border-radius: 10px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    transition: background 0.2s; white-space: nowrap;
}
.btn-filter:hover { background: #1e2d3d; }

.btn-reset {
    background: #f1f5f9; color: #64748b; border: none;
    padding: 9px 16px; border-radius: 10px;
    font-size: 13px; font-weight: 500; cursor: pointer;
    text-decoration: none; display: inline-block;
    transition: background 0.2s;
}
.btn-reset:hover { background: #e2e8f0; }

/* STATS */
.stat-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 14px;
}

.stat-card {
    background: #fff; border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    position: relative; overflow: hidden;
}

.stat-card::before {
    content: ''; position: absolute;
    top: 0; left: 0; width: 4px; height: 100%;
    border-radius: 14px 0 0 14px;
}
.stat-card.slate::before  { background: #64748b; }
.stat-card.green::before  { background: #16a34a; }
.stat-card.amber::before  { background: #f59e0b; }
.stat-card.red::before    { background: #ef4444; }
.stat-card.blue::before   { background: #2563eb; }

.stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.7px; color: #94a3b8; margin-bottom: 6px; }
.stat-val   { font-size: 30px; font-weight: 800; line-height: 1; color: #0f1923; }
.stat-card.green .stat-val { color: #16a34a; }
.stat-card.amber .stat-val { color: #f59e0b; }
.stat-card.red   .stat-val { color: #ef4444; }
.stat-card.blue  .stat-val { color: #2563eb; }

/* RATE BAR */
.rate-card { background: #fff; border-radius: 14px; padding: 18px 22px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
.rate-label { font-size: 13px; font-weight: 700; color: #0f1923; margin-bottom: 10px; }
.rate-bar-wrap { background: #e5e7eb; border-radius: 99px; height: 10px; overflow: hidden; margin-bottom: 10px; }
.rate-bar-fill { height: 100%; border-radius: 99px; transition: width 0.6s ease; }
.rate-bar-fill.high { background: linear-gradient(90deg, #22c55e, #16a34a); }
.rate-bar-fill.mid  { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
.rate-bar-fill.low  { background: linear-gradient(90deg, #f87171, #ef4444); }
.rate-text { font-size: 13px; color: #64748b; }
.rate-pct  { font-size: 20px; font-weight: 800; color: #0f1923; }

/* TABLE */
.table-card { background: #fff; border-radius: 14px; padding: 20px 22px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
.table-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 8px; }
.table-title  { font-size: 14px; font-weight: 700; color: #0f1923; }
.table-count  { font-size: 12px; color: #94a3b8; }

.data-table { width: 100%; border-collapse: collapse; }
.data-table th { background: #f8fafc; padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; text-align: left; }
.data-table td { padding: 12px 14px; font-size: 13px; color: #374151; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #f8fafc; }

.badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; }
.badge.present { background: #dcfce7; color: #16a34a; }
.badge.late    { background: #fef3c7; color: #d97706; }
.badge.absent  { background: #fee2e2; color: #dc2626; }

.empty-state { text-align: center; padding: 48px 20px; }
.empty-state .ei { font-size: 40px; margin-bottom: 10px; }
.empty-state p { font-size: 13px; color: #94a3b8; }
</style>

<div class="ah-page">

    <div>
        <div class="section-title">📅 Attendance History</div>
        <div class="section-sub">Full attendance log for your child. Filter by name, status, or date.</div>
    </div>

    <!-- Filter -->
    <div class="filter-card">
        <div class="filter-card-title">🔍 Filter Records</div>
        <form method="GET" class="filter-row">
            <input type="hidden" name="page" value="attendance">
            <select name="child">
                <option value="">All Children</option>
                <?php foreach ($children as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ($selected_child == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['fullname']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">All Statuses</option>
                <option value="present" <?= $selected_status === 'present' ? 'selected' : '' ?>>✅ Present</option>
                <option value="late"    <?= $selected_status === 'late'    ? 'selected' : '' ?>>⚠️ Late</option>
                <option value="absent"  <?= $selected_status === 'absent'  ? 'selected' : '' ?>>❌ Absent</option>
            </select>
            <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>">
            <button type="submit" class="btn-filter">Apply Filter</button>
            <a href="?page=attendance" class="btn-reset">Reset</a>
        </form>
    </div>

    <!-- Stats -->
    <div class="stat-row">
        <div class="stat-card slate">
            <div class="stat-label">Total Records</div>
            <div class="stat-val"><?= $total ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Present</div>
            <div class="stat-val"><?= $counts['present'] ?></div>
        </div>
        <div class="stat-card amber">
            <div class="stat-label">Late</div>
            <div class="stat-val"><?= $counts['late'] ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-label">Absent</div>
            <div class="stat-val"><?= $counts['absent'] ?></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-label">Attendance Rate</div>
            <div class="stat-val"><?= $rate ?>%</div>
        </div>
    </div>

    <!-- Rate bar -->
    <div class="rate-card">
        <div class="rate-label">
            Attendance Rate
            <span style="float:right;" class="rate-pct"><?= $rate ?>%</span>
        </div>
        <div class="rate-bar-wrap">
            <div class="rate-bar-fill <?= $barClass ?>" style="width:<?= $rate ?>%;"></div>
        </div>
        <div class="rate-text">
            <?= $attended ?> attended (present + late) out of <?= $total ?> total school days recorded.
        </div>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">📋 Attendance Records</div>
            <div class="table-count"><?= $total ?> record<?= $total !== 1 ? 's' : '' ?></div>
        </div>

        <?php if (!empty($data_rows)): ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Time Scanned</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($data_rows as $i => $row): ?>
            <tr>
                <td style="color:#94a3b8;"><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($row['fullname']) ?></strong></td>
                <td>
                    <span class="badge <?= $row['status'] ?>">
                        <?= $row['status'] === 'present' ? '✅' : ($row['status'] === 'late' ? '⚠️' : '❌') ?>
                        <?= ucfirst($row['status']) ?>
                    </span>
                </td>
                <td><?= date('M d, Y', strtotime($row['scan_date'])) ?></td>
                <td><?= $row['status'] === 'absent' ? '<span style="color:#94a3b8;">—</span>' : date('h:i A', strtotime($row['scan_time'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="empty-state">
                <div class="ei">📭</div>
                <p>No attendance records found<?= ($selected_child || $selected_status || $selected_date) ? ' for the selected filters' : '' ?>.</p>
            </div>
        <?php endif; ?>
    </div>

</div>