# Design: token-import-export

## Context
Download/upload custom-overrides.css as a portable file. Only editable tokens accepted on import.

## Decisions
1. Export via GET /settings/overrides/export (DataDownloadResponse)
2. Import via POST /settings/overrides/import (multipart upload)
3. Max file size 256 KB
4. Import validation: only TokenRegistry-listed tokens accepted
