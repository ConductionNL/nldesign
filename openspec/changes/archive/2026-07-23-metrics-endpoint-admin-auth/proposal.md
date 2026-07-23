---
kind: code
---

## Why

Hydra ADR-006 (`hydra/openspec/architecture/adr-006-metrics.md`) states the fleet-wide contract
for every app's observability surface:

> Every app: `GET /api/metrics` (Prometheus text, **admin auth**) + `GET /api/health` (JSON,
> public).

`nldesign`'s `MetricsController::index()` (`lib/Controller/MetricsController.php:75-76`) is
annotated `#[PublicPage]`, making `/api/metrics` reachable by **any unauthenticated caller** —
the opposite of ADR-006's "admin auth" requirement. Nextcloud's `SecurityMiddleware` default is
admin-only for any controller method that carries neither `#[PublicPage]` nor
`#[NoAdminRequired]`; the correct implementation is to omit both attributes, not to add
`#[PublicPage]`.

This is a fleet outlier, not a fleet convention: both `pipelinq/lib/Controller/MetricsController.php`
(lines 13-14: "Auth posture is the engine's: there is intentionally NO `#[NoAdminRequired]` /
`#[PublicPage]`, so NC requires an admin session (ADR-006)") and
`docudesk/lib/Controller/MetricsController.php` (line 8, 38: "the admin-only auth posture (no
`#[NoAdminRequired]`)... means Nextcloud requires an admin session") deliberately rely on the
SecurityMiddleware default to satisfy ADR-006. nldesign is the only sampled app that instead
opts in to `#[PublicPage]`.

The unauthenticated response body currently leaks, to any anonymous requester:
`nldesign_info{version="…",php_version="…",nextcloud_version="…"}` (app/PHP/Nextcloud version
fingerprinting — useful for targeting known CVEs against a specific version) plus the active
token set name, the count of stored token sets, and the count of custom CSS overrides
(`lib/Controller/MetricsController.php:115-172`). None of this is required to be public: nldesign's
own `REQ-PROM-009` health endpoint already covers the "is the app up" signal Prometheus/load-balancer
scraping actually needs, and it is correctly public per ADR-006. The metrics endpoint's own spec
(`openspec/specs/prometheus-metrics/spec.md`, `REQ-PROM-001` scenario "Metrics endpoint is publicly
accessible without CSRF") currently *codifies* the deviation rather than catching it — the spec
needs to move in step with the code fix.

## What Changes

- **BREAKING (auth posture):** Remove `#[PublicPage]` from `MetricsController::index()`
  (`lib/Controller/MetricsController.php:75`). The route falls through to
  `SecurityMiddleware`'s admin-only default, matching ADR-006 and the pipelinq/docudesk
  precedent. `#[NoCSRFRequired]` stays (Prometheus scrapers still can't present a CSRF token; a
  scraper authenticates via HTTP Basic/app-password against an admin account instead, same as the
  fleet precedent apps).
- Update `openspec/specs/prometheus-metrics/spec.md` `REQ-PROM-001` scenario "Metrics endpoint is
  publicly accessible without CSRF" to reflect admin-auth-with-no-CSRF-requirement, not
  public-without-authentication — the two are different properties and the current wording
  conflates them.
- No change to `HealthController` (`/api/health` is correctly `#[PublicPage]` per ADR-006 and
  `REQ-PROM-009`) or to any other route.
- Operators who scrape `/api/metrics` unauthenticated today (if any) will start receiving 401s and
  must switch to an authenticated scrape (admin session cookie or Basic auth with an admin
  app-password), matching how every other fleet app's Prometheus target is configured.

## Impact

- `lib/Controller/MetricsController.php` — remove `#[PublicPage]`.
- `openspec/specs/prometheus-metrics/spec.md` — reword `REQ-PROM-001`'s CSRF scenario.
- Any external Prometheus scrape config pointed at nldesign's `/api/metrics` without credentials
  (deployment-side change, outside this repo).
