---
status: reviewed
reviewed_date: 2026-02-28
---

# CSS Architecture Specification

## Purpose
Defines the 7-layer CSS architecture that transforms NL Design System tokens into Nextcloud-compatible theming. The layered approach ensures that organization-specific tokens cascade correctly, incomplete token sets fall back gracefully, and NL Design System component tokens (using the `--utrecht-*` prefix) are bridged to the `--nldesign-*` namespace. The load order is critical: each layer builds on the previous one.

## Requirements

### REQ-CSS-001: Seven-Layer Load Order
The app MUST load CSS files in a strict 7-layer order during the boot phase, with conditional layers for optional features.

#### Scenario: Standard CSS load order
- GIVEN the nldesign app boots via `Application::boot()`
- WHEN `injectThemeCSS()` is called
- THEN CSS files MUST be loaded in this exact order via `\OCP\Util::addStyle()`:
  1. `fonts` (Layer 1 -- @font-face declarations)
  2. `defaults` (Layer 2 -- all `--nldesign-*` token defaults)
  3. `tokens/{activeTokenSet}` (Layer 3 -- organization overrides)
  4. `utrecht-bridge` (Layer 4 -- `--utrecht-*` to `--nldesign-component-*` mapping)
  5. `theme` (Layer 5 -- `--nldesign-*` to Nextcloud element selectors)
  6. `overrides` (Layer 6 -- Nextcloud `--color-*` variable mappings)
  7. `element-overrides` (Layer 7 -- low-level element styling)

#### Scenario: Conditional CSS loading
- GIVEN the hide_slogan setting is enabled (value `'1'`)
- WHEN `injectThemeCSS()` is called
- THEN `hide-slogan` CSS MUST be loaded after the 7 core layers
- AND if show_menu_labels is also enabled, `show-menu-labels` CSS MUST also be loaded

#### Scenario: Layer order enforced
- GIVEN a later layer references a token defined in an earlier layer
- WHEN the CSS cascade is applied
- THEN later layers MUST be able to read values set by earlier layers
- AND Layer 3 (token set) MUST override Layer 2 (defaults) for the same `--nldesign-*` variable
- AND Layer 4 (utrecht-bridge) MUST override Layer 2 (defaults) for `--nldesign-component-*` variables

### REQ-CSS-002: Layer 1 -- Font Declarations
The fonts layer MUST declare Fira Sans @font-face rules for all required weights and styles.

#### Scenario: Fira Sans font faces registered
- GIVEN the `css/fonts.css` file is loaded
- WHEN the browser processes the @font-face rules
- THEN it MUST register `'Fira Sans'` at weight 400 normal
- AND it MUST register `'Fira Sans'` at weight 400 italic
- AND it MUST register `'Fira Sans'` at weight 700 normal
- AND it MUST register `'Fira Sans'` at weight 700 italic
- AND each @font-face MUST use `font-display: swap` for performance
- AND each @font-face MUST specify both woff2 and woff formats

### REQ-CSS-003: Layer 2 -- Default Token Definitions
The defaults layer MUST define ALL `--nldesign-*` tokens on `:root` with Rijkshuisstijl-based values.

#### Scenario: All token categories defined
- GIVEN the `css/defaults.css` file is loaded
- WHEN the `:root` rule is processed
- THEN it MUST define tokens for all categories: brand colors (primary, primary-text, primary-hover, primary-light), status colors (error, warning, success, info), background colors (hover, dark, darker, header, nav), text colors (text, text-muted, text-light), border colors (border, border-dark), focus colors, link colors (link, link-hover, link-visited), button colors (primary-background, primary-text, primary-border, primary-hover), typography (font-family), border-radius (default, small, large, rounded, pill), animation timing, and placeholder colors

#### Scenario: Component tokens defined
- GIVEN the `css/defaults.css` file is loaded
- WHEN the `:root` rule is processed
- THEN it MUST define `--nldesign-component-*` tokens for: button (base, hover, active, disabled, focus, primary-action, secondary-action), textbox (base, states), form field/select/fieldset, headings (h1-h6 with font-size, font-weight, line-height, color), paragraph, link, table, badge, separator, and ordered/unordered lists

