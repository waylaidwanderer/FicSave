const Downloader = require('./index');

class FanfictionNet extends Downloader {
    constructor(url) {
        super(url, {
            title: '#profile_top > b.xcontrast_txt',
            author: '#profile_top > a[href^="/u/"]',
            cover_art: '#img_large img',
            body: '#storytext',
            description: ['#profile_top > div.xcontrast_txt:nth-child(8)', '#profile_top > span.xgray']
        });
    }

    getChapters() {
        const self = this;
        const chapters = [];
        this.$('#chap_select').first().find('option').each(function chapterValue() {
            chapters.push(self.$(this).text().trim());
        });
        if (chapters.length === 0) {
            chapters.push(this.data.title);
        }
        return chapters;
    }

    static getBaseUrl(url) {
        const matches = /https:\/\/(.*)\/s\/(\d+)/.exec(url);
        if (!matches) {
            throw new Error('invalid URL');
        }
        matches[1] = matches[1].replace('m.', 'www.');
        return `https://${matches[1]}/s/${matches[2]}`;
    }

    getChapterUrl(chapterNumber) {
        return `${this.url}/${chapterNumber}`;
    }
}

module.exports = FanfictionNet;
