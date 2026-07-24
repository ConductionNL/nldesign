---
sidebar_position: 7
---

# NL-Government Icons Integration

## Overview

The NL Design app includes **1488 icons** materialized from `@conduction/nextcloud-vue`'s
EUPL-compatible NL-government icon packs (RVO, OpenGemeenten, Gemeente Den Haag), plus
**23 logos**, making them available for use across all Nextcloud apps.

**The proprietary City-of-Amsterdam icon set (`@amsterdam/design-system-assets`) is NOT
bundled.** Its `LICENSE.md` marks the set proprietary to the City of Amsterdam,
restricted to contexts where Amsterdam is the main communicator — nldesign shipping it to
arbitrary Dutch-government instances was exactly the redistribution its notice forbids.
It was removed; see `CHANGELOG.md` for the full list of removed filenames and their
77 one-release legacy-name aliases.

## Availability and fallbacks

These icon and logo URLs only resolve while the nldesign app is installed **and enabled** on the instance. A consumer app that references `imagePath('nldesign', 'icons/...')` on an instance without nldesign will get a broken image. Consumers MUST either ship a fallback icon or declare a dependency on `nldesign` in their `appinfo/info.xml`.

## Naming stability

Icon and logo filenames are a public API consumed by other apps. Within an installed nc-vue pack version, renaming or removing a bundled icon or logo is a **breaking change**: it MUST be recorded in the changelog naming both the old and new filename, and `img/ICONS.md` MUST be regenerated in the same change so the inventory regression test (`tests/Unit/IconAssetsTest.php`) keeps passing. Upgrading the `@conduction/nextcloud-vue` dependency (which can change pack contents) MUST be an explicit, reviewed change whose diff of added/removed keys is listed in the changelog — never a silent regenerate.

## Available Icons

