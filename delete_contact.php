<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['delete'])) {
    header("Location: chat.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$contact_id = $_GET['delete'];

// Remove contact from the database
$stmt = $pdo->prepare("DELETE FROM contacts WHERE (user_id = ? AND contact_id = ?) OR (user_id = ? AND contact_id = ?)");
$stmt->execute([$user_id, $contact_id, $contact_id, $user_id]);

header("Location: chat.php"); // Redirect back to the chat page
exit();
