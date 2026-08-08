# Administrator Settings Tasks

- [x] Implement the panel as IDelegatedSettings.
- [x] Use app-scoped IAppConfig and a narrow delegated config allowlist.
- [x] Render only ready profiles from the manifest-backed inventory.
- [x] Expose revision, one-step rollback, and bounded operation history.
- [x] Protect every controller action with AuthorizedAdminSetting.
- [x] Validate profiles and require expected revisions for writes.
- [x] Keep native Nextcloud as an explicit revisioned and rollback-capable state.
- [x] Record a bounded generic operation source without retaining a user id.
- [x] Use same-origin, CSRF-protected JSON requests.
- [x] Reject non-success responses and malformed JSON in the browser.
- [x] Validate profile-state response shapes and ignore superseded plan/history
  reads.
- [x] Disable controls while busy and restore failed optimistic UI changes.
- [x] Handle profile conflicts by reloading canonical state.
- [x] Render dynamic values with textContent.
- [x] Support an explicit empty-catalogue state and aria-live status.
- [x] Expose only a manual Nextcloud Theming plan.
- [x] Remove non-functional token editor, import/export, and apply-dialog UI.
- [x] Add authorization-attribute and application-service unit tests.
- [ ] Add browser integration tests against packaged supported Nextcloud
  versions.
