# NL Design System Theme for Nextcloud

Apply Dutch government design tokens (NL Design System) to your Nextcloud instance with open-source fonts and components.

## Features

- **Multiple Token Sets**: Choose from various Dutch government design systems:
  - Rijkshuisstijl (Dutch national government)
  - Gemeente Utrecht
  - Gemeente Amsterdam
  - Gemeente Den Haag
  - Gemeente Rotterdam

- **Open Source Fonts**: Uses **Fira Sans** from `@fontsource/fira-sans` as a professional alternative to proprietary government fonts

- **Easy Configuration**: Select your preferred token set via the admin settings panel

- **CSS Variables**: Uses CSS custom properties for flexible theming that integrates with Nextcloud's existing theme system

- **No Build Required**: Fonts loaded via CDN, tokens are pre-compiled CSS

- **Amsterdam Design System Icons**: Includes 344 SVG icons and 6 logos from the official Amsterdam Design System for use across all Nextcloud apps

## Icons

The app includes **344 icons** and **6 logos** from the Amsterdam Design System:

- Search, navigation, and UI icons
- Filled and outline variants
- Amsterdam municipal logos
- SVG format for scalability
- Accessible via Nextcloud's image path API

**[View Icon Documentation →](ICONS.md)**

## Installation

### Method 1: From Git Repository

1. Clone or download this app to your Nextcloud apps directory:
   ```bash
   cd /path/to/nextcloud/apps
   git clone https://github.com/ConductionNL/nldesign.git
   ```

2. Install npm dependencies (for fonts and icons):
   ```bash
   cd nldesign
   npm install
   npm run build
   ```

3. Enable the app in Nextcloud:
   ```bash
   occ app:enable nldesign
   ```

4. Configure the theme in **Settings → Administration → Theming**

### Method 2: Docker Environment

If you're running in the provided Docker environment:

```bash
# From the server directory
docker exec -u 33 nextcloud php occ app:enable nldesign
```

## Configuration

Navigate to **Settings → Administration → Theming** and find the "NL Design System Theme" section.

Select your preferred design token set and reload the page to see the changes.

### Configuring Background Color

The NL Design app does not set a background color - this allows you to use Nextcloud's built-in theming system to configure the background color to match your organization's needs.

**To set the background color:**

