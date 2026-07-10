## 1. Fix the auth posture

- [ ] 1.1 Remove `#[PublicPage]` from `MetricsController::index()`
      (`lib/Controller/MetricsController.php:75`). Keep `#[NoCSRFRequired]` / `@NoCSRFRequired`.
- [ ] 1.2 Remove the now-inaccurate docblock line above `index()` that implies public,
      unauthenticated access, and replace it with the ADR-006 admin-auth rationale (mirror the
      wording style used in `pipelinq/lib/Controller/MetricsController.php:13-14` and
      `docudesk/lib/Controller/MetricsController.php:38`).

## 2. Update the spec

- [ ] 2.1 In `openspec/specs/prometheus-metrics/spec.md`, reword the `REQ-PROM-001` scenario
      "Metrics endpoint is publicly accessible without CSRF" to state the endpoint requires an
      admin session (SecurityMiddleware default, no `#[NoAdminRequired]`/`#[PublicPage]`) and is
      merely CSRF-exempt for scraper convenience — not unauthenticated.
- [ ] 2.2 Update the spec's "Current Implementation Status" section to match the new auth
      posture.

## 3. Tests

- [ ] 3.1 Add/update a controller test asserting a request to `metrics#index` without an admin
      session is rejected (401/redirect per Nextcloud's SecurityMiddleware behavior for
      non-admin-authenticated requests), mirroring how `pipelinq`/`docudesk` test their metrics
      controllers' admin-only posture (if an equivalent test pattern exists there — otherwise add
      a minimal PHPUnit case using the standard OCP SecurityMiddleware test harness for this app).
- [ ] 3.2 Confirm `HealthController` behavior is unaffected (still public) — no code change
      expected, but re-run its existing test to confirm no regression.

## 4. Verify

- [ ] 4.1 Run the PHPUnit suite (`composer test:unit` / project's PHPUnit target) and confirm all
      MetricsController and HealthController tests pass.
- [ ] 4.2 Manually curl `/index.php/apps/nldesign/api/metrics` unauthenticated against a running
      dev instance and confirm it now returns 401 (not the metrics body), and confirm an
      authenticated admin session (or Basic auth with an admin app-password) still returns the
      metrics body.
