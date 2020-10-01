/**
 * Used for development of cover styles
 *
 * Run by running `npm run cover-generator`
 */
const fs = require('fs');
const path = require('path');
const simpleGenerator = require('./simple');

const savePath = path.resolve(__dirname, '../../tmp/');
console.log(`Saving covers into ${savePath}`);

const author = 'Marko Markovic';
const title = 'How to do *anything* in three simple steps';

simpleGenerator(author, title).pipe(fs.createWriteStream(path.join(savePath, 'simple.png')));
