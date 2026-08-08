---
status: reviewed
reviewed_date: 2026-08-08
---

# CSS Projection — Technical Design

## Purpose

The load-bearing projection adapts four statically gated profile roles to a small
Nextcloud core custom-property surface. It does not equate the full NL Design
System vocabulary with Nextcloud components and does not style Nextcloud DOM
structure.

## Runtime order

`RuntimeStylesheetPlan` owns one explicit three-layer precedence contract:

1. `fonts`
2. `tokens/{validated-ready-profile}`
3. `theme`

`TemplateStylesListener` applies the plan for normal and login templates. It
reads app-scoped state, validates the profile before constructing a stylesheet
name, and fails open with a warning. `Application` only registers listeners.

## Boundaries

- A ready profile must define font family, primary, primary text, and primary
  hover in the app namespace; there is no cross-organisation defaults layer.
- A profile with explicit dark colours repeats those colours in the narrowly
  permitted system-dark/default branch so untouched account preferences do not
  retain the light projection on a dark Nextcloud shell.
- `theme.css` maps only those roles and may use root/body theme-state guards,
  but no component or structural Nextcloud selectors.
- Runtime never edits package files or generates CSS.
- Explicit OpenDyslexic and high-contrast preferences outrank branding.
- Nextcloud retains page surfaces, dark-mode derivation, images, layout, and
  all unmapped framework variables.
- The retired login-footer and app-menu selector experiments are absent from
  the runtime. A future surface adapter requires its own compatibility evidence.
- WCAG conformance is measured; it is not inferred from token names.

## Packaged assets

`scripts/build-fonts.js` copies an exact allowlist of eight Fira Sans font files
and the SIL OFL 1.1 notice. Missing input fails the build. No runtime request
contacts an external font service.

Unused Amsterdam icon/logo package assets are not redistributed because their
package excluded those assets from its open-source licence.

## Compatibility posture

Static linting proves syntax and selected invariants only. Declared Nextcloud
32 through 34 support requires packaged browser evidence across core/login,
theme preferences, and named app surfaces before release.
