<?php
/**
 * register.php — School Attendance Notification System
 *
 * EMAIL EXISTENCE VERIFICATION FLOW:
 *  Step 1 (POST register):
 *      Validate form → Generate OTPs → Send to email(s) → Store in session → Redirect to otp_verify.php
 *      If send_otp_email() fails (bounce/bad inbox), we reject immediately.
 *
 *  Step 2 (otp_verify.php):
 *      User types the OTP(s) they received → POST back here with action=verify_otp
 *
 *  Step 3 (action=complete_registration):
 *      OTPs confirmed → Insert into DB → Send QR → Create parent account
 */

session_start();
include "includes/db.php";
include "includes/mailer.php";

// ── Helpers ────────────────────────────────────────────────────────────────

function redirect_error(string $msg, string $page = 'index.php'): never {
    header("Location: {$page}?error=" . urlencode($msg));
    exit();
}

function validate_gmail_format(string $email): string|true {
    $email = strtolower(trim($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "\"$email\" is not a valid email format.";
    }

    $domain = substr(strrchr($email, "@"), 1);

    if ($domain !== 'gmail.com') {
        return "Only Gmail accounts (@gmail.com) are accepted. \"$email\" is not a Gmail address.";
    }

    if (!checkdnsrr($domain, 'MX')) {
        return "The domain \"$domain\" does not have valid mail server records.";
    }

    return true;
}

function generate_otp(): string {
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

// ── STEP 1: Validate + send OTPs ──────────────────────────────────────────

if (isset($_POST['register'])) {

    $fullname     = trim($_POST['fullname'] ?? '');
    $email        = strtolower(trim($_POST['email'] ?? ''));
    $role         = trim($_POST['role'] ?? '');
    $password     = $_POST['password'] ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';
    $parent_email = strtolower(trim($_POST['parent_email'] ?? ''));

    if (empty($fullname) || empty($email) || empty($role) || empty($password) || empty($confirm)) {
        redirect_error("All fields are required.");
    }

    $fmt = validate_gmail_format($email);
    if ($fmt !== true) {
        redirect_error("Account email error: " . $fmt);
    }

    if ($role === 'student') {
        if (empty($parent_email)) {
            redirect_error("A parent Gmail address is required for student accounts.");
        }
        $pfmt = validate_gmail_format($parent_email);
        if ($pfmt !== true) {
            redirect_error("Parent email error: " . $pfmt);
        }
        if ($email === $parent_email) {
            redirect_error("The parent Gmail must be different from the student Gmail.");
        }
    }

    if ($password !== $confirm) {
        redirect_error("Passwords do not match.");
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        redirect_error("This Gmail address is already registered.");
    }

    $otp_user   = generate_otp();
    $otp_parent = ($role === 'student') ? generate_otp() : null;
    $otp_expiry = time() + 600;

    $_SESSION['pending_registration'] = [
        'fullname'        => $fullname,
        'email'           => $email,
        'role'            => $role,
        'password'        => password_hash($password, PASSWORD_DEFAULT),
        'parent_email'    => $parent_email,
        'otp_user'        => $otp_user,
        'otp_parent'      => $otp_parent,
        'otp_expiry'      => $otp_expiry,
        'verified_user'   => false,
        'verified_parent' => ($role !== 'student'),
    ];

    $sent_user = send_otp_email($email, $fullname, $otp_user);
    if (!$sent_user) {
        unset($_SESSION['pending_registration']);
        redirect_error(
            "We could not deliver a verification code to $email. " .
            "Please double-check that this is a real, active Gmail account."
        );
    }

    if ($role === 'student' && !empty($parent_email)) {
        $sent_parent = send_otp_email($parent_email, "Parent of $fullname", $otp_parent);
        if (!$sent_parent) {
            unset($_SESSION['pending_registration']);
            redirect_error(
                "We could not deliver a verification code to the parent email $parent_email. " .
                "Please make sure it is a real, active Gmail address."
            );
        }
    }

    header("Location: otp_verify.php");
    exit();
}

// ── STEP 2: Verify OTPs submitted from otp_verify.php ─────────────────────

if (($_POST['action'] ?? '') === 'verify_otp') {

    if (empty($_SESSION['pending_registration'])) {
        redirect_error("Session expired. Please register again.");
    }

    $reg = &$_SESSION['pending_registration'];

    if (time() > $reg['otp_expiry']) {
        unset($_SESSION['pending_registration']);
        redirect_error("Verification codes have expired. Please register again.");
    }

    $otp_user_input   = trim($_POST['otp_user'] ?? '');
    $otp_parent_input = trim($_POST['otp_parent'] ?? '');

    if (!$reg['verified_user']) {
        if ($otp_user_input !== $reg['otp_user']) {
            redirect_error("Invalid verification code for your email.", "otp_verify.php");
        }
        $reg['verified_user'] = true;
    }

    if ($reg['role'] === 'student' && !$reg['verified_parent']) {
        if ($otp_parent_input !== $reg['otp_parent']) {
            redirect_error("Invalid verification code for the parent email.", "otp_verify.php");
        }
        $reg['verified_parent'] = true;
    }

    header("Location: register.php?action=complete_registration");
    exit();
}

// ── STEP 3: Complete registration ─────────────────────────────────────────

if (($_GET['action'] ?? '') === 'complete_registration') {

    if (empty($_SESSION['pending_registration'])) {
        redirect_error("Session expired. Please register again.");
    }

    $reg = $_SESSION['pending_registration'];

    if (!$reg['verified_user'] || !$reg['verified_parent']) {
        redirect_error("Email verification incomplete.", "otp_verify.php");
    }

    $fullname     = $reg['fullname'];
    $email        = $reg['email'];
    $role         = $reg['role'];
    $hashed       = $reg['password'];
    $parent_email = $reg['parent_email'];

    // ── Generate & save QR locally for students ──────────────────────────
    $qr_token = null;
    $qr_code  = null;

    if ($role === 'student') {
        $qr_token = bin2hex(random_bytes(16));
        $scan_url = "http://localhost/School-Attendance-Notification-System/scan.php?token=" . $qr_token;
        $api_url  = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($scan_url);

        // Save QR image locally so my_qr.php can always display it reliably
        $qr_dir = __DIR__ . '/qrcodes/';
        if (!is_dir($qr_dir)) {
            mkdir($qr_dir, 0755, true);
        }
        $qr_filename = 'qr_' . $qr_token . '.png';
        $qr_img_data = file_get_contents($api_url);
        file_put_contents($qr_dir . $qr_filename, $qr_img_data);

        $qr_code = 'qrcodes/' . $qr_filename; // relative path stored in DB
    }

    // Insert user
    $sql  = "INSERT INTO users (fullname, email, role, password, parent_email, qr_code, qr_token)
             VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss",
        $fullname, $email, $role, $hashed,
        $parent_email, $qr_code, $qr_token
    );

    if (!$stmt->execute()) {
        redirect_error("Registration failed. Please try again.");
    }

    // ── Send QR email using local file path so image is embedded, not hotlinked ──
    if ($role === 'student') {
        $qr_local_path = __DIR__ . '/' . $qr_code; // absolute path to saved PNG
        send_qr_email($email, $fullname, $qr_local_path);
    }

    // Auto-create parent account if not yet registered
    if ($role === 'student' && !empty($parent_email)) {
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->bind_param("s", $parent_email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows === 0) {
            $temp_pass       = bin2hex(random_bytes(6));
            $parent_hash     = password_hash($temp_pass, PASSWORD_DEFAULT);
            $parent_fullname = "Parent of " . $fullname;

            $psql = "INSERT INTO users (fullname, email, role, password) VALUES (?, ?, 'parent', ?)";
            $pst  = $conn->prepare($psql);
            $pst->bind_param("sss", $parent_fullname, $parent_email, $parent_hash);
            $pst->execute();

            if (function_exists('send_parent_welcome_email')) {
                send_parent_welcome_email($parent_email, $parent_fullname, $fullname, $temp_pass);
            }
        }
    }

    unset($_SESSION['pending_registration']);
    header("Location: index.php?success=registered");
    exit();
}
?>