<?php
date_default_timezone_set('Asia/Manila');

session_start();
include "includes/db.php";
include "includes/mailer.php";

header('Content-Type: application/json');

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid QR code.']);
    exit();
}

$sql  = "SELECT * FROM users WHERE qr_token = ? AND role = 'student'";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $token);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$student = mysqli_fetch_assoc($result);

if (!$student) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or unrecognized QR code.']);
    exit();
}

$today    = date('Y-m-d');
$now_time = date('H:i:s');
$now_full = date('Y-m-d H:i:s');

// Check duplicate scan
$check = "SELECT * FROM attendance WHERE student_id = ? AND scan_date = ? LIMIT 1";
$stmt  = mysqli_prepare($conn, $check);
mysqli_stmt_bind_param($stmt, "is", $student['id'], $today);
mysqli_stmt_execute($stmt);
$existing = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($existing) > 0) {
    $row        = mysqli_fetch_assoc($existing);
    $scanned_at = date('h:i A', strtotime($row['scan_time']));
    echo json_encode([
        'status'       => 'duplicate',
        'student_name' => $student['fullname'],
        'scanned_at'   => $scanned_at,
    ]);
    exit();
}

// Determine attendance status
$now_minutes   = (int)date('H') * 60 + (int)date('i');
$present_start = 8 * 60;       // 8:00 AM
$present_end   = 8 * 60 + 10;  // 8:10 AM
$late_end      = 9 * 60;       // 9:00 AM

if ($now_minutes >= $present_start && $now_minutes <= $present_end) {
    $status = 'present';
} elseif ($now_minutes > $present_end && $now_minutes <= $late_end) {
    $status = 'late';
} else {
    $status = 'absent';
}

// Insert attendance record
$insert = "INSERT INTO attendance (student_id, scan_date, scan_time, status, date_added)
           VALUES (?, ?, ?, ?, ?)";
$stmt   = mysqli_prepare($conn, $insert);
mysqli_stmt_bind_param($stmt, "issss", $student['id'], $today, $now_time, $status, $now_full);
mysqli_stmt_execute($stmt);

// Send email notification to parent for present or late
if (!empty($student['parent_email']) && in_array($status, ['present', 'late'])) {
    send_attendance_notification(
        $student['parent_email'],
        $student['fullname'],
        $status,
        $now_time
    );
}

echo json_encode([
    'status'       => 'success',
    'student_name' => $student['fullname'],
    'att_status'   => $status,
]);
?>