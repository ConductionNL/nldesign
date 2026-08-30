# Design — app-token-set-selection

## Problem

nldesign owns the token-set catalogue, custom-set upload, and WCAG contrast math end to end, but
every read path over that state is either admin-only (`settings#*`, all
`#[AuthorizedAdminSetting(Admin::class)]`) or scoped to the single active set
(`Capabilities`, instance-wide `CssInjectionService`). A leaf app that wants to let its own
non-admin users pick an NL Design token set for *their own* app/page (OpenBuild's virtual-app
builder is the live example) has no supported way to enumerate the catalogue, and no supported way
to check a candidate color's contrast without re-implementing nldesign's WCAG math client-side —
which OpenBuild has already done twice: once correctly (matching nldesign's formula, but pinned to
a hardcoded background and enforced as a hard block) and once by building a full ad hoc scoped-CSS
rewriter that depends on an invariant (flat `:root`-only token CSS) nldesign has never published as
a contract. This design closes both gaps and resolves the resulting policy conflict, without
building the client-side applier itself (that is the companion `nextcloud-vue` change's job).

## Key decision 1 — the catalogue endpoint is `#[NoAdminRequired]`, not `#[PublicPage]`, with a closed 5-field projection

**Auth posture: authenticated non-admin user, not anonymous.**

- The only known consumer (OpenBuild's `ThemePickerDialog`, and the class of leaf-app pickers it
  represents) always runs inside an authenticated Nextcloud session — a builder editing a virtual
  app's manifest. There is no anonymous/pre-login consumer for an *enumeration* endpoint, unlike
  `Capabilities` (`IPublicCapability`), which exists specifically because the *active* theme is
  already unavoidably visible to an anonymous visitor loading the very page that renders it.
- Exposing the full catalogue — including any admin-uploaded custom sets, which may encode an
  organisation's not-yet-publicly-announced huisstijl — to anonymous internet traffic is a new
  information-disclosure surface with no consumer need. `#[NoAdminRequired]` keeps it inside the
  instance's own authenticated user base, which is the correct trust boundary: any logged-in user of
  a given instance already belongs to that instance's organisation.
- This matches the Nextcloud idiom precisely — `#[NoAdminRequired]` means exactly "any logged-in
  user", not "any admin" and not "the whole internet" — and keeps the endpoint outside the
  `/settings/*` URL prefix this app already reserves, by convention, for admin-gated routes (every
  existing `/settings/*` route is `AuthorizedAdminSetting`; the non-`/settings/*` `/api/*` prefix is
  already used for `metrics`/`health`, both focused, non-admin-config endpoints). The new route is
  `GET /api/token-sets`.

**Response shape: closed 5 fields (`id`, `name`, `design_system`, `theming.{primary_color,
background_color, logo}`, `wcagLevel`), not the admin shape.**

- Reuses `TokenSetService::getAvailableTokenSets()` verbatim for discovery — no second scan, no
  second manifest-merge logic, per the task's own constraint not to duplicate discovery.
- Deliberately narrower than the existing admin `TokenSetEntry` shape: drops `description` (not
  needed by a swatch-driven picker; can be added later without a breaking change if a consumer
  needs it — see Open Questions), `custom` (a leaf-app picker does not need to distinguish shipped
  from admin-uploaded — both are equally "available for this instance"), `warnings` (the raw
  per-pair warning structure is an nldesign-internal audit shape; `wcagLevel` is the stable,
  already-public-facing summary a leaf app actually needs), and `upstreamVersion`/`upstreamRef`
  (upstream-freshness is an nldesign-admin concern, not a picker concern). Keeping the contract
  minimal lets nldesign evolve its internal admin fields (warning shapes, provenance fields)
  without a cross-app breaking change — the same reasoning `theming-capability`'s existing
  `Capabilities` payload already applies (a fixed, small, allowlisted key set, not a raw entity
  dump).
- Field naming intentionally mirrors two existing precedents rather than inventing a third
  convention: `id`/`name`/`design_system`/`theming.primary_color`/`theming.background_color`/`logo`
  are copied byte-for-byte from `TokenSetEntry`/`token-sets.json` (already snake_case for these
  keys, unchanged since 2026-02); `wcagLevel` reuses `Capabilities`' already-shipped camelCase name
  for the identical concept (`AAA`/`AA`/`fail`/`null`) rather than minting `wcag_level`. This is a
  deliberate consistency-over-purity choice — each field matches the precedent it's projected from.
  (ADR-011's "every property needs a human-friendly title/description" is an OpenRegister
  schema-property rule; this is a plain REST projection, not an OR schema, but the same discipline
  is honoured in spirit: every field above is named for what a consumer reads it as, and is
  documented, not a raw internal identifier.)

**`wcagLevel` computation and caching.**

`wcagLevel` per catalogue entry MUST be computed via the same path
`Capabilities::computeWcagLevel()`/`auditWcagLevel()` already uses for the single active set
(`ShippedTokenSetAuditService::auditSet()`), and MUST be cached the same way: `ICache` prefix
`nldesign_wcag_level`, key `level-<id>`, TTL 3600s. Extending the *same* cache namespace (rather
than inventing a second one) means a set that is both the active theme (warmed by `Capabilities` on
every capabilities-document read) and a catalogue entry (read by this endpoint) shares one cache
entry — no duplicate computation, no duplicate cache key space. Without this, a naive
per-request implementation would re-run the audit for all ~43+ sets on every picker open, which is
the same per-request cost `TokenSetService::applyWarnings()` already pays today for the *admin*
list endpoint (it calls `ShippedTokenSetAuditService::warningsFor()` per shipped entry unconditionally)
— acceptable there because it's admin-only and infrequent; not acceptable to duplicate uncached on a
leaf-app endpoint that may be polled more often.

## Key decision 2 — the contrast entry point generalises `ContrastService`, and never returns a verdict

**Why not just expose the existing `ContrastService::check()` as-is.** `check()` is hardcoded to
exactly two fixed pairs (`--nldesign-color-primary-text` vs `--nldesign-color-primary` @4.5:1;
`--nldesign-color-primary` vs `--nldesign-color-background` @3:1) — the pairs nldesign's own
upload form cares about. OpenBuild's duplicated need (`checkThemeContrast.js`) is structurally
different: three independent candidate colors (`primaryColor` as text, `primaryColor`/
`secondaryColor`/`accentColor` each as a UI element) against one shared background, not nldesign's
two named token pairs. Exposing `check()` unchanged would not let OpenBuild delete its duplicate —
it would just move the duplication server-side while still requiring OpenBuild to keep its own
pairing logic client-side.

**Shape: `POST /api/contrast/evaluate` (`#[NoAdminRequired]`), a new `ContrastService::evaluate()`
generalisation.** Accepts a list of candidates `{ name, value, role: "text"|"ui" }` and an optional
`background` (hex; defaults to the CALLER'S supplied value — see Open Questions for whether nldesign
should ever supply an implicit default), evaluated at the existing thresholds (`text` → 4.5:1,
`ui` → 3.0:1) using the same relative-luminance math `ContrastService` already implements
(`relativeLuminance()`, `ratio()`, `parseColor()` are reused unchanged — only the pairing/threshold
selection is generalised). Returns, per candidate: `{ name, ratio, threshold, level: "AA", pass,
unevaluated? }`. `check()` (the fixed-pair, upload-time method) is unchanged and keeps serving
`CustomTokenSetService::store()` exactly as today — `evaluate()` is additive, not a replacement.

**The endpoint returns data, never a verdict.** No `blocked`/`allowed` field exists in the response
at all. This is deliberate and is what makes the blocking-policy resolution (decision 4) possible
without nldesign dictating another app's UI: nldesign supplies the ratio/threshold/pass facts: the
calling app's own UI decides what to do with them (warn, block, ignore). This is consistent with
nldesign's own `check()` today, whose caller (`CustomTokenSetService::store()`) already treats every
result as a non-blocking `warnings` array, never a rejection.

## Key decision 3 — a design-system-owned scope attribute, contract published (not implemented) here

**The contract, precisely:**

- Token namespace: `--nldesign-*` (existing, unchanged — reaffirmed as the stable vocabulary a
  scoped applier rewrites verbatim, never renaming or reinterpreting a property).
- Scope attribute: `data-nldesign-theme-scope="<scopeId>"` — **owned by nldesign** (the design
  system), not by any one leaf app. This deliberately supersedes OpenBuild's existing
  `data-openbuild-theme-scope="<appSlug>"`, which only OpenBuild can use and which every other leaf
  app would otherwise have to reinvent under its own prefix. `<scopeId>`'s value format (an app
  slug, a widget id, anything unique enough to avoid collision on one page) is the *consuming*
  app's concern — nldesign does not mint, validate, or interpret it; it only requires the attribute
  exists on the scope root so the CSS selector rewrite below has a well-formed target. Kept
  value-bearing (not presence-only) so more than one differently-themed scope can coexist on one
  page (e.g. two virtual-app widgets on one dashboard, each with a different token set) — the same
  shape OpenBuild's existing rewriter already proved workable at.
