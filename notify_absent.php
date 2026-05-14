<?php
date_default_timezone_set('Asia/Manila');

include "includes/db.php";
include "includes/mailer.php";

// ✅ Only run after 5:00 PM
$cutoff_hour  = 17;
// ✅ Only run between 5:00 PM and 11:59 PM — never past midnight to avoid next-day issues
$current_hour = (int) date('G');
if ($current_hour < 17 || $current_hour >= 24) {
    echo "Script can only run between 5:00 PM and 11:59 PM. Current time: " . date('h:i A') . "\n";
    exit();
}

// ✅ Only run on weekdays (Monday–Friday)
$day_of_week = (int) date('N'); // 1=Mon, 7=Sun
if ($day_of_week >= 6) {
    echo "No school on weekends. Skipping.\n";
    exit();
}
$today        = date('Y-m-d');
$date_display = date('F j, Y');

// Get all active students who have NO attendance record for today
$sql = "
    SELECT u.id, u.fullname, u.parent_email
    FROM users u
    WHERE u.role = 'student'
      AND u.parent_email IS NOT NULL
      AND u.parent_email != ''
      AND u.id NOT IN (
          SELECT student_id FROM attendance WHERE scan_date = ?
      )
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $today);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$notified = 0;

while ($student = mysqli_fetch_assoc($result)) {
    // Insert absent attendance record
   $insert = "INSERT INTO attendance (student_id, scan_date, scan_time, status, date_added)
           VALUES (?, ?, '17:00:00', 'absent', NOW())
           ON DUPLICATE KEY UPDATE 
               scan_time = IF(scan_time = '00:00:00', '17:00:00', scan_time),
               status = 'absent'";

    $ins_stmt = mysqli_prepare($conn, $insert);
    mysqli_stmt_bind_param($ins_stmt, "is", $student['id'], $today);
    mysqli_stmt_execute($ins_stmt);

    // Send the email
    send_absent_notification(
        $student['parent_email'],
        $student['fullname']
    );

    // ✅ ADD THIS — log it to notifications table so dashboard updates
    $notif_msg  = "Your child {$student['fullname']} was marked ABSENT on {$date_display}. No QR scan was recorded.";
    $log_stmt   = mysqli_prepare($conn,
        "INSERT INTO notifications (student_id, message, sender, is_read, created_at)
         VALUES (?, ?, 'system', 0, NOW())"
    );
    mysqli_stmt_bind_param($log_stmt, "is", $student['id'], $notif_msg);
    mysqli_stmt_execute($log_stmt);

    $notified++;
}

echo "Done. Notified {$notified} parent(s) of absent students on {$today}.\n";
?>