<?php

$host     = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASS');
$database = getenv('DB_NAME');
$port     = getenv('DB_PORT') ?: '3306';

$conn = mysqli_connect($host, $username, $password, $database, (int)$port);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_error());
}
?>