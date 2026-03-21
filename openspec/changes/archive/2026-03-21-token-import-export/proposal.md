# Token Import/Export Specification

## Problem
Allows admins to download the current `custom-overrides.css` as a portable file and upload a previously saved file to restore or share a token configuration. Only known, editable Nextcloud `--color-*` tokens are accepted on import — unknown variables are silently rejected and their count is reported.


## Proposed Solution
Implement Token Import/Export Specification following the detailed specification. Key requirements include:
- Requirement: Export Current Overrides
- Requirement: Import Token File
- Requirement: Import Validation
- Requirement: Import Result Feedback
- Requirement: Upload Endpoint

## Scope
This change covers all requirements defined in the token-import-export specification.

## Success Criteria
- Admin downloads overrides
- Download with no custom overrides
- Download is a GET request to a dedicated endpoint
- Admin uploads a valid overrides file
- Import replaces existing overrides
