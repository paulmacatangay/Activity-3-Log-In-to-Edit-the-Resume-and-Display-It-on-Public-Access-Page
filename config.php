<?php
// Database configuration
$host = 'localhost';
$dbname = 'portfolio_db';
$username = 'postgres';
$password = '1234'; 

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

