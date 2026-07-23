# Prometheus Metrics — Admin-Auth Delta

**Spec refs**: `prometheus-metrics`, hydra ADR-006 (metrics — admin auth, health — public)
**Standards**: Prometheus text exposition format, OWASP information-exposure guidance (version
fingerprinting)

## MODIFIED Requirements

### Requirement: Metrics Endpoint

The app MUST expose a Prometheus-compatible metrics endpoint that returns all app metrics in the
standard text exposition format, reachable only by an authenticated Nextcloud admin session (per
hydra ADR-006: `GET /api/metrics` is "Prometheus text, admin auth"). `MetricsController::index()`
MUST carry neither `#[PublicPage]` nor `#[NoAdminRequired]`, so Nextcloud's `SecurityMiddleware`
default (admin-only) applies. The endpoint MAY remain `#[NoCSRFRequired]` (or
`@NoCSRFRequired`) so an authenticated scraper is not blocked by CSRF token requirements — CSRF
exemption and authentication are independent properties, and this requirement narrows the
previous "publicly accessible without CSRF" wording, which incorrectly conflated the two.

#### Scenario: Metrics endpoint rejects unauthenticated requests

- GIVEN an anonymous (non-admin-authenticated) caller requests `GET /index.php/apps/nldesign/api/metrics`
- WHEN the request reaches `MetricsController::index()`
- THEN Nextcloud's `SecurityMiddleware` MUST reject the request (no session / non-admin session)
  because the method carries neither `#[PublicPage]` nor `#[NoAdminRequired]`
- AND the response MUST NOT contain `nldesign_info`, token-set counts, override counts, or any
  other metric value

#### Scenario: Metrics endpoint serves an authenticated admin without a CSRF token

- GIVEN an authenticated admin session (or an admin app-password via HTTP Basic, as configured for
  a Prometheus scrape target)
- WHEN `GET /index.php/apps/nldesign/api/metrics` is called without a CSRF token
- THEN the request MUST succeed (CSRF exemption still applies for admin-authenticated callers)
- AND the response MUST have content type `text/plain; version=0.0.4; charset=utf-8`

#### Scenario: Metrics endpoint returns all metric families for an authenticated admin

- GIVEN an authenticated admin session
- WHEN the metrics endpoint is called
- THEN it MUST contain HELP and TYPE lines for each metric family exactly as before this change
  (info, up, token sets total, active token set, custom overrides total, theming syncs total)
- AND each metric family MUST have at least one sample line

#### Scenario: Route registration is unchanged

- GIVEN the app's routes configuration
- WHEN routes are loaded from `appinfo/routes.php`
- THEN a GET route for `/api/metrics` MUST still be mapped to `metrics#index` (only the
  controller method's auth attributes change, not the route)
