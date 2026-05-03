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
        $rs = strtolower(trim($r['status'] ?? ''));
        if ($rs === 'present')     $present_count++;
        elseif ($rs === 'late')    $late_count++;
        elseif ($rs === 'absent')  $absent_count++;
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
    while ($t = $tq->fetch_assoc()) $tmap[$t['scan_date']] = strtolower(trim($t['status'] ?? ''));
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
.pn-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 14px; margin-bottom: 20px; }
.pn-stat { background: #fff; border-radius: 14px; padding: 18px 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.06); position: relative; overflow: hidden; }
.pn-stat::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; border-radius: 14px 0 0 14px; }
.pn-stat.slate::before { background: #64748b; }
.pn-stat.green::before { background: #16a34a; }
.pn-stat.amber::before { background: #f59e0b; }
.pn-stat.red::before   { background: #ef4444; }
.pn-stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.7px; color: #94a3b8; margin-bottom: 6px; }
.pn-stat-val   { font-size: 30px; font-weight: 800; line-height: 1; color: #0f1923; }
.pn-stat.green .pn-stat-val { color: #16a34a; }
.pn-stat.amber .pn-stat-val { color: #f59e0b; }
.pn-stat.red   .pn-stat-val { color: #ef4444; }
.email-banner { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 14px 18px; display: flex; align-items: flex-start; gap: 12px; font-size: 13px; color: #15803d; margin-bottom: 16px; }
.email-banner .eb-icon { font-size: 20px; flex-shrink: 0; margin-top: 1px; }
.email-banner strong { color: #14532d; }
.email-banner p { margin: 4px 0 0; color: #16a34a; font-size: 12px; line-height: 1.5; }
.pn-filters { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 16px; }
.pn-filters a { padding: 7px 16px; border-radius: 99px; text-decoration: none; font-size: 13px; font-weight: 500; background: #f1f5f9; color: #64748b; border: 1px solid transparent; transition: all 0.15s; }
.pn-filters a:hover { background: #e2e8f0; }
.pn-filters a.active { background: #0f1923; color: #fff; border-color: #0f1923; }
.pn-feed { background: #fff; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
.feed-header { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
.feed-header-title { font-size: 14px; font-weight: 700; color: #0f1923; }
.feed-count { font-size: 12px; color: #94a3b8; font-weight: 500; }
.feed-item { display: flex; align-items: center; gap: 16px; padding: 16px 20px; border-bottom: 1px solid #f1f5f9; transition: background 0.15s; }
.feed-item:last-child { border-bottom: none; }
.feed-item:hover { background: #f8fafc; }
.feed-item.present { border-left: 4px solid #16a34a; }
.feed-item.late    { border-left: 4px solid #f59e0b; }
.feed-item.absent  { border-left: 4px solid #ef4444; }
.feed-item.unknown { border-left: 4px solid #94a3b8; }
.feed-avatar { width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 800; flex-shrink: 0; color: #fff; }
.feed-avatar.present { background: #16a34a; }
.feed-avatar.late    { background: #f59e0b; }
.feed-avatar.absent  { background: #ef4444; }
.feed-avatar.unknown { background: #94a3b8; }
.feed-content { flex: 1; min-width: 0; }
.feed-name { font-size: 13px; font-weight: 700; color: #0f1923; margin-bottom: 3px; }
.feed-msg  { font-size: 13px; color: #374151; margin-bottom: 5px; line-height: 1.4; }
.feed-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.spill { display: inline-flex; align-items: center; gap: 4px; padding: 2px 9px; border-radius: 99px; font-size: 11px; font-weight: 700; }
.spill.present { background: #dcfce7; color: #16a34a; }
.spill.late    { background: #fef3c7; color: #d97706; }
.spill.absent  { background: #fee2e2; color: #dc2626; }
.spill.unknown { background: #f1f5f9; color: #64748b; }
.feed-time { font-size: 11px; color: #94a3b8; }
.feed-dot  { color: #d1d5db; font-size: 10px; }
.notif-sent    { margin-left: auto; flex-shrink: 0; font-size: 11px; font-weight: 600; color: #16a34a; background: #dcfce7; padding: 4px 10px; border-radius: 99px; white-space: nowrap; }
.notif-sent.na { color: #94a3b8; background: #f1f5f9; }
.feed-empty    { padding: 60px 20px; text-align: center; }
.feed-empty .fe-icon { font-size: 48px; margin-bottom: 14px; }
.feed-empty h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
.feed-empty p  { font-size: 13px; color: #94a3b8; }

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
            $rs = strtolower(trim($r['status'] ?? ''));
            if ($filter_status && $rs !== $filter_status) return false;
            $row_date = $r['scan_date'] ?? date('Y-m-d', strtotime($r['created_at'] ?? 'now'));
            if ($filter_date && $row_date !== $filter_date) return false;
            return true;
        });
        $filtered  = array_values($filtered);
        $f_total   = count($filtered);
        $f_present = count(array_filter($filtered, fn($r) => strtolower(trim($r['status'] ?? '')) === 'present'));
        $f_late    = count(array_filter($filtered, fn($r) => strtolower(trim($r['status'] ?? '')) === 'late'));
        $f_absent  = count(array_filter($filtered, fn($r) => strtolower(trim($r['status'] ?? '')) === 'absent'));
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
                        $s        = strtolower(trim($r['status'] ?? ''));
                        $cls      = match($s) { 'present' => 'sp-present', 'absent' => 'sp-absent', 'late' => 'sp-late', default => '' };
                        $ico      = match($s) { 'present' => '✅', 'absent' => '❌', 'late' => '⏰', default => '⭕' };
                        $row_date = $r['scan_date'] ?? date('Y-m-d', strtotime($r['created_at'] ?? 'now'));
                        $row_time = (!empty($r['scan_time']) && $s !== 'absent') ? date('g:i A', strtotime($r['scan_time'])) : '—';
                    ?>
                    <tr>
                        <td style="color:#94a3b8;font-size:12px;"><?= $i + 1 ?></td>
                        <td>
                            <strong><?= date('M j, Y', strtotime($row_date)) ?></strong>
                            <div style="font-size:11px;color:#94a3b8;"><?= date('l', strtotime($row_date)) ?></div>
                        </td>
                        <td style="font-size:12px;color:#64748b;"><?= $row_time ?></td>
                        <td><span class="sp <?= $cls ?>"><?= $ico ?> <?= ucfirst($s) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>


    <?php /* ═══════════════════════════════════════════
            PAGE: NOTIFICATIONS
            Queries attendance table directly.
            Status is sanitized with strtolower+trim.
    ═══════════════════════════════════════════ */ ?>
    <?php elseif ($page === 'notifications'): ?>

        <?php
        $notif_filter = $_GET['filter'] ?? 'all';
        $child_ids    = $selected_child ? intval($selected_child['id']) : 0;

        // Build status filter clause using LOWER+TRIM at DB level
        $status_sql = '';
        if ($notif_filter === 'absent')  $status_sql = "AND LOWER(TRIM(a.status)) = 'absent'";
        if ($notif_filter === 'late')    $status_sql = "AND LOWER(TRIM(a.status)) = 'late'";
        if ($notif_filter === 'present') $status_sql = "AND LOWER(TRIM(a.status)) = 'present'";

        // Fetch records — normalize status in SQL so PHP always gets a clean value
        $records = $conn->query("
            SELECT a.student_id,
                   LOWER(TRIM(a.status)) AS status,
                   a.scan_date, a.scan_time, u.fullname
            FROM attendance a
            JOIN users u ON u.id = a.student_id
            WHERE a.student_id = $child_ids
              AND LOWER(TRIM(a.status)) IN ('present', 'late', 'absent')
              $status_sql
            ORDER BY a.scan_date DESC, a.scan_time DESC
            LIMIT 80
        ");

        // Stats — also normalized
        $statsRes = $conn->query("
            SELECT LOWER(TRIM(a.status)) AS status, COUNT(*) AS cnt
            FROM attendance a
            WHERE a.student_id = $child_ids
              AND LOWER(TRIM(a.status)) IN ('present', 'late', 'absent')
            GROUP BY LOWER(TRIM(a.status))
        ");
        $counts = ['present' => 0, 'late' => 0, 'absent' => 0];
        while ($row = $statsRes->fetch_assoc()) {
            if (isset($counts[$row['status']])) $counts[$row['status']] = (int)$row['cnt'];
        }
        $ntotal = array_sum($counts);
        ?>

        <!-- Stats -->
        <div class="pn-stats">
            <div class="pn-stat slate"><div class="pn-stat-label">Total Records</div><div class="pn-stat-val"><?= $ntotal ?></div></div>
            <div class="pn-stat green"><div class="pn-stat-label">Present</div><div class="pn-stat-val"><?= $counts['present'] ?></div></div>
            <div class="pn-stat amber"><div class="pn-stat-label">Late</div><div class="pn-stat-val"><?= $counts['late'] ?></div></div>
            <div class="pn-stat red"><div class="pn-stat-label">Absent</div><div class="pn-stat-val"><?= $counts['absent'] ?></div></div>
        </div>

        <!-- Email banner -->
        <div class="email-banner">
            <div class="eb-icon">📧</div>
            <div>
                <strong>You are receiving email alerts at <?= htmlspecialchars($parent_email) ?></strong>
                <p>✅ <strong>Present/Late</strong> — sent instantly when your child scans &nbsp;|&nbsp; ❌ <strong>Absent</strong> — sent at 9:01 AM if no scan is recorded.</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="pn-filters">
            <a href="?page=notifications<?= $selected_child_id ? '&child_id='.$selected_child_id : '' ?>&filter=all"     class="<?= $notif_filter==='all'     ?'active':'' ?>">All</a>
            <a href="?page=notifications<?= $selected_child_id ? '&child_id='.$selected_child_id : '' ?>&filter=present" class="<?= $notif_filter==='present' ?'active':'' ?>">✅ Present</a>
            <a href="?page=notifications<?= $selected_child_id ? '&child_id='.$selected_child_id : '' ?>&filter=late"    class="<?= $notif_filter==='late'    ?'active':'' ?>">⚠️ Late</a>
            <a href="?page=notifications<?= $selected_child_id ? '&child_id='.$selected_child_id : '' ?>&filter=absent"  class="<?= $notif_filter==='absent'  ?'active':'' ?>">❌ Absent</a>
        </div>

        <!-- Feed -->
        <div class="pn-feed">
            <div class="feed-header">
                <div class="feed-header-title">📋 Attendance Notification Log</div>
                <div class="feed-count">Latest 80 records</div>
            </div>

            <?php if ($records && $records->num_rows > 0): ?>
                <?php while ($n = $records->fetch_assoc()):
                    // status is already clean (LOWER+TRIM done in SQL)
                    $s = $n['status'];

                    $icons = ['present' => '✅', 'late' => '⚠️', 'absent' => '❌'];
                    $msgs  = [
                        'present' => '<strong>'.htmlspecialchars($n['fullname']).'</strong> arrived at school and was marked <strong>Present</strong>.',
                        'late'    => '<strong>'.htmlspecialchars($n['fullname']).'</strong> arrived <strong>late</strong>. A parent email alert was sent.',
                        'absent'  => '<strong>'.htmlspecialchars($n['fullname']).'</strong> was recorded as <strong>Absent</strong> today. A parent email alert was sent.',
                    ];

                    // Safe fallbacks (should never trigger now, but just in case)
                    $msgText  = $msgs[$s]  ?? '<strong>'.htmlspecialchars($n['fullname']).'</strong> has an attendance record.';
                    $iconText = $icons[$s] ?? '📋';

                    $timeStr = $s === 'absent'
                        ? date('F j, Y', strtotime($n['scan_date'] ?? 'now'))
                        : ((!empty($n['scan_time']) ? date('h:i A', strtotime($n['scan_time'])) . ' · ' : '') . date('F j, Y', strtotime($n['scan_date'] ?? 'now')));

                    $emailSent = in_array($s, ['late', 'absent']);
                    $initials  = strtoupper(substr($n['fullname'] ?? '?', 0, 1));
                ?>
                <div class="feed-item <?= htmlspecialchars($s) ?>">
                    <div class="feed-avatar <?= htmlspecialchars($s) ?>"><?= $initials ?></div>
                    <div class="feed-content">
                        <div class="feed-name"><?= htmlspecialchars($n['fullname']) ?></div>
                        <div class="feed-msg"><?= $msgText ?></div>
                        <div class="feed-meta">
                            <span class="spill <?= htmlspecialchars($s) ?>"><?= $iconText ?> <?= ucfirst($s) ?></span>
                            <span class="feed-dot">•</span>
                            <span class="feed-time"><?= $timeStr ?></span>
                        </div>
                    </div>
                    <?php if ($emailSent): ?>
                        <span class="notif-sent">📧 Email sent</span>
                    <?php else: ?>
                        <span class="notif-sent na">No email</span>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="feed-empty">
                    <div class="fe-icon">📭</div>
                    <h3>No notifications yet</h3>
                    <p>Attendance alerts will appear here once your child starts scanning their QR code at school.</p>
                </div>
            <?php endif; ?>
        </div>


    <?php /* ═══════════════════════════════════════════
            PAGE: PROFILE
    ═══════════════════════════════════════════ */ ?>
    <?php elseif ($page === 'profile'): ?>

    <style>
    /* ── PROFILE PAGE ── */
    .profile-grid { display: grid; grid-template-columns: 340px 1fr; gap: 22px; align-items: start; }
    @media(max-width:900px) { .profile-grid { grid-template-columns: 1fr; } }

    /* Parent card */
    .parent-card {
        background: #fff; border-radius: 16px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.07);
        overflow: visible;
        position: relative;
    }
    .parent-card-banner {
        height: 90px;
        background: linear-gradient(135deg, #0f1923 0%, #1a3a28 100%);
        display: flex; align-items: flex-end;
        padding: 0 24px 0;
        position: relative;
        border-radius: 16px 16px 0 0;
    }
    .parent-avatar-xl {
        width: 72px; height: 72px; border-radius: 50%;
        background: linear-gradient(135deg, #16a34a, #0f4d20);
        border: 4px solid #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; font-weight: 800; color: #fff;
        font-family: 'Sora', sans-serif;
        position: absolute;
        bottom: -36px;
        left: 24px;
        flex-shrink: 0;
    }
    .parent-card-body { padding: 48px 24px 24px; }
    .parent-name-xl {
        font-family: 'Sora', sans-serif;
        font-size: 17px; font-weight: 700; color: #0f1923;
        margin-bottom: 6px;
    }
    .parent-role-pill {
        display: inline-flex; align-items: center; gap: 5px;
        background: #dcfce7; color: #15803d;
        font-size: 11px; font-weight: 700;
        padding: 3px 12px; border-radius: 99px;
        margin-bottom: 18px;
    }
    .profile-info-rows { display: flex; flex-direction: column; gap: 10px; }
    .profile-info-row {
        display: flex; align-items: center; gap: 12px;
        padding: 10px 14px; background: #f8fafc;
        border-radius: 10px; border: 1px solid #f1f5f9;
    }
    .profile-info-icon {
        width: 34px; height: 34px; border-radius: 8px;
        background: #0f1923; display: flex; align-items: center;
        justify-content: center; flex-shrink: 0; font-size: 15px;
    }
    .profile-info-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #94a3b8; }
    .profile-info-value { font-size: 13px; font-weight: 600; color: #0f1923; margin-top: 1px; word-break: break-all; }

    /* Children cards */
    .children-section-title {
        font-family: 'Sora', sans-serif;
        font-size: 15px; font-weight: 700; color: #0f1923;
        margin-bottom: 14px;
        display: flex; align-items: center; gap: 8px;
    }
    .child-profile-card-full {
        background: #fff; border-radius: 16px;
        box-shadow: 0 4px 14px rgba(0,0,0,0.07);
        overflow: hidden; margin-bottom: 16px;
    }
    .child-card-header {
        padding: 18px 22px 16px;
        border-bottom: 1px solid #f1f5f9;
        display: flex; align-items: center; gap: 16px;
    }
    .child-avatar-lg {
        width: 52px; height: 52px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; font-weight: 800; color: #fff; flex-shrink: 0;
        font-family: 'Sora', sans-serif;
    }
    .child-card-name { font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: #0f1923; }
    .child-card-sub  { font-size: 12px; color: #64748b; margin-top: 2px; }
    .child-card-email {
        display: inline-flex; align-items: center; gap: 4px;
        background: #f1f5f9; color: #475569;
        font-size: 11px; padding: 3px 10px; border-radius: 20px; margin-top: 5px;
    }
    .child-card-body { padding: 20px 22px; display: grid; grid-template-columns: 1fr auto; gap: 20px; align-items: start; }
    .child-info-rows { display: flex; flex-direction: column; gap: 8px; }
    .child-info-row {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 12px; background: #f8fafc;
        border-radius: 9px; border: 1px solid #f1f5f9;
    }
    .child-info-icon { font-size: 14px; flex-shrink: 0; width: 22px; text-align: center; }
    .child-info-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.7px; color: #94a3b8; }
    .child-info-value { font-size: 12px; font-weight: 600; color: #0f1923; margin-top: 1px; }

    /* QR box */
    .qr-box {
        display: flex; flex-direction: column; align-items: center; gap: 10px;
        background: #f8fafc; border: 1px solid #e2e8f0;
        border-radius: 14px; padding: 16px;
        min-width: 150px;
    }
    .qr-box-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; color: #64748b; text-align: center; }
    .qr-box img { width: 120px; height: 120px; border-radius: 8px; border: 3px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
    .qr-no-img {
        width: 120px; height: 120px; border-radius: 8px;
        background: #e2e8f0; display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        gap: 6px; color: #94a3b8; font-size: 11px; font-weight: 600;
        text-align: center; padding: 12px;
    }
    .qr-no-img-icon { font-size: 28px; }
    .qr-token-chip {
        font-size: 10px; font-weight: 700; color: #475569;
        background: #e2e8f0; padding: 3px 10px; border-radius: 99px;
        font-family: monospace; letter-spacing: 0.5px;
        max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        text-align: center;
    }
    </style>

    <div class="profile-grid">

        <!-- LEFT: Parent Info Card -->
        <div>
            <div class="parent-card">
                <div class="parent-card-banner">
                    <div class="parent-avatar-xl"><?= htmlspecialchars($initials) ?></div>
                </div>
                <div class="parent-card-body">
                    <div class="parent-name-xl"><?= htmlspecialchars($parent_name) ?></div>
                    <div class="parent-role-pill">✔ Parent / Guardian</div>
                    <div class="profile-info-rows">
                        <div class="profile-info-row">
                            <div class="profile-info-icon">✉️</div>
                            <div>
                                <div class="profile-info-label">Email Address</div>
                                <div class="profile-info-value"><?= htmlspecialchars($parent_email) ?></div>
                            </div>
                        </div>
                        <div class="profile-info-row">
                            <div class="profile-info-icon">👨‍👩‍👧</div>
                            <div>
                                <div class="profile-info-label">Linked Children</div>
                                <div class="profile-info-value"><?= count($children) ?> student<?= count($children) !== 1 ? 's' : '' ?> linked</div>
                            </div>
                        </div>
                        <div class="profile-info-row">
                            <div class="profile-info-icon">🏫</div>
                            <div>
                                <div class="profile-info-label">Portal Access</div>
                                <div class="profile-info-value">EduTrack Parent Portal</div>
                            </div>
                        </div>
                        <div class="profile-info-row">
                            <div class="profile-info-icon">🔔</div>
                            <div>
                                <div class="profile-info-label">Email Alerts</div>
                                <div class="profile-info-value">Active — Late &amp; Absent</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Children Cards with QR -->
        <div>
            <div class="children-section-title"> Linked Children</div>
            <?php foreach ($children as $c):
                $cb = $ava_colors[$c['id'] % count($ava_colors)];
                $qr_path = !empty($c['qr_code']) ? $c['qr_code'] : null;

                // Attendance quick stats for each child
                $cid_stat = intval($c['id']);
                $sq = $conn->query("SELECT LOWER(TRIM(status)) as s, COUNT(*) as cnt FROM attendance WHERE student_id = $cid_stat AND LOWER(TRIM(status)) IN ('present','late','absent') GROUP BY LOWER(TRIM(status))");
                $cstats = ['present'=>0,'late'=>0,'absent'=>0];
                while ($sr = $sq->fetch_assoc()) $cstats[$sr['s']] = (int)$sr['cnt'];
                $ctotal = array_sum($cstats);
                $crate  = $ctotal > 0 ? round((($cstats['present']+$cstats['late'])/$ctotal)*100) : 0;
                $crate_color = $crate >= 80 ? '#16a34a' : ($crate >= 60 ? '#ea580c' : '#dc2626');
            ?>
            <div class="child-profile-card-full">
                <div class="child-card-header">
                    <div class="child-avatar-lg" style="background:<?= $cb ?>"><?= getInitials($c['fullname']) ?></div>
                    <div>
                        <div class="child-card-name"><?= htmlspecialchars($c['fullname']) ?></div>
                        <div class="child-card-sub">Student Account</div>
                        <span class="child-card-email">✉ <?= htmlspecialchars($c['email']) ?></span>
                    </div>
                </div>
                <div class="child-card-body">
                    <div class="child-info-rows">
                        <div class="child-info-row">
                            <div class="child-info-icon">✅</div>
                            <div>
                                <div class="child-info-label">Present</div>
                                <div class="child-info-value"><?= $cstats['present'] ?> day<?= $cstats['present'] !== 1 ? 's' : '' ?></div>
                            </div>
                        </div>
                        <div class="child-info-row">
                            <div class="child-info-icon">⚠️</div>
                            <div>
                                <div class="child-info-label">Late</div>
                                <div class="child-info-value"><?= $cstats['late'] ?> day<?= $cstats['late'] !== 1 ? 's' : '' ?></div>
                            </div>
                        </div>
                        <div class="child-info-row">
                            <div class="child-info-icon">❌</div>
                            <div>
                                <div class="child-info-label">Absent</div>
                                <div class="child-info-value"><?= $cstats['absent'] ?> day<?= $cstats['absent'] !== 1 ? 's' : '' ?></div>
                            </div>
                        </div>
                        <div class="child-info-row">
                            <div class="child-info-icon">📊</div>
                            <div>
                                <div class="child-info-label">Attendance Rate</div>
                                <div class="child-info-value" style="color:<?= $crate_color ?>"><?= $crate ?>% — <?= $crate >= 80 ? 'Good Standing' : ($crate >= 60 ? 'Needs Improvement' : 'Critical') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- QR Code -->
                    <div class="qr-box">
                        <div class="qr-box-label">Student QR Code</div>
                        <?php if ($qr_path && file_exists($qr_path)): ?>
                            <img src="<?= htmlspecialchars($qr_path) ?>" alt="QR Code for <?= htmlspecialchars($c['fullname']) ?>">
                        <?php elseif ($qr_path): ?>
                            <img src="<?= htmlspecialchars($qr_path) ?>" alt="QR Code" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                            <div class="qr-no-img" style="display:none"><div class="qr-no-img-icon">📷</div>QR not available</div>
                        <?php else: ?>
                            <div class="qr-no-img"><div class="qr-no-img-icon">📷</div>No QR assigned yet</div>
                        <?php endif; ?>
                        <?php if (!empty($c['qr_token'])): ?>
                            <div class="qr-token-chip" title="<?= htmlspecialchars($c['qr_token']) ?>">
                                <?= htmlspecialchars(substr($c['qr_token'], 0, 16)) ?>…
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <?php endif; // end page switch ?>
    <?php endif; // end has children ?>

</div>
</body>
</html>