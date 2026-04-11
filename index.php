<?php
include 'includes/db.php';

// Fetch all users (not used in this page currently, but could be used for admin overview)
$result = $conn->query("SELECT * FROM users");
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SANS — School Attendance & Notification System</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  
  <!-- External CSS file -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<!-- Background layers for design -->
<div class="bg-layer"></div>
<div class="grid-overlay"></div>  

<div class="page">

  <!-- LEFT PANEL: Branding and hero content -->
  <div class="left-panel">
    <!-- Decorative orbs -->
    <div class="deco-orb orb-1"></div>
    <div class="deco-orb orb-2"></div>

    <!-- School badge -->
    <div class="school-badge">
      <div class="badge-icon">🎓</div>
      <div class="badge-text">
        <div class="brand">Inabanga College of Arts & Sciences</div>
        <div class="tagline">School Attendance & Notification System</div>
      </div>
    </div>

    <!-- Hero headline -->
    <h1 class="hero-headline">
      Every student<br>present &amp;<br><span class="accent">accounted for.</span>
    </h1>

    <!-- Hero subtext -->
    <p class="hero-sub">
      A unified platform for tracking daily attendance, sending real-time notifications to parents, and keeping educators informed — all in one place.
    </p>

    <!-- Quick stats -->
    <div class="stats-row">
      <div class="stat">
        <div class="stat-num">150+</div>
        <div class="stat-label">Students</div>
      </div>
      <div class="stat">
        <div class="stat-num">20</div>
        <div class="stat-label">Classrooms</div>
      </div>
      <div class="stat">
        <div class="stat-num">100%</div>
        <div class="stat-label">Accuracy</div>
      </div>
    </div>
  </div>

  <!-- RIGHT PANEL: Forms for login and registration -->
  <div class="right-panel">
    <div class="card">

      <!-- Tabs to switch between login and register -->
      <div class="tab-row">
        <button class="tab-btn active" id="tab-login" onclick="switchTab('login')">Sign In</button>
        <button class="tab-btn" id="tab-register" onclick="switchTab('register')">Register</button>
      </div>

      <!-- LOGIN FORM -->
      <div class="form-view active" id="view-login">

        <!-- Form title and subtitle -->
        <div class="form-title">Welcome back</div>
        <div class="form-sub">Sign in to your SANS account</div>
        
        <!-- Login form submission -->
        <form action="login.php" method="POST">

          <!-- Email field -->
          <div class="field-group">
            <label class="field-label">Email Address</label>
            <div class="field-wrap">
              <span class="field-icon">✉️</span>
              <input type="email" name="email" placeholder="your@school.edu" required>
            </div>
          </div>

          <!-- Password field -->
          <div class="field-group">
            <label class="field-label">Password</label>
            <div class="field-wrap">
              <span class="field-icon">🔒</span>
              <input type="password" name="password" placeholder="Enter your password" required>
            </div>
          </div>

          <!-- Forgot password link -->
          <div class="field-meta">
            <a href="#" class="forgot-link">Forgot password?</a>
          </div>

          <!-- Submit button -->
          <button class="btn-submit" type="submit" name="login">
            <span class="btn-label">Sign In →</span>
          </button>

        </form>
      </div>

      <!-- REGISTER FORM -->
      <div class="form-view" id="view-register">

        <!-- Registration form submission -->
        <form action="register.php" method="POST">

          <!-- Full Name -->
          <div class="field-group">
            <label class="field-label">Full Name</label>
            <div class="field-wrap">
              <span class="field-icon">👤</span>
              <input type="text" name="fullname" placeholder="e.g. Maria Santos" required>
            </div>
          </div>

          <!-- Email -->
          <div class="field-group">
            <label class="field-label">Email Address</label>
            <div class="field-wrap">
              <span class="field-icon">✉️</span>
              <input type="email" name="email" placeholder="your@school.edu" required>
            </div>
          </div>

          <!-- Role selection -->
          <div class="field-group role-select">
            <label class="field-label">Role</label>
            <div class="field-wrap">
              <span class="field-icon">🏫</span>
              <select name="role" required>
                <option value="">Select your role</option>
                <option value="admin">Administrator</option>
                <option value="student">Student</option>
                <option value="parent">Parent</option>
              </select>
            </div>
          </div>

          <!-- Password -->
          <div class="field-group">
            <label class="field-label">Password</label>
            <div class="field-wrap">
              <span class="field-icon">🔒</span>
              <input type="password" name="password" placeholder="Enter password" required>
            </div>
          </div>

          <!-- Confirm Password -->
          <div class="field-group">
            <label class="field-label">Confirm Password</label>
            <div class="field-wrap">
              <span class="field-icon">🔒</span>
              <input type="password" name="confirm_password" placeholder="Confirm password" required>
            </div>
          </div>

          <!-- Submit button -->
          <button type="submit" name="register" class="btn-submit">
            Create Account →
          </button>

        </form>

      </div>

      <!-- Success Splash for registration -->
      <div class="success-splash" id="reg-success">
        <div class="splash-icon">✅</div>
        <div class="splash-title">Account Created!</div>
        <div class="splash-sub">Your account has been registered.<br>You can now sign in with your credentials.</div>
        <button class="splash-back" onclick="goToLogin()">← Back to Sign In</button>
      </div>

    </div>
  </div>
</div>

<!-- JS to switch between login and register tabs -->
<script>
function switchTab(tab) {
  // Hide both forms
  document.getElementById('view-login').classList.remove('active');
  document.getElementById('view-register').classList.remove('active');

  // Reset tab buttons
  document.getElementById('tab-login').classList.remove('active');
  document.getElementById('tab-register').classList.remove('active');

  // Show the selected form and activate corresponding tab
  if (tab === 'login') {
    document.getElementById('view-login').classList.add('active');
    document.getElementById('tab-login').classList.add('active');
  } else {
    document.getElementById('view-register').classList.add('active');
    document.getElementById('tab-register').classList.add('active');
  }
}
</script>

<!-- External JS file -->
<script src="js/script.js"></script>
</body>
</html>