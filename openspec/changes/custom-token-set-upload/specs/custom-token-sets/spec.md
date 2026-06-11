# Spec delta: Custom Token Sets (custom-token-set-upload)

New capability: admins upload, validate, manage, and activate their own organization token sets ("eigen huisstijl"), in the app's native `--nldesign-*` CSS format or the W3C Design Tokens JSON format. Backs GOVERNMENT-FEATURES F-04.

## ADDED Requirements

### Requirement: Upload Custom Token Set (CSS format)
The admin settings panel MUST provide an upload control that accepts a CSS file containing `--nldesign-*` custom property declarations and stores it as a new token set with id `custom-{slug}`, where the slug is derived from the admin-supplied display name (`[a-z0-9-]`, max 64 chars).

#### Scenario: Admin uploads a valid CSS token set
- GIVEN an admin on the NL Design settings panel
- AND a file `huisstijl.css` containing `:root { --nldesign-color-primary: #007bc7; --nldesign-color-primary-text: #ffffff; }`
- WHEN the admin uploads it with display name "Gemeente Voorbeeld"
- THEN the server MUST write `css/tokens/custom-gemeente-voorbeeld.css` atomically (temp file + rename)
- AND the response MUST report the set id `custom-gemeente-voorbeeld` and counts `{ imported, skipped }`
- AND the token set dropdown MUST list "Gemeente Voorbeeld" after a panel reload without any other configuration

#### Scenario: Upload endpoint is admin-only and CSRF-protected
@e2e exclude auth-posture assertion — PHPUnit/Newman verify middleware rejection, not a UI flow
- GIVEN a non-admin authenticated user
- WHEN they POST a file to `/settings/tokensets/upload`
- THEN Nextcloud's SecurityMiddleware MUST reject the request (no `NoAdminRequired` on the method)
- AND requests without a CSRF token MUST be rejected (no `NoCSRFRequired` on the method)

#### Scenario: Slug collisions are rejected
@e2e exclude validation branch — PHPUnit on the service
- GIVEN an existing custom set `custom-gemeente-voorbeeld`
- WHEN the admin uploads another file with display name "Gemeente Voorbeeld"
- THEN the upload MUST be rejected with HTTP 409 and a localized message offering to delete or rename
- AND the existing file MUST NOT be modified

#### Scenario: Shipped set ids can never be shadowed
@e2e exclude namespace invariant — PHPUnit on the service
- GIVEN the shipped token set `utrecht`
- WHEN any custom set is created
- THEN its id MUST start with `custom-`
- AND no upload MUST ever write to a path other than `css/tokens/custom-*.css`

### Requirement: CSS Validation Whitelist
Uploaded CSS MUST be parsed and re-serialized through a whitelist: exactly one `:root` rule; only `--nldesign-*` and `--{slug}-*` custom properties; values MUST NOT contain `@import`, `expression(`, `javascript:`, `<`, or `url()` with a scheme or host (relative `url()` and `data:image/svg+xml` are permitted). The served file MUST be generated from the parsed declarations, never from the raw upload bytes. Uploads over 512 KB MUST be rejected.

#### Scenario: Disallowed CSS payload is rejected with a structured error
@e2e exclude security validation branch — PHPUnit on the validator with a payload corpus
- GIVEN a CSS file containing `:root { --nldesign-color-primary: #007bc7; } .header { background: url(https://evil.example/x.png); }`
- WHEN the admin uploads it
- THEN the upload MUST be rejected with HTTP 422
- AND the error body MUST identify the offending construct (selector other than `:root`)
- AND no file MUST be written

#### Scenario: External url() in a token value is rejected
@e2e exclude security validation branch — PHPUnit on the validator
- GIVEN a CSS file containing `:root { --nldesign-logo-url: url('https://evil.example/logo.svg'); }`
- WHEN the admin uploads it
- THEN the upload MUST be rejected with HTTP 422 naming the offending property
- AND a file using `url('../../img/logos/custom.svg')` or a `data:image/svg+xml` URI MUST be accepted

#### Scenario: Unknown properties are skipped and counted
@e2e exclude parser behavior — PHPUnit on the validator
- GIVEN a CSS file with three `--nldesign-*` declarations and two `--color-primary`-style Nextcloud variables
- WHEN the admin uploads it
- THEN only the three `--nldesign-*` declarations MUST be written
- AND the response MUST report `imported: 3, skipped: 2`
- AND skipped declarations MUST be listed by name so the admin can move them to `custom-overrides.css` instead

#### Scenario: Oversized upload is rejected
@e2e exclude size guard — PHPUnit on the controller
- GIVEN a CSS file larger than 512 KB
- WHEN the admin uploads it
- THEN the upload MUST be rejected with HTTP 413 before parsing

### Requirement: W3C Design Tokens JSON Import
The upload control MUST also accept a JSON file in the Design Tokens Community Group format (`$value`/`$type`, nested groups). Recognized `color`, `fontFamily`, and `dimension` tokens MUST be mapped to `--nldesign-*` variables via the published mapping table; unmapped tokens MUST be skipped and counted. The mapped result MUST pass through the same whitelist, serialization, and storage pipeline as CSS uploads.

