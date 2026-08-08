---
sidebar_position: 10
---

# Fluent / Teams-Look — Evidence Gates Before Roadmapping

**Status:** **All four gates run.** A, B, C passed on a live Nextcloud 33.0.5 instance; D returned a split verdict. **Roadmapping is unblocked** — see §5 for what to build.

**Evidence snapshot:** 2026-08-08

> **Historical experiment notice.** These gates measured a live Nextcloud
> instance and the repository's pre-overhaul CSS plumbing. References to
> `defaults.css`, `overrides.css`, `element-overrides.css`, radius/motion
> forwarding, or a broad seven-layer stack are superseded by the bounded
> three-layer architecture. Preserve the measurements as research; re-design
> and re-run any proposed feature against the current runtime before adding it
> to the roadmap. Claims that an automatic Theming bridge is already available
> are also superseded; the current bridge is manual-only.

## Results — tested on cloud.opus95.com (Nextcloud 33.0.5)

Applied via `theming_customcss`, scoped to `@media (min-width: 2400px)` so ordinary sessions were unaffected. **Restored byte-exact afterwards**; temporary test user deleted; the infra repo was not modified.

| Gate | Result | Key evidence |
|---|---|---|
| **A** — surface/ground split | **PASS**, with a new requirement | `bodyBg: rgb(255,255,255)` achieved |
| **B1** — mask swap on MDI class | **PASS** | svg hidden, `::after` mask applied, visible in screenshot |
| **B2** — app-menu `<img>` swap | **PASS**, but §13's selector is wrong | `content: url()` applied via `src` fingerprint |
| **B3** — path-data fingerprint | **PASS** | matched exactly 5 of 5 folder icons; DOMPurify preserves `d` |
| **B5** — `:has()` performance | **PASS**, decisively | **0.018 ms/query** on a 2,428-node DOM |
| **C** — `side_menu` composition | **PASS**, already in production | v5.3.0 enabled and configured on this instance |
| **D** — v2 plumbing cost | **RUN — split verdict** | motion is 2 lines; type scale is blocked on a missing baseline |

**Icons are not a blocker.** Three things the plan got wrong, all now corrected below.

### 1. `--color-main-background` *is* overridable — but needs theme scoping

Gate A assumed this might be unreachable. It is reachable: Theming's generated sheets sit at cascade indices **26–33**, `theming_customcss` at **36**, so a later `:root` rule wins — measured `rootVar: #ffffff`.

**The catch:** Nextcloud puts `data-theme-light` / `data-theme-dark` on **`body`**, and token sets scope to those attributes. At body level that attribute selector shadows the inherited `:root` value, so a `:root`-only layer loses (`bodyVar: #faf7f0`). Matching the specificity fixes it:

```css
[data-theme-light], [data-theme-dark] { --color-main-background: #ffffff !important; }
```

> **Requirement for the design-language layer: emit theme-scoped rules, not only `:root`.** A `:root`-only layer silently loses to any theme-scoped token set — which SENERAWA is, and any dark-mode-aware set will be.

### 2. The app-menu selector in §13 does not exist in NC 33

`#header .app-menu-entry__icon img` matched **zero** elements. The header has 15 `<img>` and they carry **no class**. They do have distinctive `src` paths, so the fingerprint principle just moves from class to `src`:

```css
#header img[src$="/files/img/app.svg"] { content: url("data:image/svg+xml;base64,…") !important; }
```

### 3. The `.icon-vue` ceiling is gone, with live evidence

`:has(> svg > path[d="…"])` matched exactly the five folder icons, and `path[d]` survived DOMPurify untouched. On the real Files page: **65 `.material-design-icon` (per-icon class) vs 39 `.icon-vue` (fingerprint) — 63/37**, friendlier than the ~50/50 file-count estimate, and fingerprinting covers both anyway.

