# Tasks: nextcloud-theme-editor

## 1. Foundation — Token Registry and CSS Load Order

- [x] 1.1 Create `TokenRegistry.php`: static array of all editable `--color-*` tokens with tab assignment (`login|content|status|typography`), type (`color|text`), and display label. Derived from mapped tokens in `overrides.css`. Excludes all tokens marked "intentionally not overridden".
- [x] 1.2 Create `CustomOverridesService.php`: `read(): array`, `write(array $tokens): void` (atomic write via temp file + rename), `ensureExists(): void`. `write()` validates names against `TokenRegistry`. CSS format: single `:root {}` block, no `!important`.
- [x] 1.3 Extend `Application::injectThemeCSS()`: add `\OCP\Util::addStyle('nldesign', 'custom-overrides')` as the final call. Calls `CustomOverridesService::ensureExists()` to guarantee the file exists.

## 2. Backend Endpoints — Overrides CRUD

- [x] 2.1 Add `GET /settings/overrides` route and `SettingsController::getOverrides()`: returns overrides + registry + tab labels.
- [x] 2.2 Add `POST /settings/overrides` route and `SettingsController::setOverrides()`: validates token names against registry (returns HTTP 400 for unknown/excluded tokens), calls `CustomOverridesService::write()`.

## 3. Backend Endpoints — Token Set Preview

- [x] 3.1 Create `TokenSetPreviewService.php`: `getResolvedColors(string $tokenSetId): array`. Parses defaults.css + tokens/{id}.css + overrides.css to compute resolved `--color-*` values server-side.
- [x] 3.2 Add `GET /settings/tokenset-preview/{tokenSetId}` route and `SettingsController::getTokenSetPreview()`.

## 4. Backend Endpoints — Import/Export

- [x] 4.1 Add `GET /settings/overrides/export` route and `SettingsController::exportOverrides()`: returns raw CSS as download.
- [x] 4.2 Add `POST /settings/overrides/import` route and `SettingsController::importOverrides()`: accepts CSS file upload (max 256 KB), filters against TokenRegistry, returns `{ imported, skipped }`.

## 5. Token Editor Panel (vanilla JS — no Vue)

- [x] 5.1 `initTokenEditor()` + `renderTokenEditor()`: fetches `/settings/overrides`, reads resolved values from `getComputedStyle`, renders tabbed panel into `#nldesign-token-editor`.
- [x] 5.2 `buildTokenRow()`: renders label, color picker + hex text, or text input, customized badge, reset button.
- [x] 5.3 `applyLivePreview()`: wires `style.setProperty()` on input events.
- [x] 5.4 `saveOverrides()`: collects non-default values, POSTs to `/settings/overrides`.
- [x] 5.5 Reset button: `style.removeProperty()`, clears badge, marks as dirty.

## 6. Token Set Apply Dialog (vanilla JS)

- [x] 6.1 `openTokenSetApplyDialog()`: fetches preview, computes changes via `getComputedStyle`, calls `showApplyDialog()`.
- [x] 6.2 Live preview per checkbox row via `style.setProperty()`.
- [x] 6.3 Apply: fetch + merge + POST to `/settings/overrides`, then POST token set config.
- [x] 6.4 Select all / Deselect all toggles.
- [x] 6.5 Intercept token-set dropdown change event to open dialog instead of direct save.

## 7. Import/Export UI (vanilla JS)

- [x] 7.1 Download button: triggers `GET /settings/overrides/export` via `<a download>`.
- [x] 7.2 Upload button + hidden file input: POSTs to `/settings/overrides/import`, shows result message, reloads editor.

## 8. Integration and Registration

- [x] 8.1 Register all new routes in `appinfo/routes.php`: overrides CRUD, export, import, tokenset-preview.
- [x] 8.2 `CustomOverridesService` and `TokenSetPreviewService` use `IAppManager` constructor injection (autowired by Nextcloud DI — no manual registration needed).
- [x] 8.3 Added `#nldesign-token-editor` placeholder to `templates/settings/admin.php`. Updated heading text to reflect broader scope.
