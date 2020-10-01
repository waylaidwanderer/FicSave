const simpleGenerator = require('./simple');

const generate = (author, title) => {
    // Only simple generator for now
    return simpleGenerator(author, title);
};

module.exports = generate;
