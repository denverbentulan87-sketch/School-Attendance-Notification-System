<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ✅ ABSOLUTE PATH FIX (SUPER SAFE)
$path = $_SERVER['DOCUMENT_ROOT'] . "/School-Attendance-&-Notification-System/phpqrcode/qrlib.php";

if (!file_exists($path)) {
    die("QR LIB NOT FOUND: " . $path);
}

include $path;

// CHECK ID
if (!isset($_GET['id'])) {
    die("No ID");
}

$id = intval($_GET['id']);

// QR CONTENT
$data = "http://localhost/School-Attendance-&-Notification-System/mark_attendance.php?id=" . $id;

// OUTPUT
header('Content-Type: image/png');
QRcode::png($data);
exit();