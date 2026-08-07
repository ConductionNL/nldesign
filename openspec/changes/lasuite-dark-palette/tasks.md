# Tasks — lasuite dark palette

## 1. Source the palette

- [ ] Extract La Suite's shipped dark Cunningham values for the `--lasuite-color-*`
      ramp, the same way the light ramp was sourced
      - do not invert the light ramp; upstream ships a dark theme and it is the reference
- [ ] Record which tokens have no upstream dark counterpart and decide each explicitly

## 2. Emit

- [ ] Extend the generator so the lasuite dark variant emits `--lasuite-color-*`
      alongside the existing `--nldesign-*` values
- [ ] Emit under the EXISTING dark scope selectors so injection order and scoping
      keep satisfying the current dark-mode spec
- [ ] Verify no REQ-CSS-007 reserved variable is written

## 3. Translucent values

- [ ] Re-express the active-row wash so it is legible on both grounds
- [ ] Audit `element-overrides.css` for any other translucent literal and give each
      a dark counterpart

## 4. Verification

- [ ] Extend `tests/e2e/spec-coverage/dark-mode.spec.ts` to render in dark mode and
      assert computed shell values (header, canvas, card, active row, search)
- [ ] Add the WCAG contrast loop the dark-mode spec already requires for generated variants
- [ ] Add a guard asserting every `--lasuite-color-*` token READ by element-overrides
      has a dark value — the check that would have caught this

## 5. Evidence

- [ ] Capture the four surfaces in dark mode before and after
- [ ] Update `openspec/specs/dark-mode/spec.md` with the merged requirements

## Acceptance criteria

- Header, canvas, card, active row and search field all resolve to dark values in dark mode
- No reserved REQ-CSS-007 variable is written by the dark variant
- The selected navigation row stays identifiable in dark mode
- `composer check:strict`, stylelint and the unit suite stay green
