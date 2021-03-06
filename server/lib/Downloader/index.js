const fs = require('fs');
const cheerio = require('cheerio');
const httpsProxyAgent = require('https-proxy-agent');
const axios = require('axios');
const sanitizeFilename = require('sanitize-filename');
const querystring = require('querystring');

const Epub = require('epub-gen');
const { EventEmitter } = require('events');
const Redis = require('ioredis');

const coverGenerator = require('../CoverGenerator');

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

const SCRAPER_CONCURRENCY_LIMIT = parseInt(process.env.SCRAPER_CONCURRENCY_LIMIT) || 0;

class Downloader extends EventEmitter {
    constructor(url, selectors = {}) {
        super();
        this.url = this.constructor.getBaseUrl(url);
        this.html = '';
        this.selectors = {
            title: null,
            author: null,
            summary: null,
            metadata: null,
            cover_art: null,
            cover_placeholder_name: null,
            body: null,
            description: null,
            ...selectors,
        };
        this.data = null;
        this.numChapters = 0;
        this.numChaptersFetched = 0;
        this.proxies = null;
        this.isScraperRequired = false;
    }

    /* eslint-disable */
    getChapters() { }

    static getBaseUrl(url) {
        return url;
    }

    getChapterUrl(chapterNumber) {}
    /* eslint-enable */

    async fetchData(retries = 0) {
        const response = await this.makeRequest(this.url);
        this.html = response.data;
        const $ = cheerio.load(this.html);
        this.$ = $;
        const title = $(this.selectors.title).first().text().trim();
        const author = $(this.selectors.author).first().text().trim();
        this.data = {
            title,
            author,
            description: this.getDescription(),
            publisher: this.url,
            cover: this.selectors.cover_art ? $(this.selectors.cover_art).first().attr('src') : null,
            appendChapterTitles: false,
            css: `
                body {
                    font-family: 'Arial', sans-serif;
                }
            `
        };
        if (this.data.cover && this.data.cover.startsWith('//') && !this.data.cover.includes(this.selectors.cover_placeholder_name)) {
            this.data.cover = `https:${this.data.cover}`;
        } else {
            this.data.cover = coverGenerator(author, title);
        }
        this.fileName = sanitizeFilename(`${this.data.title} - ${this.data.author}.epub`) || '__blank__';
        this.emit('fileName', this.fileName);
        this.data.output = `./tmp/${this.fileName}`;
        return this.fileName;
    }

    getDescription() {
        if (Array.isArray(this.selectors.description)) {
            return this.selectors.description
                .map(selector => this.$.html(this.$(selector)).trim())
                .join('\n');
        }
        return this.$.html(this.selectors.description).trim();
    }

    async download() {
        if (!this.data) {
            await this.fetchData();
        }
        this.emit('numChaptersFetched', 0);
        const chapterList = await this.getChapters();
        this.numChapters = chapterList.length;
        this.emit('numChapters', this.numChapters);
        let bookContents = [{
            title: `${this.data.title} by ${this.data.author}`,
            data: `
                <div style="text-align: center;">
                    <h1>${this.data.title}</h1>
                    <h3>by <em>${this.data.author}</em></h3>
                    <div style="text-align: left;">${this.data.description}</div>
                    <p style="text-align: left;">URL: <a href="${this.url}">${this.url}</a></p>
                </div>
            `,
            beforeToc: true,
        }];
        if (this.isScraperRequired) {
            // chapter downloads must be done sequentially when using a scraper
            for (let i = 0; i < chapterList.length; i++) {
                const chapterTitle = chapterList[i];
                bookContents.push(await this.buildChapter(i + 1, chapterTitle));
            }
        } else {
            bookContents = bookContents.concat(
                await Promise.all(
                    chapterList.map((chapterTitle, index) => this.buildChapter(index + 1, chapterTitle))
                )
            );
        }
        this.data.content = bookContents;
        await (new Epub(this.data).promise);
        fs.unlink(this.data.cover, err => {
            if (err) {
                console.log(err);
            }
            // probably fine if it doesn't get deleted for some reason
        });
        return {
            outputPath: this.data.output,
            fileName: this.fileName,
        };
    }

