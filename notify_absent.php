<?php
date_default_timezone_set('Asia/Manila');

include "includes/db.php";
include "includes/mailer.php";

// ✅ Only run after 5:00 PM
$cutoff_hour  = 17;
$current_hour = (int) date('G');

if ($current_hour < $cutoff_hour) {
    $run_after = date('h:i A', mktime($cutoff_hour, 0, 0));
    echo "Too early. Absent marking only runs after {$run_after}. Current time: " . date('h:i A') . "\n";
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
    $insert   = "INSERT IGNORE INTO attendance (student_id, scan_date, scan_time, status, date_added)
                 VALUES (?, ?, '00:00:00', 'absent', NOW())";
    $ins_stmt = mysqli_prepare($conn, $insert);
    mysqli_stmt_bind_param($ins_stmt, "is", $student['id'], $today);
    mysqli_stmt_execute($ins_stmt);

    send_absent_notification(
        $student['parent_email'],
        $student['fullname']
    );

    $notified++;
}

echo "Done. Notified {$notified} parent(s) of absent students on {$today}.\n";
?>