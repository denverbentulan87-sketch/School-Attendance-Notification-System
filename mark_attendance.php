<?php
include 'includes/db.php';

if(isset($_GET['id'])){
    $student_id = intval($_GET['id']);
    $date = date("Y-m-d");

    // Check if already marked
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE student_id=? AND date_added=?");
    $stmt->bind_param("is", $student_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows == 0){

        // Insert attendance
        $stmt = $conn->prepare("INSERT INTO attendance (student_id, date_added, status, created_at) VALUES (?, ?, 'present', NOW())");
        $stmt->bind_param("is", $student_id, $date);
        $stmt->execute();

        echo "<h2 style='color:green;text-align:center;'>✅ Attendance Marked</h2>";
    } else {
        echo "<h2 style='color:orange;text-align:center;'>⚠ Already Marked Today</h2>";
    }

} else {
    echo "Invalid QR";
}
?>