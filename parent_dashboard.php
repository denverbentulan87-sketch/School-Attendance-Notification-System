<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent'){
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
<title>Parent Dashboard - SANS</title>

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

/* ✅ SIDEBAR (MATCH ADMIN STYLE) */
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

.logo {
    font-size: 22px;
    font-weight: 600;
    text-align: center;
    margin-bottom: 30px;
}

/* NAV */
.nav a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 15px;
    margin: 6px 0;
    color: white;
    text-decoration: none;
    border-radius: 10px;
    transition: 0.25s;
}

/* HOVER */
.nav a:hover {
    background: rgba(255,255,255,0.15);
}

/* ACTIVE */
.nav a.active {
    background: #3b82f6;
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

/* MAIN */
.main {
    flex: 1;
    padding: 25px;
}

/* HEADER */
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

/* ✅ CARDS (MATCH ADMIN STYLE) */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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

.card h3 {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 10px;
}

.card p {
    font-size: 26px;
    font-weight: 600;
}

/* TABLE */
.table {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}

.table h3 {
    margin-bottom: 15px;
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
    border-bottom: 1px solid #e5e7eb;
}

/* STATUS COLORS */
.present {
    color: #16a34a;
    font-weight: 500;
}

.absent {
    color: #dc2626;
    font-weight: 500;
}

/* RESPONSIVE */
@media(max-width: 768px){
    .sidebar {
        display: none;
    }
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div>
        <div class="logo">🎓 EduTrack</div>

        <div class="nav">
            <a href="?page=dashboard" class="<?= $page=='dashboard'?'active':'' ?>">🏠 Dashboard</a>
            <a href="?page=children" class="<?= $page=='children'?'active':'' ?>">👨‍👩‍👧 My Children</a>
            <a href="?page=attendance" class="<?= $page=='attendance'?'active':'' ?>">📅 Attendance</a>
            <a href="?page=notifications" class="<?= $page=='notifications'?'active':'' ?>">🔔 Notifications</a>
        </div>
    </div>

    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>
</div>

<!-- MAIN -->
<div class="main">

    <div class="header">
        <div class="welcome">Welcome, <?php echo $name; ?> 👋</div>
        <div class="role"><?php echo ucfirst($role); ?></div>
    </div>

    <?php
    switch($page) {
        case 'dashboard':
            include 'parent_pages/dashboard.php';
            break;

        case 'children':
            include 'parent_pages/children.php';
            break;

        case 'attendance':
            include 'parent_pages/attendance.php';
            break;

        case 'notifications':
            include 'parent_pages/notifications.php';
            break;

        default:
            include 'parent_pages/dashboard.php';
            break;
    }
    ?>

</div>

</body>
</html>