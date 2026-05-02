<?php
/**
 * otp_verify.php
 * Shows OTP input fields for the user (and parent, if student).
 * Submits to register.php with action=verify_otp
 */
session_start();

if (empty($_SESSION['pending_registration'])) {
    header("Location: index.php?error=" . urlencode("Session expired. Please register again."));
    exit();
}

$reg          = $_SESSION['pending_registration'];
$is_student   = ($reg['role'] === 'student');
$email        = htmlspecialchars($reg['email']);
$parent_email = htmlspecialchars($reg['parent_email'] ?? '');
$error        = htmlspecialchars($_GET['error'] ?? '');

// Calculate time remaining (for display)
$seconds_left = max(0, $reg['otp_expiry'] - time());
$minutes_left = ceil($seconds_left / 60);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email — School Attendance System</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,.10);
            padding: 40px 36px;
            max-width: 480px;
            width: 100%;
        }

        .icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 16px;
        }

        h1 {
            font-size: 22px;
            font-weight: 700;
            color: #1a202c;
            text-align: center;
            margin-bottom: 8px;
        }

        .subtitle {
            font-size: 14px;
            color: #718096;
            text-align: center;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        .email-badge {
            display: inline-block;
            background: #ebf8ff;
            color: #2b6cb0;
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 13px;
            font-weight: 600;
        }

        .section {
            margin-bottom: 24px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #4a5568;
            margin-bottom: 8px;
        }

        .otp-group {
            display: flex;
            gap: 8px;
            justify-content: center;
        }

        .otp-group input {
            width: 48px;
            height: 56px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 22px;
            font-weight: 700;
            text-align: center;
            color: #1a202c;
            outline: none;
            transition: border-color .2s;
        }

        .otp-group input:focus {
            border-color: #4299e1;
        }

        /* Hidden single input that holds the full OTP value */
        .otp-hidden { display: none; }

        .divider {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 24px 0;
        }

        .error-msg {
            background: #fff5f5;
            border: 1px solid #fc8181;
            color: #c53030;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 14px;
            margin-bottom: 20px;
        }

        .timer {
            font-size: 13px;
            color: #a0aec0;
            text-align: center;
            margin-bottom: 20px;
        }

        .timer span {
            font-weight: 700;
            color: #e53e3e;
        }

        button[type=submit] {
            width: 100%;
            padding: 14px;
            background: #3182ce;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: background .2s;
        }

        button[type=submit]:hover { background: #2c5282; }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 16px;
            font-size: 14px;
            color: #718096;
            text-decoration: none;
        }

        .back-link:hover { color: #2d3748; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">📧</div>
    <h1>Check Your Email<?= $is_student ? 's' : '' ?></h1>
    <p class="subtitle">
        We sent a 6-digit verification code to
        <span class="email-badge"><?= $email ?></span><?php if ($is_student): ?>
        and <span class="email-badge"><?= $parent_email ?></span><?php endif; ?>.
        Enter the code<?= $is_student ? 's' : '' ?> below to verify your account<?= $is_student ? 's' : '' ?>.
    </p>

    <?php if ($error): ?>
        <div class="error-msg">⚠️ <?= $error ?></div>
    <?php endif; ?>

    <div class="timer">
        Codes expire in <span id="countdown"><?= $minutes_left ?> min</span>
    </div>

    <form method="POST" action="register.php" id="otpForm">
        <input type="hidden" name="action" value="verify_otp">

        <!-- User OTP -->
        <div class="section">
            <label>Your verification code (sent to <?= $email ?>)</label>
            <div class="otp-group" data-target="otp_user">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            </div>
            <input type="hidden" name="otp_user" id="otp_user" class="otp-hidden">
        </div>

        <?php if ($is_student): ?>
        <hr class="divider">

        <!-- Parent OTP -->
        <div class="section">
            <label>Parent verification code (sent to <?= $parent_email ?>)</label>
            <div class="otp-group" data-target="otp_parent">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
                <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]">
            </div>
            <input type="hidden" name="otp_parent" id="otp_parent" class="otp-hidden">
        </div>
        <?php endif; ?>

        <button type="submit">✅ Verify & Complete Registration</button>
    </form>

    <a href="index.php" class="back-link">← Start over</a>
</div>

<script>
// ── OTP box navigation (auto-advance, backspace, paste) ──────────────────
document.querySelectorAll('.otp-group').forEach(group => {
    const inputs = group.querySelectorAll('input');
    const targetId = group.dataset.target;
    const hidden   = document.getElementById(targetId);

    function updateHidden() {
        hidden.value = Array.from(inputs).map(i => i.value).join('');
    }

    inputs.forEach((input, idx) => {
        input.addEventListener('input', e => {
            // Allow only digits
            input.value = input.value.replace(/\D/g, '').slice(-1);
            if (input.value && idx < inputs.length - 1) inputs[idx + 1].focus();
            updateHidden();
        });

        input.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !input.value && idx > 0) {
                inputs[idx - 1].focus();
            }
        });

        // Handle paste (e.g. paste full 6-digit code)
        input.addEventListener('paste', e => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData)
                .getData('text').replace(/\D/g, '').slice(0, 6);
            pasted.split('').forEach((ch, i) => {
                if (inputs[i]) inputs[i].value = ch;
            });
            inputs[Math.min(pasted.length, inputs.length - 1)].focus();
            updateHidden();
        });
    });
});

// ── Validate hidden fields before submit ─────────────────────────────────
document.getElementById('otpForm').addEventListener('submit', e => {
    const userOtp = document.getElementById('otp_user').value;
    if (userOtp.length !== 6) {
        e.preventDefault();
        alert('Please enter the full 6-digit code for your email.');
        return;
    }
    <?php if ($is_student): ?>
    const parentOtp = document.getElementById('otp_parent').value;
    if (parentOtp.length !== 6) {
        e.preventDefault();
        alert('Please enter the full 6-digit code for the parent email.');
        return;
    }
    <?php endif; ?>
});

// ── Countdown timer ──────────────────────────────────────────────────────
let remaining = <?= $seconds_left ?>;
const countdownEl = document.getElementById('countdown');

const interval = setInterval(() => {
    remaining--;
    if (remaining <= 0) {
        clearInterval(interval);
        countdownEl.textContent = 'EXPIRED';
        countdownEl.style.color = '#e53e3e';
        document.querySelector('button[type=submit]').disabled = true;
        document.querySelector('button[type=submit]').textContent = 'Code expired — please register again';
        return;
    }
    const m = Math.floor(remaining / 60);
    const s = remaining % 60;
    countdownEl.textContent = m > 0
        ? `${m}m ${s.toString().padStart(2,'0')}s`
        : `${s}s`;
}, 1000);
</script>
</body>
</html>