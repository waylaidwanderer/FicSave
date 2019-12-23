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

    socket.user = {
        token: query.token,
    };

    const onProgress = (data) => {
        socket.emit('progress', data);
    };

    const onError = (data) => {
        socket.emit('error', data);
    };

    const onComplete = (data) => {
        socket.emit('complete', data);
    };

    redisSubscriber.on(`${socket.user.token}/progress`, onProgress);
    redisSubscriber.on(`${socket.user.token}/error`, onError);
    redisSubscriber.on(`${socket.user.token}/complete`, onComplete);

    socket.on('disconnect', () => {
        if (!socket.user) {
            return;
        }

        redisSubscriber.removeListener(`${socket.user.token}/progress`, onProgress);
        redisSubscriber.removeListener(`${socket.user.token}/error`, onError);
        redisSubscriber.removeListener(`${socket.user.token}/complete`, onComplete);

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
