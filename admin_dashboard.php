<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: index.php");
    exit();
}

/* ── Fetch fresh user data from DB ── */
include_once "includes/db.php";

/* ════════════════════════════════════════
   INLINE POST HANDLER — Update Profile
════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action']) && $_POST['_action'] === 'update_profile') {
    $uid       = (int) $_SESSION['user_id'];
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName  = trim($_POST['last_name']  ?? '');
    $newEmail  = trim($_POST['email']      ?? '');

    if (empty($firstName) || empty($newEmail)) {
        header("Location: admin_dashboard.php?page=profile&tab=edit&error=First+name+and+email+are+required");
        exit();
    }

    $fullname = $lastName ? "$firstName $lastName" : $firstName;

    /* Check email not taken by another user */
    $chk = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $chk->bind_param("si", $newEmail, $uid);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows > 0) {
        header("Location: admin_dashboard.php?page=profile&tab=edit&error=Email+already+in+use");
        exit();
    }
    $chk->close();

    $upd = $conn->prepare("UPDATE users SET fullname = ?, email = ? WHERE id = ?");
    $upd->bind_param("ssi", $fullname, $newEmail, $uid);
    if ($upd->execute()) {
        $_SESSION['name'] = $fullname;
        header("Location: admin_dashboard.php?page=profile&tab=edit&success=Profile+updated+successfully");
    } else {
        header("Location: admin_dashboard.php?page=profile&tab=edit&error=Update+failed.+Please+try+again");
    }
    $upd->close();
    exit();
}

