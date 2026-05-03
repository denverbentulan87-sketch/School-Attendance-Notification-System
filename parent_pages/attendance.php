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

$where = []; $params = []; $types = '';

if ($selected_child) {
    // Validate that the selected child belongs to this parent
    $valid = false;
    foreach ($children as $c) { if ($c['id'] == $selected_child) { $valid = true; break; } }
    if ($valid) {
        $where[] = 'a.student_id = ?';
        $params[] = (int)$selected_child;
        $types .= 'i';
    }
} else {
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
    SELECT a.status, a.scan_date, a.scan_time, u.fullname, u.id as student_id
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
$total    = count($data_rows);
$attended = $counts['present'] + $counts['late'];
$rate     = $total > 0 ? round(($attended / $total) * 100) : 0;
$barClass = $rate >= 90 ? 'high' : ($rate >= 75 ? 'mid' : 'low');

// Month breakdown for filtered set
$month_counts = [];
foreach ($data_rows as $r) {
    $mo = date('M Y', strtotime($r['scan_date']));
    if (!isset($month_counts[$mo])) $month_counts[$mo] = ['present' => 0, 'late' => 0, 'absent' => 0];
    if (isset($month_counts[$mo][$r['status']])) $month_counts[$mo][$r['status']]++;
}
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap');

.ah-wrap * { box-sizing: border-box; font-family: 'Sora', sans-serif; }

.ah-wrap {
    display: flex; flex-direction: column; gap: 24px;
    background: #f0f4f8; min-height: 100%;
    padding: 4px 0 32px;
}

/* HEADER */
.ah-header { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
.ah-header h1 { font-size: 22px; font-weight: 800; color: #0d1b2a; margin: 0 0 4px; letter-spacing: -0.5px; }
.ah-header p  { font-size: 13px; color: #64748b; margin: 0; }

/* FILTER CARD */
.filter-card {
    background: #fff; border-radius: 16px;
    padding: 20px 22px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.07);
}
.filter-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 14px; }
.filter-row   { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }

.filter-row select,
.filter-row input[type="date"] {
    padding: 10px 14px; border: 1.5px solid #e5e7eb;
    border-radius: 10px; font-size: 13px; font-family: 'Sora', sans-serif;
    color: #374151; background: #fff; outline: none;
    transition: border-color 0.2s; min-width: 160px;
}
.filter-row select:focus,
.filter-row input:focus { border-color: #0d1b2a; }

.btn-apply {
    background: #0d1b2a; color: #fff; border: none;
    padding: 10px 24px; border-radius: 10px;
    font-size: 13px; font-weight: 600; font-family: 'Sora', sans-serif;
    cursor: pointer; transition: background 0.2s; white-space: nowrap;
}
.btn-apply:hover { background: #1e2d3d; }

.btn-reset {
    background: #f1f5f9; color: #64748b; border: none;
    padding: 10px 16px; border-radius: 10px;
    font-size: 13px; font-weight: 500; cursor: pointer;
    text-decoration: none; display: inline-block;
    font-family: 'Sora', sans-serif; transition: background 0.2s;
}
.btn-reset:hover { background: #e2e8f0; color: #374151; }

/* STAT STRIP */
.stat-strip {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
    gap: 12px;
}

.stat-tile {
    background: #fff; border-radius: 14px;
    padding: 16px 18px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.07);
    position: relative; overflow: hidden;
}
.stat-tile::after {
    content: ''; position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px; border-radius: 0 0 14px 14px;
}
.stat-tile.s-total::after   { background: #94a3b8; }
.stat-tile.s-present::after { background: #16a34a; }
.stat-tile.s-late::after    { background: #f59e0b; }
.stat-tile.s-absent::after  { background: #ef4444; }
.stat-tile.s-rate::after    { background: #2563eb; }

.stat-tile-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 6px; }
.stat-tile-val   { font-size: 28px; font-weight: 800; line-height: 1; color: #0d1b2a; }
.stat-tile.s-present .stat-tile-val { color: #16a34a; }
.stat-tile.s-late    .stat-tile-val { color: #f59e0b; }
.stat-tile.s-absent  .stat-tile-val { color: #ef4444; }
.stat-tile.s-rate    .stat-tile-val { color: #2563eb; }

/* RATE BAR */
.rate-card {
    background: #fff; border-radius: 16px;
    padding: 18px 22px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.07);
}
.rate-head { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px; }
.rate-head-label { font-size: 13px; font-weight: 700; color: #0d1b2a; }
.rate-head-pct   { font-size: 22px; font-weight: 800; color: #0d1b2a; font-family: 'JetBrains Mono', monospace; }
.rate-bar-track  { background: #e5e7eb; border-radius: 99px; height: 8px; overflow: hidden; margin-bottom: 10px; }
.rate-bar-fill   { height: 100%; border-radius: 99px; transition: width 0.7s cubic-bezier(0.4,0,0.2,1); }
.rate-bar-fill.high { background: linear-gradient(90deg, #22c55e, #16a34a); }
.rate-bar-fill.mid  { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
.rate-bar-fill.low  { background: linear-gradient(90deg, #f87171, #ef4444); }
.rate-subtext { font-size: 12px; color: #94a3b8; }

/* TABLE CARD */
.table-card {
    background: #fff; border-radius: 16px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.07);
    overflow: hidden;
}

.table-top {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 22px 0; flex-wrap: wrap; gap: 8px;
}
.table-top-title { font-size: 14px; font-weight: 700; color: #0d1b2a; }
.table-top-count { font-size: 12px; color: #94a3b8; background: #f1f5f9; padding: 4px 12px; border-radius: 99px; }

.data-table { width: 100%; border-collapse: collapse; margin-top: 14px; }
.data-table th {
    background: #f8fafc; padding: 10px 16px;
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.7px;
    color: #94a3b8; text-align: left;
}
.data-table td {
    padding: 13px 16px; font-size: 13px; color: #374151;
    border-bottom: 1px solid #f1f5f9; vertical-align: middle;
}
.data-table tr:last-child td { border-bottom: none; }
.data-table tbody tr:hover td { background: #fafbfc; }

.num-col { color: #cbd5e1 !important; font-family: 'JetBrains Mono', monospace; font-size: 12px !important; }
.time-col { font-family: 'JetBrains Mono', monospace; color: #64748b !important; font-size: 12px !important; }

.badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 700; }
.badge.present { background: #dcfce7; color: #16a34a; }
.badge.late    { background: #fef3c7; color: #d97706; }
.badge.absent  { background: #fee2e2; color: #dc2626; }

/* EMPTY */
.empty-state { padding: 56px 24px; text-align: center; }
.empty-state .ei  { font-size: 44px; margin-bottom: 12px; }
.empty-state p    { font-size: 13px; color: #94a3b8; margin: 0; }

/* ACTIVE FILTER CHIPS */
.filter-chips { display: flex; gap: 8px; flex-wrap: wrap; }
.f-chip {
    background: #f1f5f9; color: #374151;
    padding: 5px 12px; border-radius: 99px;
    font-size: 11px; font-weight: 600;
    display: flex; align-items: center; gap: 6px;
}
.f-chip span { color: #94a3b8; }
</style>

<div class="ah-wrap">

    <!-- Header -->
    <div class="ah-header">
        <div>
            <h1>📅 Attendance History</h1>
            <p>Complete attendance log for your child<?= $total_children !== 1 ? 'ren' : '' ?>. Filter by name, status, or date.</p>
        </div>
    </div>

    <!-- Active filters display -->
    <?php if ($selected_child || $selected_status || $selected_date): ?>
    <div class="filter-chips">
        <div class="f-chip"><span>Filters:</span></div>
        <?php if ($selected_child):
            $cname = '';
            foreach ($children as $c) { if ($c['id'] == $selected_child) { $cname = $c['fullname']; break; } }
        ?>
        <div class="f-chip">👤 <?= htmlspecialchars($cname) ?></div>
        <?php endif; ?>
        <?php if ($selected_status): ?>
        <div class="f-chip">
            <?= $selected_status === 'present' ? '✅' : ($selected_status === 'late' ? '⚠️' : '❌') ?>
            <?= ucfirst($selected_status) ?>
        </div>
        <?php endif; ?>
        <?php if ($selected_date): ?>
        <div class="f-chip">📅 <?= date('M d, Y', strtotime($selected_date)) ?></div>
        <?php endif; ?>
        <a href="?page=attendance" class="f-chip" style="color:#ef4444;background:#fee2e2;text-decoration:none;">✕ Clear all</a>
    </div>
    <?php endif; ?>

    <!-- Filter bar -->
    <div class="filter-card">
        <div class="filter-title">🔍 Filter Records</div>
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
            <button type="submit" class="btn-apply">Apply Filter</button>
            <a href="?page=attendance" class="btn-reset">Reset</a>
        </form>
    </div>

    <!-- Stats -->
    <div class="stat-strip">
        <div class="stat-tile s-total">
            <div class="stat-tile-label">Total Records</div>
            <div class="stat-tile-val"><?= $total ?></div>
        </div>
        <div class="stat-tile s-present">
            <div class="stat-tile-label">Present</div>
            <div class="stat-tile-val"><?= $counts['present'] ?></div>
        </div>
        <div class="stat-tile s-late">
            <div class="stat-tile-label">Late</div>
            <div class="stat-tile-val"><?= $counts['late'] ?></div>
        </div>
        <div class="stat-tile s-absent">
            <div class="stat-tile-label">Absent</div>
            <div class="stat-tile-val"><?= $counts['absent'] ?></div>
        </div>
        <div class="stat-tile s-rate">
            <div class="stat-tile-label">Attendance Rate</div>
            <div class="stat-tile-val"><?= $rate ?>%</div>
        </div>
    </div>

    <!-- Rate bar -->
    <div class="rate-card">
        <div class="rate-head">
            <div class="rate-head-label">Attendance Rate</div>
            <div class="rate-head-pct"><?= $rate ?>%</div>
        </div>
        <div class="rate-bar-track">
            <div class="rate-bar-fill <?= $barClass ?>" style="width:<?= $rate ?>%;"></div>
        </div>
        <div class="rate-subtext">
            <?= $attended ?> attended (present + late) out of <?= $total ?> total records
            <?php if ($selected_child || $selected_status || $selected_date): ?> · filtered view<?php endif; ?>
        </div>
    </div>

    <!-- Table -->
    <div class="table-card">
        <div class="table-top">
            <div class="table-top-title">📋 Attendance Records</div>
            <div class="table-top-count"><?= $total ?> record<?= $total !== 1 ? 's' : '' ?></div>
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
                <td class="num-col"><?= $i + 1 ?></td>
                <td><strong><?= htmlspecialchars($row['fullname']) ?></strong></td>
                <td>
                    <span class="badge <?= $row['status'] ?>">
                        <?= $row['status'] === 'present' ? '✅' : ($row['status'] === 'late' ? '⚠️' : '❌') ?>
                        <?= ucfirst($row['status']) ?>
                    </span>
                </td>
                <td><?= date('M d, Y', strtotime($row['scan_date'])) ?></td>
                <td class="time-col">
                    <?= ($row['status'] !== 'absent' && !empty($row['scan_time'])) ? date('h:i A', strtotime($row['scan_time'])) : '—' ?>
                </td>
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