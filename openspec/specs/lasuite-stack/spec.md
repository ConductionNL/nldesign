# lasuite-stack Specification

## Purpose
TBD - created by archiving change lasuite-design-stack. Update Purpose after archive.
## Requirements
### Requirement: La Suite Design System Bundle

The app MUST ship a `lasuite` design system: an entry in `design-systems.json` whose
`stylesheets` array declares, in exact order, `systems/lasuite/fonts`,
`systems/lasuite/defaults`, `systems/lasuite/brand-override`, `systems/lasuite/bridge`,
`systems/lasuite/element-overrides`, with all five files living under `css/systems/lasuite/`. The
`brand-override` layer MUST load immediately after `defaults` (see the lasuite-parity spec's sourced
violet override requirement). The bundle's goal is a **pixel-adjacent match to La Suite chrome** — the
type ramp, palette, radii, and surface tones of La Suite numérique apps (Docs/Meet/Chat, Cunningham
design system) — so a Nextcloud core inside a MijnBureau/EDIC sovereign-workplace bundle shows no
visual seam against the surrounding La Suite apps.

#### Scenario: Bundle resolves and loads in declared order

- GIVEN the active token set has `design_system: "lasuite"`
- WHEN `Application::injectThemeCSS()` runs
- THEN `DesignSystemService` MUST resolve the `lasuite` entry from `design-systems.json`
- AND the five stylesheets MUST be added via `\OCP\Util::addStyle()` in the declared order
  (fonts → defaults → brand-override → bridge → element-overrides)
- AND `tokens/lasuite` MUST load after them, followed by `custom-overrides` (standard layering)

#### Scenario: Deactivating lasuite leaves no residue

- GIVEN the admin switches from the `lasuite` set to any other set
- WHEN the next page renders
- THEN no `systems/lasuite/*` stylesheet MUST be loaded
- AND no `--lasuite-*` or `--lasuite--*` custom property MUST be defined

### Requirement: La Suite Fonts Layer With Open Fallback

The fonts layer MUST self-host **Inter** (SIL Open Font License 1.1) as the fallback typeface —
the first open font in La Suite's own configured stack (`Marianne, Inter, Roboto Flex Variable,
sans-serif`) — with @font-face rules for weights 400/500/600/700 (plus 400/700 italic) using
woff2 sources and `font-display: swap`. The layer MUST define
`--lasuite-font-family: Marianne, Inter, sans-serif`. The app MAY bundle **Marianne** (the
French-state typeface) self-hosted from `@gouvfr/dsfr@1.15.1` under Etalab Open Licence 2.0, but
its activation MUST be gated per the `marianne-font` capability: the real self-hosted
`@font-face Marianne` declarations live in a separate `css/systems/lasuite/marianne.css`
stylesheet that is emitted ONLY when the active design system is `lasuite` AND the admin
acknowledgement flag `marianne_enabled` is `'1'`. When the gate is off, no `url()` source for
Marianne exists at runtime, so Inter renders. Inter also renders as the fallback for any glyph
Marianne does not cover. The OFL license text MUST ship alongside the Inter files, and the
Etalab licence + Marianne restriction MUST ship alongside the Marianne files (see
`marianne-font`). This requirement no longer forbids bundling Marianne; silent, ungated
activation is what is forbidden.

#### Scenario: Inter served by default, Marianne gated

- GIVEN the lasuite fonts layer is loaded AND the `marianne_enabled` gate is off (the default)
- WHEN the browser resolves `--lasuite-font-family`
- THEN text MUST render in the bundled Inter
- AND no HTTP request for any Marianne resource MUST occur (the gated `marianne.css` is not
  emitted)

#### Scenario: Marianne renders when the gate is on

- GIVEN the `lasuite` system is active AND an admin has enabled `marianne_enabled`
- WHEN the font stack resolves
- THEN Marianne MUST be used (first family in the stack, now self-hosted from the app)
- AND the `Marianne-*.woff2` files MUST load from the app's own directory with an app-relative
  URL (no external host)
- AND Inter MUST remain the fallback for any glyph Marianne does not cover

#### Scenario: License artifacts present

- GIVEN the shipped app package
- WHEN `css/systems/lasuite/fonts/` is inspected
- THEN it MUST contain the Inter woff2 files and an `OFL.txt` license text
- AND the bundled Marianne woff2 files under `css/systems/lasuite/fonts/marianne/` MUST travel
  with their Etalab Open Licence 2.0 notice and the Marianne restriction (see `marianne-font`)

### Requirement: Cunningham Token Defaults Layer

The defaults layer MUST be **generated**, not hand-transcribed. It MUST be produced by
`scripts/generate-lasuite-tokens.mjs` reading `@openfun/cunningham-tokens` (MIT, a `devDependency`)
and MUST define **all 1167** Cunningham tokens as `--lasuite--*` custom properties on `:root`, using
the reversible `--c--` ⇄ `--lasuite--` prefix-swap mapping (double-dash separators preserved). The
generated base MUST be the published Cunningham **blue** base (`--lasuite--globals--colors--brand-600:
#0659C5`); the deployed **violet** values live in the separate `brand-override` layer, never in this
file. The file MUST carry a provenance header attributing Cunningham, its MIT licence, and the source
package version/token-count/generation-date, and MUST include a closed compatibility-alias block for
the short `--lasuite-*` names the bridge and element-overrides layers consume. The generated output
MUST be committed and guarded against drift (see the lasuite-parity spec). The full requirements for
generation, naming, aliases, and drift live in the lasuite-parity spec.

