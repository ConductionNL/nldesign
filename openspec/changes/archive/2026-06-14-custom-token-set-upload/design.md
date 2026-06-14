# Design: custom-token-set-upload

## Context

The app's token-set pipeline is fully file-driven:

- `TokenSetService::getAvailableTokenSets()` scans `css/tokens/*.css` and merges metadata from the shipped `token-sets.json` (REQ-TSET-001/002).
- `Application::injectThemeCSS()` loads `tokens/{token_set}.css` via `\OCP\Util::addStyle()` when the design system is not `none`.
- `TokenSetPreviewService` parses the token-set CSS server-side for the apply dialog.
- `CustomOverridesService` already demonstrates the app's accepted write pattern: atomic write (temp file + rename) into the app directory (`css/custom-overrides.css`).

A custom token set that lands as a file in `css/tokens/` therefore inherits discovery, loading, preview, apply-dialog, and theming-sync behavior for free. The design problem reduces to: safe ingestion, metadata, id namespacing, and lifecycle.

## Goals / Non-Goals

**Goals**
- Admin-facing upload of a complete huisstijl as a token set (CSS or W3C Design Tokens JSON)
- Whitelist-based validation strong enough that uploaded CSS can be served to anonymous users (login page) without risk
- WCAG AA contrast warnings computed server-side at upload time
- Round-trip: download exactly what will be served; delete cleanly

**Non-Goals**
- No multi-file NL Design System "theme package" (zip) support — single CSS/JSON file only
- No per-group/multi-tenant assignment of custom sets (separate future change)
- No editing of an uploaded set in the token editor beyond what `custom-overrides.css` already allows on top of any active set
- No dark-mode variant generation

## Decisions

### D1 — Storage: app dir `css/tokens/custom-{slug}.css`

