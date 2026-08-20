# CSS Architecture — La Suite Design System Delta

**Spec refs**: `css-architecture` (REQ-CSS-011, REQ-CSS-012), `lasuite-stack` (new, this change)
**Standards**: CSS Custom Properties, CSS Cascade and Specificity

## MODIFIED Requirements

### Requirement: Design System Resolution

The app MUST support multiple design systems and resolve the correct one for each token set.
Shipped design systems are `none`, `nldesign`, `summer-breeze`, `high-contrast`, and `lasuite`.

#### Scenario: Design system resolved from token set metadata

- GIVEN `token-sets.json` contains an entry with `design_system: "nldesign"`
- WHEN `DesignSystemService::getTokenSetMeta()` is called for that token set
- THEN the `design_system` field MUST be returned
- AND `DesignSystemService::getDesignSystem("nldesign")` MUST return the nldesign stylesheet bundle

#### Scenario: La Suite design system resolves

- GIVEN `token-sets.json` contains the `lasuite` entry with `design_system: "lasuite"`
- WHEN `DesignSystemService::getDesignSystem("lasuite")` is called
- THEN it MUST return the lasuite bundle with exactly four stylesheets in order:
  `systems/lasuite/fonts`, `systems/lasuite/defaults`, `systems/lasuite/bridge`,
  `systems/lasuite/element-overrides`
- AND activating the `lasuite` token set MUST load that bundle followed by `tokens/lasuite`

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
structure, one directory per shipped design system.

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
  bridge.css, element-overrides.css) with its font binaries under `css/systems/lasuite/fonts/`
- AND the lasuite files MUST NOT conflict with any other system's files (the `--lasuite-*`
  namespace is exclusive to this directory)

#### Scenario: Future design systems have separate directories

- GIVEN a new design system "custom-ds" is added
- WHEN its stylesheets are declared in `design-systems.json`
- THEN its CSS files MUST be in `css/systems/custom-ds/`
- AND they MUST NOT conflict with nldesign files
