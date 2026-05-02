<?php
include 'includes/db.php';

$parent_email = $_SESSION['email'] ?? '';

// Get children
$stmt = $conn->prepare("SELECT id, fullname FROM users WHERE parent_email = ?");
$stmt->bind_param("s", $parent_email);
$stmt->execute();
$childRes = $stmt->get_result();
$children_data = []; $child_ids = [];
while ($row = $childRes->fetch_assoc()) {
    $children_data[] = $row;
    $child_ids[] = $row['id'];
}
$total_children = count($children_data);
$ids = !empty($child_ids) ? implode(',', $child_ids) : '0';

// Today's stats using scan_date
$today = date('Y-m-d');
$present_today = 0; $late_today = 0; $absent_today = 0; $no_record = 0;
$child_statuses = [];

foreach ($children_data as $child) {
    $att = $conn->query("
        SELECT status FROM attendance
        WHERE student_id = '{$child['id']}' AND scan_date = '$today'
        LIMIT 1
    ");
    if ($att && $att->num_rows > 0) {
        $r = $att->fetch_assoc();
        $child_statuses[$child['id']] = $r['status'];
        if ($r['status'] === 'present') $present_today++;
        elseif ($r['status'] === 'late') $late_today++;
        else $absent_today++;
    } else {
        $child_statuses[$child['id']] = 'no-record';
        $no_record++;
    }
}

// All-time attendance stats
$allStats = $conn->query("
    SELECT status, COUNT(*) as cnt
    FROM attendance
    WHERE student_id IN ($ids)
    GROUP BY status
");
$allCounts = ['present' => 0, 'late' => 0, 'absent' => 0];
while ($s = $allStats->fetch_assoc()) {
    if (isset($allCounts[$s['status']])) $allCounts[$s['status']] = (int)$s['cnt'];
}
$allTotal = array_sum($allCounts);
$allRate  = $allTotal > 0 ? round((($allCounts['present'] + $allCounts['late']) / $allTotal) * 100) : 0;

// Recent 5 records
$recent = $conn->query("
    SELECT a.status, a.scan_date, a.scan_time, u.fullname
    FROM attendance a
    JOIN users u ON u.id = a.student_id
    WHERE a.student_id IN ($ids)
    ORDER BY a.scan_date DESC, a.scan_time DESC
    LIMIT 5
");
?>

<style>
.pd-page { display: flex; flex-direction: column; gap: 22px; }

.section-title {
    font-size: 20px; font-weight: 700; color: #0f1923;
    display: flex; align-items: center; gap: 8px; margin-bottom: 4px;
}
.section-sub { font-size: 13px; color: #64748b; }

/* TODAY BANNER */
.today-banner {
    background: #fff; border-radius: 14px;
    padding: 16px 22px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
}
.today-label { font-size: 13px; font-weight: 600; color: #64748b; }
.today-date  { font-size: 15px; font-weight: 700; color: #0f1923; margin-top: 2px; }

/* STAT CARDS */
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

/* CHILDREN CARDS */
.children-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 14px;
}

.child-card {
    background: #fff; border-radius: 14px;
    padding: 18px 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
    display: flex; align-items: center; gap: 14px;
}

.child-avatar {
    width: 44px; height: 44px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 800; color: #fff; flex-shrink: 0;
}

.child-avatar.present   { background: #16a34a; }
.child-avatar.late      { background: #f59e0b; }
.child-avatar.absent    { background: #ef4444; }
.child-avatar.no-record { background: #94a3b8; }

.child-name  { font-size: 14px; font-weight: 700; color: #0f1923; margin-bottom: 4px; }
.child-label { font-size: 12px; color: #64748b; }

.spill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 99px;
    font-size: 11px; font-weight: 700; margin-top: 5px;
}
.spill.present   { background: #dcfce7; color: #16a34a; }
.spill.late      { background: #fef3c7; color: #d97706; }
.spill.absent    { background: #fee2e2; color: #dc2626; }
.spill.no-record { background: #f1f5f9; color: #64748b; }

/* OVERALL RATE CARD */
.rate-card {
    background: #fff; border-radius: 14px;
    padding: 20px 22px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}
.rate-card-title { font-size: 14px; font-weight: 700; color: #0f1923; margin-bottom: 14px; }
.rate-bar-wrap { background: #e5e7eb; border-radius: 99px; height: 10px; overflow: hidden; margin-bottom: 10px; }
.rate-bar-fill { height: 100%; border-radius: 99px; transition: width 0.6s ease; }
.rate-bar-fill.high   { background: linear-gradient(90deg, #22c55e, #16a34a); }
.rate-bar-fill.mid    { background: linear-gradient(90deg, #fbbf24, #f59e0b); }
.rate-bar-fill.low    { background: linear-gradient(90deg, #f87171, #ef4444); }
.rate-dots { display: flex; gap: 16px; flex-wrap: wrap; }
.rate-dot  { font-size: 12px; color: #64748b; display: flex; align-items: center; gap: 5px; }
.dot-bullet { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

/* RECENT TABLE */
.table-card { background: #fff; border-radius: 14px; padding: 20px 22px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
.table-card-title { font-size: 14px; font-weight: 700; color: #0f1923; margin-bottom: 14px; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th { background: #f8fafc; padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; text-align: left; }
.data-table td { padding: 12px 14px; font-size: 13px; color: #374151; border-bottom: 1px solid #f1f5f9; }
.data-table tr:last-child td { border-bottom: none; }
.data-table tr:hover td { background: #f8fafc; }
.badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; }
.badge.present { background: #dcfce7; color: #16a34a; }
.badge.late    { background: #fef3c7; color: #d97706; }
.badge.absent  { background: #fee2e2; color: #dc2626; }
</style>

<div class="pd-page">

    <div>
        <div class="section-title">📊 Parent Dashboard</div>
        <div class="section-sub">Overview of your child's attendance and today's status.</div>
    </div>

    <!-- Today banner -->
    <div class="today-banner">
        <div>
            <div class="today-label">Today's Date</div>
            <div class="today-date"><?= date('l, F j, Y') ?></div>
        </div>
        <div style="font-size:13px;color:#64748b;">
            <?= $total_children ?> child<?= $total_children !== 1 ? 'ren' : '' ?> linked to your account
        </div>
    </div>

    <!-- Today's stat cards -->
    <div class="stat-row">
        <div class="stat-card slate">
            <div class="stat-label">Children</div>
            <div class="stat-val"><?= $total_children ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Present Today</div>
            <div class="stat-val"><?= $present_today ?></div>
        </div>
        <div class="stat-card amber">
            <div class="stat-label">Late Today</div>
            <div class="stat-val"><?= $late_today ?></div>
        </div>
        <div class="stat-card red">
            <div class="stat-label">Absent Today</div>
            <div class="stat-val"><?= $absent_today ?></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-label">Overall Rate</div>
            <div class="stat-val"><?= $allRate ?>%</div>
        </div>
    </div>

    <!-- Children today status -->
    <?php if (!empty($children_data)): ?>
    <div>
        <div style="font-size:14px;font-weight:700;color:#0f1923;margin-bottom:12px;">👨‍👩‍👧 Today's Status</div>
        <div class="children-grid">
            <?php foreach ($children_data as $child):
                $st = $child_statuses[$child['id']] ?? 'no-record';
                $icons = ['present' => '✅', 'late' => '⚠️', 'absent' => '❌', 'no-record' => '—'];
                $labels = ['present' => 'Present', 'late' => 'Late', 'absent' => 'Absent', 'no-record' => 'No scan yet'];
                $initial = strtoupper(substr($child['fullname'], 0, 1));
            ?>
            <div class="child-card">
                <div class="child-avatar <?= $st ?>"><?= $initial ?></div>
                <div>
                    <div class="child-name"><?= htmlspecialchars($child['fullname']) ?></div>
                    <div class="child-label">Student</div>
                    <span class="spill <?= $st ?>"><?= $icons[$st] ?> <?= $labels[$st] ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Overall attendance rate -->
    <div class="rate-card">
        <div class="rate-card-title">📈 Overall Attendance Rate (All Time)</div>
        <?php
            $barClass = $allRate >= 90 ? 'high' : ($allRate >= 75 ? 'mid' : 'low');
        ?>
        <div class="rate-bar-wrap">
            <div class="rate-bar-fill <?= $barClass ?>" style="width:<?= $allRate ?>%;"></div>
        </div>
        <div class="rate-dots">
            <span class="rate-dot"><span class="dot-bullet" style="background:#16a34a;"></span><?= $allCounts['present'] ?> Present</span>
            <span class="rate-dot"><span class="dot-bullet" style="background:#f59e0b;"></span><?= $allCounts['late'] ?> Late</span>
            <span class="rate-dot"><span class="dot-bullet" style="background:#ef4444;"></span><?= $allCounts['absent'] ?> Absent</span>
            <span class="rate-dot"><span class="dot-bullet" style="background:#94a3b8;"></span><?= $allTotal ?> Total</span>
        </div>
    </div>

    <!-- Recent records -->
    <div class="table-card">
        <div class="table-card-title">🕐 Recent Attendance</div>
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
            <?php if ($recent && $recent->num_rows > 0): ?>
                <?php while ($r = $recent->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($r['fullname']) ?></strong></td>
                    <td>
                        <span class="badge <?= $r['status'] ?>">
                            <?= $r['status'] === 'present' ? '✅' : ($r['status'] === 'late' ? '⚠️' : '❌') ?>
                            <?= ucfirst($r['status']) ?>
                        </span>
                    </td>
                    <td><?= date('M d, Y', strtotime($r['scan_date'])) ?></td>
                    <td><?= $r['status'] === 'absent' ? '—' : date('h:i A', strtotime($r['scan_time'])) ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:24px;">No records yet</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>