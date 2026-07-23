---
kind: code
---

## Why

An admin who activates the `utrecht` token set today has no way to know whether Utrecht has
since published new tokens upstream. NLDS follows explicit semver conventions and its own
documentation warns that even a *patch* release upstream can break downstream consumers — yet
nldesign's only sync mechanism is the nightly GitHub Actions workflow (`token-sync-workflow`
spec), which is CI-side: it opens a PR on the nldesign *repository*, invisible to every
deployed instance until a new app release ships and is installed. The gemeente rollout
research names this directly as a pain: "no in-product signal when upstream municipality
tokens update" — communications departments own the huisstijl and expect to be told when
their brand source changed, the way M365 Brand Center surfaces brand updates. Theme
freshness/semver awareness in the admin panel is a top-ranked ecosystem opportunity
(competitive research, opportunity #2).

This change adds the app's **first background job** — a daily `OCP\BackgroundJob\TimedJob`
that checks whether the pinned upstream (`nl-design-system/themes`) has moved past the
revision the installed token sets were generated from, and surfaces a review notice in the
nldesign admin settings. Because this is also the app's **first outbound network call** (a
new egress, to GitHub, from an app positioned for sovereign/air-gapped deployments), the
check is **opt-in: default OFF**, with an admin toggle that names the exact host contacted.
Rationale for default-OFF over default-ON-with-opt-out: (1) privacy/sovereignty by default —
a theming app phoning a US-hosted service unprompted would undermine the app's core
positioning and surprise gov security officers; (2) air-gapped and egress-filtered instances
are a normal deployment mode for the target audience, and a default-on job would generate
daily failure noise there; (3) the cost of opt-in is one checkbox for the admins who want
it. When enabled, the job degrades silently offline — a failed check never affects theming,
never notifies, and never throws. There is explicitly **NO auto-apply**: the job only
informs; applying updated tokens remains the existing human release/upgrade path.

To compare "installed" against "upstream", installed sets need provenance:
`token-sets.json` entries gain optional `upstreamVersion` (upstream theme package semver)
and `upstreamRef` (nl-design-system/themes commit SHA at generation time) fields, recorded
by the existing generation script — hence MODIFIED deltas to the `token-sets` and
`token-sync-workflow` specs alongside the new `upstream-freshness` spec.

## What Changes

- **MODIFIED `token-sets` spec**: `token-sets.json` entries MAY carry `upstreamVersion`
  and `upstreamRef`; absence is valid (hand-made/custom/nextcloud sets have no upstream).
- **MODIFIED `token-sync-workflow` spec**: the generation script records
  `upstreamVersion` (from each upstream theme's package version) and `upstreamRef` (the
  themes-repo commit SHA it generated from) into `token-sets.json`.
- New `lib/BackgroundJob/UpstreamFreshnessJob.php` (`OCP\BackgroundJob\TimedJob`, 24 h
  interval, time-insensitive), registered via `appinfo/info.xml` `<background-jobs>`. Run
  contract: short-circuit with zero network when the opt-in toggle is off; otherwise ONE
  conditional HTTP GET (ETag `If-None-Match`, ETag + last state cached in IAppConfig) to a
  pinned manifest URL — default: the GitHub commits API for the pinned branch of
  `nl-design-system/themes`, returning the branch head SHA (steady state is a 304 and zero
  further work). Only when the head SHA differs from the installed sets' `upstreamRef` is
  ONE follow-up compare request made to attribute changed upstream paths
  (`proprietary/<org>/…`) to installed token set ids — best-effort: if attribution fails,
  a generic "upstream has updates" notice is stored instead. Hard cap: two outbound
  requests per run, 10 s timeout each, via `OCP\Http\Client\IClientService`. Every failure
  path is caught inside `run()` and logged at info level at most — job failure never
  breaks theming, cron, or the admin panel.
- New `lib/Service/UpstreamFreshnessService.php` owning the IAppConfig state
  (`upstream_freshness_enabled` default `'no'`, `upstream_etag`, `upstream_head_sha`,
  `upstream_checked_at`, `upstream_updates` JSON, `upstream_freshness_dismissed` JSON),
  the comparison logic, and notice/dismissal semantics.
- Admin settings surface: a new "Upstream token updates" block — opt-in toggle whose label
  states the outbound host ("checks api.github.com once a day"), last-checked timestamp,
  and per-set notices "Token set X has upstream update vY — review & apply" with a Dismiss
  action. Dismissal is per (setId, upstream version/SHA): a *newer* upstream version
  re-surfaces a previously dismissed notice. Endpoints
  `GET/POST /settings/upstream-freshness` and
  `POST /settings/upstream-freshness/dismiss`, all admin-only
  (`#[AuthorizedAdminSetting]`, CSRF-protected).
- Explicit non-goals (specced as prohibitions): no auto-apply, no auto-download, no
  writes to `css/tokens/` or the active token set from the job, no telemetry payload —
  the outbound request carries no instance-identifying data beyond what any HTTP request
  carries.
- New canonical spec `openspec/specs/upstream-freshness/spec.md` (delta in this change).
- No DB tables, no Vue, no new composer dependencies. Not BREAKING: default OFF; the two
  manifest fields are optional and ignored by all existing consumers.

## Impact

- `lib/BackgroundJob/UpstreamFreshnessJob.php` — new (new `lib/BackgroundJob/` dir; first
  background job in the app).
- `lib/Service/UpstreamFreshnessService.php` — new.
- `lib/Controller/SettingsController.php` — `getUpstreamFreshness`,
  `setUpstreamFreshness`, `dismissUpstreamNotice` methods.
- `appinfo/routes.php` — three new routes; `appinfo/info.xml` — `<background-jobs>` entry.
- `lib/Settings/Admin.php`, `templates/settings/admin.php`, `js/admin.js` — admin block.
- `token-sets.json` — optional `upstreamVersion`/`upstreamRef` fields (populated by the
  next sync-workflow run; this change updates the generation script
  `scripts/generate-tokens.mjs` and the workflow docs to record them).
- `l10n/` — new strings; `tests/unit/BackgroundJob/UpstreamFreshnessJobTest.php`,
  `tests/unit/Service/UpstreamFreshnessServiceTest.php` — new.
- `openspec/specs/upstream-freshness/spec.md` (new),
  `openspec/specs/token-sets/spec.md` + `openspec/specs/token-sync-workflow/spec.md`
  (modified) — via this change's deltas.
- Cross-references: complements (does not modify) the CI-side `token-sync-workflow`; no
  overlap with `email-template-theming` / `custom-font-upload`.
