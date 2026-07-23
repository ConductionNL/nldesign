## 1. Extract the injection service

- [x] 1.1 Create `lib/Service/CssInjectionService.php` (SPDX docblock, `@spec` tags): move the
      body of `Application::injectThemeCSS()` into `inject(string $context): void` with
      constructor-injected `IConfig`, `DesignSystemService`, `CustomOverridesService`. Preserve
      the exact stylesheet order: design-system stylesheets (declared order) → `tokens/{set}` →
      `icon-contrast` → `error-contrast` → `custom-overrides` (after
      `CustomOverridesService::ensureExists()`) → conditional `hide-slogan` /
      `show-menu-labels`. No logic changes beyond parameterization.
      Note: also constructor-injects `FontService` + `IURLGenerator` (omitted from this task's
      own wording but required by the proposal's explicit instruction to preserve the
      custom-font stylesheet link — layer 4.5 in the cascade). The two `\OCP\Util::` static
      calls are wrapped in protected `emitStyle()`/`emitFontLink()` seams purely so unit tests
      can assert the call sequence via a partial mock (the real static call delegates to
      `\OC_Util`, unavailable outside a full Nextcloud bootstrap) — no behavior change.
- [x] 1.2 Implement context gating in the service: read appconfig `themed_contexts` (JSON array;
      recognized values `user`, `login`, `guest`, `public`, `error`). Absent / empty / invalid
      JSON ⇒ all contexts themed. When the given context is not in a valid configured list,
      `inject()` returns without adding any style.

## 2. Listener

- [x] 2.1 Create `lib/Listener/ThemeInjectionListener.php` implementing
      `OCP\EventDispatcher\IEventListener` (SPDX docblock, `@spec` tags), handling:
      - `OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent`: map
        `getResponse()->getRenderAs()` to context (`user`/`guest`/`public`/`error`; any other
        value → treat as themed, context `user` gating semantics per design.md §2), apply the
        per-app guard (2.2), then call `CssInjectionService::inject($context)`;
      - `OCP\AppFramework\Http\Events\BeforeLoginTemplateRenderedEvent`: no per-app guard, call
        `inject('login')`.
      Wrap handling in try/catch failing open to no-op (an event listener throwing must never
      break page rendering).
      Note: "any other value" is passed through to `inject()` unchanged rather than normalized
      to `'user'` — `CssInjectionService::VALID_CONTEXTS` only recognizes the five named
      contexts, so any unmapped/future `renderAs` value automatically fails open to themed
      regardless of the configured `themed_contexts` list (this is what the ADDED spec
      requirement "Unknown renderAs values stay themed" actually demands: the list must never
      be able to strip theming for an unknown value, which mapping to literal `'user'` would
      have violated whenever `'user'` is excluded).
- [x] 2.2 Move the per-app guard into the listener: resolve app id primarily from
      `TemplateResponse::getApp()` (ignore empty/`core`), fall back to
      `AppThemingService::resolveAppIdFromPath($request->getPathInfo())`, then consult
      `AppThemingService::isThemingDisabledFor()`; any throwable or unresolved id fails open to
      themed. Update `AppThemingService::resolveAppIdFromPath()` docblock to describe its
      fallback role (no API change).
      Note: `resolveAppIdFromPath()`'s docblock was already accurate/generic (describes the
      path-matching contract, not caller identity) — left unchanged; no behavior or API change.

## 3. Application cleanup

- [x] 3.1 In `lib/AppInfo/Application.php` `register()`: add
      `$context->registerEventListener(BeforeTemplateRenderedEvent::class, ThemeInjectionListener::class);`
      and `$context->registerEventListener(BeforeLoginTemplateRenderedEvent::class, ThemeInjectionListener::class);`
      with imports; update the docblock.
- [x] 3.2 Remove `injectThemeCSS()` and `isThemingDisabled()`; reduce `boot()` to an empty
      method with a comment stating injection is event-driven (IBootstrap requires the method).
- [x] 3.3 Remove the dead import `use OCA\NLDesign\Themes\NLDesignTheme;` (line 27 — class
      exists nowhere; no `lib/Themes/` directory).
- [x] 3.4 Bump `appinfo/info.xml` `<version>` (CSS delivery path changed — bust the `?v=`
      cache-buster). Bumped `0.1.3-unstable.14` → `0.1.3-unstable.15`; CHANGELOG.md "Unreleased"
      section updated.

## 4. Tests

