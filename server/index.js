const { fork } = require('child_process');
const io = require('socket.io');

const Redis = require('ioredis');

const redis = new Redis();
const server = io.listen(3000);

server.on('connection', (socket) => {
    const { query } = socket.handshake;
    if (!query.token) {
        socket.disconnect(true);
        return;
    }
    console.log('user connected with token', query.token);
    socket.user = {
        token: query.token,
    };
});

server.on('download', (msg) => {
    
});
