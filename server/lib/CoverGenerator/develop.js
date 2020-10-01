/**
 * Used for development of cover styles
 *
 * Run by running `npm run cover-generator`
 */
const fs = require('fs');
const path = require('path');
const simpleGenerate = require('./simple');

const savePath = path.resolve(__dirname, '../../tmp/');
console.log(`Saving covers into ${savePath}`);
try {
    fs.mkdirSync(savePath, true);
} catch (err) {
    if (err.code !== 'EEXIST') console.error(err);
}

const saveImage = (dataImageString, imageSavePath) =>
    fs.writeFileSync(imageSavePath, Buffer.from(dataImageString.replace('data:image/png;base64,', ''), 'base64'));

const author = 'Marko Markovic';
const title = 'How to do *anything* in three simple steps';

saveImage(simpleGenerate(author, title), `${savePath}/simple.png`);
