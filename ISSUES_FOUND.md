# Critical Issues Found & Solutions

## Issues Identified

### 1. ❌ **Fonts NOT Loading - CSP Violation**

**Problem**: Content Security Policy blocks CDN fonts
```
Loading the font 'https://cdn.jsdelivr.net/npm/@fontsource/fira-sans...' 
violates the following Content Security Policy directive: "font-src 'self' data:"
```

**Impact**: Fira Sans cannot load from CDN, falling back to system fonts

**Solution**: Need to either:
- A) Download fonts locally to `css/fonts/` directory
- B) Use base64 encoded fonts in CSS
- C) Update Nextcloud CSP configuration

### 2. ❌ **"Goedemorgen" Not Visible**

**Problem**: White text on white background
**Solution**: Added CSS to force text color, but needs testing

### 3. ❌ **Icon Gradients Still Showing**

**Problem**: File type icons (PDF, PNG, ODT) have colorful gradients
**Solution**: Need more aggressive CSS to override SVG gradients

### 4. ❌ **Nextcloud Logo Still Showing**

**Problem**: Logo replacement CSS not working
**Solution**: Need better selector for logo element

### 5. ❌ **Header Overflow Scroll**

**Problem**: Top right corner has weird scroll behavior
**Solution**: Added overflow: hidden to header

---

## Recommended Actions

### Priority 1: Fix Fonts (CSP Issue)

**Option A: Download Fonts Locally** (RECOMMENDED)

```bash
cd nldesign
mkdir -p css/fonts
# Download from node_modules
cp node_modules/@fontsource/fira-sans/files/fira-sans-latin-400-normal.woff2 css/fonts/
cp node_modules/@fontsource/fira-sans/files/fira-sans-latin-700-normal.woff2 css/fonts/
```

Then update `fonts.css` to use local paths:
```css
@font-face {
  font-family: 'Fira Sans';
  src: url('../fonts/fira-sans-latin-400-normal.woff2') format('woff2');
  font-weight: 400;
  font-style: normal;
}
```

**Option B: Use System Font Stack** (TEMPORARY)

Update token files to use safe fallback:
```css
--nldesign-font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
```

### Priority 2: Fix Icon Gradients

Need to add CSS that removes gradients from file icons more aggressively.

### Priority 3: Fix Logo

Need to find correct selector for Nextcloud logo and replace it.

---

## Next Steps

1. Download fonts locally (fixes CSP issue)
2. Update fonts.css with local paths
3. Test font loading in browser DevTools
4. Fix remaining visual issues (icons, logo)

