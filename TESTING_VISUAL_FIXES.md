# NL Design System Visual Fixes - Testing Guide

## Issues Fixed

### ✅ 1. Removed All Gradients
**Problem**: Menu icons and backgrounds had gradients (not NL Design style)
**Fix**: Removed all `background-image`, `background-gradient`, and `filter` properties
**Test**: Icons should be solid colors without any gradient effects

### ✅ 2. Removed Background Images
**Problem**: Dashboard had decorative background image
**Fix**: Force `background-image: none !important` on all main containers
**Test**: Solid white background (or theme color) everywhere, no images

### ✅ 3. Removed Transparency
**Problem**: Widgets and panels were semi-transparent
**Fix**: Force `opacity: 1 !important` and remove `backdrop-filter`
**Test**: All widgets should be solid, not see-through

### ✅ 4. Fixed Rounded Corners
**Problem**: Elements had rounded corners (not Rijkshuisstijl/Amsterdam style)
**Fix**: Apply `border-radius: var(--nldesign-border-radius) !important` to all elements
**Test**: Sharp corners (0px) for Rijkshuisstijl/Amsterdam, rounded (4px) for others

### ✅ 5. Fixed Font Application
**Problem**: Fonts not consistently applied
**Fix**: Force `font-family: var(--nldesign-font-family) !important` on all elements with `*` selector
**Test**: All text should use Fira Sans

### ✅ 6. Fixed "Aanpassen" Button Text Overflow
**Problem**: Button text was cut off
**Fix**: Added `min-width: max-content`, `white-space: nowrap`, `overflow: visible`
**Test**: Button text should not be cut off, buttons expand to fit content

### ✅ 7. Fixed Active Menu Item Style
**Problem**: Active menu items didn't have proper NL Design style
**Fix**: Added left border accent (4px solid primary color) + bold text
**Test**: Active menu items have colored left border and bold text

### ✅ 8. Removed Shadows
**Problem**: Shadows are not typical in NL Design System
**Fix**: Force `box-shadow: none !important` on all elements
**Test**: No drop shadows on buttons, panels, modals

---

## Testing Checklist

Navigate to: **http://localhost:8080/apps/dashboard/**

### Dashboard Page Tests

#### Background & Transparency
- [ ] ✅ No background image visible
- [ ] ✅ Solid white background
- [ ] ✅ Widgets are fully opaque (not transparent)
- [ ] ✅ No blurred backgrounds behind panels

#### Icons & Gradients
- [ ] ✅ Menu icons are solid (no gradient)
- [ ] ✅ App icons are solid colors
- [ ] ✅ No color transitions or fades

#### Corners & Borders
- [ ] ✅ Sharp corners (0px) for Rijkshuisstijl/Amsterdam
- [ ] ✅ Rounded corners (4px) for Utrecht/Den Haag/Rotterdam
- [ ] ✅ Consistent corner style across all elements

#### Typography
- [ ] ✅ All text uses Fira Sans font
- [ ] ✅ Headers use Fira Sans
- [ ] ✅ Button text uses Fira Sans
- [ ] ✅ Widget content uses Fira Sans

#### Buttons
- [ ] ✅ "Aanpassen" button text fully visible
- [ ] ✅ No text overflow on any buttons
- [ ] ✅ Buttons expand to fit content
- [ ] ✅ Button colors match theme (blue/red/green)

#### Active Menu Items
- [ ] ✅ Active item has colored left border (4px)
- [ ] ✅ Active item text is bold
- [ ] ✅ Active item background is light tint of primary color
- [ ] ✅ Clear visual distinction from inactive items

#### Shadows
- [ ] ✅ No drop shadows on widgets
- [ ] ✅ No shadows on buttons
- [ ] ✅ No shadows on panels
- [ ] ✅ Clean, flat design

---

## Visual Testing by Theme

### Rijkshuisstijl
```
Expected:
- Header: Deep blue (#154273)
- Background: Pure white
- Corners: SHARP (0px)
- Font: Fira Sans
- Active menu: Light blue bg + bold + left border
- Buttons: Blue with sharp corners
- No gradients, no transparency, no shadows
```

### Utrecht
```
Expected:
- Header: Red (#cc0000)
- Background: Pure white
- Corners: ROUNDED (4px)
- Font: Fira Sans
- Active menu: Light red bg + bold + left border
- Buttons: Red with rounded corners
- No gradients, no transparency, no shadows
```

### Amsterdam
```
Expected:
- Header: Red (#ec0000)
- Background: Pure white
- Corners: SHARP (0px)
- Font: Fira Sans
- Active menu: Light tint + bold + left border
- Buttons: Blue (#004699) with sharp corners
- No gradients, no transparency, no shadows
```

