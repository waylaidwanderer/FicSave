const argv = require('minimist')(process.argv.slice(2));
const url = require('url');

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
        throw new Error(`No downloader found for "${downloadUrl.host}".`);
    }

    let outputPath;
    try {
        outputPath = await downloader.download();
    } catch (err) {
        console.log(err);
        throw new Error('There was an error downloading this story. Please try again later.');
    }

    console.log(outputPath);
}
