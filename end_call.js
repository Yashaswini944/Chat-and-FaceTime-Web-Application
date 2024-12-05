function endCall() {
    signalingSocket.emit('end_call', {
        userId: remoteUserId, // The ID of the user you're calling
    });

    // Close the video call (peer connection)
    peerConnection.close();
    localStream.getTracks().forEach(track => track.stop());

    // Optionally, navigate back to the chat page or hide the call UI
    window.location.href = "chat.php"; // Navigate back to the chat page after call ends
}

// Listen for the call end event on the other user's side
signalingSocket.on('end_call', (data) => {
    alert('The call has ended.');
    // Close the video call and navigate back
    window.location.href = "chat.php";
});
