---
sidebar_position: 7
---

# Amsterdam Design System Icons Integration

## Overview

The NL Design app now includes **344 icons** from the Amsterdam Design System and **23 logos**, making them available for use across all Nextcloud apps.

## Availability and fallbacks

These icon and logo URLs only resolve while the nldesign app is installed **and enabled** on the instance. A consumer app that references `imagePath('nldesign', 'icons/...')` on an instance without nldesign will get a broken image. Consumers MUST either ship a fallback icon or declare a dependency on `nldesign` in their `appinfo/info.xml`.

## Naming stability

Icon and logo filenames are a public API consumed by other apps (they hardcode names like `MagnifyingGlass.svg`). Renaming or removing a bundled icon or logo is a **breaking change**: it MUST be recorded in the changelog naming both the old and new filename, and `img/ICONS.md` MUST be updated in the same change so the inventory regression test (`tests/Unit/IconAssetsTest.php`) keeps passing. Syncing a newer Amsterdam Design System release is an explicit, reviewed change — never a silent regenerate.

## Available Icons

View all available icons in the [icon documentation](https://codeberg.org/Conduction/nldesign/src/branch/main/img/ICONS.md) or browse the files in:
- **Icons:** `img/icons/` (344 SVG files)
- **Logos:** `img/logos/` (23 SVG files)

## Usage in Nextcloud Apps

### Method 1: Direct SVG Reference (Recommended)

```php
<?php
// In your template file
$iconUrl = \OC::$server->getURLGenerator()->imagePath('nldesign', 'icons/Bell.svg');
?>
<img src="<?php p($iconUrl); ?>" alt="Notifications" class="nldesign-icon" />
```

### Method 2: Background Image in CSS

```css
.my-icon {
    background-image: url('../../../nldesign/img/icons/Search.svg');
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
$iconPath = \OC::$SERVERROOT . '/apps/nldesign/img/icons/Bell.svg';
if (file_exists($iconPath)) {
    echo file_get_contents($iconPath);
}
?>
```

## Icon Categories

The Amsterdam Design System icons are organized into several categories:

### Common Icons
- **Navigation:** ArrowForward, ArrowBackward, ArrowUp, ArrowDown, House, Menu
- **Actions:** Plus, Minus, Close, CheckMark, Pencil, Delete, Save, Download, Upload
- **Communication:** Bell, Mail, Phone, SpeechBalloonEllipsis
- **Interface:** Search, Filter, Settings, Info, Warning, Error
- **Media:** Play, Pause, VolumeOn, VolumeOff, Camera, Image

> Filenames follow the upstream Amsterdam Design System exactly (PascalCase). Some names differ from a generic icon vocabulary — e.g. the search icon is `Search.svg`, the home icon is `House.svg`, the edit icon is `Pencil.svg`, and the check icon is `CheckMark.svg`. Always confirm the exact filename in `img/icons/` before referencing it.

### Filled Variants
Many icons have 'Fill' variants (e.g., `Bell.svg` and `BellFill.svg`) for different visual weights.

## Logos

The 23 logos in `img/logos/` cover government and municipal organizations. A representative sample:
- `amsterdam.svg` - City of Amsterdam logo
- `ggd-amsterdam.svg` - GGD Amsterdam logo
- `stadsarchief.svg` - Amsterdam City Archives
- `stadsbank-van-lening.svg` - Stadsbank van Lening
- `museum_weesp.svg` - Museum Weesp
- `vga-verzekeringen.svg` - VGA Verzekeringen
- `rijkshuisstijl.svg`, `vng.svg`, `provincie-zuid-holland.svg`, and other municipal logos (Utrecht, Rotterdam, Leiden, Nijmegen, Tilburg, Hoorn, Epe, and more)

Browse `img/logos/` for the complete set.

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
    <img src="<?php p(\OC::$server->getURLGenerator()->imagePath('nldesign', 'icons/Plus.svg')); ?>" 
         alt="" class="button-icon" />
    Toevoegen
</button>
```

### Menu Item with Icon
```php
<li>
    <a href="/path">
        <img src="<?php p(\OC::$server->getURLGenerator()->imagePath('nldesign', 'icons/House.svg')); ?>" 
             alt="Home icon" />
        Home
    </a>
</li>
```

### Status Indicator
```php
<div class="status-indicator">
    <img src="<?php p(\OC::$server->getURLGenerator()->imagePath('nldesign', 'icons/CheckMarkCircleFill.svg')); ?>" 
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

Icons are from the Amsterdam Design System:
- **Package:** @amsterdam/design-system-assets
- **License:** Mozilla Public License 2.0
- **Source:** https://github.com/Amsterdam/design-system

## Resources

- **Amsterdam Design System Storybook:** https://designsystem.amsterdam/?path=/docs/brand-assets-icons--docs
- **Icon Browser:** Browse all icons at the Storybook link above
- **Component Library:** https://github.com/Amsterdam/design-system

## Building Icons

Icons are automatically built from npm packages. To rebuild:

```bash
npm run build:icons
```

This copies SVG files from `node_modules/@amsterdam/design-system-assets` to the app's `img/` directory.
