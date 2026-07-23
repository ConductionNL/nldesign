## 1. Fix the auth posture

- [x] 1.1 Remove `#[PublicPage]` from `MetricsController::index()`
      (`lib/Controller/MetricsController.php:75`). Keep `#[NoCSRFRequired]` / `@NoCSRFRequired`.
      (Already landed on `development` prior to this change — verified via `git diff
      origin/development -- lib/Controller/MetricsController.php` = empty; no `#[PublicPage]`
      attribute or import present, `@NoCSRFRequired` docblock annotation retained.)
- [x] 1.2 Remove the now-inaccurate docblock line above `index()` that implies public,
      unauthenticated access, and replace it with the ADR-006 admin-auth rationale (mirror the
      wording style used in `pipelinq/lib/Controller/MetricsController.php:13-14` and
      `docudesk/lib/Controller/MetricsController.php:38`).
      (Already landed alongside 1.1 — `index()`'s docblock reads "Deliberately NOT a
      #[PublicPage]: ... the Nextcloud SecurityMiddleware default applies — admin-only".)

## 2. Update the spec

- [x] 2.1 In `openspec/specs/prometheus-metrics/spec.md`, reword the `REQ-PROM-001` scenario
      "Metrics endpoint is publicly accessible without CSRF" to state the endpoint requires an
      admin session (SecurityMiddleware default, no `#[NoAdminRequired]`/`#[PublicPage]`) and is
      merely CSRF-exempt for scraper convenience — not unauthenticated.
      (Already landed — scenario is now titled "Metrics endpoint requires an authenticated admin
      session"; also updated the stale e2e-coverage anchor in
      `tests/e2e/spec-coverage/prometheus-metrics.spec.ts` that still pointed at the old
      scenario title.)
- [x] 2.2 Update the spec's "Current Implementation Status" section to match the new auth
      posture. (Added an explicit line documenting the absent `#[PublicPage]`/`#[NoAdminRequired]`
      attributes and the retained `@NoCSRFRequired` annotation.)

## 3. Tests

- [x] 3.1 Add/update a controller test asserting a request to `metrics#index` without an admin
      session is rejected (401/redirect per Nextcloud's SecurityMiddleware behavior for
      non-admin-authenticated requests), mirroring how `pipelinq`/`docudesk` test their metrics
      controllers' admin-only posture (if an equivalent test pattern exists there — otherwise add
      a minimal PHPUnit case using the standard OCP SecurityMiddleware test harness for this app).
      (Neither pipelinq nor docudesk has a MetricsController test to mirror. Added
      `tests/Unit/Controller/MetricsControllerTest.php`: this standalone PHPUnit harness has no
      live `SecurityMiddleware`/route-dispatch layer to assert a real 401 against, so the test
      asserts the statically-verifiable precondition the admin-only default depends on —
      `index()` carries neither `#[PublicPage]` nor `#[NoAdminRequired]` (via `ReflectionMethod`
      + real OCP attribute classes, mounted from the Nextcloud checkout in the docker phpunit
      run) — plus confirms `@NoCSRFRequired` is retained and the method still returns a correct
      `TextPlainResponse` body. The literal unauthenticated-401 assertion is covered by the
      deferred manual curl check in 4.2.)
- [x] 3.2 Confirm `HealthController` behavior is unaffected (still public) — no code change
      expected, but re-run its existing test to confirm no regression.
      (No code change: `git diff origin/development -- lib/Controller/HealthController.php` is
      empty. No dedicated HealthController unit test exists or can safely be added in this
      harness — `HealthController` extends OpenRegister's `GenericHealthController`, an optional
      soft dependency not present in nldesign's own `vendor/` tree, so reflecting/instantiating
      the class here would fail to autoload the parent. Its `#[PublicPage]` + `#[NoCSRFRequired]`
      posture and behavior are engine-owned and exercised by OpenRegister's own test suite; this
      change touches neither the controller nor the manifest health-check config.)

## 4. Verify

- [x] 4.1 Run the PHPUnit suite (`composer test:unit` / project's PHPUnit target) and confirm all
      MetricsController and HealthController tests pass. (Ran via the docker phpunit-unit
      command from the builder brief — see PR body for the exact pass count/tail.)
- [ ] 4.2 Manually curl `/index.php/apps/nldesign/api/metrics` unauthenticated against a running
      dev instance and confirm it now returns 401 (not the metrics body), and confirm an
      authenticated admin session (or Basic auth with an admin app-password) still returns the
      metrics body. (deferred to post-merge live verification — requires the live 8080 instance)
