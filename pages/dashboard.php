<?php
include 'includes/db.php';

$today = date('Y-m-d');

$total = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='student'")->fetch_assoc()['total'];

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

$notifications = $conn->query("SELECT COUNT(*) AS total FROM notifications")->fetch_assoc()['total'];

$recent = $conn->query("
    SELECT users.fullname AS name, attendance.status,
           attendance.scan_date, attendance.scan_time
    FROM attendance
    JOIN users ON users.id = attendance.student_id
    ORDER BY attendance.scan_date DESC, attendance.scan_time DESC
    LIMIT 5
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

// Helper: get initials from full name
function getInitials($name) {
    $parts = array_filter(explode(' ', trim($name)));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(mb_substr($part, 0, 1));
    }
    return $initials ?: '?';
}

// Helper: pick a consistent avatar color based on name
function avatarColor($name) {
    $colors = [
        ['#dbeafe','#1d4ed8'], // blue
        ['#dcfce7','#15803d'], // green
        ['#ede9fe','#6d28d9'], // purple
        ['#fef3c7','#92400e'], // amber
        ['#fee2e2','#b91c1c'], // red
        ['#e0f2fe','#0369a1'], // sky
        ['#fce7f3','#9d174d'], // pink
        ['#f0fdf4','#166534'], // emerald
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

/* Avatar + name cell */
.student-cell { display:flex; align-items:center; gap:10px; }
.avatar-circle {
    width:36px; height:36px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:700; flex-shrink:0;
    font-family:'Sora',sans-serif; letter-spacing:0.3px;
}
.student-cell-name { font-weight:600; font-size:13.5px; color:#1e293b; }

/* Status badges */
.sp { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
.sp-present { background:#dcfce7; color:#15803d; }
.sp-ontime  { background:#dcfce7; color:#15803d; }
.sp-late    { background:#fef3c7; color:#92400e; }
.sp-absent  { background:#fee2e2; color:#b91c1c; }

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
        <div class="dash-card-label">Present Today</div>
        <div class="dash-card-value green"><?= $presentToday ?></div>
    </div>
    <div class="dash-card">
        <div class="dash-card-label">Absent Today</div>
        <div class="dash-card-value red"><?= $absentToday ?></div>
    </div>
    <div class="dash-card">
        <div class="dash-card-label">Notifications Sent</div>
        <div class="dash-card-value amber"><?= $notifications ?></div>
    </div>
</div>

<div class="dash-table-card">
    <h3>📋 Recent Attendance</h3>
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
                    echo ($r['scan_time'] === '00:00:00' && $s === 'absent')
                        ? '—'
                        : date('g:i A', strtotime($r['scan_time']));
                    ?>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:30px;font-style:italic;">No attendance records found</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="dash-charts">
    <div class="chart-card">
        <h3>📊 Today Attendance</h3>
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
        datasets: [{ label: 'Today', data: [<?= (int)$presentToday ?>, <?= (int)$absentToday ?>],
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