## 1. GroupThemingService

- [ ] 1.1 Create `lib/Service/GroupThemingService.php`: read/write the `group_token_sets`
      appconfig JSON array (ordered `{group, tokenSet}` entries) and the
      `group_token_sets_generation` counter; malformed stored JSON reads as empty mapping.
- [ ] 1.2 Implement save-time validation: group exists (`IGroupManager::groupExists`), token
      set available (`TokenSetService` shipped discovery + `custom_token_sets` manifest), max
      one entry per group; throw a typed validation exception carrying the offending entry +
      reason; bump the generation only on successful writes.
- [ ] 1.3 Implement `resolveTokenSetForRequest()`: preview hook first (no-op stub until change
      `theme-preview-workflow` lands — leave a clearly-marked integration point, NOT dead
      code: the method delegates to an overridable `getActivePreviewSet(): ?string` returning
      null), then no-session branch → instance default, then cached group resolution, then
      instance default. Entries whose set no longer exists are skipped. Whole method wrapped
      fail-open to the default set (mirror `Application::isThemingDisabled()` catch-all).
- [ ] 1.4 Implement caching per design.md: `ICacheFactory::createDistributed('nldesign-group-theming')`,
      key `resolve:{userId}:{generation}`, TTL 3600; empty-mapping fast path short-circuits
      before cache and group access.
- [ ] 1.5 SPDX docblocks + `@spec` tags on every method (hydra gates).

## 2. Boot wiring

- [ ] 2.1 `lib/AppInfo/Application.php`: replace the direct
      `$config->getAppValue(..., 'token_set', 'nextcloud')` read in `injectThemeCSS()` with
      `GroupThemingService::resolveTokenSetForRequest()`; keep the per-app exclusion guard
      FIRST and all layer ordering unchanged.
- [ ] 2.2 Verify (unit test, not code) that occ/cron/CLI contexts resolve to the default set
      via the no-session branch without touching `IGroupManager`.

## 3. Endpoints

- [ ] 3.1 `lib/Controller/SettingsController.php`: add `getGroupTheming()` (mapping in order +
      available groups `{id, displayName}` + available token sets) and `setGroupTheming()`
      (replace-all with validation; 422 with entry + reason on failure), both
      `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)`, no
      `#[PublicPage]`/`#[NoAdminRequired]`/`#[NoCSRFRequired]`.
- [ ] 3.2 `appinfo/routes.php`: register
      `['name' => 'settings#getGroupTheming', 'url' => '/settings/group-theming', 'verb' => 'GET']`
      and the POST counterpart.

## 4. Admin UI (vanilla)

- [ ] 4.1 `templates/settings/admin.php`: add the "Group theming" section markup —
      `#nldesign-group-theming-list`, add-mapping button, save button, feedback element,
      localized hint (priority ordering + core-theming-stays-instance-global limitation),
      empty-state text. All strings via `$l->t()` (English keys).
- [ ] 4.2 `js/admin.js`: fetch/render/save flow mirroring the existing
      `#nldesign-app-theming-list` pattern — row rendering with group select, token-set
      select, keyboard-operable move-up/move-down (focus preserved after move), remove;
      POST full ordered mapping; render 422 per-entry errors in the feedback element.
- [ ] 4.3 `css/admin.css`: row layout styles using existing admin panel conventions (CSS
      variables only, no hardcoded colors).

## 5. Unit tests

- [ ] 5.1 `tests/unit/Service/GroupThemingServiceTest.php` — precedence matrix: single-group
      match; multi-group user → earliest entry; dead-set entry skipped then next entry wins;
      dead-set-only → default; unmapped user → default; empty mapping → default with ZERO
      group-manager calls; no session → default with zero group-manager calls; group backend
      throws → default, no exception escapes.
- [ ] 5.2 Caching tests with a cache spy: second resolve hits cache (no group lookup);
      generation bump invalidates (new key, fresh lookup); TTL bound set.
- [ ] 5.3 Validation tests: unknown group 422-shape, unknown set, duplicate group, atomicity
      (nothing persisted on failure), generation bumps only on success.
- [ ] 5.4 Controller tests for both endpoints (payload shapes, 422 propagation).
- [ ] 5.5 Boot-order test: excluded app suppresses injection for a group-mapped user
      (per-app-theming orthogonality); empty-mapping resolution equals the `token_set` app
      value (css-architecture regression scenario).
- [ ] 5.6 vitest: group-theming section render, add/reorder/remove, ordered POST body,
      422 feedback rendering, empty state.

## 6. Verify

- [ ] 6.1 PHPUnit green in the nextcloud:34 container; vitest green; `composer check:strict`
      passes.
- [ ] 6.2 Live on 8080: create two test users in two groups (`grp-ams`, `grp-utr` — NC
      password ≥ 10 chars), map `grp-ams → amsterdam` and `grp-utr → utrecht` via the new
      admin section; verify the save round-trips after reload.
- [ ] 6.3 Live on 8080 (browser): log in as each user in separate sessions and confirm user A's
      pages load `tokens/amsterdam` CSS and user B's load `tokens/utrecht` (assert via the
      page's stylesheet links / computed `--nldesign-color-primary`); confirm the admin
      (unmapped) still gets the instance default set and the LOGIN page shows the default set.
- [ ] 6.4 Live on 8080: change the mapping (swap the two sets), reload both user sessions, and
      confirm both flip on the next request (generation invalidation).
- [ ] 6.5 Live on 8080: add an app to the per-app exclusion list and confirm the group-mapped
      user gets NO nldesign CSS on that app's pages but the mapped set elsewhere.
- [ ] 6.6 Clean up: remove test users/groups/mappings so the shared instance is left in its
      prior state.
