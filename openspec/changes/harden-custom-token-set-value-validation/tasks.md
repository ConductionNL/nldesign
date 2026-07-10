## 1. Harden the value gate

- [ ] 1.1 In `lib/Service/CustomTokenSetValidator.php::isForbiddenValue()`, add `;`, `/*`, and `*/`
      to the rejected-substring checks (alongside the existing `@import`, `expression(`,
      `javascript:`, `<` checks), matching `CustomOverridesService::buildDeclarationLines()`'s
      `preg_match('/[{};]|\/\*|\*\//', $value)` guard.
- [ ] 1.2 In `lib/Service/CustomTokenSetValidator.php::serialize()`, keep the existing `trim($value)`
      but confirm (via the new tests in task 2) that a rejected value can never reach this method —
      `serialize()` is defense-in-depth, not the primary gate.

## 2. Tests

- [ ] 2.1 Add a test to `tests/Unit/Service/CustomTokenSetValidatorTest.php` asserting
      `isForbiddenValue('red; background: url(https://evil.example/x.png)')` returns `true`.
- [ ] 2.2 Add a test asserting a value containing a bare CSS comment marker
      (`isForbiddenValue('red /* } */')`) returns `true`.
- [ ] 2.3 Add a `validateDeclarations()`-level test (not just `isForbiddenValue()`) proving a
      declaration set containing the semicolon-smuggling payload is rejected end-to-end with a 422
      `lastError`, not silently split into accepted/skipped.
- [ ] 2.4 Confirm the JSON upload path (`CustomTokenSetController::mapFromJson()`) is covered too —
      it reuses `isForbiddenValue()` per token (`lib/Controller/CustomTokenSetController.php:222`),
      so the same fixture (crafted as a W3C Design Tokens JSON value) must be rejected there as
      well. Add or extend a controller-level test if one exists for `mapFromJson()`.

## 3. Regression check on already-stored sets (follow-up note, not blocking)

- [ ] 3.1 Document in `CHANGELOG.md` that this hardening only affects new uploads; any
      already-stored `custom-*.css` file uploaded before this fix is not retroactively
      re-validated. File a follow-up issue if a migration/re-scan is later deemed necessary — out
      of scope for this change.

## 4. Verify

- [ ] 4.1 Run `composer test:unit` (or the project's PHPUnit target) and confirm the new tests pass
      and no existing `CustomTokenSetValidatorTest` / `CustomTokenSetControllerTest` cases regress.
- [ ] 4.2 Manually upload a CSS token set containing the semicolon payload via the admin panel and
      confirm the upload is rejected with a 422 and a user-facing error, not silently accepted.
