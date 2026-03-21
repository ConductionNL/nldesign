# CSS Architecture — Technical Design

## Overview

The NL Design System Nextcloud app uses a **7-layer CSS cascade** to transform abstract design tokens into a fully-themed Nextcloud interface. Each layer has a single responsibility and builds on the layers before it. The ordering is enforced in PHP at boot time and cannot be reordered without breaking the cascade.

The three namespaces involved are:

| Namespace | Owner | Purpose |
|---|---|---|
| `--nldesign-*` | This app | Internal design language (brand + component tokens) |
| `--utrecht-*` | NL Design System community | Vendor component tokens (bridged to `--nldesign-component-*`) |
| `--color-*`, `--border-radius`, `--font-face` | Nextcloud core | Framework variables consumed by Nextcloud's own components |

---

## Load Order — `Application::injectThemeCSS()`

File: `lib/AppInfo/Application.php`

The method calls `\OCP\Util::addStyle()` in this exact sequence:

```
1. fonts              → css/fonts.css
2. defaults           → css/defaults.css
3. tokens/{tokenSet}  → css/tokens/{activeTokenSet}.css
4. utrecht-bridge     → css/utrecht-bridge.css
5. theme              → css/theme.css
6. overrides          → css/overrides.css
7. element-overrides  → css/element-overrides.css

[conditional] hide-slogan       → css/hide-slogan.css        (when hide_slogan = '1')
[conditional] show-menu-labels  → css/show-menu-labels.css   (when show_menu_labels = '1')
```

The `tokenSet` value is read from `IConfig::getAppValue('nldesign', 'token_set', 'rijkshuisstijl')`. The default is `rijkshuisstijl`. Token set files live in `css/tokens/{name}.css` and there are currently 37 municipality/organisation files.

---

## Layer Descriptions

### Layer 1 — `css/fonts.css` — @font-face Declarations

Registers **Fira Sans** as the design system typeface — an open-source substitute for RijksoverheidSansWebText. Four `@font-face` rules cover weight 400/700 and style normal/italic. Each rule:

- Specifies `local()` as the first source (avoids network fetch if already installed)
- Provides both `woff2` and `woff` formats for broad browser support
- Sets `font-display: swap` so text renders immediately in the fallback font while Fira Sans loads

Font files are co-located in `css/fonts/`.

### Layer 2 — `css/defaults.css` — `--nldesign-*` Token Defaults

Defines **every** `--nldesign-*` token on `:root` using Rijkshuisstijl-based values. This is the fallback layer: if a token set in Layer 3 does not define a particular token, the value from this layer is used throughout Layers 4–7.

Token categories defined here:

- **Brand colors**: `--nldesign-color-primary`, `-text`, `-hover`, `-light`, `-light-hover`
- **Status colors**: `--nldesign-color-error`, `-warning`, `-success`, `-info` (each with `-rgb` and `-hover` where applicable)
- **Background colors**: `-hover`, `-dark`, `-darker`; header/nav backgrounds; logo lint tokens
- **Text colors**: `--nldesign-color-text`, `-muted`, `-light`
- **Border colors**: `--nldesign-color-border`, `-dark`
- **Focus**: `--nldesign-color-focus` (rgba), `-rgb`
- **Link colors**: `-link`, `-hover`, `-visited`
- **Button colors**: `-button-primary-background`, `-text`, `-border`, `-hover`
- **Typography**: `--nldesign-font-family` (Fira Sans + system fallback stack)
- **Border radius**: default (0), small (0), large (4px), rounded (0), pill (100px)
- **Animation timing**: `--nldesign-animation-quick` (100ms), `-slow` (300ms)
- **Placeholder colors**: `-placeholder-light`, `-dark`
- **Component tokens** (`--nldesign-component-*`): button (base/hover/active/disabled/focus/primary-action/secondary-action), textbox (base/states), form field/select/fieldset, headings h1–h6 (font-size/weight/line-height/color), paragraph, link, table, badge, separator, ordered/unordered lists

Component tokens in defaults.css reference brand tokens via `var()` (e.g. `--nldesign-component-button-primary-action-background-color: var(--nldesign-color-primary)`). This ensures component tokens stay consistent when only brand tokens are overridden in Layer 3.

