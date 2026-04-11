<?php
session_start();
include 'includes/db.php';

$student_id = $_SESSION['student_id'];

$count = $conn->query("
    SELECT COUNT(*) as total 
    FROM notifications 
    WHERE student_id='$student_id' AND is_read=0
")->fetch_assoc()['total'];

echo $count;