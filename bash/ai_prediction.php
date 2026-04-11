<?php
include 'includes/db.php';

$user_id = $_SESSION['user_id'];

$data = $conn->query("
    SELECT 
        SUM(status='present') as present,
        SUM(status='absent') as absent
    FROM attendance
    WHERE student_id='$user_id'
")->fetch_assoc();

$total = $data['present'] + $data['absent'];
$rate = $total > 0 ? ($data['present'] / $total) * 100 : 0;

if($rate >= 90){
    $risk = "LOW RISK ✅";
} elseif($rate >= 75){
    $risk = "MEDIUM RISK ⚠️";
} else {
    $risk = "HIGH RISK 🚨";
}

echo "<h3>AI Prediction: $risk</h3>";