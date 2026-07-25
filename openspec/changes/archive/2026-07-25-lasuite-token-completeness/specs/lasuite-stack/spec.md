# lasuite-stack

**Spec refs:** lasuite-stack, lasuite-parity, css-architecture
**Standards:** Cunningham design tokens (`@openfun/cunningham-tokens`, MIT); SIL OFL 1.1 (Inter); WCAG 2.1 AA

## MODIFIED Requirements

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

### Requirement: La Suite Asset License Compliance

Every asset bundled for the lasuite stack MUST be under an MIT- or EUPL-compatible license:
Cunningham token values (MIT, sourced from `@openfun/cunningham-tokens` as a `devDependency` — only
the generated CSS values ship, not the package), Inter (SIL OFL 1.1). The app MUST NOT bundle Marianne
font files, La Suite or French-state logos, or any other French-government-restricted asset; the
lasuite token set's logo slot MUST remain empty. The sourced `brand-override.css` reproduces observed
CSS variable *values* (colours), which are not themselves a protected asset, and MUST carry a
provenance comment.

#### Scenario: Compliance is test-enforced

- GIVEN the test suite runs
- WHEN the license-compliance test executes
- THEN it MUST assert `OFL.txt` exists under `css/systems/lasuite/fonts/`
- AND it MUST assert no file path in the app matches `/marianne/i`
- AND it MUST assert the `lasuite` entry in `token-sets.json` has no `logo` key

#### Scenario: Cunningham is a devDependency, not a shipped package

- GIVEN the app package
- WHEN its runtime dependencies are inspected
- THEN `@openfun/cunningham-tokens` MUST appear only under `devDependencies` in `package.json`
- AND only the generated `--lasuite--*` CSS values MUST ship (no copy of the package in the app)

## ADDED Requirements

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