#### Scenario: DTCG color tokens map onto the nldesign vocabulary
@e2e exclude mapping logic — PHPUnit on the mapper with DTCG fixtures
- GIVEN a `huisstijl.tokens.json` containing `{ "color": { "primary": { "$type": "color", "$value": "#154273" }, "on-primary": { "$type": "color", "$value": "#ffffff" } } }`
- WHEN the admin uploads it with display name "Eigen huisstijl"
- THEN `css/tokens/custom-eigen-huisstijl.css` MUST contain `--nldesign-color-primary: #154273` and `--nldesign-color-primary-text: #ffffff`
- AND the response MUST report `imported: 2, skipped: 0`

#### Scenario: Unmapped DTCG tokens degrade to skipped counts
@e2e exclude mapping tolerance — PHPUnit on the mapper
- GIVEN a DTCG file containing a recognized `color.primary` token and an unrecognized `shadow.elevation-1` token
- WHEN the admin uploads it
- THEN the upload MUST succeed with `imported: 1, skipped: 1`
- AND the skipped token path MUST be listed in the response

#### Scenario: Malformed JSON is rejected
@e2e exclude parse guard — PHPUnit on the controller
- GIVEN a `.json` upload that is not valid JSON
- WHEN the admin uploads it
- THEN the upload MUST be rejected with HTTP 422 and a localized parse error
- AND no file MUST be written

### Requirement: WCAG AA Contrast Warnings on Upload
The server MUST compute WCAG 2.1 relative-luminance contrast ratios for the fixed token pairs (`--nldesign-color-primary` vs `--nldesign-color-primary-text` at 4.5:1; `--nldesign-color-primary` vs `--nldesign-color-background` at 3:1) from the uploaded values. Failures MUST be returned as non-blocking warnings, persisted in the custom-set manifest entry, and resurfaced in the token-set apply dialog. Pairs with unresolvable (non-literal) values MUST be reported as `unevaluated`, never as passing.

#### Scenario: Low-contrast upload succeeds with a warning
- GIVEN a CSS upload with `--nldesign-color-primary: #cccccc` and `--nldesign-color-primary-text: #ffffff` (ratio ≈ 1.6:1)
- WHEN the admin uploads it
- THEN the upload MUST succeed
- AND the response MUST contain a warning for the pair with the computed ratio and the 4.5:1 AA threshold
- AND the admin panel MUST display the warning with a localized explanation referencing WCAG 2.1 AA

#### Scenario: Contrast warning resurfaces when applying the set
- GIVEN a stored custom set with a persisted contrast warning
- WHEN the admin selects it in the token set dropdown and the apply dialog opens
- THEN the dialog MUST display the persisted contrast warning above the change list
- AND the admin MUST still be able to apply the set (warning is non-blocking)

#### Scenario: Compliant upload produces no warnings
@e2e exclude computation branch — PHPUnit on the contrast service with known-ratio fixtures
- GIVEN a CSS upload with `--nldesign-color-primary: #154273` and `--nldesign-color-primary-text: #ffffff` (ratio ≥ 4.5:1)
- WHEN the admin uploads it
- THEN the response `warnings` array MUST be empty

### Requirement: Custom Set Metadata and Theming Bridge
Custom-set metadata (display name, description, `theming.primary_color`, `theming.background_color`) MUST be stored in the `nldesign` appconfig key `custom_token_sets` as a JSON object indexed by set id. `theming.primary_color` and `theming.background_color` MUST be derived from the uploaded `--nldesign-color-primary` / `--nldesign-color-background` values when present so the theming-sync dialog works for custom sets exactly as for shipped sets.

#### Scenario: Uploaded set participates in theming sync
- GIVEN a custom set uploaded with `--nldesign-color-primary: #007bc7`
- WHEN the admin selects the custom set and the theming sync dialog opens
- THEN the dialog MUST offer to sync `#007bc7` as the Nextcloud primary color
- AND accepting MUST behave identically to a shipped set with the same theming metadata

#### Scenario: Manifest entry without a file is dropped
@e2e exclude discovery edge — PHPUnit on the discovery merge
- GIVEN the appconfig manifest contains `custom-stale` but `css/tokens/custom-stale.css` does not exist
- WHEN token sets are discovered
- THEN `custom-stale` MUST NOT appear in the available token sets
- AND no error MUST be raised

### Requirement: Manage Custom Token Sets
The admin panel MUST list uploaded sets with their contrast status and provide download (export) and delete actions. Export MUST return the exact CSS file that is served (`text/css`, `Content-Disposition: attachment`). Deleting the currently active set MUST reset the active token set to `nextcloud` in the same operation.

#### Scenario: Admin downloads an uploaded set
- GIVEN a stored custom set `custom-gemeente-voorbeeld`
- WHEN the admin clicks its Download action
- THEN the browser MUST download `custom-gemeente-voorbeeld.css`
- AND the content MUST be byte-identical to the served `css/tokens/custom-gemeente-voorbeeld.css`

#### Scenario: Admin deletes an inactive custom set
- GIVEN a stored custom set that is not the active token set
- WHEN the admin clicks Delete and confirms
- THEN the CSS file and its manifest entry MUST be removed
- AND the set MUST disappear from the dropdown and the custom-set list

#### Scenario: Deleting the active set falls back to nextcloud
- GIVEN `custom-gemeente-voorbeeld` is the active token set
- WHEN the admin deletes it
- THEN the active token set MUST be reset to `nextcloud`
- AND after reload no `custom-gemeente-voorbeeld` CSS MUST be injected on any page
