<?php
require_once 'db_connection.php';
session_start();

if (!isset($_SESSION['user_id']) || !isset($_GET['chat_id'])) {
    echo "Error: Invalid session or chat.";
    exit();
}

$user_id = $_SESSION['user_id'];
$chat_user_id = $_GET['chat_id'];

// Fetch the messages between the logged-in user and the selected chat user
$stmt = $pdo->prepare("SELECT * FROM chats WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
$stmt->execute([$user_id, $chat_user_id, $chat_user_id, $user_id]);

$messages = $stmt->fetchAll();

// Output the messages in the same format as in `chat.php`
foreach ($messages as $message) {
    $messageClass = ($message['sender_id'] == $user_id) ? 'sent' : 'received';
    echo '<div class="message ' . $messageClass . '">';
    echo '<p>' . htmlspecialchars($message['message']) . '</p>';
    echo '</div>';
}
?>
