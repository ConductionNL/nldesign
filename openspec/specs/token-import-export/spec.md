# Token Import/Export Specification

## Purpose
Allows admins to download the current `custom-overrides.css` as a portable file and upload a previously saved file to restore or share a token configuration. Only known, editable Nextcloud `--color-*` tokens are accepted on import — unknown variables are silently rejected and their count is reported.

## ADDED Requirements

### Requirement: Export Current Overrides
The admin settings panel MUST provide a **Download** button that exports the current `custom-overrides.css` as a file download.

#### Scenario: Admin downloads overrides
- GIVEN `custom-overrides.css` contains `--color-primary: #c00000` and `--color-error: #b30000`
- WHEN the admin clicks Download
- THEN the browser MUST download a file named `custom-overrides.css`
- AND the file content MUST be valid CSS containing the current overrides
- AND the file MUST be formatted identically to the server-side `custom-overrides.css`

#### Scenario: Download with no custom overrides
- GIVEN `custom-overrides.css` is empty (no custom tokens set)
- WHEN the admin clicks Download
- THEN the browser MUST download a file with only the header comment and an empty `:root {}` block
- AND the download MUST NOT be blocked or result in an error

#### Scenario: Download is a GET request to a dedicated endpoint
- GIVEN the admin clicks Download
- WHEN the request is made
- THEN it MUST call `GET /api/overrides/export`
- AND the response Content-Type MUST be `text/css`
- AND the Content-Disposition MUST be `attachment; filename="custom-overrides.css"`

### Requirement: Import Token File
The admin settings panel MUST provide an **Upload** button that accepts a CSS file, parses it for known `--color-*` tokens, and writes the recognized tokens to `custom-overrides.css`, replacing the current overrides.

#### Scenario: Admin uploads a valid overrides file
- GIVEN a CSS file contains `--color-primary: #aa0000` and `--color-error: #990000`
- WHEN the admin uploads the file
- THEN both tokens MUST be written to `custom-overrides.css` (replacing previous overrides)
- AND the token editor forms MUST reflect the imported values
- AND the live preview MUST update to show the imported values

#### Scenario: Import replaces existing overrides
- GIVEN `custom-overrides.css` currently contains `--color-warning: #ff8800`
- AND the uploaded file contains `--color-primary: #aa0000` but NOT `--color-warning`
- WHEN the admin uploads the file
- THEN `custom-overrides.css` MUST contain only `--color-primary: #aa0000`
- AND `--color-warning` MUST be removed (import is a full replace, not a merge)

### Requirement: Import Validation
On upload, the importer MUST validate each CSS custom property against the canonical editable token registry. Only tokens on the editable list MUST be written.

#### Scenario: File contains unknown tokens
- GIVEN an uploaded CSS file contains `--color-primary: #aa0000` (known) and `--my-custom-var: red` (unknown)
- WHEN the file is imported
- THEN `--color-primary` MUST be written to `custom-overrides.css`
- AND `--my-custom-var` MUST be silently rejected
- AND the response MUST report: "2 tokens found: 1 imported, 1 skipped"

#### Scenario: File contains only unknown tokens
- GIVEN an uploaded CSS file contains only variables not in the editable token registry
- WHEN the file is imported
- THEN `custom-overrides.css` MUST be written as empty (header + empty `:root {}`)
- AND the response MUST report: "X tokens found: 0 imported, X skipped"
- AND no error MUST be thrown (it is valid to import a file that contributes no tokens)

#### Scenario: File contains excluded tokens
- GIVEN an uploaded CSS file contains `--color-main-background: #ffffff` (excluded)
- WHEN the file is imported
- THEN `--color-main-background` MUST be silently rejected
- AND it MUST be counted in the "skipped" total

#### Scenario: File is not valid CSS
- GIVEN the admin uploads a file that is not parseable CSS (e.g. a JSON file or empty file)
- WHEN the import endpoint receives the file
- THEN the server MUST return HTTP 400
- AND the error MUST state the file could not be parsed as CSS
- AND `custom-overrides.css` MUST remain unchanged

#### Scenario: File exceeds size limit
- GIVEN the admin uploads a file larger than 256 KB
- WHEN the upload is submitted
- THEN the server MUST return HTTP 413
- AND `custom-overrides.css` MUST remain unchanged

### Requirement: Import Result Feedback
After a successful import, the UI MUST show a summary of the import result before the admin can continue.

#### Scenario: Import summary is shown
- GIVEN a file with 15 tokens was uploaded, of which 12 were known and 3 were unknown
- WHEN the import completes
- THEN the UI MUST show a message: "12 tokens imported, 3 tokens skipped (not recognized)"
- AND the message MUST be dismissible
- AND the token editor forms MUST immediately reflect the imported values without a page reload

### Requirement: Upload Endpoint
The import MUST be handled by a dedicated POST endpoint that accepts a multipart file upload.

#### Scenario: Upload endpoint receives file
- GIVEN the admin submits a file via the Upload button
- WHEN the request is made
- THEN it MUST POST to `POST /api/overrides/import` as `multipart/form-data`
- AND the server MUST parse the file content server-side (not rely on client-side JS parsing)
- AND the response MUST be JSON with `{ imported: N, skipped: M }`