View all available icons in the [icon documentation](https://codeberg.org/Conduction/nldesign/src/branch/main/img/ICONS.md) or browse the files in:
- **Icons:** `img/icons/{rvo,open-gemeenten,den-haag}/` (1488 SVG files across 3 sets)
- **Legacy aliases:** `img/icons/*.svg` (77 one-release compatibility files — see CHANGELOG.md, removed next minor release)
- **Logos:** `img/logos/` (23 SVG files, static checked-in huisstijl assets — not build output)

## Usage in Nextcloud Apps

### Method 1: Direct SVG Reference (Recommended)

```php
<?php
// In your template file
$iconUrl = \OC::$server->getURLGenerator()->imagePath('nldesign', 'icons/den-haag/dh-communication-message.svg');
?>
<img src="<?php p($iconUrl); ?>" alt="Notifications" class="nldesign-icon" />
```

### Method 2: Background Image in CSS

```css
.my-icon {
    background-image: url('../../../nldesign/img/icons/den-haag/dh-functional-search.svg');
    background-size: contain;
    background-repeat: no-repeat;
    width: 24px;
    height: 24px;
}
```

### Method 3: Inline SVG (For Dynamic Styling)

```php
<?php
// Read and output SVG content directly
$iconPath = \OC::$SERVERROOT . '/apps/nldesign/img/icons/den-haag/dh-communication-message.svg';
if (file_exists($iconPath)) {
    echo file_get_contents($iconPath);
}
?>
```

### Vue-based consumers

Vue apps SHOULD import the packs from `@conduction/nextcloud-vue` directly rather than
going through the PHP image-path contract:

```js
import { rvoIcons } from '@conduction/nextcloud-vue/src/icons/rvo.js'
// <CnIconBrowser :url-icons="rvoIcons" … />
```

## Icon Sets

| Set | Directory | Upstream | Licence | Count |
| --- | --- | --- | --- | --- |
| RVO / ROOS | `img/icons/rvo/` | Rijksdienst voor Ondernemend Nederland | CC0-1.0 | 1163 |
| OpenGemeenten | `img/icons/open-gemeenten/` | OpenGemeenten Iconenset ("Line" style) | CC0-1.0 | 256 |
| Gemeente Den Haag | `img/icons/den-haag/` | Gemeente Den Haag icon set | EUPL-1.2 | 69 |

Filenames are the pack entry keys (kebab-case slugs already carrying a short set prefix,
e.g. `rvo-zoek`, `dh-functional-search`, `og-zoeken`) — confirm the exact filename in
`img/icons/{set}/` or `img/ICONS.md` before referencing it.

### Legacy Amsterdam filename aliases (one release only)

For exactly this release, 77 of the 344 removed Amsterdam filenames resolve at their old
top-level path (`img/icons/{Name}.svg`) to a byte-identical copy of a replacement icon
from the sets above — e.g. `Search.svg`, `Star.svg`, `House.svg`, `Mail.svg`. See
`img/ICONS.md` for the full alias table. **These aliases are removed in the next minor
release** — do not build new integrations against them; migrate to the set-prefixed path.

## Logos

The 23 logos in `img/logos/` cover government and municipal organizations. A representative sample:
- `amsterdam.svg` - City of Amsterdam logo
- `ggd-amsterdam.svg` - GGD Amsterdam logo
- `stadsarchief.svg` - Amsterdam City Archives
- `stadsbank-van-lening.svg` - Stadsbank van Lening
- `museum_weesp.svg` - Museum Weesp
- `vga-verzekeringen.svg` - VGA Verzekeringen
- `rijkshuisstijl.svg`, `vng.svg`, `provincie-zuid-holland.svg`, and other municipal logos (Utrecht, Rotterdam, Leiden, Nijmegen, Tilburg, Hoorn, Epe, and more)

Browse `img/logos/` for the complete set. These are static, checked-in huisstijl assets
tied to `token-sets.json` `theming.logo` entries — `scripts/build-icons.js` never
creates, modifies, or deletes anything under `img/logos/`.

## Styling Icons

Icons are designed to work with the NL Design System color tokens:

```css
.nldesign-icon {
    width: 24px;
    height: 24px;
    /* Apply NL Design colors */
    filter: invert(var(--nldesign-icon-invert, 0));
}

/* For dark backgrounds */
.nldesign-icon--light {
    filter: invert(1);
}
```

## Examples

### Button with Icon
```php
<button class="button-vue--vue-primary">
    <img src="<?php p(\OC::$server->getURLGenerator()->imagePath('nldesign', 'icons/rvo/rvo-plus.svg')); ?>"
         alt="" class="button-icon" />
    Toevoegen
</button>
```

### Menu Item with Icon
```php
<li>
    <a href="/path">
        <img src="<?php p(\OC::$server->getURLGenerator()->imagePath('nldesign', 'icons/den-haag/dh-objects-house.svg')); ?>"
             alt="Home icon" />
        Home
    </a>
</li>
```

### Status Indicator
```php
<div class="status-indicator">
    <img src="<?php p(\OC::$server->getURLGenerator()->imagePath('nldesign', 'icons/den-haag/dh-informational-checkcircle.svg')); ?>"
         alt="Success" class="status-icon status-icon--success" />
    <span>Voltooid</span>
</div>
```

## Browser Compatibility

All icons are SVG format and support:
- Modern browsers (Chrome, Firefox, Safari, Edge)
- IE11+ (with appropriate polyfills)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Accessibility

When using icons, always provide appropriate alt text or aria-labels:

```php
<!-- Decorative icon (no alt needed) -->
<img src="<?php p($iconUrl); ?>" alt="" aria-hidden="true" />

<!-- Functional icon (provide alt text) -->
<img src="<?php p($iconUrl); ?>" alt="Search" />

<!-- Icon button (use aria-label) -->
<button aria-label="Close dialog">
    <img src="<?php p($iconUrl); ?>" alt="" />
</button>
```

## License

Icons are sourced from `@conduction/nextcloud-vue`:
- **Package:** `@conduction/nextcloud-vue` (devDependency, build-time only)
- **Canonical licence record:** `src/icons/ATTRIBUTION.md` (https://codeberg.org/Conduction/nextcloud-vue/src/branch/main/src/icons/ATTRIBUTION.md)
- **Licences:** RVO CC0-1.0, OpenGemeenten CC0-1.0, Gemeente Den Haag EUPL-1.2

The proprietary `@amsterdam/design-system-assets` package is **not** a dependency of this
app and no shipped asset derives from it.

## Resources

- **nc-vue icon attribution:** https://codeberg.org/Conduction/nextcloud-vue/src/branch/main/src/icons/ATTRIBUTION.md
- **RVO icon source:** https://github.com/nl-design-system/rvo
- **OpenGemeenten icon source:** https://github.com/OpenGemeenten/Iconenset
- **Gemeente Den Haag icon source:** https://github.com/nl-design-system/denhaag

## Building Icons

Icons are automatically built from `@conduction/nextcloud-vue`'s bundled icon packs. To rebuild:

```bash
npm run build:icons
```

This decodes the data-URI icon packs at `node_modules/@conduction/nextcloud-vue/src/icons/{rvo,openGemeenten,denHaag}.js` into standalone SVG files under `img/icons/{set}/`, materializes the one-release legacy aliases from `scripts/icon-aliases.json`, and regenerates `img/ICONS.md`. It never touches `img/logos/`.
