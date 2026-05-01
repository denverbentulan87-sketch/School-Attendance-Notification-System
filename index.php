<?php
include 'includes/db.php';
$result = $conn->query("SELECT * FROM users");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SANS — School Attendance & Notification System</title>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,600;0,700;0,900;1,600&family=Figtree:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --navy:    #0D1B2A;
      --navy-2:  #162236;
      --navy-3:  #1E3050;
      --slate:   #4A6FA5;
      --ice:     #E8F0FB;
      --gold:    #C8973A;
      --gold-lt: #E8B860;
      --white:   #FFFFFF;
      --gray-1:  #F4F6FA;
      --gray-2:  #E2E8F0;
      --gray-3:  #94A3B8;
      --gray-4:  #64748B;
      --text:    #1A2332;
      --danger:  #DC2626;
      --success: #16A34A;
      --radius:  14px;
      --shadow:  0 20px 60px rgba(13,27,42,0.18);
    }

    html, body {
      height: 100%;
      font-family: 'Figtree', sans-serif;
      background: var(--gray-1);
      color: var(--text);
      overflow: hidden;
    }

    /* ── LAYOUT ── */
    .page {
      display: grid;
      grid-template-columns: 1fr 520px;
      height: 100vh;
      overflow: hidden;
    }

    /* ══ LEFT PANEL ══ */
    .left-panel {
      background: var(--navy);
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 60px 64px;
    }

    /* Geometric background shapes */
    .left-panel::before {
      content: '';
      position: absolute;
      top: -120px; right: -120px;
      width: 500px; height: 500px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(200,151,58,0.12) 0%, transparent 70%);
      pointer-events: none;
    }

    .left-panel::after {
      content: '';
      position: absolute;
      bottom: -80px; left: -80px;
      width: 360px; height: 360px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(74,111,165,0.18) 0%, transparent 70%);
      pointer-events: none;
    }

    /* Grid lines overlay */
    .grid-lines {
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
      background-size: 60px 60px;
      pointer-events: none;
    }

    /* Accent bar */
    .accent-bar {
      width: 48px;
      height: 4px;
      background: linear-gradient(90deg, var(--gold), var(--gold-lt));
      border-radius: 2px;
      margin-bottom: 32px;
      animation: slideIn 0.8s ease forwards;
    }

    /* Badge */
    .school-badge {
      display: flex;
      align-items: center;
      gap: 14px;
      margin-bottom: 48px;
      animation: fadeUp 0.7s ease forwards;
    }

    .badge-icon {
      width: 52px; height: 52px;
      background: linear-gradient(135deg, var(--gold), var(--gold-lt));
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 24px;
      box-shadow: 0 8px 24px rgba(200,151,58,0.35);
      flex-shrink: 0;
    }

    .badge-text .brand {
      font-family: 'Figtree', sans-serif;
      font-weight: 700;
      font-size: 15px;
      color: var(--white);
      letter-spacing: -0.2px;
      line-height: 1.2;
    }

    .badge-text .tagline {
      font-size: 11px;
      font-weight: 500;
      color: var(--gray-3);
      text-transform: uppercase;
      letter-spacing: 1.2px;
      margin-top: 3px;
    }

    /* Hero headline */
    .hero-headline {
      font-family: 'Fraunces', serif;
      font-size: clamp(38px, 4vw, 54px);
      font-weight: 900;
      color: var(--white);
      line-height: 1.08;
      letter-spacing: -1.5px;
      margin-bottom: 22px;
      animation: fadeUp 0.8s 0.1s ease both;
    }

    .hero-headline .accent {
      color: var(--gold);
      font-style: italic;
    }

    .hero-sub {
      font-size: 15px;
      font-weight: 400;
      color: var(--gray-3);
      line-height: 1.7;
      max-width: 380px;
      margin-bottom: 52px;
      animation: fadeUp 0.8s 0.2s ease both;
    }

    /* Stats */
    .stats-row {
      display: flex;
      gap: 0;
      animation: fadeUp 0.8s 0.3s ease both;
    }

    .stat {
      padding: 0 32px 0 0;
      border-right: 1px solid rgba(255,255,255,0.1);
      margin-right: 32px;
    }

    .stat:last-child { border-right: none; margin-right: 0; padding-right: 0; }

    .stat-num {
      font-family: 'Fraunces', serif;
      font-size: 32px;
      font-weight: 700;
      color: var(--gold);
      line-height: 1;
      letter-spacing: -1px;
    }

    .stat-label {
      font-size: 11px;
      font-weight: 600;
      color: var(--gray-3);
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-top: 5px;
    }

    /* Bottom caption */
    .left-caption {
      position: absolute;
      bottom: 32px; left: 64px;
      font-size: 11.5px;
      color: rgba(255,255,255,0.2);
      font-weight: 500;
      letter-spacing: 0.5px;
    }

    /* ══ RIGHT PANEL ══ */
    .right-panel {
      background: var(--white);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 48px;
      position: relative;
      overflow-y: auto;
    }

    .right-panel::before {
      content: '';
      position: absolute;
      top: 0; left: 0;
      width: 1px;
      height: 100%;
      background: linear-gradient(180deg, transparent, var(--gray-2) 20%, var(--gray-2) 80%, transparent);
    }

    .card {
      width: 100%;
      max-width: 400px;
      animation: fadeUp 0.6s ease both;
    }

    /* Top logo for right panel on mobile */
    .card-logo {
      display: none;
    }

    /* ── TABS ── */
    .tab-row {
      display: flex;
      background: var(--gray-1);
      border-radius: 12px;
      padding: 5px;
      margin-bottom: 36px;
      gap: 4px;
    }

    .tab-btn {
      flex: 1;
      padding: 11px 16px;
      border: none;
      border-radius: 9px;
      font-family: 'Figtree', sans-serif;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      background: transparent;
      color: var(--gray-3);
      transition: all 0.2s ease;
      letter-spacing: -0.1px;
    }

    .tab-btn.active {
      background: var(--white);
      color: var(--navy);
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    /* ── FORM VIEWS ── */
    .form-view { display: none; }
    .form-view.active { display: block; }

    .form-title {
      font-family: 'Fraunces', serif;
      font-size: 28px;
      font-weight: 700;
      color: var(--navy);
      letter-spacing: -0.8px;
      margin-bottom: 6px;
      line-height: 1.15;
    }

    .form-sub {
      font-size: 14px;
      color: var(--gray-3);
      margin-bottom: 28px;
      font-weight: 400;
    }

    /* ── FIELD ── */
    .field-group {
      margin-bottom: 18px;
    }

    .field-label {
      display: block;
      font-size: 11.5px;
      font-weight: 700;
      color: var(--gray-4);
      text-transform: uppercase;
      letter-spacing: 0.8px;
      margin-bottom: 8px;
    }

    .field-wrap {
      position: relative;
    }

    .field-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 15px;
      pointer-events: none;
      line-height: 1;
    }

    .field-wrap input,
    .field-wrap select {
      width: 100%;
      padding: 13px 14px 13px 42px;
      border: 1.5px solid var(--gray-2);
      border-radius: 10px;
      font-size: 14px;
      font-family: 'Figtree', sans-serif;
      font-weight: 500;
      color: var(--navy);
      background: var(--gray-1);
      outline: none;
      transition: all 0.2s ease;
      appearance: none;
    }

    .field-wrap input:focus,
    .field-wrap select:focus {
      border-color: var(--slate);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(74,111,165,0.12);
    }

    .field-wrap input::placeholder { color: var(--gray-3); font-weight: 400; }

    /* Select arrow */
    .field-wrap select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2394A3B8' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      padding-right: 36px;
    }

    /* Parent email hint */
    .field-hint {
      font-size: 11.5px;
      color: var(--gray-3);
      margin-top: 6px;
      padding-left: 2px;
      font-weight: 400;
    }

    /* ── FIELD META ── */
    .field-meta {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 22px;
      margin-top: -8px;
    }

    .forgot-link {
      font-size: 13px;
      color: var(--slate);
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s;
    }

    .forgot-link:hover { color: var(--navy); }

    /* ── SUBMIT BUTTON ── */
    .btn-submit {
      width: 100%;
      padding: 14px 20px;
      background: var(--navy);
      color: var(--white);
      border: none;
      border-radius: 11px;
      font-size: 15px;
      font-family: 'Figtree', sans-serif;
      font-weight: 700;
      cursor: pointer;
      letter-spacing: -0.2px;
      transition: all 0.2s ease;
      position: relative;
      overflow: hidden;
      margin-top: 4px;
    }

    .btn-submit::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(255,255,255,0.08), transparent);
      pointer-events: none;
    }

    .btn-submit:hover {
      background: var(--navy-3);
      transform: translateY(-1px);
      box-shadow: 0 8px 24px rgba(13,27,42,0.25);
    }

    .btn-submit:active { transform: translateY(0); }

    /* ── ALERT ── */
    .alert {
      padding: 12px 16px;
      border-radius: 9px;
      font-size: 13.5px;
      font-weight: 500;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .alert-error {
      background: #FEF2F2;
      color: var(--danger);
      border: 1px solid #FECACA;
    }

    /* ── DIVIDER ── */
    .form-divider {
      display: flex;
      align-items: center;
      gap: 12px;
      margin: 20px 0;
    }

    .form-divider span {
      font-size: 12px;
      color: var(--gray-3);
      font-weight: 500;
      white-space: nowrap;
    }

    .form-divider::before,
    .form-divider::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--gray-2);
    }

    /* ── SUCCESS SPLASH ── */
    .success-splash {
      display: none;
      flex-direction: column;
      align-items: center;
      text-align: center;
      padding: 20px 0;
    }

    .splash-icon-wrap {
      width: 72px; height: 72px;
      background: linear-gradient(135deg, #DCFCE7, #BBF7D0);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 32px;
      margin-bottom: 20px;
      box-shadow: 0 8px 24px rgba(22,163,74,0.2);
    }

    .splash-title {
      font-family: 'Fraunces', serif;
      font-size: 26px;
      font-weight: 700;
      color: var(--navy);
      margin-bottom: 10px;
      letter-spacing: -0.5px;
    }

    .splash-sub {
      font-size: 14px;
      color: var(--gray-4);
      line-height: 1.6;
      margin-bottom: 28px;
    }

    .splash-back {
      background: var(--navy);
      color: var(--white);
      border: none;
      padding: 12px 28px;
      border-radius: 10px;
      font-size: 14px;
      font-family: 'Figtree', sans-serif;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
    }

    .splash-back:hover {
      background: var(--navy-3);
      transform: translateY(-1px);
    }

    /* ── ANIMATIONS ── */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(18px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    @keyframes slideIn {
      from { width: 0; opacity: 0; }
      to   { width: 48px; opacity: 1; }
    }

    /* ── REGISTER SCROLL ── */
    #view-register {
      max-height: calc(100vh - 120px);
      overflow-y: auto;
      padding-right: 4px;
    }

    #view-register::-webkit-scrollbar { width: 4px; }
    #view-register::-webkit-scrollbar-track { background: transparent; }
    #view-register::-webkit-scrollbar-thumb { background: var(--gray-2); border-radius: 2px; }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
      .page { grid-template-columns: 1fr; }
      .left-panel { display: none; }
      .right-panel { padding: 32px 24px; }
    }
  </style>
