# Design — upstream-token-freshness

Architecturally non-trivial on three axes: first background job in the app, first outbound
network egress, and a provenance contract spanning the CI sync workflow and the deployed app.

## 1. Egress policy: default OFF (opt-in)

Decision: the freshness check ships **disabled**; an admin must enable it via a toggle whose
label names the contacted host and cadence ("checks api.github.com once a day").

Considered: default ON with opt-out — better notice coverage (the pain is precisely that
admins don't know updates exist), and the request is metadata-only. Rejected because the
app's audience is sovereignty-sensitive government IT: an app whose pitch is "self-hosted,
CSP-clean, no third-party requests" silently phoning a US-hosted API on install is a
credibility injury larger than the feature's value, and air-gapped/egress-filtered
deployments (a normal mode for this audience) would accrue daily failure noise. Opt-in costs
one checkbox and keeps the AVG/privacy-by-default story clean. The admin panel makes the
feature discoverable at the exact place admins manage token sets, mitigating the
discoverability loss.

Consequence: when disabled (default), `run()` returns before any network or state access —
zero egress, zero log noise, near-zero cron cost.

## 2. Freshness protocol: pinned URL, conditional GET, ≤2 requests

Upstream (`nl-design-system/themes`) publishes no single per-theme version manifest, so
per-set version polling would need N requests. Instead:

1. **Steady-state check (1 request):** conditional GET to a **pinned manifest URL** —
   default `https://api.github.com/repos/nl-design-system/themes/commits/<pinned-branch>`
   with `Accept: application/vnd.github.sha` (response body = branch head SHA). The ETag
   from the previous run is sent as `If-None-Match` and cached in IAppConfig; the normal
   daily outcome is `304 Not Modified` → done. The URL is a single overridable app-config
   value (`upstream_manifest_url`) so forks/mirrors/proxies (e.g. an internal GitHub
   mirror on egress-filtered networks) can be pointed at without code changes.
2. **Attribution (at most 1 more request, only on change):** if the fetched head SHA
   differs from the `upstreamRef` recorded in installed `token-sets.json` entries, one
   compare-API GET (`.../compare/{oldestInstalledRef}...{headSha}`) lists changed files;
   paths matching `proprietary/<org>/` map to token-set ids. Using the *oldest* installed
   ref makes the changed-file list a superset covering every installed set in one request.
   Attribution is best-effort: any failure (rate limit, truncated compare, unparseable
   body) degrades to a single generic "upstream has updates" notice keyed by head SHA.

Hard invariants: ≤2 outbound requests per run, 10 s timeout each via
`IClientService` (respecting instance proxy config), no retry within a run (next daily run
is the retry), no auth token (anonymous rate limits are ample for 1-2 req/day).

`upstreamVersion` (human-facing semver, e.g. "vY" in the notice) comes from the sync
workflow recording each theme package's version at generation time — the job itself never
parses upstream package files; when a set has no `upstreamVersion`, the notice falls back
to the short SHA.

## 3. Failure containment

The job wraps its entire body in a catch-all: network errors, non-2xx/304 statuses,
malformed bodies, and IAppConfig write failures are logged at `info` (first occurrence) /
`debug` (repeats) and abort the run leaving prior notice state untouched. Nothing in the
theming render path reads anything this job writes except the admin settings panel, so a
wedged or failing job cannot affect page rendering, login, or mail. This is specced as a
requirement (silent degradation), not just an implementation note.

## 4. Provenance fields

`token-sets.json` entries gain optional `upstreamVersion` + `upstreamRef`, written by
`scripts/generate-tokens.mjs` during the CI sync (MODIFIED `token-sync-workflow`). Sets
without the fields (hand-made, `nextcloud`, `summer-breeze`, custom uploads) are simply
excluded from freshness comparison. The comparison baseline is `upstreamRef`; per-set
semver display uses `upstreamVersion`. Custom sets (`custom-*`) are always excluded.

## 5. Notice + dismissal semantics

Notices live in IAppConfig `upstream_updates` (JSON: setId → {installedRef,
installedVersion?, headSha, upstreamVersion?, detectedAt}); dismissals in
`upstream_freshness_dismissed` (setId → dismissed headSha/version). A notice renders only
when its detected head SHA (or version) differs from the dismissed one — so dismissing vY
suppresses vY forever but vZ re-surfaces. Dismissal is per-set; the generic
(unattributed) notice dismisses by head SHA. No Nextcloud notification-app integration in
this change (admins visit the settings panel anyway to act); a follow-up could add
`OCP\Notification` if demanded.

## 6. Explicit non-goals

No auto-apply, no auto-download of token files, no writes to `css/tokens/` or the active
token set from the job, no instance-identifying telemetry in the outbound request. The
human path for actually updating tokens remains: app release via the CI sync workflow →
instance upgrade → admin reviews and applies.
