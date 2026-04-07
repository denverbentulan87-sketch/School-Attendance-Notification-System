<?php
include "includes/db.php";

if(isset($_POST['register'])){

    // Get and sanitize user input
    $fullname = trim($_POST['fullname']); 
    $email = trim($_POST['email']);       
    $role = $_POST['role'];               
    $password = $_POST['password'];       
    $confirm = $_POST['confirm_password']; 


    // Check for empty fields
    if(empty($fullname) || empty($email) || empty($role) || empty($password) || empty($confirm)){
        echo "All fields are required.";
        exit();
    }

    if($password !== $confirm){
        echo "Passwords do not match.";
        exit();
    }

    // Check if email is already registered
    $check = "SELECT * FROM users WHERE email=?";           
    $stmt = mysqli_prepare($conn, $check);                  
    mysqli_stmt_bind_param($stmt, "s", $email);             
    mysqli_stmt_execute($stmt);                              
    $result = mysqli_stmt_get_result($stmt);               

    if(mysqli_num_rows($result) > 0){
        echo "Email already registered.";
        exit();
    }
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user into database
    $sql = "INSERT INTO users (fullname, email, role, password) VALUES (?, ?, ?, ?)"; 
    $stmt = mysqli_prepare($conn, $sql); // Prepare the statement
    mysqli_stmt_bind_param($stmt, "ssss", $fullname, $email, $role, $hashed); 

    // Execute insertion
    if(mysqli_stmt_execute($stmt)){
        header("Location: index.php?success=registered");
        exit();
    }else{
        echo "Registration failed. Please try again.";
    }

}
?>