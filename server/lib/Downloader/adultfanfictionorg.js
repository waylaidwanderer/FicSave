const Downloader = require('./index');

class AdultFanfictionOrg extends Downloader {
    constructor(url) {
        super(url, {
            title: '#contentdata > table:nth-child(2) > tbody:nth-child(1) > tr:nth-child(1) > td:nth-child(1) > table:nth-child(1) > tbody:nth-child(1) > tr:nth-child(1) > td:nth-child(1) > h2:nth-child(1) > a:nth-child(1)',
            author: '#contentdata > table:nth-child(2) > tbody:nth-child(1) > tr:nth-child(1) > td:nth-child(1) > table:nth-child(1) > tbody:nth-child(1) > tr:nth-child(1) > td:nth-child(2) > b:nth-child(1) > i:nth-child(1) > a:nth-child(1)',
            body: '#contentdata > table:nth-child(2) > tbody:nth-child(1) > tr:nth-child(3)',
            description: '#contentdata > table:nth-child(2) > tbody:nth-child(1) > tr:nth-child(1) > td:nth-child(1) > table:nth-child(1) > tbody:nth-child(1)'
        });
    }

    getChapters() {
        const self = this;
        const chapters = [];
        this.$('.dropdown-content').first().find('a').each(function chapterValue() {
            chapters.push(self.$(this).text().trim());
        });
        if (chapters.length === 0) {
            chapters.push(this.data.title);
        }
        return chapters;
    }

    static getBaseUrl(url) {
        const matches = /(.*):\/\/(.*)\/story\.php\?no=(\d+)/.exec(url);
        if (!matches) {
            throw new Error('Invalid URL');
        }
        return `${matches[1]}://${matches[2]}/story.php?no=${matches[3]}`;
    }

    getChapterUrl(chapterNumber) {
        return `${this.url}&chapter=${chapterNumber}`;
    }
}

module.exports = AdultFanfictionOrg;
