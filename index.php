<?php
include 'includes\db.php';

$result = $conn->query("SELECT * FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SANS — School Attendance & Notification System</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="assets\css\style.css">
  
</head>
<body>

<div class="bg-layer"></div>
<div class="grid-overlay"></div>

<div class="page">

  <!-- ══ LEFT PANEL ══ -->
  <div class="left-panel">
    <div class="deco-orb orb-1"></div>
    <div class="deco-orb orb-2"></div>

    <div class="school-badge">
      <div class="badge-icon">🎓</div>
      <div class="badge-text">
        <div class="brand">SANS</div>
        <div class="tagline">School Attendance & Notification System</div>
      </div>
    </div>
    

    <h1 class="hero-headline">
      Every student<br>present &amp;<br><span class="accent">accounted for.</span>
    </h1>

    <p class="hero-sub">
      A unified platform for tracking daily attendance, sending real-time notifications to parents, and keeping educators informed — all in one place.
    </p>

    <div class="stats-row">
      <div class="stat">
        <div class="stat-num">2+</div>
        <div class="stat-label">Students</div>
      </div>
      <div class="stat">
        <div class="stat-num">340</div>
        <div class="stat-label">Classrooms</div>
      </div>
      <div class="stat">
        <div class="stat-num">98%</div>
        <div class="stat-label">Accuracy</div>
      </div>
    </div>
  </div>

  <!-- ══ RIGHT PANEL ══ -->
  <div class="right-panel">
    <div class="card">

      <!-- Tabs -->
      <div class="tab-row">
        <button class="tab-btn active" id="tab-login" onclick="switchTab('login')">Sign In</button>
        <button class="tab-btn" id="tab-register" onclick="switchTab('register')">Register</button>
      </div>

      <!-- ── LOGIN FORM ── -->
<div class="form-view active" id="view-login">

<div class="form-title">Welcome back</div>
<div class="form-sub">Sign in to your SANS account</div>

<form action="login.php" method="POST">

<div class="field-group">
<label class="field-label">Email Address</label>

<div class="field-wrap">
<span class="field-icon">✉️</span>

<input type="email" name="email" placeholder="your@school.edu" required>

</div>
</div>

<div class="field-group">

<label class="field-label">Password</label>

<div class="field-wrap">

<span class="field-icon">🔒</span>

<input type="password" name="password" placeholder="Enter your password" required>

</div>

</div>

<div class="field-meta">
<a href="#" class="forgot-link">Forgot password?</a>
</div>

<button class="btn-submit" type="submit" name="login">

<span class="btn-label">Sign In →</span>

</button>

</form>

</div>

      <!-- ── REGISTER FORM ── -->
     <form action="register.php" method="POST">

<div class="field-group">
<label class="field-label">Full Name</label>
<div class="field-wrap">
<span class="field-icon">👤</span>
<input type="text" name="fullname" placeholder="e.g. Maria Santos" required>
</div>
</div>

<div class="field-group">
<label class="field-label">Email Address</label>
<div class="field-wrap">
<span class="field-icon">✉️</span>
<input type="email" name="email" placeholder="your@school.edu" required>
</div>
</div>

<div class="field-group role-select">
<label class="field-label">Role</label>
<div class="field-wrap">
<span class="field-icon">🏫</span>

<select name="role" required>
<option value="">Select your role</option>
<option value="admin">Administrator</option>
<option value="teacher">Teacher</option>
<option value="parent">Parent</option>
</select>

</div>
</div>

<div class="field-group">
<label>Password</label>
<input type="password" name="password" required>
</div>

<div class="field-group">
<label>Confirm Password</label>
<input type="password" name="confirm_password" required>
</div>

<button type="submit" name="register" class="btn-submit">
Create Account →
</button>

</form>

        <!-- Success Splash -->
        <div class="success-splash" id="reg-success">
          <div class="splash-icon">✅</div>
          <div class="splash-title">Account Created!</div>
          <div class="splash-sub">Your account has been registered.<br>You can now sign in with your credentials.</div>
          <button class="splash-back" onclick="goToLogin()">← Back to Sign In</button>
        </div>

      </div>
      <!-- end register view -->

    </div>
  </div>
</div>

<script src="js\script.js"></script>
</body>
</html>