---
sidebar_position: 6
---

# App compatibility

NL Design can influence another Nextcloud app only where that app consumes a
Nextcloud custom property or markup selector that this projection reaches.
Compatibility is therefore a tested relationship between versions, not an
automatic consequence of using Nextcloud or Vue.

## Guidance for app authors

Prefer Nextcloud's documented semantic variables and components. For example:

```css
.my-button {
  color: var(--color-primary-element-text);
  background: var(--color-primary-element);
  border-radius: var(--border-radius-element);
}
```

Avoid hardcoded brand colors, assumptions that the header is dark, generated
Vue data-v hashes, and selectors tied to another app's private DOM.

Use app-owned variables only when the dependency on NL Design is intentional.
Otherwise depend on Nextcloud variables so the app remains compatible with
native Theming when NL Design is absent.

## What to test

At minimum, exercise:

- native Nextcloud and several materially different ready profiles;
- light, dark, and high-contrast user themes where supported;
- keyboard focus, zoom, narrow viewport, and overflow behavior;
- normal, error, warning, disabled, selected, and hover states;
- pages rendered before and after a profile change;
- the exact Nextcloud and target-app versions being declared.

Passing stylelint or finding a mapped variable is not evidence that text
contrast, icons, component state, or layout is correct.

## Compatibility reports

A proposed surface-specific override should name its target app, tested
version range, selectors, expected failure behavior, and browser evidence. It
must ship as an isolated, bounded adapter rather than entering the selector-
free core projection, and should be removed when a stable Nextcloud variable
becomes available.

No repository-wide claim is currently made that all Nextcloud apps are
compatible.
