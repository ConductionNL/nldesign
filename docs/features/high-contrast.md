---
sidebar_position: 15
---

# High-Contrast Theme (WCAG AAA)

The **"Hoog contrast (WCAG AAA)"** token set is a maximum-legibility, black-on-white
theme for low-vision users. It fulfils [GOVERNMENT-FEATURES](../GOVERNMENT-FEATURES.md)
A-08 ("Hoog contrast modus") and the Digitoegankelijk / EN 301 549 expectation of a
user-selectable high-contrast option beyond the 4.5:1 AA baseline.

## What it delivers

- Pure black (`#000000`) interactive and foreground colour on a pure white
  (`#ffffff`) background: the fixed WCAG pairs reach **21:1**, well above the
  WCAG 2.2 **AAA** thresholds (≥ 7:1 body text, ≥ 4.5:1 non-text UI).
- Heavy, always-visible borders on inputs, buttons and table rows; a thick
  focus ring; underlined links; no low-contrast placeholder text.
- Bundled, self-hosted Fira Sans (no external CDN).

Its AAA compliance is not asserted in prose — it is verified at the AAA threshold
by the [contrast audit](contrast-audit.md) and recorded in
[`../reference/contrast-report.md`](../reference/contrast-report.md).

## How to enable it

Select **"Hoog contrast (WCAG AAA)"** from the token-set dropdown in
**Settings → Administration → Theming**, exactly like any other token set. No
extra UI, controller, route or config key is involved — it is delivered along the
existing design-system seam (`design-systems.json` + `css/systems/high-contrast/` +
`token-sets.json`).

## Operating-system high-contrast cooperation

The high-contrast system stylesheet honours two media features so it cooperates
with OS-level settings rather than fighting them (EN 301 549):

- `@media (prefers-contrast: more)` strengthens borders and the focus ring further.
- `@media (forced-colors: active)` (e.g. Windows High Contrast Mode) hands colour
  control to the operating system: the theme uses CSS `system-color` keywords
  (`CanvasText`, `ButtonText`, `LinkText`) and preserves visible text, control
  borders and focus indicators instead of hardcoding colours the OS would discard.
