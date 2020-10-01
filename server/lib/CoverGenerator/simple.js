const { createCanvas } = require('canvas');

const rgb = (r, g, b) => `rgb(${r}, ${g}, ${b})`;

const red = rgb(255, 0, 0);
const black = rgb(0, 0, 0);
const gray1 = rgb(30, 30, 30);
const gray2 = rgb(60, 60, 60);
const gray3 = rgb(120, 120, 120);
const gray4 = rgb(150, 150, 150);
const white = rgb(255, 255, 255);

const textMeasure = (ctx, text, x = 0, y = 0) => {
    const measure = ctx.measureText(text);
    return {
        ...measure,
        height: measure.actualBoundingBoxAscent + measure.actualBoundingBoxDescent,
        boxX: x - measure.actualBoundingBoxLeft,
        boxY: y - measure.actualBoundingBoxAscent,
        boxW: measure.actualBoundingBoxRight - measure.actualBoundingBoxLeft,
        boxH: measure.actualBoundingBoxAscent + measure.actualBoundingBoxDescent
    };
};

const textFitWidth = (ctx, text, padding = 50) => {
    const imageWidth = ctx.canvas.width;
    let out = text;
    let bounds = textMeasure(ctx, out);
    if (bounds.width < imageWidth) {
        return text;
    }

    const words = out.split(' ');
    let numWords = Math.floor(words.length / 2);
    while (bounds.width >= imageWidth - padding && numWords > 0) {
        const lines = [];
        for (let i = 0; i < words.length / numWords; i++) {
            const start = i * numWords;
            const end = i * numWords + numWords;
            lines.push(words.slice(start, end).join(' '));
        }
        out = lines.join('\n');
        bounds = textMeasure(ctx, out);
        numWords--;
    }
    return out;
};

/**
 * WARNING!
 * Text positioning in canvas is sooooo fiddly.
 * You'll hate it.
 * I know I already do.
 */
const generate = (author, title) => {
    const canvas = createCanvas(516, 792);
    const ctx = canvas.getContext('2d');
    const imageW = ctx.canvas.width;
    const imageH = ctx.canvas.height;
    let x,
        y = 0;

    // Background
    const bgGradient = ctx.createLinearGradient(0, 0, 0, imageH);
    bgGradient.addColorStop(0, gray3);
    bgGradient.addColorStop(100, gray2);
    ctx.fillStyle = bgGradient;
    ctx.fillRect(0, 0, imageW, imageH);

    // Author
    ctx.fillStyle = gray2;
    ctx.font = `24px "serif"`;
    let measure = textMeasure(ctx, author);
    ctx.fillStyle = black;
    ctx.textAlign = 'center';
    ctx.fillText(textFitWidth(ctx, author, x, y), imageW / 2, imageH - 50 - measure.height);

    // Title
    ctx.font = `bold 32px "serif"`;
    ctx.textAlign = 'center';
    const fittedTitle = textFitWidth(ctx, title);
    measure = textMeasure(ctx, fittedTitle);
    x = imageW / 2;
    y = 200 - measure.height / 2;
    measure = textMeasure(ctx, fittedTitle, x, y);

    ctx.fillStyle = gray4;
    ctx.fillRect(measure.boxX - 30, measure.boxY - 30, measure.boxW + 60, measure.boxH + 60);
    ctx.fillStyle = gray1;
    ctx.fillText(fittedTitle, x, y);

    return canvas.createPNGStream();
};

module.exports = generate;
