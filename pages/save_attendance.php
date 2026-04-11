<?php
include 'includes/db.php';

if(isset($_POST['student_id'], $_POST['status'], $_POST['date'])){

    $student_id = $_POST['student_id'];
    $status = $_POST['status'];
    $date = $_POST['date'];

    // Check if already exists
    $check = $conn->prepare("SELECT id FROM attendance WHERE student_id=? AND date_added=?");
    $check->bind_param("is", $student_id, $date);
    $check->execute();
    $result = $check->get_result();

    if($result->num_rows == 0){

        // Insert attendance
        $stmt = $conn->prepare("
            INSERT INTO attendance (student_id, date_added, status)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $student_id, $date, $status);
        $stmt->execute();

        // Send notification
        $msg = "Your child was marked $status on $date";

        $notify = $conn->prepare("
            INSERT INTO notifications (student_id, message)
            VALUES (?, ?)
        ");
        $notify->bind_param("is", $student_id, $msg);
        $notify->execute();

    } else {
        // Update instead of duplicate
        $update = $conn->prepare("
            UPDATE attendance SET status=? 
            WHERE student_id=? AND date_added=?
        ");
        $update->bind_param("sis", $status, $student_id, $date);
        $update->execute();
    }

    echo "success";
}