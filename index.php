<?php
include 'includes\db.php';

$result = $conn->query("SELECT * FROM icas");
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
        <div class="stat-num">12K+</div>
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

        <div class="alert alert-error" id="login-alert">
          <span>⚠️</span><span id="login-alert-msg">Invalid email or password. Please try again.</span>
        </div>

        <div class="field-group">
          <label class="field-label">Email Address</label>
          <div class="field-wrap">
            <span class="field-icon">✉️</span>
            <input type="email" id="login-email" placeholder="your@school.edu" autocomplete="email"/>
          </div>
          <div class="field-error" id="login-email-err">Please enter a valid email address.</div>
        </div>

        <div class="field-group">
          <label class="field-label">Password</label>
          <div class="field-wrap">
            <span class="field-icon">🔒</span>
            <input type="password" id="login-password" placeholder="Enter your password" autocomplete="current-password"/>
            <button class="toggle-pw" onclick="togglePw('login-password', this)" type="button">👁️</button>
          </div>
          <div class="field-error" id="login-pw-err">Password is required.</div>
        </div>

        <div class="field-meta">
          <a href="#" class="forgot-link" onclick="showForgot(event)">Forgot password?</a>
        </div>

        <button class="btn-submit" id="btn-login" onclick="handleLogin()">
          <span class="btn-label">Sign In →</span>
          <div class="spinner"></div>
        </button>
      </div>

      <!-- ── REGISTER FORM ── -->
      <div class="form-view" id="view-register">

        <div id="reg-form-wrap">
          <div class="form-title">Create Account</div>
          <div class="form-sub">Join the SANS system today</div>

          <div class="alert alert-error" id="reg-alert">
            <span>⚠️</span><span id="reg-alert-msg">Please fix the errors above.</span>
          </div>

          <div class="field-group">
            <label class="field-label">Full Name</label>
            <div class="field-wrap">
              <span class="field-icon">👤</span>
              <input type="text" id="reg-name" placeholder="e.g. Maria Santos" autocomplete="name"/>
            </div>
            <div class="field-error" id="reg-name-err">Full name is required.</div>
          </div>

          <div class="field-group">
            <label class="field-label">Email Address</label>
            <div class="field-wrap">
              <span class="field-icon">✉️</span>
              <input type="email" id="reg-email" placeholder="your@school.edu" autocomplete="email"/>
            </div>
            <div class="field-error" id="reg-email-err">Please enter a valid email.</div>
          </div>

          <div class="field-group role-select">
            <label class="field-label">Role</label>
            <div class="field-wrap">
              <span class="field-icon">🏫</span>
              <select id="reg-role">
                <option value="" disabled selected>Select your role…</option>
                <option value="admin">Administrator</option>
                <option value="teacher">Teacher</option>
                <option value="parent">Parent / Guardian</option>
              </select>
            </div>
            <div class="field-error" id="reg-role-err">Please select a role.</div>
          </div>

          <div class="field-group">
            <label class="field-label">Password</label>
            <div class="field-wrap">
              <span class="field-icon">🔒</span>
              <input type="password" id="reg-pw" placeholder="Minimum 8 characters"/>
              <button class="toggle-pw" onclick="togglePw('reg-pw', this)" type="button">👁️</button>
            </div>
            <div class="field-error" id="reg-pw-err">Password must be at least 8 characters.</div>
          </div>

          <div class="field-group">
            <label class="field-label">Confirm Password</label>
            <div class="field-wrap">
              <span class="field-icon">🔑</span>
              <input type="password" id="reg-pw2" placeholder="Re-enter your password"/>
              <button class="toggle-pw" onclick="togglePw('reg-pw2', this)" type="button">👁️</button>
            </div>
            <div class="field-error" id="reg-pw2-err">Passwords do not match.</div>
          </div>

          <button class="btn-submit" id="btn-register" onclick="handleRegister()">
            <span class="btn-label">Create Account →</span>
            <div class="spinner"></div>
          </button>
        </div>

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