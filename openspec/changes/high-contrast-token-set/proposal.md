# Proposal: high-contrast-token-set

## Why

`docs/GOVERNMENT-FEATURES.md` A-08 lists "Hoog contrast modus — High-contrast token set" with status **Gepland** (planned). It is the one accessibility line on the checklist that is honestly marked as not-yet-shipped, and it has no spec and no code. It is also genuinely demanded: Dutch government accessibility obligations run through **Digitoegankelijk** (Tijdelijk besluit digitale toegankelijkheid overheid) and **EN 301 549** (which folds in WCAG 2.1/2.2). The Specter intelligence corpus shows an **Accessibility** requirement cluster of 266 requirements across 122 tenders and a **WCAG accessibility** cluster of 168 / 93, with EN 301 549 explicitly cited. Beyond the 4.5:1 AA baseline, EN 301 549 references user-adjustable contrast and operating-system high-contrast modes (WCAG 1.4.6 AAA 7:1, plus `forced-colors` support), and competitor government systems (US Web Design System, GOV.UK) ship high-contrast / forced-colors handling. nldesign has the architecture to deliver this cleanly and does not.

The app already generalized from a single "7-layer CSS" theme into a **design-system model**: `design-systems.json` declares selectable systems (`none`, `nldesign`, `summer-breeze`), each a named ordered list of stylesheets under `css/systems/{id}/`, and `token-sets.json` binds each token set to a design system. Adding a high-contrast option is therefore a data + CSS addition along an existing seam — a new `css/systems/high-contrast/` system and a `hoog-contrast` token set — not new runtime code. Verifying it meets AAA is exactly what the companion `shipped-token-set-contrast-audit` gate does.

## What Changes

- **NEW** — A `high-contrast` design system in `design-systems.json` with its stylesheets under `css/systems/high-contrast/` (fonts + theme + element-overrides), tuned for maximum legibility (pure/near-pure foreground on background, heavy borders, thick focus rings).
- **NEW** — A `hoog-contrast` token set in `token-sets.json` + `css/tokens/hoog-contrast.css` bound to the `high-contrast` system, whose fixed pairs meet WCAG 2.2 **AAA** (≥7:1 body text, ≥4.5:1 large text / non-text UI). It appears in the admin token-set dropdown like any other set.
- **NEW** — `@media (prefers-contrast: more)` and `forced-colors: active` handling in the high-contrast system stylesheet so the theme cooperates with OS-level high-contrast / Windows High Contrast Mode (EN 301 549), keeping focus indicators and text visible via `system-color` keywords.
- **STATUS** — `GOVERNMENT-FEATURES.md` A-08 moves from **Gepland** to **Beschikbaar** only once the set ships and passes the AAA verdict in the contrast audit; documented as an accessibility feature.

## Capabilities

### New Capabilities
- `high-contrast-token-set` — A selectable WCAG-AAA high-contrast theme plus OS high-contrast (`prefers-contrast` / `forced-colors`) cooperation, delivered along the existing design-system seam.

## Decisions

1. **A design system + token set, not a global toggle.** nldesign's selection model is "pick a token set (which names a design system)". A high-contrast *token set* fits that model, is per-instance selectable, and needs no new UI, controller, or config key — unlike a bespoke on/off switch.
2. **AAA target, verified by the contrast audit.** The set targets WCAG 2.2 AAA (7:1). Correctness is not asserted in prose; it is checked by `shipped-token-set-contrast-audit`, so A-08 can only be marked "Beschikbaar" when the gate is green. This change depends on that audit capability.
3. **Cooperate with OS high-contrast, don't fight it.** `forced-colors: active` means the OS overrides colors; the stylesheet uses `system-color` keywords and preserves focus/borders rather than hardcoding values the OS will discard. This is the EN 301 549 expectation.
4. **CSS + data only.** No PHP: the existing `DesignSystemService` already resolves `design_system` → stylesheet list and `Application::injectThemeCSS()` already loads it. Adding a system and a set is a data/CSS change along that seam (same shape as the existing `summer-breeze` system).
5. **Naming.** Token set id `hoog-contrast` (Dutch, consistent with government audience), display name "Hoog contrast (WCAG AAA)". Design system id `high-contrast` (consistent with the English `design-systems.json` ids `none`/`nldesign`/`summer-breeze`).

## Impact

- **nldesign app only** — `design-systems.json`, `token-sets.json`, new `css/systems/high-contrast/*.css`, new `css/tokens/hoog-contrast.css`. No PHP, no route, no config key.
- **Depends on** `shipped-token-set-contrast-audit` for its AAA verification gate (sequence after it, or land together).
- **No database migration.**
- **Bump `info.xml` version** so the new CSS assets bust the immutable cache.

## Rollback Strategy

- Remove the `high-contrast` system and `hoog-contrast` set entries + their CSS; any instance that had selected it falls back per the existing "unknown/removed set" discovery behavior (active set resets to a shipped default).
- Re-mark `GOVERNMENT-FEATURES.md` A-08 as "Gepland".
