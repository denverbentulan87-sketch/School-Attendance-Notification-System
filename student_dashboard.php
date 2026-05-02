<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student'){
    header("Location: index.php");
    exit();
}

$name       = $_SESSION['name'];
$role       = $_SESSION['role'];
$page       = $_GET['page'] ?? 'dashboard';
$student_id = $_SESSION['user_id'];

/* ── Fetch student's own data ── */
$sq = $conn->prepare("SELECT fullname, email, parent_email, qr_code, qr_token FROM users WHERE id = ?");
$sq->bind_param("i", $student_id);
$sq->execute();
$me = $sq->get_result()->fetch_assoc();

$aq = $conn->prepare("SELECT COALESCE(SUM(status='present'),0) AS present, COALESCE(SUM(status='absent'),0) AS absent FROM attendance WHERE student_id = ?");
$aq->bind_param("i", $student_id);
$aq->execute();
$my_att = $aq->get_result()->fetch_assoc();

$has_qr = !empty($me['qr_code']) && !empty($me['qr_token']);

/* ── Avatar initials ── */
$words    = array_slice(explode(' ', $name), 0, 2);
$initials = strtoupper(implode('', array_map(fn($w) => $w[0], $words)));

function getInitials(string $n): string {
    $p = explode(' ', trim($n));
    $i = strtoupper(substr($p[0],0,1));
    if (count($p)>1) $i .= strtoupper(substr(end($p),0,1));
    return $i;
}
$ava_colors = ['#3b82f6','#16a34a','#be185d','#9333ea','#ea580c','#0891b2'];
$avatar_bg  = $ava_colors[$student_id % count($ava_colors)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard – EduTrack</title>

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

/* ── Card/table base (same as admin) ── */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px; margin-bottom: 30px;
}

.card {
    background: white; padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    transition: 0.3s;
}
.card:hover { transform: translateY(-5px); }

