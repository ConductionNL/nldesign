# Spec delta: Claim Accuracy (fix-readiness-claims)

New capability: the app's public claim surfaces — manifest metadata, README, government feature checklist, compliance and audit docs — MUST agree with the shipped code. Each claim is pinned to a filesystem source of truth so drift is caught mechanically rather than shipped as misleading metadata to procuring organizations.

## ADDED Requirements

### Requirement: Manifest Licence Matches the Bundled Licence
`appinfo/info.xml` `<licence>` MUST declare the same licence family as the root `LICENSE` file and the SPDX headers of the PHP sources. The bundled `LICENSE` is the European Union Public Licence v1.2 and every `lib/**/*.php` file carries `SPDX-License-Identifier: EUPL-1.2`, so `<licence>` MUST be `eupl` and MUST NOT be `agpl`. The `<description>` MUST also state the EUPL-1.2 licence in prose, consistent with the rest of the Conduction fleet.

#### Scenario: Declared licence equals the bundled licence
@e2e exclude static metadata invariant — PHPUnit/grep asserts info.xml and LICENSE agree, not a UI flow
- GIVEN the shipped app package
- WHEN `appinfo/info.xml` `<licence>` is read
- THEN it MUST equal `eupl`
- AND the first line of `LICENSE` MUST contain "EUROPEAN UNION PUBLIC LICENCE"
- AND `<description>` MUST mention the EUPL-1.2 licence

#### Scenario: SPDX headers agree with the manifest
@e2e exclude static source invariant — PHPUnit scans lib/ headers
- GIVEN every PHP file under `lib/`
- WHEN its `SPDX-License-Identifier` tag is read
- THEN it MUST be `EUPL-1.2`
- AND no `lib/` PHP file MUST declare AGPL

### Requirement: Government Checklist States the Real Licence and Host
`docs/GOVERNMENT-FEATURES.md` — used by procuring organizations as a Programma van Eisen checklist — MUST state the real licence (EUPL-1.2) and the canonical source host (`codeberg.org/Conduction/nldesign`). It MUST NOT state AGPL, and open-source / source-access rows MUST NOT reference GitHub as the canonical repository.

#### Scenario: Checklist licence and host are correct
@e2e exclude documentation invariant — checked against the shipped doc, not a UI flow
- GIVEN `docs/GOVERNMENT-FEATURES.md`
- WHEN the "Licentie" line and the open-source technical row are read
- THEN the licence MUST read EUPL-1.2 (never AGPL)
- AND the source-access reference MUST point at the Codeberg repository (never GitHub)

### Requirement: Font Delivery Documented as Bundled and Self-Hosted
Nextcloud enforces a strict Content-Security-Policy and government instances are frequently air-gapped, so the app MUST bundle Fira Sans as self-hosted `.woff2/.woff` files under `css/fonts/` and load them from `css/fonts.css` with app-relative `url()` only. Documentation MUST describe this delivery and MUST NOT instruct or claim that fonts load from an external CDN when the code contains no external font URL.

#### Scenario: Stylesheet uses only bundled fonts
@e2e exclude static asset invariant — PHPUnit inspects css/fonts.css and css/fonts/
- GIVEN `css/fonts.css`
- WHEN its `url()` references are inspected
- THEN none MUST use an `http://` or `https://` scheme
- AND every referenced `css/fonts/*.woff2` file MUST exist on disk

#### Scenario: Documentation matches the bundled delivery
@e2e exclude documentation invariant — checked against README/compliance docs
- GIVEN the font sections of `README.md` and `docs/reference/compliance.md`
- WHEN they describe how the font is delivered
- THEN they MUST describe self-hosted, app-bundled woff2 delivery
- AND they MUST NOT claim CDN/jsdelivr loading, nor that the font files are "not loaded"

### Requirement: Token-Set Count Claims Match the Inventory
Every human-readable statement of how many token sets ship — `appinfo/info.xml` `<description>`, `README.md`, `project.md`, and the documentation — MUST agree with the number of entries in `token-sets.json` (equivalently, the `css/tokens/*.css` inventory). A statement enumerating a subset MUST either enumerate accurately or state the true total.

#### Scenario: README count equals the inventory
@e2e exclude static inventory invariant — PHPUnit compares README count to token-sets.json length
- GIVEN the token-set count stated in `README.md`
- WHEN compared with the length of `token-sets.json`
- THEN the two MUST be equal
- AND `project.md` MUST NOT state a different total

### Requirement: Token Audit Scope Stated Honestly
`docs/reference/token-audit.md` MUST state which token sets have actually been reviewed and MUST NOT assert a blanket production verdict ("100/100", "APPROVED FOR PRODUCTION", "all token sets reviewed") that covers sets which were never audited. Sets that are community-derived and not individually reviewed MUST be labelled as unaudited.

#### Scenario: Audit doc scopes its verdict to reviewed sets
@e2e exclude documentation invariant — checked against the shipped doc
- GIVEN `docs/reference/token-audit.md`
- WHEN the audited scope is read
- THEN it MUST name the manually-reviewed sets (currently the original five)
- AND it MUST mark the remaining shipped sets as not individually audited
- AND no "APPROVED FOR PRODUCTION" verdict MUST be stated as covering the unaudited sets
