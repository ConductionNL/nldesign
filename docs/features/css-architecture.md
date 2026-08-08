---
sidebar_position: 2
---

# Bounded CSS projection

The load-bearing runtime adds one explicit three-layer cascade to normal and
login templates:

```text
fonts
  -> tokens/validated-ready-profile
  -> theme
```

`RuntimeStylesheetPlan` owns this order and has unit coverage. The template
listener validates the active profile before forming a stylesheet name and
fails open if state or style planning cannot be read.

## Responsibilities

| Layer | Purpose |
| --- | --- |
| fonts | Four local Fira Sans faces in WOFF and WOFF2 |
| profile | A reviewed `nextcloud-core-v1` projection in the app namespace |
| theme | Map font and primary interaction roles to Nextcloud core custom properties |

The projection uses only root and body theme-state guards; it contains no
component or structural Nextcloud selectors. The retired login-footer and
app-menu experiments are not loaded. Selector-based surface adaptations require
a separate compatibility contract and packaged browser evidence.

## Accessibility ownership

The font mapping does not override Nextcloud's explicit OpenDyslexic theme.
The colour mapping does not run for explicit light or dark high-contrast
themes, or when the operating system requests more contrast. Nextcloud keeps
ownership of dark-mode derivation, page surfaces, layout, images, and all
other framework variables.

## Evidence limits

The quality gate rejects generated Vue scope hashes, remote profile assets,
unresolved source placeholders, unsafe CSS constructs, and profiles that lack
the required projection properties. It also rejects unconsumed ready-profile
properties and measures the supplied light/dark primary pairs at 4.5:1. Static
checks do not prove visual,
accessibility, browser, app, or Nextcloud-major compatibility; those claims
need packaged integration evidence.