#### Scenario: Defaults serve as fallback
- GIVEN an incomplete token set is loaded in Layer 3
- AND that token set does NOT define `--nldesign-color-error`
- WHEN the error color is used in Layers 5-7
- THEN it MUST resolve to the Rijkshuisstijl default `#d52b1e` from Layer 2

### REQ-CSS-004: Layer 3 -- Organization Token Overrides
Token set CSS files MUST override `--nldesign-*` variables on `:root` for organization-specific values.

#### Scenario: Organization colors applied
- GIVEN the active token set is `amsterdam`
- AND `css/tokens/amsterdam.css` defines `--nldesign-color-primary: #004699`
- WHEN the CSS cascade resolves `--nldesign-color-primary`
- THEN the resolved value MUST be `#004699` (Amsterdam blue)
- AND all variables in Layers 4-7 referencing `--nldesign-color-primary` MUST use this value

#### Scenario: Rijkshuisstijl lint tokens
- GIVEN the active token set is `rijkshuisstijl`
- AND it defines `--nldesign-color-logo-background: #154273`, `--nldesign-size-lint: 48px`, `--nldesign-size-lint-height: 96px`
- WHEN the header renders
- THEN a colored lint/ribbon MUST appear behind the logo

#### Scenario: Non-lint theme (no logo background)
- GIVEN the active token set does NOT define `--nldesign-color-logo-background`
- WHEN the header renders
- THEN the lint pseudo-element MUST be invisible (0px width, transparent background from defaults)
- AND the logo MUST display in its natural colors without a filter

### REQ-CSS-005: Layer 4 -- Utrecht Bridge Mapping
The Utrecht bridge MUST map `--utrecht-*` component tokens to `--nldesign-component-*` tokens with fallback to Layer 2 defaults.

#### Scenario: Utrecht token present in token set
- GIVEN a token set defines `--utrecht-button-primary-action-background-color: #123456`
- WHEN Layer 4 processes the bridge mapping
- THEN `--nldesign-component-button-primary-action-background-color` MUST resolve to `#123456`

#### Scenario: Utrecht token absent (fallback to defaults)
- GIVEN a token set does NOT define any `--utrecht-*` button tokens
- WHEN Layer 4 processes the bridge mapping
- THEN `--nldesign-component-button-primary-action-background-color` MUST fall back to `var(--nldesign-color-primary)` from Layer 2 defaults

#### Scenario: No circular references
- GIVEN the bridge CSS uses `var()` with fallback values
- WHEN fallback values are specified
- THEN fallback values MUST NOT self-reference (e.g. `var(--nldesign-foo, var(--nldesign-foo))` is forbidden)
- AND fallback values MUST reference either a concrete value or a variable defined in Layer 2

#### Scenario: Component categories bridged
- GIVEN the bridge CSS is loaded
- THEN it MUST map `--utrecht-*` tokens for: button (base, hover, active, disabled, focus, primary-action, secondary-action), textbox, form field/select, headings (h1-h6), paragraph, link, table, badge, separator, lists, breadcrumb, and code

### REQ-CSS-006: Layer 5 -- Theme Element Mapping
The theme layer MUST apply `--nldesign-*` tokens to Nextcloud element selectors using `!important`. Additionally, it includes Nextcloud CSS variable overrides on `body` and `body[data-themes]` for higher specificity than the `:root` overrides in Layer 6.

#### Scenario: Nextcloud CSS variables overridden (high-specificity)
- GIVEN Layer 5 (`css/theme.css`) is loaded
- WHEN the `body` and `body[data-themes]` rules are processed
- THEN `--color-primary` MUST be set to `var(--nldesign-color-primary) !important`
- AND `--color-primary-text` MUST be set to `var(--nldesign-color-primary-text) !important`
- AND status colors (error, warning, success, info) MUST be mapped
- AND border-radius variables MUST be mapped
- NOTE: These overrides supplement (and take higher specificity than) the `:root` overrides in Layer 6