- Selector-rewrite rule: `:root` → `[data-nldesign-theme-scope="<scopeId>"]`, a 1:1 selector-prefix
  transform. No property name, value, or declaration order is altered — the scoped applier is a
  pure selector substitution over an already-validated, already-flat stylesheet.
- Source scope: **base/light `css/tokens/<id>.css` only.** The dark variant
  (`css/tokens/dark/<id>.css`, generated by `DarkPaletteService`) is a different shape entirely —
  `@media (prefers-color-scheme: dark) { body:not([data-theme-light])... { ... } }` — confirmed by
  reading a generated file. Scoped per-app dark-mode theming is a distinct, harder problem (the
  rewrite target would need to be a compound selector under the media query, and the `body:not(...)`
  guard clashes with an arbitrary scope element that is not `body`) and is explicitly **out of
  scope** for this change; see Open Questions.
- Defensive rule (bail, never partially rewrite): a consumer performing the rewrite MUST first
  verify the fetched CSS is exactly one flat `:root { ... }` block with no at-rules and no other
  selectors, and MUST inject nothing (degrade to default/unstyled) if it is not. This is not a new
  invention — it is OpenBuild's own already-shipped rule
  (`nldesign-theme-selection` REQ-NTS-003: *"if the fetched text contains constructs the rewriter
  does not positively recognise ... the applier SHALL inject nothing and degrade to default
  styling"*), promoted here from one leaf app's local defensive coding into a first-class nldesign
  contract requirement so every future consumer gets the same guarantee instead of rediscovering the
  need for it.

**Why nldesign can credibly promise the flat-`:root`-only shape.** Verified empirically, not
assumed: every one of the 43 shipped `css/tokens/*.css` files was grepped for `@media`, `@supports`,
`@import`, `@font-face`, and for any top-level selector other than `:root` — zero matches. For
custom uploads, `CustomTokenSetValidator` already enforces this mechanically at write time
(`hasNonRootSelector()`-style validation rejects any other selector; `serialize()` always emits
exactly one `:root { }` block — see `lib/Service/CustomTokenSetValidator.php`). This change adds a
**regression test** (task 3.2) turning the shipped-set half of that invariant, which today holds by
authoring discipline only, into a mechanically-enforced guarantee — because a future
hand-authored shipped set is the one path not already validated by code.

**Why the applier itself is not built here.** Building the rewriter/applier is Vue/JS runtime
behaviour with no natural home in nldesign (a PHP-only, no-Vue app per this app's existing
architecture) — it belongs in `nextcloud-vue`, shared across every leaf app, exactly as the task
scopes it. nldesign's job is to guarantee the CSS shape and publish the attribute name the shared
applier targets.

## Key decision 4 — curated-set selection is warn-only; nldesign's warn model wins the conflict

**The conflict, stated precisely.** nldesign's own contrast policy has been warn-only since the
`custom-token-sets` capability shipped: *"Failures MUST be returned as non-blocking warnings"*
(admin upload). OpenBuild's independently-built `app-theming` feature enforces the opposite for its
own raw-color picker: *"There SHALL be no override or bypass of this check"* (hard block on save).
Once this change makes nldesign's catalogue enumerable and its contrast math shared, a naive
integration could accidentally import OpenBuild's hard-block policy into the *selection* flow (an
end user picking one of nldesign's own, already-audited, already-published token sets) — which
would be new friction nldesign never intended and cannot justify: a shipped/curated set's contrast
has already been through `ShippedTokenSetAuditService`'s audit (or an admin's original upload
warning) once; re-blocking a *selection* of that same set a second time, in a different app, adds no
new information and only adds friction.

