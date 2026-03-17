<?php

session_start();
include "includes/db.php";

if(isset($_POST['login'])){

$email = trim($_POST['email']);
$password = $_POST['password'];

# Check if fields are empty
if(empty($email) || empty($password)){
    echo "Please enter email and password.";
    exit();
}

# Check if user exists
$sql = "SELECT * FROM users WHERE email=?";
$stmt = mysqli_prepare($conn,$sql);
mysqli_stmt_bind_param($stmt,"s",$email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if(mysqli_num_rows($result) == 1){

$user = mysqli_fetch_assoc($result);

# Verify hashed password
if(password_verify($password,$user['password'])){

$_SESSION['user_id'] = $user['id'];
$_SESSION['name'] = $user['fullname'];
$_SESSION['role'] = $user['role'];

header("Location: dashboard.php");
exit();

}else{

echo "Incorrect password.";

}

}else{

echo "Account not found.";

}

}
?>