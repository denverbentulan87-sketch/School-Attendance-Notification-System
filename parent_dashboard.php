<?php
session_start();
include 'includes/db.php';

// Only parents allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: index.php");
    exit();
}

$parent_id    = $_SESSION['user_id'];
$parent_name  = $_SESSION['name'];
$page         = $_GET['page'] ?? 'dashboard';

/* ── Fetch parent's own email ── */
$pq = $conn->prepare("SELECT email FROM users WHERE id = ?");
$pq->bind_param("i", $parent_id);
$pq->execute();
$parent = $pq->get_result()->fetch_assoc();
$parent_email = $parent['email'];

/* ── Fetch linked child ── */
$cq = $conn->prepare("
    SELECT id, fullname, email, qr_code, qr_token 
    FROM users 
    WHERE role = 'student' AND parent_email = ?
");
$cq->bind_param("s", $parent_email);
$cq->execute();
$children = $cq->get_result()->fetch_all(MYSQLI_ASSOC);

/* ── Avatar helper ── */
function getInitials(string $n): string {
    $p = explode(' ', trim($n));
    $i = strtoupper(substr($p[0], 0, 1));
    if (count($p) > 1) $i .= strtoupper(substr(end($p), 0, 1));
    return $i;
}

/* ── Admin-style initials (same as admin_dashboard.php) ── */
$words    = array_slice(explode(' ', $parent_name), 0, 2);
$initials = strtoupper(implode('', array_map(fn($w) => $w[0], $words)));

$ava_colors = ['#3b82f6','#16a34a','#be185d','#9333ea','#ea580c','#0891b2'];
$avatar_bg  = $ava_colors[$parent_id % count($ava_colors)];

/* ── Selected child ── */
$selected_child_id = $_GET['child_id'] ?? ($children[0]['id'] ?? null);
$selected_child    = null;
foreach ($children as $c) {
    if ($c['id'] == $selected_child_id) { $selected_child = $c; break; }
}

/* ── Attendance data ── */
$att_records   = [];
$present_count = 0;
$absent_count  = 0;

if ($selected_child) {
    $cid = $selected_child['id'];
    $aq  = $conn->prepare("SELECT a.* FROM attendance a WHERE a.student_id = ? ORDER BY a.created_at DESC");
    $aq->bind_param("i", $cid);
    $aq->execute();
    $att_records = $aq->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($att_records as $r) {
        if ($r['status'] === 'present') $present_count++;
        else $absent_count++;
    }
}

$total       = $present_count + $absent_count;
$attend_rate = $total > 0 ? round(($present_count / $total) * 100) : 0;

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
<title>Parent Dashboard – EduTrack</title>

<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Sora:wght@600;700&display=swap" rel="stylesheet">

<style>
/* ── RESET ── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    display: flex;
    min-height: 100vh;
    background: #eef2f7;
    font-family: 'DM Sans', sans-serif;
}

/* ══════════════════════════════
   SIDEBAR — exact copy from admin_dashboard.php
══════════════════════════════ */
.sidebar {
    width: 245px;
    min-height: 100vh;
    background: #0f1923;
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0; bottom: 0;
    border-right: 1px solid #1e2d3d;
    z-index: 100;
}

.sidebar-brand {
    padding: 24px 20px 20px;
    border-bottom: 1px solid #1e2d3d;
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
}

.brand-icon {
    width: 38px; height: 38px;
    background: #16a34a;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

.brand-icon svg {
    width: 20px; height: 20px;
    stroke: #fff; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
}

.brand-name {
    font-family: 'Sora', sans-serif;
    font-size: 18px; font-weight: 700;
    color: #ffffff; letter-spacing: -0.3px; line-height: 1.1;
}

.brand-tag {
    font-size: 10px; font-weight: 600;
    color: #16a34a; letter-spacing: 1.3px;
    text-transform: uppercase; margin-top: 2px;
}

.sidebar-section-label {
    font-size: 10px; font-weight: 600;
    letter-spacing: 1.5px; text-transform: uppercase;
    color: #3d5166; padding: 20px 20px 8px;
}

.nav a {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 18px; margin: 2px 10px;
    border-radius: 10px; cursor: pointer;
    text-decoration: none; color: #7a93aa;
    font-size: 14px; font-weight: 500;
    position: relative;
    transition: background 0.15s, color 0.15s;
}

.nav a:hover { background: #1a2b3c; color: #c2d4e2; }
.nav a:hover .nav-icon svg { stroke: #c2d4e2; }

.nav a.active { background: #0d3321; color: #4ade80; }
.nav a.active .nav-icon svg { stroke: #4ade80; }

.nav a.active::before {
    content: '';
    position: absolute; left: -10px; top: 50%; transform: translateY(-50%);
    width: 3px; height: 20px;
    background: #16a34a; border-radius: 0 3px 3px 0;
}

.nav-icon {
    width: 20px; height: 20px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

.nav-icon svg {
    width: 17px; height: 17px;
    stroke: #7a93aa; fill: none;
    stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round;
    transition: stroke 0.15s;
}

.sidebar-divider { height: 1px; background: #1e2d3d; margin: 10px 20px; }

.sidebar-footer {
    margin-top: auto;
    padding: 16px 20px;
    border-top: 1px solid #1e2d3d;
}

.user-card { display: flex; align-items: center; gap: 10px; }

.user-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: #1a3a28; border: 2px solid #16a34a;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: #4ade80; flex-shrink: 0;
}

.user-info { flex: 1; min-width: 0; }

.user-name {
    font-size: 13px; font-weight: 600; color: #dde8f2;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}

.user-role { font-size: 11px; color: #3d5166; margin-top: 1px; }

.logout-link {
    width: 30px; height: 30px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 8px; background: #1e2d3d;
    text-decoration: none; flex-shrink: 0;
    transition: background 0.15s;
}

.logout-link:hover { background: #2a3f54; }

.logout-link svg {
    width: 14px; height: 14px;
    stroke: #7a93aa; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
}

/* ══════════════════════════════
   MAIN — exact copy from admin_dashboard.php
══════════════════════════════ */
.main {
    margin-left: 245px;
    flex: 1;
    padding: 32px;
    min-height: 100vh;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 28px;
}

.welcome {
    font-family: 'Sora', sans-serif;
    font-size: 22px; font-weight: 700;
    color: #0f1923;
}

.welcome-sub {
    font-size: 13px; color: #64748b; margin-top: 3px;
}

.role {
    background: #16a34a; color: white;
    padding: 7px 16px; border-radius: 20px;
    font-size: 13px; font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    box-shadow: 0 3px 10px rgba(22,163,74,0.3);
}

/* ── Shared card styles (same as admin) ── */
.card {
    background: white; padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    transition: 0.3s;
}
.card:hover { transform: translateY(-3px); }

.table-container {
    background: white; padding: 20px;
    border-radius: 12px; margin-top: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

table { width: 100%; border-collapse: collapse; }
table th { background: #f1f5f9; padding: 12px; text-align: left; font-size: 12px; color: #64748b; font-weight: 600; }
table td { padding: 12px; border-bottom: 1px solid #eee; font-size: 13px; }
table tr:hover { background: #f9fafb; }

/* ══════════════════════════════
   CHILD SELECTOR TABS
══════════════════════════════ */
.child-selector {
    display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;
}
.child-tab {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 18px; border-radius: 10px;
    border: 1.5px solid #1e2d3d; background: #1a2b3c;
    cursor: pointer; text-decoration: none;
    color: #7a93aa; font-size: 13px; font-weight: 500;
    transition: all 0.18s;
}
.child-tab:hover { border-color: #16a34a; color: #4ade80; }
.child-tab.active { background: #0d3321; border-color: #16a34a; color: #4ade80; }
.child-tab-avatar {
    width: 24px; height: 24px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 10px; font-weight: 700; color: #fff;
}

/* ══════════════════════════════
   STAT CARDS
══════════════════════════════ */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px; margin-bottom: 24px;
}
.stat-card {
    background: white; border-radius: 12px;
    padding: 20px 22px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    display: flex; align-items: center; gap: 16px;
    transition: 0.3s;
}
.stat-card:hover { transform: translateY(-3px); }
.stat-icon {
    width: 46px; height: 46px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.si-green  { background: #dcfce7; }
.si-red    { background: #fee2e2; }
.si-blue   { background: #dbeafe; }
.si-amber  { background: #fef3c7; }
.si-green svg  { stroke: #16a34a; }
.si-red svg    { stroke: #dc2626; }
.si-blue svg   { stroke: #2563eb; }
.si-amber svg  { stroke: #d97706; }
.stat-icon svg { width: 22px; height: 22px; fill: none; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
.stat-label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.stat-value { font-family: 'Sora', sans-serif; font-size: 28px; font-weight: 700; color: #0f1923; line-height: 1; }
.stat-sub   { font-size: 11px; color: #94a3b8; margin-top: 3px; }

/* ══════════════════════════════
   RATE BAR
══════════════════════════════ */
.rate-bar-wrap {
    background: white; border-radius: 12px;
    padding: 22px 24px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    margin-bottom: 24px;
}
.rate-bar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.rate-bar-title  { font-family: 'Sora', sans-serif; font-size: 15px; font-weight: 700; color: #0f1923; }
.rate-pct        { font-family: 'Sora', sans-serif; font-size: 22px; font-weight: 700; }
.rate-pct.good   { color: #16a34a; }
.rate-pct.warn   { color: #ea580c; }
.rate-pct.bad    { color: #dc2626; }
.bar-track  { height: 10px; background: #f1f5f9; border-radius: 99px; overflow: hidden; }
.bar-fill   { height: 100%; border-radius: 99px; transition: width 0.6s ease; }
.bar-fill.good { background: linear-gradient(90deg, #22c55e, #16a34a); }
.bar-fill.warn { background: linear-gradient(90deg, #fb923c, #ea580c); }
.bar-fill.bad  { background: linear-gradient(90deg, #f87171, #dc2626); }
.bar-legend     { display: flex; gap: 16px; margin-top: 10px; }
.bar-legend-item{ display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; }
.bar-dot        { width: 8px; height: 8px; border-radius: 50%; }

/* ══════════════════════════════
   TABLE CARD
══════════════════════════════ */
.table-card {
    background: white; border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    overflow: hidden; margin-bottom: 24px;
}
.table-card-header {
    padding: 16px 20px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
}
.table-card-title {
    font-family: 'Sora', sans-serif; font-size: 15px;
    font-weight: 700; color: #0f1923;
    display: flex; align-items: center; gap: 8px;
}
.table-wrap { overflow-x: auto; }
.status-pill {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 700;
}
.sp-present { background: #dcfce7; color: #15803d; }
.sp-absent  { background: #fee2e2; color: #b91c1c; }
.sp-late    { background: #fef3c7; color: #92400e; }
.empty-state { text-align: center; padding: 50px 20px; color: #94a3b8; }
.empty-icon  { font-size: 38px; margin-bottom: 10px; }

/* ══════════════════════════════
   CHILD PROFILE STRIP
══════════════════════════════ */
.child-profile-card {
    background: white; border-radius: 12px;
    padding: 20px 22px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    margin-bottom: 24px;
    display: flex; align-items: center; gap: 18px;
}
.child-big-avatar {
    width: 56px; height: 56px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px; font-weight: 700; color: #fff; flex-shrink: 0;
}
.child-profile-info h3 { font-family: 'Sora', sans-serif; font-size: 16px; font-weight: 700; color: #0f1923; }
.child-profile-info p  { font-size: 12px; color: #64748b; margin-top: 2px; }
.child-email-tag {
    display: inline-flex; align-items: center; gap: 4px;
    background: #f1f5f9; color: #475569;
    font-size: 11px; padding: 3px 10px; border-radius: 20px; margin-top: 6px;
}

/* ══════════════════════════════
   NO CHILD / EMPTY STATES
══════════════════════════════ */
.no-child-box {
    background: white; border-radius: 12px;
    padding: 60px 30px; text-align: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.no-child-icon  { font-size: 48px; margin-bottom: 14px; }
.no-child-title { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700; color: #16a34a; margin-bottom: 8px; }
.no-child-desc  { font-size: 13px; color: #64748b; line-height: 1.7; }

/* ══════════════════════════════
   NOTIFICATIONS
══════════════════════════════ */
.notif-list { display: flex; flex-direction: column; gap: 10px; }
.notif-item {
    background: white; border-radius: 10px;
    padding: 14px 18px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    border-left: 4px solid #1e2d3d;
    display: flex; gap: 14px; align-items: flex-start;
}
.notif-item.present { border-left-color: #22c55e; }
.notif-item.absent  { border-left-color: #f87171; }
.notif-icon  { font-size: 20px; flex-shrink: 0; margin-top: 2px; }
.notif-msg   { font-size: 13px; color: #0f1923; font-weight: 500; margin-bottom: 4px; }
.notif-time  { font-size: 11px; color: #64748b; }

/* ══════════════════════════════
   PROFILE PAGE
══════════════════════════════ */
.profile-top {
    background: white; border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    display: flex; align-items: center; gap: 20px;
    margin-bottom: 20px;
}
.profile-avatar-lg {
    width: 68px; height: 68px; border-radius: 50%;
    background: #1a3a28; border: 2px solid #16a34a;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; font-weight: 700; color: #4ade80; flex-shrink: 0;
}
.profile-name-lg { font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700; color: #0f1923; }
.profile-role-tag {
    display: inline-flex; align-items: center; gap: 5px;
    background: #dcfce7; color: #15803d;
    font-size: 11px; font-weight: 700;
    padding: 3px 12px; border-radius: 20px; margin-top: 6px;
}
.profile-email { font-size: 12px; color: #64748b; margin-top: 4px; }

@media(max-width: 768px){
    .sidebar { display: none; }
    .main    { margin-left: 0; }
}
</style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<div class="sidebar">

    <a class="sidebar-brand" href="parent_dashboard.php">
        <div class="brand-icon">
            <svg viewBox="0 0 24 24">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
            </svg>
        </div>
        <div>
            <div class="brand-name">EduTrack</div>
            <div class="brand-tag">Parent Portal</div>
        </div>
    </a>

    <div class="sidebar-section-label">Main Menu</div>

    <div class="nav">

        <a href="?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24">
                    <rect x="3" y="3" width="7" height="7" rx="1"/>
                    <rect x="14" y="3" width="7" height="7" rx="1"/>
                    <rect x="3" y="14" width="7" height="7" rx="1"/>
                    <rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
            </span>
            Dashboard
        </a>

        <a href="?page=attendance" class="<?= $page === 'attendance' ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M9 11l3 3L22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
            </span>
            Attendance History
        </a>

        <a href="?page=notifications" class="<?= $page === 'notifications' ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
            </span>
            Notifications
        </a>

    </div>

    <div class="sidebar-divider"></div>
    <div class="sidebar-section-label">Account</div>

    <div class="nav">
        <a href="?page=profile" class="<?= $page === 'profile' ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </span>
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
                <svg viewBox="0 0 24 24">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
            </a>
        </div>
    </div>

</div>

<!-- ══ MAIN CONTENT ══ -->
<div class="main">

    <div class="header">
        <div>
            <div class="welcome">
                <?php
                $page_titles = [
                    'dashboard'     => 'Parent Dashboard ',
                    'attendance'    => 'Attendance History 📅',
                    'notifications' => 'Notifications 🔔',
                    'profile'       => 'My Profile 👤',
                ];
                echo $page_titles[$page] ?? 'Parent Dashboard 👨‍👩‍👧';
                ?>
            </div>
            <div class="welcome-sub">
                <?php if ($selected_child): ?>
                    Viewing records for <strong><?= htmlspecialchars($selected_child['fullname']) ?></strong>
                <?php else: ?>
                    Monitor your child's attendance and notifications.
                <?php endif; ?>
            </div>
        </div>
        <div class="role">Parent</div>
    </div>

    <?php if (empty($children)): ?>

    <!-- NO LINKED CHILD -->
    <div class="no-child-box">
        <div class="no-child-icon">🔍</div>
        <div class="no-child-title">No Student Linked</div>
        <div class="no-child-desc">
            Your email (<strong><?= htmlspecialchars($parent_email) ?></strong>) is not set as a parent email for any student yet.<br><br>
            Please ask the school administrator to link your email to your child's account,<br>
            or ask your child to register with your email as their parent email.
        </div>
    </div>

    <?php else: ?>

    <!-- CHILD TABS -->
    <?php if (count($children) > 1): ?>
    <div class="child-selector">
        <?php foreach ($children as $c):
            $c_bg     = $ava_colors[$c['id'] % count($ava_colors)];
            $c_init   = getInitials($c['fullname']);
            $is_active = ($c['id'] == $selected_child_id);
        ?>
        <a href="?page=<?= $page ?>&child_id=<?= $c['id'] ?>"
           class="child-tab <?= $is_active ? 'active' : '' ?>">
            <div class="child-tab-avatar" style="background:<?= $c_bg ?>"><?= $c_init ?></div>
            <?= htmlspecialchars($c['fullname']) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($page === 'dashboard' || $page === 'attendance'): ?>

        <!-- CHILD INFO STRIP -->
        <?php if ($selected_child):
            $c_bg   = $ava_colors[$selected_child['id'] % count($ava_colors)];
            $c_init = getInitials($selected_child['fullname']);
        ?>
        <div class="child-profile-card">
            <div class="child-big-avatar" style="background:<?= $c_bg ?>"><?= $c_init ?></div>
            <div class="child-profile-info">
                <h3><?= htmlspecialchars($selected_child['fullname']) ?></h3>
                <p>Student Account</p>
                <span class="child-email-tag">✉ <?= htmlspecialchars($selected_child['email']) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <!-- STAT CARDS -->
        <div class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon si-blue">
                    <svg viewBox="0 0 24 24"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
                </div>
                <div>
                    <div class="stat-label">Total Classes</div>
                    <div class="stat-value"><?= $total ?></div>
                    <div class="stat-sub">All recorded</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon si-green">
                    <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <div>
                    <div class="stat-label">Present</div>
                    <div class="stat-value"><?= $present_count ?></div>
                    <div class="stat-sub">Days attended</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon si-red">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </div>
                <div>
                    <div class="stat-label">Absent</div>
                    <div class="stat-value"><?= $absent_count ?></div>
                    <div class="stat-sub">Days missed</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon si-amber">
                    <svg viewBox="0 0 24 24"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </div>
                <div>
                    <div class="stat-label">Rate</div>
                    <div class="stat-value"><?= $attend_rate ?>%</div>
                    <div class="stat-sub">Attendance rate</div>
                </div>
            </div>
        </div>

        <!-- RATE BAR -->
        <?php
            $bar_class  = $attend_rate >= 80 ? 'good' : ($attend_rate >= 60 ? 'warn' : 'bad');
            $rate_label = $attend_rate >= 80 ? '🟢 Good Standing' : ($attend_rate >= 60 ? '🟡 Needs Improvement' : '🔴 Critical — Please Contact School');
        ?>
        <div class="rate-bar-wrap">
            <div class="rate-bar-header">
                <div class="rate-bar-title">📊 Overall Attendance Rate — <?= $rate_label ?></div>
                <div class="rate-pct <?= $bar_class ?>"><?= $attend_rate ?>%</div>
            </div>
            <div class="bar-track">
                <div class="bar-fill <?= $bar_class ?>" style="width:<?= $attend_rate ?>%"></div>
            </div>
            <div class="bar-legend">
                <span class="bar-legend-item"><span class="bar-dot" style="background:#22c55e"></span><?= $present_count ?> Present</span>
                <span class="bar-legend-item"><span class="bar-dot" style="background:#f87171"></span><?= $absent_count ?> Absent</span>
                <span class="bar-legend-item"><span class="bar-dot" style="background:#e2e8f0"></span><?= $total ?> Total</span>
            </div>
        </div>

        <!-- ATTENDANCE TABLE -->
        <div class="table-card">
            <div class="table-card-header">
                <div class="table-card-title">📋 Attendance Records</div>
                <span style="font-size:12px;color:#94a3b8;"><?= count($att_records) ?> record<?= count($att_records) != 1 ? 's' : '' ?></span>
            </div>
            <div class="table-wrap">
                <?php if (empty($att_records)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📅</div>
                    <div>No attendance records found yet.</div>
                </div>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date &amp; Time</th>
                            <th>Status</th>
                            <th>Subject / Class</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($att_records as $i => $r): ?>
                        <tr>
                            <td style="color:#94a3b8;font-size:12px;"><?= $i + 1 ?></td>
                            <td>
                                <div style="font-weight:600;color:#0f1923;"><?= date('M j, Y', strtotime($r['created_at'])) ?></div>
                                <div style="font-size:11px;color:#94a3b8;"><?= date('g:i A', strtotime($r['created_at'])) ?></div>
                            </td>
                            <td>
                                <?php
                                $s   = strtolower($r['status']);
                                $cls = match($s) { 'present' => 'sp-present', 'absent' => 'sp-absent', 'late' => 'sp-late', default => '' };
                                $ico = match($s) { 'present' => '✅', 'absent' => '❌', 'late' => '⏰', default => '⭕' };
                                ?>
                                <span class="status-pill <?= $cls ?>"><?= $ico ?> <?= ucfirst($r['status']) ?></span>
                            </td>
                            <td><?= htmlspecialchars($r['subject'] ?? '—') ?></td>
                            <td style="font-size:12px;color:#94a3b8;font-style:italic;"><?= htmlspecialchars($r['notes'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($page === 'notifications'): ?>

        <?php if (empty($notifs)): ?>
        <div class="no-child-box">
            <div class="no-child-icon">🔔</div>
            <div class="no-child-title">No Notifications</div>
            <div class="no-child-desc">There are no notifications for your child yet.</div>
        </div>
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
                <div class="table-card-header">
                    <div class="table-card-title">🧒 Linked Children</div>
                </div>
                <div style="padding: 8px 0;">
                <?php foreach ($children as $c):
                    $cb   = $ava_colors[$c['id'] % count($ava_colors)];
                    $ci   = getInitials($c['fullname']);
                ?>
                <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid #f1f5f9;">
                    <div style="width:36px;height:36px;border-radius:50%;background:<?= $cb ?>;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;"><?= $ci ?></div>
                    <div>
                        <div style="font-size:13px;font-weight:600;color:#0f1923;"><?= htmlspecialchars($c['fullname']) ?></div>
                        <div style="font-size:11px;color:#94a3b8;"><?= htmlspecialchars($c['email']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <?php endif; // end has children ?>

</div>
</body>
</html>