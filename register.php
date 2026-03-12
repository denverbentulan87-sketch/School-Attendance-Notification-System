<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
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
</body>
</html>