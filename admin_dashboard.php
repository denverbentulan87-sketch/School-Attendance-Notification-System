<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: index.php");
    exit();
}

$name = $_SESSION['name'];
$role = $_SESSION['role'];
$page = $_GET['page'] ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - SANS</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}

body {
    display: flex;
    background: #f4f7fb;
}

/* ✅ SIDEBAR (ONLY THIS PART UPDATED) */
.sidebar {
    width: 240px;
    height: 100vh;
    background: #1e3a8a;
    color: white;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 20px;
}

/* NAV LINKS */
.nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    margin: 6px 0;
    color: white;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.25s ease;
    font-size: 16px; /* ✅ match other dashboards */
}

/* HOVER */
.nav a:hover {
    background: rgba(255,255,255,0.15);
}

/* ACTIVE (LIKE STUDENT DASHBOARD) */
.nav a.active {
    background: #3b82f6;
    color: white;
    font-weight: 500;
}

/* LOGOUT */
.logout a {
    display: block;
    padding: 10px;
    background: #ef4444;
    text-align: center;
    border-radius: 10px;
    color: white;
    text-decoration: none;
}

.logout a:hover {
    background: #dc2626;
}

/* === KEEP EVERYTHING BELOW UNCHANGED === */

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

.table-header h2 {
    font-size: 20px;
}

.search-box input {
    padding: 8px 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

table {
    width: 100%;
    border-collapse: collapse;
}

table th {
    background: #f1f5f9;
    padding: 12px;
    text-align: left;
}

table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

table tr:hover {
    background: #f9fafb;
}

/* Buttons */
.btn {
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 13px;
}

.btn-edit {
    background: #3b82f6;
    color: white;
}

.btn-delete {
    background: #ef4444;
    color: white;
}

.btn-add {
    background: #10b981;
    color: white;
    border: none;
    cursor: pointer;
}

.logo {
    font-size: 22px;
    font-weight: 600;
    text-align: center;
    margin-bottom: 30px;
}
.main {
    flex: 1;
    padding: 25px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.welcome {
    font-size: 22px;
    font-weight: 500;
}

.role {
    background: #3b82f6;
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 14px;
}

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

.card:hover {
    transform: translateY(-5px);
}

.table {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

table td {
    border-bottom: 1px solid #e5e7eb;
}

/* Status colors */
.present {
    color: green;
    font-weight: 500;
}

.absent {
    color: red;
    font-weight: 500;
}

/* Responsive */
@media(max-width: 768px){
    .sidebar {
        display: none;
    }
}
</style>
</head>

<body>

<div class="sidebar">
    <div>
        <div class="logo">🎓 SANS</div>

        <div class="nav">
            <a href="admin_dashboard.php?page=dashboard" class="<?= $page=='dashboard'?'active':'' ?>">🏠 Dashboard</a>
            <a href="admin_dashboard.php?page=students" class="<?= $page=='students'?'active':'' ?>">👨‍🎓 Students</a>
            <a href="admin_dashboard.php?page=attendance" class="<?= $page=='attendance'?'active':'' ?>">📅 Attendance</a>
            <a href="admin_dashboard.php?page=notifications" class="<?= $page=='notifications'?'active':'' ?>">🔔 Notifications</a>
            <a href="admin_dashboard.php?page=reports" class="<?= $page=='reports'?'active':'' ?>">📊 Reports</a>
        </div>
    </div>

    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="main">

    <div class="header">
        <div class="welcome">Welcome, <?php echo $name; ?> 👋</div>
        <div class="role"><?php echo ucfirst($role); ?></div>
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