/* ════════════════════════════════════════
   INLINE POST HANDLER — Change Password
════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action']) && $_POST['_action'] === 'update_password') {
    $uid             = (int) $_SESSION['user_id'];
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password']      ?? '';
    $confirmPassword = $_POST['confirm_password']  ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        header("Location: admin_dashboard.php?page=profile&tab=password&error=All+fields+are+required");
        exit();
    }
    if ($newPassword !== $confirmPassword) {
        header("Location: admin_dashboard.php?page=profile&tab=password&error=New+passwords+do+not+match");
        exit();
    }
    if (strlen($newPassword) < 8) {
        header("Location: admin_dashboard.php?page=profile&tab=password&error=Password+must+be+at+least+8+characters");
        exit();
    }

    /* Verify current password */
    $fetch = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $fetch->bind_param("i", $uid);
    $fetch->execute();
    $row = $fetch->get_result()->fetch_assoc();
    $fetch->close();

    if (!$row || !password_verify($currentPassword, $row['password'])) {
        header("Location: admin_dashboard.php?page=profile&tab=password&error=Current+password+is+incorrect");
        exit();
    }

    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $upd->bind_param("si", $hashed, $uid);
    if ($upd->execute()) {
        header("Location: admin_dashboard.php?page=profile&tab=password&success=Password+updated+successfully");
    } else {
        header("Location: admin_dashboard.php?page=profile&tab=password&error=Update+failed.+Please+try+again");
    }
    $upd->close();
    exit();
}
$stmt = $conn->prepare("SELECT id, fullname, email, role, date_added FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$adminUser = $stmt->get_result()->fetch_assoc();
$stmt->close();

$name       = $adminUser['fullname']   ?? ($_SESSION['name'] ?? 'Admin');
$email      = $adminUser['email']      ?? '';
$role       = $adminUser['role']       ?? 'admin';
$dateAdded  = $adminUser['date_added'] ?? '';
$userId     = $adminUser['id']         ?? $_SESSION['user_id'];

/* Split name into first / last for edit form */
$nameParts = explode(' ', trim($name), 2);
$firstName = $nameParts[0] ?? '';
$lastName  = $nameParts[1] ?? '';

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

/* ── Dashboard card/table styles ── */
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

/* ══════════════════════════════
   PROFILE PAGE STYLES
   (matches student my_profile tabs)
══════════════════════════════ */
.profile-wrapper {
    max-width: 860px;
    margin: 0 auto;
}

/* Hero card */
.profile-hero {
    background: linear-gradient(135deg, #0f1923 0%, #1a3a28 100%);
    border-radius: 16px;
    padding: 32px 36px;
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 24px;
    box-shadow: 0 8px 24px rgba(15,25,35,0.18);
}

.profile-hero-avatar {
    width: 80px; height: 80px;
    border-radius: 50%;
    background: #1a3a28;
    border: 3px solid #16a34a;
    display: flex; align-items: center; justify-content: center;
    font-size: 28px; font-weight: 700;
    color: #4ade80;
    flex-shrink: 0;
    box-shadow: 0 0 0 6px rgba(22,163,74,0.15);
}

.profile-hero-info { flex: 1; }

.profile-hero-name {
    font-family: 'Sora', sans-serif;
    font-size: 22px; font-weight: 700;
    color: #ffffff;
}

.profile-hero-role {
    display: inline-block;
    margin-top: 6px;
    background: rgba(22,163,74,0.2);
    color: #4ade80;
    font-size: 12px; font-weight: 600;
    letter-spacing: 1.2px; text-transform: uppercase;
    padding: 4px 12px; border-radius: 20px;
    border: 1px solid rgba(74,222,128,0.3);
}

.profile-hero-meta {
    display: flex; gap: 20px;
    margin-top: 14px;
    flex-wrap: wrap;
}

.profile-hero-meta span {
    font-size: 13px; color: #94a3b8;
    display: flex; align-items: center; gap: 6px;
}

.profile-hero-meta svg {
    width: 14px; height: 14px;
    stroke: #4ade80; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
}

/* Tabs */
.profile-tabs {
    display: flex;
    gap: 4px;
    background: #ffffff;
    border-radius: 12px;
    padding: 6px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.profile-tab {
    flex: 1;
    padding: 10px 16px;
    border-radius: 8px;
    border: none;
    background: transparent;
    color: #64748b;
    font-size: 13px; font-weight: 600;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 7px;
    transition: background 0.15s, color 0.15s;
    font-family: 'DM Sans', sans-serif;
    text-decoration: none;
}

.profile-tab svg {
    width: 15px; height: 15px;
    stroke: currentColor; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
}

.profile-tab:hover { background: #f1f5f9; color: #0f172a; }

.profile-tab.active {
    background: #16a34a;
    color: #ffffff;
    box-shadow: 0 3px 10px rgba(22,163,74,0.3);
}

/* Tab panels */
.profile-panel { display: none; }
.profile-panel.active { display: block; }

/* Info card */
.profile-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 24px 28px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 16px;
}

.profile-card-title {
    font-family: 'Sora', sans-serif;
    font-size: 15px; font-weight: 700;
    color: #0f1923;
    margin-bottom: 18px;
    display: flex; align-items: center; gap: 8px;
}

.profile-card-title svg {
    width: 17px; height: 17px;
    stroke: #16a34a; fill: none;
    stroke-width: 2; stroke-linecap: round; stroke-linejoin: round;
}

.profile-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
}

.profile-field label {
    display: block;
    font-size: 11px; font-weight: 600;
    letter-spacing: 0.8px; text-transform: uppercase;
    color: #94a3b8; margin-bottom: 5px;
}

.profile-field .field-value {
    font-size: 14px; font-weight: 500;
    color: #1e293b;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 14px;
}

/* Edit form */
.profile-form-group { margin-bottom: 16px; }
.profile-form-group label {
    display: block;
    font-size: 12px; font-weight: 600;
    letter-spacing: 0.6px; text-transform: uppercase;
    color: #64748b; margin-bottom: 6px;
}
.profile-form-group input,
.profile-form-group select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px; font-family: 'DM Sans', sans-serif;
    color: #1e293b; background: #f8fafc;
    transition: border 0.15s;
    outline: none;
}
.profile-form-group input:focus,
.profile-form-group select:focus {
    border-color: #16a34a;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(22,163,74,0.1);
}

.profile-save-btn {
    background: #16a34a;
    color: #fff;
    border: none;
    padding: 11px 28px;
    border-radius: 8px;
    font-size: 14px; font-weight: 600;
    cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    box-shadow: 0 3px 10px rgba(22,163,74,0.3);
    transition: background 0.15s, transform 0.1s;
}
.profile-save-btn:hover { background: #15803d; transform: translateY(-1px); }

/* Password strength */
.pw-strength-bar {
    height: 4px; border-radius: 4px;
    background: #e2e8f0; margin-top: 6px; overflow: hidden;
}
.pw-strength-fill {
    height: 100%; border-radius: 4px;
    width: 0%; transition: width 0.3s, background 0.3s;
}

/* Activity log */
.activity-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 14px 0;
    border-bottom: 1px solid #f1f5f9;
}
.activity-item:last-child { border-bottom: none; }
.activity-dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: #16a34a; flex-shrink: 0; margin-top: 4px;
    box-shadow: 0 0 0 3px rgba(22,163,74,0.15);
}
.activity-text { font-size: 13px; color: #374151; }
.activity-time { font-size: 11px; color: #94a3b8; margin-top: 2px; }

@media(max-width: 768px){
    .sidebar { display: none; }
    .main    { margin-left: 0; }
    .profile-grid { grid-template-columns: 1fr; }
    .profile-tabs { flex-wrap: wrap; }
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

        <!-- ── MY PROFILE (added below Reports) ── -->
        <a href="admin_dashboard.php?page=profile"
           class="<?= $page === 'profile' ? 'active' : '' ?>">
            <span class="nav-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
            </span>
            My Profile
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
        case 'profile':
            // ── INLINE PROFILE PAGE ──────────────────────────────
            $activeTab = $_GET['tab'] ?? 'info';
            ?>
            <div class="profile-wrapper">

                <?php if (!empty($_GET['success'])): ?>
                <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:12px 18px;border-radius:10px;margin-bottom:16px;font-size:14px;font-weight:500;">
                    ✅ <?= htmlspecialchars($_GET['success']) ?>
                </div>
                <?php elseif (!empty($_GET['error'])): ?>
                <div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 18px;border-radius:10px;margin-bottom:16px;font-size:14px;font-weight:500;">
                    ⚠️ <?= htmlspecialchars($_GET['error']) ?>
                </div>
                <?php endif; ?>

                <!-- Hero -->
                <div class="profile-hero">
                    <div class="profile-hero-avatar"><?= htmlspecialchars($initials) ?></div>
                    <div class="profile-hero-info">
                        <div class="profile-hero-name"><?= htmlspecialchars($name) ?></div>
                        <div class="profile-hero-role">Administrator</div>
                        <div class="profile-hero-meta">
                            <span>
                                <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                <?= htmlspecialchars($email) ?>
                            </span>
                            <span>
                                <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                Admin Portal
                            </span>
                            <?php if($dateAdded): ?>
                            <span>
                                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                Member since <?= date('M d, Y', strtotime($dateAdded)) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="profile-tabs">
                    <a href="?page=profile&tab=info"
                       class="profile-tab <?= $activeTab === 'info'     ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                        Personal Info
                    </a>
                    <a href="?page=profile&tab=edit"
                       class="profile-tab <?= $activeTab === 'edit'     ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit Profile
                    </a>
                    <a href="?page=profile&tab=password"
                       class="profile-tab <?= $activeTab === 'password' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Change Password
                    </a>
                    <a href="?page=profile&tab=activity"
                       class="profile-tab <?= $activeTab === 'activity' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        Activity Log
                    </a>
                </div>

                <!-- ── Tab: Personal Info ── -->
                <?php if($activeTab === 'info'): ?>
                <div class="profile-card">
                    <div class="profile-card-title">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                        Account Information
                    </div>
                    <div class="profile-grid">
                        <div class="profile-field">
                            <label>Full Name</label>
                            <div class="field-value"><?= htmlspecialchars($name) ?></div>
                        </div>
                        <div class="profile-field">
                            <label>Role</label>
                            <div class="field-value"><?= ucfirst(htmlspecialchars($role)) ?></div>
                        </div>
                        <div class="profile-field">
                            <label>Email Address</label>
                            <div class="field-value"><?= htmlspecialchars($email) ?></div>
                        </div>
                        <div class="profile-field">
                            <label>User ID</label>
                            <div class="field-value">#<?= htmlspecialchars($userId) ?></div>
                        </div>
                        <div class="profile-field">
                            <label>Date Registered</label>
                            <div class="field-value"><?= $dateAdded ? date('F d, Y', strtotime($dateAdded)) : '—' ?></div>
                        </div>
                        <div class="profile-field">
                            <label>Status</label>
                            <div class="field-value" style="color:#16a34a;font-weight:600;">● Active</div>
                        </div>
                    </div>
                </div>

                <!-- ── Tab: Edit Profile ── -->
                <?php elseif($activeTab === 'edit'): ?>
                <div class="profile-card">
                    <div class="profile-card-title">
                        <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Edit Profile
                    </div>
                    <form method="POST" action="admin_dashboard.php">
                        <input type="hidden" name="_action" value="update_profile">
                        <div class="profile-grid">
                            <div class="profile-form-group">
                                <label>First Name</label>
                                <input type="text" name="first_name"
                                       value="<?= htmlspecialchars($firstName) ?>" required>
                            </div>
                            <div class="profile-form-group">
                                <label>Last Name</label>
                                <input type="text" name="last_name"
                                       value="<?= htmlspecialchars($lastName) ?>" required>
                            </div>
                            <div class="profile-form-group">
                                <label>Email Address</label>
                                <input type="email" name="email"
                                       value="<?= htmlspecialchars($email) ?>" required>
                            </div>
                            <div class="profile-form-group">
                                <label>Role</label>
                                <input type="text" value="<?= ucfirst(htmlspecialchars($role)) ?>" disabled
                                       style="opacity:0.6;cursor:not-allowed;">
                            </div>
                        </div>
                        <button type="submit" class="profile-save-btn">Save Changes</button>
                    </form>
                </div>

                <!-- ── Tab: Change Password ── -->
                <?php elseif($activeTab === 'password'): ?>
                <div class="profile-card">
                    <div class="profile-card-title">
                        <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Change Password
                    </div>
                    <form method="POST" action="admin_dashboard.php" style="max-width:480px;">
                        <input type="hidden" name="_action" value="update_password">
                        <div class="profile-form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required placeholder="Enter current password">
                        </div>
                        <div class="profile-form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" id="newPw" required placeholder="Enter new password"
                                   oninput="updateStrength(this.value)">
                            <div class="pw-strength-bar">
                                <div class="pw-strength-fill" id="strengthFill"></div>
                            </div>
                        </div>
                        <div class="profile-form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required placeholder="Confirm new password">
                        </div>
                        <button type="submit" class="profile-save-btn">Update Password</button>
                    </form>
                </div>
                <script>
                function updateStrength(pw) {
                    var fill = document.getElementById('strengthFill');
                    var score = 0;
                    if(pw.length >= 8) score++;
                    if(/[A-Z]/.test(pw)) score++;
                    if(/[0-9]/.test(pw)) score++;
                    if(/[^A-Za-z0-9]/.test(pw)) score++;
                    var colors = ['#ef4444','#f97316','#eab308','#16a34a'];
                    var widths  = ['25%','50%','75%','100%'];
                    fill.style.width      = score ? widths[score-1]  : '0%';
                    fill.style.background = score ? colors[score-1] : 'transparent';
                }
                </script>

                <!-- ── Tab: Activity Log ── -->
                <?php elseif($activeTab === 'activity'): ?>
                <div class="profile-card">
                    <div class="profile-card-title">
                        <svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                        Recent Activity
                    </div>
                    <?php
                    /* Replace with real DB query when ready */
                    $activities = [
                        ['action' => 'Logged in to Admin Portal',        'time' => 'Just now'],
                        ['action' => 'Viewed Reports Dashboard',          'time' => '5 mins ago'],
                        ['action' => 'Updated attendance record',         'time' => '1 hour ago'],
                        ['action' => 'Sent absence notification',         'time' => '3 hours ago'],
                        ['action' => 'Added new student record',          'time' => 'Yesterday, 2:30 PM'],
                        ['action' => 'Exported attendance report (PDF)',  'time' => 'Yesterday, 10:00 AM'],
                    ];
                    foreach($activities as $act): ?>
                    <div class="activity-item">
                        <div class="activity-dot"></div>
                        <div>
                            <div class="activity-text"><?= htmlspecialchars($act['action']) ?></div>
                            <div class="activity-time"><?= htmlspecialchars($act['time']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

            </div><!-- /.profile-wrapper -->
            <?php
            break;

        default:
            include 'pages/dashboard.php';
            break;
    }
    ?>

</div>

</body>
</html> 