<?php
session_start();
include "includes/db.php";

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    die("Invalid QR code.");
}

$sql  = "SELECT * FROM users WHERE qr_token = ? AND role = 'student'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    die("QR code not recognized.");
}

$now   = date('Y-m-d H:i:s');
$today = date('Y-m-d');

// Check if already scanned today
$check = "SELECT * FROM attendance WHERE student_id = ? AND DATE(date_added) = ?";
$stmt  = mysqli_prepare($conn, $check);
mysqli_stmt_bind_param($stmt, "is", $student['id'], $today);
mysqli_stmt_execute($stmt);
$existing = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($existing) > 0) {
    echo "Already marked present today.";
    exit();
}

// Scan window: 7:00 AM - 9:00 AM
$hour   = (int)date('H');
$status = ($hour >= 7 && $hour < 9) ? 'present' : 'absent';

$insert = "INSERT INTO attendance (student_id, status, date_added) VALUES (?, ?, ?)";
$stmt   = mysqli_prepare($conn, $insert);
mysqli_stmt_bind_param($stmt, "iss", $student['id'], $status, $now);
mysqli_stmt_execute($stmt);

echo "✅ Attendance recorded! Status: " . ucfirst($status);
?>