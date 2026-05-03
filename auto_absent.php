<?php
/**
 * auto_absent.php
 * ---------------------------------------------------------------
 * Run this via cron every minute:
 *   * * * * * php /path/to/your/project/auto_absent.php
 *
 * At exactly 12:00 PM daily, every student who has no attendance
 * record for today is marked absent and their parent receives
 * an email notification via send_absent_notification().
 * ---------------------------------------------------------------
 */

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';

$today    = date('Y-m-d');
$now_time = date('H:i');
$now_full = date('Y-m-d H:i:s');

// Only run at exactly 12:00 PM
if ($now_time !== '12:00') {
    exit();
}

// ── Guard: don't run twice in the same day ────────────────────────────────
$guard_check = mysqli_query($conn,
    "SELECT id FROM absent_job_log WHERE run_date = '$today' LIMIT 1"
);
if (mysqli_num_rows($guard_check) > 0) {
    exit();
}

// Log that we're running now
mysqli_query($conn,
    "INSERT INTO absent_job_log (run_date, run_at) VALUES ('$today', '$now_full')"
);

// ── Find all students with no attendance record today ─────────────────────
$sql = "
    SELECT u.id, u.fullname, u.parent_email
    FROM   users u
    WHERE  u.role = 'student'
      AND  u.id NOT IN (
               SELECT student_id
               FROM   attendance
               WHERE  scan_date = '$today'
           )
";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    exit();   // everyone already scanned — nothing to do
}

// ── Mark each student absent and send email ───────────────────────────────
while ($student = mysqli_fetch_assoc($result)) {

    $sid = (int) $student['id'];

    // Insert absent record
    $ins = mysqli_prepare($conn,
        "INSERT INTO attendance (student_id, scan_date, scan_time, status, date_added)
         VALUES (?, ?, '12:00:00', 'absent', ?)"
    );
    mysqli_stmt_bind_param($ins, 'iss', $sid, $today, $now_full);
    mysqli_stmt_execute($ins);

    // Send absent notification email to parent
    if (!empty($student['parent_email'])) {
        send_absent_notification(
            $student['parent_email'],
            $student['fullname']
        );
    }
}
?>