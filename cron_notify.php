<?php
// 15 9 * * * php /var/www/html/cron_notify.php

include "includes/db.php";
include "includes/mailer.php";

$today = date('Y-m-d');

// Fixed: student_id and DATE(date_added) instead of user_id and scan_date
$sql = "
    SELECT u.id, u.fullname, u.parent_email 
    FROM users u
    LEFT JOIN attendance a 
        ON u.id = a.student_id 
        AND DATE(a.date_added) = ?
    WHERE u.role = 'student' AND a.id IS NULL
";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $today);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    if (!empty($row['parent_email'])) {
        notify_parent_absent($row['parent_email'], $row['fullname'], $today);
        echo "Notified parent of: " . $row['fullname'] . "\n";
    }
}
?>