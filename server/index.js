const { fork } = require('child_process');
const io = require('socket.io');

const Redis = require('ioredis');

const dotenvResult = require('dotenv').config();
if (dotenvResult.error) {
    throw dotenvResult.error;
}

const redisConfig = {
    port: process.env.REDIS_PORT,
    host: process.env.REDIS_HOST,
    password: process.env.REDIS_PASSWORD,
};
const redisClient = new Redis(redisConfig);
const redisSubscriber = new Redis(redisConfig);
const server = io.listen(3000);

const socketUsers = {};

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

    // TODO: remove these listeners somehow after specific socket disconnection

    redisSubscriber.on(`${socket.user.token}/progress`, (data) => {
        socket.emit('progress', data);
    });

    redisSubscriber.on(`${socket.user.token}/error`, (data) => {
        socket.emit('error', data);
    });

    redisSubscriber.on(`${socket.user.token}/complete`, (data) => {
        socket.emit('complete', data);
    });

    socket.on('disconnect', () => {
        if (!socket.user) {
            return;
        }

        socketUsers[socket.user.token] = socketUsers[socket.user.token].filter(userSocket => userSocket.id !== socket.id);
        if (socketUsers[socket.user.token].length === 0) {
            delete socketUsers[socket.user.token];
            redisSubscriber.unsubscribe(
                `${socket.user.token}/progress`,
                `${socket.user.token}/error`,
                `${socket.user.token}/complete`,
            );
        }
    });

    socket.on('download', (msg) => {
        // TODO
    });

    if (socketUsers[socket.user.token]) {
        socketUsers[socket.user.token].push(socket);
        return;
    }

    socketUsers[socket.user.token] = [socket];
    redisSubscriber.subscribe(
        `${socket.user.token}/progress`,
        `${socket.user.token}/error`,
        `${socket.user.token}/complete`,
    );
});