### Layer 3 — `css/tokens/{name}.css` — Organisation Token Overrides

Organisation-specific files override **only the tokens they need to change** on `:root`. Everything else falls back to Layer 2 values. This means an incomplete token set is safe — it degrades gracefully.

Each token set file is self-contained and documents its source design system. Two patterns exist:

**Non-lint themes** (e.g. `amsterdam.css`): Define primary, status, border, link, and text colors. Leave lint tokens undefined, so the default `--nldesign-size-lint: 0px` from Layer 2 keeps the pseudo-element invisible.

**Lint themes** (e.g. `rijkshuisstijl.css`): Additionally define:
- `--nldesign-color-logo-background: #154273` — colored ribbon behind logo
- `--nldesign-size-lint: 48px` — ribbon width
- `--nldesign-size-lint-height: 96px` — ribbon height (hangs below 50px header)
- `--nldesign-logo-center: 32px` — horizontal offset of logo within ribbon
- `--nldesign-logo-filter: brightness(0) invert(1)` — inverts logo for white-on-blue

Token sets may also define organisation-specific palette variables under a private namespace (e.g. `--rh-color-lintblauw`, `--amsterdam-color-red`) for documentation purposes. These are not consumed by later layers.

### Layer 4 — `css/utrecht-bridge.css` — `--utrecht-*` → `--nldesign-component-*` Mapping

The NL Design System community currently uses the `--utrecht-*` prefix for component tokens. This bridge layer reads those values and writes them into the `--nldesign-component-*` namespace so that the rest of the cascade does not need to know about vendor prefixes.

Every bridge mapping uses `var(--utrecht-X, fallback)` where:
- `--utrecht-X` is the vendor token (present if the token set defines it)
- `fallback` is either a concrete value or a `var(--nldesign-*)` reference from Layer 2

**Critical rule — no circular references**: The fallback value must never reference the same `--nldesign-component-*` variable being assigned. For example, this is forbidden:

```css
/* WRONG — circular reference, produces guaranteed-invalid value */
--nldesign-component-button-color: var(--utrecht-button-color, var(--nldesign-component-button-color));

/* CORRECT — fallback references a Layer 2 brand token */
--nldesign-component-button-color: var(--utrecht-button-color, var(--nldesign-color-primary));
```

This layer is marked as **temporary** in comments. When the NL Design System adopts a vendor-neutral prefix, this file can be deleted and defaults.css becomes the sole source of component token values.

Component categories bridged: button (all states and variants), textbox (including via `--utrecht-form-input-*` fallback chain), form field/select/fieldset, headings h1–h6, paragraph, link, table, badge, separator, ordered/unordered lists, breadcrumb, code.

### Layer 5 — `css/theme.css` — `--nldesign-*` → Nextcloud Element Selectors

This layer is the primary styling layer. It applies `--nldesign-*` tokens to actual Nextcloud HTML elements using a combination of:

- **`body[data-themes]` and `body`** selectors with `!important` to override Nextcloud's scoped theme variables
- **`#header`** — background, text color, `overflow: visible` (required for lint bar)
- **`#nextcloud::before`** — the lint/ribbon pseudo-element (uses lint token variables, invisible by default)
- **`#nextcloud .logo`** — dynamic logo via `--nldesign-logo-url`, positioned at bottom of lint strip
- **`#body-login`** — hides Nextcloud's built-in header, applies guest-box pseudo-elements for lint and logo on the login card
- **Button classes** — `.button-vue--vue-primary`, `.button-vue--vue-secondary`, `button.primary`, etc.
- **Form elements** — inputs, textareas, selects, labels with state variants (hover, focus, invalid, disabled)
- **Navigation** — `#app-navigation` background, active item highlight
- **Links** — `a`, `a:hover`, `a:visited` with header exceptions
- **Focus** — `*:focus-visible` with 2px solid outline using `--nldesign-color-focus`
- **Typography** — `h1`–`h6`, `p` from component tokens; `font-family` on body
- **Dashboard** — removes gradients, removes transparency, excludes `.mydash-widget` and `.tile-widget` from solid backgrounds

