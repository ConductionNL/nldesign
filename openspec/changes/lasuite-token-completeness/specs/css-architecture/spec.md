# css-architecture

**Spec refs:** css-architecture, lasuite-stack, lasuite-parity, token-sets
**Standards:** CSS Custom Properties (W3C); CSS Cascade and Specificity; WCAG 2.1 AA

## MODIFIED Requirements

### Requirement: Design System Resolution

The app MUST support multiple design systems and resolve the correct one for each token set.
Shipped design systems are `none`, `nldesign`, `summer-breeze`, `high-contrast`, `lasuite`, and
(optionally) `cunningham`.

#### Scenario: Design system resolved from token set metadata

- GIVEN `token-sets.json` contains an entry with `design_system: "nldesign"`
- WHEN `DesignSystemService::getTokenSetMeta()` is called for that token set
- THEN the `design_system` field MUST be returned
- AND `DesignSystemService::getDesignSystem("nldesign")` MUST return the nldesign stylesheet bundle

#### Scenario: La Suite design system resolves

- GIVEN `token-sets.json` contains the `lasuite` entry with `design_system: "lasuite"`
- WHEN `DesignSystemService::getDesignSystem("lasuite")` is called
- THEN it MUST return the lasuite bundle with exactly five stylesheets in order:
  `systems/lasuite/fonts`, `systems/lasuite/defaults`, `systems/lasuite/brand-override`,
  `systems/lasuite/bridge`, `systems/lasuite/element-overrides`
- AND activating the `lasuite` token set MUST load that bundle followed by `tokens/lasuite`

#### Scenario: Cunningham blue-base design system resolves

- GIVEN `token-sets.json` contains a `cunningham` entry with `design_system: "cunningham"`
- WHEN `DesignSystemService::getDesignSystem("cunningham")` is called
- THEN it MUST return a bundle of exactly four stylesheets in order: `systems/lasuite/fonts`,
  `systems/lasuite/defaults`, `systems/lasuite/bridge`, `systems/lasuite/element-overrides`
  (the same shared files as `lasuite`, **without** `systems/lasuite/brand-override`)
- AND activating the `cunningham` token set MUST resolve the blue base (`--color-primary #1A509F`
  — brand-650, the same scale step the shared bridge/element-overrides derive `--color-primary`
  from for lasuite's violet `#4844AD`; `#0659C5` is brand-600, a different, unrendered step)

#### Scenario: Unknown design system falls back safely

- GIVEN a token set references a design system id not in `design-systems.json`
- WHEN `DesignSystemService::getDesignSystem()` is called with the unknown id
- THEN it MUST return a fallback with an empty `stylesheets` array
- AND no CSS MUST be loaded for the design system layers
- AND the app MUST not throw an exception

#### Scenario: Design systems are cached per request

- GIVEN `DesignSystemService::getDesignSystems()` is called multiple times in one request
- WHEN the second call is made
- THEN the cached result MUST be returned without re-reading `design-systems.json`

### Requirement: CSS Files in Systems Directory Structure

Design system CSS files MUST be organized in a `css/systems/{designSystemId}/` directory
structure, one directory per shipped design system. The `lasuite` and `cunningham` design systems
share a single `css/systems/lasuite/` directory (the `cunningham` bundle reuses the lasuite files
minus the brand override); no separate `css/systems/cunningham/` directory is required.

#### Scenario: NL Design system files in correct directory

- GIVEN the nldesign design system is active
- WHEN stylesheets are loaded
- THEN all CSS files MUST be located in `css/systems/nldesign/` (fonts.css, defaults.css,
  utrecht-bridge.css, theme.css, overrides.css, element-overrides.css)
- AND token set files MUST remain in `css/tokens/` regardless of design system

#### Scenario: La Suite system files in correct directory

- GIVEN the lasuite design system is active
- WHEN stylesheets are loaded
- THEN all CSS files MUST be located in `css/systems/lasuite/` (fonts.css, defaults.css,
  brand-override.css, bridge.css, element-overrides.css) with its font binaries under
  `css/systems/lasuite/fonts/`
- AND the lasuite files MUST NOT conflict with any other system's files (the `--lasuite-*` and
  `--lasuite--*` namespaces are exclusive to this directory)

#### Scenario: Cunningham reuses the lasuite directory

- GIVEN the cunningham design system is active
- WHEN stylesheets are loaded
- THEN they MUST resolve to files under `css/systems/lasuite/` (fonts, defaults, bridge,
  element-overrides), reusing the shared generated defaults
- AND `systems/lasuite/brand-override` MUST NOT be loaded for the cunningham bundle

#### Scenario: Future design systems have separate directories

- GIVEN a new design system "custom-ds" is added
- WHEN its stylesheets are declared in `design-systems.json`
- THEN its CSS files MUST be in `css/systems/custom-ds/`
- AND they MUST NOT conflict with nldesign files
