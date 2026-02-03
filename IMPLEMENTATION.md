# NL Design System Implementation in Nextcloud

## Overview

This document describes how the NL Design System has been implemented in Nextcloud, the technical strategy used, and compliance with official Rijkshuisstijl requirements.

## Implementation Strategy

### Two-Layer CSS Variable System

The implementation uses a **two-layer CSS variable mapping pattern** that allows switching between different government design systems without modifying Nextcloud's core components.

#### Layer 1: Design Tokens (Token Files)

Each organization has a token file (e.g., `css/tokens/rijkshuisstijl.css`) that defines design tokens as CSS variables with the `--nldesign-*` prefix:

```css
:root {
    /* Primary colors */
    --nldesign-color-primary: #154273;
    --nldesign-color-primary-text: #ffffff;
    --nldesign-color-header-background: #154273;
    
    /* Typography */
    --nldesign-font-family: 'RijksoverheidSansWebText', ...;
    
    /* Border radius */
    --nldesign-border-radius: 0;
}
```

**Available Token Sets:**
- **Rijkshuisstijl** - Dutch national government (Rijksoverheid)
- **Utrecht** - Gemeente Utrecht (red theme)
- **Amsterdam** - Gemeente Amsterdam (red theme)
- **Den Haag** - Gemeente Den Haag (green theme)
- **Rotterdam** - Gemeente Rotterdam (green theme)

#### Layer 2: Nextcloud Integration (theme.css)

The `css/theme.css` file performs two critical functions:

1. **Maps NL Design tokens to Nextcloud's CSS variables:**
```css
body {
    --color-primary: var(--nldesign-color-primary) !important;
    --color-primary-text: var(--nldesign-color-primary-text) !important;
    --border-radius: var(--nldesign-border-radius) !important;
}
```

2. **Directly overrides specific UI elements with high-specificity selectors:**
```css
#header {
    background: var(--nldesign-color-header-background) !important;
}

.button-vue--vue-primary {
    background-color: var(--nldesign-color-button-primary-background) !important;
    border-radius: var(--nldesign-border-radius) !important;
}
```

### Loading Mechanism

The app injects CSS files at runtime through `lib/AppInfo/Application.php`:

```php
private function injectThemeCSS($serverContainer): void {
    $config = $serverContainer->getConfig();
    $tokenSet = $config->getAppValue(self::APP_ID, 'token_set', 'rijkshuisstijl');
    
    // Load selected token set
    \OCP\Util::addStyle(self::APP_ID, 'tokens/' . $tokenSet);
    
    // Load mapping layer
    \OCP\Util::addStyle(self::APP_ID, 'theme');
}
```

**Cascade Flow:**
```
1. Token File → Sets --nldesign-* variables
2. theme.css → Maps to Nextcloud --color-* variables
3. Nextcloud Components → Use mapped variables
```

## Advantages of This Approach

✅ **No Component Replacement** - Works with existing Nextcloud Vue components  
✅ **Non-Destructive** - No modifications to Nextcloud core  
✅ **Runtime Switchable** - Change themes via admin panel  
✅ **Standards-Based** - Uses official NL Design System token naming  
✅ **Maintainable** - Token files can be replaced with npm packages  
✅ **Accessible** - Preserves Nextcloud's accessibility features  

## Rijkshuisstijl Compliance Audit

