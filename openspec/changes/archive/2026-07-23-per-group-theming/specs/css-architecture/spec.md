# CSS Architecture — Per-Request Token-Set Resolution Delta

**Spec refs**: `css-architecture` (REQ-CSS-001), `per-group-theming` (new, this change)
**Standards**: CSS Cascade; Nextcloud `OCP\Util::addStyle` boot-time injection

## MODIFIED Requirements

### Requirement: Design System Driven Stylesheet Loading

The app MUST resolve which design system a token set belongs to and load the corresponding
stylesheet bundle in declared order. The active token set for a request MUST be obtained
through `GroupThemingService::resolveTokenSetForRequest()` (precedence: admin preview → group
mapping → instance default `token_set`, per the `per-group-theming` spec) instead of reading
the `token_set` app value directly; with an empty group mapping and no active preview the
resolved set MUST be identical to the `token_set` app value, preserving prior behavior exactly.
Resolution affects ONLY which token set (and thus which design-system bundle) is chosen; the
layer order, the custom-overrides layer, and the conditional stylesheets are unchanged and
identical for all users.

#### Scenario: Standard CSS load order for nldesign design system

- GIVEN the nldesign app boots via `Application::boot()`
- AND the token set resolved for the request belongs to the `nldesign` design system
- WHEN `injectThemeCSS()` is called
- THEN the `DesignSystemService` MUST resolve the design system from `design-systems.json`
- AND CSS files MUST be loaded in the order declared in the design system's `stylesheets` array via `\OCP\Util::addStyle()`
- AND the standard nldesign order MUST be:
  1. `systems/nldesign/fonts` (Layer 1 -- @font-face declarations)
  2. `systems/nldesign/defaults` (Layer 2 -- all `--nldesign-*` token defaults)
  3. Token set file loaded separately: `tokens/{resolvedTokenSet}` (Layer 3 -- organization overrides)
  4. `systems/nldesign/utrecht-bridge` (Layer 4 -- `--utrecht-*` to `--nldesign-component-*` mapping)
  5. `systems/nldesign/theme` (Layer 5 -- `--nldesign-*` to Nextcloud element selectors)
  6. `systems/nldesign/overrides` (Layer 6 -- Nextcloud `--color-*` variable mappings)
  7. `systems/nldesign/element-overrides` (Layer 7 -- low-level element styling)

#### Scenario: Stock Nextcloud design system loads no stylesheets

- GIVEN the token set resolved for the request has `design_system: "none"`
- WHEN `injectThemeCSS()` is called
- THEN the design system's `stylesheets` array MUST be empty
- AND no nldesign CSS files MUST be loaded for layers 1-7
- AND Nextcloud's default theming MUST remain untouched

#### Scenario: Token set CSS loaded after design system stylesheets

- GIVEN the design system stylesheets have been loaded
- AND the design system is not `"none"`
- WHEN the token set file is loaded
- THEN `tokens/{resolvedTokenSet}` MUST be loaded after all design system stylesheets
- AND before the custom-overrides layer

#### Scenario: Custom overrides always loaded last

- GIVEN all design system stylesheets and token set CSS are loaded
- WHEN `injectThemeCSS()` continues
- THEN `custom-overrides` MUST be loaded after all design system and token layers
- AND `CustomOverridesService::ensureExists()` MUST be called before loading
- AND custom overrides MUST override all previous layers in the cascade
- AND the custom-overrides layer MUST be the same instance-global file for every user,
  whichever token set was resolved for the request

#### Scenario: Conditional CSS loading

- GIVEN the hide_slogan setting is enabled (value `'1'`)
- WHEN `injectThemeCSS()` is called
- THEN `hide-slogan` CSS MUST be loaded after all core and custom-override layers
- AND if show_menu_labels is also enabled, `show-menu-labels` CSS MUST also be loaded
- AND the conditional stylesheets MUST be instance-global (not per-group)

#### Scenario: Empty mapping preserves legacy resolution byte-for-byte

@e2e exclude regression invariant — PHPUnit asserts resolved id equals the app value
- GIVEN `group_token_sets` is absent or an empty array and no preview is active
- WHEN any request resolves its token set
- THEN the resolved id MUST equal the `token_set` app value (default `nextcloud`)
- AND the set of stylesheets injected MUST be identical to the pre-change behavior
