const { fork } = require('child_process');
const io = require('socket.io');

const Redis = require('ioredis');

const dotenvResult = require('dotenv').config();
if (dotenvResult.error) {
    throw dotenvResult.error;
}

let redisConnectionString = `redis${process.env.REDIS_TLS === 'true' ? 's' : ''}://`;
if (process.env.REDIS_PASSWORD) {
    redisConnectionString = `${redisConnectionString}:${process.env.REDIS_PASSWORD}@`;
}
redisConnectionString = `${redisConnectionString}${process.env.REDIS_HOST}:${process.env.REDIS_PORT}/${process.env.REDIS_DB}`;
const redisClient = new Redis(redisConnectionString);
redisClient.ping((err) => {
    if (err) {
        console.log(err);
        process.exit();
    }
    console.log('Successfully connected to redis.');
});
const redisSubscriber = new Redis(redisConnectionString);
const server = io.listen(process.env.APP_PORT);
console.log(`Listening on port ${process.env.APP_PORT}.`);
const socketUsers = {};

server.on('connection', (socket) => {
    const { query } = socket.handshake;
    if (!query.token) {
        socket.disconnect(true);
        return;
    }

    socket.user = {
        token: JSON.stringify(query.token).replace(/\W/g, ''),
    };

    const onProgress = (data) => {
        data = JSON.parse(data);
        socket.emit('progress', data);
    };
    const onError = (data) => {
        data = JSON.parse(data);
        socket.emit('err', data);
    };
    const onComplete = (data) => {
        data = JSON.parse(data);
        socket.emit('complete', data);
    };
    const onMessage = (channel, message) => {
        switch (channel) {
            case `${socket.user.token}/progress`:
                onProgress(message);
                break;
            case `${socket.user.token}/error`:
                onError(message);
                break;
            case `${socket.user.token}/complete`:
                onComplete(message);
                break;
            default:
                break;
        }
    };
    redisSubscriber.on('message', onMessage);

    socket.on('disconnect', () => {
        if (!socket.user) {
            return;
        }

        redisSubscriber.removeListener('message', onMessage);

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
        try {
            download(socket, msg);
        } catch (err) {
            console.log(err);
        }
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

function download(socket, msg) {
    const encodedUrl = Buffer.from(msg.url).toString('base64');
    const params = [
        `--userToken=${socket.user.token}`,
        `--url=${encodedUrl}`,
    ];
    console.log(params);
    const child = fork('./download.js', params, {
        silent: true,
    });
    child.on('error', (err) => {
        console.log(`PID ${child.pid} error: ${err}`);
    });
    if (!child.stderr && !child.stdout) {
        throw new Error('Malformed process');
    }
    child.stderr.on('data', data => process.stderr.write(data));
    child.stdout.on('data', data => process.stdout.write(data));
    child.on('exit', (code, signal) => {
        if (!code && signal !== 'SIGINT' && signal !== 'SIGABRT') {
            return;
        }
        console.log(`PID ${child.pid} has unexpectedly exited (code: ${code}, signal: ${signal}). Restarting...`);
    });
    return child;
}
