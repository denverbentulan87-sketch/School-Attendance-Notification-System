<?php
include 'includes/db.php';

// Validate student_id
if (!isset($_GET['student_id']) || empty($_GET['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required.']);
    exit;
}

$student_id = intval($_GET['student_id']); // Sanitize input

$data = $conn->query("
    SELECT s.name, p.email, a.status, a.date_added
    FROM students s
    JOIN parents p ON s.parent_id = p.id
    JOIN attendance a ON a.student_id = s.id
    WHERE s.id = '$student_id'
    ORDER BY a.date_added DESC
    LIMIT 1
")->fetch_assoc();

// Check if data exists
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No attendance record found for this student.']);
    exit;
}

// Check if email exists
if (empty($data['email'])) {
    echo json_encode(['success' => false, 'message' => 'No parent email found for this student.']);
    exit;
}

$to      = $data['email'];
$subject = "Attendance Alert";
$message = "Student: " . $data['name'] . "\nStatus: " . $data['status'] . "\nDate: " . $data['date_added'];
$headers = "From: no-reply@sans.edu\r\nContent-Type: text/plain; charset=UTF-8";

$sent = mail($to, $subject, $message, $headers);

if ($sent) {
    echo json_encode(['success' => true, 'message' => 'Notification Sent to ' . $data['email'] . '!']);
} else {
    // mail() fails on localhost — show a friendly message instead of crashing
    echo json_encode([
        'success' => false,
        'message' => 'Email could not be sent. Mail server may not be configured on localhost. Record found for: ' . $data['name']
    ]);
}
?>