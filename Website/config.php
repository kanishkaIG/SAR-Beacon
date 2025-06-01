<?php
$host = "sql113.infinityfree.com";
$db = "if0_37585560_sarbeacon";
$user = "if0_37585560";
$pass = "mDK0hrbz2xkt8";

try {
    // Initialize the PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    // Set error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If connection fails, display an error message and stop execution
    die("Connection failed: " . $e->getMessage());
}
?>
