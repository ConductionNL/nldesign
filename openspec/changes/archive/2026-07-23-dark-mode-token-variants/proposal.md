---
kind: code
---

## Why

NL Design System ships **no dark mode**. The NLDS core team has it "on the radar" only, and no
municipality publishes an official dark theme in `nl-design-system/themes` — so every one of
nldesign's 42 token sets today is light-only. Meanwhile the Nextcloud platform natively *demands*
dark mode: the theming app hardcodes a `dark` and `dark-highcontrast` theme, the "System default"
(auto) theme follows `prefers-color-scheme`, and the single-purpose **Breeze Dark** app holds a
10/10 store rating in the Customization category — the clearest demand proof available. The result
on a themed gemeente instance today: a user who picks the Nextcloud dark theme gets NC's dark
`--color-main-background`/`--color-main-text` while nldesign keeps forcing light-derived
`--nldesign-*` values (light headers, light primary-light surfaces, a light-only logo) on top —
a broken half-dark hybrid. Upstream evidence for the logo half of the problem: nextcloud/server#47357
(open) asks for a dark-mode logo variant because NC core has only ONE logo slot; nl-design-system/themes#1237
tracks logo variants on the NLDS side. Being first to ship contrast-verified dark variants for
Dutch government palettes is a genuine first-mover position (research: 04-nlds-ecosystem, opportunity
#3 "NLDS dark mode first-mover").

nldesign already owns every ingredient: `ContrastService` computes WCAG 2.1 ratios over defined
token pairs, `CssParserService` parses `:root` blocks out of token set files,
`TokenSetService`/`DesignSystemService` know every set and its design system, and
`Application::injectThemeCSS()` is the single injection point. What is missing is (a) a derivation
algorithm that turns a light palette into a dark one, (b) a generation step that materialises it as
static CSS (never per-request), (c) correct scoping so the dark palette activates exactly when
Nextcloud itself is dark, and (d) a dark logo slot.

**Verified against the NC 34 server checkout** (`apps/theming` + `core/templates`, see design.md
for file/line evidence): Nextcloud marks dark on `<body>` two different ways. An *explicit* user
choice of the dark theme stamps a boolean `data-theme-dark` attribute AND lists the id in
`data-themes="dark,..."` (`core/templates/layout.user.php:55-56`, `UserTheming.vue:263/269`; core's
own icon CSS keys on `[data-themes*=dark]`, `core/src/icons.cjs:374`). The *auto* ("System
default") theme instead leaves body at `data-theme-default` / `data-themes=default` (anonymous
pages: empty) and delivers dark purely via a `<link media="(prefers-color-scheme: dark)">`
stylesheet (`ThemeInjectionService::injectHeaders()` + `DarkTheme::getMediaQuery()`), with an
explicit theme choice winning because its variables are re-declared under the higher-specificity
`[data-theme-<id>]` scope (`ThemingController.php:415`). Our dark CSS must therefore ship BOTH
scopes, and the media-query scope must exclude bodies carrying an explicit light choice.

The user's theme choice is sacred: this change never touches the `enforce_theme` system config and
never overrides an explicit light/dark selection — it only makes nldesign *follow* whatever
Nextcloud already decided (upstream complaints server#38966/#41048/#46217 about enforce_theme
killing OS detection are exactly the anti-pattern we refuse to import).

## What Changes

- **New canonical spec `dark-mode`** — auto-derived dark palette per token set:
  - `DarkPaletteService`: algorithmic derivation from the light `--nldesign-*` declarations —
    preserve hue, invert the lightness scale, then verify every text pair with the existing
    `ContrastService` against WCAG AA 4.5:1 and nudge lightness until each pair passes (see
    design.md for the full algorithm).
  - Hand-authored overrides win: a token set file MAY carry its own
    `@media (prefers-color-scheme: dark) { :root { ... } }` block; any declaration found there
    replaces the derived value for that token.
  - **Generation, not runtime**: an `occ nldesign:generate-dark-variants` command plus an
    `IRepairStep` write static `css/tokens/dark/<set>.css` files at build/install/upgrade time.
    Shipped sets get their generated files committed (CI regeneration alongside the existing
    token-sync workflow); no per-request derivation ever happens.
  - Generated files are dual-scoped exactly as verified above:
    `@media (prefers-color-scheme: dark)` restricted to bodies WITHOUT an explicit theme choice,
    AND `body[data-theme-dark]` / `body[data-themes*=dark]` for explicit dark.
  - `Application::injectThemeCSS()` loads `tokens/dark/<set>` (when the file exists and the
    feature is enabled) directly after `tokens/<set>` and before custom overrides.
  - Admin toggle `dark_variants` (IConfig, default enabled) to disable dark variants per
    instance; exposed in the admin panel and via an admin-only settings endpoint.
  - The app MUST NOT set or modify `enforce_theme`.
- **MODIFIED `token-sets` spec** — the `theming` manifest object gains an optional `logo_dark`
  key (relative path under `img/logos/`), and the manifest schema documents the optional
  hand-authored dark block convention in token set CSS files.
- **MODIFIED `theming-sync` spec** — `logo_dark` validated like `logo` (same path rules);
  the theming-sync dialog shows the dark logo swatch when present. Because NC core has no dark
  logo slot (that is literally server#47357), the dark logo is DELIVERED by nldesign itself: the
  generated dark variant sets `--nldesign-logo-url` to the dark logo under dark scopes. Syncing
  `logo_dark` into NC core theming is explicitly out of scope until upstream grows a slot.
- No Vue, no DB tables, no event listeners: pure PHP services + static CSS + IConfig, consistent
  with the app architecture.

## Impact

- `lib/Service/DarkPaletteService.php` (new) — derivation + WCAG verification loop
- `lib/Command/GenerateDarkVariants.php` (new) — occ command
- `lib/Migration/GenerateDarkVariantsRepairStep.php` (new) — install/upgrade generation
- `lib/AppInfo/Application.php` — load `tokens/dark/<set>` conditionally
- `lib/Controller/SettingsController.php`, `appinfo/routes.php` — dark-variants toggle endpoint
- `lib/Service/ThemingService.php` — validate `logo_dark` path
- `templates/settings/admin.php`, `js/admin.js` — admin toggle + dark logo in theming-sync dialog
- `token-sets.json` — optional `logo_dark` in `theming` for sets that have a dark logo asset
- `css/tokens/dark/*.css` (generated, committed for shipped sets)
- `appinfo/info.xml` — register command + repair step, version bump (cache-bust)
- `openspec/specs/dark-mode/` (new), `openspec/specs/token-sets/`, `openspec/specs/theming-sync/`
- Tests: `tests/unit/Service/DarkPaletteServiceTest.php` with known color fixtures, command test,
  settings toggle test; vitest for the dialog addition
