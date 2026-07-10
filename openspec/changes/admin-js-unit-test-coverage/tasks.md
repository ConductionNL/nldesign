## 1. Extract testable logic from `js/admin.js`

- [ ] 1.1 Move `escapeHtml()` (`js/admin.js:376-379`) into `js/lib/` (or export it) so it can be
      unit tested directly, without a DOM-dependent `admin.js` bootstrap.
- [ ] 1.2 Extract the app-search filter predicate used at `js/admin.js:1122-1127`
      (`opt.hidden = q !== '' && opt.getAttribute('data-app-name').indexOf(q) === -1`) into a pure
      function `matchesAppSearch(appName, query)`.
- [ ] 1.3 Extract `updateTriggerLabel()`'s counting logic (`js/admin.js:1093-1098`) into a pure
      function that takes an array of `{checked}`-like objects and returns
      `{themed, total}`, keeping the DOM-touching wrapper in `admin.js` calling the pure function.
- [ ] 1.4 Extract the disabled-apps payload builder in `saveAppTheming()`
      (`js/admin.js:1154-1159`) into a pure function `buildDisabledAppsPayload(checkboxStates)`.
- [ ] 1.5 Confirm each extraction is behavior-preserving: `admin.js`'s existing e2e specs
      (`tests/e2e/spec-coverage/app-theming.spec.ts`, `token-set-apply-dialog.spec.ts`, etc.)
      still pass unmodified after the refactor.

## 2. Add unit tests

- [ ] 2.1 Create `tests/vitest/admin.spec.js`.
- [ ] 2.2 Test `escapeHtml()` against `<script>`, `"`, `'`, `&`, and plain-text inputs — assert
      the exact escaped output (mirroring the exact-output-assertion style of
      `tests/vitest/tokenTransforms.spec.js`).
- [ ] 2.3 Test `matchesAppSearch()` for: empty query (always matches), case-insensitive substring
      match, and no-match cases.
- [ ] 2.4 Test the trigger-label counting function for: zero apps, all themed, all un-themed, and
      a mixed set.
- [ ] 2.5 Test `buildDisabledAppsPayload()` for: all checked (empty disabled list), all unchecked
      (full disabled list), and a mixed set.

## 3. Raise the coverage floor

- [ ] 3.1 Run `npm run test:coverage` and record the new `vitest` percentage from
      `coverage-vitest/coverage-summary.json`.
- [ ] 3.2 Update `tests/.coverage-baseline.json`'s `vitest` value to the new (higher) percentage,
      keeping `tolerance` unchanged, per the existing ratchet convention (forward-only).
- [ ] 3.3 Run `npm run test:coverage-ratchet` and confirm it passes against the new floor.

## 4. Fix or correct the lint gate

- [ ] 4.1 Decide: add real linting (`eslint` + a minimal flat config covering `js/**/*.js`,
      matching the fleet's other apps' eslint setup) with `package.json`'s `lint` script actually
      invoking it, OR correct the `lint` script's message so it no longer claims "No JavaScript to
      lint" while 1391 lines of JS ship.
- [ ] 4.2 If adding eslint: run it against `js/admin.js` and `js/lib/**`, fix any findings (or
      file follow-up issues per the fleet's "always file issues for deferred work" rule for
      anything non-trivial), and confirm `.github/workflows/code-quality.yml`'s
      `enable-eslint: true` now has a real config/dependency to act on.

## 5. Verify

- [ ] 5.1 Run `npm run test:unit` (vitest) and confirm all new and existing tests pass.
- [ ] 5.2 Run the full e2e spec-coverage suite and confirm no regression from the extraction
      refactor.
- [ ] 5.3 Run `npm run test:coverage-ratchet` and confirm it passes at the new floor.
