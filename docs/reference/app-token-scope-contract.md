---
sidebar_position: 9
---

# App Token-Set Selection: Public Catalogue, Contrast, and Scoped-Application Contract

This page documents Thematiq's read-only, non-admin consumption surface for a
**leaf app's own picker** — a builder tool inside another Conduction app
(OpenBuild's virtual-app theme picker is the live example) — to enumerate the
Thematiq token-set catalogue, evaluate WCAG contrast for arbitrary candidate
colors, and (via a shared client-side applier built elsewhere) apply a token
set scoped to one element on the page rather than instance-wide.

This is **Thematiq's side of the contract only**. The scoped applier itself is
implemented in `nextcloud-vue`, shared across every leaf app — this page
documents the contract it implements against; it ships no Vue and no client
code.

## `GET /api/token-sets` — non-admin catalogue

Authenticated non-admin user (`#[NoAdminRequired]`, **not** `#[PublicPage]`):
any logged-in user of the instance, not anonymous internet traffic. This is
deliberately narrower than admin-only (any logged-in user can read it) and
narrower than public (an anonymous visitor cannot).

```
GET /apps/thematiq/api/token-sets
```

Response:

```json
{
  "tokenSets": [
    {
      "id": "rijkshuisstijl",
      "name": "Rijkshuisstijl",
      "design_system": "nldesign",
      "theming": {
        "primary_color": "#154273",
        "background_color": "#F5F6F7",
        "logo": "img/logos/rijkshuisstijl.svg"
      },
      "wcagLevel": "AA"
    }
  ]
}
```

Each entry is exactly the 5 fields above (`theming.logo` present only when
the set declares one — omitted, never `null`, otherwise). This is
deliberately narrower than the admin `GET /settings/tokensets` response: no
`description`, `custom`, `warnings`, `upstreamVersion`, or `upstreamRef`.
Every entry `TokenSetService::getAvailableTokenSets()` discovers is included
— shipped sets and admin-uploaded custom sets alike, no filtering by origin.

`wcagLevel` is one of `AAA`, `AA`, `fail`, or `null` (stock/custom/unknown
sets), computed via the same audit path the public capabilities document
(`capabilities.nldesign.wcagLevel`) uses for the active set, and cached under
the same `ICache` prefix (`nldesign_wcag_level`) — a set that is both the
active theme and a catalogue entry is audited at most once per cache TTL
window (3600s), not twice.

## `POST /api/contrast/evaluate` — shared contrast evaluation

Authenticated non-admin user (`#[NoAdminRequired]`). No CSRF exemption: a
same-origin, authenticated browser POST carries the Nextcloud request token
automatically.

```
POST /apps/thematiq/api/contrast/evaluate
Content-Type: application/json

{
  "candidates": [
    { "name": "primary", "value": "#154273", "role": "text" },
    { "name": "accent", "value": "#e8f0f8", "role": "ui" }
  ],
  "background": "#F5F6F7"
}
```

Response:

```json
{
  "results": [
    { "name": "primary", "ratio": 8.99, "threshold": 4.5, "level": "AA", "pass": true },
    { "name": "accent", "ratio": 1.05, "threshold": 3.0, "level": "AA", "pass": false }
  ]
}
```

`role: "text"` candidates are evaluated at the 4.5:1 WCAG AA threshold,
`role: "ui"` candidates at 3.0:1. A candidate (or the `background`) that is
not a parseable literal color (hex or `rgb()`/`rgba()` — e.g. `var(--token)`)
is reported `unevaluated: true`, `ratio: null`, and is **never** reported as
`pass: true`.

**The response never contains a `blocked`, `allowed`, or `verdict` field.**
This endpoint reports facts only — ratio, threshold, level, pass — and the
calling app's own UI decides what to do with them. See
[Selection contrast is non-blocking](#selection-contrast-is-non-blocking)
below for the resolved policy on what a caller is expected to do with this
data.

## Selection contrast is non-blocking

Evaluating contrast for a leaf app's **selection** of an existing catalogue
entry (any set listed by `GET /api/token-sets` — shipped or already-uploaded
custom) MUST always be treated as a warning, never a hard block — consistent
with Thematiq's own upload-time policy
([custom-token-sets](../features/custom-token-sets.md): non-blocking WCAG AA
warnings). Neither endpoint above exposes a mechanism for a caller to
condition a hard block on Thematiq's own contrast data for a selection flow
— there is no `blocked`/`allowed` field to even wire a hard block off of.

This policy governs **selection only**. A leaf app's own **free-hand
custom-color authoring** (a distinct concern — e.g. a leaf app's own raw
color-picker feature, not a catalogue selection) remains that app's own
decision: it may keep, relax, or drop its own local blocking policy on top of
the same shared ratio data from `POST /api/contrast/evaluate`.

## Scoped-application contract for base token CSS

A shared client-side applier (built in `nextcloud-vue`, not this app)
implements against the following contract to apply a token set scoped to one
element on a page, rather than instance-wide:

1. **Token namespace**: `--nldesign-*` (existing, unchanged) — the stable
   vocabulary a scoped applier rewrites verbatim, never renaming or
   reinterpreting a property.
2. **Scope attribute**: `data-nldesign-theme-scope="<scopeId>"` — owned by
   nldesign (the design system), not by any individual leaf app. `<scopeId>`
   is minted and validated entirely by the consuming applier; nldesign only
   requires the attribute exists on the scope root. Value-bearing (not
   presence-only) so more than one differently-themed scope can coexist on
   one page.
3. **Selector-rewrite rule**: `:root` → `[data-nldesign-theme-scope="<scopeId>"]`
   — a 1:1 selector-prefix transform. No property name, value, or
   declaration order is ever altered.
4. **Scope of coverage**: base/light `css/tokens/<id>.css` **only**. The
   generated dark variant (`css/tokens/dark/<id>.css`) uses an unrelated
   `@media (prefers-color-scheme: dark) { body:not(...) { ... } }` shape and
   is explicitly **out of scope** — scoped per-app dark-mode theming is a
   distinct, unsolved problem.
5. **Defensive rule (bail, never partially rewrite)**: a consuming applier
   MUST first verify the fetched CSS is exactly one flat `:root { }` block
   with no at-rules and no other selector, and MUST inject nothing
   (degrading to default/unstyled) if it is not — partial rewriting must
   never occur.

Thematiq guarantees every shipped `css/tokens/*.css` file (excluding
`dark/`) satisfies this flat `:root`-only shape — enforced by an automated
regression test (`tests/Unit/TokenCssShapeTest.php`) over every shipped set.
Custom-uploaded sets already satisfy this shape by construction: the
existing `CustomTokenSetValidator` rejects any non-`:root` selector or
at-rule at upload time.

## What this contract is not

- No Vue, no scoped applier implementation ships from Thematiq — the
  applier is `nextcloud-vue`'s responsibility.
- No new appconfig/storage — both endpoints are pure read/compute
  projections over existing state.
- No change to any existing endpoint's shape, auth posture, or behaviour —
  the admin `GET /settings/tokensets`, `POST /settings/tokensets/upload`,
  and the public capabilities document are all unchanged.
