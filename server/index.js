const io = require('socket.io');
const server = io.listen(3000);

server.on('connection', (socket) => {
    const { query } = socket.handshake;
    if (!query.token) {
        socket.disconnect(true);
        return;
    }
    console.log('user connected with token', query.token);
});
