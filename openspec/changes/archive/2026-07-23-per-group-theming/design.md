# Design — per-group-theming

## Context

`Application::boot()` runs on every request and injects the token-set CSS stack via
`OCP\Util::addStyle` (`injectThemeCSS()`). Today the set is a single IConfig app value
(`token_set`). This change makes the *choice of set* request-dependent (who is logged in),
while everything else about injection stays as specified in `css-architecture`.

## Decision 1 — Resolution precedence

Order of authority for the token set injected on a request:

1. **Admin preview** (change `theme-preview-workflow`): if the current session carries an
   active preview, the previewed set wins for that admin's requests. Rationale: preview exists
   to answer "what would it look like"; being in a mapped group must not make a preview
   unverifiable.
2. **Group mapping**: logged-in user, first entry of the ordered `group_token_sets` array whose
   group contains the user. Array order IS the priority order — there is no separate priority
   field to drift out of sync. Tie-break is therefore deterministic by construction: a user in
   groups of entries 2 and 5 gets entry 2. Entries referencing a token set that no longer
   exists (deleted custom set) are skipped and resolution continues down the list.
3. **Instance default**: the existing `token_set` app value. Used for: no session
   (login/public/anonymous/error pages), user in no mapped group, empty mapping, or ANY
   resolution failure (exception, cache unavailable, group backend slow-path error). Fail open
   to the default set — presentation, never security; a broken group backend must not brick
   theming (same philosophy as `isThemingDisabled()`'s catch-all).

Non-goals at this layer: per-user mapping (groups only), per-domain mapping (Domain Theming
app's territory), and NC core theming values — `ThemingDefaults` logo/primary and everything
theming-sync pushes remain instance-global. The spec states this limitation normatively.

## Decision 2 — Storage shape

IConfig app value `group_token_sets` = JSON array: `[{"group": "gemeente-a", "tokenSet":
"amsterdam"}, ...]`. Chosen over a JSON object keyed by group because objects don't have a
reliable order and priority IS the semantics. Companion app value
`group_token_sets_generation` = monotonically increasing integer, bumped on every mapping
write. Validation on save: every `group` must exist in `IGroupManager`, every `tokenSet` must
be an available set id (shipped manifest or custom manifest); duplicates by group are rejected
(one entry per group — priority handles multi-group users, not duplicate rows).

## Decision 3 — Caching and invalidation

Resolution cost without caching = one `getUserGroupIds()` + a linear scan of the mapping per
request. That group lookup is the part to amortize. Design:

- Cache: `ICacheFactory::createDistributed('nldesign-group-theming')` (falls back to local/array
  cache when no distributed cache is configured — still correct, just per-node).
- Key: `resolve:{userId}:{generation}`; value: resolved token set id; TTL 1 hour as a backstop.
- Invalidation: bump `group_token_sets_generation` on every mapping save. Old-generation keys
  become unreachable and age out via TTL — no enumeration, no flush of unrelated keys, O(1)
  invalidation. Group-membership changes (user added to a group mid-session) are picked up at
  worst after TTL expiry; acceptable for presentation, documented in the spec scenario.
- The mapping array itself is one IConfig read (already cached by NC's config layer); only the
  group-membership resolution is cached.
- Boot-path budget: cache hit = one cache get; cache miss = one group lookup + scan + cache
  set. No lookup at all when the mapping is empty (fast-path: empty mapping ⇒ default set,
  no cache, no group query — preserves today's exact behavior for non-adopters).

## Decision 4 — Injection wiring

`injectThemeCSS()` currently reads `token_set` once. Change: resolve via
`GroupThemingService::resolveTokenSetForRequest()` which internally consults (in order) preview
state (cross-change; until theme-preview-workflow lands the hook is a no-op), the group
mapping, then the default. Everything downstream (design-system resolution, stylesheet order,
custom overrides last, conditional CSS, per-app exclusion guard BEFORE any of it) is untouched.
The per-app exclusion check stays first and orthogonal: excluded app ⇒ no injection at all,
whatever the mapping says.

Session-less surfaces (login, public share, error pages) resolve to the default set explicitly
via the `IUserSession::getUser() === null` branch — not by exception fallback — so the behavior
is intentional, testable, and stated in the spec.

## Decision 5 — Admin UI (vanilla JS)

A new section under the per-app theming block in `templates/settings/admin.php`:
`#nldesign-group-theming-list` rendering one row per mapping entry — group select (populated
from a server-provided group list), token-set select (same option source as the main dropdown),
up/down reorder buttons, remove button — plus an "Add mapping" button and a Save button with
feedback element, exactly mirroring the fetch + render + save flow of the existing
`#nldesign-app-theming-list` code in `js/admin.js`. No drag-and-drop (keyboard-accessible
up/down buttons instead — consistent with the admin-panel-keyboard-accessibility work).

## Risks / trade-offs

- **Split-brain branding**: group users get set X while core theming (logo/primary synced from
  the default set) shows set Y's values in mail templates and clients. Mitigated by stating the
  limitation in spec + admin UI hint text; NOT mitigable technically without upstream per-group
  ThemingDefaults (server#23545).
- **Cache staleness on membership change** (≤ TTL). Accepted; documented.
- **Boot-path regression risk**: mitigated by the empty-mapping fast path and the fail-open
  catch-all; precedence matrix unit tests + a live two-user verification cover the rest.
