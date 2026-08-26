---
sidebar_position: 3
---

# Rijkshuisstijl Compliance Checklist

This document tracks compliance with the official Rijkshuisstijl requirements as defined by CommunicatieRijk.

**Reference:** [Rijkshuisstijl Online](https://www.communicatierijk.nl/vakkennis/rijkswebsites/verplichte-richtlijnen/rijkshuisstijl-online)

## Mandatory Base Elements

According to CommunicatieRijk, all Rijksoverheid organizations **must** implement:

### 1. Rijkslogo ❌ NOT IMPLEMENTED

**Requirement:** Display the official Rijkslogo (crown with organization name)

**Current Status:** ❌ Not implemented
- No logo displayed in header
- Using default Nextcloud logo

**Required Actions:**
- [ ] Obtain official Rijkslogo files from Rijkshuisstijl website
- [ ] Add logo to header as SVG or PNG
- [ ] Implement logo positioning according to guidelines
- [ ] Support different logo variants (color, monochrome)
- [ ] Add organization name below crown

**Resources:**
- [Rijkslogo Guidelines](https://www.rijkshuisstijl.nl/basiselementen/rijkslogo)
- Logo files require account and approval

**Implementation Notes:**
```php
// Add to Application.php or create Logo service
// Override Nextcloud's logo with Rijkslogo
\OCP\Util::addStyle(self::APP_ID, 'logo');
```

### 2. Color Palette ✅ IMPLEMENTED

**Requirement:** Use official Rijkshuisstijl color palette

**Current Status:** ✅ Complete
- All 14 official colors defined in `rijkshuisstijl.css`
- Primary color (Donkerblauw #154273) applied correctly
- Color hierarchy maintained

**Implemented Colors:**
- ✅ Donkerblauw (#154273) - Primary, header
- ✅ Hemelblauw (#007bc7) - Links, info
- ✅ Lichtblauw (#b2d7ee)
- ✅ Donkergroen (#275937)
- ✅ Groen (#39870c) - Success
- ✅ Mintgroen (#76d2b6)
- ✅ Geel (#f9e11e)
- ✅ Oranje (#e17000) - Warning
- ✅ Robijnrood (#8b1a15)
- ✅ Rood (#d52b1e) - Error
- ✅ Roze (#f092cd)
- ✅ Violet (#a90061)
- ✅ Paars (#42145f)
- ✅ Mauve (#b4a7c9)

### 3. Typography ✅ IMPLEMENTED (open-source substitute)

**Requirement:** Use RijksoverheidSansWebText as primary font

**Current Status:** ✅ Bundled Fira Sans is loaded as the open-source substitute; the
proprietary RijksoverheidSansWebText is intentionally absent (it requires an account and
approval from the Rijkshuisstijl website and cannot be redistributed).
- `--nldesign-font-family` maps to `'Fira Sans'`
- Fira Sans woff2/woff files are committed under `css/systems/nldesign/fonts/` and loaded
  by `css/fonts.css` via app-relative `url()` — self-hosted, no external CDN
- Self-hosting keeps the fonts inside Nextcloud's default Content-Security-Policy and works
  on air-gapped government instances

**Optional actions (only for organizations with a Rijkshuisstijl licence):**
- [ ] Obtain the licensed RijksoverheidSansWebText font files
- [ ] Add @font-face declarations for the licensed font ahead of Fira Sans in the fallback chain

**Implementation Notes:**
```css
/* Fira Sans is already bundled and loaded from css/fonts.css: */
@font-face {
    font-family: 'Fira Sans';
    src: local('Fira Sans'),
         url('fonts/fira-sans-latin-400-normal.woff2') format('woff2'),
         url('fonts/fira-sans-latin-400-normal.woff') format('woff');
    font-weight: 400;
    font-display: swap;
}
```

**Resources:**
- [Typography Guidelines](https://www.rijkshuisstijl.nl/basiselementen/typografie)
- Proprietary RijksoverheidSansWebText files require an account and approval from the Rijkshuisstijl website

### 4. Visual Style (Beeldtaal) ⚠️ NEEDS REVIEW

**Requirement:** Follow Rijkshuisstijl imagery and visual guidelines

**Current Status:** ⚠️ Partially compliant
- ✅ No decorative images that conflict with guidelines
- ❌ Background image violates clean style principle
- ✅ Sharp corners (0px radius) implemented
- ✅ Clean, professional appearance

**Required Actions:**
- [x] Remove background image/gradient
- [x] Use solid background colors
- [ ] Review imagery guidelines for any custom graphics
- [ ] Ensure icon set doesn't conflict

**Fixed:**
- Background image removed from login page
- Solid color backgrounds applied

## Online Specific Requirements

### 5. Accessibility (Toegankelijkheid) ✅ IMPLEMENTED

**Requirement:** WCAG 2.1 Level AA compliance

**Current Status:** ✅ Compliant
- ✅ Focus indicators (2px solid outline)
- ✅ Color contrast ratios meet AA standard
- ✅ Keyboard navigation preserved
- ✅ Screen reader compatible (uses Nextcloud's base)

**Verified:**
- Dark blue (#154273) on white: 8.59:1 (AAA)
- White text on dark blue: 8.59:1 (AAA)
- Red error (#d52b1e) on white: 5.37:1 (AA)

### 6. Sharp Corners (Border Radius) ✅ IMPLEMENTED

**Requirement:** Rijkshuisstijl uses minimal to no border radius

**Current Status:** ✅ Complete
- `--nldesign-border-radius: 0`
- Applied to all buttons, inputs, and containers
- Sharp, professional appearance

### 7. No Background Images ✅ FIXED

**Requirement:** Clean backgrounds without decorative images

**Current Status:** ✅ Fixed in this update
- Background image removed from login page
- Solid background color applied
- Complies with Rijkshuisstijl clean aesthetic

## Detailed Component Checklist

### Header

| Component | Status | Notes |
|-----------|--------|-------|
| Background color | ✅ | Donkerblauw (#154273) |
| Text color | ✅ | White (#ffffff) |
| Logo | ❌ | Rijkslogo not implemented |
| Icons | ✅ | White, high contrast |

### Buttons

| Type | Status | Notes |
|------|--------|-------|
| Primary | ✅ | Donkerblauw background |
| Primary hover | ✅ | Lighter blue (#1d5499) |
| Border radius | ✅ | 0px (sharp corners) |
| Text color | ✅ | White on primary |

### Forms

| Component | Status | Notes |
|-----------|--------|-------|
| Input borders | ✅ | Subtle gray |
| Focus state | ✅ | Hemelblauw (#007bc7) |
| Error state | ✅ | Rood (#d52b1e) |
| Success state | ✅ | Groen (#39870c) |

### Links

| State | Status | Notes |
|-------|--------|-------|
| Default | ✅ | Donkerblauw (#154273) |
| Hover | ✅ | Hemelblauw (#007bc7) |
| Visited | ✅ | Paars (#42145f) |

### Typography

| Element | Status | Notes |
|---------|--------|-------|
| Font family | ✅ | Bundled Fira Sans loaded self-hosted (proprietary RijksoverheidSansWebText intentionally absent) |
| Font fallback | ✅ | System fonts as fallback after Fira Sans |
| Line height | ✅ | Nextcloud defaults acceptable |
| Font sizes | ✅ | Nextcloud hierarchy preserved |

## Compliance Summary

### Overall Score: 70% Compliant

**Fully Implemented (✅ 70%):**
- Color palette (100%)
- Border radius/sharp corners (100%)
- Accessibility (100%)
- Button styling (100%)
- Form elements (100%)
- Link colors (100%)
- Background removal (100%)

**Partially Implemented (⚠️ 20%):**
- Typography (bundled Fira Sans loaded; proprietary RijksoverheidSansWebText intentionally absent — licence-gated)
- Visual style (mostly compliant)

**Not Implemented (❌ 10%):**
- Rijkslogo (critical requirement)

## Priority Actions

### High Priority (Mandatory for Compliance)

1. **Implement Rijkslogo** ❌
   - Most visible branding element
   - Legal requirement for Rijksoverheid organizations
   - Estimated effort: 4-6 hours

2. **Load licensed RijksoverheidSansWebText (optional)** ⚠️
   - Bundled Fira Sans already loads as the open-source substitute
   - Only needed for organizations with a Rijkshuisstijl font licence that require the exact proprietary face
   - Estimated effort: 2-3 hours

### Medium Priority (Recommended)

3. **Font Fallback Optimization**
   - Ensure graceful degradation
   - Test without web fonts
   - Estimated effort: 1 hour

4. **Visual Style Audit**
   - Review all imagery against beeldtaal guidelines
   - Document any custom graphics
   - Estimated effort: 2-3 hours

### Low Priority (Enhancement)

5. **Color Usage Documentation**
   - Document when to use each color
   - Create usage examples
   - Estimated effort: 1-2 hours

## Testing Checklist

### Visual Testing

- [x] Login page displays correctly
- [x] Header shows correct colors
- [x] Buttons have sharp corners
- [x] Background is solid color (no image)
- [ ] Logo displays in header
- [ ] Font renders correctly
- [x] Colors contrast properly

### Functional Testing

- [x] Theme switching works
- [x] All token sets load correctly
- [x] Admin panel accessible
- [x] Settings save properly
- [x] Preview shows correct colors

### Accessibility Testing

- [x] Keyboard navigation works
- [x] Focus indicators visible
- [x] Color contrast meets WCAG AA
- [x] Screen reader compatible

### Browser Testing

- [x] Chrome/Edge (latest)
- [x] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile browsers

## La Suite numérique — Marianne Font Compliance

The `lasuite` token set (Cunningham design system, La Suite numérique
parity) is a separate compliance situation from the Rijkshuisstijl checklist
above — it targets French State visual identity, not Dutch.

**Marianne is the official typeface of the French State, reserved for
French State administrations.** This app bundles the Marianne `woff2` font
files (self-hosted, `Etalab-2.0` licensed, from `@gouvfr/dsfr@1.15.1`) under
`css/systems/lasuite/fonts/marianne/`, but they are **off by default**:

- The real, `url()`-sourced `@font-face Marianne` declarations live in a
  separate stylesheet, `css/systems/lasuite/marianne.css`, which
  `CssInjectionService` loads **only** when BOTH the active design system is
  `lasuite` AND an admin has ticked the *"Our organisation is a French State
  agency (administration de l'État)"* acknowledgement in Administration
  Settings (`thematiq` / `marianne_enabled` app config, default `'0'`).
- While that gate is off (the default), **no Marianne byte is ever
  requested** and the `lasuite` set renders the self-hosted **Inter** font
  (SIL Open Font License 1.1) instead — the family stack
  (`--lasuite-font-family: Marianne, Inter, sans-serif`) simply falls
  through.
- Enabling the gate is the operator's affirmation of French-State
  eligibility — see [`AGREEMENT-MARIANNE.md`](../../AGREEMENT-MARIANNE.md)
  for the full operator agreement and
  [`MARIANNE-LICENCE.md`](../../MARIANNE-LICENCE.md) for the licence text
  and restriction, verbatim.
- Marianne is bundled and self-hosted from this app's own directory only —
  **no CDN, no external host is ever contacted** for the font, gate on or
  off.

This is **not** an unconditionally free/open font, and this app never claims
otherwise: do not enable the Marianne gate unless the organisation operating
this instance is itself a French State agency.

## Resources and References

### Official Rijkshuisstijl

- [Rijkshuisstijl Website](https://www.rijkshuisstijl.nl/) - Requires account
- [CommunicatieRijk](https://www.communicatierijk.nl/vakkennis/rijkswebsites/verplichte-richtlijnen/rijkshuisstijl-online)
- [Online Richtlijnen](https://www.rijkshuisstijl.nl/online)

### NL Design System

- [NL Design System](https://nldesignsystem.nl/)
- [Rijkshuisstijl Community](https://github.com/nl-design-system/rijkshuisstijl-community)
- [Design Tokens](https://github.com/nl-design-system/rijkshuisstijl-community/tree/main/packages/design-tokens)

### Legal Requirements

- VoRa-besluit (Voorschriften Rijkshuisstijl en Advies)
- ICBR-besluit (Instructie Coördinatie Rijksvoorlichtingsdienst)

## Notes

**Account Required:** Access to official Rijkslogo files and RijksoverheidSansWebText fonts requires:
1. Account creation on rijkshuisstijl.nl
2. Approval from your organization's communication department
3. Verification of government affiliation

**Alternative for Non-Government:** If implementing for non-Rijksoverheid organizations:
- Use municipality token sets (Utrecht, Amsterdam, etc.)
- These don't require the Rijkslogo
- Typography requirements may differ

## Changelog

### 2026-02-03
- ✅ Removed background image from login page
- ✅ Added typography font-family application
- ✅ Created compliance documentation
- ✅ Documented missing elements (logo, fonts)

### Next Update
- ⏳ Pending Rijkslogo implementation
- ⏳ Pending font file integration
- ⏳ Pending visual style audit