.table-container {
    background: white; padding: 20px;
    border-radius: 12px; margin-top: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

table { width: 100%; border-collapse: collapse; }
table th { background: #f1f5f9; padding: 12px; text-align: left; }
table td { padding: 12px; border-bottom: 1px solid #eee; }
table tr:hover { background: #f9fafb; }

.present { color: green; font-weight: 500; }
.absent  { color: red;   font-weight: 500; }

/* ══════════════════════════════
   PROFILE PAGE
══════════════════════════════ */
.profile-wrap {
    display: grid;
    grid-template-columns: 290px 1fr;
    gap: 22px; align-items: start;
}

.profile-card {
    background: white; border-radius: 14px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden;
}
.profile-card-top {
    padding: 30px 22px 22px; text-align: center;
    background: linear-gradient(160deg, #0f1923 0%, #1a2b3c 100%);
}
.profile-big-avatar {
    width: 76px; height: 76px; border-radius: 50%;
    margin: 0 auto 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 26px; font-weight: 700; color: #fff;
    border: 3px solid rgba(255,255,255,0.15);
}
.profile-fullname {
    font-family: 'Sora', sans-serif; font-size: 16px;
    font-weight: 700; color: #fff; margin-bottom: 6px;
}
.profile-role-pill {
    display: inline-flex; align-items: center; gap: 5px;
    background: rgba(34,197,94,0.18); color: #4ade80;
    font-size: 11px; font-weight: 600;
    padding: 3px 12px; border-radius: 20px;
    border: 1px solid rgba(34,197,94,0.3);
}

.cred-list { padding: 6px 0; }
.cred-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 12px 20px; border-bottom: 1px solid #f1f5f9;
}
.cred-item:last-child { border-bottom: none; }
.cred-icon {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 14px;
}
.ci-blue   { background: #dbeafe; }
.ci-green  { background: #dcfce7; }
.ci-purple { background: #ede9fe; }
.ci-amber  { background: #fef3c7; }
.cred-details { flex: 1; min-width: 0; }
.cred-label {
    font-size: 10px; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 3px;
}
.cred-value { font-size: 13px; font-weight: 500; color: #0f1923; word-break: break-all; }
.cred-masked { font-size: 13px; color: #94a3b8; letter-spacing: 3px; }

/* QR panel */
.qr-panel {
    background: white; border-radius: 14px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05); overflow: hidden;
}
.qr-panel-header {
    padding: 16px 20px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; justify-content: space-between;
}
.qr-panel-title {
    font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700;
    color: #0f1923; display: flex; align-items: center; gap: 8px;
}
.qr-active-pill { display:inline-flex;align-items:center;gap:5px;background:#dcfce7;color:#15803d;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px; }
.qr-no-pill     { display:inline-flex;align-items:center;gap:5px;background:#fff7ed;color:#c2410c;font-size:11px;font-weight:700;padding:4px 12px;border-radius:20px;border:1px dashed #fdba74; }
.qr-panel-body  { padding: 24px 20px; }
.qr-display-grid { display: grid; grid-template-columns: auto 1fr; gap: 22px; align-items: start; }
.qr-big-img {
    width: 148px; height: 148px; border-radius: 12px;
    border: 1.5px solid #e2e8f0; display: block; cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
}
.qr-big-img:hover { transform: scale(1.04); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
.qr-click-hint { font-size: 10px; color: #94a3b8; text-align: center; margin-top: 5px; }
.qr-info-title { font-family:'Sora',sans-serif; font-size:14px; font-weight:700; color:#0f1923; margin-bottom:6px; }
.qr-info-desc  { font-size: 12.5px; color: #64748b; line-height: 1.65; margin-bottom: 14px; }
.qr-token-box {
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 7px;
    padding: 8px 11px; font-family: monospace; font-size: 10.5px;
    color: #64748b; word-break: break-all; margin-bottom: 15px;
}
.qr-token-box strong { color: #374151; font-weight: 600; }
.qr-action-row { display: flex; gap: 8px; flex-wrap: wrap; }

.btn-enlarge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 600;
    background: #7c3aed; color: #fff; border: none; cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: background 0.15s, transform 0.1s; text-decoration: none;
}
.btn-enlarge:hover { background: #6d28d9; transform: translateY(-1px); }

.btn-download {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 14px; border-radius: 8px; font-size: 12px; font-weight: 600;
    background: #16a34a; color: #fff; border: none; cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    transition: background 0.15s, transform 0.1s; text-decoration: none;
}
.btn-download:hover { background: #15803d; transform: translateY(-1px); }

.no-qr-state { text-align: center; padding: 40px 20px; }
.no-qr-icon  { font-size: 46px; margin-bottom: 12px; }
.no-qr-title { font-family:'Sora',sans-serif; font-size:15px; font-weight:700; color:#f59e0b; margin-bottom:6px; }
.no-qr-desc  { font-size: 12.5px; color: #94a3b8; line-height: 1.6; }

/* ══════════════════════════════
   QR MODAL
══════════════════════════════ */
.stu-modal {
    display: none; position: fixed; z-index: 9999; inset: 0;
    background: rgba(10,20,40,0.58);
    justify-content: center; align-items: center;
    backdrop-filter: blur(4px);
}
.stu-modal-box {
    background: #fff; border-radius: 18px; width: 340px;
    box-shadow: 0 30px 80px rgba(0,0,0,0.22);
    animation: modalUp 0.22s cubic-bezier(.25,.8,.25,1); overflow: hidden;
}
@keyframes modalUp {
    from { transform: translateY(20px) scale(0.97); opacity: 0; }
    to   { transform: translateY(0) scale(1); opacity: 1; }
}
.stu-modal-header {
    padding: 18px 20px; border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: flex-start; justify-content: space-between;
    background: linear-gradient(135deg, #0f1923 0%, #1a2b3c 100%);
}
.stu-modal-title { font-family:'Sora',sans-serif; font-size:15px; font-weight:700; color:#fff; }
.stu-modal-sub   { font-size: 11px; color: #94a3b8; margin-top: 3px; }
.stu-modal-close {
    width: 28px; height: 28px; border-radius: 7px;
    background: rgba(255,255,255,0.1); border: none; cursor: pointer;
    font-size: 14px; color: #fff;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.15s; flex-shrink: 0;
}
.stu-modal-close:hover { background: rgba(239,68,68,0.35); }
.stu-modal-body {
    padding: 22px;
    display: flex; flex-direction: column; align-items: center; gap: 14px;
}
.stu-modal-qr { width: 200px; height: 200px; border-radius: 12px; border: 1.5px solid #e2e8f0; display: block; }
.stu-modal-hint { font-size: 12px; color: #64748b; text-align: center; line-height: 1.6; }
.stu-modal-actions { display: flex; gap: 8px; width: 100%; }
.btn-dl-qr {
    flex: 1; padding: 10px 14px; background: #16a34a; color: #fff;
    border: none; border-radius: 9px; font-size: 12.5px; font-weight: 600;
    font-family: 'DM Sans', sans-serif; cursor: pointer; text-align: center;
    text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 6px;
    transition: background 0.15s;
}
.btn-dl-qr:hover { background: #15803d; }
.btn-close-qr {
    flex: 1; padding: 10px 14px; background: #fff; color: #374151;
    border: 1.5px solid #e2e8f0; border-radius: 9px; font-size: 12.5px; font-weight: 600;
    font-family: 'DM Sans', sans-serif; cursor: pointer;
    transition: background 0.15s, color 0.15s;
}
.btn-close-qr:hover { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }

@media(max-width: 768px){
    .sidebar { display: none; }
    .main    { margin-left: 0; }
}
</style>
</head>
<body>

<!-- ══ SIDEBAR ══ -->
<div class="sidebar">

    <a class="sidebar-brand" href="student_dashboard.php">
        <div class="brand-icon">
            <svg viewBox="0 0 24 24">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
            </svg>
        </div>
        <div>
            <div class="brand-name">EduTrack</div>
            <div class="brand-tag">Student Portal</div>
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
            My Attendance
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
                <div class="user-name"><?= htmlspecialchars($name) ?></div>
                <div class="user-role">Student</div>
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
                <?= $page === 'profile' ? 'My Profile 👤' : 'Welcome, ' . htmlspecialchars($name) . ' 👋' ?>
            </div>
            <div class="welcome-sub">
                <?= $page === 'profile'
                    ? 'Your account credentials and QR attendance code.'
                    : 'View your attendance records and notifications.' ?>
            </div>
        </div>
        <div class="role"><?= ucfirst($role) ?></div>
    </div>

    <!-- ════ MY PROFILE ════ -->
    <?php if ($page === 'profile'): ?>
    <div class="profile-wrap">

        <!-- LEFT: credentials card -->
        <div class="profile-card">
            <div class="profile-card-top">
                <div class="profile-big-avatar" style="background:<?= $avatar_bg ?>;"><?= $initials ?></div>
                <div class="profile-fullname"><?= htmlspecialchars($me['fullname']) ?></div>
                <div style="margin-top:6px;"><span class="profile-role-pill">✔ Student</span></div>
            </div>
            <div class="cred-list">

                <div class="cred-item">
                    <div class="cred-icon ci-blue">👤</div>
                    <div class="cred-details">
                        <div class="cred-label">Full Name</div>
                        <div class="cred-value"><?= htmlspecialchars($me['fullname']) ?></div>
                    </div>
                </div>

                <div class="cred-item">
                    <div class="cred-icon ci-green">✉️</div>
                    <div class="cred-details">
                        <div class="cred-label">Student Email (Login)</div>
                        <div class="cred-value"><?= htmlspecialchars($me['email']) ?></div>
                    </div>
                </div>

                <div class="cred-item">
                    <div class="cred-icon ci-amber">📧</div>
                    <div class="cred-details">
                        <div class="cred-label">Parent Email</div>
                        <?php if (!empty($me['parent_email'])): ?>
                            <div class="cred-value"><?= htmlspecialchars($me['parent_email']) ?></div>
                        <?php else: ?>
                            <div class="cred-value" style="color:#f59e0b;font-style:italic;">Not set</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="cred-item">
                    <div class="cred-icon ci-purple">🔒</div>
                    <div class="cred-details">
                        <div class="cred-label">Password</div>
                        <div class="cred-masked">••••••••</div>
                    </div>
                </div>

                <?php
                    $total = $my_att['present'] + $my_att['absent'];
                    $rate  = $total > 0 ? round(($my_att['present'] / $total) * 100) : 0;
                ?>
                <div class="cred-item">
                    <div class="cred-icon ci-green">📊</div>
                    <div class="cred-details">
                        <div class="cred-label">Attendance</div>
                        <div class="cred-value">
                            <span style="color:#15803d;font-weight:600;"><?= $my_att['present'] ?> present</span>
                            &nbsp;/&nbsp;
                            <span style="color:#b91c1c;font-weight:600;"><?= $my_att['absent'] ?> absent</span>
                            &nbsp;—&nbsp;<strong><?= $rate ?>%</strong>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- RIGHT: QR panel -->
        <div class="qr-panel">
            <div class="qr-panel-header">
                <div class="qr-panel-title">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="3" width="5" height="5" rx="1"/><rect x="16" y="3" width="5" height="5" rx="1"/><rect x="3" y="16" width="5" height="5" rx="1"/><path d="M16 16h2v2h-2zM18 20v-2M20 18h-2"/></svg>
                    My QR Attendance Code
                </div>
                <?php if ($has_qr): ?>
                    <span class="qr-active-pill">✔ Active</span>
                <?php else: ?>
                    <span class="qr-no-pill">⚠ Not Assigned</span>
                <?php endif; ?>
            </div>

            <div class="qr-panel-body">
                <?php if ($has_qr): ?>
                <div class="qr-display-grid">
                    <div>
                        <img src="<?= htmlspecialchars($me['qr_code']) ?>"
                             class="qr-big-img" alt="My QR Code"
                             title="Click to enlarge"
                             onclick="openStuQrModal()">
                        <div class="qr-click-hint">click to enlarge</div>
                    </div>
                    <div>
                        <div class="qr-info-title">Your Attendance QR</div>
                        <div class="qr-info-desc">
                            Show this QR code to your teacher when attending class.
                            Each scan logs your attendance automatically.
                            Do not share this code with others.
                        </div>
                        <div class="qr-token-box">
                            <strong>Token:</strong> <?= htmlspecialchars($me['qr_token']) ?>
                        </div>
                        <div class="qr-action-row">
                            <button class="btn-enlarge" onclick="openStuQrModal()">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
                                Enlarge
                            </button>
                            <a href="<?= htmlspecialchars($me['qr_code']) ?>" target="_blank" class="btn-download">
                                ⬇ Download
                            </a>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="no-qr-state">
                    <div class="no-qr-icon">📲</div>
                    <div class="no-qr-title">No QR Code Assigned</div>
                    <div class="no-qr-desc">Your QR code hasn't been generated yet.<br>Please contact your administrator to request one.</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <?php elseif ($page == 'dashboard'): ?>
        <?php include 'student_pages/dashboard.php'; ?>
    <?php elseif($page == 'attendance'): ?>
        <?php include 'student_pages/attendance.php'; ?>
    <?php elseif($page == 'notifications'): ?>
        <?php include 'student_pages/notifications.php'; ?>
    <?php elseif($page == 'notif_count'): ?>
        <?php include 'student_pages/notif_count.php'; ?>
    <?php endif; ?>

</div>

<!-- QR ENLARGE MODAL -->
<?php if ($has_qr): ?>
<div id="stuQrModal" class="stu-modal" onclick="if(event.target===this)closeStuQrModal()">
    <div class="stu-modal-box">
        <div class="stu-modal-header">
            <div>
                <div class="stu-modal-title">My QR Code</div>
                <div class="stu-modal-sub"><?= htmlspecialchars($me['fullname']) ?> — <?= htmlspecialchars($me['email']) ?></div>
            </div>
            <button class="stu-modal-close" onclick="closeStuQrModal()">✕</button>
        </div>
        <div class="stu-modal-body">
            <img src="<?= htmlspecialchars($me['qr_code']) ?>" class="stu-modal-qr" alt="My QR Code">
            <div class="stu-modal-hint">
                Present this QR to your teacher to mark your attendance.<br>
                Keep it safe — do not share it with anyone.
            </div>
            <div class="stu-modal-actions">
                <a href="<?= htmlspecialchars($me['qr_code']) ?>" target="_blank" class="btn-dl-qr">⬇ Download</a>
                <button class="btn-close-qr" onclick="closeStuQrModal()">✕ Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function openStuQrModal()  { document.getElementById('stuQrModal').style.display = 'flex'; }
function closeStuQrModal() { document.getElementById('stuQrModal').style.display = 'none'; }
</script>
</body>
</html>