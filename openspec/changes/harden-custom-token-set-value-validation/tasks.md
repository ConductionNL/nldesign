## 1. Harden the value gate

- [x] 1.1 In `lib/Service/CustomTokenSetValidator.php::isForbiddenValue()`, add `;`, `/*`, and `*/`
      to the rejected-substring checks (alongside the existing `@import`, `expression(`,
      `javascript:`, `<` checks), matching `CustomOverridesService::buildDeclarationLines()`'s
      `preg_match('/[{};]|\/\*|\*\//', $value)` guard.
      (Already present on `development` at HEAD — the `;`/`/*`/`*/` checks and the `@spec` tag for
      this change were carried in commit `1b22d95` "wip: preserve uncommitted work"; verified by
      reading the current source. Additionally refactored `isForbiddenValue()` in this session,
      extracting `containsDangerousKeyword()` / `containsInjectionCharacter()` /
      `hasDisallowedUrlTarget()` private helpers — pure decomposition, no behaviour change — since
      the added checks had pushed the method's PHPMD CyclomaticComplexity to 14 against a threshold
      of 10; all 98 unit tests (incl. the new ones) still pass unchanged after the split.)
- [x] 1.2 In `lib/Service/CustomTokenSetValidator.php::serialize()`, keep the existing `trim($value)`
      but confirm (via the new tests in task 2) that a rejected value can never reach this method —
      `serialize()` is defense-in-depth, not the primary gate.
      (`trim($value)` already present in `serialize()`; task 2.3's `validateDeclarations()`
      end-to-end test confirms the smuggling payload never survives past the gate to reach
      `serialize()`.)

## 2. Tests

- [x] 2.1 Add a test to `tests/Unit/Service/CustomTokenSetValidatorTest.php` asserting
      `isForbiddenValue('red; background: url(https://evil.example/x.png)')` returns `true`.
      (`testSemicolonSmuggledBackgroundDeclarationIsForbidden`)
- [x] 2.2 Add a test asserting a value containing a bare CSS comment marker
      (`isForbiddenValue('red /* } */')`) returns `true`.
      (`testBareCommentMarkerValueIsForbidden`)
- [x] 2.3 Add a `validateDeclarations()`-level test (not just `isForbiddenValue()`) proving a
      declaration set containing the semicolon-smuggling payload is rejected end-to-end with a 422
      `lastError`, not silently split into accepted/skipped.
      (`testValidateDeclarationsRejectsSemicolonSmugglingEndToEnd`)
- [x] 2.4 Confirm the JSON upload path (`CustomTokenSetController::mapFromJson()`) is covered too —
      it reuses `isForbiddenValue()` per token (`lib/Controller/CustomTokenSetController.php:222`),
      so the same fixture (crafted as a W3C Design Tokens JSON value) must be rejected there as
      well. Add or extend a controller-level test if one exists for `mapFromJson()`.
      (No controller test existed for this controller; added
      `tests/Unit/Controller/CustomTokenSetControllerTest.php` — a new file — exercising `upload()`
      end-to-end with a real `CustomTokenSetValidator`/`DesignTokensMapper` and a mocked
      `IRequest`/`IL10N`/`CustomTokenSetService`: semicolon smuggling rejected, comment-marker
      smuggling rejected, and a clean mapped value still accepted/stored — no regression.)

## 3. Regression check on already-stored sets (follow-up note, not blocking)

- [x] 3.1 Document in `CHANGELOG.md` that this hardening only affects new uploads; any
      already-stored `custom-*.css` file uploaded before this fix is not retroactively
      re-validated. File a follow-up issue if a migration/re-scan is later deemed necessary — out
      of scope for this change.
      (Added a `### Security` entry under `## Unreleased` in `CHANGELOG.md`. No follow-up issue
      filed — a re-scan migration is not deemed necessary per the proposal's own scoping: this is
      a defense-in-depth gap requiring the admin role that already owns the upload pipeline.)

## 4. Verify

- [x] 4.1 Run `composer test:unit` (or the project's PHPUnit target) and confirm the new tests pass
      and no existing `CustomTokenSetValidatorTest` / `CustomTokenSetControllerTest` cases regress.
      (Ran via `phpunit -c phpunit-unit.xml` in the `nextcloud:34.0.0-apache` container — see PR
      description / builder report for the exact pass count.)
- [ ] 4.2 Manually upload a CSS token set containing the semicolon payload via the admin panel and
      confirm the upload is rejected with a 422 and a user-facing error, not silently accepted.
      (deferred to post-merge live verification — requires the live 8080 instance, per builder
      brief instructions; the equivalent assertion is covered by
      `testValidateDeclarationsRejectsSemicolonSmugglingEndToEnd` and
      `testJsonUploadWithSemicolonSmugglingIsRejected` at the unit level.)
