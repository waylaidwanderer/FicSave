const cheerio = require('cheerio');
const axios = require('axios');

const Epub = require('epub-gen');
const { EventEmitter } = require('events');

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
            body: null,
            ...selectors,
        };
        this.data = null;
        this.numChapters = 0;
        this.numChaptersFetched = 0;
    }

    /* eslint-disable */
    getChapters() { }

    static getBaseUrl(url) {
        return url;
    }

    getChapterUrl(chapterNumber) {}
    /* eslint-enable */

    async fetchData() {
        const response = await axios.get(this.url);
        this.html = response.data;
        const $ = cheerio.load(this.html);
        this.$ = $;
        this.data = {
            title: $(this.selectors.title).first().text().trim(),
            author: $(this.selectors.author).first().text().trim(),
            publisher: this.url,
            cover: this.selectors.cover_art ? $(this.selectors.cover_art).first().attr('src') : null,
        };
        if (this.data.cover.startsWith('//')) {
            this.data.cover = `https:${this.data.cover}`;
        }
        this.fileName = `${this.data.title} - ${this.data.author}.epub`;
        this.emit('fileName', this.fileName);
        this.data.output = `./tmp/${this.fileName}`;
        return this.fileName;
    }

    async download() {
        this.emit('numChaptersFetched', 0);
        if (!this.data) {
            await this.fetchData();
        }
        const chapterList = await this.getChapters();
        this.numChapters = chapterList.length;
        this.emit('numChapters', this.numChapters);
        const buildChaptersPromises = [];
        chapterList.forEach((chapterTitle, index) => buildChaptersPromises.push(this.buildChapter(index + 1, chapterTitle)));
        this.data.content = await Promise.all(buildChaptersPromises);
        await (new Epub(this.data).promise);
        return {
            outputPath: this.data.output,
            fileName: this.fileName,
        };
    }

    async fetchChapter(chapterUrl) {
        const response = await axios.get(chapterUrl);
        const $ = cheerio.load(response.data);
        return $(this.selectors.body).html();
    }

    async buildChapter(chapterNumber, chapterTitle) {
        const chapterUrl = this.getChapterUrl(chapterNumber);
        const chapterContent = await this.fetchChapter(chapterUrl);
        this.emit('numChaptersFetched', ++this.numChaptersFetched);
        return {
            title: chapterTitle,
            data: chapterContent,
        };
    }
}

module.exports = Downloader;
