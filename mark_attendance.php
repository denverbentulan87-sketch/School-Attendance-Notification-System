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

// Look up the student by their QR token
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

// Re-declare time variables here (after timezone is set) to ensure accuracy
$today    = date('Y-m-d');
$now_time = date('H:i:s');
$now_full = date('Y-m-d H:i:s');

// Check if already scanned today
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

// -------------------------------------------------------
// Attendance logic:
//   Present : 8:00 AM – 8:10 AM
//   Late    : 8:11 AM – 11:59 AM
//   Rejected: 12:00 PM and beyond (auto_absent.php handles it)
// -------------------------------------------------------
$now_hour    = (int)date('H');
$now_min     = (int)date('i');
$now_minutes = $now_hour * 60 + $now_min;

$present_start = 8 * 60;       // 480 → 8:00 AM
$present_end   = 8 * 60 + 10;  // 490 → 8:10 AM
$late_end      = 12 * 60 - 1;  // 719 → 11:59 AM

if ($now_minutes >= $present_start && $now_minutes <= $present_end) {
    $status = 'present';
} elseif ($now_minutes > $present_end && $now_minutes <= $late_end) {
    $status = 'late';
} else {
    // 12:00 PM or later — scanner closed
    echo json_encode([
        'status'       => 'error',
        'student_name' => $student['fullname'],
        'message'      => 'Scanner closed. This student will be marked absent automatically.',
    ]);
    exit();
}

// Safety net — should never be blank after the checks above
if (empty($status)) {
    $status = 'late';
}

// Insert the attendance record
$insert = "INSERT INTO attendance (student_id, scan_date, scan_time, status, date_added)
           VALUES (?, ?, ?, ?, ?)";
$stmt   = mysqli_prepare($conn, $insert);
mysqli_stmt_bind_param($stmt, "issss", $student['id'], $today, $now_time, $status, $now_full);
mysqli_stmt_execute($stmt);

// Save notification to the notifications table
$notif_msg  = "Your child {$student['fullname']} was marked " . strtoupper($status) . " at " . date('h:i A', strtotime($now_time)) . ".";
$notif_stmt = mysqli_prepare($conn,
    "INSERT INTO notifications (student_id, message, created_at, sender, is_read)
     VALUES (?, ?, ?, 'system', 0)"
);
mysqli_stmt_bind_param($notif_stmt, "iss", $student['id'], $notif_msg, $now_full);
mysqli_stmt_execute($notif_stmt);

// Send present/late notification email to parent
if (!empty($student['parent_email'])) {
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