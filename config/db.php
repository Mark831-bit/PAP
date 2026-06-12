<?php

$dsn = 'mysql:host=localhost;dbname=pap;charset=utf8mb4';
$user = 'root';
$pass = ''; 

$pdo = new PDO($dsn, $user, $pass, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
 
$conn = new mysqli("localhost", "root", "", "pap");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}