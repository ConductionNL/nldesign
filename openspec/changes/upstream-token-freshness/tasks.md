## 1. Provenance fields (manifest + sync script)

- [ ] 1.1 Update `scripts/generate-tokens.mjs`: record `upstreamVersion` (the upstream
      theme package's version at generation time) and `upstreamRef` (the
      `nl-design-system/themes` commit SHA being generated from, passed in or read from
      the cloned checkout) into each generated entry in `token-sets.json`, while still
      preserving manually added metadata (existing preservation contract).
- [ ] 1.2 Update `.github/workflows/sync-tokens.yml` to pass the checked-out themes-repo
      commit SHA to the script.
- [ ] 1.3 Verify `TokenSetService::getAvailableTokenSets()` passes the new optional fields
      through untouched (no code change expected — assert in a unit test that unknown
      manifest keys survive the merge, or add pass-through if they are stripped).

## 2. Service

- [ ] 2.1 Create `lib/Service/UpstreamFreshnessService.php` (SPDX docblocks) owning
      IAppConfig state: `upstream_freshness_enabled` (default `'no'`),
      `upstream_manifest_url` (default: GitHub commits-API URL for the pinned branch of
      `nl-design-system/themes`), `upstream_etag`, `upstream_head_sha`,
      `upstream_checked_at`, `upstream_updates` (JSON), `upstream_freshness_dismissed`
      (JSON). Public API: `isEnabled()`, `setEnabled(bool)`, `getStatus(): array`
      (enabled, lastChecked, notices after dismissal filtering), `dismiss(string $setId,
      string $versionOrSha): void`, `runCheck(): void`.
- [ ] 2.2 Implement `runCheck()`: return immediately when disabled; conditional GET
      (`If-None-Match` with stored ETag) via `IClientService` with 10 s timeout; on 304 →
      update `upstream_checked_at` only; on 200 → store new ETag + head SHA, compare
      against installed sets' `upstreamRef` (skip sets without the field and all
      `custom-*` sets); when differing, ONE compare-API request
      (`compare/{oldestInstalledRef}...{head}`) mapping changed `proprietary/<org>/` paths
      to installed set ids → write per-set notices `{installedRef, installedVersion,
      headSha, upstreamVersion, detectedAt}`; on attribution failure write one generic
      notice keyed by head SHA. Hard cap two requests per run; entire body in try/catch —
      failures log at info level and leave prior notice state untouched; nothing ever
      thrown to the caller.
- [ ] 2.3 Dismissal semantics: `getStatus()` filters out notices whose head SHA (or
      upstream version, when present) equals the dismissed marker for that set; a newer
      detection re-surfaces.

## 3. Background job

- [ ] 3.1 Create `lib/BackgroundJob/UpstreamFreshnessJob.php` extending
      `OCP\BackgroundJob\TimedJob`: constructor sets `setInterval(24 * 60 * 60)` and
      `setTimeSensitivity(self::TIME_INSENSITIVE)`; `run($argument)` delegates to
      `UpstreamFreshnessService::runCheck()` inside its own try/catch (belt and braces —
      hydra stub-scan gate: the body is a real delegation, never empty).
- [ ] 3.2 Register the job in `appinfo/info.xml`:
      `<background-jobs><job>OCA\NLDesign\BackgroundJob\UpstreamFreshnessJob</job></background-jobs>`.
      Bump `<version>` (cache-buster convention).

## 4. Settings endpoints + admin UI (vanilla JS — no Vue)

- [ ] 4.1 `SettingsController`: add `getUpstreamFreshness()` (GET → `getStatus()`),
      `setUpstreamFreshness()` (POST param `enabled`), `dismissUpstreamNotice()` (POST
      params `setId`, `version`). All `#[AuthorizedAdminSetting(Admin::class)]`,
      CSRF-protected (same posture as sibling settings routes).
