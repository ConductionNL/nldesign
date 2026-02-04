# Amsterdam Design System Icons Integration

## Overview

The NL Design app now includes **344 icons** and **6 logos** from the Amsterdam Design System, making them available for use across all Nextcloud apps.

## Available Icons

View all available icons in the [icon documentation](../img/ICONS.md) or browse the files in:
- **Icons:** `img/icons/` (344 SVG files)
- **Logos:** `img/logos/` (6 SVG files)

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
    background-image: url('../../../nldesign/img/icons/MagnifyingGlass.svg');
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
- **Navigation:** ArrowForward, ArrowBackward, ArrowUp, ArrowDown, Home, Menu
- **Actions:** Plus, Minus, Close, Check, Edit, Delete, Save, Download, Upload
- **Communication:** Bell, Email, Phone, Chat
- **Interface:** Search (MagnifyingGlass), Filter, Settings, Info, Warning, Error
- **Media:** Play, Pause, Stop, Volume, Camera, Image

### Filled Variants
Many icons have 'Fill' variants (e.g., `Bell.svg` and `BellFill.svg`) for different visual weights.

## Logos

Available Amsterdam-related logos:
- `amsterdam.svg` - City of Amsterdam logo
- `ggd-amsterdam.svg` - GGD Amsterdam logo
- `stadsarchief.svg` - Amsterdam City Archives
- `stadsbank-van-lening.svg` - Stadsbank van Lening
- `museum_weesp.svg` - Museum Weesp
- `vga-verzekeringen.svg` - VGA Verzekeringen

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
        <img src="<?php p(\OC::$server->getURLGenerator()->imagePath('nldesign', 'icons/Home.svg')); ?>" 
             alt="Home icon" />
        Home
    </a>
</li>
```

### Status Indicator
```php
<div class="status-indicator">
    <img src="<?php p(\OC::$server->getURLGenerator()->imagePath('nldesign', 'icons/CheckmarkCircleFill.svg')); ?>" 
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
