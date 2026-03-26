<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent'){
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
<title>Parent Dashboard - SANS</title>
<style>
/* Reuse same styles from admin */
<?php include 'dashboard_styles.css'; ?> /* optional: external CSS */
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">🎓 SANS</div>
    <div class="nav">
        <a href="#">Dashboard</a>
        <a href="#">My Children</a>
        <a href="#">Attendance</a>
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
        <div class="card"><h3>Children Enrolled</h3><p>2</p></div>
        <div class="card"><h3>Present Today</h3><p>2</p></div>
        <div class="card"><h3>Absent Today</h3><p>0</p></div>
        <div class="card"><h3>Notifications</h3><p>5</p></div>
    </div>

    <div class="table">
        <h3>Recent Attendance</h3>
        <table>
            <tr><th>Child</th><th>Date</th><th>Status</th></tr>
            <tr><td>Juan Dela Cruz</td><td>March 17, 2026</td><td>Present</td></tr>
            <tr><td>Maria Dela Cruz</td><td>March 17, 2026</td><td>Present</td></tr>
        </table>
    </div>
</div>

</body>
</html>