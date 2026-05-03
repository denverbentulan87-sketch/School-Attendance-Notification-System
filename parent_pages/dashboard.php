<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: index.php");
    exit();
}

$parent_id    = $_SESSION['user_id'];
$parent_name  = $_SESSION['name'];
$page         = $_GET['page'] ?? 'dashboard';

/* ── Parent email ── */
$pq = $conn->prepare("SELECT email FROM users WHERE id = ?");
$pq->bind_param("i", $parent_id);
$pq->execute();
$parent       = $pq->get_result()->fetch_assoc();
$parent_email = $parent['email'];

/* ── Linked children ── */
$cq = $conn->prepare("SELECT id, fullname, email, qr_code, qr_token FROM users WHERE role = 'student' AND parent_email = ?");
$cq->bind_param("s", $parent_email);
$cq->execute();
$children = $cq->get_result()->fetch_all(MYSQLI_ASSOC);

function getInitials(string $n): string {
    $p = explode(' ', trim($n));
    $i = strtoupper(substr($p[0], 0, 1));
    if (count($p) > 1) $i .= strtoupper(substr(end($p), 0, 1));
    return $i;
}

$words    = array_slice(explode(' ', $parent_name), 0, 2);
$initials = strtoupper(implode('', array_map(fn($w) => $w[0], $words)));

$ava_colors = ['#3b82f6','#16a34a','#be185d','#9333ea','#ea580c','#0891b2'];

/* ── Selected child ── */
$selected_child_id = $_GET['child_id'] ?? ($children[0]['id'] ?? null);
$selected_child    = null;
foreach ($children as $c) {
    if ($c['id'] == $selected_child_id) { $selected_child = $c; break; }
}

/* ── Attendance records ── */
$att_records   = [];
$present_count = 0;
$late_count    = 0;
$absent_count  = 0;

if ($selected_child) {
    $cid = $selected_child['id'];
    $aq  = $conn->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY scan_date DESC, scan_time DESC");
    $aq->bind_param("i", $cid);
    $aq->execute();
    $att_records = $aq->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($att_records as $r) {
        if ($r['status'] === 'present')     $present_count++;
        elseif ($r['status'] === 'late')    $late_count++;
        else                                 $absent_count++;
    }
}

$total       = $present_count + $late_count + $absent_count;
$attend_rate = $total > 0 ? round((($present_count + $late_count) / $total) * 100) : 0;
$bar_class   = $attend_rate >= 80 ? 'good' : ($attend_rate >= 60 ? 'warn' : 'bad');
$rate_label  = $attend_rate >= 80 ? '🟢 Good Standing' : ($attend_rate >= 60 ? '🟡 Needs Improvement' : '🔴 Critical');

/* ── Last 30-day trend (dashboard only) ── */
$trend_labels = $trend_present = $trend_late = $trend_absent = [];
if ($selected_child && $page === 'dashboard') {
    $today = date('Y-m-d');
    $cid   = $selected_child['id'];
    $tq    = $conn->query("SELECT scan_date, status FROM attendance WHERE student_id='$cid' AND scan_date >= DATE_SUB('$today', INTERVAL 29 DAY) ORDER BY scan_date ASC");
    $tmap  = [];
    while ($t = $tq->fetch_assoc()) $tmap[$t['scan_date']] = $t['status'];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $trend_labels[]  = date('M d', strtotime($d));
        $st = $tmap[$d] ?? null;
        $trend_present[] = $st === 'present' ? 1 : 0;
        $trend_late[]    = $st === 'late'    ? 1 : 0;
        $trend_absent[]  = $st === 'absent'  ? 1 : 0;
    }
}

/* ── Attendance history filters ── */
$filter_status = $_GET['status'] ?? '';
$filter_date   = $_GET['date']   ?? '';

