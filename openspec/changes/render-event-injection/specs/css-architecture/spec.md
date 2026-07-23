# CSS Architecture — Render-Event Injection Delta

**Spec refs**: `css-architecture` (REQ-CSS-001, REQ-CSS-009), `per-app-theming` (companion delta
in this change); core precedent: Nextcloud `ThemeInjectionService` injecting on
`BeforeTemplateRenderedEvent` + `BeforeLoginTemplateRenderedEvent`
**Standards**: Nextcloud app framework events (`OCP\AppFramework\Http\Events\*`),
`OCP\EventDispatcher\IEventListener`, CSS Cascade (load order unchanged)

## ADDED Requirements

### Requirement: Render-Context Discrimination

Style injection MUST be per-render-context. The listener MUST derive a context from the event:
`BeforeLoginTemplateRenderedEvent` ⇒ `login`; `BeforeTemplateRenderedEvent` ⇒ the response's
`renderAs` value mapped to `user`, `guest`, `public`, or `error`; any other or future `renderAs`
value MUST be treated as themed (fail open). The appconfig key `themed_contexts` (JSON array of
the five context names) selects which contexts receive nldesign CSS. An absent, empty, or
unparseable value MUST theme ALL contexts — the default behavior is byte-identical to the
previous boot-time injection on every surface. This change ships no admin UI for the key
(occ-only); ambiguity always resolves to themed because theming is presentation, not security.

#### Scenario: Default themes every context
- GIVEN the `themed_contexts` appconfig key is absent
- WHEN a login page, a user page, a guest page, a public share page, and an app-framework error
  page are each rendered
- THEN every one of them MUST receive the full nldesign stylesheet set exactly as before this
  change

#### Scenario: A context can be deliberately unthemed
- GIVEN `themed_contexts` is `["user","login","guest","error"]`
- WHEN a public share page (`renderAs: public`) is rendered
- THEN no nldesign stylesheet MUST be injected on that page
- AND a user page rendered in the same configuration MUST remain fully themed

#### Scenario: Invalid configuration fails open to themed
@e2e exclude config-validation branch — PHPUnit on CssInjectionService
- GIVEN `themed_contexts` contains unparseable JSON or a non-array value
- WHEN any template renders
- THEN all contexts MUST be treated as themed
- AND no error MUST be raised

#### Scenario: Unknown renderAs values stay themed
@e2e exclude forward-compatibility branch — PHPUnit on the listener mapping
- GIVEN a `BeforeTemplateRenderedEvent` whose response `renderAs` is `blank` or a value unknown
  to the listener
- WHEN the listener handles the event
- THEN injection MUST proceed as themed (fail open)
- AND the unknown value MUST NOT cause the configured context list to strip theming

## MODIFIED Requirements

### Requirement: Design System Driven Stylesheet Loading

The app MUST resolve which design system a token set belongs to and load the corresponding
stylesheet bundle in declared order. Injection MUST be event-driven, not boot-driven: a
`ThemeInjectionListener` (`OCP\EventDispatcher\IEventListener`), registered in
`Application::register()` for both `OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent`
and `OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent`, MUST invoke
`CssInjectionService::inject($context)`, which performs the resolution and `\OCP\Util::addStyle()`
calls previously made from `Application::boot()`. `Application::boot()` MUST NOT inject any
style. Requests that render no template (WebDAV, OCS/API, cron) MUST NOT execute any injection
logic. A listener failure MUST fail open to a no-op (never break page rendering). The listener
MUST NOT double-inject: repeated dispatch for one request MUST yield each stylesheet at most once
(guaranteed by `Util::addStyle` idempotency and asserted by test).

#### Scenario: Standard CSS load order for nldesign design system
- GIVEN a page render dispatches `BeforeTemplateRenderedEvent` (or
  `BeforeLoginTemplateRenderedEvent`)
- AND the active token set belongs to the `nldesign` design system
- WHEN `CssInjectionService::inject()` runs for a themed context
- THEN the `DesignSystemService` MUST resolve the design system from `design-systems.json`
- AND CSS files MUST be loaded in the order declared in the design system's `stylesheets` array
  via `\OCP\Util::addStyle()`
