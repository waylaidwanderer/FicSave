const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const hash = str => crypto.createHash('sha256').update(str).digest('hex');

const simpleGenerator = require('./simple');

const savePath = path.resolve(__dirname, '../../tmp');

const generate = (author, title) => {
    const saveFilename = path.join(savePath, hash([author, title, 'simple'].join('-')) + '.png');

    // Only simple generator for now
    simpleGenerator(author, title).pipe(fs.createWriteStream(saveFilename));
    return saveFilename;
};

module.exports = generate;
