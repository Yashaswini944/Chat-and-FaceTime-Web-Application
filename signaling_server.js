// signaling-server.js
const WebSocket = require('ws');

// Create a WebSocket server on port 8080
const wss = new WebSocket.Server({ port: 8080 });

const users = {}; // Map to store connected users

// Handle incoming WebSocket connections
wss.on('connection', (ws) => {
    console.log('New client connected.');

    // Listen for messages from clients
    ws.on('message', (message) => {
        const data = JSON.parse(message);

        // Register a new user
        if (data.userId) {
            users[data.userId] = ws;
            console.log(`User ${data.userId} connected.`);
            return;
        }

        // Relay signaling data to the target user
        if (data.signal && data.to) {
            const targetUser = users[data.to];
            if (targetUser) {
                targetUser.send(JSON.stringify({
                    signal: data.signal,
                    from: data.from,
                }));
                console.log(`Relayed signal from ${data.from} to ${data.to}`);
            }
        }
    });

    // Remove disconnected users
    ws.on('close', () => {
        for (let userId in users) {
            if (users[userId] === ws) {
                console.log(`User ${userId} disconnected.`);
                delete users[userId];
                break;
            }
        }
    });
});

console.log('Signaling server running on ws://localhost:8080');
