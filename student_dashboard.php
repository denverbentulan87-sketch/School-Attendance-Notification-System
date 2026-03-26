<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student'){
    header("Location: index.php");
    exit();
}

$name = $_SESSION['name'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard - SANS</title>
<style>
/* Reuse same styles from admin */
<?php include 'dashboard_styles.css'; ?> 
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">🎓 SANS</div>
    <div class="nav">
        <a href="#">Dashboard</a>
        <a href="#">My Attendance</a>
        <a href="#">Notifications</a>
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

    <div class="cards">
        <div class="card"><h3>Days Present</h3><p>110</p></div>
        <div class="card"><h3>Days Absent</h3><p>10</p></div>
        <div class="card"><h3>Notifications</h3><p>3</p></div>
    </div>

    <div class="table">
        <h3>My Recent Attendance</h3>
        <table>
            <tr><th>Date</th><th>Status</th></tr>
            <tr><td>March 17, 2026</td><td>Present</td></tr>
            <tr><td>March 16, 2026</td><td>Present</td></tr>
            <tr><td>March 15, 2026</td><td>Absent</td></tr>
        </table>
    </div>
</div>

</body>
</html>