### Den Haag
```
Expected:
- Header: Green (#1a7a3e)
- Background: Pure white
- Corners: ROUNDED (4px)
- Font: Fira Sans
- Active menu: Light green bg + bold + left border
- Buttons: Green with rounded corners
- No gradients, no transparency, no shadows
```

### Rotterdam
```
Expected:
- Header: Green (#00811f)
- Background: Pure white
- Corners: ROUNDED (4px)
- Font: Fira Sans
- Active menu: Light green bg + bold + left border
- Buttons: Green with rounded corners
- No gradients, no transparency, no shadows
```

---

## Browser DevTools Verification

### Check Font Loading
1. Open DevTools (F12)
2. Inspect any text element
3. Check Computed styles
4. Look for `font-family`
5. Should see: `"Fira Sans", -apple-system, ...`

### Check No Gradients
1. Inspect icon element
2. Check Computed styles
3. Look for `background-image`
4. Should see: `none`

### Check No Transparency
1. Inspect widget/panel
2. Check Computed styles
3. Look for `opacity`
4. Should see: `1`
5. Look for `backdrop-filter`
6. Should see: `none`

### Check Border Radius
1. Inspect button or panel
2. Check Computed styles
3. Look for `border-radius`
4. Should see: `0px` (Rijkshuisstijl/Amsterdam) or `4px` (others)

### Check Active Menu Border
1. Click a menu item to make it active
2. Inspect the active element
3. Check Computed styles
4. Look for `border-left`
5. Should see: `4px solid [primary-color]`

---

## Before/After Comparison

### Before (Issues)
- ❌ Background image visible
- ❌ Transparent widgets (can see through)
- ❌ Icon gradients (color transitions)
- ❌ Rounded corners (when should be sharp)
- ❌ "Aanpassen" text cut off
- ❌ System fonts instead of Fira Sans
- ❌ Active menu items look like inactive
- ❌ Drop shadows everywhere

### After (Fixed)
- ✅ Clean white background
- ✅ Solid opaque widgets
- ✅ Solid color icons
- ✅ Correct corner radius per theme
- ✅ All button text visible
- ✅ Fira Sans font everywhere
- ✅ Active menu items clearly marked
- ✅ Flat design (no shadows)

---

## Hard Refresh Instructions

If changes don't appear immediately:

### Browser Hard Refresh
- **Chrome/Edge**: `Ctrl + Shift + R` (Windows/Linux) or `Cmd + Shift + R` (Mac)
- **Firefox**: `Ctrl + Shift + R` (Windows/Linux) or `Cmd + Shift + R` (Mac)
- **Safari**: `Cmd + Option + R`

### Clear Nextcloud Cache
```bash
docker exec -u 33 nextcloud php occ maintenance:repair --include-expensive
```

### Clear Browser Cache
1. Open DevTools (F12)
2. Go to Network tab
3. Right-click → "Clear browser cache"
4. Reload page

---

## Known Good Selectors (Now Fixed)

```css
/* Dashboard backgrounds - now solid */
#body-user,
#content,
.app-dashboard { background: white; no images; }

/* Icons - now solid */
.icon,
.app-menu-icon { no gradients; }

/* Widgets - now opaque */
.panel,
.widget { opacity: 1; no transparency; }

/* Buttons - now properly sized */
.button-vue { min-width: max-content; no overflow; }

/* Active menu - now styled */
.app-navigation-entry.active { 
  border-left: 4px solid primary;
  font-weight: bold;
  background: light primary;
}

/* Corners - now correct */
* { border-radius: var(--nldesign-border-radius); }

/* Font - now everywhere */
* { font-family: 'Fira Sans'; }
```

---

## Troubleshooting

### Issue: Still seeing gradients
**Solution**: Hard refresh browser, check CSS is actually loaded

### Issue: Still seeing background image
**Solution**: Clear Nextcloud cache, might be cached asset

### Issue: Fonts look wrong
**Solution**: Check Network tab to verify Fira Sans is loading from CDN

### Issue: "Aanpassen" still cut off
**Solution**: Inspect button, check if our CSS is being overridden

### Issue: Active menu items not styled
**Solution**: Click item to activate it, some items need interaction

---

## Success Criteria

✅ **All checks must pass:**
1. No background images visible
2. All widgets are solid (not transparent)
3. No icon gradients
4. Correct border radius for theme
5. All text uses Fira Sans
6. "Aanpassen" button text fully visible
7. Active menu items have left border + bold text
8. No drop shadows anywhere

---

**Status**: All fixes applied and cache cleared  
**Next Step**: Navigate to http://localhost:8080/apps/dashboard/ and verify all items above
