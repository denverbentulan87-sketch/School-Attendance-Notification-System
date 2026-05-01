<?php
session_start();
include "includes/db.php";

if (isset($_POST['login'])) {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: index.php?error=Please+fill+in+all+fields");
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: index.php?error=Invalid+email+or+password");
        exit();
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['password'])) {
        header("Location: index.php?error=Invalid+email+or+password");
        exit();
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['name']    = $user['fullname'];
    $_SESSION['role']    = $user['role'];

    // Role-based redirect
    switch ($user['role']) {
        case 'admin':
            header("Location: admin_dashboard.php");
            break;
        case 'teacher':
            header("Location: teacher_dashboard.php");
            break;
        case 'student':
            header("Location: student_dashboard.php");
            break;
        case 'parent':
            header("Location: parent_dashboard.php");
            break;
        default:
            header("Location: index.php?error=Unknown+role");
            break;
    }
    exit();
}
?>