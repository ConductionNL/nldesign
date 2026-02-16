# NL Design System Theme for Nextcloud

Apply Dutch government design tokens (NL Design System) to your Nextcloud instance with open-source fonts and components.

## Features

- **39 Token Sets**: Choose from Dutch government design systems including Rijkshuisstijl, Amsterdam, Utrecht, Den Haag, Rotterdam, and 34 more municipalities and organizations
- **14 Official Logos**: SVG logos sourced from official websites and the NL Design System themes repository
- **Open Source Fonts**: Uses **Fira Sans** from `@fontsource/fira-sans` as a professional alternative to proprietary government fonts
- **Easy Configuration**: Select your preferred token set via the admin settings panel
- **Theming Sync**: Automatically syncs Nextcloud's built-in theming (login page, email templates) to match your selected token set
- **CSS Variables**: Uses CSS custom properties for flexible theming that integrates with Nextcloud's existing theme system
- **No Build Required**: Fonts loaded via CDN, tokens are pre-compiled CSS
- **Amsterdam Design System Icons**: Includes 344 SVG icons and 6 logos from the official Amsterdam Design System

## Installation

### From Git Repository

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

### Docker Environment

If you're running in the provided Docker environment:

```bash
docker exec -u 33 nextcloud php occ app:enable nldesign
```

## Configuration

Navigate to **Settings → Administration → Theming** and find the "NL Design System Theme" section.

Select your preferred design token set and reload the page to see the changes. When selecting a token set with theming metadata, a sync dialog offers to update Nextcloud's built-in theming (login page, email templates) to match.

### Background Color

The NL Design app does not set a background color — this delegates to Nextcloud's built-in theming system so organizations can configure it independently.

**To set the background color:**

1. Navigate to **Settings → Administration → Theming**
2. Scroll to **Background and color** section
3. Click **Color** and enter your desired background color
4. Click **Background image** and select **Remove background image** for a solid color

See [Brand Identity](docs/brand-identity.md) for the full list of brand colors per organization.

## Architecture

```
nldesign/
├── appinfo/
│   ├── info.xml               # App metadata
│   └── routes.php             # API routes
├── css/
│   ├── fonts.css              # Fira Sans font declarations
│   ├── defaults.css           # Sensible defaults for ALL --nldesign-* tokens
│   ├── tokens/                # 39 token set files per organization
│   │   ├── rijkshuisstijl.css
│   │   ├── amsterdam.css
│   │   └── ... (39 files)
│   ├── utrecht-bridge.css     # --utrecht-* → --nldesign-component-* mapping
│   ├── theme.css              # Maps --nldesign-* tokens to Nextcloud element styling
│   ├── overrides.css          # Maps Nextcloud CSS variables to --nldesign-* tokens
│   ├── element-overrides.css  # Element-level styling overrides (header, login, etc.)
│   └── admin.css              # Admin settings styles
├── docs/
│   ├── brand-identity.md      # Brand colors and logos per organization
│   ├── tokens.md              # CSS token architecture and mapping guidelines
│   ├── mappings.md            # Complete Nextcloud → NL Design variable mapping
│   ├── icons.md               # Amsterdam Design System icon reference
│   ├── assets.md              # Guide to official Rijkshuisstijl assets
│   ├── compliance.md          # Rijkshuisstijl compliance checklist
│   └── token-audit.md         # Token set audit against official specs
├── img/
│   ├── logos/                 # 14 municipality/organization logos (SVG)
│   └── nederland-logo.svg     # Rijkshuisstijl national logo
├── lib/
│   ├── AppInfo/Application.php     # CSS load order bootstrap
│   ├── Controller/SettingsController.php
│   ├── Service/TokenSetService.php # Filesystem-based token discovery
│   └── Settings/Admin.php
├── scripts/
│   ├── generate-tokens.mjs    # Generates token CSS from NL Design System themes repo
│   └── generate-logos.mjs     # Generates placeholder logos for missing municipalities
├── token-sets.json            # Manifest of available token sets + theming metadata
├── package.json               # NPM dependencies
└── .github/workflows/
    └── sync-tokens.yml        # Nightly upstream token sync
```

## How It Works

### Seven-Layer CSS Variable System