The theme layer uses `!important` throughout because Nextcloud's own component styles (often Vue scoped) use high-specificity or also `!important`. Without it, token-based values are silently overridden.

### Layer 6 — `css/overrides.css` — Nextcloud `--color-*` Variable Mapping

This layer maps Nextcloud's internal CSS variable names to `--nldesign-*` tokens on `:root`. It serves as an exhaustive catalogue: every Nextcloud CSS variable is either mapped with `!important` or commented out with an explicit reason.

**Variables intentionally NOT overridden** (with comments explaining why):

- `--color-main-background` and its derivatives (`-rgb`, `-translucent`, `-blur`) — overriding breaks dark mode; Nextcloud calculates these
- `--color-background-plain`, `--color-background-plain-text` — admin/user configured
- `--color-primary-element-text-dark` — auto-calculated dark variant
- `--color-text-maxcontrast-default`, `-background-blur` — auto-calculated per theme
- `--color-error-text`, `--color-warning-text`, `--color-success-text`, `--color-info-text` — auto-calculated contrast
- `--background-invert-if-dark`, `--background-invert-if-bright` — dark mode inversion filters
- Layout variables (`--header-height`, `--navigation-width`, etc.) — core Nextcloud layout constants
- Spacing/touch targets (`--default-clickable-area`, `--default-grid-baseline`) — accessibility standards
- Gradient variables — depend on unmapped background vars
- Images (`--image-background`, `--image-logo`) — admin setting

This deliberate restraint is the **dark mode preservation strategy**. By leaving main background and inversion filter variables alone, Nextcloud's dark mode system continues to function correctly. The nldesign overrides only affect brand color expressions (primary, status, borders, text) that should be consistent regardless of light/dark mode.

### Layer 7 — `css/element-overrides.css` — Low-Level Element Styling

The final core layer handles cases that require highly specific selectors or that `theme.css` could not address at a higher abstraction level.

Key responsibilities:

**Font forcing**: Applies `--nldesign-font-family` to an exhaustive list including `html`, `html body *`, all semantic elements, and `#body-user *`. This catches elements that ignore inherited `font-family` due to browser user-agent stylesheets or scoped component styles.

**Header-end icon visibility**: The header has a white background; Nextcloud's icons are white SVGs by default (designed for dark headers). Layer 7 applies `filter: invert(1) brightness(0) contrast(100)` to `#header .header-end svg` and related selectors to make icons black on white. Exceptions are explicitly carved out for `.avatardiv img` (real user photo) and `.user-status-icon svg` (colored status dot).

**App navigation as card**: `#app-navigation` receives `margin-right: 30px` to create a visual gap between the sidebar card and the main content area. The closed state (`.app-navigation--close`) gets `margin-right: 0` to prevent a ghost gap.

**App content as card**: `#app-content` and `.app-content` get `--color-main-background` as background (respecting dark mode) and `border-radius: var(--nldesign-border-radius)`.

**MyDash exclusion**: Elements under `#app-mydash` and `body:has(#mydash-app)` get transparent backgrounds so MyDash's own widget styling is not overridden.

**Solid backgrounds**: Panels, widgets, and dashboard elements outside MyDash get `background: #ffffff` and `backdrop-filter: none` to prevent translucency effects.

**Text color corrections**: A targeted list of element selectors (excluding `#header *`, `.tile-widget *`, and `.button-vue--vue-primary *`) forces `--nldesign-color-text`. Button text color corrections are handled separately to ensure primary button text is always `--nldesign-color-primary-text`.

**Dropdown / popover text**: `.popovermenu *` is forced to `#000000` so dropdown menus (which may appear on any background) remain readable.

**Header overflow chain**: `#header` needs `overflow: visible` so the lint pseudo-element can hang below the 50px header. Child `.header-menu` is reset to `overflow: hidden` to avoid layout bleed.

---

## Variable Naming Convention

### Brand tokens: `--nldesign-color-{category}-{variant}`

