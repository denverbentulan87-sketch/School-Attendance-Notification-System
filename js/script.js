// ── Simulated credentials ──────────────────────────
  const VALID_USERS = [
    { email: 'admin@sans.edu', password: 'password123', role: 'admin' },
    { email: 'teacher@sans.edu', password: 'teacher2024', role: 'teacher' },
  ];

  // In-memory registry for newly registered users
  const registeredUsers = [];

  // ── Tab switching ──────────────────────────────────
  function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.form-view').forEach(v => v.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    document.getElementById('view-' + tab).classList.add('active');
    clearErrors();
  }

  // ── Password toggle ────────────────────────────────
  function togglePw(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') { input.type = 'text'; btn.textContent = '🙈'; }
    else { input.type = 'password'; btn.textContent = '👁️'; }
  }

  // ── Clear all errors ────────────────────────────────
  function clearErrors() {
    document.querySelectorAll('.field-error').forEach(e => e.classList.remove('show'));
    document.querySelectorAll('.error-field').forEach(e => e.classList.remove('error-field'));
    document.querySelectorAll('.alert').forEach(a => a.classList.remove('show'));
  }

  function showErr(inputId, errId) {
    const inp = document.getElementById(inputId);
    if (inp) inp.classList.add('error-field');
    const err = document.getElementById(errId);
    if (err) err.classList.add('show');
  }

  function showAlert(alertId, msgId, msg) {
    document.getElementById(msgId).textContent = msg;
    const el = document.getElementById(alertId);
    el.classList.add('show');
    el.classList.remove('shake');
    void el.offsetWidth;
    el.classList.add('shake');
  }

  // ── Fake loading delay ──────────────────────────────
  function setLoading(btnId, on) {
    const btn = document.getElementById(btnId);
    on ? btn.classList.add('loading') : btn.classList.remove('loading');
  }

  // ── LOGIN ───────────────────────────────────────────
  function handleLogin() {
    clearErrors();
    const email = document.getElementById('login-email').value.trim();
    const pw    = document.getElementById('login-password').value;
    let valid = true;

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showErr('login-email', 'login-email-err'); valid = false;
    }
    if (!pw) {
      showErr('login-password', 'login-pw-err'); valid = false;
    }
    if (!valid) return;

    setLoading('btn-login', true);

    setTimeout(() => {
      setLoading('btn-login', false);

      // Check predefined + registered users
      const allUsers = [...VALID_USERS, ...registeredUsers];
      const match = allUsers.find(u => u.email.toLowerCase() === email.toLowerCase() && u.password === pw);

      if (match) {
        // Success — redirect or show welcome
        document.querySelector('.card').innerHTML = `
          <div style="text-align:center; padding: 20px 0; animation: fadeUp 0.5s ease both;">
            <div style="font-size:56px; margin-bottom:18px; animation: popIn 0.5s cubic-bezier(0.34,1.56,0.64,1) both;">🎉</div>
            <div style="font-family:'Playfair Display',serif; font-size:24px; font-weight:700; margin-bottom:8px;">Welcome back!</div>
            <div style="font-size:13px; color:var(--muted); line-height:1.7; margin-bottom:6px;">Signed in as <strong style="color:var(--amber)">${match.email}</strong></div>
            <div style="font-size:12px; color:var(--muted); text-transform:uppercase; letter-spacing:1.5px; margin-bottom:28px;">${match.role}</div>
            <div style="font-size:13px; color:var(--muted);">Redirecting to dashboard…</div>
          </div>
        `;
      } else {
        // Wrong credentials
        showAlert('login-alert', 'login-alert-msg', '❌ Invalid email or password. Please check your credentials and try again.');
        document.getElementById('login-password').value = '';
        document.getElementById('login-password').classList.add('error-field');
        document.getElementById('login-email').classList.add('error-field');
      }
    }, 900);
  }

  // ── REGISTER ────────────────────────────────────────
  function handleRegister() {
    clearErrors();
    const name  = document.getElementById('reg-name').value.trim();
    const email = document.getElementById('reg-email').value.trim();
    const role  = document.getElementById('reg-role').value;
    const pw    = document.getElementById('reg-pw').value;
    const pw2   = document.getElementById('reg-pw2').value;
    let valid = true;

    if (!name) { showErr('reg-name', 'reg-name-err'); valid = false; }
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showErr('reg-email', 'reg-email-err'); valid = false; }
    if (!role) { showErr('reg-role', 'reg-role-err'); valid = false; }
    if (!pw || pw.length < 8) { showErr('reg-pw', 'reg-pw-err'); valid = false; }
    if (pw !== pw2) { showErr('reg-pw2', 'reg-pw2-err'); valid = false; }

    // Check duplicate email
    const allUsers = [...VALID_USERS, ...registeredUsers];
    if (valid && allUsers.find(u => u.email.toLowerCase() === email.toLowerCase())) {
      showAlert('reg-alert', 'reg-alert-msg', '⚠️ An account with this email already exists.');
      showErr('reg-email', 'reg-email-err');
      document.getElementById('reg-email-err').textContent = 'This email is already registered.';
      return;
    }

    if (!valid) {
      showAlert('reg-alert', 'reg-alert-msg', '⚠️ Please fix the highlighted fields before continuing.');
      return;
    }

    setLoading('btn-register', true);

    setTimeout(() => {
      setLoading('btn-register', false);
      // Save to simulated DB
      registeredUsers.push({ email, password: pw, role, name });
      // Show success
      document.getElementById('reg-form-wrap').style.display = 'none';
      document.getElementById('reg-success').classList.add('show');
    }, 900);
  }

  // ── Forgot Password ──────────────────────────────────
  function showForgot(e) {
    e.preventDefault();
    const email = document.getElementById('login-email').value.trim();
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showAlert('login-alert', 'login-alert-msg', '💡 Enter your email address first, then click Forgot Password.');
      showErr('login-email', 'login-email-err');
      document.getElementById('login-email-err').textContent = 'Enter your email to reset password.';
      return;
    }
    document.getElementById('login-alert-msg').textContent = `📧 Password reset link sent to ${email}`;
    const al = document.getElementById('login-alert');
    al.classList.remove('alert-error'); al.classList.add('alert-success');
    al.classList.add('show');
  }

  // ── Back to login after register ──────────────────────
  function goToLogin() {
    document.getElementById('reg-form-wrap').style.display = '';
    document.getElementById('reg-success').classList.remove('show');
    // Reset reg fields
    ['reg-name','reg-email','reg-pw','reg-pw2'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('reg-role').selectedIndex = 0;
    switchTab('login');
  }

  // Enter key support
  document.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      const loginActive = document.getElementById('view-login').classList.contains('active');
      if (loginActive) handleLogin();
      else handleRegister();
    }
  });


