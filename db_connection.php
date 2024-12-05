<?php
$host = 'localhost';
$port = 4306; // MySQL port
$dbname = 'chat_app';
$username = 'root';
$password = 'Yashu@1234';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