Full detail, including incidental findings (two bundled copies of nextcloud-vue; Nextcloud does not sanitise custom CSS; Fluent icons inline as 212–1,237 B data URIs), is in the session results file.

### 4. Gate D — the plumbing splits in two, and one half is blocked

Measured against the repository, not estimated.

**The token layer barely participates today.** Token sets define **1,912** distinct `--nldesign-*` tokens; the mapping layers consume **36**; the overlap — tokens that actually reach Nextcloud — is **35**. **1,877 tokens, 98% of 476 KB of token CSS, reach nothing.** This is the strongest available evidence for `roadmap.md`'s position that "the number of token files or settings controls is not a success metric."

It also means the regression surface for new forwarding is far smaller than feared: token sets are not currently driving typography, density, or motion at all.

**Motion — two lines, do it.** `--nldesign-animation-quick` (100ms) and `--nldesign-animation-slow` (300ms) already exist in `defaults.css`, and Nextcloud's variables are `--animation-quick` / `--animation-slow`. The names already align; only the forwarding is missing (`grep` in `theme.css`: zero hits). A Fluent layer would override 300ms → 200ms, which is the intended behaviour.

**Type scale — blocked on a missing baseline.** Not a mechanical change:

| | |
|---|---|
| Token sets defining semantic `--nldesign-font-size-*` | **6 of 40** |
| `defaults.css` definitions for that scale | **0** |
| Value spread for `font-size-md` | 16px (×4), 18px (×1), 24px (×1) |
| Value spread for `font-size-lg` | 18px (×3), 20px (×2), 36px (×1) |

Adding `--default-font-size: var(--nldesign-font-size-md)` today would give six sets one of three different sizes and give the other **34 sets nothing at all** — the variable would resolve empty and take Nextcloud's base type size with it.

> **So the forwarding cannot land until `defaults.css` carries the full semantic scale.** That is the shared-plumbing work, and it is a *design* task — choosing a baseline scale — not a mechanical one. It is also the right order: define the vocabulary, then forward it, then let the Fluent layer override it.

**One latent bug found on the way.** `css/tokens/noaberkracht.css:44` reads `--nldesign-font-size-3xs: 10x;` — missing the `p`, an invalid CSS length. Harmless today because nothing consumes it; it becomes a live rendering bug the moment font-size forwarding lands. Worth fixing whether or not this work proceeds.

**Revised v2 estimate:** motion is trivial. Type scale is *a baseline-scale design decision plus forwarding*, not "a week of regression testing across 39 sets" — because 34 of those sets have nothing to regress. Density and stroke width need the same treatment as type: check whether a baseline exists before promising a forwarding line.

### 5. What to build, now that the gates have reported

Ordered by evidence, not appetite. Everything here is inside `nldesign`; the icon work stays out per the scope boundary.

**Ship first — nothing blocks it.**

1. **`css/shape/fluent.css`, radii only, behind `nldesign:design_language`.** Six radius variables, already forwarded (`theme.css:46-51`), default `nldesign` so existing instances are untouched. Sharp-vs-rounded is the most legible Fluent signal, and this is days.
2. **Motion, in the same change.** Two forwarding lines in `theme.css` for `--animation-quick` / `--animation-slow`; the `--nldesign-*` tokens already exist.
3. **Surface/ground split — but emit theme-scoped rules.** Gate A's requirement: `[data-theme-light], [data-theme-dark]`, not `:root` alone, or the layer silently loses to any dark-mode-aware token set.

**Then, and only in this order.**

4. **Define the semantic type scale in `defaults.css`.** A design decision — pick the baseline — not a forwarding line. 34 of 40 sets currently have nothing (Gate D).
5. **Forward type and density** once (4) exists.
6. **Fix `noaberkracht.css:44`** (`10x` → `10px`) before (5) makes it live.

**Do not build.**

