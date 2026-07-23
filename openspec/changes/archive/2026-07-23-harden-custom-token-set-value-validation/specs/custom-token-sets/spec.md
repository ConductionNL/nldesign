# Custom Token Sets — Value-Injection Hardening Delta

**Spec refs**: `custom-token-sets`, ADR-005 (security — input validation at trust boundaries)
**Standards**: CSS Syntax Module Level 3 (declaration termination), OWASP input-validation guidance

## MODIFIED Requirements

### Requirement: CSS Validation Whitelist

The server MUST reject, as a hard upload failure, any accepted declaration (`--nldesign-*` or
`--{slug}-*` name) whose value contains a semicolon (`;`) or a CSS comment marker (`/*` or `*/`),
in addition to the existing rejections (`@import`, `expression(`, `javascript:`, raw `<`, and
disallowed `url()` schemes/hosts). A value containing any of these MUST cause
`CustomTokenSetValidator::isForbiddenValue()` to return `true`, which MUST propagate as a 422
upload failure via `CustomTokenSetController::upload()` for both the CSS and W3C Design Tokens
JSON upload paths.

#### Scenario: Semicolon-smuggled declaration is rejected (CSS upload)

- GIVEN a CSS upload whose `:root` block contains
  `--nldesign-color-primary: red; background: url(https://evil.example/x.png);`
- WHEN the admin uploads the file
- THEN the upload MUST fail with HTTP 422
- AND the response MUST NOT contain a served CSS file with the smuggled `background` declaration

#### Scenario: Comment-marker payload is rejected (CSS upload)

- GIVEN a CSS upload whose `:root` block contains a declaration value containing `/*` or `*/`
- WHEN the admin uploads the file
- THEN the upload MUST fail with HTTP 422

#### Scenario: Semicolon-smuggled value is rejected (W3C Design Tokens JSON upload)

- GIVEN a W3C Design Tokens JSON upload whose mapped `--nldesign-*` value contains a semicolon
  followed by an additional declaration
- WHEN the admin uploads the file
- THEN `CustomTokenSetController::mapFromJson()` MUST reject the upload with HTTP 422 via the same
  `isForbiddenValue()` gate used by the CSS path

#### Scenario: Legitimate values with no injection characters still succeed

- GIVEN a CSS upload with `--nldesign-color-primary: #154273`
- WHEN the admin uploads it
- THEN the upload MUST succeed exactly as before this change (no regression for benign values)