    async fetchChapter(chapterUrl) {
        const response = await this.makeRequest(chapterUrl);
        const $ = cheerio.load(response.data);
        const body = $(this.selectors.body);
        body.find('*').each(function() {
            if (['A', 'IMG'].includes($(this).prop('tagName'))) {
                return;
            }
            this.attribs = {};
        });
        return body.html();
    }

    async buildChapter(chapterNumber, chapterTitle) {
        const chapterUrl = this.getChapterUrl(chapterNumber);
        const chapterContent = await this.fetchChapter(chapterUrl);
        this.emit('numChaptersFetched', ++this.numChaptersFetched);
        chapterTitle = chapterTitle.trim();
        if (chapterTitle) {
            chapterTitle = `${chapterNumber}. ${chapterTitle.replace(`${chapterNumber}. `, '')}`;
        } else {
            chapterTitle = `Chapter ${chapterNumber}`;
        }
        const data = `
            <h2 style="text-align: center;">${chapterTitle}</h2>
            <div>
                ${chapterContent}
            </div>
        `;
        return {
            title: chapterTitle,
            data,
        };
    }

    async getHttpClientRequestOptions() {
        let options = {
            headers: {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.150 Safari/537.36',
            },
            timeout: 5000,
        };
        const proxyToUse = await this.getProxyToUse();
        if (proxyToUse) {
            const httpsAgent = new httpsProxyAgent(`http://${proxyToUse}`);
            options = {
                ...options,
                httpsAgent,
                httpAgent: httpsAgent,
            };
        }
        return options;
    }

    async getProxyToUse() {
        if (this.proxies === null) {
            try {
                this.proxies = (await fs.promises.readFile('./proxies.txt', 'utf-8'))
                    .split('\n')
                    .map(line => line.trim())
                    .filter(line => !!line);
            } catch (e) {
                this.proxies = [];
            }
        }
        if (this.proxies.length > 0) {
            return this.proxies[Math.floor(Math.random() * this.proxies.length)];
        }
        return null;
    }

    async makeRequest(url, retries = 0) {
        if (this.isScraperRequired) {
            const lockKey = await this.waitForTurnToScrape();
            try {
                const response = await axios.get('https://app.scrapingbee.com/api/v1/?' + querystring.stringify({
                    api_key: process.env.SCRAPINGBEE_API_KEY,
                    url,
                    render_js: 'false',
                }));
                await redisClient.del(lockKey);
                return response;
            } catch (e) {
                await redisClient.del(lockKey);
                if (retries >= 3) {
                    throw e;
                }
                console.error(e, `Retrying ${url}...`);
                return this.makeRequest(url, retries + 1);
            }
        }
        try {
            return await axios.get(url, {
                ...(await this.getHttpClientRequestOptions()),
                referer: this.url,
            });
        } catch (e) {
            if (e.response && e.response.data.includes('Please turn JavaScript on and reload the page.')) {
                this.isScraperRequired = true;
                return this.makeRequest(url, retries);
            }
            if (retries >= 3) {
                throw e;
            }
            console.error(e, `Retrying ${url}...`);
            return this.makeRequest(url, retries + 1);
        }
    }

    /**
     * The scraping service enforces a concurrency limit of N,
     * which is why we need to make sure all downloader processes wait their turn.
     * @returns {Promise<string|null>}
     */
    async waitForTurnToScrape() {
        if (SCRAPER_CONCURRENCY_LIMIT < 1) {
            return null;
        }
        for (let i = 0; i < SCRAPER_CONCURRENCY_LIMIT; i++) {
            const lockKey = `scraper:lock:${i}`;
            // set an expiring lock for 1 min just in case, even though it should be manually released when the request is done
            const lockAcquired = await redisClient.set(lockKey, Date.now(), 'NX', 'EX', 60);
            if (lockAcquired) {
                return lockKey;
            }
        }
        // lock wasn't acquired yet, wait 1s and try again
        await new Promise(resolve => setTimeout(resolve, 1000));
        return this.waitForTurnToScrape();
    }
}

module.exports = Downloader;