</head>
<body>

<div class="page">

  <!-- ══ LEFT PANEL ══ -->
  <div class="left-panel">
    <div class="grid-lines"></div>

    <div class="school-badge">
      <div class="badge-icon">🎓</div>
      <div class="badge-text">
        <div class="brand">Inabanga College of Arts &amp; Sciences</div>
        <div class="tagline">Attendance &amp; Notification System</div>
      </div>
    </div>

    <div class="accent-bar"></div>

    <h1 class="hero-headline">
      Every student<br>present &amp;<br><span class="accent">accounted for.</span>
    </h1>

    <p class="hero-sub">
      A unified platform for tracking daily attendance, sending real-time notifications to parents, and keeping educators informed — all in one place.
    </p>

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

    <div class="left-caption">© 2026 Inabanga College of Arts &amp; Sciences</div>
  </div>

  <!-- ══ RIGHT PANEL ══ -->
  <div class="right-panel">
    <div class="card">

      <!-- TABS -->
      <div class="tab-row">
        <button class="tab-btn active" id="tab-login" onclick="switchTab('login')">Sign In</button>
        <button class="tab-btn" id="tab-register" onclick="switchTab('register')">Register</button>
      </div>

      <!-- ── LOGIN FORM ── -->
      <div class="form-view active" id="view-login">

        <?php if (isset($_GET['error']) && !isset($_GET['from_register'])): ?>
          <div class="alert alert-error">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <div class="form-title">Welcome back</div>
        <div class="form-sub">Sign in to your EduTrack account</div>

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

          <button class="btn-submit" type="submit" name="login">Sign In →</button>

        </form>
      </div>

      <!-- ── REGISTER FORM ── -->
      <div class="form-view" id="view-register">

        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-error">⚠️ <?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>

        <div class="form-title">Create account</div>
        <div class="form-sub">Join EduTrack and get started today</div>

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

          <div class="field-group">
            <label class="field-label">Role</label>
            <div class="field-wrap">
              <span class="field-icon">🏫</span>
              <select name="role" id="role-select" required onchange="toggleParentEmail(this.value)">
                <option value="">Select your role</option>
                <option value="admin">Administrator</option>
                <option value="student">Student</option>
                <option value="parent">Parent</option>
              </select>
            </div>
          </div>

          <!-- Parent email — students only -->
          <div class="field-group" id="parent-email-field" style="display:none;">
            <label class="field-label">Parent Gmail <span style="color:#DC2626;">*</span></label>
            <div class="field-wrap">
              <span class="field-icon">📧</span>
              <input type="email" name="parent_email" id="parent_email" placeholder="parent@gmail.com">
            </div>
            <div class="field-hint">Your parent will be notified if you miss attendance.</div>
          </div>

          <div class="field-group">
            <label class="field-label">Password</label>
            <div class="field-wrap">
              <span class="field-icon">🔒</span>
              <input type="password" name="password" placeholder="Create a password" required>
            </div>
          </div>

          <div class="field-group">
            <label class="field-label">Confirm Password</label>
            <div class="field-wrap">
              <span class="field-icon">🔒</span>
              <input type="password" name="confirm_password" placeholder="Re-enter your password" required>
            </div>
          </div>

          <button type="submit" name="register" class="btn-submit">Create Account →</button>

        </form>
      </div>

      <!-- ── SUCCESS SPLASH ── -->
      <div class="success-splash" id="reg-success">
        <div class="splash-icon-wrap">✅</div>
        <div class="splash-title">Account Created!</div>
        <div class="splash-sub">
          Your account has been registered.<br>
          Check your email for your QR code,<br>then sign in with your credentials.
        </div>
        <button class="splash-back" onclick="goToLogin()">← Back to Sign In</button>
      </div>

    </div>
  </div>
</div>

<script>
  function toggleParentEmail(role) {
    const field = document.getElementById('parent-email-field');
    const input = document.getElementById('parent_email');
    if (role === 'student') {
      field.style.display = 'block';
      input.required = true;
    } else {
      field.style.display = 'none';
      input.required = false;
      input.value = '';
    }
  }

  function switchTab(tab) {
    ['view-login','view-register'].forEach(id =>
      document.getElementById(id).classList.remove('active'));
    ['tab-login','tab-register'].forEach(id =>
      document.getElementById(id).classList.remove('active'));

    document.getElementById('view-' + tab).classList.add('active');
    document.getElementById('tab-' + tab).classList.add('active');
  }

  function goToLogin() {
    document.getElementById('reg-success').style.display = 'none';
    switchTab('login');
  }

  // Auto-switch to register tab on register error
  <?php if (isset($_GET['error'])): ?>
    switchTab('register');
  <?php endif; ?>

  // Show success splash
  <?php if (isset($_GET['success']) && $_GET['success'] === 'registered'): ?>
    document.getElementById('view-login').classList.remove('active');
    document.getElementById('view-register').classList.remove('active');
    document.getElementById('reg-success').style.display = 'flex';
  <?php endif; ?>
</script>

</body>
</html>