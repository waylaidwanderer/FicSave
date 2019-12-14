const Downloader = require('./index');

class FanfictionNet extends Downloader {
    constructor(url) {
        super(url, {
            title: '#profile_top > b.xcontrast_txt',
            author: '#profile_top > a.xcontrast_txt:nth-child(5)',
            cover_art: '#img_large img',
            body: '#storytext',
        });
    }

    getChapters() {
        const self = this;
        const chapters = [];
        this.$('#chap_select').first().find('option').each(function chapterValue() {
            chapters.push(self.$(this).text().trim());
        });
        return chapters;
    }

    static getBaseUrl(url) {
        const matches = /https:\/\/(.*)\/s\/(\d+)/.exec(url);
        if (!matches) {
            throw new Error('invalid URL');
        }
        return `https://www.fanfiction.net/s/${matches[2]}`;
    }

    getChapterUrl(chapterNumber) {
        return `${this.url}/${chapterNumber}`;
    }
}

module.exports = FanfictionNet;
