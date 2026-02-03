# NL Design System Assets Guide

This document explains where to find official and community fonts, logos, and other assets for implementing the NL Design System in Nextcloud.

## Overview

The **Rijkshuisstijl Community** provides both:
1. **Open-source alternatives** (free to use, no permission needed)
2. **Documentation** on how to use **official proprietary assets** (requires permission)

**Repository:** [nl-design-system/rijkshuisstijl-community](https://github.com/nl-design-system/rijkshuisstijl-community)

## Fonts

### Open Source Alternative (Included)

The `@rijkshuisstijl-community/font` package provides **Fira Sans** as an open-source alternative to the proprietary RijksoverheidSansWebText fonts.

**Installation:**

```bash
npm install @rijkshuisstijl-community/font
```

**Implementation:**

```scss
// Import the fonts
@use "@fontsource/fira-sans/400.css" as FiraSansRegular;
@use "@fontsource/fira-sans/400-italic.css" as FiraSansItalic;
@use "@fontsource/fira-sans/700.css" as FiraSansBold;
@use "@fontsource/fira-sans/700-italic.css" as FiraSansBoldItalic;
```

The package includes:
- **Regular** (400 weight)
- **Regular Italic**
- **Bold** (700 weight)  
- **Bold Italic**

**Why Fira Sans?**
- Free and open-source
- Good alternative to RijksoverheidSansWebText
- Similar readability and professional appearance
- No permission/licensing required

### Official Proprietary Fonts (Optional)

**Font Name:** RijksoverheidSansWebText

**License:** Proprietary - Requires permission from Ministerie van Algemene Zaken

**How to Obtain:**
1. Visit [rijkshuisstijl.nl](https://www.rijkshuisstijl.nl/)
2. Create an account (requires government affiliation)
3. Request access through your organization's communication department
4. Download fonts from the Rijkshuisstijl website

**Usage After Permission:**
```scss
@font-face {
  font-family: 'RijksoverheidSansWebText';
  src: url('../fonts/RijksoverheidSansWebText-Regular.woff2') format('woff2'),
       url('../fonts/RijksoverheidSansWebText-Regular.woff') format('woff');
  font-weight: normal;
  font-style: normal;
  font-display: swap;
}
```

**Important:** Only use if you have explicit permission from the Dutch government.

## Logos

### Open Source Alternative (nederland-map icon)

The Rijkshuisstijl Community uses the `nederland-map` icon as a logo placeholder since the official Rijkslogo cannot be used outside official Rijksoverheid media.

**From README:**
> Omdat buiten officiële Rijksoverheids-media het logo van de Rijksoverheid niet mag worden gebruik, wordt binnen dit project standaard het icoon met id `nederland-map` gebruiken, met een witte achtergrond.

**Implementation:**
```html
<!-- Use the nederland-map icon from the icon set -->
<rhc-icon icon="nederland-map"></rhc-icon>
```

### Official Rijkslogo (Requires Permission)

**What it is:** The official crown logo with organization name

**License:** Copyright - Strict usage restrictions

**Quote from Community:**
> "Logo en stijlgids: Copyright geldt voor het Rijkshuisstijl-logo en merkidentiteit. Gebruik hiervan is strikt verboden, behalve voor het ontwikkelen van websites en apps voor de Rijksoverheid."

**How to Obtain:**
1. Contact [Ministerie van Algemene Zaken](https://www.rijkshuisstijl.nl/contact)
2. Provide proof of government affiliation
3. Explain use case (developing for Rijksoverheid)
4. Download from Rijkshuisstijl website after approval

**Formats Available:**
- SVG (recommended for web)
- PNG (various sizes)
- Variations for light/dark backgrounds

## Icons

The Rijkshuisstijl Community uses **open-source icons** from:

### 1. Tabler Icons (Default)

**Source:** [https://tabler.io/icons](https://tabler.io/icons)

**License:** MIT (free to use)

**Usage:** Most icons come from this set by default

### 2. Custom Community Icons

Contributors can add custom icons to the icon set.

**Documentation:** See the [Icon component Storybook page](https://rijkshuisstijl-community.vercel.app/?path=/docs/rhc-icon--docs)

**Custom icon implementation example:**
```typescript
// Add custom icon to the set
import { registerIcon } from '@rijkshuisstijl-community/web-components';

registerIcon('custom-icon', '<svg>...</svg>');
```

## Design Tokens

All color, spacing, typography, and other design tokens are available as npm packages.

### Installation

```bash
npm install @rijkshuisstijl-community/design-tokens
```

### Usage

```scss
@use "@rijkshuisstijl-community/design-tokens/dist/index.scss";
```

Or in JavaScript:

```javascript
import tokens from '@rijkshuisstijl-community/design-tokens';
```

**Token Structure:**
```json
{
  "rh": {
    "color": {
      "donkerblauw": {
        "value": "#154273"
      }
    }
  }
}
```

## Components

The community provides ready-to-use components in multiple formats:

### CSS Components

```bash
npm install @rijkshuisstijl-community/components-css
```

### React Components

```bash
npm install @rijkshuisstijl-community/components-react
```

### Web Components

```bash
npm install @rijkshuisstijl-community/web-components
```

### Twig Templates

```bash
npm install @rijkshuisstijl-community/components-twig
```

## Implementation for Our Nextcloud App

### Current Status

✅ **Design Tokens** - Manually implemented in `css/tokens/rijkshuisstijl.css`
❌ **Fonts** - Not using the community package
❌ **Logo** - Not implemented
❌ **Icons** - Not integrated

### Recommended Improvements

#### 1. Install Font Package

```bash
cd /home/rubenlinde/nextcloud-docker-dev/workspace/server/apps-extra/nldesign
npm install @rijkshuisstijl-community/font
```

#### 2. Create Font CSS File

Create `css/fonts.css`:

```scss
// Import Fira Sans as Rijkshuisstijl alternative
@use "@fontsource/fira-sans/400.css";
@use "@fontsource/fira-sans/400-italic.css";
@use "@fontsource/fira-sans/700.css";
@use "@fontsource/fira-sans/700-italic.css";
```

#### 3. Load Font CSS in Application.php

```php
private function injectThemeCSS($serverContainer): void {
    // ... existing code ...
    
    // Add fonts
    \OCP\Util::addStyle(self::APP_ID, 'fonts');
}
```

#### 4. Add Nederland Map Icon as Logo

Create `css/logo.css`:

```css
/* Replace Nextcloud logo with nederland-map icon */
#header .logo {
    background-image: url('data:image/svg+xml,...nederland-map-svg...');
    background-size: contain;
    background-position: center;
}
```

#### 5. Update Token File to Use Fira Sans

Update `css/tokens/rijkshuisstijl.css`:

```css
:root {
    /* Use Fira Sans as alternative */
    --nldesign-font-family: 'Fira Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Cantarell, Ubuntu, 'Helvetica Neue', Arial, sans-serif;
}
```

## Legal Considerations

### What You CAN Use (No Permission Needed)

✅ Design tokens (colors, spacing, etc.)
✅ Fira Sans font (open source alternative)
✅ nederland-map icon as logo replacement
✅ Tabler Icons icon set
✅ CSS/React/Web Components from community
✅ Color palette (#154273, etc.)

### What You CANNOT Use (Without Permission)

❌ Official Rijkslogo (crown logo)
❌ RijksoverheidSansWebText fonts
❌ "Rijksoverheid" wordmark/branding
❌ Official photography/imagery from rijkshuisstijl.nl

### Who Needs Permission?

**You NEED permission if:**
- You work for a Dutch central government organization
- You're a contractor developing for Rijksoverheid
- Your website will use official Rijksoverheid branding

**You DON'T need permission if:**
- Using for demonstrations/prototypes
- Using community alternatives (Fira Sans, nederland-map)
- Building for municipalities (use their design systems instead)
- Educational/learning purposes

## Resources

### Official Resources (Require Account)

- [Rijkshuisstijl Website](https://www.rijkshuisstijl.nl/) - Official guidelines
- [Request Access](https://www.rijkshuisstijl.nl/contact) - Contact for permissions

### Community Resources (Open Access)

- [GitHub Repository](https://github.com/nl-design-system/rijkshuisstijl-community)
- [Storybook](https://rijkshuisstijl-community.vercel.app/) - Component documentation
- [NL Design System](https://nldesignsystem.nl/) - General NL DS information
- [GitHub Discussions](https://github.com/nl-design-system/rijkshuisstijl-community/discussions) - Ask questions

### NPM Packages

- `@rijkshuisstijl-community/font` - Fonts (Fira Sans)
- `@rijkshuisstijl-community/design-tokens` - Design tokens
- `@rijkshuisstijl-community/components-css` - CSS components
- `@rijkshuisstijl-community/components-react` - React components
- `@rijkshuisstijl-community/web-components` - Web Components
- `@fontsource/fira-sans` - Fira Sans web fonts

## Quick Start for Nextcloud Implementation

### Step 1: Install Dependencies

```bash
cd nldesign/
npm init -y  # If package.json doesn't exist
npm install @rijkshuisstijl-community/font @fontsource/fira-sans
```

### Step 2: Build Assets

```bash
# If using a build process
npm run build
```

### Step 3: Update CSS

```css
/* Add to theme.css */
@import url('~@fontsource/fira-sans/400.css');
@import url('~@fontsource/fira-sans/700.css');
```

### Step 4: Enable in Nextcloud

```bash
php occ app:enable nldesign
```

### Step 5: Test

Navigate to Settings → Administration → Theming and verify the font renders correctly.

## Conclusion

**For our Nextcloud app**, we should:

1. ✅ Use the **community font package** (`@rijkshuisstijl-community/font` with Fira Sans)
2. ✅ Use the **nederland-map icon** as logo (no permission needed)
3. ✅ Keep using our **manually defined design tokens** (they match the community tokens)
4. ❌ Do NOT include proprietary Rijkslogo or RijksoverheidSansWebText fonts

This keeps us **compliant, open-source, and legal** while still achieving ~90% visual similarity to the official Rijkshuisstijl!
