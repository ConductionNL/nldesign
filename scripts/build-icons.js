#!/usr/bin/env node
/**
 * Build script for NL Design System Icons
 * 
 * This script extracts SVG icons from the Amsterdam Design System
 * and makes them available for use in Nextcloud.
 */

const fs = require('fs');
const path = require('path');

// Paths
const iconsSourcePath = path.join(__dirname, '..', 'node_modules', '@amsterdam', 'design-system-assets', 'icons');
const logosSourcePath = path.join(__dirname, '..', 'node_modules', '@amsterdam', 'design-system-assets', 'logo');
const iconsDestPath = path.join(__dirname, '..', 'img', 'icons');
const logosDestPath = path.join(__dirname, '..', 'img', 'logos');

console.log('Building NL Design System Icons...');

// Create destination directories
[iconsDestPath, logosDestPath].forEach(dir => {
	if (!fs.existsSync(dir)) {
		fs.mkdirSync(dir, { recursive: true });
		console.log(`Created directory: ${dir}`);
	}
});

// Copy icons
if (fs.existsSync(iconsSourcePath)) {
	const iconFiles = fs.readdirSync(iconsSourcePath).filter(file => file.endsWith('.svg'));
	let copiedCount = 0;
	
	iconFiles.forEach(file => {
		const sourceFile = path.join(iconsSourcePath, file);
		const destFile = path.join(iconsDestPath, file);
		fs.copyFileSync(sourceFile, destFile);
		copiedCount++;
	});
	
	console.log(`✓ Copied ${copiedCount} icon SVG files to ${iconsDestPath}`);
} else {
	console.warn(`Warning: Icons source not found at: ${iconsSourcePath}`);
}

// Copy logos
if (fs.existsSync(logosSourcePath)) {
	const logoFiles = fs.readdirSync(logosSourcePath).filter(file => file.endsWith('.svg'));
	let copiedCount = 0;
	
	logoFiles.forEach(file => {
		const sourceFile = path.join(logosSourcePath, file);
		const destFile = path.join(logosDestPath, file);
		fs.copyFileSync(sourceFile, destFile);
		copiedCount++;
	});
	
	console.log(`✓ Copied ${copiedCount} logo SVG files to ${logosDestPath}`);
} else {
	console.warn(`Warning: Logos source not found at: ${logosSourcePath}`);
}

// Create icon reference documentation
const iconFiles = fs.existsSync(iconsDestPath) ? fs.readdirSync(iconsDestPath).filter(f => f.endsWith('.svg')) : [];
const logoFiles = fs.existsSync(logosDestPath) ? fs.readdirSync(logosDestPath).filter(f => f.endsWith('.svg')) : [];

const readmeContent = `# Amsterdam Design System Icons & Logos

This directory contains SVG icons and logos from the Amsterdam Design System.

## Icons (${iconFiles.length} total)

Available in: \`img/icons/\`

${iconFiles.slice(0, 20).map(f => `- ${f.replace('.svg', '')}`).join('\n')}
${iconFiles.length > 20 ? `\n... and ${iconFiles.length - 20} more` : ''}

## Logos (${logoFiles.length} total)

Available in: \`img/logos/\`

${logoFiles.map(f => `- ${f.replace('.svg', '')}`).join('\n')}

## Usage in Nextcloud

To use these icons in your Nextcloud app:

\`\`\`php
// In your template or controller
\\OCP\\Util::addStyle('nldesign', 'icons');

// Then reference the icon
<img src="<?php p(\\OC::$server->getURLGenerator()->imagePath('nldesign', 'icons/MagnifyingGlass.svg')); ?>" alt="Search">
\`\`\`

## Documentation

View all icons at: https://designsystem.amsterdam/?path=/docs/brand-assets-icons--docs

## License

Icons from @amsterdam/design-system-assets (Mozilla Public License 2.0)
`;

const readmePath = path.join(__dirname, '..', 'img', 'ICONS.md');
fs.writeFileSync(readmePath, readmeContent);
console.log(`✓ Created icon documentation: ${readmePath}`);

console.log('\n✅ Icon build complete!');
console.log(`   ${iconFiles.length} icons + ${logoFiles.length} logos ready to use`);