1. Navigate to **Settings → Administration → Theming** (Nextcloud's main theming section, not the NL Design section)
2. Scroll to **Background and color** section
3. Click on **Color** and enter your desired background color
4. **Important**: Also click on **Background image** and select **Remove background image** to ensure a solid color background

**Recommended colors by token set:**

| Token Set | Primary Color | Background Color |
|-----------|--------------|------------------|
| **Rijkshuisstijl** | `#154273` (donkerblauw) | `#F5F6F7` (light gray) |
| **Utrecht** | `#CC0000` (red) | `#FFFFFF` (white) |
| **Amsterdam** | `#EC0000` (red) | `#FFFFFF` (white) |
| **Den Haag** | `#1A7A3E` (green) | `#FFFFFF` (white) |
| **Rotterdam** | `#00811F` (green) | `#FFFFFF` (white) |

**Note**: The primary colors are automatically applied by the NL Design app when you select a token set. You only need to configure the background color manually in Nextcloud's theming settings.

**Why does NL Design not set the background color?**

By delegating background color management to Nextcloud's theming system, organizations can:
- Use their own custom background colors
- Easily change backgrounds without modifying app code
- Maintain compatibility with Nextcloud's theming API
- Allow different backgrounds for different user groups or instances

## Fonts

This app uses **Fira Sans** as an open-source alternative to the proprietary government fonts:

- **Source**: `@fontsource/fira-sans` npm package
- **License**: SIL Open Font License 1.1 (free to use)
- **Weights**: Regular (400) and Bold (700), plus italic variants
- **Delivery**: Loaded via CDN from jsdelivr.net
- **No permission needed**: Unlike RijksoverheidSansWebText, Fira Sans is freely available

### Why Fira Sans?

- Designed by Carrois Apostrophe for readability
- Used by Mozilla and other organizations
- Excellent legibility for government services
- Similar characteristics to official government fonts
- Officially recommended by Rijkshuisstijl Community as open-source alternative

## Architecture

```
nldesign/
├── appinfo/
│   ├── info.xml          # App metadata
│   └── routes.php        # API routes
├── css/
│   ├── fonts.css         # Fira Sans font declarations
│   ├── theme.css         # Maps NL Design tokens to Nextcloud variables
│   ├── tokens/           # Token set files per organization
│   │   ├── rijkshuisstijl.css
│   │   ├── utrecht.css
│   │   ├── amsterdam.css
│   │   ├── denhaag.css
│   │   └── rotterdam.css
│   └── admin.css         # Admin settings styles
├── js/
│   └── admin.js          # Admin settings JavaScript
├── lib/
│   ├── AppInfo/
│   │   └── Application.php  # Loads CSS files
│   ├── Controller/
│   │   └── SettingsController.php
│   └── Settings/
│       └── Admin.php
├── templates/
│   └── settings/
│       └── admin.php     # Settings UI
├── package.json          # NPM dependencies
└── node_modules/         # Fonts from npm
    └── @fontsource/fira-sans/
```

## How It Works

### Two-Layer CSS Variable System

1. **Token Layer**: Each organization has a token file (e.g., `rijkshuisstijl.css`) that defines design tokens as CSS variables:
   ```css
   :root {
       --nldesign-color-primary: #154273;
       --nldesign-font-family: 'Fira Sans', sans-serif;
   }
   ```

2. **Mapping Layer**: The `theme.css` file maps these to Nextcloud's CSS variables:
   ```css
   body {
       --color-primary: var(--nldesign-color-primary) !important;
       font-family: var(--nldesign-font-family) !important;
   }
   ```

3. **Font Loading**: The `fonts.css` file loads Fira Sans from CDN:
   ```css
   @font-face {
       font-family: 'Fira Sans';
       src: url('https://cdn.jsdelivr.net/npm/@fontsource/fira-sans@5.0.0/...');
   }
   ```

### Loading Order

```php
// In Application.php
\OCP\Util::addStyle(self::APP_ID, 'fonts');         // 1. Load Fira Sans
\OCP\Util::addStyle(self::APP_ID, 'tokens/utrecht'); // 2. Load token set
\OCP\Util::addStyle(self::APP_ID, 'theme');         // 3. Map to Nextcloud
```

## Development

### Prerequisites

- Node.js 18+
- npm

### Setup

```bash
cd nldesign
npm install
```

### Updating Fonts

The fonts are loaded from CDN, so no build step is required. However, if you want to download fonts locally:

```bash
# Fonts are in node_modules/@fontsource/fira-sans/files/
cp node_modules/@fontsource/fira-sans/files/*.woff2 css/fonts/
```

Then update `css/fonts.css` to use local paths instead of CDN.

### Creating New Token Sets

To add a new municipality or organization:

1. Create a new file in `css/tokens/` (e.g., `tilburg.css`)
2. Define the `--nldesign-*` variables following the existing pattern
3. Add the option to `templates/settings/admin.php`
4. Update `lib/Controller/SettingsController.php` to validate the new option

## NPM Packages Used

### Current Dependencies

- **`@fontsource/fira-sans`** (v5.0.0)
  - Open-source web fonts
  - Self-hosted option for Fira Sans
  - Includes all weights and styles

### Community Packages (Reference)

These packages inspired our token definitions but are not direct dependencies:

- `@rijkshuisstijl-community/design-tokens` - Official Rijkshuisstijl tokens
- `@rijkshuisstijl-community/font` - Font package (includes Fira Sans)
- `@utrecht/design-tokens` - Utrecht municipality tokens

Note: We maintain manual CSS token files for better compatibility with Nextcloud's asset pipeline, but they're based on the official NL Design System specifications.

## Compliance

### Open Source Implementation

✅ **What's Included (Free & Legal)**:
- Fira Sans fonts (SIL OFL 1.1 license)
- Design token values (colors, spacing, etc.)
- CSS mapping to Nextcloud variables
- All municipality color schemes

❌ **What's NOT Included (Requires Permission)**:
- Official Rijkslogo (crown logo)
- RijksoverheidSansWebText proprietary fonts
- Official government imagery

### Legal Usage

This implementation is **fully legal and open-source** for:
- Demonstrations and prototypes
- Educational purposes
- Municipal websites (with their respective themes)
- Any organization using open-source alternatives

**Permission Required** for:
- Official Rijksoverheid organizations using the Rijkslogo
- Using proprietary RijksoverheidSansWebText fonts
- Official government communications

## Resources

### Official Documentation
- [NL Design System](https://nldesignsystem.nl/)
- [Rijkshuisstijl Community](https://github.com/nl-design-system/rijkshuisstijl-community)
- [Utrecht Design System](https://github.com/nl-design-system/utrecht)
- [Rijkshuisstijl Online](https://www.communicatierijk.nl/vakkennis/rijkswebsites/verplichte-richtlijnen/rijkshuisstijl-online)

### Font Resources
- [Fira Sans on Google Fonts](https://fonts.google.com/specimen/Fira+Sans)
- [Fontsource Documentation](https://fontsource.org/fonts/fira-sans)
- [@fontsource/fira-sans on npm](https://www.npmjs.com/package/@fontsource/fira-sans)

### Community
- [NL Design System Community Slack](https://praatmee.codefor.nl/) - Join `#nl-design-system`
- [GitHub Discussions](https://github.com/nl-design-system/rijkshuisstijl-community/discussions)

## License

AGPL-3.0-or-later

### Component Licenses

- **This App**: AGPL-3.0-or-later
- **Fira Sans Font**: SIL Open Font License 1.1
- **Design Tokens**: Public domain (color values, measurements)

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Adding New Token Sets

1. Research the official design system
2. Create a new token file in `css/tokens/`
3. Follow the existing pattern with `--nldesign-*` variables
4. Add to admin UI
5. Test in Nextcloud
6. Submit PR with documentation

## Authors

- [Conduction](https://conduction.nl)

## Changelog

### v0.1.0 (2026-02-03)
- Initial release
- Support for 5 token sets (Rijkshuisstijl, Utrecht, Amsterdam, Den Haag, Rotterdam)
- Fira Sans font integration via @fontsource
- CDN-based font loading
- Full CSS variable mapping
- Admin settings panel
- Background image removal for clean Rijkshuisstijl compliance
- **Amsterdam Design System Icons**: 344 SVG icons + 6 logos integrated from @amsterdam/design-system-assets
