<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
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

/* Sidebar */
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

.nav a {
    display: block;
    padding: 12px;
    margin: 8px 0;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: 0.3s;
}

.nav a:hover {
    background: #3b82f6;
}

.logout a {
    display: block;
    padding: 10px;
    background: #ef4444;
    text-align: center;
    border-radius: 8px;
    color: white;
    text-decoration: none;
}

.logout a:hover {
    background: #dc2626;
}

/* Main */
.main {
    flex: 1;
    padding: 25px;
}

/* Header */
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

/* Cards */
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

.card h3 {
    font-size: 14px;
    color: #6b7280;
    margin-bottom: 10px;
}

.card p {
    font-size: 24px;
    font-weight: 600;
    color: #111827;
}

/* Table */
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
            <a href="#">🏠 Dashboard</a>
            <a href="#">👨‍🎓 Students</a>
            <a href="#">📅 Attendance</a>
            <a href="#">🔔 Notifications</a>
            <a href="#">📊 Reports</a>
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

    <div class="cards">
        <div class="card">
            <h3>Total Students</h3>
            <p>120</p>
        </div>
        <div class="card">
            <h3>Present Today</h3>
            <p style="color: green;">110</p>
        </div>
        <div class="card">
            <h3>Absent Today</h3>
            <p style="color: red;">10</p>
        </div>
        <div class="card">
            <h3>Notifications Sent</h3>
            <p>35</p>
        </div>
    </div>

    <div class="table">
        <h3>Recent Attendance</h3>
        <table>
            <tr>
                <th>Student</th>
                <th>Date</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>Juan Dela Cruz</td>
                <td>March 17, 2026</td>
                <td class="present">Present</td>
            </tr>
            <tr>
                <td>Maria Santos</td>
                <td>March 17, 2026</td>
                <td class="absent">Absent</td>
            </tr>
            <tr>
                <td>Pedro Reyes</td>
                <td>March 17, 2026</td>
                <td class="present">Present</td>
            </tr>
        </table>
    </div>

</div>

</body>
</html>