**The resolution.** Two policies, cleanly separated by what is being evaluated, not by which app is
calling:

1. **Selecting** an existing catalogue entry (shipped or already-uploaded custom, i.e. anything
   `GET /api/token-sets` lists) is **always warn, never blocked** — regardless of which app is
   doing the selecting. This is nldesign's policy, applies uniformly, and is what "nldesign's
   warn model as the single source of truth" means concretely: nldesign will never expose a
   selection flow, contract, or response shape that lets — let alone requires — a caller to hard
   block on it. This is also mechanical, not just documentary: `POST /api/contrast/evaluate` (and
   the catalogue's `wcagLevel`) return facts only, no `blocked` field exists to even wire a hard
   block off of.
2. **Authoring** a brand-new raw color (a leaf app's own free-hand color picker, distinct from
   picking one of nldesign's sets — OpenBuild's `appTheme.primaryColor` etc. is the existing
   example) is **not what this change governs**. A leaf app remains free to keep, relax, or drop its
   own local blocking policy for *that* concern using the same shared ratio data — nldesign
   supplies facts, never a verdict (decision 2). The companion OpenBuild change's own scope
   decides whether `checkThemeContrast.js`'s hard block survives for free-hand authoring after it
   switches to calling the shared endpoint; that decision is explicitly out of scope here.

This is why decision 2's endpoint never returns a verdict: it is the mechanism that makes both
policies representable from the same data without nldesign having to special-case "am I being
called for a selection or an authoring flow".

## Data flow

```
leaf app picker (e.g. OpenBuild ThemePickerDialog)
  │
  ├─ GET /api/token-sets   (#[NoAdminRequired])
  │     └─ TokenSetService::getAvailableTokenSets()  (existing, unchanged)
  │           └─ project → { id, name, design_system, theming{...}, wcagLevel }
  │                 └─ wcagLevel: ShippedTokenSetAuditService::auditSet()
  │                       cached  ICache["nldesign_wcag_level"]["level-<id>"]  (shared w/ Capabilities)
  │
  ├─ builder picks a set → (companion nc-vue applier, NOT this change)
  │     fetches css/tokens/<id>.css → verifies flat :root-only → rewrites
  │     :root → [data-nldesign-theme-scope="<scopeId>"] → injects <style>
  │     (bails + degrades to default styling if the CSS is not flat :root-only)
  │
  └─ (optional) POST /api/contrast/evaluate   (#[NoAdminRequired])
        candidates [{name, value, role}], background
          └─ ContrastService::evaluate()  (new; reuses relativeLuminance/ratio/parseColor)
                └─ { name, ratio, threshold, level, pass, unevaluated? }[]   — never a verdict
                      └─ caller decides: selection path → warn only (decision 4);
                                          free-hand authoring path → caller's own policy
```

## Scope / non-goals

- No Vue, no scoped applier implementation — published contract only (companion `nextcloud-vue`
  change).
- No OpenBuild edits — `ThemePickerDialog`'s fallback tiers and `checkThemeContrast.js`'s fate are
  the companion OpenBuild change's decision, informed by this contract.
- No dark-mode scoped application — the contract in decision 3 covers base/light token CSS only.
- No new appconfig/storage — both new endpoints are pure read/compute projections over existing
  state.
- No change to any existing endpoint's shape, auth posture, or behaviour — `check()`,
  `getAvailableTokenSets()` (admin), and `Capabilities` are unchanged; the catalogue and contrast
  endpoints are additive.
- Does not extend `custom_token_sets` visibility semantics (e.g. per-group/per-tenant filtering) —
  the catalogue lists everything the active instance's `TokenSetService` already discovers, same as
  the admin endpoint does today; multi-tenant scoping (if ever needed) is `per-group-theming`'s
  territory, not this change's.

## Open Questions

- Should `description` be added to the catalogue's 5-field contract in a later revision (a picker
  richer than swatches-and-name might want it)? Deferred — not needed by the known consumer today,
  and additive later is non-breaking.
- Should `POST /api/contrast/evaluate` supply an *implicit* default background (e.g. the active
  token set's own `--nldesign-color-background`) when the caller omits one, rather than requiring
  every caller to pass it explicitly? Pinning to a fixed `#FFFFFF` (OpenBuild's current, arguably
  wrong, assumption) is simpler but reintroduces the exact staleness OpenBuild's own module
  docblock already flags as a known simplification ("v1 does not vary the check per theme"). Left
  open for the apply phase to resolve against the companion changes' actual needs.
- Dark-mode scoped application (compound selector under `@media (prefers-color-scheme: dark)`,
  reconciling the `body:not(...)` guard with an arbitrary scope element) is real, unsolved, and
  will need its own change once a consumer needs it — not blocking this change.
- Final controller class names (`CatalogController`/`ContrastController` proposed here, mirroring
  `MetricsController`/`HealthController`) are an implementation-time decision; the apply phase may
  choose to fold one or both into `SettingsController` instead (which already mixes admin and
  non-admin methods, e.g. `FontController::serve()` is public alongside its admin upload methods) —
  not load-bearing for this spec, which only fixes the URL/route names and auth attributes.