| Option | Pros | Cons |
|---|---|---|
| **A. App dir `css/tokens/` (chosen)** | Zero changes to loader/discovery/preview; consistent with `custom-overrides.css`; `addStyle()` works on login page | Lost on app-dir-replacing upgrade (same accepted trade-off as overrides); needs writable app dir |
| B. IAppData + controller-served CSS route | Survives upgrades; proper NC storage API | New `#[PublicPage]`+`#[NoCSRFRequired]` CSS-serving endpoint; `addStyle()` cannot address it, so boot must emit a raw `<link>` header; preview service and discovery need a second source; double the moving parts |
| C. Database (appconfig blob) | No filesystem at all | CSS in appconfig is size-limited and abuses the API; still needs a serving endpoint (all of B's cons) |

Chosen: **A**, with the export endpoint as the upgrade-loss mitigation and a documented caveat in the feature docs. If the fleet later standardizes on appdata-backed assets, migration is a copy of files plus no API change (ids stay stable).

### D2 — Validation pipeline (CSS input)

1. Size cap **512 KB** (bundled sets are < 20 KB; cap is generous).
2. MIME/extension gate: `.css` / `text/css` or `.json` / `application/json`.
3. Parse with the existing `CssParserService`; the file MUST reduce to exactly one `:root { … }` rule.
4. Declaration whitelist: property name MUST match `--nldesign-[a-z0-9-]+` or `--{slug}-[a-z0-9-]+` (org-palette extras, as bundled sets do with e.g. `--utrecht-color-*`).
5. Value blacklist: reject values containing `@import`, `expression(`, `javascript:`, `<`, or `url(` with a scheme/host (only relative `url('../../img/…')` and `data:image/svg+xml` are allowed, matching bundled logo usage).
6. Comments are stripped; output is **re-serialized** from the parsed declarations (never the raw upload bytes), so only whitelisted content can ever reach the served file.
7. Atomic write via temp file + rename (same as `CustomOverridesService::write()`).

### D3 — W3C Design Tokens JSON mapping

Input: a single JSON document in the Design Tokens Community Group draft format (`$value`/`$type`, nested groups). Mapping table (published in docs and returned by the API for transparency):

| Token path (suffix match) | `--nldesign-*` target |
|---|---|
| `color.primary` / `brand.primary` | `--nldesign-color-primary` |
| `color.primary-text` / `color.on-primary` | `--nldesign-color-primary-text` |
| `color.primary-hover` | `--nldesign-color-primary-hover` |
| `color.background` | `--nldesign-color-background` |
| `fontFamily.base` / `typography.font-family` | `--nldesign-font-family` |
| `border-radius.base` (dimension) | `--nldesign-border-radius` |
| … (full table maintained in `lib/Service/DesignTokensMapper.php`) | |

Unmapped tokens are skipped and counted. The mapper produces the same internal declaration list as the CSS path, so D2 steps 5–7 apply identically.

### D4 — Metadata and discovery merge

- Appconfig key `custom_token_sets` = JSON object `{ "custom-{slug}": { "name": …, "description": …, "theming": { "primary_color": …, "background_color": … } } }`.
- `theming.primary_color` / `background_color` are derived from the uploaded tokens when possible (`--nldesign-color-primary`, `--nldesign-color-background`) so the theming-sync dialog works for custom sets too; the admin can override them in the upload form.
- `getAvailableTokenSets()` merges: filesystem scan (already finds `custom-*.css`) + shipped manifest + custom manifest. A `custom-*.css` file without a manifest entry behaves like today's "CSS without manifest" scenario (id-as-name fallback); a manifest entry without a file is dropped (mirrors REQ-TSET-001).
- Dropdown labels custom sets with a "(custom)" suffix group so admins can tell shipped from uploaded sets.

### D5 — WCAG AA contrast check

Server-side relative-luminance computation (WCAG 2.1 definition) over fixed token pairs:

- `--nldesign-color-primary` vs `--nldesign-color-primary-text` — threshold 4.5:1 (normal text on buttons)
- `--nldesign-color-primary` vs `--nldesign-color-background` — threshold 3:1 (UI component boundary)

Result shape: `warnings: [{ pair, ratio, threshold, level: "AA" }]`, returned in the upload response and persisted in the custom-set manifest entry so the apply dialog can resurface it. Non-blocking by design (Decision 6 in proposal). Only hex/`rgb()` literal values are evaluated; pairs referencing unresolvable values are skipped with an `unevaluated` note rather than a false pass.

### D6 — Endpoints

| Route | Verb | Controller method | Notes |
|---|---|---|---|
| `/settings/tokensets/upload` | POST | `customTokenSet#upload` | multipart file + `name` field; admin-only (no `NoAdminRequired`) |
| `/settings/tokensets/custom` | GET | `customTokenSet#list` | manifest entries incl. contrast warnings |
| `/settings/tokensets/custom/{id}/export` | GET | `customTokenSet#export` | `text/css`, `Content-Disposition: attachment` |
| `/settings/tokensets/custom/{id}` | DELETE | `customTokenSet#delete` | active-set fallback to `nextcloud` |

All admin-only by Nextcloud default (no `#[NoAdminRequired]`), CSRF-checked (no `#[NoCSRFRequired]`).

## Risks / Trade-offs

- **Upgrade wipes uploaded files** — accepted, same as `custom-overrides.css`; mitigated by export + docs caveat; a repair-step re-import from appconfig is explicitly out of scope (CSS body would have to live in appconfig — see D1/C).
- **Whitelist may be too strict for exotic huisstijl CSS** — by design; the supported contract is the `--nldesign-*` vocabulary, not arbitrary CSS. Arbitrary per-token tweaks remain available via the token editor / `custom-overrides.css`.
- **W3C DTCG format is still a draft** — mapper is suffix-match based and tolerant; format drift degrades to "skipped" counts, never errors.

## Open Questions

- Should the upload form offer "activate immediately" (running the existing apply dialog straight after upload)? Default: no — upload returns to the dropdown, admin applies via the normal flow, keeping one apply path.
