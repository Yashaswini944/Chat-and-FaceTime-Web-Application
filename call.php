<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$call_type = $_GET['call_type'] ?? 'voice'; // Determine if it's a voice or video call
$receiver_id = $_GET['user_id'] ?? null;

// Ensure the call type is valid
if (!in_array($call_type, ['voice', 'video']) || !$receiver_id) {
    header("Location: chat.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($call_type); ?> Call</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
        }
        
        .call-container {
            text-align: center;
        }

        video {
            width: 80%;
            border-radius: 10px;
            background-color: black;
        }

        button {
            margin: 10px;
            padding: 10px 20px;
            background-color: #2ecc71;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="call-container">
        <h2><?php echo ucfirst($call_type); ?> Call with User <?php echo htmlspecialchars($receiver_id); ?></h2>
        <video id="localVideo" autoplay muted></video>
        <video id="remoteVideo" autoplay></video>
        <div>
            <button onclick="toggleMute()">Mute/Unmute</button>
            <button onclick="toggleVideo()">Enable/Disable Video</button>
            <button onclick="endCall()">End Call</button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/simple-peer/simplepeer.min.js"></script>
    <script>
        const callType = "<?php echo $call_type; ?>";
        const userId = "<?php echo $user_id; ?>";
        const receiverId = "<?php echo $receiver_id; ?>";

        // Initialize WebSocket for signaling
        const socket = new WebSocket('ws://localhost:8080'); // Replace with your WebSocket server

        let peer;
        let localStream;

        socket.onopen = () => {
            console.log("Connected to WebSocket server.");
            startCall();
        };

        socket.onmessage = (message) => {
            const data = JSON.parse(message.data);
            if (data.signal) {
                peer.signal(data.signal);
            }
        };

        function startCall() {
            const constraints = callType === "video" ? { video: true, audio: true } : { video: false, audio: true };

            navigator.mediaDevices.getUserMedia(constraints)
                .then(stream => {
                    localStream = stream;
                    document.getElementById("localVideo").srcObject = stream;

                    peer = new SimplePeer({
                        initiator: userId < receiverId, // Deterministically decide who initiates
                        stream: localStream,
                        trickle: false
                    });

                    peer.on("signal", data => {
                        socket.send(JSON.stringify({ signal: data, to: receiverId }));
                    });

                    peer.on("stream", remoteStream => {
                        document.getElementById("remoteVideo").srcObject = remoteStream;
                    });
                })
                .catch(error => {
                    console.error("Error accessing media devices: ", error);
                });
        }

        function toggleMute() {
            localStream.getAudioTracks().forEach(track => track.enabled = !track.enabled);
        }

        function toggleVideo() {
            localStream.getVideoTracks().forEach(track => track.enabled = !track.enabled);
        }

        function endCall() {
            if (peer) peer.destroy();
            localStream.getTracks().forEach(track => track.stop());
            window.location.href = "chat.php";
        }
    </script>
</body>
</html>
