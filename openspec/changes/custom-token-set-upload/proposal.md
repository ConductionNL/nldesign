# Proposal: custom-token-set-upload

## Why

`docs/GOVERNMENT-FEATURES.md` F-04 claims "Aangepaste token sets uploaden — eigen huisstijl als token set" with status **Beschikbaar**. This document is a PvE/tender compliance checklist used by procuring municipalities, so "Beschikbaar" must be true — but no upload route exists in `appinfo/routes.php`, no code exists in `lib/`, and no spec covers it. The existing `token-import-export` capability only covers `custom-overrides.css` (individual `--color-*` overrides), not uploading a whole new token set.

Municipalities that are not among the 48+ bundled organizations (or that have a refreshed huisstijl) currently have no supported way to bring their own brand: they would have to fork the app or hand-edit files on the server. A first-class upload flow closes the F-04 over-claim and is the single most-requested capability of any design-token theming product (NL Design System "theme" packages, Tokens Studio exports, W3C Design Tokens files all assume an import path).

## What Changes

- **NEW** — Admin can upload a custom token set from the NL Design admin settings panel, in either of two formats:
  - **CSS** — a `:root { --nldesign-*: …; }` file in the app's native token format (same format as the bundled `css/tokens/*.css` files)
  - **W3C Design Tokens JSON** — a `*.tokens.json` file following the Design Tokens Community Group format; recognized `color`/`fontFamily`/`dimension` tokens are mapped onto the `--nldesign-*` vocabulary
- **NEW** — Uploaded sets are stored as `css/tokens/custom-{slug}.css` and are picked up by the existing filesystem discovery, apply dialog, preview, and theming-sync machinery without modification
- **NEW** — Server-side validation pipeline: size cap, strict parse, `--nldesign-*` whitelist, rejection of `@import` / external `url()` / non-declaration CSS payloads
- **NEW** — WCAG 2.1 AA contrast check on upload: contrast ratios of the uploaded primary/primary-text (and other paired tokens) are computed server-side and reported as **non-blocking warnings** before the admin activates the set — protecting the F-09 "WCAG 2.1 AA-conforme kleuren" guarantee on the only new surface that accepts arbitrary colors
- **NEW** — Manage uploaded sets: list, download (round-trip export), and delete; deleting the active set falls back to `nextcloud`
- **MODIFIED** — Token set discovery/manifest merge: uploaded sets carry their metadata (name, primary color, background color) from an appconfig-backed manifest instead of the shipped `token-sets.json`, and are labeled as custom in the dropdown

## Capabilities

### New Capabilities
- `custom-token-sets` — Upload, validate, list, download, and delete admin-provided token sets; format mapping (W3C Design Tokens JSON → `--nldesign-*` CSS); WCAG AA contrast warnings; storage and lifecycle

### Modified Capabilities
- `token-sets` — Discovery merges uploaded custom sets (appconfig manifest) with shipped sets (`token-sets.json`); custom ids are namespaced `custom-*` and may never shadow a shipped set

## Decisions

1. **Storage location**: uploaded CSS lands in the app directory at `css/tokens/custom-{slug}.css` — exactly the same write target pattern the app already uses for `css/custom-overrides.css` (`CustomOverridesService`). This means the existing `Util::addStyle('nldesign', 'tokens/'.$tokenSet)` loader, the filesystem discovery scan, `TokenSetPreviewService`, and the apply dialog all work unchanged. The known trade-off (files in the app dir do not survive an app-store upgrade that replaces the directory) is identical to the accepted `custom-overrides.css` trade-off and is mitigated by the download/export endpoint. See design.md for rejected alternatives.
2. **Metadata storage**: shipped `token-sets.json` stays read-only. Custom-set metadata (display name, description, `theming.primary_color`, `theming.background_color`) lives in the `nldesign` appconfig key `custom_token_sets` (JSON object indexed by id) and is merged during discovery.
3. **Id namespace**: uploaded sets always get id `custom-{slug}` where slug is derived from the supplied display name (`[a-z0-9-]`, max 64 chars). Shipped set ids can therefore never be overwritten, and a custom set is recognizable everywhere by its prefix.
4. **Validation is whitelist-based**: only `--nldesign-*` custom property declarations inside a single `:root` block are accepted from CSS uploads (plus `--{slug}-*` org-palette extras, mirroring how bundled sets like `utrecht.css` carry `--utrecht-color-*`). `@import`, `@font-face`, external `url(…)`, `expression(…)`, and any selector other than `:root` are rejected with a structured error. Admin-only feature, but the output is CSS served to every user on every page — it must not become a stored-XSS/exfiltration vector.
5. **W3C Design Tokens mapping is best-effort and reported**: recognized token paths map to `--nldesign-*` variables via a published mapping table; unmapped tokens are skipped and counted (`imported`, `skipped`), mirroring the import behavior already specced in `token-import-export`.
6. **Contrast check warns, never blocks**: per the report recommendation, WCAG AA failures (e.g. primary vs primary-text < 4.5:1) produce warnings in the upload response and in the apply dialog, but the admin may proceed. Government users must be able to upload an in-progress huisstijl; the compliance signal is the warning.
7. **No Vue**: the admin panel is vanilla PHP templates + vanilla JS per the established `admin-settings` spec; the upload UI follows the existing import/export UI pattern (hidden file input + result message).

## Impact

- **nldesign app only** — new `CustomTokenSetService`, new controller endpoints (`POST /settings/tokensets/upload`, `GET /settings/tokensets/custom`, `GET /settings/tokensets/custom/{id}/export`, `DELETE /settings/tokensets/custom/{id}`), admin template/JS additions
- **token-sets discovery** — merge step for the appconfig manifest; alphabetical sort now spans shipped + custom sets
- **docs** — `docs/GOVERNMENT-FEATURES.md` F-04 becomes genuinely true; `docs/features/` gains a custom-token-sets page
- **No database migration** — appconfig + file-based, consistent with the rest of the app

## Rollback Strategy

- Delete `css/tokens/custom-*.css` and the `custom_token_sets` appconfig key; if the active `token_set` starts with `custom-`, reset it to `nextcloud`
- All other capabilities (discovery, editor, overrides) are unaffected by removal — custom sets are additive leaves in the existing CSS stack
- If rolled back before release, downgrade F-04 in `docs/GOVERNMENT-FEATURES.md` to "Gepland" so the compliance checklist stays honest