Based on [official Rijkshuisstijl requirements](https://www.communicatierijk.nl/vakkennis/rijkswebsites/verplichte-richtlijnen/rijkshuisstijl-online), the following elements are **mandatory** for government websites:

### ✅ Implemented

| Element | Status | Implementation |
|---------|--------|----------------|
| **Color Palette** | ✅ Complete | All official colors defined in `rijkshuisstijl.css` |
| **Typography Base** | ✅ Partial | Font family variable defined |
| **Sharp Corners** | ✅ Complete | `--nldesign-border-radius: 0` |
| **Primary Color (#154273)** | ✅ Complete | Donkerblauw applied to header/buttons |
| **Accessibility** | ✅ Complete | Focus states, color contrast maintained |

### ⚠️ Needs Attention

| Element | Status | Issue | Fix Required |
|---------|--------|-------|--------------|
| **Rijkslogo** | ❌ Missing | Crown logo not displayed | Add logo to header |
| **Typography (Font Files)** | ⚠️ Incomplete | Font declared but not loaded | Load RijksoverheidSansWebText |
| **Background Image** | ⚠️ Non-compliant | Nextcloud default gradient used | Remove background, use solid color |

### 📋 Official Requirements

According to CommunicatieRijk, the **mandatory base elements** are:

1. **Rijkslogo** - Crown logo with organization name
2. **Color Palette** - Official Rijkshuisstijl colors
3. **Typography** - RijksoverheidSansWebText font family
4. **Visual Style** - Beeldtaal (imagery guidelines)

## Areas Covered by Implementation

### Colors
All 14 official Rijkshuisstijl colors are defined:
- Donkerblauw (#154273) - Primary
- Hemelblauw (#007bc7) - Links/focus
- Lichtblauw (#b2d7ee)
- Donkergroen (#275937)
- Groen (#39870c) - Success
- Mintgroen (#76d2b6)
- Geel (#f9e11e)
- Oranje (#e17000) - Warning
- Robijnrood (#8b1a15)
- Rood (#d52b1e) - Error
- Roze (#f092cd)
- Violet (#a90061)
- Paars (#42145f)
- Mauve (#b4a7c9)

### UI Elements Styled
- **Header bar** - Dark blue (#154273)
- **Buttons** - Primary/secondary styling
- **Forms** - Inputs, focus states
- **Navigation** - Sidebar, active states
- **Links** - Proper color hierarchy
- **Status indicators** - Error/warning/success
- **Borders** - Sharp corners (0px radius)

### Accessibility Features
- **Focus indicators** - 2px solid outline
- **Color contrast** - WCAG AA compliant
- **Keyboard navigation** - Preserved from Nextcloud

## Technical Implementation Details

### Why `!important` is Used

Nextcloud's theming system has high CSS specificity. The `!important` flags ensure NL Design tokens override Nextcloud's defaults:

```css
body[data-themes] {
    --color-primary: var(--nldesign-color-primary) !important;
}
```

This is a **recommended pattern** for theme override apps in Nextcloud's architecture.

### Browser Compatibility

The implementation uses:
- CSS Custom Properties (CSS Variables) - Supported in all modern browsers
- `:root` selector - Universal support
- `var()` function - Standard CSS feature

**Minimum Browser Requirements:**
- Chrome/Edge 49+
- Firefox 31+
- Safari 9.1+

### Performance Considerations

- **CSS-only implementation** - No JavaScript overhead
- **Two small CSS files** - Total ~10KB uncompressed
- **Native CSS variables** - Browser-optimized performance
- **No runtime processing** - Styles applied at page load

## Configuration

### Admin Settings

Navigate to **Settings → Administration → Theming** to access the NL Design System panel.

### Available Options

1. **Rijkshuisstijl** - Dutch national government
2. **Gemeente Utrecht** - Municipality of Utrecht
3. **Gemeente Amsterdam** - Municipality of Amsterdam  
4. **Gemeente Den Haag** - Municipality of The Hague
5. **Gemeente Rotterdam** - Municipality of Rotterdam

### Preview Feature

The settings panel includes a live preview showing:
- Header color bar
- Primary button styling
- Secondary button styling

## Future Improvements

### Recommended Enhancements

1. **Typography Integration**
   - Load RijksoverheidSansWebText font files
   - Implement proper font fallback chain
   - Add font subsetting for performance

2. **Logo Implementation**
   - Add Rijkslogo to header
   - Support custom organization logos
   - Implement logo position options

3. **Background Control**
   - Remove/disable Nextcloud background image
   - Implement solid color backgrounds
   - Add option to disable user backgrounds

4. **Token Package Integration**
   - Import from official npm packages
   - Automated token updates
   - Version tracking

5. **Extended Token Sets**
   - Add more municipalities
   - Support custom token imports
   - Theme marketplace integration

### NPM Package Integration

Future versions could import tokens directly from official packages:

```json
{
  "dependencies": {
    "@rijkshuisstijl-community/design-tokens": "^1.0.0",
    "@utrecht/design-tokens": "^1.0.0",
    "@amsterdam/design-tokens": "^1.0.0"
  }
}
```

## Resources

### Official Documentation
- [NL Design System](https://nldesignsystem.nl/)
- [Rijkshuisstijl Online Richtlijnen](https://www.communicatierijk.nl/vakkennis/rijkswebsites/verplichte-richtlijnen/rijkshuisstijl-online)
- [Rijkshuisstijl Community](https://github.com/nl-design-system/rijkshuisstijl-community)

### Design Token Repositories
- [Utrecht Design System](https://github.com/nl-design-system/utrecht)
- [Amsterdam Design System](https://github.com/Amsterdam/design-system)
- [Den Haag Design System](https://github.com/nl-design-system/denhaag)

### Nextcloud Documentation
- [App Development Guide](https://docs.nextcloud.com/server/latest/developer_manual/app_development/)
- [Theming Documentation](https://docs.nextcloud.com/server/latest/admin_manual/configuration_server/theming.html)

## License

AGPL-3.0-or-later

## Authors

- [Conduction](https://conduction.nl)