#### Scenario: Brand base matches the published Cunningham blue base

- GIVEN the generated defaults layer is loaded on its own (no brand override)
- WHEN `--lasuite--globals--colors--brand-600` is resolved
- THEN it MUST equal `#0659C5` (published Cunningham blue base)
- AND the deployed violet `#4844AD` MUST come only from the `brand-override` layer, not this file

#### Scenario: Attribution and generation present

- GIVEN `css/systems/lasuite/defaults.css`
- WHEN its header is read
- THEN it MUST name Cunningham, the MIT licence, the source package (`@openfun/cunningham-tokens`), its
  version, the token count (1167), and the `--c--`→`--lasuite--` mapping rule
- AND the file MUST be re-derivable by re-running `scripts/generate-lasuite-tokens.mjs`

### Requirement: La Suite Bridge Layer

The bridge layer MUST map `--lasuite-*` tokens onto the `--nldesign-*` namespace (primary
family, status colors with `-rgb` variants, text/muted-text, border, focus, font-family,
border-radius, and the `--nldesign-component-*` tokens the shared theme machinery consumes) and
onto Nextcloud `--color-*` variables, honouring the css-architecture invariants: `!important`
only where Nextcloud's own equal-specificity assignments must be beaten (ADR-CSS-002);
`--color-main-background`, `--color-main-background-rgb`, `--color-main-background-translucent`,
`--color-background-plain`, `--background-invert-if-dark`, and `--background-invert-if-bright`
MUST NOT be overridden (REQ-CSS-007 dark-mode compatibility); no circular `var()` references
(REQ-CSS-005).

#### Scenario: Primary maps to La Suite brand

- GIVEN the lasuite system is active
- WHEN `--color-primary` is resolved on a rendered page
- THEN it MUST resolve to `#4844AD` via `--nldesign-color-primary` ← `--lasuite-color-brand-650`
- AND `--color-primary-text` MUST resolve to a value with ≥ 4.5:1 contrast against it

#### Scenario: Dark-compatibility variables untouched

- GIVEN the bridge layer is loaded
- WHEN a user enables Nextcloud's dark theme
- THEN `--color-main-background` and both `--background-invert-if-*` variables MUST carry
  Nextcloud's own dark values (the bridge declares none of them)
- AND no unreadable surface MUST result

### Requirement: La Suite Element Overrides Layer

The element-overrides layer MUST adjust Nextcloud chrome to the La Suite look: a light header
surface with hairline border and brand-650 accents, flat navigation surfaces, 4px control radii
on buttons/inputs, and font application via body inheritance (ADR-CSS-001 — no
universal-selector `!important` font forcing). The layer MUST keep WCAG 2.1 AA contrast on all
adjusted element pairs.

#### Scenario: Controls carry La Suite radii

