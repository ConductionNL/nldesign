'use strict';

/**
 * Docusaurus only uses this result to add optional width and height attributes.
 * NL Design forbids local Markdown images, so no file parser is necessary.
 */
async function imageSizeFromFile() {
    return {};
}

module.exports = { imageSizeFromFile };
