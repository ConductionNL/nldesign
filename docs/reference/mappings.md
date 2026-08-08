---
sidebar_position: 2
---

# Nextcloud projection mappings

The executable mapping is deliberately limited to `css/theme.css`. A ready
profile supplies four app-owned values:

| App-owned input | Nextcloud outputs |
| --- | --- |
| `--nldesign-font-family` | `--font-face` |
| `--nldesign-color-primary` | `--color-primary`, `--color-primary-element` |
| `--nldesign-color-primary-text` | `--color-primary-text`, `--color-primary-element-text` |
| `--nldesign-color-primary-hover` | `--color-primary-hover`, `--color-primary-element-hover` |

The load-bearing CSS uses only root and body theme-state guards. It has no
component or structural Nextcloud selectors and makes no attempt to map every
source token positionally. Similar token names do not prove that components
have the same semantic role.

## Deliberate exclusions

Nextcloud remains responsible for:

- main surfaces and derived dark-mode values;
- explicit user themes, high contrast, and inversion filters;
- configured logos and background images;
- status, text, border, layout, spacing, radius, motion, shadow, and
  feature-specific variables;
- third-party app component styling.

The projection also yields to the explicit OpenDyslexic theme and to both
explicit and operating-system high-contrast preferences.

## Retired selector experiments

The former login-footer and app-menu selector styles are not part of the
runtime. The footer selector hid the instance identity link together with the
slogan, while the menu selector depended on header structure that was not
present across the claimed Nextcloud range. A future surface adapter must have
an accurately bounded purpose and packaged browser evidence for every claimed
major before it can become reachable.

## Evidence

Exact mapping count is intentionally not a quality metric. Compatibility
claims require rendered tests across the declared Nextcloud, browser, mode,
and enabled-app matrix.
