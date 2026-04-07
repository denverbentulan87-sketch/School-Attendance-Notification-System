<?php

session_start();
include "includes/db.php";

if(isset($_POST['login'])){

    $email = trim($_POST['email']); 
    $password = $_POST['password']; 

    if(empty($email) || empty($password)){
        echo "Please enter email and password.";
        exit();
    }

    $sql = "SELECT * FROM users WHERE email=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) == 1){

        $user = mysqli_fetch_assoc($result);

        if(password_verify($password, $user['password'])){

            // Store session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];

            // 🔥 Role-based redirection
            if($user['role'] == 'admin'){
                header("Location: admin_dashboard.php");
            }
            elseif($user['role'] == 'student'){
                header("Location: student_dashboard.php");
            }
            elseif($user['role'] == 'parent' ){
                header("Location: parent_dashboard.php");
            }
            else{
                echo "Invalid role.";
            }

            exit();

        } else {
            echo "Incorrect password.";
        }

    } else {
        echo "Account not found.";
    }

}
?>