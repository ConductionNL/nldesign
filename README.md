# NL Design System Theme for Nextcloud

Apply Dutch government design tokens (NL Design System) to your Nextcloud instance.

## Features

- **Multiple Token Sets**: Choose from various Dutch government design systems:
  - Rijkshuisstijl (Dutch national government)
  - Gemeente Utrecht
  - Gemeente Amsterdam
  - Gemeente Den Haag
  - Gemeente Rotterdam

- **Easy Configuration**: Select your preferred token set via the admin settings panel.

- **CSS Variables**: Uses CSS custom properties for flexible theming that integrates with Nextcloud's existing theme system.

## Installation

1. Clone or download this app to your Nextcloud apps directory:
   ```bash
   cd /path/to/nextcloud/apps
   git clone https://github.com/ConductionNL/nldesign.git
   ```

2. Enable the app in Nextcloud:
   ```bash
   occ app:enable nldesign
   ```

3. Configure the theme in **Settings → Administration → Theming**.

## Configuration

Navigate to **Settings → Administration → Theming** and find the "NL Design System Theme" section.

Select your preferred design token set and reload the page to see the changes.

## Development

### Prerequisites

- Node.js 18+
- npm

### Setup

```bash
cd nldesign
npm install
```

### Updating Design Tokens

To update the design tokens from the official NL Design System packages:

```bash
npm run update-tokens
```

### Manual Token Updates

If you need to manually update tokens, edit the CSS files in `css/tokens/`:

- `rijkshuisstijl.css` - Rijksoverheid styling
- `utrecht.css` - Gemeente Utrecht styling
- `amsterdam.css` - Gemeente Amsterdam styling
- `denhaag.css` - Gemeente Den Haag styling
- `rotterdam.css` - Gemeente Rotterdam styling

## Architecture

```
nldesign/
├── appinfo/
│   ├── info.xml          # App metadata
│   └── routes.php        # API routes
├── css/
│   ├── theme.css         # Maps NL Design tokens to Nextcloud variables
│   ├── tokens/           # Token set files per organization
│   └── admin.css         # Admin settings styles
├── js/
│   └── admin.js          # Admin settings JavaScript
├── lib/
│   ├── AppInfo/
│   │   └── Application.php
│   ├── Controller/
│   │   └── SettingsController.php
│   └── Settings/
│       └── Admin.php
└── templates/
    └── settings/
        └── admin.php
```

## How It Works

1. The app defines a set of intermediate CSS variables (`--nldesign-*`).
2. Token set files (e.g., `rijkshuisstijl.css`) set these variables to organization-specific values.
3. The main `theme.css` maps these intermediate variables to Nextcloud's CSS variables (`--color-*`).
4. This allows switching between token sets without modifying the mapping logic.

## Resources

- [NL Design System](https://nldesignsystem.nl/)
- [Rijkshuisstijl Community](https://github.com/nl-design-system/rijkshuisstijl-community)
- [Utrecht Design System](https://github.com/nl-design-system/utrecht)
- [Amsterdam Design System](https://github.com/Amsterdam/design-system)
- [Nextcloud Theming Documentation](https://docs.nextcloud.com/server/latest/developer_manual/app_development/index.html)

## License

AGPL-3.0-or-later

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Authors

- [Conduction](https://conduction.nl)
