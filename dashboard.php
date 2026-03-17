<?php
session_start();

if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit();
}

$name = $_SESSION['name'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>SANS Dashboard</title>

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family: 'Segoe UI', sans-serif;
}

body{
display:flex;
background:#f4f6fa;
height:100vh;
}

/* SIDEBAR */

.sidebar{
width:240px;
background:#0d1b2a;
color:white;
padding:30px 20px;
display:flex;
flex-direction:column;
}

.logo{
font-size:20px;
font-weight:bold;
margin-bottom:40px;
}

.nav a{
display:block;
color:white;
text-decoration:none;
padding:12px;
border-radius:6px;
margin-bottom:10px;
transition:0.3s;
}

.nav a:hover{
background:#f0a500;
color:#000;
}

.logout{
margin-top:auto;
}

/* MAIN */

.main{
flex:1;
padding:30px;
}

.header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:30px;
}

.welcome{
font-size:22px;
font-weight:600;
}

.role{
background:#f0a500;
color:#000;
padding:6px 12px;
border-radius:20px;
font-size:14px;
}

/* CARDS */

.cards{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
gap:20px;
}

.card{
background:white;
padding:25px;
border-radius:10px;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

.card h3{
color:#0d1b2a;
margin-bottom:10px;
}

.card p{
font-size:28px;
font-weight:bold;
color:#f0a500;
}

/* TABLE */

.table{
margin-top:30px;
background:white;
padding:20px;
border-radius:10px;
box-shadow:0 4px 12px rgba(0,0,0,0.08);
}

table{
width:100%;
border-collapse:collapse;
}

th,td{
padding:12px;
border-bottom:1px solid #eee;
text-align:left;
}

th{
background:#0d1b2a;
color:white;
}

</style>
</head>

<body>

<!-- SIDEBAR -->

<div class="sidebar">

<div class="logo">
🎓 SANS
</div>

<div class="nav">

<a href="#">Dashboard</a>
<a href="#">Students</a>
<a href="#">Attendance</a>
<a href="#">Notifications</a>
<a href="#">Reports</a>

</div>

<div class="logout">
<a href="logout.php">Logout</a>
</div>

</div>


<!-- MAIN CONTENT -->

<div class="main">

<div class="header">

<div class="welcome">
Welcome, <?php echo $name; ?> 👋
</div>

<div class="role">
<?php echo ucfirst($role); ?>
</div>

</div>


<!-- STATISTICS -->

<div class="cards">

<div class="card">
<h3>Total Students</h3>
<p>120</p>
</div>

<div class="card">
<h3>Present Today</h3>
<p>110</p>
</div>

<div class="card">
<h3>Absent Today</h3>
<p>10</p>
</div>

<div class="card">
<h3>Notifications Sent</h3>
<p>35</p>
</div>

</div>


<!-- TABLE -->

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
<td>Present</td>
</tr>

<tr>
<td>Maria Santos</td>
<td>March 17, 2026</td>
<td>Absent</td>
</tr>

<tr>
<td>Pedro Reyes</td>
<td>March 17, 2026</td>
<td>Present</td>
</tr>

</table>

</div>

</div>

</body>
</html>