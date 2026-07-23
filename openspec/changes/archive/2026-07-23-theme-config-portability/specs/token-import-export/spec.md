# Token Import/Export — Scope Cross-Reference Delta

**Spec refs**: `token-import-export` (canonical), `config-portability` (new, this change)
**Standards**: none new — scope clarification only

The `token-import-export` capability covers ONLY the token-editor overrides file
(`custom-overrides.css`). This delta modifies its two top-level requirements to state that scope
explicitly and cross-reference the full configuration bundle (`config-portability`), so the
overrides-only download is never mistaken for whole-config OTAP portability. Behaviour is
unchanged.

## MODIFIED Requirements

### Requirement: Export Current Overrides

The admin settings panel MUST provide a **Download** button that exports the current
`custom-overrides.css` as a file download. This export covers ONLY the token-editor overrides
file — it is NOT a complete configuration export: the active token set, feature toggles, per-app
exclusions, and custom token sets are exported exclusively by the full configuration bundle
defined in the `config-portability` spec (`GET /settings/config/export` /
`occ nldesign:config:export`), and the overrides UI SHOULD point admins needing whole-config
promotion (OTAP) at the bundle.

#### Scenario: Admin downloads overrides

- GIVEN `custom-overrides.css` contains `--color-primary: #c00000` and `--color-error: #b30000`
- WHEN the admin clicks Download
- THEN the browser MUST download a file named `custom-overrides.css`
- AND the file content MUST be valid CSS containing the current overrides
- AND the file MUST be formatted identically to the server-side `custom-overrides.css`

#### Scenario: Download with no custom overrides

@e2e exclude Requires custom-overrides.css to be empty — environment state not guaranteed; file content verification requires intercepting download response.
- GIVEN `custom-overrides.css` is empty (no custom tokens set)
- WHEN the admin clicks Download
- THEN the browser MUST download a file with only the header comment and an empty `:root {}` block
- AND the download MUST NOT be blocked or result in an error

#### Scenario: Download is a GET request to a dedicated endpoint

@e2e exclude API-layer assertion (Content-Type, Content-Disposition headers) — not testable via browser UI DOM; would require network interception.
- GIVEN the admin clicks Download
- WHEN the request is made
- THEN it MUST call `GET /api/overrides/export`
- AND the response Content-Type MUST be `text/css`
- AND the Content-Disposition MUST be `attachment; filename="custom-overrides.css"`

#### Scenario: Overrides export is distinct from the configuration bundle

@e2e exclude Scope/documentation assertion — verified by spec cross-reference and unit tests on the two endpoints' payloads, not via browser DOM.
- GIVEN an admin needs to promote the COMPLETE nldesign configuration to another environment
- WHEN they use the overrides Download button alone
- THEN they obtain only `custom-overrides.css` — the active token set, toggles, exclusions, and
  custom token sets are NOT included
- AND the full-bundle export from the `config-portability` spec MUST be used instead for
  whole-config promotion

### Requirement: Import Token File

The admin settings panel MUST provide an **Upload** button that accepts a CSS file, parses it
for known `--color-*` tokens, and writes the recognized tokens to `custom-overrides.css`,
replacing the current overrides. This import touches ONLY the overrides file: it MUST NOT change
the active token set, feature toggles, per-app exclusions, or custom token sets — importing the
complete configuration is the `config-portability` bundle's job
(`POST /settings/config/import` / `occ nldesign:config:import`), which reuses this capability's
editable-token whitelist semantics for its overrides section.

#### Scenario: Admin uploads a valid overrides file

- GIVEN a CSS file contains `--color-primary: #aa0000` and `--color-error: #990000`
- WHEN the admin uploads the file
- THEN both tokens MUST be written to `custom-overrides.css` (replacing previous overrides)
- AND the token editor forms MUST reflect the imported values
- AND the live preview MUST update to show the imported values

#### Scenario: Import replaces existing overrides

@e2e exclude Requires file upload and filesystem verification of custom-overrides.css content — mutates shared env; file content not verifiable via DOM.
- GIVEN `custom-overrides.css` currently contains `--color-warning: #ff8800`
- AND the uploaded file contains `--color-primary: #aa0000` but NOT `--color-warning`
- WHEN the admin uploads the file
- THEN `custom-overrides.css` MUST contain only `--color-primary: #aa0000`
- AND `--color-warning` MUST be removed (import is a full replace, not a merge)

#### Scenario: Overrides import never touches other configuration

@e2e exclude Backend state assertion across multiple config keys — covered by unit tests; would mutate shared-env config.
- GIVEN the active token set is `amsterdam` with one custom token set installed
- WHEN an overrides CSS file is uploaded via `POST /api/overrides/import`
- THEN after the import the `token_set` app value, both feature toggles, the exclusion list, and
  all custom token sets MUST be unchanged