- Fluent's neutral surface scale: drops Amsterdam, Leiden and Rotterdam below 4.5:1 (§0).
- Fluent dark mode: every light-mode brand fails on `#292929`, including Fluent's own. Needs per-set lightened ramps first.
- Fluent spacing and shadows: Nextcloud exposes no contract for either; reaching them means element CSS, which the scope boundary excludes.

**Still a judgement call, not a gate:** whether an NL Design System app should carry a Fluent option at all, and how to label the loss of NLDS conformance in the admin UI. §4 below.

---

## Original plan (below) — retained for method and for the gate definitions

**Companion to:** [Fluent / Teams-look feasibility research](./teams-look.md) — that document carries the analysis; this one carries only what still needs a running Nextcloud to answer.

**Method:** `docs/roadmap.md` plans by evidence gates rather than calendar promises. This applies the same rule to Fluent work: **no sequencing, no estimates, and no roadmap entries until these gates report.**

**Relationship to Gate 0.** `roadmap.md` §"Gate 0" and `docs/gate-0-baseline.md` establish the *architecture* baseline — reproducible build, agreed branch, compatibility matrix — and gate the whole project. These are a **separate, feature-scoped scheme**, lettered A–D specifically so they do not read as continuing that numbering.

They are **independent of Gate 0 and can run before it.** Every gate here is a throwaway experiment in scratch CSS or a stock app install; none writes committed code, changes the build, or depends on the architecture refactor landing. What they *cannot* do is authorise implementation: **Fluent work still enters the roadmap behind Gate 0, like everything else.** Running these early is de-risking, not queue-jumping.

## 0. What is already settled — do not re-test

Eight things were determined from source or by measurement. They are findings, not gates. Re-running them wastes the budget these gates need.

| Finding | Evidence |
|---|---|
| Fluent brand is `#5b5fc7` light / `#4f52b2` dark | `teamsLightTheme.colorBrandBackground`, `@fluentui/tokens` v1.0.0-alpha.23 (MIT) |
| Fluent's `colorNeutralStroke1` fails 3:1 in both modes (1.53 / 2.53) | Measured. Use `colorNeutralStrokeAccessible` for `--color-border-maxcontrast` |
| Fluent's tinted surfaces drop Amsterdam, Leiden, Rotterdam below 4.5:1 | Measured against `token-sets.json` brand values |
| Every light-mode brand fails on Fluent's dark surface, including Fluent's own | Measured. Dark mode needs per-set lightened ramps |
| **Radius forwarding already works** | `css/theme.css:46-51` (six variables) + `css/overrides.css:44` |
| **Type scale, density, stroke, motion forwarding is entirely absent** | `--default-font-size`, `--default-line-height`, `--font-weight-*`, `--default-clickable-area`, `--border-width-input`, `--animation-*` defined nowhere in `css/` |
| MDI `d` attributes are 99.9% unique (7,437 distinct of 7,447) | `@mdi/js` v7.4.47. Basis for fingerprint selectors |
| MDI→Fluent mapping is ~99% automatable | 42 by name + 42 by a ~40-line alias table, on an 85-icon set |
| `LoadAdditionalEntriesEvent` cannot override another app's icon | Bare marker class, NC 31+ |
| `side_menu` is maintained across NC 18–34 | App store, v6.0.1, updated within the last month |

## 1. The gates

Four gates, independent unless noted. **Roughly a week total.** Each is written so the result is unambiguous and so a failure names its own consequence.

---

### Gate A — Can tier 1 produce the surface/ground split?

**Why it gates everything.** Teams' look depends on white panes on a grey ground. `css/theme.css:24` deliberately leaves `--color-main-background` to Nextcloud Theming, so the token layer may be unable to express it. If it cannot, the visual payoff of the whole Fluent direction is smaller than the research assumes and every downstream estimate is wrong.

**The specific obstacle**, already located: `css/theme.css:24` reads `/* --color-main-background: Managed by Nextcloud theming */`. Every other surface variable is forwarded; this one is deliberately not. So the question is whether the token layer can claim it back.