/* ── Notifications ── */
$notifs = [];
if ($selected_child) {
    $cid = $selected_child['id'];
    $nq  = $conn->prepare("SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC LIMIT 10");
    $nq->bind_param("i", $cid);
    $nq->execute();
    $notifs = $nq->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Parent Portal – EduTrack</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { display: flex; min-height: 100vh; background: #eef2f7; font-family: 'DM Sans', sans-serif; }

/* ── SIDEBAR ── */
.sidebar { width: 245px; min-height: 100vh; background: #0f1923; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; border-right: 1px solid #1e2d3d; z-index: 100; }
.sidebar-brand { padding: 24px 20px 20px; border-bottom: 1px solid #1e2d3d; display: flex; align-items: center; gap: 12px; text-decoration: none; }
.brand-icon { width: 38px; height: 38px; background: #16a34a; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.brand-icon svg { width: 20px; height: 20px; stroke: #fff; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.brand-name { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700; color: #fff; letter-spacing: -0.3px; line-height: 1.1; }
.brand-tag  { font-size: 10px; font-weight: 600; color: #16a34a; letter-spacing: 1.3px; text-transform: uppercase; margin-top: 2px; }
.sidebar-section-label { font-size: 10px; font-weight: 600; letter-spacing: 1.5px; text-transform: uppercase; color: #3d5166; padding: 20px 20px 8px; }
.nav a { display: flex; align-items: center; gap: 12px; padding: 11px 18px; margin: 2px 10px; border-radius: 10px; text-decoration: none; color: #7a93aa; font-size: 14px; font-weight: 500; position: relative; transition: background 0.15s, color 0.15s; }
.nav a:hover { background: #1a2b3c; color: #c2d4e2; }
.nav a.active { background: #0d3321; color: #4ade80; }
.nav a.active .nav-icon svg { stroke: #4ade80; }
.nav a.active::before { content: ''; position: absolute; left: -10px; top: 50%; transform: translateY(-50%); width: 3px; height: 20px; background: #16a34a; border-radius: 0 3px 3px 0; }
.nav-icon { width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.nav-icon svg { width: 17px; height: 17px; stroke: #7a93aa; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; transition: stroke 0.15s; }
.sidebar-divider { height: 1px; background: #1e2d3d; margin: 10px 20px; }
.sidebar-footer { margin-top: auto; padding: 16px 20px; border-top: 1px solid #1e2d3d; }
.user-card { display: flex; align-items: center; gap: 10px; }
.user-avatar { width: 36px; height: 36px; border-radius: 50%; background: #1a3a28; border: 2px solid #16a34a; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; color: #4ade80; flex-shrink: 0; }
.user-info { flex: 1; min-width: 0; }
.user-name { font-size: 13px; font-weight: 600; color: #dde8f2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.user-role { font-size: 11px; color: #3d5166; margin-top: 1px; }
.logout-link { width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #1e2d3d; text-decoration: none; flex-shrink: 0; transition: background 0.15s; }
.logout-link:hover { background: #2a3f54; }
.logout-link svg { width: 14px; height: 14px; stroke: #7a93aa; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }

/* ── MAIN ── */
.main { margin-left: 245px; flex: 1; padding: 32px; min-height: 100vh; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
.welcome { font-family: 'Sora', sans-serif; font-size: 22px; font-weight: 700; color: #0f1923; }
.welcome-sub { font-size: 13px; color: #64748b; margin-top: 3px; }
.role-badge { background: #16a34a; color: white; padding: 7px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; }

/* ── CHILD TABS ── */
.child-selector { display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap; }
.child-tab { display: flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: 10px; border: 1.5px solid #1e2d3d; background: #1a2b3c; text-decoration: none; color: #7a93aa; font-size: 13px; font-weight: 500; transition: all 0.18s; }
.child-tab:hover { border-color: #16a34a; color: #4ade80; }
.child-tab.active { background: #0d3321; border-color: #16a34a; color: #4ade80; }
.child-tab-avatar { width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 700; color: #fff; }

/* ── CHILD PROFILE STRIP ── */
.child-profile-card { background: white; border-radius: 12px; padding: 18px 22px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 24px; display: flex; align-items: center; gap: 18px; }
.child-big-avatar { width: 52px; height: 52px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700; color: #fff; flex-shrink: 0; }
.child-profile-info h3 { font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: #0f1923; }
.child-profile-info p  { font-size: 12px; color: #64748b; margin-top: 2px; }
.child-email-tag { display: inline-flex; align-items: center; gap: 4px; background: #f1f5f9; color: #475569; font-size: 11px; padding: 3px 10px; border-radius: 20px; margin-top: 6px; }

/* ── STAT CARDS ── */
.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 18px; margin-bottom: 24px; }
.stat-card { background: white; border-radius: 12px; padding: 20px 22px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 16px; transition: 0.3s; }
.stat-card:hover { transform: translateY(-3px); }
.stat-icon { width: 46px; height: 46px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.si-green { background: #dcfce7; } .si-red { background: #fee2e2; } .si-blue { background: #dbeafe; } .si-amber { background: #fef3c7; }
.si-green svg { stroke: #16a34a; } .si-red svg { stroke: #dc2626; } .si-blue svg { stroke: #2563eb; } .si-amber svg { stroke: #d97706; }
.stat-icon svg { width: 22px; height: 22px; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.stat-label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.stat-value { font-family: 'Sora', sans-serif; font-size: 28px; font-weight: 700; color: #0f1923; line-height: 1; }
.stat-sub   { font-size: 11px; color: #94a3b8; margin-top: 3px; }

/* ── RATE BAR ── */
.rate-bar-wrap { background: white; border-radius: 12px; padding: 22px 24px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 24px; }
.rate-bar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.rate-bar-title { font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: #0f1923; }
.rate-pct { font-family: 'Sora', sans-serif; font-size: 22px; font-weight: 700; }
.rate-pct.good { color: #16a34a; } .rate-pct.warn { color: #ea580c; } .rate-pct.bad { color: #dc2626; }
.bar-track { height: 10px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
.bar-fill  { height: 100%; border-radius: 99px; transition: width 0.6s ease; }
.bar-fill.good { background: linear-gradient(90deg,#22c55e,#16a34a); }
.bar-fill.warn { background: linear-gradient(90deg,#fb923c,#ea580c); }
.bar-fill.bad  { background: linear-gradient(90deg,#f87171,#dc2626); }
.bar-legend { display: flex; gap: 16px; margin-top: 10px; flex-wrap: wrap; }
.bar-legend-item { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; }
.bar-dot { width: 8px; height: 8px; border-radius: 50%; }

/* ── CHARTS (dashboard) ── */
.charts-row { display: grid; grid-template-columns: 220px 1fr; gap: 20px; margin-bottom: 24px; }
@media(max-width:720px) { .charts-row { grid-template-columns: 1fr; } }
.chart-card { background: white; border-radius: 12px; padding: 20px 22px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
.chart-card-title { font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700; color: #0f1923; margin-bottom: 16px; }
.donut-wrap { position: relative; width: 160px; height: 160px; margin: 0 auto 16px; }
.donut-center { position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); text-align: center; pointer-events: none; }
.donut-pct { font-size: 26px; font-weight: 800; color: #0f1923; line-height: 1; font-family: 'Sora', sans-serif; }
.donut-sub { font-size: 10px; color: #94a3b8; font-weight: 600; text-transform: uppercase; }
.donut-legend { display: flex; flex-direction: column; gap: 8px; }
.legend-row { display: flex; align-items: center; gap: 8px; font-size: 12px; color: #374151; }
.legend-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.legend-cnt { margin-left: auto; font-weight: 700; color: #0f1923; }
.bar-chart-container { height: 190px; position: relative; }

/* ── TABLE ── */
.table-card { background: white; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 24px; }
.table-card-header { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
.table-card-title { font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: #0f1923; }
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
table th { background: #f1f5f9; padding: 12px 16px; text-align: left; font-size: 12px; color: #64748b; font-weight: 600; }
table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #374151; }
table tr:last-child td { border-bottom: none; }
table tbody tr:hover td { background: #f9fafb; }
.sp { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.sp-present { background: #dcfce7; color: #15803d; }
.sp-absent  { background: #fee2e2; color: #b91c1c; }
.sp-late    { background: #fef3c7; color: #92400e; }
.empty-state { text-align: center; padding: 50px 20px; color: #94a3b8; }
.empty-icon  { font-size: 38px; margin-bottom: 10px; }

/* ── FILTER (attendance) ── */
.filter-card { background: white; border-radius: 12px; padding: 18px 22px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
.filter-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 12px; }
.filter-row { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.filter-row select, .filter-row input[type="date"] { padding: 9px 13px; border: 1.5px solid #e5e7eb; border-radius: 10px; font-size: 13px; font-family: 'DM Sans', sans-serif; color: #374151; outline: none; transition: border-color 0.2s; background: #fff; }
.filter-row select:focus, .filter-row input:focus { border-color: #0f1923; }
.btn-apply { background: #0f1923; color: #fff; border: none; padding: 9px 22px; border-radius: 10px; font-size: 13px; font-weight: 600; font-family: 'DM Sans', sans-serif; cursor: pointer; }
.btn-apply:hover { background: #1e2d3d; }
.btn-reset { background: #f1f5f9; color: #64748b; border: none; padding: 9px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; font-family: 'DM Sans', sans-serif; cursor: pointer; text-decoration: none; display: inline-block; }
.btn-reset:hover { background: #e2e8f0; }

/* ── NOTIFICATIONS ── */
.notif-list { display: flex; flex-direction: column; gap: 10px; }
.notif-item { background: white; border-radius: 10px; padding: 14px 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); border-left: 4px solid #1e2d3d; display: flex; gap: 14px; align-items: flex-start; }
.notif-item.present { border-left-color: #22c55e; } .notif-item.absent { border-left-color: #f87171; }
.notif-icon { font-size: 20px; flex-shrink: 0; }
.notif-msg  { font-size: 13px; color: #0f1923; font-weight: 500; margin-bottom: 4px; }
.notif-time { font-size: 11px; color: #64748b; }

/* ── PROFILE ── */
.profile-top { background: white; border-radius: 12px; padding: 28px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); display: flex; align-items: center; gap: 20px; margin-bottom: 20px; }
.profile-avatar-lg { width: 68px; height: 68px; border-radius: 50%; background: #1a3a28; border: 2px solid #16a34a; display: flex; align-items: center; justify-content: center; font-size: 22px; font-weight: 700; color: #4ade80; flex-shrink: 0; }
.profile-name-lg { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700; color: #0f1923; }
.profile-role-tag { display: inline-flex; align-items: center; gap: 5px; background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 700; padding: 3px 12px; border-radius: 20px; margin-top: 6px; }
.profile-email { font-size: 12px; color: #64748b; margin-top: 4px; }

.no-child-box { background: white; border-radius: 12px; padding: 60px 30px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
.no-child-icon { font-size: 48px; margin-bottom: 14px; }
.no-child-title { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700; color: #16a34a; margin-bottom: 8px; }
.no-child-desc { font-size: 13px; color: #64748b; line-height: 1.7; }

@media(max-width:768px) { .sidebar { display: none; } .main { margin-left: 0; } }
</style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<div class="sidebar">
    <a class="sidebar-brand" href="parent_dashboard.php">
        <div class="brand-icon"><svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/></svg></div>
        <div><div class="brand-name">EduTrack</div><div class="brand-tag">Parent Portal</div></div>
    </a>

    <div class="sidebar-section-label">Main Menu</div>
    <div class="nav">
        <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg></span>
            Dashboard
        </a>
        <a href="?page=attendance" class="<?= $page === 'attendance' ? 'active' : '' ?>">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
            Attendance History
        </a>
        <a href="?page=notifications" class="<?= $page === 'notifications' ? 'active' : '' ?>">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg></span>
            Notifications
        </a>
    </div>

    <div class="sidebar-divider"></div>
    <div class="sidebar-section-label">Account</div>
    <div class="nav">
        <a href="?page=profile" class="<?= $page === 'profile' ? 'active' : '' ?>">
            <span class="nav-icon"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
            My Profile
        </a>
    </div>

    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($parent_name) ?></div>
                <div class="user-role">Parent / Guardian</div>
            </div>
            <a class="logout-link" href="logout.php" title="Logout">
                <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            </a>
        </div>
    </div>
</div>

<!-- ══ MAIN ══ -->
<div class="main">

    <div class="header">
        <div>
            <div class="welcome"><?php
                echo match($page) {
                    'dashboard'     => '📊 Dashboard',
                    'attendance'    => '📅 Attendance History',
                    'notifications' => '🔔 Notifications',
                    'profile'       => '👤 My Profile',
                    default         => '📊 Dashboard'
                };
            ?></div>
            <div class="welcome-sub">
                <?= $selected_child ? 'Viewing: <strong>'.htmlspecialchars($selected_child['fullname']).'</strong>' : 'Monitor your child\'s attendance and notifications.' ?>
            </div>
        </div>
        <div class="role-badge">Parent</div>
    </div>

    <?php if (empty($children)): ?>
    <div class="no-child-box">
        <div class="no-child-icon">🔍</div>
        <div class="no-child-title">No Student Linked</div>
        <div class="no-child-desc">Your email (<strong><?= htmlspecialchars($parent_email) ?></strong>) is not linked to any student yet.<br><br>Please ask the school administrator to link your account.</div>
    </div>

    <?php else: ?>

    <!-- Child switcher tabs -->
    <?php if (count($children) > 1): ?>
    <div class="child-selector">
        <?php foreach ($children as $c):
            $cb = $ava_colors[$c['id'] % count($ava_colors)];
        ?>
        <a href="?page=<?= $page ?>&child_id=<?= $c['id'] ?>" class="child-tab <?= $c['id'] == $selected_child_id ? 'active' : '' ?>">
            <div class="child-tab-avatar" style="background:<?= $cb ?>"><?= getInitials($c['fullname']) ?></div>
            <?= htmlspecialchars($c['fullname']) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Child profile strip -->
    <?php if ($selected_child):
        $c_bg = $ava_colors[$selected_child['id'] % count($ava_colors)];
    ?>
    <div class="child-profile-card">
        <div class="child-big-avatar" style="background:<?= $c_bg ?>"><?= getInitials($selected_child['fullname']) ?></div>
        <div class="child-profile-info">
            <h3><?= htmlspecialchars($selected_child['fullname']) ?></h3>
            <p>Student Account</p>
            <span class="child-email-tag">✉ <?= htmlspecialchars($selected_child['email']) ?></span>
        </div>
    </div>
    <?php endif; ?>


    <?php /* ═══════════════════════════════════════════
            PAGE: DASHBOARD
            Stat summary + donut chart + bar chart
            NO records table here
    ═══════════════════════════════════════════ */ ?>
    <?php if ($page === 'dashboard'): ?>

        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon si-blue"><svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg></div>
                <div><div class="stat-label">Total Records</div><div class="stat-value"><?= $total ?></div><div class="stat-sub">All time</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon si-green"><svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <div><div class="stat-label">Present</div><div class="stat-value"><?= $present_count ?></div><div class="stat-sub">Days attended</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon si-amber"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div>
                <div><div class="stat-label">Late</div><div class="stat-value"><?= $late_count ?></div><div class="stat-sub">Arrived late</div></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon si-red"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
                <div><div class="stat-label">Absent</div><div class="stat-value"><?= $absent_count ?></div><div class="stat-sub">Days missed</div></div>
            </div>
        </div>

        <div class="rate-bar-wrap">
            <div class="rate-bar-header">
                <div class="rate-bar-title">Overall Attendance Rate &mdash; <?= $rate_label ?></div>
                <div class="rate-pct <?= $bar_class ?>"><?= $attend_rate ?>%</div>
            </div>
            <div class="bar-track"><div class="bar-fill <?= $bar_class ?>" style="width:<?= $attend_rate ?>%"></div></div>
            <div class="bar-legend">
                <span class="bar-legend-item"><span class="bar-dot" style="background:#22c55e"></span><?= $present_count ?> Present</span>
                <span class="bar-legend-item"><span class="bar-dot" style="background:#f59e0b"></span><?= $late_count ?> Late</span>
                <span class="bar-legend-item"><span class="bar-dot" style="background:#f87171"></span><?= $absent_count ?> Absent</span>
                <span class="bar-legend-item"><span class="bar-dot" style="background:#e2e8f0"></span><?= $total ?> Total</span>
            </div>
        </div>

        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-card-title">Status Breakdown</div>
                <div class="donut-wrap">
                    <canvas id="donutChart"></canvas>
                    <div class="donut-center">
                        <div class="donut-pct"><?= $attend_rate ?>%</div>
                        <div class="donut-sub">Rate</div>
                    </div>
                </div>
                <div class="donut-legend">
                    <div class="legend-row"><span class="legend-dot" style="background:#16a34a"></span>Present<span class="legend-cnt"><?= $present_count ?></span></div>
                    <div class="legend-row"><span class="legend-dot" style="background:#f59e0b"></span>Late<span class="legend-cnt"><?= $late_count ?></span></div>
                    <div class="legend-row"><span class="legend-dot" style="background:#ef4444"></span>Absent<span class="legend-cnt"><?= $absent_count ?></span></div>
                </div>
            </div>
            <div class="chart-card">
                <div class="chart-card-title">Last 30 Days Trend</div>
                <div class="bar-chart-container">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
        </div>

        <script>
        Chart.defaults.font.family = "'DM Sans', sans-serif";
        new Chart(document.getElementById('donutChart'), {
            type: 'doughnut',
            data: {
                labels: ['Present','Late','Absent'],
                datasets: [{ data: [<?= $present_count ?>,<?= $late_count ?>,<?= $absent_count ?>], backgroundColor: ['#16a34a','#f59e0b','#ef4444'], borderWidth: 3, borderColor: '#fff', hoverOffset: 4 }]
            },
            options: { cutout: '72%', plugins: { legend: { display: false } }, animation: { duration: 800 } }
        });
        new Chart(document.getElementById('barChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($trend_labels) ?>,
                datasets: [
                    { label: 'Present', data: <?= json_encode($trend_present) ?>, backgroundColor: '#16a34a', borderRadius: 4, barPercentage: 0.7 },
                    { label: 'Late',    data: <?= json_encode($trend_late) ?>,    backgroundColor: '#f59e0b', borderRadius: 4, barPercentage: 0.7 },
                    { label: 'Absent',  data: <?= json_encode($trend_absent) ?>,  backgroundColor: '#ef4444', borderRadius: 4, barPercentage: 0.7 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', align: 'end', labels: { boxWidth: 10, font: { size: 11 }, color: '#64748b' } } },
                scales: {
                    x: { stacked: true, grid: { display: false }, ticks: { maxTicksLimit: 8, font: { size: 9 }, color: '#94a3b8', maxRotation: 0 } },
                    y: { stacked: true, display: false, max: 1 }
                }
            }
        });
        </script>


    <?php /* ═══════════════════════════════════════════
            PAGE: ATTENDANCE HISTORY
            Filter bar + stats + full records table
            NO charts here
    ═══════════════════════════════════════════ */ ?>
    <?php elseif ($page === 'attendance'): ?>

        <div class="filter-card">
            <div class="filter-label">🔍 Filter Records</div>
            <form method="GET" class="filter-row">
                <input type="hidden" name="page" value="attendance">
                <?php if ($selected_child_id): ?><input type="hidden" name="child_id" value="<?= $selected_child_id ?>"><?php endif; ?>
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="present" <?= $filter_status === 'present' ? 'selected' : '' ?>>✅ Present</option>
                    <option value="late"    <?= $filter_status === 'late'    ? 'selected' : '' ?>>⏰ Late</option>
                    <option value="absent"  <?= $filter_status === 'absent'  ? 'selected' : '' ?>>❌ Absent</option>
                </select>
                <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
                <button type="submit" class="btn-apply">Apply</button>
                <a href="?page=attendance<?= $selected_child_id ? '&child_id='.$selected_child_id : '' ?>" class="btn-reset">Reset</a>
            </form>
        </div>

        <?php
        $filtered = array_filter($att_records, function($r) use ($filter_status, $filter_date) {
            if ($filter_status && $r['status'] !== $filter_status) return false;
            $row_date = $r['scan_date'] ?? date('Y-m-d', strtotime($r['created_at']));
            if ($filter_date && $row_date !== $filter_date) return false;
            return true;
        });
        $filtered  = array_values($filtered);
        $f_total   = count($filtered);
        $f_present = count(array_filter($filtered, fn($r) => $r['status'] === 'present'));
        $f_late    = count(array_filter($filtered, fn($r) => $r['status'] === 'late'));
        $f_absent  = count(array_filter($filtered, fn($r) => $r['status'] === 'absent'));
        $f_rate    = $f_total > 0 ? round((($f_present + $f_late) / $f_total) * 100) : 0;
        $f_class   = $f_rate >= 80 ? 'good' : ($f_rate >= 60 ? 'warn' : 'bad');
        ?>

        <div class="stat-grid">
            <div class="stat-card"><div class="stat-icon si-blue"><svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg></div><div><div class="stat-label">Total</div><div class="stat-value"><?= $f_total ?></div><div class="stat-sub">Records shown</div></div></div>
            <div class="stat-card"><div class="stat-icon si-green"><svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div><div><div class="stat-label">Present</div><div class="stat-value"><?= $f_present ?></div></div></div>
            <div class="stat-card"><div class="stat-icon si-amber"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></div><div><div class="stat-label">Late</div><div class="stat-value"><?= $f_late ?></div></div></div>
            <div class="stat-card"><div class="stat-icon si-red"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div><div><div class="stat-label">Absent</div><div class="stat-value"><?= $f_absent ?></div></div></div>
        </div>

        <div class="rate-bar-wrap">
            <div class="rate-bar-header">
                <div class="rate-bar-title">Attendance Rate <?= ($filter_status || $filter_date) ? '<span style="font-size:12px;color:#94a3b8;font-weight:400;">— filtered view</span>' : '' ?></div>
                <div class="rate-pct <?= $f_class ?>"><?= $f_rate ?>%</div>
            </div>
            <div class="bar-track"><div class="bar-fill <?= $f_class ?>" style="width:<?= $f_rate ?>%"></div></div>
            <div class="bar-legend">
                <span class="bar-legend-item"><span class="bar-dot" style="background:#22c55e"></span><?= $f_present ?> Present</span>
                <span class="bar-legend-item"><span class="bar-dot" style="background:#f59e0b"></span><?= $f_late ?> Late</span>
                <span class="bar-legend-item"><span class="bar-dot" style="background:#f87171"></span><?= $f_absent ?> Absent</span>
            </div>
        </div>

        <div class="table-card">
            <div class="table-card-header">
                <div class="table-card-title">📋 Attendance Records</div>
                <span style="font-size:12px;color:#94a3b8;"><?= $f_total ?> record<?= $f_total !== 1 ? 's' : '' ?></span>
            </div>
            <div class="table-wrap">
                <?php if (empty($filtered)): ?>
                <div class="empty-state"><div class="empty-icon">📭</div><div>No records found<?= ($filter_status || $filter_date) ? ' for the selected filters' : '' ?>.</div></div>
                <?php else: ?>
                <table>
                    <thead><tr><th>#</th><th>Date</th><th>Time Scanned</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php foreach ($filtered as $i => $r):
                        $s        = strtolower($r['status']);
                        $cls      = match($s) { 'present' => 'sp-present', 'absent' => 'sp-absent', 'late' => 'sp-late', default => '' };
                        $ico      = match($s) { 'present' => '✅', 'absent' => '❌', 'late' => '⏰', default => '⭕' };
                        $row_date = $r['scan_date'] ?? date('Y-m-d', strtotime($r['created_at']));
                        $row_time = (!empty($r['scan_time']) && $s !== 'absent') ? date('g:i A', strtotime($r['scan_time'])) : '—';
                    ?>
                    <tr>
                        <td style="color:#94a3b8;font-size:12px;"><?= $i + 1 ?></td>
                        <td>
                            <strong><?= date('M j, Y', strtotime($row_date)) ?></strong>
                            <div style="font-size:11px;color:#94a3b8;"><?= date('l', strtotime($row_date)) ?></div>
                        </td>
                        <td style="font-size:12px;color:#64748b;"><?= $row_time ?></td>
                        <td><span class="sp <?= $cls ?>"><?= $ico ?> <?= ucfirst($r['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>


    <?php elseif ($page === 'notifications'): ?>

        <?php if (empty($notifs)): ?>
        <div class="no-child-box"><div class="no-child-icon">🔔</div><div class="no-child-title">No Notifications</div><div class="no-child-desc">There are no notifications for your child yet.</div></div>
        <?php else: ?>
        <div class="notif-list">
            <?php foreach ($notifs as $n):
                $cls  = strtolower($n['type'] ?? '');
                $icon = $cls === 'present' ? '✅' : ($cls === 'absent' ? '🚨' : '🔔');
            ?>
            <div class="notif-item <?= $cls ?>">
                <div class="notif-icon"><?= $icon ?></div>
                <div>
                    <div class="notif-msg"><?= htmlspecialchars($n['message']) ?></div>
                    <div class="notif-time">🕐 <?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>


    <?php elseif ($page === 'profile'): ?>

        <div style="max-width:500px;">
            <div class="profile-top">
                <div class="profile-avatar-lg"><?= htmlspecialchars($initials) ?></div>
                <div>
                    <div class="profile-name-lg"><?= htmlspecialchars($parent_name) ?></div>
                    <div class="profile-email">✉ <?= htmlspecialchars($parent_email) ?></div>
                    <span class="profile-role-tag">✔ Parent / Guardian</span>
                </div>
            </div>
            <div class="table-card">
                <div class="table-card-header"><div class="table-card-title">🧒 Linked Children</div></div>
                <div style="padding:8px 0;">
                <?php foreach ($children as $c):
                    $cb = $ava_colors[$c['id'] % count($ava_colors)];
                ?>
                <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid #f1f5f9;">
                    <div style="width:36px;height:36px;border-radius:50%;background:<?= $cb ?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;"><?= getInitials($c['fullname']) ?></div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#0f1923;"><?= htmlspecialchars($c['fullname']) ?></div>
                        <div style="font-size:11px;color:#94a3b8;"><?= htmlspecialchars($c['email']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>

    <?php endif; // end page switch ?>
    <?php endif; // end has children ?>

</div>
</body>
</html>