```
--nldesign-color-primary
--nldesign-color-primary-text
--nldesign-color-primary-hover
--nldesign-color-primary-light
--nldesign-color-primary-light-hover
--nldesign-color-error
--nldesign-color-error-rgb
--nldesign-color-header-background
--nldesign-color-logo-background
--nldesign-size-lint
--nldesign-size-lint-height
--nldesign-logo-url
--nldesign-logo-filter
--nldesign-logo-center
--nldesign-font-family
--nldesign-border-radius
--nldesign-border-radius-{small|large|rounded|pill}
--nldesign-animation-quick
--nldesign-animation-slow
```

### Component tokens: `--nldesign-component-{component}-{property}`

```
--nldesign-component-button-background-color
--nldesign-component-button-primary-action-background-color
--nldesign-component-textbox-border-color
--nldesign-component-heading-1-font-size
--nldesign-component-form-field-label-color
--nldesign-component-link-color
```

States are inlined in the property segment:
```
--nldesign-component-button-hover-background-color
--nldesign-component-button-active-background-color
--nldesign-component-button-disabled-background-color
--nldesign-component-button-focus-border-color
```

### Utrecht vendor tokens (Layer 4 input only): `--utrecht-{component}-{property}`

These are never set by this app — they are read from token set files if present. The bridge always provides a fallback so their absence is safe.

---

## Specificity Strategy

| Situation | Technique |
|---|---|
| Override Nextcloud's scoped CSS variables | `body[data-themes], body { --color-*: value !important }` |
| Override element styles from Nextcloud components | `!important` on property declarations |
| Override `--nldesign-*` tokens between layers | Normal cascade (later layer wins on same `:root` selector) |
| Target Nextcloud-specific structure | ID selectors (`#header`, `#app-navigation`, `#body-login`) |
| Exclude special containers | `:not(.mydash-widget)` chaining |

The `!important` on Nextcloud CSS variable declarations in `theme.css` and `overrides.css` is required because Nextcloud's own theming system also sets these variables with `!important` on `body[data-themes]`. Without matching `!important`, the design tokens silently lose.

`--nldesign-*` tokens are set without `!important` (plain `:root` rules) because they only need to win over each other, and that is handled by layer order alone.

---

## Dark Mode Preservation

Nextcloud's dark mode works by computing a set of background variables and filter utilities (`--background-invert-if-dark`, `--background-invert-if-bright`) at the framework level. These depend on `--color-main-background` being unchanged.

The architecture preserves dark mode by:

1. **Never overriding** `--color-main-background` or its derivatives in `overrides.css`
2. **Never overriding** `--background-invert-if-dark` or `--background-invert-if-bright`
3. Allowing `--color-main-background` to be set by Nextcloud's theming admin panel
4. Using `var(--color-main-background)` (not a hardcoded value) when element-overrides.css needs to set a card background

This means the NL Design token colors (primary, status, borders, text) are always applied, while the background plane and contrast inversion adjust automatically per user preference.

---

## Conditional Layers

Two optional CSS files are loaded after the 7 core layers based on admin configuration:

**`css/hide-slogan.css`** (when `hide_slogan = '1'`): Hides `footer.guest-box` on the login page, which Nextcloud uses to display the site tagline/payoff. Loaded after the core 7 so it can safely override without needing to know the specifics of the core layers.

**`css/show-menu-labels.css`** (when `show_menu_labels = '1'`): Forces text labels to be visible on app navigation menu items. By default Nextcloud hides these at smaller viewport widths.

---

## Token File Structure

Organisation token files (`css/tokens/*.css`) follow this pattern:

1. File-level JSDoc comment with organisation name, source design system URL, and a note on primary color semantics
2. Single `:root { }` block
3. Sections in order: primary colors, organisation-specific palette (private namespace), logo URL, lint/ribbon tokens (if applicable), background colors, header/nav colors, text colors, status colors, border colors, focus, link colors, button colors, typography, border radius
4. Only tokens that differ from Rijkshuisstijl defaults need to be included — partial files are valid

Currently 37 organisation token files are implemented, covering municipalities (Amsterdam, Utrecht, Rotterdam, Den Haag, Tilburg, etc.), provinces (Zuid-Holland), national organisations (DUO, VNG, Rijkshuisstijl), and community organisations (Demodam, XXLLNC, NoaberKracht).
