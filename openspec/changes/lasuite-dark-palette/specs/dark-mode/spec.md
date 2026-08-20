# dark-mode — delta

Requirements added by the `lasuite-dark-palette` change.

## ADDED Requirements

### Requirement: A design system whose overrides consume its own token ramp SHALL ship a dark counterpart for that ramp

A design system MUST define dark values for every `--{system}-*` token its own
`element-overrides.css` consumes. When a design system's `element-overrides.css`
reads `--{system}-*` tokens directly, the system SHALL define dark values for
every such token it consumes.
Redefining only the shared `--nldesign-*` layer is NOT sufficient: an override
that reads the system ramp with `!important` discards the `--nldesign-*` value
entirely, so the dark variant computes and is then thrown away.

Dark values SHALL be sourced from the upstream design system's own dark palette
where one exists, rather than derived by inverting the light ramp.

#### Scenario: A system-ramp token consumed by an override has a dark value

- **GIVEN** `element-overrides.css` reads `--lasuite-color-gray-000`
- **WHEN** the dark variant for that system is generated
- **THEN** `--lasuite-color-gray-000` has a dark value in that variant
- **AND** the value comes from the upstream dark palette, not from inverting the light one

#### Scenario: Redefining only the shared layer fails the check

- **GIVEN** a dark variant that redefines `--nldesign-color-header-background`
- **AND** an override that sets the header from `--lasuite-color-gray-000` with `!important`
- **WHEN** the dark-ramp check runs
- **THEN** it fails, because the token the override actually consumes has no dark value

### Requirement: Translucent override values SHALL be legible on both grounds

A value expressed as a translucent overlay SHALL be defined per ground, or
expressed so it resolves correctly on both. A dark wash intended for a light
surface SHALL NOT be reused unchanged on a dark surface.

#### Scenario: The active-row wash remains visible in dark mode

- **GIVEN** the active navigation row is marked by a translucent wash
- **WHEN** the interface renders in dark mode
- **THEN** the wash is distinguishable from the surrounding surface
- **AND** the selected row is identifiable without relying on colour alone

### Requirement: Dark-mode verification SHALL assert rendered values, not only stylesheet injection

The dark-mode e2e coverage SHALL load real pages in dark mode and assert the
COMPUTED values of the shell — header, content canvas, content card, active row
and search field. Asserting stylesheet order, scoping and toggle state alone is
insufficient: a stylesheet can be correctly ordered, correctly scoped, and have
no effect on the system under test.

#### Scenario: The shell is dark in dark mode

- **WHEN** a page renders with the dark variant active
- **THEN** the header background is a dark value
- **AND** the content canvas and card are dark values
- **AND** none of them resolve to the light ramp

#### Scenario: Injection-order coverage alone does not satisfy this requirement

- **GIVEN** a suite that asserts only injection order, scoping and toggle state
- **WHEN** a design system ships no dark ramp for its own tokens
- **THEN** that suite passes while the interface renders light
- **AND** this requirement is therefore NOT met by such a suite