**Method — two runs, in this order.**

*Run 1, token layer.* Add to a scratch token file and load it in the normal cascade position:

```css
:root {
	--color-main-background: #ffffff !important;   /* Fluent Bg1 */
	--color-background-hover: #f5f5f5 !important;  /* Bg1Hover  */
	--color-background-dark: #f0f0f0 !important;   /* Bg4       */
	--color-background-darker: #e6e6e6 !important; /* Bg6       */
}
```

Load **Files list, Settings, and a modal dialog**. Observe whether panes separate from the ground, or whether Nextcloud Theming's own value wins. Check both a fresh instance and one with a Theming background already set — they may behave differently.

*Run 2, only if run 1 loses.* Set the equivalent through `ThemingService`'s background field and re-check the same three surfaces.

**Timebox:** half a day.

| Result | Consequence |
|---|---|
| Run 1 wins | Best case. The design-language layer is self-contained; tier 1 delivers what the research claims |
| Run 1 loses, run 2 wins | Proceed, but the setting must write instance branding through the Theming bridge — a coupling the research does **not** assume, and one that collides with "selection is not mutation" (`architecture.md` invariant 5). Revisit both |
| Both lose | **Reassess the direction.** Fluent without surfaces is radius and type only, which may not justify a settings control |

---

### Gate B — Does the icon technique work? *(the one everyone is worried about)*

**Why it gates.** Determines whether icons are a blocker, and whether the answer ships as an app. Runs entirely in a scratch CSS file — no app, no build step, no commitment. Full specimen ladder in [research §13](./teams-look.md).

**Method.** Vendor a handful of Fluent SVGs from `@fluentui/svg-icons` (MIT), paste rules into Theming → Custom CSS, and work down the ladder. **Stop as soon as you have a decision.**

| # | Specimen | Tests | Expected |
|---|---|---|---|
| B1 | Folder icon, Files list | `mask` swap on a per-icon MDI class | Works |
| B2 | App menu icon | `content: url()` on `<img>` — **check Firefox and Chromium** | Likely works |
| B3 | **A `NcIconSvgWrapper` icon via `:has(> svg > path[d="…"])`** | **DOMPurify passing `d` through unaltered** | Should work — **this is the load-bearing one** |
| B4 | Action icon inside `NcActions` | Popover teleported to `<body>` | Unknown |
| B5 | `:has()` cost with ~50 long attribute selectors, large DOM | Performance, not correctness | Unknown |
| B6 | Diagnostic: outline every `.icon-vue` in magenta, **one screenshot** | How much is unswappable if B3 fails | — |

**Timebox:** one to two days.

| Result | Consequence |
|---|---|
| B1–B3 pass, B5 acceptable | **Icons are not a blocker.** Proceed to inventory; it ships as an app |
| B3 fails on DOMPurify | Fingerprinting is dead. Fall back to JS patching ([research §14.3](./teams-look.md)) — same mapping table, heavier delivery |
| B5 too slow | Same fallback. The table survives; only the mechanism changes |
| B1 fails | Masking is dead entirely. Icons are blocked — ship the design-language layer and close the question |
| B2 fails but B1 passes | Nav rail unreachable by CSS; JS can still rewrite `<img src>` |

**Take the B6 screenshot early even if everything passes.** It is the cheapest way to see the true remainder.

---

### Gate C — Does `side_menu` compose with this app?

**Why it gates.** The rail is the actual "Teams" signal and is out of scope for `nldesign` by decision. `side_menu` supplies it — but this app's `#header` overrides in `element-overrides.css` assume Nextcloud's own header. If they fight, the recommended pairing does not exist and the Teams-look story loses its centrepiece.

**Method.** Install `side_menu` + `AppOrder`, enable a token set, and look. Note whether `nldesign:show_menu_labels` interacts.

**Timebox:** half a day.

