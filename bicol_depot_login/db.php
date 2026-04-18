<?php
// Db config
$host = "localhost";
$dbname = "bicol_depot";
$user = "root"; //phpMyAdmin username
$pass = ""; //phpMyAdmin password

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>