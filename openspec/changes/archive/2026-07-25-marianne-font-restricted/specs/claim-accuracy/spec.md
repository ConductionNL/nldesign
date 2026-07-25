**Spec refs**: openspec/specs/claim-accuracy/spec.md,
openspec/specs/marianne-font/spec.md, openspec/specs/lasuite-stack/spec.md

**Standards**: Etalab Open Licence 2.0 (`Etalab-2.0`); honest-claims / no-overclaim principle
already governing this spec

## ADDED Requirements

### Requirement: Marianne Licensing Is Stated Honestly

Every human-readable statement about Marianne MUST describe its real, legally-restricted situation and MUST NOT overclaim or underclaim.
Marianne is a legally restricted French-State asset now bundled behind a gate, so `README.md`,
the relevant `docs/` page (font delivery / compliance), and any in-product notice
MUST state that Marianne is bundled self-hosted under the Etalab Open Licence
2.0 (from `@gouvfr/dsfr`), that it is the official typeface of the French State reserved for
French State administrations, that it is **off by default** and activated only when an admin
acknowledges the organisation is a French State agency, and that Inter is used otherwise. The
documentation MUST NOT claim Marianne is an unconditionally free/open font, and MUST NOT (as the
pre-change text did) assert that "no Marianne file exists anywhere in the app" now that the
gated files ship. Statements about font delivery MUST remain consistent with the
`### Requirement: Font Delivery Documented as Bundled and Self-Hosted` requirement — self-hosted,
app-relative, no CDN.

#### Scenario: Docs describe the gated, restricted Marianne accurately
@e2e exclude documentation invariant — checked against the shipped README/docs, not a UI flow

- GIVEN the Marianne sections of `README.md` and the font-delivery `docs/` page
- WHEN they describe Marianne
- THEN they MUST state it is bundled self-hosted under Etalab Open Licence 2.0, restricted to
  French State agencies, and off by default until an admin acknowledges eligibility
- AND they MUST NOT describe Marianne as an unconditionally free/open font
- AND they MUST NOT claim that no Marianne file ships in the app

#### Scenario: No claim that Marianne loads from an external host
@e2e exclude static asset invariant — PHPUnit inspects the Marianne stylesheet and docs

- GIVEN `css/systems/lasuite/marianne.css` and the documentation describing its delivery
- WHEN the `url()` references and the doc prose are read
- THEN none MUST use an `http://` or `https://` scheme
- AND the documentation MUST NOT claim CDN loading nor that Marianne is fetched from an external
  server
