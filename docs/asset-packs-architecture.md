---
sidebar_position: 12
---

# Asset Packs — Architecture for a Nextcloud Icon and Asset Extension

**Status:** proposed architecture, ready to build

**Evidence snapshot:** verified on Nextcloud **34.0.2**, 2026-08-08, in Firefox 153 and Chromium 151

**Home:** staged here because the research lives here. **This document moves to the sibling repository when it is created** (see §9); it describes an app that is deliberately *not* `nldesign`.

> **Current-app boundary:** composition statements below are target assumptions,
> not a claim about the implemented `nldesign` feature set. The current app
> projects only font and primary interaction roles and offers manual Theming
> recommendations. It does not yet project radius/type/density or perform
> automatic logo, favicon, or background writes.

**Supporting evidence:** [feasibility research](./teams-look.md) and [evidence gates](./teams-look-gates.md). Every mechanism below was measured on a live instance, not inferred. Where something is extrapolated rather than measured, it says so.

## 1. Decision

**An asset pack is a compiled set of replacement assets, selected by an administrator from a dropdown, delivered as one stylesheet.**

It is the third of three independent components. Keeping them separate is the architecture; the Teams goal is what they compose to:

| Component | Supplies | Owned by |
|---|---|---|
| Navigation rail | left vertical app rail | **`side_menu`** — a third-party app, already installed and configured on the reference instance |
| Colour, radius, type, density | the design language | **`nldesign` target** — only font and primary interaction roles exist today |
| **Glyphs and asset artwork** | icon geometry, theme previews | **this app** — markup conventions, higher upgrade risk |

None depends on another at build time. `nldesign` needs no knowledge of this app; this app reads `--nldesign-*` custom properties **if they are present in the cascade** and falls back to its own defaults if not. Custom properties are global, so the coupling is nil — no PHP API, no shared service, no version negotiation.

**Why separate at all:** `nldesign` projects through documented variables and can honestly claim a wide Nextcloud range. This app rides Vue class names and Material Design path data — conventions nobody promised to keep. Bundling them would mean an administrator cannot take the stable half without inheriting the fragile half, and would make `nldesign`'s support claim untrue. §8 is the whole reason this is a second app.

## 2. Scope: what an asset pack owns

"Assets" is broader than icons. The instance inventory (958 SVG, 121 font files, 53 backgrounds, 10 sounds, 87 email templates) resolves into four groups:

| Asset | In a pack? | Why |
|---|---|---|
| **UI icons** — 2,023 distinct geometries shipped, **83 distinct visible** across eight real pages | **Yes** — the primary payload | Reachable by fingerprint; see §4 |
| **Theme preview thumbnails** — the 5 images in Appearance settings | **Yes** — cheap and high value | A `<div>` with a CSS `background-image`, so a stylesheet overrides it. **The only asset gap that is actively misleading**: after theming, the picture a user clicks to choose "Light" no longer resembles what they get |
| Fonts | **No** — `nldesign` owns `--font-face` | Note the risk it carries: `core/fonts` ships **Noto Sans subsetted per script** (Latin, Cyrillic, Greek, Devanagari, Vietnamese). Overriding the face silently drops coverage for some scripts |
| Logo, favicon, backgrounds | **No** — Nextcloud Theming owns these | `nldesign` currently provides a manual hand-off; automatic lifecycle support remains proposed |
| **Sounds** — Talk ringtones, notification chimes | **No — unreachable** | App config only; no CSS involved. Declare it, do not imply it |
| **Email templates** — 87 PHP files | **No** | Theming reaches colour and logo; layout is PHP |
| App icons in the header — `<img>`, no class | **Optional, second phase** | `src` fingerprint plus `content: url()`; verified working, but see §7 on the security boundary |

**Explicitly not in scope:** components (message bubbles, compose boxes) are not reachable from CSS at all, and file-type icons in the Files list are Vue components rather than the legacy `core/img/filetypes` files, so replacing those files changes nothing.

## 3. Delivery: one compiled stylesheet per pack

This mirrors `RuntimeStylesheetPlan`'s existing `tokens/{profile}` contract exactly. **Nothing is generated at request time.**

```
css/
  packs/
    lucide.css        compiled: fingerprints + appearance + previews
    fluent.css
    tabler.css
```

