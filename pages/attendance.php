<?php
include 'includes/db.php';

$today = date('Y-m-d');

$totalStudents = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='student'")->fetch_assoc()['total'];

$presentToday = $conn->query("
    SELECT COUNT(DISTINCT student_id) AS total
    FROM attendance
    WHERE status IN ('present', 'on_time', 'late')
      AND scan_date = '$today'
")->fetch_assoc()['total'];

$absentToday = $conn->query("
    SELECT COUNT(*) AS total FROM users
    WHERE role = 'student'
      AND id NOT IN (
          SELECT DISTINCT student_id
          FROM attendance
          WHERE scan_date = '$today'
            AND status IN ('present', 'on_time', 'late')
      )
")->fetch_assoc()['total'];

$lateToday = $conn->query("
    SELECT COUNT(DISTINCT student_id) AS total
    FROM attendance
    WHERE status = 'late'
      AND scan_date = '$today'
")->fetch_assoc()['total'];

// Filters
$where_parts   = [];
$filter_date   = '';
$filter_status = '';
$filter_search = '';

if (!empty($_GET['filter_date'])) {
    $filter_date = $conn->real_escape_string($_GET['filter_date']);
    $where_parts[] = "attendance.scan_date = '$filter_date'";
}

if (!empty($_GET['filter_status'])) {
    $filter_status = $conn->real_escape_string($_GET['filter_status']);
    if ($filter_status === 'present') {
        $where_parts[] = "attendance.status IN ('present', 'on_time')";
    } elseif ($filter_status === 'late') {
        $where_parts[] = "attendance.status = 'late'";
    } elseif ($filter_status === 'absent') {
        $where_parts[] = "attendance.status = 'absent'";
    }
}

if (!empty($_GET['filter_search'])) {
    $filter_search = $conn->real_escape_string($_GET['filter_search']);
    $where_parts[] = "users.fullname LIKE '%$filter_search%'";
}

$where = count($where_parts) ? "WHERE " . implode(" AND ", $where_parts) : "";

$records = $conn->query("
    SELECT users.fullname, attendance.scan_date, attendance.scan_time, attendance.status
    FROM attendance
    JOIN users ON users.id = attendance.student_id
    $where
    ORDER BY attendance.scan_date DESC, attendance.scan_time DESC
");
?>
<style>
.att-page { font-family:'DM Sans',sans-serif; display:flex; flex-direction:column; gap:24px; }

.gate-banner {
    display:flex; align-items:center; justify-content:space-between;
    background:linear-gradient(135deg,#0f1923 0%,#1e3a2f 100%);
    border-radius:16px; padding:20px 28px; gap:16px;
    box-shadow:0 4px 20px rgba(0,0,0,0.15); flex-wrap:wrap;
}
.gate-banner-left { display:flex; align-items:center; gap:16px; }
.gate-banner-icon { width:52px;height:52px;background:rgba(34,197,94,0.15);border:1px solid rgba(34,197,94,0.3);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;flex-shrink:0; }
.gate-banner-text h3 { font-family:'Sora',sans-serif;font-size:16px;font-weight:700;color:#fff;margin-bottom:3px; }
.gate-banner-text p { font-size:13px;color:rgba(255,255,255,0.5);line-height:1.4; }
.gate-banner-text p strong { color:#22c55e; }
.btn-gate { display:inline-flex;align-items:center;gap:8px;background:#22c55e;color:#fff;padding:12px 22px;border-radius:10px;font-size:14px;font-weight:700;font-family:'DM Sans',sans-serif;text-decoration:none;white-space:nowrap;transition:background 0.15s,transform 0.1s;box-shadow:0 4px 14px rgba(34,197,94,0.4); }
.btn-gate:hover { background:#16a34a;transform:translateY(-1px); }

.att-card { background:#fff;border-radius:16px;box-shadow:0 2px 12px rgba(0,0,0,0.06);overflow:hidden; }
.att-card-header { padding:18px 24px;border-bottom:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px; }
.att-card-header h2 { font-family:'Sora',sans-serif;font-size:16px;font-weight:700;color:#0f1923; }
.att-card-body { padding:20px 24px; }

/* Summary cards — now 4 columns and clickable */
.summary-grid { display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:14px; }
.summary-card { border-radius:12px;padding:18px 16px;text-align:center;text-decoration:none;display:block;transition:transform 0.15s,box-shadow 0.15s; }
.summary-card:hover { transform:translateY(-3px);box-shadow:0 8px 24px rgba(0,0,0,0.1); }
.summary-card .s-label { font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;margin-bottom:8px; }
.summary-card .s-value { font-family:'Sora',sans-serif;font-size:30px;font-weight:700;line-height:1; }
.summary-card .s-sub   { font-size:11px;margin-top:6px;opacity:0.6; }
.summary-card.total   { background:#ede9fe; } .summary-card.total   .s-label { color:#6d28d9; } .summary-card.total   .s-value { color:#4c1d95; }
.summary-card.present { background:#dcfce7; } .summary-card.present .s-label { color:#15803d; } .summary-card.present .s-value { color:#14532d; }
.summary-card.late    { background:#fef3c7; } .summary-card.late    .s-label { color:#92400e; } .summary-card.late    .s-value { color:#78350f; }
.summary-card.absent  { background:#fee2e2; } .summary-card.absent  .s-label { color:#b91c1c; } .summary-card.absent  .s-value { color:#7f1d1d; }

.btn-export { display:inline-flex;align-items:center;gap:7px;background:#0f1923;color:#fff;padding:10px 18px;border-radius:9px;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;text-decoration:none;transition:background 0.15s,transform 0.1s; }
.btn-export:hover { background:#1e2d3d;transform:translateY(-1px); }

/* Filter bar */
.filter-bar { display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:14px;padding:14px 16px;background:#f8fafc;border-radius:12px;border:1px solid #f1f5f9; }
.filter-bar label { font-size:12px;font-weight:600;color:#64748b;white-space:nowrap; }
.filter-bar input[type="date"],
.filter-bar input[type="text"],
.filter-bar select {
    padding:8px 12px;border-radius:9px;border:1.5px solid #e2e8f0;font-size:13px;
    font-family:'DM Sans',sans-serif;color:#0f1923;background:#fff;outline:none;transition:border 0.15s;
}
.filter-bar input[type="text"] { min-width:170px; }
.filter-bar input:focus,.filter-bar select:focus { border-color:#16a34a; }
.filter-divider { width:1px;height:26px;background:#e2e8f0;flex-shrink:0; }
.btn-filter { padding:9px 18px;border-radius:9px;border:none;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;background:#16a34a;color:#fff;transition:background 0.15s; }
.btn-filter:hover { background:#15803d; }
.btn-reset { padding:9px 16px;border-radius:9px;font-size:13px;font-weight:600;font-family:'DM Sans',sans-serif;text-decoration:none;background:#fff;color:#475569;border:1.5px solid #e2e8f0;transition:background 0.15s; }
.btn-reset:hover { background:#f1f5f9; }

/* Quick-status tabs */
.status-tabs { display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px; }
.status-tab { display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:8px;border:1.5px solid #e2e8f0;font-size:12px;font-weight:600;font-family:'DM Sans',sans-serif;cursor:pointer;background:#fff;color:#64748b;text-decoration:none;transition:all 0.15s; }
.status-tab:hover { border-color:#94a3b8; }
.status-tab.act-all     { background:#0f1923;color:#fff;border-color:#0f1923; }
.status-tab.act-present { background:#dcfce7;color:#15803d;border-color:#86efac; }
.status-tab.act-late    { background:#fef3c7;color:#92400e;border-color:#fde68a; }
.status-tab.act-absent  { background:#fee2e2;color:#b91c1c;border-color:#fca5a5; }

/* Active filter pills */
.active-filters { display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px; }
.filter-label-sm { font-size:12px;color:#94a3b8;font-weight:500; }
.filter-pill { display:inline-flex;align-items:center;gap:6px;background:#0f1923;color:#fff;font-size:12px;font-weight:600;padding:4px 11px;border-radius:20px; }
.filter-pill a { color:rgba(255,255,255,0.55);text-decoration:none;font-size:13px;line-height:1; }
.filter-pill a:hover { color:#fff; }

.results-meta { font-size:12px;color:#94a3b8;margin-bottom:12px; }
.results-meta strong { color:#475569; }

.att-table { width:100%;border-collapse:collapse; }
.att-table th { background:#f8fafc;padding:12px 16px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.7px;color:#64748b;text-align:left; }
.att-table td { padding:12px 16px;font-size:13.5px;color:#374151;border-bottom:1px solid #f1f5f9; }
.att-table tbody tr:hover { background:#fafcff; }
.att-table tbody tr:last-child td { border-bottom:none; }

.status-badge { display:inline-flex;align-items:center;gap:4px;font-size:12px;font-weight:600;padding:3px 10px;border-radius:20px; }
.status-present,.status-ontime { background:#dcfce7;color:#15803d; }
.status-late    { background:#fef3c7;color:#92400e; }
.status-absent  { background:#fee2e2;color:#b91c1c; }

.empty-row td { text-align:center;color:#94a3b8;font-style:italic;padding:36px; }
.empty-icon   { font-size:32px;display:block;margin-bottom:8px; }
</style>

<div class="att-page">

    <!-- Gate Scanner Banner -->
    <div class="gate-banner">
        <div class="gate-banner-left">
            <div class="gate-banner-icon">📷</div>
            <div class="gate-banner-text">
                <h3>Gate QR Scanner</h3>
                <p>Open on the <strong>entrance tablet</strong> to scan students' QR codes and mark attendance automatically.</p>
            </div>
        </div>
        <a href="gate_scanner.php" target="_blank" class="btn-gate">🚪 Open Gate Scanner</a>
    </div>

    <!-- Today's Summary — cards link to filtered view -->
    <div class="att-card">
        <div class="att-card-header">
            <h2>Today's Summary</h2>
            <a href="export_attendance.php" class="btn-export">&#8595; Export PDF</a>
        </div>
        <div class="att-card-body">
            <div class="summary-grid">
                <a href="?page=attendance" class="summary-card total">
                    <div class="s-label">Total Students</div>
                    <div class="s-value"><?= $totalStudents ?></div>
                    <div class="s-sub">All enrolled</div>
                </a>
                <a href="?page=attendance&filter_date=<?= $today ?>&filter_status=present" class="summary-card present">
                    <div class="s-label">Present Today</div>
                    <div class="s-value"><?= $presentToday ?></div>
                    <div class="s-sub">On time · present</div>
                </a>
                <a href="?page=attendance&filter_date=<?= $today ?>&filter_status=late" class="summary-card late">
                    <div class="s-label">Late Today</div>
                    <div class="s-value"><?= $lateToday ?></div>
                    <div class="s-sub">Arrived late</div>
                </a>
                <a href="?page=attendance&filter_date=<?= $today ?>&filter_status=absent" class="summary-card absent">
                    <div class="s-label">Absent Today</div>
                    <div class="s-value"><?= $absentToday ?></div>
                    <div class="s-sub">No scan recorded</div>
                </a>
            </div>
        </div>
    </div>

    <!-- Attendance Records -->
    <div class="att-card">
        <div class="att-card-header"><h2>Attendance Records</h2></div>
        <div class="att-card-body">

            <!-- Filter bar -->
            <form method="GET" class="filter-bar">
                <input type="hidden" name="page" value="attendance">

                <label>📅 Date</label>
                <input type="date" name="filter_date" value="<?= htmlspecialchars($filter_date) ?>" onchange="this.form.submit()">

                <div class="filter-divider"></div>

                <label>🔍 Name</label>
                <input type="text" name="filter_search" placeholder="Search student…" value="<?= htmlspecialchars($filter_search) ?>">

                <div class="filter-divider"></div>

                <label>📋 Status</label>
                <select name="filter_status">
                    <option value="">All Statuses</option>
                    <option value="present" <?= $filter_status === 'present' ? 'selected' : '' ?>>✔ Present</option>
                    <option value="late"    <?= $filter_status === 'late'    ? 'selected' : '' ?>>⏰ Late</option>
                    <option value="absent"  <?= $filter_status === 'absent'  ? 'selected' : '' ?>>✖ Absent</option>
                </select>

                <button class="btn-filter" type="submit">Apply</button>
                <a href="admin_dashboard.php?page=attendance" class="btn-reset">Reset</a>
            </form>

            <!-- Quick status tabs -->
            <?php
            $base = "admin_dashboard.php?page=attendance"
                  . ($filter_date   ? "&filter_date=$filter_date"                         : "")
                  . ($filter_search ? "&filter_search=" . urlencode($filter_search) : "");
            ?>
            <div class="status-tabs">
                <a href="<?= $base ?>"                          class="status-tab <?= !$filter_status              ? 'act-all'     : '' ?>">🗂 All</a>
                <a href="<?= $base ?>&filter_status=present"    class="status-tab <?= $filter_status === 'present'  ? 'act-present' : '' ?>">✔ Present</a>
                <a href="<?= $base ?>&filter_status=late"       class="status-tab <?= $filter_status === 'late'     ? 'act-late'    : '' ?>">⏰ Late</a>
                <a href="<?= $base ?>&filter_status=absent"     class="status-tab <?= $filter_status === 'absent'   ? 'act-absent'  : '' ?>">✖ Absent</a>
            </div>

            <!-- Active filter pills -->
            <?php if ($filter_date || $filter_status || $filter_search): ?>
            <div class="active-filters">
                <span class="filter-label-sm">Active filters:</span>
                <?php if ($filter_date): ?>
                    <span class="filter-pill">📅 <?= date('M d, Y', strtotime($filter_date)) ?>
                        <a href="?page=attendance<?= $filter_status ? "&filter_status=$filter_status" : '' ?><?= $filter_search ? "&filter_search=" . urlencode($filter_search) : '' ?>">✕</a>
                    </span>
                <?php endif; ?>
                <?php if ($filter_status): ?>
                    <span class="filter-pill">📋 <?= ucfirst($filter_status) ?>
                        <a href="?page=attendance<?= $filter_date ? "&filter_date=$filter_date" : '' ?><?= $filter_search ? "&filter_search=" . urlencode($filter_search) : '' ?>">✕</a>
                    </span>
                <?php endif; ?>
                <?php if ($filter_search): ?>
                    <span class="filter-pill">🔍 "<?= htmlspecialchars($filter_search) ?>"
                        <a href="?page=attendance<?= $filter_date ? "&filter_date=$filter_date" : '' ?><?= $filter_status ? "&filter_status=$filter_status" : '' ?>">✕</a>
                    </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Result count -->
            <?php $count = $records ? $records->num_rows : 0; ?>
            <div class="results-meta">Showing <strong><?= $count ?></strong> record<?= $count !== 1 ? 's' : '' ?></div>

            <table class="att-table">
                <thead>
                    <tr><th>Name</th><th>Date</th><th>Time</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php if ($records && $records->num_rows > 0): ?>
                    <?php while ($r = $records->fetch_assoc()):
                        $s = $r['status'];
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['fullname']) ?></strong></td>
                        <td style="color:#64748b;"><?= date('M d, Y', strtotime($r['scan_date'])) ?></td>
                        <td style="color:#64748b;">
                            <?php
                            echo ($s === 'absent' && (empty($r['scan_time']) || $r['scan_time'] === '00:00:00'))
                                ? '—'
                                : date('g:i A', strtotime($r['scan_time']));
                            ?>
                        </td>
                        <td>
                            <?php if ($s === 'present' || $s === 'on_time'): ?>
                                <span class="status-badge status-present">✔ Present</span>
                            <?php elseif ($s === 'late'): ?>
                                <span class="status-badge status-late">⏰ Late</span>
                            <?php else: ?>
                                <span class="status-badge status-absent">✖ Absent</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr class="empty-row">
                        <td colspan="4">
                            <span class="empty-icon">🔍</span>
                            No records found<?= ($filter_date || $filter_status || $filter_search) ? ' for the selected filters' : '' ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>