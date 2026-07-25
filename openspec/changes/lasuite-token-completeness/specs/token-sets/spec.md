# token-sets

**Spec refs:** token-sets, lasuite-parity, css-architecture
**Standards:** NL Design System; Cunningham design tokens (MIT); WCAG 2.1 AA

## MODIFIED Requirements

### Requirement: Token Set Count and Coverage

The app MUST support at minimum the documented set of Dutch government organizations as token
sets, plus the `lasuite` set for European sovereign-workplace (MijnBureau/EDIC) bundles, and MAY
additionally ship the published Cunningham blue base as a `cunningham` set.

#### Scenario: All required token sets present

- GIVEN the nldesign app is installed
- WHEN the `css/tokens/` directory is scanned
- THEN it MUST contain CSS files for at least: rijkshuisstijl, amsterdam, utrecht, rotterdam,
  denhaag, nextcloud, lasuite
- AND the total number MUST be at least 40

#### Scenario: Token set count matches manifest

- GIVEN the `token-sets.json` manifest lists N entries
- WHEN the `css/tokens/` directory is scanned
- THEN each manifest entry MUST have a corresponding CSS file
- AND conversely, each CSS file SHOULD have a corresponding manifest entry (files without
  manifest entries receive auto-generated names)

#### Scenario: Token sets include major Dutch municipalities

- GIVEN the available token sets
- THEN they MUST include: amsterdam, rotterdam, denhaag, utrecht, groningen, nijmegen, leiden,
  tilburg, zwolle, haarlem
- AND they MUST include government organizations: rijkshuisstijl, duo, vng

#### Scenario: La Suite set manifest entry

- GIVEN the `token-sets.json` manifest
- WHEN the `lasuite` entry is read
- THEN it MUST declare `design_system: "lasuite"`
- AND its `theming` object MUST contain `primary_color: "#4844AD"` (the deployed violet brand) and
  `background_color: "#FFFFFF"`
- AND it MUST NOT contain a `logo` key (no La Suite/state logos are bundled — the logo slot
  stays empty for trademark reasons)

#### Scenario: La Suite token set file is a standard Layer-3 set

- GIVEN `css/tokens/lasuite.css`
- WHEN it is loaded after the lasuite design system bundle
- THEN it MUST declare only `--nldesign-*` custom properties on `:root` (REQ-TSET-005 rules
  apply unchanged)
- AND undefined tokens MUST fall through to the lasuite bridge/defaults values

#### Scenario: Cunningham blue-base set manifest entry (optional sibling)

- GIVEN the app ships the optional `cunningham` sibling set
- WHEN the `cunningham` entry in `token-sets.json` is read
- THEN it MUST declare `design_system: "cunningham"`
- AND its `theming` object MUST contain `primary_color: "#0659C5"` (the published Cunningham blue
  base) and `background_color: "#FFFFFF"`
- AND it MUST NOT contain a `logo` key
- AND `css/tokens/cunningham.css` MUST exist as a standard Layer-3 `--nldesign-*` set pinning the blue
  identity, reusing the shared generated `defaults.css` via its design system bundle
- AND shipping or omitting this set MUST NOT change any `lasuite` behaviour
