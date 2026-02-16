#!/usr/bin/env node
/**
 * Generate placeholder SVG logos for municipalities that don't have official logos.
 * These create clean, minimalist badge-style logos with the municipality's
 * abbreviation on their signature color.
 *
 * Usage: node scripts/generate-logos.mjs
 */

import { writeFileSync, mkdirSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const logosDir = join(__dirname, '..', 'img', 'logos');

// Ensure logos directory exists
mkdirSync(logosDir, { recursive: true });

/**
 * Municipality/organization data: id, display abbreviation, primary color hex
 * Colors sourced from each organization's NL Design System token set.
 */
const logos = [
	{ id: 'bodegraven-reeuwijk', abbr: 'BR', color: '#0066CC', name: 'Bodegraven-Reeuwijk' },
	{ id: 'borne', abbr: 'Bo', color: '#003352', name: 'Borne' },
	{ id: 'buren', abbr: 'Bu', color: '#D41422', name: 'Buren' },
	{ id: 'demodam', abbr: 'Dd', color: '#03A9F4', name: 'Demodam' },
	{ id: 'dinkelland', abbr: 'Dk', color: '#006CB9', name: 'Dinkelland' },
	{ id: 'drechterland', abbr: 'Dr', color: '#1B6E8C', name: 'Drechterland' },
	{ id: 'duiven', abbr: 'Du', color: '#1D5B8F', name: 'Duiven' },
	{ id: 'duo', abbr: 'DUO', color: '#004FA3', name: 'DUO', fontSize: 16 },
	{ id: 'enkhuizen', abbr: 'Ek', color: '#0055AD', name: 'Enkhuizen' },
	{ id: 'epe', abbr: 'Ep', color: '#00549E', name: 'Epe' },
	{ id: 'groningen', abbr: 'Gr', color: '#154273', name: 'Groningen' },
	{ id: 'haarlem', abbr: 'Ha', color: '#1457A3', name: 'Haarlem' },
	{ id: 'haarlemmermeer', abbr: 'Hm', color: '#068E8C', name: 'Haarlemmermeer' },
	{ id: 'hoorn', abbr: 'Ho', color: '#09366C', name: 'Hoorn' },
	{ id: 'horstaandemaas', abbr: 'HM', color: '#125EA4', name: 'Horst a/d Maas' },
	{ id: 'leiden', abbr: 'Le', color: '#D62410', name: 'Leiden' },
	{ id: 'leidschendam-voorburg', abbr: 'LV', color: '#1E1B54', name: 'Leidschendam-Voorburg' },
	{ id: 'nijmegen', abbr: 'Ni', color: '#157C68', name: 'Nijmegen' },
	{ id: 'noaberkracht', abbr: 'Nb', color: '#4376FC', name: 'Noaberkracht' },
	{ id: 'noordoostpolder', abbr: 'NP', color: '#389003', name: 'Noordoostpolder' },
	{ id: 'noordwijk', abbr: 'Nw', color: '#2C2276', name: 'Noordwijk' },
	{ id: 'provincie-zuid-holland', abbr: 'ZH', color: '#C42035', name: 'Zuid-Holland' },
	{ id: 'riddeliemers', abbr: 'Rl', color: '#154273', name: 'Riddeliemers' },
	{ id: 'ridderkerk', abbr: 'Rk', color: '#008937', name: 'Ridderkerk' },
	{ id: 'stedebroec', abbr: 'SB', color: '#035935', name: 'Stede Broec' },
	{ id: 'tilburg', abbr: 'Ti', color: '#003366', name: 'Tilburg' },
	{ id: 'tubbergen', abbr: 'Tb', color: '#067432', name: 'Tubbergen' },
	{ id: 'venray', abbr: 'Ve', color: '#2A8113', name: 'Venray' },
	{ id: 'vng', abbr: 'VNG', color: '#003865', name: 'VNG', fontSize: 16 },
	{ id: 'vught', abbr: 'Vu', color: '#0088AD', name: 'Vught' },
	{ id: 'westervoort', abbr: 'Wv', color: '#003C6B', name: 'Westervoort' },
	{ id: 'xxllnc', abbr: 'xx', color: '#333333', name: 'xxllnc' },
	{ id: 'zevenaar', abbr: 'Ze', color: '#596E28', name: 'Zevenaar' },
	{ id: 'zwolle', abbr: 'Zw', color: '#3A4F93', name: 'Zwolle' },
];

function generateSvg({ abbr, color, name, fontSize }) {
	const textSize = fontSize || (abbr.length > 2 ? 14 : 20);
	const textY = abbr.length > 2 ? 26 : 27;

	return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">
  <rect width="40" height="40" rx="6" fill="${color}"/>
  <text x="20" y="${textY}" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="${textSize}" font-weight="bold" fill="#FFFFFF">${abbr}</text>
</svg>
`;
}

let created = 0;
for (const logo of logos) {
	const svg = generateSvg(logo);
	const filePath = join(logosDir, `${logo.id}.svg`);
	writeFileSync(filePath, svg, 'utf-8');
	console.log(`Created: ${logo.id}.svg (${logo.name}, ${logo.color})`);
	created++;
}

console.log(`\nDone! Created ${created} logo files in img/logos/`);