```php
final class AssetPackStylesheetPlan
{
    /** @return array<int, string> App-relative stylesheet names, in load order. */
    public function build(string $packId): array
    {
        return ['packs/'.$packId];
    }
}
```

Loaded from a template listener with `\OCP\Util::addStyle(Application::APP_ID, 'packs/'.$packId)`. One file, one request, no controller route, no runtime SVG generation. A pack that is missing loads nothing and the instance renders stock — see §5.

**Scope is per-instance (administrator), not per-user — decided 2026-08-08.** Nextcloud's own theme selection is per-user, but this follows `nldesign`'s existing model: an admin dropdown in the `theming` section, stored in app config. Per-user selection would break the one-stylesheet model — every pack's rules would need scoping by a body attribute, or delivery would need to vary per session. **If per-user is wanted later it is a different design, not a setting**, and this document does not cover it.

| Config key | Default | Meaning |
|---|---|---|
| `nldesign_icons:pack` | `none` | Active pack id, or `none` for stock Nextcloud |
| `nldesign_icons:pack_revision` | `0` | Bumped on every change — see §3.1 |

### 3.1 Cache-busting is a requirement, not an afterthought

`\OCP\Util::addStyle` appends Nextcloud's app-version cache buster. **That does not change when an administrator switches pack**, so clients keep the previous pack's stylesheet until the app version changes or the browser cache expires.

This is not hypothetical. `theming_customcss` serves its stylesheet at a hardcoded `?v=0` and never busts at all; during this research a change that had applied correctly server-side appeared to have failed for several minutes purely because of it, with selectors matching perfectly and rules absent from the loaded sheet.

**Requirement:** the stylesheet URL must carry a token derived from the selection. Bump `pack_revision` on every write and include it in the emitted URL. A pack switch that a user cannot see is indistinguishable from a pack switch that does not work.

## 4. The projection: identification versus appearance

The compiled stylesheet has two halves with different shapes, different growth, and different failure modes. **Keeping them apart is structural, not stylistic.**

### 4.1 Identification — finds Nextcloud's icons

Nextcloud draws icons three ways, and only one carries a per-icon class:

| Population | Count (34.0.2, Files page) | Hook |
|---|---|---|
| `.material-design-icon` | 58 | per-icon class (`folder-icon`) |
| `.icon-vue` (`NcIconSvgWrapper`) | 41 | **same class for every icon** — no per-icon selector exists |
| `img[src$=".svg"]` | 17 | **no class at all** |

The class-less populations are still addressable, because the geometry itself is the identifier. Across `@mdi/js` v7.4.47, **7,437 of 7,447 path strings are unique** — the ten collisions are genuine aliases — so an attribute selector on `d` identifies an icon exactly:

```css
.material-design-icon:has(> svg > path[d="M10,4H4C2.89,…Z"]) > svg > path,
.icon-vue:has(> svg > path[d="M10,4H4C2.89,…Z"]) > svg > path {
	d: path("<replacement geometry>");
}
```

Header `<img>` uses the same principle on a different attribute: `#header img[src$="/files/img/app.svg"] { content: url(…) }`.

Cost is measured, not assumed: **0.014 ms per `:has()` query** on a 2,214-node DOM. A hundred such rules is under two milliseconds. Performance is not a constraint here.

This half **grows with icon count** and is **coupled to Nextcloud's Material Design version** (§8).

### 4.2 Appearance — one rule, driven by tokens

Substitution keeps a real `<path>` in the DOM rather than flattening to a mask, so SVG presentation properties stay live:

```css
/* Written once. Applies to the substituted set only — see the invariant below. */
<every substituted selector> {
	fill: none;
	stroke: var(--nldesign-icon-color, currentColor);
	stroke-width: var(--nldesign-icon-weight, 2);
	stroke-linecap: var(--nldesign-icon-corner, round);
	stroke-linejoin: var(--nldesign-icon-corner, round);
	vector-effect: non-scaling-stroke;
}
```

This half is **fixed size** regardless of icon count, and it is where the pack becomes token-driven rather than hand-styled. Three tokens control every icon at once:

