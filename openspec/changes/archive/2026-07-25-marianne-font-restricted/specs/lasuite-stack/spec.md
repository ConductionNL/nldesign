**Spec refs**: openspec/specs/lasuite-stack/spec.md,
openspec/specs/marianne-font/spec.md (the new capability this defers Marianne detail to)

**Standards**: Etalab Open Licence 2.0 (`Etalab-2.0`); SIL Open Font License 1.1 (Inter);
`@gouvfr/dsfr@1.15.1`; `suitenumerique/meet#426`

## MODIFIED Requirements

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
