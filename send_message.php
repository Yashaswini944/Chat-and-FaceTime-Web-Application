<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_POST['receiver_id']) || !isset($_POST['message'])) {
    echo "Error: Invalid request.";
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'];
$message = $_POST['message'];

// Insert message into the database
$stmt = $pdo->prepare("INSERT INTO chats (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->execute([$sender_id, $receiver_id, $message]);

// Return a success response
echo "Message sent successfully!";
?>
