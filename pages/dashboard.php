<?php
include 'includes/db.php';

$today = date('Y-m-d');

// ✅ Date filter — defaults to today
$selected_date = isset($_GET['dash_date']) && !empty($_GET['dash_date'])
    ? $conn->real_escape_string($_GET['dash_date'])
    : $today;

$is_today = ($selected_date === $today);

$total = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='student'")->fetch_assoc()['total'];

$presentToday = $conn->query("
    SELECT COUNT(DISTINCT student_id) AS total
    FROM attendance
    WHERE status IN ('present', 'on_time', 'late')
      AND scan_date = '$selected_date'
")->fetch_assoc()['total'];

$absentToday = $conn->query("
    SELECT COUNT(DISTINCT student_id) AS total
    FROM attendance
    WHERE status = 'absent'
      AND scan_date = '$selected_date'
")->fetch_assoc()['total'];

$notifications = $conn->query("
    SELECT COUNT(*) AS total FROM notifications
    WHERE DATE(created_at) = '$selected_date'
")->fetch_assoc()['total'];

$recent = $conn->query("
    SELECT users.fullname AS name, 
           attendance.status,
           attendance.scan_date,
           attendance.scan_time
    FROM attendance
    JOIN users ON users.id = attendance.student_id
    WHERE attendance.scan_date = '$selected_date'
    ORDER BY attendance.date_added DESC
    LIMIT 10
");

$weeklyData = ['present' => [], 'absent' => []];
$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $p = (int)$conn->query("
        SELECT COUNT(DISTINCT student_id) AS total FROM attendance
        WHERE status IN ('present', 'on_time', 'late')
          AND scan_date = '$d'
    ")->fetch_assoc()['total'];
    $dates[]                 = date('M d', strtotime($d));
    $weeklyData['present'][] = $p;
    $weeklyData['absent'][]  = max(0, (int)$total - $p);
}

function getInitials($name) {
    $parts = array_filter(explode(' ', trim($name)));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(mb_substr($part, 0, 1));
    }
    return $initials ?: '?';
}

function avatarColor($name) {
    $colors = [
        ['#dbeafe','#1d4ed8'],
        ['#dcfce7','#15803d'],
        ['#ede9fe','#6d28d9'],
        ['#fef3c7','#92400e'],
        ['#fee2e2','#b91c1c'],
        ['#e0f2fe','#0369a1'],
        ['#fce7f3','#9d174d'],
        ['#f0fdf4','#166534'],
    ];
    $idx = abs(crc32($name)) % count($colors);
    return $colors[$idx];
}
?>
<style>
.dash-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px,1fr));
    gap: 18px; margin-bottom: 28px;
}
.dash-card {
    background: #fff; border-radius: 14px; padding: 22px 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    display: flex; flex-direction: column; gap: 6px; transition: transform 0.2s;
}
.dash-card:hover { transform: translateY(-3px); }
.dash-card-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.7px; color:#94a3b8; }
.dash-card-value { font-family:'Sora',sans-serif; font-size:34px; font-weight:700; line-height:1; }
.dash-card-value.purple { color:#6366f1; }
.dash-card-value.green  { color:#16a34a; }
.dash-card-value.red    { color:#dc2626; }
.dash-card-value.amber  { color:#d97706; }

.dash-table-card {
    background:#fff; border-radius:14px; padding:22px 24px;
    box-shadow:0 2px 12px rgba(0,0,0,0.06); margin-bottom:24px;
}
.dash-table-card h3 { font-family:'Sora',sans-serif; font-size:15px; font-weight:700; color:#0f1923; margin-bottom:16px; }
.dash-table { width:100%; border-collapse:collapse; }
.dash-table th { background:#f8fafc; padding:11px 14px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.5px; color:#64748b; text-align:left; }
.dash-table td { padding:12px 14px; font-size:13.5px; color:#374151; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.dash-table tr:last-child td { border-bottom:none; }
.dash-table tr:hover td { background:#fafcff; }

.student-cell { display:flex; align-items:center; gap:10px; }
.avatar-circle {
    width:36px; height:36px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:700; flex-shrink:0;
    font-family:'Sora',sans-serif; letter-spacing:0.3px;
}
.student-cell-name { font-weight:600; font-size:13.5px; color:#1e293b; }

.sp { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
.sp-present { background:#dcfce7; color:#15803d; }
.sp-ontime  { background:#dcfce7; color:#15803d; }
.sp-late    { background:#fef3c7; color:#92400e; }
.sp-absent  { background:#fee2e2; color:#b91c1c; }

/* ✅ Date filter bar */
.dash-filter-bar {
    display: flex; align-items: center; gap: 10px;
    margin-bottom: 16px; flex-wrap: wrap;
}
.dash-filter-bar input[type="date"] {
    padding: 8px 12px; border: 1.5px solid #e2e8f0;
    border-radius: 9px; font-size: 13px; color: #0f1923;
    background: #fff; outline: none; transition: border 0.15s;
    font-family: 'DM Sans', sans-serif;
}
.dash-filter-bar input[type="date"]:focus { border-color: #16a34a; }
.dash-filter-btn {
    padding: 8px 18px; border-radius: 9px; border: none;
    font-size: 13px; font-weight: 600; cursor: pointer;
    background: #16a34a; color: #fff; transition: background 0.15s;
    font-family: 'DM Sans', sans-serif;
}
.dash-filter-btn:hover { background: #15803d; }
.dash-filter-reset {
    padding: 8px 14px; border-radius: 9px; font-size: 13px;
    font-weight: 600; text-decoration: none; background: #fff;
    color: #475569; border: 1.5px solid #e2e8f0; transition: background 0.15s;
    font-family: 'DM Sans', sans-serif;
}
.dash-filter-reset:hover { background: #f1f5f9; }
.dash-date-label {
    font-size: 13px; color: #64748b; font-weight: 500;
}
.dash-date-label strong { color: #0f1923; }

.dash-charts { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media(max-width:900px){ .dash-charts { grid-template-columns:1fr; } }
.chart-card { background:#fff; border-radius:14px; padding:22px 24px; box-shadow:0 2px 12px rgba(0,0,0,0.06); }
.chart-card h3 { font-family:'Sora',sans-serif; font-size:14px; font-weight:700; color:#0f1923; margin-bottom:16px; }
</style>

<div class="dash-cards">
    <div class="dash-card">
        <div class="dash-card-label">Total Students</div>
        <div class="dash-card-value purple"><?= $total ?></div>
    </div>
    <div class="dash-card">
        <div class="dash-card-label"><?= $is_today ? 'Present Today' : 'Present' ?></div>
        <div class="dash-card-value green"><?= $presentToday ?></div>
    </div>
    <div class="dash-card">
        <div class="dash-card-label"><?= $is_today ? 'Absent Today' : 'Absent' ?></div>
        <div class="dash-card-value red"><?= $absentToday ?></div>
    </div>
    <div class="dash-card">
        <div class="dash-card-label"><?= $is_today ? 'Notifications Sent' : 'Notifications' ?></div>
        <div class="dash-card-value amber"><?= $notifications ?></div>
    </div>
</div>

<div class="dash-table-card">
    <h3>📋 Attendance Records</h3>

    <!-- ✅ Date filter -->
    <form method="GET" class="dash-filter-bar">
        <input type="hidden" name="page" value="dashboard">
        <input type="date" name="dash_date" value="<?= htmlspecialchars($selected_date) ?>" max="<?= $today ?>">
        <button type="submit" class="dash-filter-btn">🔍 Filter</button>
        <?php if (!$is_today): ?>
            <a href="?page=dashboard" class="dash-filter-reset">✕ Back to Today</a>
        <?php endif; ?>
        <span class="dash-date-label">
            Showing: <strong><?= $is_today ? 'Today' : date('F j, Y', strtotime($selected_date)) ?></strong>
        </span>
    </form>

    <table class="dash-table">
        <thead>
            <tr><th>Name</th><th>Status</th><th>Date</th><th>Time</th></tr>
        </thead>
        <tbody>
        <?php if ($recent && $recent->num_rows > 0): ?>
            <?php while ($r = $recent->fetch_assoc()):
                $initials = getInitials($r['name']);
                [$bgColor, $textColor] = avatarColor($r['name']);
                $s = $r['status'];
            ?>
            <tr>
                <td>
                    <div class="student-cell">
                        <div class="avatar-circle" style="background:<?= $bgColor ?>;color:<?= $textColor ?>;">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                        <span class="student-cell-name"><?= htmlspecialchars($r['name']) ?></span>
                    </div>
                </td>
                <td>
                    <?php if ($s === 'present' || $s === 'on_time'): ?>
                        <span class="sp sp-present">✔ Present</span>
                    <?php elseif ($s === 'late'): ?>
                        <span class="sp sp-late">⏰ Late</span>
                    <?php else: ?>
                        <span class="sp sp-absent">✖ Absent</span>
                    <?php endif; ?>
                </td>
                <td style="color:#64748b;font-size:12px;"><?= date('M d, Y', strtotime($r['scan_date'])) ?></td>
                <td style="color:#64748b;font-size:12px;">
                    <?php
                    if ($s === 'absent' && $r['scan_time'] === '17:00:00') {
                        echo '5:00 PM';
                    } elseif ($r['scan_time'] === '00:00:00' && $s === 'absent') {
                        echo '—';
                    } else {
                        echo date('g:i A', strtotime($r['scan_time']));
                    }
                    ?>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:30px;font-style:italic;">
                No attendance records found for <?= $is_today ? 'today' : date('F j, Y', strtotime($selected_date)) ?>
            </td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="dash-charts">
    <div class="chart-card">
        <h3>📊 <?= $is_today ? 'Today' : date('M d', strtotime($selected_date)) ?> Attendance</h3>
        <canvas id="todayChart"></canvas>
    </div>
    <div class="chart-card">
        <h3>📈 Weekly Attendance Trend</h3>
        <canvas id="weeklyChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('todayChart'), {
    type: 'bar',
    data: {
        labels: ['Present / On Time', 'Absent'],
        datasets: [{ label: 'Attendance', data: [<?= (int)$presentToday ?>, <?= (int)$absentToday ?>],
            backgroundColor: ['rgba(34,197,94,0.7)','rgba(239,68,68,0.7)'],
            borderColor: ['#16a34a','#dc2626'], borderWidth:2, borderRadius:6 }]
    },
    options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}} }
});
new Chart(document.getElementById('weeklyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [
            { label:'Present', data:<?= json_encode($weeklyData['present']) ?>, borderColor:'#16a34a', backgroundColor:'rgba(34,197,94,0.1)', fill:true, tension:0.3, pointBackgroundColor:'#16a34a' },
            { label:'Absent',  data:<?= json_encode($weeklyData['absent'])  ?>, borderColor:'#dc2626', backgroundColor:'rgba(239,68,68,0.08)', fill:true, tension:0.3, pointBackgroundColor:'#dc2626' }
        ]
    },
    options: { responsive:true, plugins:{legend:{position:'top'}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}}} }
});
</script>