- GIVEN the lasuite system is active
- WHEN a primary button renders
- THEN its background MUST be the brand color, its text white, and its border-radius `4px`
- AND its hover state MUST use a darker brand-scale step

#### Scenario: Icon fonts survive

- GIVEN the element-overrides layer applies the Inter-based stack
- WHEN a Material Design Icons glyph or code-editor monospace block renders
- THEN its own font-family MUST be preserved (no universal `!important` font rule exists)

### Requirement: La Suite Asset License Compliance

Every asset bundled for the lasuite stack MUST be under a redistributable license and MUST carry
that license: Cunningham token values (MIT), Inter (SIL OFL 1.1), and Marianne
(Etalab Open Licence 2.0, from `@gouvfr/dsfr@1.15.1`). Marianne MAY be bundled ONLY when it
ships together with (a) its `etalab-2.0` licence text and the verbatim French-State restriction,
(b) the operator user agreement, and (c) the default-off admin acknowledgement gate defined by
the `marianne-font` capability — so it is never silently activated on an instance whose operator
has not affirmed eligibility. The app MUST NOT bundle La Suite or French-state **logos** or any
other French-government-restricted asset beyond the gated Marianne fonts; the lasuite token
set's logo slot MUST remain empty.

#### Scenario: Compliance is test-enforced

- GIVEN the test suite runs
- WHEN the license-compliance test executes
- THEN it MUST assert `OFL.txt` exists under `css/systems/lasuite/fonts/`
- AND it MUST assert every bundled `css/systems/lasuite/fonts/marianne/*.woff2` maps to
  `Etalab-2.0` in `.license-overrides.json` and that `MARIANNE-LICENCE.md` + `AGREEMENT-MARIANNE.md`
  exist
- AND it MUST assert the gated `css/systems/lasuite/marianne.css` uses only app-relative `url()`
  (no external Marianne source)
- AND it MUST assert the `lasuite` entry in `token-sets.json` has no `logo` key

### Requirement: Visual Parity Verification

The change MUST be verified by visual comparison: a side-by-side capture of a real La Suite app
page (La Suite Docs) and the lasuite-themed Nextcloud on the dev instance, checked for parity of
typeface rendering, primary interactive color, control radii, header treatment, and greyscale
surface tones. The comparison artifact MUST be produced as part of verification, and the
acceptance bar is pixel-adjacent (same visual family at a glance), not pixel-identical.

#### Scenario: Side-by-side capture passes the parity checklist

- GIVEN the lasuite token set is active on the 8080 dev instance
- WHEN Playwright captures the themed Files view and login page next to a La Suite Docs page
- THEN the composite MUST show: Inter-rendered type, `#4844AD` primary interactive elements,
  4px control radii, a white header with hairline border, and Cunningham-greyscale surfaces
- AND any checklist miss MUST be fixed before the change is archived

### Requirement: La Suite Brand Override Layer

The app MUST ship `css/systems/lasuite/brand-override.css`: a hand-authored, provenance-commented layer
that reproduces the *deployed* La Suite violet theme, which no published Cunningham package contains.
It MUST redeclare on `:root` the brand and logo tokens the live override changes — `brand-600
#534fc2`, `brand-650 #4844ad` (also the logo colour), the dependent brand scale, and the `logo-*`
tokens (plus the short aliases the bridge reads) — and MUST load immediately after `defaults` in the
`lasuite` bundle so the cascade resolves to violet. It MUST NOT be generated by
`scripts/generate-lasuite-tokens.mjs` and MUST NOT be included in the `cunningham` (blue-base) bundle.

#### Scenario: Violet override wins the cascade for lasuite

- GIVEN the `lasuite` bundle is active
- WHEN `--color-primary` and `--lasuite--globals--colors--brand-600` are resolved on a page
- THEN `--color-primary` MUST resolve to `#4844AD` and `--lasuite--globals--colors--brand-600` to
  `#534fc2` (the override beating the blue base)

#### Scenario: Provenance recorded

- GIVEN `css/systems/lasuite/brand-override.css`
- WHEN its header comment is read
- THEN it MUST state the values were observed in the `docs.numerique.gouv.fr` La Suite Docs bundle,
  `:root` block 5, with an observation date, and are absent from any published Cunningham package

