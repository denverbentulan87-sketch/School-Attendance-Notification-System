<?php
session_start();
include 'includes/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student'){
    header("Location: index.php");
    exit();
}

$name = $_SESSION['name'];
$role = $_SESSION['role'];
$page = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html>
<head>
<title>Student Dashboard</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
body{margin:0;font-family:Poppins;background:#f4f7fb;display:flex;}
.sidebar{
    width:230px;
    height:100vh;
    background:#1e3a8a;
    color:white;
    padding:20px;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
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

.sidebar a{
    display:block;
    padding:10px;
    margin:5px 0;
    color:white;
    text-decoration:none;
    border-radius:8px;
}
.sidebar a.active,
.sidebar a:hover{background:#3b82f6;}

.main{flex:1;padding:20px;}
</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div>
        <h2>🎓 SANS</h2>

        <a href="?page=dashboard" class="<?= $page=='dashboard'?'active':'' ?>">🏠 Dashboard</a>
        <a href="?page=attendance" class="<?= $page=='attendance'?'active':'' ?>">📅 My Attendance</a>
        <a href="?page=notifications" class="<?= $page=='notifications'?'active':'' ?>">🔔 Notifications</a>
    </div>

    <a href="logout.php" style="background:red;text-align:center;border-radius:8px;">Logout</a>

</div>

<!-- MAIN CONTENT -->
<div class="main">

    <div class="header">
        <div class="welcome">Welcome, <?php echo $name; ?> 👋</div>
        <div class="role"><?php echo ucfirst($role); ?></div>
    </div>

<?php
// LOAD PAGES (LIKE ADMIN)
if($page == 'dashboard'){
    include 'student_pages/dashboard.php';
}
elseif($page == 'attendance'){
    include 'student_pages/attendance.php';
}
elseif($page == 'notifications'){
    include 'student_pages/notifications.php';
}
elseif($page == 'notif_count'){
    include 'student_pages/notif_count.php';
}
?>

</div>

</body>
</html>