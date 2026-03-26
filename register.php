<?php
// Include the database connection
include "includes/db.php";

// Check if the registration form has been submitted
if(isset($_POST['register'])){

    // Get and sanitize user input
    $fullname = trim($_POST['fullname']); 
    $email = trim($_POST['email']);       
    $role = $_POST['role'];               
    $password = $_POST['password'];       
    $confirm = $_POST['confirm_password']; 


    // Check for empty fields
    if(empty($fullname) || empty($email) || empty($role) || empty($password) || empty($confirm)){
        // If any field is empty, show error and stop execution
        echo "All fields are required.";
        exit();
    }

    // Check if passwords match

    if($password !== $confirm){
        // If password and confirm password do not match, show error and stop execution
        echo "Passwords do not match.";
        exit();
    }

    // Check if email is already registered
    $check = "SELECT * FROM users WHERE email=?";           // SQL query with placeholder
    $stmt = mysqli_prepare($conn, $check);                  // Prepare the statement
    mysqli_stmt_bind_param($stmt, "s", $email);             // Bind email to placeholder
    mysqli_stmt_execute($stmt);                              // Execute query
    $result = mysqli_stmt_get_result($stmt);               // Get the result set

    if(mysqli_num_rows($result) > 0){
        // If email already exists in the database, show error and stop
        echo "Email already registered.";
        exit();
    }

    // Hash the password
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user into database
    $sql = "INSERT INTO users (fullname, email, role, password) VALUES (?, ?, ?, ?)"; // SQL insert query
    $stmt = mysqli_prepare($conn, $sql); // Prepare the statement
    mysqli_stmt_bind_param($stmt, "ssss", $fullname, $email, $role, $hashed); // Bind user input

    // Execute insertion
    if(mysqli_stmt_execute($stmt)){
        // If insertion successful, redirect to login page with success message
        header("Location: index.php?success=registered");
        exit();
    }else{
        // If insertion fails, show error
        echo "Registration failed. Please try again.";
    }

}
?>