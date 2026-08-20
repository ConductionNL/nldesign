---
kind: code
---

## Why

nldesign holds the instance's huisstijl (active token set, design system, WCAG posture, logo,
slogan/menu-label toggles) but exposes none of it programmatically. Every read surface it has is
admin-only (`lib/Settings/*` endpoints are `AuthorizedAdminSetting`), so portals, clients and
other Conduction apps that want to render in the instance's huisstijl have nothing to ask.
Evidence this consumer demand is real and currently unmet:

- **openbuild** ships a flagged, dormant code path because of exactly this gap:
  `openbuild/src/dialogs/ThemePickerDialog.vue` documents "(b) [flagged, NOT YET BUILT] a
  non-admin nldesign list endpoint — all of nldesign's settings/* is
  AuthorizedAdminSetting(Admin::class) today" and is written to activate automatically once
  nldesign ships a non-admin surface.
- Nextcloud core's own precedent: `apps/theming` implements `OCP\Capabilities\IPublicCapability`
  (name/url/slogan/colors/logo/background/favicon) and mobile/desktop clients consume it as the
  branding-push channel (platform research, `02-nc-theming-platform.md`). nldesign's richer,
  token-level huisstijl has no equivalent channel — ranked opportunity #5 in the platform map.
- Market: this is the **leaf-reintegration surface** — Conduction apps and municipal portals
  consume the huisstijl programmatically instead of hardcoding colors/logos per app
  (`05-gap-analysis.md` wave row 9: "portals/Conduction apps consume huisstijl programmatically
  (leaf reintegration point)"). The deferred `document-letterhead-tokens` change (branded
  PDF/letterhead output, docudesk leaf) is a planned consumer of this same payload and is
  cross-referenced here rather than specced.

A capability is the right shape (vs a new REST endpoint): it rides the existing, cached,
client-understood `/ocs/v2.php/cloud/capabilities` surface, works pre-login via
`IPublicCapability` (branding is needed on login/error surfaces before any session exists, and
everything exposed is already visible to anyone who loads the themed login page), and requires no
new route/auth surface in nldesign.

## What Changes

- New `lib/Capabilities.php` implementing `OCP\Capabilities\IPublicCapability`, returning a
  `nldesign` capability object:
  - `version` — nldesign app version;
  - `tokenSet` — `{ id, name, version }` of the active token set (`token_set` appconfig,
    default `nextcloud`); `version` is the manifest's optional version field, `null` when absent;
  - `designSystem` — the resolved design-system id for the active set
    (`DesignSystemService::getTokenSetMeta`, default `nldesign`, `none` for stock);
  - `wcagLevel` — the audited contrast level of the active set: `"AAA"` (declares
    `contrast_level: "AAA"` and passes the AAA audit), `"AA"` (passes the AA audit), `"fail"`
    (shipped set failing its audit), or `null` (custom/unaudited set), computed via
    `ShippedTokenSetAuditService`;
  - `logos` — object of available logo variant web paths for the active set; today one variant:
    `{ "default": "/apps/nldesign/img/logos/….svg" }` from the set's `theming.logo` manifest
    entry, or `{}` when the set declares none (the object shape leaves room for a `dark` variant,
    deferred to the dark-mode-token-variants change);
  - `hideSlogan`, `showMenuLabels` — the boolean state of the `hide_slogan` /
    `show_menu_labels` appconfig toggles.
- Register it in `Application::register()` via `$context->registerCapability(Capabilities::class)`
  (first real registration in that method; the existing AppHost comment stays accurate for
  health).
- WCAG audit result is cached (ICacheFactory, invalidated when the active token set changes) so
  the capabilities endpoint never re-parses token CSS per request; capability computation never
  throws (degrades to a minimal payload) because one throwing capability breaks the whole
  capabilities endpoint for every client.
- New canonical spec `theming-capability` (ADDED requirements in this change's delta).
- Unit tests (`tests/Unit/CapabilitiesTest.php`) + live curl verification against
  `/ocs/v2.php/cloud/capabilities` on the 8080 dev instance.
- No breaking changes; purely additive. No new routes, no Vue, no DB tables (IConfig + existing
  manifests only).

## Impact

- `lib/Capabilities.php` — new.
- `lib/AppInfo/Application.php` — `register()` gains the `registerCapability` call.
- `openspec/specs/theming-capability/spec.md` — new canonical spec (via this change's delta).
- `tests/Unit/CapabilitiesTest.php` — new.
- Consumers (outside this repo, later): openbuild ThemePickerDialog path (b), portals, the
  deferred `document-letterhead-tokens` change.
