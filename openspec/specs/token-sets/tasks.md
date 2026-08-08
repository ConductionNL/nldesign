# Profile Catalogue Tasks

- [x] Make `token-sets.json` the authoritative package inventory.
- [x] Ship 40 matching profile snapshots and classify 8 as ready.
- [x] Exclude source-only entries from runtime selection.
- [x] Require `nextcloud-core-v1` and complete semantic inputs for ready entries.
- [x] Validate exact manifest/stylesheet correspondence during the build.
- [x] Bound manifest, CSS, and asset size, type, path, and symlink handling.
- [x] Cache app path and manifest parsing per request.
- [x] Normalize metadata and allowlist manual Theming hints.
- [x] Store canonical profile state through app-scoped `IAppConfig`.
- [x] Require expected revisions, an exclusive lock, and a public app-config
  cache refresh before the locked read for publish and rollback.
- [x] Keep compatibility mirrors and bounded history non-load-bearing.
- [x] Read the legacy mirror only when canonical state is absent and fail a
  corrupt or incomplete canonical record safely to native Nextcloud without a
  partial rollback target.
- [x] Render through template events with an explicit three-layer plan.
- [x] Cover discovery, source-only exclusion, hints, traversal, symlinks,
  publication, lock and cache-refresh failure, stale cached reads, stale writes,
  rollback, history, and malformed state with unit tests.
- [ ] Add signed source descriptors and provenance evidence in a later compiler
  slice.
- [ ] Promote another source-only entry only with semantic, rights,
  accessibility, and packaged-browser evidence.
