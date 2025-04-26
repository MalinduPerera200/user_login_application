<?php
$db_host = "localhost";         
$db_user = "root";              
$db_pass = "Malindu@2003";       
$db_name = "user_auth_db";     

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

if (!$conn->set_charset("utf8mb4")) {
    printf("Error loading character set utf8mb4: %s\n", $conn->error);
}
?>
