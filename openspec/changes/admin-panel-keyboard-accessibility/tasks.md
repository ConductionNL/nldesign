## 1. App-theming dropdown (`js/admin.js:1063-1144`)

- [ ] 1.1 Add `aria-haspopup="listbox"` to `trigger` (`js/admin.js:1073-1077`) and toggle
      `aria-expanded="true"/"false"` on `trigger` alongside the existing `dropdown.classList.toggle('open')`
      at `js/admin.js:1129-1133`.
- [ ] 1.2 Add `role="listbox"` to `optList` (`js/admin.js:1090-1091`) or `role="dialog"` +
      `aria-label` to `panel` (`js/admin.js:1079-1080`) — pick whichever role matches the actual
      interaction pattern (a filterable multi-select list reads as a listbox).
- [ ] 1.3 Add a `keydown` listener on `dropdown` that: closes the panel and returns focus to
      `trigger` on `Escape`; does not trap Tab (the panel is not a modal — Tab should be allowed to
      leave it, matching current click-outside-to-close behavior).
- [ ] 1.4 Verify `updateTriggerLabel()` (`js/admin.js:1093-1098`) continues to update
      `triggerLabel.textContent` unchanged — no behavior change to the counting logic, only to
      opened/closed semantics.

## 2. Theming-sync dialog (`showThemingSyncDialog()`, `js/admin.js:283-373`)

- [ ] 2.1 Add `role="dialog"` and `aria-modal="true"` to `#nldesign-theming-dialog-overlay`'s
      inner `.nldesign-dialog` element (built at `js/admin.js:284-311`).
- [ ] 2.2 After `document.body.insertAdjacentHTML('beforeend', dialogHtml)` (`js/admin.js:313`),
      capture `document.activeElement` as the element to restore focus to, then move focus to the
      dialog's heading or first focusable control (e.g. the cancel button).
- [ ] 2.3 Add a focus-trap `keydown` listener scoped to the dialog: `Tab`/`Shift+Tab` cycle only
      through the dialog's focusable elements (cancel button, confirm button, and any focusable
      content in `rows`).
- [ ] 2.4 Add `Escape`-closes-dialog behavior equivalent to the existing cancel click handler
      (`js/admin.js:318-320`), and restore focus to the captured trigger element on any close path
      (cancel, overlay click, confirm, Escape).

## 3. Apply-token-set dialog (`js/admin.js:~840-940`)

- [ ] 3.1 Add `role="dialog"` and `aria-modal="true"` to `#nldesign-apply-dialog-overlay`'s inner
      `.nldesign-dialog` element (built at `js/admin.js:866-887`).
- [ ] 3.2 Apply the same focus-in-on-open / focus-trap / focus-restore-on-close / Escape-closes
      pattern as task 2, reusing `cancelDialog()` (`js/admin.js:917-925`) as the Escape handler
      target.
- [ ] 3.3 Confirm the "Select all" / "Deselect all" buttons (`js/admin.js:908-915`) and the
      per-token checkboxes remain reachable via Tab within the trap.

## 4. Tests

- [ ] 4.1 Extend `tests/e2e/spec-coverage/app-theming.spec.ts`'s
      `checkboxes-are-accessible` test (or add a new test) to: focus the trigger via keyboard,
      press Enter/Space to open, assert `aria-expanded="true"`, press `Escape`, assert the panel
      closed and focus returned to the trigger.
- [ ] 4.2 Add e2e coverage (new spec file or extend an existing theming-sync/token-set-apply-dialog
      spec-coverage spec) asserting: opening the theming-sync dialog moves focus into it, `Escape`
      closes it and restores focus to the trigger, and Tab does not leave the dialog while open.
- [ ] 4.3 Add the equivalent assertions for the apply-token-set dialog.

## 5. Verify

- [ ] 5.1 Run the new/extended Playwright specs and confirm they pass against a running dev
      instance.
- [ ] 5.2 Manually keyboard-navigate the admin settings page (Tab only, no mouse) through: opening
      the app-theming dropdown, closing it with Escape, opening a theming-sync/apply dialog, and
      closing it with Escape — confirm no keyboard trap and no lost focus.
