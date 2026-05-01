<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: index.php");
    exit();
}

$name = $_SESSION['name'];
$role = $_SESSION['role'];
$page = $_GET['page'] ?? 'dashboard';

/* Admin initials for avatar */
$words    = array_slice(explode(' ', $name), 0, 2);
$initials = strtoupper(implode('', array_map(fn($w) => $w[0], $words)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - SANS</title>

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
   SIDEBAR
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

/* Brand */
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

/* Section label */
.sidebar-section-label {
    font-size: 10px; font-weight: 600;
    letter-spacing: 1.5px; text-transform: uppercase;
    color: #3d5166; padding: 20px 20px 8px;
}

/* Nav items */
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

/* Active left accent bar */
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

/* Footer */
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
   MAIN CONTENT
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
    font-size: 22px;
    font-weight: 700;
    color: #0f1923;
}

.welcome-sub {
    font-size: 13px;
    color: #64748b;
    margin-top: 3px;
}

.role {
    background: #16a34a;
    color: white;
    padding: 7px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    box-shadow: 0 3px 10px rgba(22,163,74,0.3);
}

/* ── Keep existing dashboard card/table styles ── */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    transition: 0.3s;
}

.card:hover { transform: translateY(-5px); }

.table-container {
    background: white;
    padding: 20px;
    border-radius: 12px;
    margin-top: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.table-header h2 { font-size: 20px; }

.search-box input {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

table { width: 100%; border-collapse: collapse; }

table th {
    background: #f1f5f9;
    padding: 12px;
    text-align: left;
}

table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

table tr:hover { background: #f9fafb; }

.btn {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
}

.btn-edit   { background: #3b82f6; color: white; }
.btn-delete { background: #ef4444; color: white; }
.btn-add {
    background: #10b981; color: white;
    border: none; cursor: pointer;
}

.present { color: green; font-weight: 500; }
.absent  { color: red;   font-weight: 500; }

@media(max-width: 768px){
    .sidebar { display: none; }
    .main    { margin-left: 0; }
}
</style>
</head>

<body>

<!-- ══ SIDEBAR ══ -->
<div class="sidebar">

    <a class="sidebar-brand" href="admin_dashboard.php">
        <div class="brand-icon">
            <svg viewBox="0 0 24 24">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
            </svg>
        </div>
        <div>
            <div class="brand-name">EduTrack</div>
            <div class="brand-tag">Admin Portal</div>
        </div>
    </a>

    <div class="sidebar-section-label">Main Menu</div>

    <div class="nav">

        <a href="admin_dashboard.php?page=dashboard"
           class="<?= $page === 'dashboard' ? 'active' : '' ?>">
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

        <a href="admin_dashboard.php?page=students"
           class="<?= $page === 'students' ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </span>
            Students
        </a>

        <a href="admin_dashboard.php?page=attendance"
           class="<?= $page === 'attendance' ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M9 11l3 3L22 4"/>
                    <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
            </span>
            Attendance
        </a>

        <a href="admin_dashboard.php?page=notifications"
           class="<?= $page === 'notifications' ? 'active' : '' ?>">
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
    <div class="sidebar-section-label">Analytics</div>

    <div class="nav">
        <a href="admin_dashboard.php?page=reports"
           class="<?= $page === 'reports' ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
            </span>
            Reports
        </a>
    </div>

    <!-- User card + logout -->
    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar"><?= htmlspecialchars($initials) ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($name) ?></div>
                <div class="user-role">Administrator</div>
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
            <div class="welcome">Welcome, <?= htmlspecialchars($name) ?> 👋</div>
            <div class="welcome-sub">Manage your students, track attendance, and send notifications.</div>
        </div>
        <div class="role"><?= ucfirst($role) ?></div>
    </div>

    <?php
    switch($page) {
        case 'students':
            include 'pages/students.php';
            break;
        case 'attendance':
            include 'pages/attendance.php';
            break;
        case 'notifications':
            include 'pages/notifications.php';
            break;
        case 'reports':
            include 'pages/reports.php';
            break;
        default:
            include 'pages/dashboard.php';
            break;
    }
    ?>

</div>

</body>
</html>