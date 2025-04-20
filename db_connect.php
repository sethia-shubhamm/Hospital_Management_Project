
<?php

$host = "localhost";
$username = "root";  
$password = "root";      
$database = "hospital_management_system";
$conn = mysqli_connect($host, $username, $password, $database);


if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


$db_selected = mysqli_select_db($conn, $database);
if (!$db_selected) {
    
    echo "Database $database does not exist, trying to create it...<br>";
    $sql = "CREATE DATABASE IF NOT EXISTS $database";
    if (mysqli_query($conn, $sql)) {
        echo "Database created successfully<br>";
        mysqli_select_db($conn, $database);
    } else {
        echo "Error creating database: " . mysqli_error($conn) . "<br>";
    }
}
?>