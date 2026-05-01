<?php
session_start();
include "includes/db.php";
include "includes/mailer.php";

if (isset($_POST['register'])) {

    $fullname     = trim($_POST['fullname']);
    $email        = trim($_POST['email']);
    $role         = $_POST['role'];
    $password     = $_POST['password'];
    $confirm      = $_POST['confirm_password'];
    $parent_email = isset($_POST['parent_email']) ? trim($_POST['parent_email']) : '';

    // 1. Check empty fields
    if (empty($fullname) || empty($email) || empty($role) || empty($password) || empty($confirm)) {
        header("Location: index.php?error=All+fields+are+required");
        exit();
    }

    // 2. Student must have parent email
    if ($role === 'student' && empty($parent_email)) {
        header("Location: index.php?error=Parent+Gmail+is+required+for+student+accounts");
        exit();
    }

    // 3. Password match
    if ($password !== $confirm) {
        header("Location: index.php?error=Passwords+do+not+match");
        exit();
    }

    // 4. Check duplicate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        header("Location: index.php?error=Email+already+registered");
        exit();
    }

    // 5. Hash password
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // 6. Generate QR for students only
    $qr_token = null;
    $qr_code  = null;

    if ($role === 'student') {
        $qr_token = bin2hex(random_bytes(16));
        $scan_url = "http://localhost/School-Attendance-Notification-System/scan.php?token=" . $qr_token;
        $qr_code  = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($scan_url);
    }

    // 7. Insert user into database
    $sql  = "INSERT INTO users (fullname, email, role, password, parent_email, qr_code, qr_token) 
             VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss",
        $fullname, $email, $role, $hashed,
        $parent_email, $qr_code, $qr_token
    );

    if (!$stmt->execute()) {
        header("Location: index.php?error=Registration+failed.+Please+try+again.");
        exit();
    }

    // 8. Send QR to student
    if ($role === 'student') {
        send_qr_email($email, $fullname, $qr_code);
    }

    // 9. Auto-create parent account if parent_email given and not yet registered
    if ($role === 'student' && !empty($parent_email)) {

        $chk = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $chk->bind_param("s", $parent_email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows === 0) {
            // Generate a temporary password for the parent
            $temp_pass   = bin2hex(random_bytes(6)); // 12-char hex
            $parent_hash = password_hash($temp_pass, PASSWORD_DEFAULT);

            // Derive parent name from student name: "Parent of <student>"
            $parent_fullname = "Parent of " . $fullname;

            $psql = "INSERT INTO users (fullname, email, role, password) VALUES (?, ?, 'parent', ?)";
            $pst  = $conn->prepare($psql);
            $pst->bind_param("sss", $parent_fullname, $parent_email, $parent_hash);
            $pst->execute();

            // Notify parent of their new account
            if (function_exists('send_parent_welcome_email')) {
                send_parent_welcome_email($parent_email, $parent_fullname, $fullname, $temp_pass);
            }
        }
        // If parent account already exists, they are already linked via parent_email column on student
    }

    header("Location: index.php?success=registered");
    exit();
}
?>