# Admin JS Test Coverage — Delta

**Spec refs**: none pre-existing (new capability) — cross-cuts `per-app-theming`, `token-sets`
**Standards**: hydra ADR-008 (testing — distinguish unit vs. e2e reality)

## ADDED Requirements

### Requirement: Admin Panel Logic Has Direct Unit Coverage

The pure logic units within `js/admin.js` MUST have dedicated Vitest unit tests that call them directly, independent of e2e/Playwright coverage. This applies to HTML escaping, the disabled-apps payload sent in the app-theming save POST, the app-search filter predicate, and the themed/total counting logic.

#### Scenario: escapeHtml neutralizes script-injection input

- GIVEN the string `<script>alert(1)</script>`
- WHEN `escapeHtml()` is called with that string
- THEN the returned string MUST NOT contain an unescaped `<script>` tag
- AND the returned string, when inserted into `innerHTML`, MUST render as inert text

#### Scenario: Disabled-apps payload reflects unchecked checkboxes only

- GIVEN a set of app checkbox states where some are checked (themed) and some are unchecked
  (excluded)
- WHEN the disabled-apps payload builder is called with that checkbox state set
- THEN the returned list MUST contain exactly the app ids whose checkbox is unchecked
- AND MUST NOT contain any app id whose checkbox is checked

#### Scenario: Vitest coverage floor reflects real admin.js coverage, not just the pure-helper module

- GIVEN `tests/.coverage-baseline.json`'s `vitest` value
- WHEN `npm run test:coverage` is run after this change lands
- THEN the measured `vitest` coverage percentage MUST be higher than the pre-change baseline of
  8.18%
- AND `tests/.coverage-baseline.json` MUST be updated to the new value so the ratchet cannot
  silently fall back to the old, lower floor