| Result | Consequence |
|---|---|
| Compose cleanly | The Teams-look recipe is a README section and zero structural CSS |
| Fight, fixable by scoping | Add a scoping rule; small |
| Fight badly | Either the rail story needs a different app, or `element-overrides.css` needs the §9.1 cleanup first |

---

### Gate D — What does the v2 plumbing actually cost?

**Why it gates estimates, not viability.** Radii work today; type scale, density, stroke and motion need a new `--nldesign-*` token plus a forwarding line each. That touches shared files every token set depends on. **Only run this if Gate A passes** — it is wasted work otherwise.

**Method.** Add forwarding for one variable end to end — `--default-font-size` is the representative case — and check all 40 token sets for regressions. Extrapolate.

**Timebox:** one day.

| Result | Consequence |
|---|---|
| Clean, no regressions | v2 is a week. Roadmap it as its own change |
| Regressions across sets | v2 is materially more expensive. **Ship v1 radii-only and stop**; radius is the strongest Fluent signal anyway |

## 2. Sequencing

```
Gate A ──┬── pass ──> Gate D
         └── fail ──> reassess direction; A is the cheapest kill
Gate B ── independent, run in parallel — answers the loudest open question
Gate C ── independent, half a day
```

**A and B first, in parallel.** A can kill the direction, B answers what everyone is asking. C is cheap and can slot anywhere. D only after A passes.

**Roughly a week.** At the end you can roadmap with evidence instead of assumptions.

## 3. Decisions each gate unblocks

| Question | Gated on |
|---|---|
| Is a Fluent design-language setting worth building? | A |
| Ships as v1 radii-only, or full shape layer? | A + D |
| Are icons a blocker? | B1–B3 |
| Do icons ship as an app or need JS? | B3 + B5 |
| Is the documented Teams-look recipe real? | C |
| Do we need a second app at all? | B + C |
| **Do we create a sibling repo?** | **B + C — and not before.** If B says icons are not worth it and C says `side_menu` composes, the sibling has nothing to hold. See [research §9.2](./teams-look.md) |

## 4. Two decisions no gate can settle

These are judgement, and testing will not resolve them. Decide them deliberately alongside the results.

**Should an NL Design System theming app offer a Fluent option at all?** [Research §12](./teams-look.md) makes it defensible — the layer carries shape only, no Microsoft brand colour, and the organisation's identity still supplies every colour. But it remains a product-identity question, not a technical one.

**NL Design System conformance.** NLDS house style is sharp and flat; Fluent is rounded and soft. `Utrecht × Fluent` conforms to neither. Where NLDS compliance is contractual — plausible in Dutch public-sector procurement — this setting breaks it by design. Defensible as a deliberate admin choice, but the admin UI must say so, and it should default off.

## 5. Do not create the sibling repo yet

If a second app is needed it should be a **sibling repository, not a monorepo folder** — the release pipeline derives the app name from the repository name (`APP_NAME=${GITHUB_REPOSITORY##*/}`, `release-stable.yaml:29`) and reads `appinfo/info.xml` from the root, so a monorepo means rewriting roughly 1,000 lines of release YAML. Reasoning in [research §9.2](./teams-look.md).

**But creating it now is premature.** The rail probably comes from `side_menu`, and components are not viable at all — so the sibling would realistically hold **only the icon work**, which is precisely what Gate B decides. Create it when Gate B says yes, and name it for what it actually contains.

## 6. Independent of all gates

Two things are worth doing now, whatever the results.

**File the upstream issue.** One line in `NcIconSvgWrapper` emitting a per-icon class or `data-icon` attribute would give every theming author a stable hook and make fingerprinting unnecessary. Long lead time, so it unblocks nothing today — but it costs an issue and a small PR, and it is the only permanent fix.

**Delete `.content[data-v-1f87d811]`** from `css/element-overrides.css`. A Vue scoped-style hash that will silently stop matching whenever that component is recompiled. Unrelated to Fluent; it is a live time bomb regardless.