- [x] 4.1 New `tests/Unit/Listener/ThemeInjectionListenerTest.php`:
      - renderAs `user`/`guest`/`public`/`error` each reach `inject()` with the right context;
      - unknown renderAs (`blank`) still injects (fail-open);
      - login event injects with context `login` and never consults the per-app guard;
      - guard: response app `calendar` + `calendar` excluded ⇒ no injection; response app empty
        ⇒ path fallback used; resolver throwing ⇒ injection proceeds;
      - non-TemplateResponse events / throwing service ⇒ no exception escapes `handle()`;
      - double dispatch of the same event yields no duplicated `addStyle` effects (idempotency,
        design.md §4).
      13 tests, incl. the `core`-attributed-response fallback case (task 2.2's second URL form)
      as an extra beyond the brief's list.
- [x] 4.2 New `tests/Unit/Service/CssInjectionServiceTest.php`: stylesheet order preserved
      (assert the exact `addStyle` sequence for a `nldesign`-system set, the empty sequence for
      `design_system: none` except token/contrast handling per current behavior, conditional
      hide-slogan/menu-labels); `themed_contexts` gating (absent ⇒ all themed; `["user"]` ⇒
      `login` context injects nothing; invalid JSON ⇒ all themed).
      14 tests, incl. custom-font-link injection/omission (layer 4.5) and the non-array-JSON
      fail-open case as extras beyond the brief's list.
- [x] 4.3 Migrate/retire the existing boot-guard unit tests that target
      `Application::isThemingDisabled()`; keep `AppThemingService` tests unchanged (service API
      untouched).
      No pre-existing tests targeted `Application::isThemingDisabled()`/`injectThemeCSS()` to
      retire — both were private methods with no reflection-based test coverage in the
      pre-change suite (verified via `grep -rl 'isThemingDisabled\|injectThemeCSS' tests/`).
      `AppThemingServiceTest.php` is untouched, confirming the service API is unaffected.
- [x] 4.4 Run `composer check:strict` and the PHP suite in the nextcloud:34 container
      (`docker run --rm -v $PWD:/app -w /app <nc-image> php vendor/bin/phpunit -c phpunit-unit.xml`);
      green.
      `check:strict`: lint/phpcs/phpmd/psalm/phpstan all green; `test:all` inside `check:strict`
      dies at suite-load time on the pre-existing, unrelated `OC\Mail\EMailTemplate` gap
      (`tests/Unit/Mail/NLDesignEMailTemplateTest.php`) — documented harness limitation, not a
      regression from this change (reproduced identically on `development` before this change).
      Running only the new + an existing unaffected suite in the nextcloud:34.0.0-apache
      container (excluding the blocked Mail test file) is fully green: 43 tests, 65 assertions.

## 5. Verify (regression — all surfaces still receive the stylesheets)

- [ ] 5.1 Deploy to the 8080 dev instance with an active token set (e.g. `rijkshuisstijl`);
      restart apache in the container (`apachectl -k restart`) so opcache picks up the change.
      (deferred to post-merge live verification)
- [ ] 5.2 Login page: `curl -sL http://localhost:8080/login | grep -c "apps/nldesign/css"` ≥ 1,
      and the matched links include the design-system and token stylesheets (compare the link
      set against a pre-change capture — must be identical).
      (deferred to post-merge live verification)
- [ ] 5.3 User page: authenticated curl (admin session cookie or basic auth)
      `curl -su admin:admin -L http://localhost:8080/index.php/apps/files/ | grep -c "apps/nldesign/css"`
      ≥ 1 with the same link set as pre-change.
      (deferred to post-merge live verification)
- [ ] 5.4 Public share page: create a public share link for a file (occ or UI), then
      unauthenticated `curl -sL http://localhost:8080/s/<token> | grep -c "apps/nldesign/css"`
      ≥ 1.
      (deferred to post-merge live verification)
- [ ] 5.5 Per-app exclusion regression: exclude `calendar` in the admin panel, confirm
      authenticated curl of `/index.php/apps/calendar/` contains NO `apps/nldesign/css` link
      while `/index.php/apps/files/` still does; re-enable afterwards.
      (deferred to post-merge live verification)
- [ ] 5.6 API-surface negative check: `curl -su admin:admin http://localhost:8080/ocs/v2.php/cloud/capabilities -H "OCS-APIRequest: true"`
      succeeds and server logs show no nldesign listener errors (`docker logs`/nextcloud.log
      grep `nldesign`).
      (deferred to post-merge live verification)
- [ ] 5.7 Browser check on 8080 (browser-1): visually confirm login page, Files, and the public
      share page render themed (header color, fonts, logo/lint) identically to before the
      change; screenshot each.
      (deferred to post-merge live verification)
- [ ] 5.8 Context gating smoke test: `occ config:app:set nldesign themed_contexts --value='["user","login","guest","error"]'`,
      reload the public share page → unthemed; delete the key
      (`occ config:app:delete nldesign themed_contexts`) → themed again.
      (deferred to post-merge live verification)