| Token | Controls | Verified |
|---|---|---|
| `--nldesign-icon-color` | colour | `stroke: var()` resolves; MDI already inherits `currentColor` |
| `--nldesign-icon-weight` | weight | `stroke-width` 1 / 2 / 3 visibly differ |
| `--nldesign-icon-corner` | **corner radius** | `stroke-linejoin: miter` vs `round` visibly sharpens or rounds |

**Corner radius only works on stroke-authored icon sets.** Filled sets (Fluent, Phosphor) bake geometry into merged paths and expose colour only. Lucide (ISC, 2,022 icons) and Tabler outline (MIT, 5,130) ship `fill="none" stroke="currentColor" stroke-width="2"` and therefore expose weight and corner radius natively. **This makes a stroke set the better default for a token-driven pack, whatever the visual target** — a finding that only emerged from testing and that inverts the obvious choice of Fluent.

## 5. Invariants

Each of these is a bug that actually happened during this research, not an invented rule.

1. **Appearance rules apply only to substituted icons — never to a population selector.** A blanket `:has(> svg > path[d])` rule strokes *unsubstituted* filled icons, drawing the outline of a solid shape. This shipped to the live instance and produced fat black checkboxes. Emit the grouped selector from the same fingerprint list.

2. **An unmatched fingerprint renders the stock icon.** Verified. This is what makes host-version drift survivable rather than fatal, and it is the named failure mode: packs degrade to mixed iconography, never to blank or broken icons.

3. **Only vendor or inline SVGs the app itself ships.** Never fetch and inline from `/apps/*` or `/custom_apps/*`. Those belong to third-party apps, and inlining them turns "an app ships an icon" into "an app injects markup into every page of an administrator's session." §7.

4. **Five theme cases, and `default` twice.** Any colour the pack sets must cover `data-theme-default` — which follows the operating system and is what a user who never opened Appearance settings has — in *both* `prefers-color-scheme` branches, alongside the four explicit cases. A stylesheet that covers only the explicit ones silently renders stock for the majority. Guardrail: `infra/skills/nextcloud-ui/ncuictl.py lint`, which fails on exactly this; wire it into the app's checks.

5. **The compiled stylesheet is a public document.** App `css/` is served without authentication, as is `theming_customcss`. Nothing here is sensitive, but write it accordingly — no internal commentary, no instance detail.

6. **Selection is not mutation.** Switching pack writes one config key and bumps a revision. It never modifies files, never touches another app's assets, and never writes below the installed app path.

## 6. Build pipeline

A new generator belongs in the sibling app; the retired profile-token generator
is not a reusable implementation. The research prototype was run end to end
against production: **45 icons, 26.7 KB, 594 bytes per icon, zero unmatched.**

1. **Vendor from npm, pinned**, licence file alongside. All candidates are permissive: Lucide ISC, Fluent MIT, Tabler MIT, Phosphor MIT.
2. **Normalise the grid** to a 24 viewBox (Phosphor ships 256), strip fixed `width`/`height` so CSS sizes them.
3. **Normalise primitives to path data.** Only **57%** of Lucide icons are path-only; the rest use `circle`, `rect`, `line`, `polyline`. CSS `d` accepts path data only, so converting them is mandatory — it took coverage from 57% to **100% of 2,022 icons**.
4. **Strip vendor noise** — Tabler ships a spacer `<path d="M0 0h24v24H0z" fill="none"/>` in every icon; both sets ship classes that would collide.
5. **Resolve the alias table.** MDI vocabulary to target vocabulary is **~99% automatable**: on an 85-icon working set, 42 matched by name and 42 more through a ~40-line alias table (`magnify`→`search`, `cog`→`settings`, `pencil`→`edit`). One table serves every pack.
6. **Emit one stylesheet per pack**, identification and appearance separated per §4.
7. **Lint** the result (invariant 4) before it ships.

**Scale check.** Mapping the **83 icons visible across eight real pages** covers essentially everything a user sees, and is 4% of the 2,023 shipped. That gap is the argument for scoping by inventory rather than by set — and for re-running the inventory per deployment rather than trusting this number.

## 7. Security boundary

**Substitution stays in CSS.** `mask`, `content: url()` and the `d` property introduce no foreign DOM, so the browser's `<img>` sandbox and Nextcloud's CSP both remain intact.