1. **Fonts** (`fonts.css`): Loads Fira Sans from CDN
2. **Defaults** (`defaults.css`): Sensible Rijkshuisstijl-based defaults for ALL `--nldesign-*` tokens
3. **Token Set** (`tokens/{org}.css`): Organization-specific tokens override defaults
4. **Utrecht Bridge** (`utrecht-bridge.css`): Maps `--utrecht-*` component tokens to `--nldesign-component-*` equivalents
5. **Theme** (`theme.css`): Maps `--nldesign-*` tokens to Nextcloud element styling using component tokens
6. **Variable Overrides** (`overrides.css`): Maps Nextcloud CSS variables to `--nldesign-*` tokens
7. **Element Overrides** (`element-overrides.css`): Applies NL Design styling to specific Nextcloud elements

Incomplete token sets work correctly because `defaults.css` provides fallback values for every token.

### Loading Order

```php
// In Application.php
\OCP\Util::addStyle(self::APP_ID, 'fonts');              // 1. Fonts
\OCP\Util::addStyle(self::APP_ID, 'defaults');           // 2. Defaults
\OCP\Util::addStyle(self::APP_ID, 'tokens/' . $org);     // 3. Token set
\OCP\Util::addStyle(self::APP_ID, 'utrecht-bridge');     // 4. Utrecht bridge
\OCP\Util::addStyle(self::APP_ID, 'theme');              // 5. Theme
\OCP\Util::addStyle(self::APP_ID, 'overrides');          // 6. Variable overrides
\OCP\Util::addStyle(self::APP_ID, 'element-overrides');  // 7. Element overrides
```

## Documentation

| Document | Description |
|----------|-------------|
| [Brand Identity](docs/brand-identity.md) | Brand colors, background colors, and logos for all 39 token sets |
| [Token Architecture](docs/tokens.md) | CSS token reference, mapping guidelines, and how to add new token sets |
| [Variable Mappings](docs/mappings.md) | Complete Nextcloud CSS variable to NL Design token mapping |
| [Icons](docs/icons.md) | Amsterdam Design System icon integration (344 icons + 6 logos) |
| [Assets Guide](docs/assets.md) | Finding and using official Rijkshuisstijl fonts, logos, and assets |
| [Compliance](docs/compliance.md) | Rijkshuisstijl compliance checklist |
| [Token Audit](docs/token-audit.md) | Audit of token sets against official NL Design System specifications |

## Development

### Syncing Tokens from Upstream

Token sets are auto-generated from the [NL Design System themes repository](https://github.com/nl-design-system/themes). A nightly GitHub Actions workflow handles this automatically.

To manually sync:

```bash
git clone https://github.com/nl-design-system/themes.git /tmp/themes
node scripts/generate-tokens.mjs /tmp/themes
```

### Adding a New Token Set

1. Create a CSS file in `css/tokens/` with `--nldesign-*` variables
2. Add an entry to `token-sets.json` with `id`, `name`, `description`, and `theming` metadata
3. Optionally add a logo SVG at `img/logos/{id}.svg` and set `--nldesign-logo-url` in the CSS file
4. **No PHP changes needed** — the admin dropdown and validation use filesystem scanning

See [Token Architecture](docs/tokens.md) for the complete token reference.

## Compliance

### Open Source Implementation

**Included (Free & Legal)**:
- Fira Sans fonts (SIL OFL 1.1 license)
- Design token values (colors, spacing, etc.)
- CSS mapping to Nextcloud variables
- All municipality color schemes and 14 official logos

**NOT Included (Requires Permission)**:
- Official Rijkslogo (crown logo)
- RijksoverheidSansWebText proprietary fonts
- Official government imagery

## Sources

- [NL Design System](https://nldesignsystem.nl/)
- [NL Design System Themes Repository](https://github.com/nl-design-system/themes)
- [Rijkshuisstijl Community](https://github.com/nl-design-system/rijkshuisstijl-community)
- [Fira Sans on Google Fonts](https://fonts.google.com/specimen/Fira+Sans)

## License

AGPL-3.0-or-later

- **This App**: AGPL-3.0-or-later
- **Fira Sans Font**: SIL Open Font License 1.1
- **Design Tokens**: Public domain (color values, measurements)

## Authors

- [Conduction](https://conduction.nl)