#### Scenario: Header styled from tokens
- GIVEN Layer 5 is loaded
- WHEN the `#header` element renders
- THEN background MUST use `var(--nldesign-color-header-background)`
- AND text color MUST use `var(--nldesign-color-header-text)`
- AND the header MUST have `overflow: visible` (for lint bar to hang below)

#### Scenario: Login page styled
- GIVEN the user is on the login page (`#body-login`)
- WHEN Layer 5 styles are applied
- THEN the original Nextcloud header MUST be hidden (`display: none`)
- AND the guest-box MUST have a white background with no shadows
- AND the lint/ribbon pseudo-elements MUST render on the login box
- AND primary buttons MUST use `--nldesign-component-button-primary-action-*` tokens

#### Scenario: Focus states for accessibility
- GIVEN any interactive element receives keyboard focus
- WHEN `:focus-visible` is triggered
- THEN the element MUST show a 2px solid outline using `var(--nldesign-color-focus)`
- AND the outline offset MUST be 2px

### REQ-CSS-007: Layer 6 -- Nextcloud Variable Overrides
The overrides layer MUST map Nextcloud `--color-*` CSS variables to `--nldesign-*` tokens on `:root`, while intentionally NOT overriding dark mode variables.

#### Scenario: Primary color variables mapped
- GIVEN Layer 6 (`css/overrides.css`) is loaded
- WHEN the `:root` rule is processed
- THEN all primary-related Nextcloud variables (--color-primary, --color-primary-text, --color-primary-hover, --color-primary-element, etc.) MUST be mapped to corresponding `--nldesign-*` tokens with `!important`

#### Scenario: Main background intentionally NOT overridden
- GIVEN Layer 6 is loaded
- WHEN the `:root` rule is processed
- THEN `--color-main-background` MUST NOT be overridden
- AND `--color-main-background-rgb` MUST NOT be overridden
- AND `--color-main-background-translucent` MUST NOT be overridden
- AND `--color-background-plain` MUST NOT be overridden
- AND each intentionally-unset variable MUST have a comment explaining why

#### Scenario: Dark mode compatibility preserved
- GIVEN a user has Nextcloud dark mode enabled
- WHEN the nldesign overrides are applied
- THEN `--background-invert-if-dark` MUST NOT be overridden
- AND `--background-invert-if-bright` MUST NOT be overridden
- AND the dark mode auto-calculated variables MUST continue to function

#### Scenario: Typography variable mapped
- GIVEN Layer 6 is loaded
- THEN `--font-face` MUST be mapped to `var(--nldesign-font-family) !important`

### REQ-CSS-008: Layer 7 -- Element-Level Overrides
The element-overrides layer MUST apply NL Design styling to specific HTML elements and Nextcloud components.

#### Scenario: Font family forced on all elements
- GIVEN Layer 7 (`css/element-overrides.css`) is loaded
- WHEN the font forcing rules are processed
- THEN `font-family: var(--nldesign-font-family) !important` MUST be applied to specific element selectors (html, body, div, span, p, h1-h6, a, button, input, textarea, select, label, li, ul, ol)
- AND it MUST also be applied via wildcard descendant selectors (`html body *`, `#body-user *`, `#app *`, `#content *`) to ensure complete coverage across all Nextcloud contexts

#### Scenario: Header icons visible on white background
- GIVEN the header has a white background
- WHEN Layer 7 is loaded
- THEN `#header .header-end svg` and related selectors MUST have `filter: invert(1) brightness(0) contrast(100)` to make icons visible
- AND avatar images (`#header .header-end .avatardiv img`) MUST be excluded from the filter
- AND user-status icons MUST be excluded from the filter

#### Scenario: App navigation styled as card
- GIVEN the app navigation sidebar renders
- WHEN Layer 7 styles are applied
- THEN `#app-navigation` MUST use `var(--color-main-background)` as background
- AND it MUST have a right margin of 30px (card layout effect)
- AND the closed state (`.app-navigation--close`) MUST have 0 margin

#### Scenario: MyDash excluded from solid backgrounds
- GIVEN the MyDash app is active
- WHEN solid background rules are applied
- THEN elements with `.mydash-widget` or `.tile-widget` classes MUST be excluded
- AND the MyDash container MUST have transparent background
