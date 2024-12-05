<?php
session_start();
require_once 'db_connection.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Check if user exists
if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Get the list of users for the contacts sidebar
$stmt_contacts = $pdo->prepare("SELECT * FROM users WHERE id != ?");
$stmt_contacts->execute([$user_id]);
$contacts = $stmt_contacts->fetchAll();

// Fetch messages for a selected user (assuming `chat_id` is passed in the URL)
$chat_user_id = isset($_GET['chat_id']) ? $_GET['chat_id'] : null;
$messages = [];

if ($chat_user_id) {
    // Ensure `created_at` column is used to order messages
    $stmt_messages = $pdo->prepare("SELECT * FROM chats WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
    $stmt_messages->execute([$user_id, $chat_user_id, $chat_user_id, $user_id]);
    $messages = $stmt_messages->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Realtime Chat App</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }

        .chat-container {
            display: flex;
            height: 100vh;
        }

        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: #fff;
            padding: 20px;
            display: flex;
            flex-direction: column;
        }

        .user-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .user-info img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
        }

        .contacts-list ul {
            list-style-type: none;
            padding: 0;
        }

        .contacts-list ul li {
            margin: 10px 0;
        }

        .contacts-list ul li a {
            color: #ecf0f1;
            text-decoration: none;
        }

        .contacts-list ul li a:hover {
            text-decoration: underline;
        }

        .chat-area {
            flex: 1;
            padding: 20px;
            background-color: #fff;
            overflow-y: scroll;
        }

        .chat-header h3 {
            margin: 0;
        }

        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 10px;
            max-width: 60%;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .sent {
            background-color: #2ecc71;
            align-self: flex-end;
            color: white;
            margin-left: auto;
        }

        .received {
            background-color: #ecf0f1;
            align-self: flex-start;
            color: black;
            margin-right: auto;
        }

        .timestamp {
            font-size: 0.8em;
            color: #7f8c8d;
            text-align: right;
            margin-top: 5px;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        textarea {
            width: 100%;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        button {
            padding: 10px;
            background-color: #2ecc71;
            border: none;
            border-radius: 5px;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background-color: #27ae60;
        }

        #callNotification {
            display: none;
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #fff;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        #callMessage {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <!-- Sidebar (Contacts) -->
        <div class="sidebar">
            <div class="user-info">
                <img src="<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile Image">
                <p><?php echo htmlspecialchars($user['first_name']); ?></p>
            </div>
            <div class="contacts-list">
                <ul>
                    <?php foreach ($contacts as $contact): ?>
                        <li><a href="chat.php?chat_id=<?php echo $contact['id']; ?>"><?php echo htmlspecialchars($contact['first_name']); ?></a></li>
                        <!-- Add buttons for voice and video calls -->
                        <a href="call.php?call_type=voice&user_id=<?php echo $contact['id']; ?>" class="btn btn-voice" style="display: inline-block; padding: 10px 20px; background-color: #3498db; color: white; border-radius: 5px; text-align: center; text-decoration: none;">Voice Call</a>
                        <a href="call.php?call_type=video&user_id=<?php echo $contact['id']; ?>" class="btn btn-video" style="display: inline-block; padding: 10px 20px; background-color: #e74c3c; color: white; border-radius: 5px; text-align: center; text-decoration: none;">Video Call</a>
                    <?php endforeach; ?>
                </ul>
            </div>
            <!-- Logout Button -->
            <div class="logout">
                <a href="logout.php" class="btn btn-logout" style="color: white; background-color: #e74c3c; padding: 10px; border-radius: 5px; text-align: center; margin-top: 20px; display: block;">Logout</a>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <?php if ($chat_user_id): ?>
                <div class="chat-header">
                    <?php
                        $stmt_chat_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                        $stmt_chat_user->execute([$chat_user_id]);
                        $chat_user = $stmt_chat_user->fetch();
                    ?>
                    <h3>Chat with <?php echo htmlspecialchars($chat_user['first_name']); ?></h3>
                </div>

                <!-- Chat Messages -->
                <div class="messages">
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?php echo $message['sender_id'] == $user_id ? 'sent' : 'received'; ?>">
                            <p><?php echo htmlspecialchars($message['message']); ?></p>
                            <div class="timestamp"><?php echo date('Y-m-d H:i:s', strtotime($message['created_at'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Message Input -->
                <form action="send_message.php" method="POST">
                    <input type="hidden" name="receiver_id" value="<?php echo $chat_user_id; ?>">
                    <textarea name="message" placeholder="Type a message..." required></textarea>
                    <button type="submit">Send</button>
                </form>
            <?php else: ?>
                <p>Select a contact to start chatting!</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="callNotification">
        <p id="callMessage"></p>
        <button onclick="acceptCall()">Accept</button>
        <button onclick="rejectCall()">Reject</button>
    </div>

    <!-- Add AJAX Script Here -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        // Get chat user ID from the URL
        var chatUserId = <?php echo $chat_user_id ? $chat_user_id : 'null'; ?>;
        const userId = <?php echo $user_id; ?>;

        // Fetch new messages every 2 seconds
        function fetchMessages() {
            if (chatUserId) {
                $.ajax({
                    url: 'fetch_messages.php',  // This file will fetch new messages from the database
                    method: 'GET',
                    data: {
                        chat_id: chatUserId
                    },
                    success: function(response) {
                        // Replace the chat content with the new messages
                        $('.messages').html(response);
                        // Scroll to the bottom of the chat window
                        $('.messages').scrollTop($('.messages')[0].scrollHeight);
                    }
                });
            }
        }

               // Call fetchMessages every 2 seconds
               setInterval(fetchMessages, 2000);

// Send message via AJAX when the form is submitted
$('form').submit(function(event) {
    event.preventDefault();

    var message = $('textarea[name="message"]').val();
    
    if (message.trim() != "") {
        $.ajax({
            url: 'send_message.php', // Handle message sending
            method: 'POST',
            data: {
                receiver_id: chatUserId,
                message: message
            },
            success: function(response) {
                // Clear the textarea
                $('textarea[name="message"]').val('');
                // Refresh the messages without reloading the page
                fetchMessages();
            }
        });
    }
});

// WebSocket for call notifications
const socket = new WebSocket('ws://localhost:8080'); // Replace with your WebSocket server

socket.onopen = () => {
    console.log("Connected to WebSocket server for notifications.");
};

socket.onmessage = (message) => {
    console.log("Received message:", message.data);
    const data = JSON.parse(message.data);
    if (data.call) {
        console.log("Incoming call notification received");
        document.getElementById('callNotification').style.display = 'block';
        document.getElementById('callMessage').innerText = `Incoming ${data.callType} call from User ${data.from}`;
    }
};

function acceptCall() {
    document.getElementById('callNotification').style.display = 'none';
    // Redirect to call.php to start the call
    window.location.href = `call.php?call_type=${callType}&user_id=${data.from}`;
}

function rejectCall() {
    document.getElementById('callNotification').style.display = 'none';
    socket.send(JSON.stringify({ reject: true, to: data.from }));
}
</script>
</body>
</html>