- AND the standard nldesign order MUST be:
  1. `systems/nldesign/fonts` (Layer 1 -- @font-face declarations)
  2. `systems/nldesign/defaults` (Layer 2 -- all `--nldesign-*` token defaults)
  3. Token set file loaded separately: `tokens/{activeTokenSet}` (Layer 3 -- organization overrides)
  4. `systems/nldesign/utrecht-bridge` (Layer 4 -- `--utrecht-*` to `--nldesign-component-*` mapping)
  5. `systems/nldesign/theme` (Layer 5 -- `--nldesign-*` to Nextcloud element selectors)
  6. `systems/nldesign/overrides` (Layer 6 -- Nextcloud `--color-*` variable mappings)
  7. `systems/nldesign/element-overrides` (Layer 7 -- low-level element styling)

#### Scenario: Stock Nextcloud design system loads no stylesheets
- GIVEN the active token set has `design_system: "none"`
- WHEN `CssInjectionService::inject()` is called
- THEN the design system's `stylesheets` array MUST be empty
- AND no nldesign CSS files MUST be loaded for layers 1-7
- AND Nextcloud's default theming MUST remain untouched

#### Scenario: Token set CSS loaded after design system stylesheets
- GIVEN the design system stylesheets have been loaded
- AND the design system is not `"none"`
- WHEN the token set file is loaded
- THEN `tokens/{activeTokenSet}` MUST be loaded after all design system stylesheets
- AND before the custom-overrides layer

#### Scenario: Custom overrides always loaded last
- GIVEN all design system stylesheets and token set CSS are loaded
- WHEN `CssInjectionService::inject()` continues
- THEN `custom-overrides` MUST be loaded after all design system and token layers
- AND `CustomOverridesService::ensureExists()` MUST be called before loading
- AND custom overrides MUST override all previous layers in the cascade

#### Scenario: Conditional CSS loading
- GIVEN the hide_slogan setting is enabled (value `'1'`)
- WHEN `CssInjectionService::inject()` is called for a themed context
- THEN `hide-slogan` CSS MUST be loaded after all core and custom-override layers
- AND if show_menu_labels is also enabled, `show-menu-labels` CSS MUST also be loaded

#### Scenario: Login page receives the identical stylesheet set
- GIVEN an active token set with a non-`none` design system
- WHEN `BeforeLoginTemplateRenderedEvent` is dispatched for the login page
- THEN the listener MUST inject the same stylesheets in the same order as for a user page render
- AND the rendered login page MUST contain the same `apps/nldesign/css` links as before this
  change (regression parity)

#### Scenario: Non-template requests execute no injection logic
@e2e exclude negative performance/behavior branch — PHPUnit asserts listener not constructed / not invoked outside the two events
- GIVEN a WebDAV, OCS, or cron request that renders no template
- WHEN the request is processed
- THEN neither `BeforeTemplateRenderedEvent` nor `BeforeLoginTemplateRenderedEvent` fires for it
- AND no nldesign configuration read, service resolution, or `ensureExists()` filesystem check
  MUST occur for injection purposes

#### Scenario: Listener failure never breaks rendering
@e2e exclude error-path branch — PHPUnit with throwing service mocks
- GIVEN `CssInjectionService` (or any dependency) throws while handling a render event
- WHEN the listener handles the event
- THEN the exception MUST be caught inside the listener
- AND the page MUST render (possibly unthemed) without an error surfacing to the user

### Requirement: Custom Overrides Layer (Layer 8)

An 8th layer MUST load admin-defined CSS overrides that always win over all design system and
token layers.

#### Scenario: Custom overrides file loaded
- GIVEN `CssInjectionService::inject()` runs for a themed render context
- WHEN all design system and token set CSS has been loaded
- THEN `CustomOverridesService::ensureExists()` MUST be called to create the file if missing
- AND `custom-overrides` CSS MUST be loaded via `\OCP\Util::addStyle()`
- AND this MUST happen after Layer 7 and before conditional stylesheets

#### Scenario: Custom overrides cascade priority
- GIVEN a custom override defines `--color-primary: #ff0000 !important`
- AND the token set defines `--nldesign-color-primary: #004699`
- WHEN the CSS cascade resolves
- THEN the custom override value MUST win because it loads later in the cascade

#### Scenario: Custom overrides file initially empty
- GIVEN no admin customizations have been made
- WHEN `CustomOverridesService::ensureExists()` creates the file
- THEN the file MUST contain valid CSS (possibly just a comment)
- AND it MUST not affect any styling