- [ ] 4.2 Routes in `appinfo/routes.php`:
      `['name' => 'settings#getUpstreamFreshness', 'url' => '/settings/upstream-freshness', 'verb' => 'GET']`,
      `['name' => 'settings#setUpstreamFreshness', 'url' => '/settings/upstream-freshness', 'verb' => 'POST']`,
      `['name' => 'settings#dismissUpstreamNotice', 'url' => '/settings/upstream-freshness/dismiss', 'verb' => 'POST']`.
- [ ] 4.3 `lib/Settings/Admin.php` + `templates/settings/admin.php`: "Upstream token
      updates" block — opt-in toggle with the egress-disclosure label (ENGLISH i18n key,
      e.g. `t('nldesign', 'Check daily for upstream token updates (contacts api.github.com)')`),
      last-checked timestamp, and the notice list: "Token set {name} has upstream update
      {version} — review & apply" with a Dismiss button per notice and NO apply button
      (informational only; link the README sync docs for the update path).
- [ ] 4.4 `js/admin.js`: wire toggle + dismiss via the endpoints; render notices from the
      GET payload; `l10n/` English keys + nl translations.

## 5. Tests

- [ ] 5.1 `tests/unit/Service/UpstreamFreshnessServiceTest.php` (mock `IClientService` /
      `IAppConfig`):
      (a) disabled ⇒ `runCheck()` performs zero HTTP calls;
      (b) 304 ⇒ only `upstream_checked_at` updated, no notices;
      (c) 200 with SHA equal to all installed refs ⇒ no notices;
      (d) 200 with new SHA + successful compare listing `proprietary/utrecht/...` ⇒
      notice for `utrecht` with its `upstreamVersion`, none for untouched sets;
      (e) compare request fails ⇒ single generic notice, no exception;
      (f) freshness request throws (timeout/DNS) ⇒ no exception escapes, prior notices
      preserved, failure logged;
      (g) sets without `upstreamRef` and `custom-*` sets never produce notices;
      (h) dismissal of vY hides the vY notice; a later detection with a newer SHA/version
      re-surfaces it;
      (i) never more than two HTTP requests per run (assert on the mock).
- [ ] 5.2 `tests/unit/BackgroundJob/UpstreamFreshnessJobTest.php` — job declares a 24 h
      interval and TIME_INSENSITIVE; `run()` delegates to the service; a service throw
      does not escape `run()`.
- [ ] 5.3 Controller test: three routes carry `AuthorizedAdminSetting`; toggle POST
      persists; dismiss POST round-trips into filtered `getStatus()` output.

## 6. Verify

- [ ] 6.1 PHPUnit in the nextcloud:34 container + `composer check:strict` — green
      (SPDX, route-auth, stub-scan on the job body, spec-coverage `@spec` tags on all new
      methods).
- [ ] 6.2 Live on 8080: enable the toggle in the admin panel; seed a stale
      `upstreamRef` into `token-sets.json` for one set (e.g. an older real SHA of
      nl-design-system/themes); force the job via
      `occ background-job:list | grep UpstreamFreshness` + `occ background-job:execute
      <id>`; confirm the admin panel shows the "review & apply" notice for that set,
      Dismiss hides it, and re-executing the job does not resurrect the dismissed notice.
- [ ] 6.3 Live offline degradation: point `upstream_manifest_url` app-config at an
      unreachable host, execute the job, and confirm: no exception in
      `data/nextcloud.log` above info level, theming still renders, admin panel still
      loads, prior notices unchanged. Reset the URL.
- [ ] 6.4 Live default-off proof: on a fresh config (delete the enabled flag), execute the
      job and confirm zero outbound requests (assert no entry in the mock/proxy log or
      via `tcpdump`-free proxy: set `upstream_manifest_url` to a local netcat listener
      and confirm it receives nothing while disabled).
- [ ] 6.5 Confirm the no-auto-apply invariant live: after a notice is shown, verify
      `css/tokens/` mtimes and the active `token_set` config are unchanged.
