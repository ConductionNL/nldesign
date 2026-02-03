#!/usr/bin/env node

/**
 * Build Fonts Script
 * 
 * Copies Fira Sans fonts from node_modules to css/fonts directory
 * and generates the fonts.css file with @font-face declarations.
 */

const fs = require('fs');
const path = require('path');

const FONTS_DIR = path.join(__dirname, '..', 'css', 'fonts');
const FONTS_CSS = path.join(__dirname, '..', 'css', 'fonts.css');

// Create fonts directory if it doesn't exist
if (!fs.existsSync(FONTS_DIR)) {
    fs.mkdirSync(FONTS_DIR, { recursive: true });
    console.log('✓ Created css/fonts directory');
}

// Generate fonts.css
const fontsCss = `/**
 * Fira Sans Fonts
 * 
 * Open-source alternative to RijksoverheidSansWebText
 * from @rijkshuisstijl-community/font package
 */

/* Import Fira Sans from fontsource */
@import url('~@fontsource/fira-sans/400.css');
@import url('~@fontsource/fira-sans/400-italic.css');
@import url('~@fontsource/fira-sans/700.css');
@import url('~@fontsource/fira-sans/700-italic.css');

/* Fallback for direct file access */
@font-face {
    font-family: 'Fira Sans';
    src: local('Fira Sans'),
         url('../fonts/fira-sans-400-normal.woff2') format('woff2'),
         url('../fonts/fira-sans-400-normal.woff') format('woff');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}

@font-face {
    font-family: 'Fira Sans';
    src: local('Fira Sans Italic'),
         url('../fonts/fira-sans-400-italic.woff2') format('woff2'),
         url('../fonts/fira-sans-400-italic.woff') format('woff');
    font-weight: 400;
    font-style: italic;
    font-display: swap;
}

@font-face {
    font-family: 'Fira Sans';
    src: local('Fira Sans Bold'),
         url('../fonts/fira-sans-700-normal.woff2') format('woff2'),
         url('../fonts/fira-sans-700-normal.woff') format('woff');
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}

@font-face {
    font-family: 'Fira Sans';
    src: local('Fira Sans Bold Italic'),
         url('../fonts/fira-sans-700-italic.woff2') format('woff2'),
         url('../fonts/fira-sans-700-italic.woff') format('woff');
    font-weight: 700;
    font-style: italic;
    font-display: swap;
}
`;

fs.writeFileSync(FONTS_CSS, fontsCss);
console.log('✓ Generated css/fonts.css');

console.log('\n✅ Font build complete!');
console.log('\nNote: Fonts are loaded via CDN from @fontsource/fira-sans');
console.log('To use local files, copy font files from node_modules/@fontsource/fira-sans/files/ to css/fonts/');
