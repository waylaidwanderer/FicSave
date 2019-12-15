const argv = require('minimist')(process.argv.slice(2));
const dotenvResult = require('dotenv').config();
const fs = require('fs');
const url = require('url');

const AWS = require('aws-sdk');

if (dotenvResult.error) {
    throw dotenvResult.error;
}

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

    let outputPath;
    let fileName;
    try {
        ({ outputPath, fileName } = await downloader.download());
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
        // TODO: data.Location
        console.log(data.Location);
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

function handleError(errMsg, err = null) {
    if (err) {
        console.log(err);
    }

    // TODO: broadcast to redis
}