Runtime inlining of SVG — replacing `<img>` with live `<svg>` to gain per-shape control — **is deliberately excluded from v1**. It works, and it is the only way to animate part of a mark, but it removes a hard browser sandbox in exchange for continued CSP correctness. The reference instance's CSP is strong (nonce-based `script-src` with `strict-dynamic`, `connect-src 'self'`), so injected script is blocked — but `style-src` permits `unsafe-inline`, and the icons in question belong to third-party apps.

If inlining is ever added: **inline only vendored assets reviewed at build time**, never anything fetched from `/apps/*` or `/custom_apps/*`, and sanitise with DOMPurify as `NcIconSvgWrapper` itself does.

**Never modify another app's files.** `apps/theming` code-signs 216 files and integrity checking is active by default; replacing a shipped asset raises an admin warning. Everything here overrides through the cascade instead, which is why theme previews are done as a `background-image` override rather than a file swap.

## 8. The real risk: the pack is coupled to the host, not just the icon set

The fingerprints are **Nextcloud's Material Design path data**. That makes a pack version-coupled to the host in a way `nldesign` is not, and it is the single largest maintenance liability.

Drift is already observable across one minor upgrade:

| | NC 33.0.5 | NC 34.0.2 |
|---|---|---|
| `.material-design-icon` | 65 | 58 |
| `.icon-vue` | 39 | 41 |
| `img[src$=".svg"]` | 17 | 17 |

Every *mechanism* survived the upgrade unchanged — fingerprinting, `d` substitution, `src` replacement, `:has()` performance, preview override. What moved was the population, which is exactly what invariant 2 makes survivable.

**Strategy:**

- Ship fingerprints for a **declared, tested Nextcloud range**, and declare a **narrower `<nextcloud max-version>` than `nldesign`**. That divergence is the point of the split; it cannot be expressed from one `info.xml`.
- Outside that range, unmatched fingerprints fall back to stock icons. Degraded, never broken.
- Re-run the inventory each supported release. It is roughly twenty minutes with `ncuictl.py`, and `probe` prints the version its markup facts were established on so a mismatch is visible immediately.
- Pin the MDI version the fingerprints were generated from, and regenerate when Nextcloud's own bundled version moves.

## 9. Packaging

**A sibling repository, not a folder in `nldesign`.**

**Correction, 2026-08-08:** an earlier version of this section rested on the release pipeline deriving the app id from the repository name (`APP_NAME=${GITHUB_REPOSITORY##*/}`). **That argument is void** — the ten release and sync workflows have since been deleted and replaced by a manual-dispatch `package-release.yml` that only uploads a build artifact, so no such coupling exists today. A monorepo is now *less* costly to tool than it was.

The conclusion survives on the stronger argument alone: **the app store models one listing per app id, each with its own supported-version range**, and §8 requires this app to declare a narrower range than `nldesign`. That divergence cannot be expressed from one `info.xml`, and it is the whole reason for the split. Tooling cost was always the weaker half of the case.

Name it for what it contains once the pack list is decided.

**Honest note on size:** the app is `info.xml`, one `Application.php` registering a template listener, a settings panel, a generator script, and compiled CSS. The repository scaffolding — 1,604 lines of workflow YAML and ~22 KB of tooling config — will exceed the source code.

## 10. What is measured, and what is projected

The distinction matters more than usual here, because this session's recurring lesson is that DOM facts expire.

**Measured on a live instance, both engines:** path-data uniqueness; `:has()` matching and cost; CSS `d` substitution; `src` fingerprinting with `content: url()`; `mask` swap; `rx` as a CSS property; stroke weight and corner-radius control; data-URI SVG filters; theme-preview override; primitive normalisation coverage; the icon inventory; DOMPurify preserving `d`; five-case theme scoping.

**Projected, well-founded but not measured:** the prototype ran **45 icons, one pack (Lucide), against one palette (SENERAWA)**. Scaling to the full 83-icon visible set, to a second pack, and to a different profile's `currentColor` is extrapolation. So is the claim that 83 icons covers a *different* deployment's visible surface — that number should be re-measured per instance, not inherited.

**Not addressed:** per-user selection (§3), runtime inlining (§7), sounds and email templates (§2), and the visual-target decision — Fluent 2 neutral chrome versus classic purple chrome — which is a product judgement, not a technical constraint.
