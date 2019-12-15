const argv = require('minimist')(process.argv.slice(2));
if (!argv.userToken || !argv.url) {
    throw new Error('Invalid arguments');
}

const fs = require('fs');
const url = require('url');

const AWS = require('aws-sdk');
const Redis = require('ioredis');

const dotenvResult = require('dotenv').config();
if (dotenvResult.error) {
    throw dotenvResult.error;
}

const redisPublisher = new Redis({
    port: process.env.REDIS_PORT,
    host: process.env.REDIS_HOST,
    password: process.env.REDIS_PASSWORD,
});

const spacesEndpoint = new AWS.Endpoint(process.env.S3_ENDPOINT);
const s3 = new AWS.S3({
    endpoint: spacesEndpoint,
    accessKeyId: process.env.S3_ACCESS_KEY_ID,
    secretAccessKey: process.env.S3_SECRET_ACCESS_KEY,
});

const FanfictionNetDownloader = require('./lib/Downloader/fanfictionnet');

let downloadUrl;
try {
    downloadUrl = url.parse(argv.url);
} catch (err) {
    throw new Error('Invalid URL.');
}

const supportedSites = [
    'www.fanfiction.net',
    'www.fictionpress.com',
];

if (!supportedSites.includes(downloadUrl.host)) {
    throw new Error('This site is currently unsupported.');
}

main();

async function main() {
    let downloader;
    if (
        downloadUrl.host === 'www.fanfiction.net'
        || downloadUrl.host === 'www.fictionpress.com'
    ) {
        downloader = new FanfictionNetDownloader(argv.url);
    }

    if (!downloader) {
        await handleError(`No downloader found for "${downloadUrl.host}".`);
        return;
    }

    let numChapters = 0;
    downloader.on('numChapters', (_numChapters) => {
        numChapters = _numChapters;
    });
    downloader.on('numChaptersFetched', (numChaptersFetched) => {
        // downscale progress by 95% - last 5% will be for building the ebook
        const progress = Math.floor((numChaptersFetched / numChapters) * 100 * 0.95);
        redisPublisher.publish(`${argv.userToken}/progress`, progress);
    });

    let outputPath;
    let fileName;
    try {
        ({ outputPath, fileName } = await downloader.download());
        redisPublisher.publish(`${argv.userToken}/progress`, 100);
    } catch (err) {
        await handleError('There was an error downloading this story. Please try again later. (1)', err);
        return;
    }

    const fileContents = await readFile(outputPath);
    try {
        const data = await s3.upload({
            Body: fileContents,
            Bucket: process.env.S3_BUCKET_NAME,
            Key: fileName,
            ACL:'public-read',
        }).promise();
        fs.unlink(outputPath, (err) => {
            if (err) {
                console.log(err);
            }
            // probably fine if it doesn't get deleted for some reason
        });
        redisPublisher.publish(`${argv.userToken}/complete`, data.Location);
    } catch (err) {
        await handleError('There was an error downloading this story. Please try again later. (2)', err);
        return;
    }
}

function readFile(path) {
    return new Promise((resolve, reject) => {
        fs.readFile(path, (err, data) => {
            if (err) {
                return reject(err);
            }
            return resolve(data);
        });
    });
}

async function handleError(errMsg, err = null) {
    if (err) {
        console.log(err);
    }

    try {
        await redisPublisher.publish(`${argv.userToken}/error`, errMsg);
    } catch (redisErr) {
        console.log(redisErr);
    }
}
