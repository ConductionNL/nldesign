---
sidebar_position: 11
---

# Making Nextcloud Look Like Microsoft Teams — Feasibility Research

**Status:** research, no implementation

**Evidence snapshot:** 2026-08-08, working tree on `senerawa-token-set` at 720e7a5

**Question asked:** "Get Nextcloud to look like MS Teams. Research how."

> **Stale implementation references, 2026-08-08.** The CSS layer was restructured while this document was being written. `defaults.css`, `overrides.css`, `element-overrides.css` and `utrecht-bridge.css` **no longer exist**; the runtime stack is now three layers (`fonts` → `tokens/{profile}` → `compatibility/nextcloud-core-v1`) and the shared core contract projects only font and primary interaction roles. **Every line-number citation below predates that change**, as do the claims about `--color-main-background` being unforwarded, the radius mapping at `theme.css:46-51`, the 1,912-token census, broad Nextcloud support, and an already-built automatic Theming bridge. The current app declares Nextcloud 32–34 but still requires packaged compatibility evidence, and exposes only a manual Theming hand-off. The *live Nextcloud findings* — contrast measurements, Fluent values, icon reachability, and gate results — remain research evidence. Re-anchor and re-test every repository-facing proposal before acting on it.

**Gates A, B and C have since been run against a live Nextcloud 33.0.5 instance and all passed** — see **[teams-look-gates.md](./teams-look-gates.md)**, which is the entry point and carries the measured results. Three claims in this document were corrected by that testing and are flagged inline below: `--color-main-background` is overridable (§5), the app-menu selector does not exist in NC 33 (§13/§14.4), and the `.icon-vue` ceiling is retired with live evidence (§7.5).

**Scope assumed:** the whole Nextcloud shell, instance-wide — because that is what this app themes. If the real intent is "Talk dressed as Teams," §6 points at entirely different selectors and most of this document does not apply. Say so and this gets rewritten.

## 1. Short answer

"Look like Teams" is four separate problems wearing one coat. **Only the first belongs in this app** — navigation patterns, icons, and components are out of scope for `nldesign` by decision (§9) and belong in a separate app.

| Tier | What it means | Where it belongs | Cost |
|---|---|---|---|
| 1. Radius, type scale, density, motion | 4px radii, 14px base, Fluent easings — **no brand colour** | **`nldesign`** — a design-language layer, not a token set (**§12**) | Radii: days. The rest: +1 week of shared plumbing (§12.5) |
| 1b. Instance branding | Logo, favicon, background images | **`nldesign`** — already built, via the Theming bridge | Hours |
| 2. Shell layout | Left vertical app rail, list pane, thin top bar, centred command box | **Separate app — and it already exists: [`side_menu`](https://apps.nextcloud.com/apps/side_menu)** | Zero if adopted; high if rebuilt |
| 3. Component shape/behaviour | Message bubbles, pill toggles, compose box, presence dots | **Nowhere** — needs Talk's own markup | Not viable as CSS |
| 4. Icons | Fluent icon set replacing Nextcloud's, folder and filetype icons | **Separate app — and it can ship as an app, no container needed** (§14: path-data fingerprinting reaches even the class-less icons) | Run the §13 spike first: 1–2 days, settles "blocker?" |

Tier 2 is what actually makes someone say *"oh, Teams."* **The signature is the left rail, not the purple** — and the separate app the boundary calls for is already written and maintained by someone else.

**Do not ship tier 1 as a `fluent` token set carrying Microsoft's purple.** Ship it as a **design-language setting** — Fluent's shape, density, and motion applied over whichever Dutch government identity the admin already chose (§12). `Gemeente Utrecht × Fluent` = Utrecht's red in Teams' ergonomics, which is what a migrating organisation actually wants and what a purple profile cannot give them.

The one-line version: **Fluent design-language setting here + `side_menu` alongside it ≈ a week, on maintained foundations.** Full recommendation in §11, layer design in §12.

## 2. Prior art — has anyone done this?

**Scope of this claim:** searched the Nextcloud app store customization category, the community theme list, GitHub theme topics, and the Nextcloud forum. **No Teams-look Nextcloud theme appears in any of them.** That is a negative result from four searches, not proof of absence — but the absence is consistent across every place one would expect to find it. What does exist:

**Themes** — all colour-scheme only, no layout change: Breeze Dark, Dracula, Material Light/Dark, Elemental, Phex, UMECloud, Pride ([theme list](https://github.com/Poussinou/nextcloud-theme-list), [Breeze Dark](https://github.com/mwalbeck/nextcloud-breeze-dark), [Dracula](https://draculatheme.com/nextcloud)). None mimics another product's shell. None is Fluent.

**Layout apps** — the closest thing to tier 2, and all off-the-shelf:

| App | What it does | Status |
|---|---|---|
| [Custom menu (`side_menu`)](https://apps.nextcloud.com/apps/side_menu) | Moves app navigation into a **vertical left sidebar**. Simon Vieille. | v6.0.1, supports NC 18–34, updated within the last month. Actively maintained. |
| [AppOrder](https://apps.nextcloud.com/apps/apporder) | Drag-and-drop app ordering | Commonly paired with `side_menu` |
| [Desktop Workspace (`desktop_workspace`)](https://apps.nextcloud.com/apps/desktop_workspace) | Opens apps in a desktop-style windowed workspace | Alternative shell metaphor, not Teams-like |
| [`classic_sidemenu`](https://github.com/mtraeger/classic_sidemenu) | Restores the old ownCloud-era side menu | Fork lineage, less current |

**Community demand exists but has no official answer.** [UX: Apps in a sidebar](https://help.nextcloud.com/t/ux-apps-in-a-sidebar/247443) proposes exactly the vertical labelled rail, with users noting it is "easier to navigate, especially if you have a lot of apps installed." **No maintainer or designer replied. No official plan.** A parallel thread, [App Navigation Redesign #59888](https://github.com/nextcloud/server/issues/59888), tracks the same territory upstream.

**The one strong finding:** `side_menu` delivers tier 2 today, as a separate app, without this repository writing a line of structural CSS. That reframes the whole problem — see §11.

**Context worth knowing.** Nextcloud markets itself as [the open source answer to Microsoft Teams](https://nextcloud.com/blog/nextcloud-talk-our-open-source-answer-to-microsoft-teams/), and 2025–26 has real sovereign-migration pressure behind that: Denmark replacing M365 across public administration, Schleswig-Holstein moving 25,000 workstations, [Nextcloud Workspace with IONOS](https://nextcloud.com/blog/nextcloud-workspace-microsoft-365-alternative-by-nextcloud-and-ionos/), and — directly adjacent to this project — SURF's Nextcloud-based "SURF Works" pilot for Dutch universities, running since July 2026. The demand for "familiar to a Teams user" is real and Dutch-government-shaped. Nextcloud's own answer to it so far has been *integration* (Sendent, Exchange connector) and *feature parity*, never *visual mimicry*.

So: not done, plausibly wanted, and the layout half is already solved by someone else.

## 3. Where the Fluent numbers come from

Not from memory or a blog. Microsoft ships the Teams themes as real exports in [`@fluentui/tokens`](https://github.com/microsoft/fluentui) (**MIT**, v1.0.0-alpha.23). Dumped `teamsLightTheme` / `teamsDarkTheme` / `teamsHighContrastTheme` directly — 459 tokens each.

This settles a live ambiguity. Two brand purples circulate in secondary sources: `#6264A7` (classic Teams) and `#5B5FC7` (Fluent 2 / Teams v2). **`teamsLightTheme.colorBrandBackground` resolves to `#5b5fc7`.** Use that. Dark resolves to `#4f52b2`.

### Teams light

```
colorBrandBackground        #5b5fc7    colorNeutralBackground1        #ffffff
colorBrandBackgroundHover   #4f52b2    colorNeutralBackground1Hover   #f5f5f5
colorBrandBackgroundPressed #383966    colorNeutralBackground1Selected #ebebeb
colorBrandBackgroundSelected #444791   colorNeutralBackground2        #fafafa
colorBrandBackground2       #e8ebfa    colorNeutralBackground3        #f5f5f5
colorBrandBackground2Hover  #dce0fa    colorNeutralBackground4        #f0f0f0
colorBrandForeground1       #5b5fc7    colorNeutralBackground5        #ebebeb
colorBrandForeground2       #4f52b2    colorNeutralBackground6        #e6e6e6
colorBrandForegroundLink    #4f52b2    colorNeutralBackgroundInverted #292929
colorBrandForegroundLinkHover #444791  colorSubtleBackgroundHover     #f5f5f5
colorBrandStroke1           #5b5fc7    colorSubtleBackgroundPressed   #e0e0e0
colorBrandStroke2           #c5cbfa

colorNeutralForeground1     #242424    colorNeutralStroke1            #d1d1d1
colorNeutralForeground2     #424242    colorNeutralStroke2            #e0e0e0
colorNeutralForeground3     #616161    colorNeutralStroke3            #f0f0f0
colorNeutralForeground4     #707070    colorNeutralStrokeAccessible   #616161
colorNeutralForegroundOnBrand #ffffff  colorNeutralStrokeDisabled     #e0e0e0
colorNeutralForegroundDisabled #bdbdbd

colorPaletteRedForeground1    #bc2f32  colorPaletteRedBackground3     #d13438
colorPaletteGreenForeground1  #0e700e  colorPaletteGreenBackground3   #107c10
colorPaletteYellowForeground1 #817400  colorPaletteYellowBackground3  #fde300
colorPaletteDarkOrangeForeground1 #c43501  colorPaletteDarkOrangeBackground3 #da3b01
```

### Teams dark

```
colorBrandBackground        #4f52b2    colorNeutralBackground1        #292929
colorBrandBackgroundHover   #5b5fc7    colorNeutralBackground1Hover   #3d3d3d
colorBrandBackground2       #2f2f4a    colorNeutralBackground2        #242424
colorBrandForeground1       #7f85f5    colorNeutralBackground3        #1f1f1f
colorBrandForegroundLink    #7f85f5    colorNeutralBackground4        #141414
colorBrandStroke1           #7f85f5    colorNeutralBackground5        #0a0a0a

colorNeutralForeground1     #ffffff    colorNeutralStroke1            #666666
colorNeutralForeground2     #d6d6d6    colorNeutralStroke2            #525252
colorNeutralForeground3     #adadad    colorNeutralStroke3            #3d3d3d
colorNeutralForeground4     #999999    colorNeutralStrokeAccessible   #adadad

colorPaletteRedForeground1  #e37d80    colorPaletteGreenForeground1   #54b054
```

### Shape, type, motion (identical light and dark)

```
borderRadiusNone   0        fontSizeBase100  10px   lineHeightBase100  14px
borderRadiusSmall  2px      fontSizeBase200  12px   lineHeightBase200  16px
borderRadiusMedium 4px      fontSizeBase300  14px   lineHeightBase300  20px
borderRadiusLarge  6px      fontSizeBase400  16px   lineHeightBase400  22px
borderRadiusXLarge 8px      fontSizeBase500  20px   lineHeightBase500  28px
borderRadiusCircular 10000px fontSizeBase600 24px   lineHeightBase600  32px

fontWeight Regular 400 / Medium 500 / Semibold 600 / Bold 700
strokeWidth Thin 1px / Thick 2px / Thicker 3px / Thickest 4px
spacing XS 4 / S 8 / M 12 / L 16 / XL 20  (horizontal and vertical agree)

shadow2  0 0 2px rgba(0,0,0,.12), 0 1px 2px rgba(0,0,0,.14)
shadow4  0 0 2px rgba(0,0,0,.12), 0 2px 4px rgba(0,0,0,.14)
shadow8  0 0 2px rgba(0,0,0,.12), 0 4px 8px rgba(0,0,0,.14)
shadow16 0 0 2px rgba(0,0,0,.12), 0 8px 16px rgba(0,0,0,.14)
                                  (dark: alphas .24 / .28)

durationFaster 100ms   durationNormal 200ms
curveEasyEase cubic-bezier(.33,0,.67,1)   curveDecelerateMid cubic-bezier(0,0,0,1)
```

Two things fall out immediately. **Fluent's `durationFaster`/`durationNormal` are exactly Nextcloud's `--animation-quick: 100ms` / `--animation-slow: 200ms`** — free alignment. And **`borderRadiusMedium: 4px` equals Nextcloud's `--border-radius-small: 4px`**, so the radius scale lands close without fighting.

## 4. Contrast audit — Fluent has the same border bug this repo already fixed once

Measured every pair this profile would actually use (WCAG 2.2, 4.5:1 text, 3:1 non-text UI). Almost everything passes with headroom. **One thing fails, in both modes:**

```
FAIL  1.53:1  (floor 3)  colorNeutralStroke1 #d1d1d1 on colorNeutralBackground1 #ffffff
FAIL  2.53:1  (floor 3)  colorNeutralStroke1 #666666 on colorNeutralBackground1 #292929
```

This is *the same failure, at almost the same ratios*, that `css/tokens/senerawa.css` already documents and fixes:

> "The originals measured 1.28:1 (light) and 2.48:1 (dark) — both under the 3:1 floor, i.e. effectively invisible to anyone who needed to see them."

Fluent is not being careless — it reserves `colorNeutralStroke1` for decorative dividers and provides **`colorNeutralStrokeAccessible`** (`#616161` light / `#adadad` dark) for strokes that must be perceivable. Both pass comfortably:

```
PASS  6.19:1  colorNeutralStrokeAccessible #616161 on #ffffff
PASS  6.48:1  colorNeutralStrokeAccessible #adadad on #292929
```

**The mapping rule that follows:** Nextcloud's `--color-border-maxcontrast` must take `colorNeutralStrokeAccessible`, never `colorNeutralStroke1`. Copying Fluent's neutral scale positionally into Nextcloud's border variables reproduces the invisible-border bug this project has already been burned by.

Everything else measured clean. Selected results:

| Pair | Light | Dark |
|---|---|---|
| Body text on surface | 15.52:1 | 14.55:1 |
| Muted text (`Foreground3`) on surface | 6.19:1 | 6.48:1 |
| Link (`BrandForegroundLink`) on surface | 6.60:1 | 4.56:1 |
| White on brand background | 5.38:1 | 6.60:1 |
| Brand foreground on `BrandBackground2` tint | 4.53:1 | — |
| Error / success / warning text | 5.85 / 6.28 / 4.74 | 5.20 / 5.34 / — |

Three pairs sit close to the floor and deserve headroom before shipping: dark link `#7f85f5` at **4.56:1**, light warning `#817400` at **4.74:1**, and light brand-on-tint at **4.53:1**. Per the repo's own standard — "a ratio of exactly 3.00 fails as soon as antialiasing or a display profile shifts it" — nudge these rather than ship them on the line.

High-contrast mode is also available (`teamsHighContrastTheme`: `#000000` background, `#ffffff` text and strokes, `#ffff00` links) if a third mode is ever wanted.

## 5. Tier 1 mapping — Fluent → `--nldesign-*` → Nextcloud

**Read this section as the full inventory of what Fluent *could* reach, then apply the §12 split to it:** the shape rows become the design-language layer, and every brand-colour row stays with the organisation's token set. The colour mapping below is what you would need *if* you shipped Fluent's palette — which §12 recommends against. It is kept because it is also the map of what the neutral surface scale would touch if it is ever added, and because the `--color-border-maxcontrast` defect it uncovers is real today regardless.

The app's cascade is `fonts → defaults → tokens/{org} → utrecht-bridge → theme → overrides → element-overrides`. `css/theme.css` already forwards `--nldesign-*` into Nextcloud's variables, so a shape-only layer needs no new plumbing beyond its own file and config key.

Verified surface: **24 distinct Nextcloud `--color-*` variables** are mapped, and all 24 live in `css/theme.css:12-43` — not in `overrides.css`, which contains only 4 and consumes rather than defines them. (`project.md`'s layer table attributes this to layer 6; the code puts it in layer 5. Minor doc drift, worth a one-line fix.)

| Nextcloud variable | `--nldesign-*` | Fluent light | Fluent dark |
|---|---|---|---|
| `--color-primary` / `--color-primary-element` | `color-primary` | `#5b5fc7` | `#4f52b2` |
| `--color-primary-hover` / `-element-hover` | `color-primary-hover` | `#4f52b2` | `#5b5fc7` |
| `--color-primary-text` / `-element-text` | `color-primary-text` | `#ffffff` | `#ffffff` |
| `--color-primary-light` / `-element-light` | `color-primary-light` | `#e8ebfa` | `#2f2f4a` |
| `--color-primary-element-light-hover` | `color-primary-light-hover` | `#dce0fa` | `#383966` |
| `--color-primary-element-light-text` | (= primary) | `#5b5fc7` | `#7f85f5` |
| `--color-main-text` | `color-text` | `#242424` | `#ffffff` |
| `--color-text-maxcontrast` | `color-text-muted` | `#616161` | `#adadad` |
| `--color-text-light` | `color-text-light` | `#ffffff` | `#ffffff` |
| `--color-background-hover` | `color-background-hover` | `#f5f5f5` | `#3d3d3d` |
| `--color-background-dark` | `color-background-dark` | `#f0f0f0` | `#1f1f1f` |
| `--color-background-darker` | `color-background-darker` | `#e6e6e6` | `#141414` |
| `--color-border` | `color-border` | `#e0e0e0` (`Stroke2`) | `#525252` |
| `--color-border-dark` | `color-border-dark` | `#d1d1d1` (`Stroke1`) | `#666666` |
| `--color-border-maxcontrast` | `color-border-dark` | **`#616161`** (`StrokeAccessible`) | **`#adadad`** |
| `--color-error` | `color-error` | see caveat | see caveat |
| `--color-warning` | `color-warning` | `#da3b01` | `#da3b01` |
| `--color-success` | `color-success` | `#107c10` | `#107c10` |
| `--color-info` | `color-info` | `#5b5fc7` | `#7f85f5` |

Plus shape and type, which are ordinary `--nldesign-*` values:

```
--nldesign-border-radius         4px    (borderRadiusMedium; NC --border-radius-small is also 4px)
--nldesign-border-radius-small   2px
--nldesign-border-radius-large   6px
--nldesign-border-radius-rounded 8px
--nldesign-border-radius-pill    10000px → clamp to 100px, NC's own value
--nldesign-font-family           see §8
```

**Two blockers inside tier 1, both real:**

**`--color-border-maxcontrast` currently aliases `--nldesign-color-border-dark`** (`css/theme.css:43`). One `--nldesign-*` token is feeding two Nextcloud variables with different contrast obligations. For the Fluent palette these must diverge — `#d1d1d1` for decorative, `#616161` for accessible. This needs a new `--nldesign-color-border-maxcontrast` token in `defaults.css`, which is a change to shared plumbing, not a self-contained profile file. **This is the one code change tier 1 cannot avoid.**

**`--color-main-background` is deliberately not set** — `css/theme.css:24` reads `/* Managed by Nextcloud theming */`. **Tested on NC 33.0.5, and that comment is too pessimistic: it *is* overridable.** Theming's generated sheets sit at cascade indices 26–33 and `theming_customcss` at 36, so a later `:root` rule wins outright (measured `#ffffff` at `:root`).

**But it must be theme-scoped.** Nextcloud puts `data-theme-light` / `data-theme-dark` on **`body`**, and token sets scope their colours to those attributes; at body level that shadows the inherited `:root` value. A `:root`-only rule therefore loses to SENERAWA's own values. Matching the specificity works:

```css
[data-theme-light], [data-theme-dark] { --color-main-background: #ffffff !important; }
```

So the surface/ground split Teams depends on is achievable — **provided the design-language layer emits theme-scoped rules, not only `:root`.** Shape tokens face no such contest: `--border-radius: 4px` applied from `:root` unopposed.

**Caveat on status colours, needs version verification.** On current server `master`, `--color-error` is a *pale background tint* (`#FFE7E7`) with a separate `--color-error-text` (`#8A0000`) and `--color-element-error` (`#c90000`). This app maps `--color-error` to a *solid* red (`#d52b1e`, `defaults.css`). That inverts the semantics on newer servers — a pre-existing hazard, not one the Teams profile introduces, but one it would inherit. Nextcloud changed this during the 28→33 range the app claims to support (`info.xml`). **Verify per version before mapping status colours at all.**

## 6. Tier 2 — the left rail, which is the actual "Teams" signal

> **Superseded — read as history. See [`teams-rail-spec.md`](teams-rail-spec.md).**
>
> Three of this section's premises are false:
>
> 1. **The markup it plans against no longer exists.** NC34 replaced the inline
>    horizontal app list with a waffle popover (`app-menu__grid`,
>    `app-menu__waffle`). `.app-menu-entry` appears **zero** times in core's
>    `dist/`; the only class surviving NC28→master is `.app-menu` itself. So
>    "reflow `.app-menu-entry` to icon-above-label" targets nothing.
> 2. **The files it cites were deleted from this repo.** `ddf77d1` removed both
>    `css/show-menu-labels.css` and `css/element-overrides.css`; neither is in
>    `git ls-files`, and `data-v-1f87d811` now survives only in these docs. Every
>    present-tense claim below about that CSS "already shipping", and the action
>    item to move it out, refer to code that is already gone. **This is the
>    stronger retraction: not "it targets nothing" but "it is not there."**
> 3. **`side_menu` is not an adequate substitute.** Its `MenuContainer.vue:115`
>    precedence chain offers a rail *or* categories, never both.
>
> What survives is this section's core warning — that overriding core's Vue DOM
> breaks silently on upgrade. The spec answers it by rendering the rail from
> `OCP\INavigationManager` and owning its own markup, so there is no core
> component to fight.

**Out of scope for `nldesign` by decision (§9).** This section is kept because it establishes *why* the boundary is technically right, and what the separate app would face if anyone rebuilt what `side_menu` already provides.

Palette is not what makes Teams recognisable. The shell is: a **vertical left app rail** with icon-above-label entries, a **list pane** beside it, a **thin top bar** with a centred command box, and content in **rounded panes**.

Nextcloud's shell is structurally the opposite. Its app switcher is **horizontal and top** (`#header nav.app-menu`, entries `.app-menu-entry` with `__icon` / `__label` / `__link`). The left sidebar (`#app-navigation-vue`, `--navigation-width: 300px`) is *per-app* navigation, not the app switcher. Turning one into the other is a DOM-position change, not a variable change.

What the shell exposes, from `apps/theming/lib/Themes/DefaultTheme.php`:

```
--header-height            44px     --navigation-width          300px
--header-menu-item-height  34px     --sidebar-min-width         300px
--default-clickable-area   34px     --body-container-radius     16px
--clickable-area-large     48px     --body-container-margin     8px
--breakpoint-mobile        1024px
```

Two of these are genuinely encouraging. `--body-container-radius: 16px` and `--body-container-margin` mean **modern Nextcloud already floats rounded content panes on a background** — the same compositional idea Teams v2 uses. And `--header-height` / `--navigation-width` are real, settable variables, so rail geometry is parameterisable even if rail *position* is not.

**How much structural liberty does this project already take?** More than the boundary now permits — see §9.1, which is why the decision has cleanup implications. `css/element-overrides.css` reaches into `#header nav.app-menu .app-menu-entry__link`, `#nextcloud > a > img`, `.app-navigation-toggle`, `.popover`, `#header .header-end .avatardiv img`, even `.content[data-v-1f87d811]` — a **Vue scoped-style hash**, which will silently break on any Nextcloud release that recompiles that component. And `css/show-menu-labels.css` already rewrites the app menu into flex-column icon-above-label entries with `!important` throughout — **that is half of a Teams rail, already written, already shipped behind the `nldesign:show_menu_labels` toggle.**

So the question was never "can this project do structural CSS" — it already does. The decision in §9 answers the real question: it should not, and what it already does moves out.

**A rail would need:** the header laid out as a column and positioned left (`#header`, `nav.app-menu`); `--header-height` neutralised and a rail width introduced; `.app-menu-entry` re-flowed to icon-above-label (largely done in `show-menu-labels.css`); the main grid offset by the rail; the per-app `#app-navigation-vue` reframed as the Teams list pane; overflow behaviour for >8 apps rebuilt, since Nextcloud's horizontal overflow menu makes no sense vertically; and `--breakpoint-mobile: 1024px` responsive collapse handled from scratch.

**The failure mode is not "it looks wrong" — it is "it breaks on upgrade, silently, for everyone."** None of these selectors is a supported contract. The app claims NC 28–33. That is six major versions of Vue component churn against `!important` selectors keyed to markup nobody promised to keep. Compare the `data-v-1f87d811` hash already in the tree: that is exactly this bet, already placed.

**And it is largely redundant.** [`side_menu`](https://apps.nextcloud.com/apps/side_menu) already does this, is maintained across NC 18–34, and was updated within the last month. It is a dedicated app with a maintainer whose whole job is tracking that DOM.

## 7. Tier 4 — overhauling assets: folder icons and "stuff"

**Icon replacement is out of scope for `nldesign` by decision (§9); branding assets stay in.** This section establishes what an icon overhaul costs in *any* app, because the answer turns out to be "more than the rail" — and that changes whether the separate app should attempt it at all.

Short version: **branding assets are easy and already done. Icons have no supported replacement path — but the workaround is cheaper than it looks.** Nextcloud offers third parties no icon API, and the icons that matter most (including the folder icon) are compiled into JavaScript, so substitution means CSS masking. The expensive-sounding part, mapping MDI to Fluent, turns out to generate from a ~40-line table at 99% coverage (§7.5). The genuinely unknown part is *how much* of the UI is class-addressable, and that needs an inventory pass, not an argument.

### 7.1 There are four asset systems, with four different answers

| System | Where it lives | Replaceable by this app? |
|---|---|---|
| **Instance branding** — logo, favicon, touch icon, login/background images | Theming app `ImageManager` | **Yes, fully supported.** Already wired through `ThemingService` (now `lib/Infrastructure/Nextcloud/Compatibility/ThemingService.php`). Hours of work. |
| **App icons** in the app menu | each app's own `img/app.svg`, rendered as a real **`<img :src="…">`** (verified: `core/src/components/AppMenu.vue`), served via `/apps/theming/img/{app}/{icon}` and recoloured by `IconBuilder::colorSvg()` | **Yes — and this is the *easiest* case, not the hardest.** It is a URL pointing at a file, so it has two independent levers: `themes/` shadowing at source, or CSS `content: url()` at render. See §7.3. |
| **Legacy filetype icons** | `core/img/filetypes/*.svg` — 29 files, still present on `master` (`folder.svg`, `folder-shared.svg`, `text.svg`, `x-office-document.svg`, …) | **Theoretically via the `themes/` shadow mechanism. In practice broken for a decade.** And it is no longer what the Files list renders. |
| **Modern UI icons**, including **the folder icon you see in Files** | `vue-material-design-icons`, compiled into the JS bundle | **No file exists to replace.** CSS mask substitution only — see §7.3. |

### 7.2 Why the folder icon specifically resists you

This is the single most-requested asset change and the one Nextcloud has quietly made impossible.

Verified in `apps/files/src/components/FileEntry/FileEntryPreview.vue`: folders render as **`FolderIcon` imported from `vue-material-design-icons`** (with `FolderOpenIcon` on drag-over, `FileIcon` for files, plus overlay icons for shared/encrypted/external). The component inlines the path data directly:

```html
<span class="material-design-icon folder-icon" role="img">
  <svg class="material-design-icon__svg" fill="currentColor" viewBox="0 0 24 24">
    <path d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z"/>
  </svg>
</span>
```

That geometry ships inside `files-main.js`. There is no `folder.svg` request to intercept, no URL to rewrite, no config to point elsewhere.

`core/img/filetypes/folder.svg` still exists — but **it is not what the Files list draws.** It survives only in legacy surfaces (public link pages, `OC.MimeType.getIconUrl` consumers, older third-party apps). Replacing it changes those and nothing else, which is exactly why the bug reports read as "I replaced the folder icon and nothing happened."

This is deliberate and documented. The developer manual states that **`core/img/` icons are deprecated as of Nextcloud 25** and that **"the svg API is not supported anymore due to performance reasons."** The supported path is Material Design Icons via `NcIconSvgWrapper` — supported *for app developers drawing their own icons*, not for anyone wanting to restyle someone else's.

The `themes/` shadow mechanism has a matching history of failure: [#411](https://github.com/nextcloud/server/issues/411), [#1290](https://github.com/nextcloud/server/issues/1290), [#3094](https://github.com/nextcloud/server/issues/3094), [#11888](https://github.com/nextcloud/server/issues/11888), [#21634](https://github.com/nextcloud/server/issues/21634) — users replacing `themes/mytheme/core/img/filetypes/folder.svg` and getting the stock icon anyway. The official documentation for it, including the `occ maintenance:mimetype:update-js` step, **stops at Nextcloud 15.** Treat it as unsupported.

One more dependency worth knowing: the Theming app's icon colourisation requires **php-imagick with SVG support**, which the admin manual flags and which is absent on a lot of installs. When it is missing, app-icon theming silently degrades.

### 7.3 Three levers, not one — and `<img>` is the good case

An earlier draft called CSS masking "the one lever." **Wrong, and wrong in a way that mattered:** it treated the app-menu `<img>` as a problem because `mask` cannot reshape an image's content. But an `<img>` is a *URL pointing at a file*, which makes it the most swappable thing in the entire UI. Ranked easiest to hardest:

**Lever 1 — `themes/` shadowing (source-level, best result).** Nextcloud's theme folder mirrors the server tree, and `imagePath()` resolves through it:

```
themes/MyTheme/apps/files/img/app.svg      → replaces the Files app icon
themes/MyTheme/core/img/logo.svg           → replaces the logo
themes/MyTheme/core/img/filetypes/…        → historically broken (§7.2)
```

This swaps the icon **at the source**, so it works in every place the icon appears — nav rail, app grid, mobile, emails — not just where a selector reaches. And the historical record supports it precisely here: in [#21634](https://github.com/nextcloud/server/issues/21634) the reporter's *logo and favicon replacements succeeded*; only `core/img/filetypes/` failed. App-icon and core-image shadowing is the part of that mechanism that works.

**The catch, and it rules this lever out here:** `themes/` lives in the **Nextcloud server root**, not inside an app. A Nextcloud app cannot ship one — it is a deployment artifact (Docker layer, Ansible task, volume mount). **The product owner has ruled that out: it must ship as an installable app.** So this lever is documented for completeness only; **§14 is the working answer.**

**Lever 2 — CSS `content: url()` on the `<img>` (works from inside an app).** No server access needed:

```css
#header .app-menu-entry__icon img,
.app-menu__current-app-icon { content: url('../img/fluent/folder_24_regular.svg'); }
```

Replaces the rendered image outright. **Verified in Firefox 153 and Chromium 151** (§14.2a-iii): identical rendering, the replacement glyph drawn over a deliberately-red control image in both.

**Lever 3 — CSS `mask` on MDI icons.** For `vue-material-design-icons`, which gives every icon a class derived from its component name — `folder-icon`, `file-icon`, `account-icon` — on a wrapper `<span>`. Hide the inlined SVG and mask in your own:

```css
.material-design-icon.folder-icon .material-design-icon__svg { display: none; }
.material-design-icon.folder-icon {
	width: 24px; height: 24px;
	background-color: currentColor;
	mask: url('../img/icons/fluent/folder_24_regular.svg') center / contain no-repeat;
}
```

It works, and §7.5 shows the rules generate rather than being hand-written. The class names are a naming convention rather than a contract, but a mechanically generated one — see §7.5.

**Lever 4 — path-data fingerprinting, and it removes the last ceiling.** `NcIconSvgWrapper` gives every icon the same `.icon-vue` class, so no *class* distinguishes them. But it injects real `<path d="…">` markup, and `d` is 99.9% unique across MDI's 7,447 icons — so an attribute selector distinguishes them perfectly. See **§14**, which is the answer when `themes/` is unavailable.

So the corrected picture, which is materially better than earlier drafts implied:

| Icon population | Lever | Verdict |
|---|---|---|
| Logo, favicon, backgrounds | Theming API | **Supported** |
| App icons (the nav rail) | `themes/` shadowing, or `content: url()` | **Swappable — the most Teams-critical surface is also the most reachable** |
| MDI icons (folder, actions, toolbars) | `mask` on per-icon class | **Swappable, generated** |
| `NcIconSvgWrapper` icons | Path-data fingerprint (§14) | **Swappable after all** — no class, but `d` identifies them |
| `core/img/filetypes/*` | `themes/` shadowing | Works in principle, but the Files list no longer renders these (§7.2) |

**Fluent icons are available and the licence is clean.** [`@fluentui/svg-icons`](https://www.npmjs.com/package/@fluentui/svg-icons) v1.1.335 is **MIT**, ships **21,551 SVGs** (84 MB unpacked), in exactly the single-path 24×24 form this technique wants:

```html
<!-- folder_24_regular.svg -->
<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path d="M3.5 6.25V8h4.63q.31 0 .53-.22l1.53-1.53…"/></svg>
```

So licensing is **not** the constraint here — I was wrong to assume it would be. The constraint is enumeration and durability.

### 7.4 Correction: this repo has no icon-replacement precedent

I initially cited the bundled Amsterdam icons as precedent. **Checked, and that is not what they are.**

`scripts/build-icons.js` copies 344 SVGs from `@amsterdam/design-system-assets` into `img/icons/`, and **nothing in this app references them.** `docs/reference/icons.md` documents them as something *another app's developer* could call `imagePath('nldesign', 'icons/Bell.svg')` on. The README's "Accessible via Nextcloud's image path API" is literally true and easy to misread: they are an available asset library, not an icon theme.

**No Nextcloud icon is currently replaced by this app.** Tier 4 is greenfield, not an extension.

### 7.5 Correction: the mapping is mostly mechanical — measured

An earlier draft of this section called an icon overhaul "weeks of per-icon judgement." **That was wrong, and the assumption behind it is testable.** Tested:

**Whole-set overlap is poor.** Fluent ships **2,457** icons at 24px-regular; MDI ships **7,447**. Exact name matches: **318 — 4.3%**. So a blanket "swap MDI for Fluent" is genuinely impossible; the long tail has no Fluent equivalent.

**But the set Nextcloud actually shows is small, and it maps almost perfectly.** Against a realistic 85-icon working set (folder, file, account, search, settings, bell, chevrons, dots, calendar, mail, …):

| | Icons | Share |
|---|---|---|
| Matched by name automatically | 42 | 49% |
| Matched via a hand-written alias table | 42 | 50% |
| **Covered** | **84** | **99%** |
| Still unmapped | 1 (`login`) | 1% |

The alias table is **~40 lines**, written in one sitting, and it is almost entirely vocabulary translation rather than design judgement: `magnify`→`search`, `cog`→`settings`, `pencil`→`edit`, `trash-can`→`delete`, `account`→`person`, `email`→`mail`, `close`→`dismiss`, `dots-horizontal`→`more_horizontal`. Both packages have machine-readable, consistent naming, so **the CSS generates from the table** — the same build-script pattern this repo already uses in `scripts/generate-tokens.mjs` and `scripts/build-icons.js`.

**I also overstated the fragility.** The `.material-design-icon.folder-icon` class pair is generated *mechanically by `vue-material-design-icons` from the component name*, not hand-written in Nextcloud templates. Breaking it needs that library to change its template — a far more stable target than the `data-v-1f87d811` scoped-style hash already sitting in `element-overrides.css`.

**What is actually hard, and it is not the mapping:**

1. **Enumerating what to cover.** `nextcloud/server` has **71 files** importing `vue-material-design-icons`, plus every installed app. Bounded and greppable, but it must be *measured* per deployment, not guessed — the 85-icon list above is an estimate, not an inventory.
2. **Icons that are not class-addressable — measured, and this is the ceiling.** `NcIconSvgWrapper` renders **`<span class="icon-vue">` with the SVG injected via `v-html`**, and it applies **the same class to every icon it draws**. There is no `search-icon` / `settings-icon` distinction: in CSS, all of them are `.icon-vue`. You cannot target one without hitting all. No stylesheet can substitute these — the geometry is in the injected markup, and `mask`/`background-image` are not used.

   How much of the UI that covers, by files importing each mechanism:

   | Repo | `vue-material-design-icons` (per-icon class — addressable) | `NcIconSvgWrapper` (shared `.icon-vue` — not addressable) | Addressable |
   |---|---|---|---|
   | `nextcloud/server` | 71 | 66 | **52%** |
   | `nextcloud-vue` | 46 | 38 | **55%** |
   | `nextcloud/spreed` (Talk) | 123 | 29 | **81%** |

   **Superseded by §14, and now disproved by live testing** (NC 33.0.5: `:has(> svg > path[d="…"])` matched exactly the five folder icons, `d` survived DOMPurify untouched, and the real Files page splits **65 class-addressable / 39 fingerprint-only**, not ~50/50). This looked like a hard ceiling for as long as the analysis was about *classes*. It is not: every one of these icons renders `<path d="…">` into the DOM, and the `d` string is a 99.9%-unique fingerprint that CSS attribute selectors match on. `.icon-vue:has(> svg > path[d="…"])` targets exactly one icon with no class, no JS, and no server access. The table above still describes which icons carry a *convenient* hook — it no longer describes what is reachable.
3. **Visual QA stays human.** Generation gives you 84 rules; someone still has to look at all of them in context. Cheap per icon, but not zero.
4. **Specificity.** Every rule needs `!important` and careful scoping, like the rest of this codebase.
5. **Coverage is bounded, not total.** Given 4.3% whole-set overlap, a deployment that installs an unusual app gets mixed iconography for it. Scoped coverage is achievable; instance-wide is not.

### 7.6 What an overhaul actually costs

| Scope | Effort | Durability |
|---|---|---|
| Logo, favicon, touch icon, backgrounds | Hours — already built | Supported API, stable |
| Legacy `core/img/filetypes/*` via `themes/` | Days | Unsupported, historically broken, **does not affect the Files list** — skip it |
| **Inventory pass**: grep `vue-material-design-icons` imports across server (71 files) + installed apps, produce the real icon list | **Days.** Mechanical, and it is the prerequisite for everything else | One-off, repeatable as a script |
| **Alias table + CSS generator** for the inventoried set | **Days.** ~40 alias lines, generated rules (§7.5) | Regenerate on Fluent/MDI updates |
| **Visual QA** of the generated rules in context | **Days.** Human, per icon, but fast | Re-check on Nextcloud upgrades |
| *Every* icon across server + all installed apps | Not achievable | 4.3% whole-set overlap — the long tail has no Fluent equivalent |

**Realistic total for a scoped, inventoried icon set: one to two weeks, not "weeks of per-icon judgement."** The earlier estimate was wrong because it assumed hand-mapping; §7.5 shows the mapping generates.

### 7.7 Recommendation for assets

**Branding assets: do them here, they are already built. Icons: tractable, and worth doing in the separate app — but inventory before you commit.**

Revised from an earlier "skip it," because the cost analysis behind that was wrong (§7.5). What survives of the original caution is narrower and worth stating:

- **Do the inventory pass first, as its own deliverable.** Until you know what fraction of visible icons are class-addressable versus drawn through `NcIconSvgWrapper` with raw SVG props, the coverage number is a guess. This is days of scripting and it converts the whole question from opinion to measurement. **Nothing else should start before it.**
- **Scope to the inventory, never instance-wide.** 4.3% whole-set overlap means full parity is impossible. Ship a bounded, named set and be honest in the admin UI about what is covered — which is exactly the "truthful report of which surfaces are covered" the roadmap already promises for profiles.
- **Sequence it after tier 1.** `fill="currentColor"` means MDI icons inherit the palette the moment the design-language setting lands. Look at the result before spending a fortnight: MDI and Fluent are both 24×24 rounded-geometric line icons and may read as siblings at nav size. If they do, the icon work buys less than it costs; if they clash, you will see exactly which ones and the inventory gets cheaper.
- **It stays out of `nldesign` regardless** (§9). This is separate-app work — but the separate app now has a much better business case than this document originally gave it.

## 8. Typography

Teams' `fontFamilyBase` is `-apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, …`. **Segoe UI is proprietary and cannot be bundled.** This project has solved this exact problem before — Fira Sans stands in for RijksoverheidSansWebText, under SIL OFL. Same move applies.

**[Selawik](https://github.com/microsoft/Selawik)** is Microsoft's own **OFL-1.1** open replacement for Segoe UI, and is the most faithful option. Two findings from the repo, both important:

- The repository ships **source only** — `Source files/Glyphs/selawik.glyphs` and UFO directories. **No compiled `.ttf`/`.otf` in the tree.** There is no `@fontsource/selawik` package either (checked; not published). So adopting Selawik means adding a font-compilation step, which the repo currently avoids — `README.md` advertises "No Build Required."
- Microsoft's own README lists known limitations: **missing kerning to match Segoe UI, and hinting that needs work.** For a body-text UI font at 14px, unhinted and unkerned is a real quality cost, not a footnote.

**Recommendation:** do not chase Selawik for v1. Use the system stack Fluent itself declares first — `-apple-system, BlinkMacSystemFont, "Segoe UI", system-ui, …` — which renders in genuine Segoe UI on Windows (where most Teams-familiar users are) and in the native system font elsewhere. That is Fluent's own documented intent, needs no bundling, no build step, and no licence question. Revisit Selawik only if a Linux-desktop fleet makes the fallback visually unacceptable.

Metrics that matter regardless: base size **14px** (`fontSizeBase300`), line-height **20px**, weights 400/500/600/700. Nextcloud's default is **15px / 1.5**, so a Teams profile is a genuine density change, not just a face swap. Expect layout knock-on.

## 9. The scope boundary — decided

**Direction from the product owner, this session:** replacing navigation patterns, icons, or components is **out of scope for `nldesign`**; that work belongs in a separate app.

This ratifies what `docs/roadmap.md` §2.3 had listed only as a proposed non-goal:

> - Replacing app-specific UI structures, icons, navigation patterns, or components.

alongside §1's "an implementation of NL Design System components inside Nextcloud" and "a promise that every Nextcloud app can be restyled."

The boundary maps onto the tiers exactly:

| Tier | Verdict |
|---|---|
| 1. Tokens — colour, type, radius, density, shadow, motion | **In scope.** This is the product. |
| 1b. Instance branding — logo, favicon, background | **In scope.** Already built, goes through the Theming bridge. |
| 2. Navigation patterns — the left rail | **Out. Separate app.** And it already exists: `side_menu`. |
| 3. Components — bubbles, pills, compose box | **Out. Separate app.** |
| 4. Icons | **Out. Separate app.** |

The boundary is also the right one on the merits, independent of anyone's preference: tiers 2–4 all depend on **unversioned Vue DOM**, while tier 1 depends on **documented CSS variables**. Those have different upgrade risk, different test surfaces, and different maintenance cadence. Mixing them means one app whose token half is stable and whose shell half breaks every Nextcloud release — and users cannot adopt the stable half without inheriting the fragile one. Splitting them lets `nldesign` keep the NC 28–33 support claim in `info.xml` honest.

### 9.1 This app is currently on the wrong side of its own boundary

Worth stating plainly, because the decision has cleanup implications and not only future ones. Today's tree already does tier-2 work:

- **`css/show-menu-labels.css`** reflows `#header nav.app-menu .app-menu-entry__link` into a flex column with icon-above-label, sets `.app-menu-entry` height and `min-width`, and suppresses Nextcloud's active-state indicator — all with `!important`. That is **navigation-pattern replacement**, shipped behind the `nldesign:show_menu_labels` toggle. It is also, as noted in §6, roughly half a Teams rail.
- **`css/element-overrides.css`** reaches into `.app-navigation-toggle`, `.popover`, `#header .header-end .avatardiv img`, `.button-vue__icon`, `#nextcloud > a > img`, and — most fragile — **`.content[data-v-1f87d811]`, a Vue scoped-style hash** that will silently stop matching whenever that component is recompiled.

None of this is a crisis; it works today. But if the boundary is real, these are the things that migrate to the separate app, and `data-v-1f87d811` is the one to remove regardless — it is a time bomb with a version number on it.

### 9.1b What the second app actually is: a reach extension

An earlier draft framed the split by *subject matter* — "`nldesign` does colours, the other app does icons." That is the wrong axis, and it makes the second app look like a novelty.

The right axis is **how a surface is reached**:

| | `nldesign` | the second app |
|---|---|---|
| Job | project design tokens onto Nextcloud | **the same job** |
| Reaches | surfaces Nextcloud exposes as **documented CSS variables** | surfaces it exposes only as **markup conventions** |
| Evidence | `--color-*`, `--border-radius-*` — a contract | Vue class names, SVG `path[d]`, `img[src]` — conventions |
| Upgrade risk | low; NC 28–33 claim is honest | re-verify every release |

So the second app is **not a different product. It is the same product reaching further** — into icon geometry and SVG internals, which have no variable contract and never will.

This is the same pattern `architecture.md` already applies internally, one level up: a load-bearing profile plane on public APIs, plus removable adapters that use private access. The second app *is* that adapter idea, packaged so an administrator can decline it.

**The coupling is nil, and that is the elegant part.** CSS custom properties are global to the cascade, so the second app's stylesheet simply reads `var(--nldesign-color-primary)`, `var(--nldesign-border-radius)` and so on, published by whichever profile is active. No PHP API, no shared service, no version negotiation. If `nldesign` is absent the variables are undefined and the app falls back to its own defaults; if `nldesign` changes profile, the icons follow automatically.

**Which reframes the product.** It is not "the Fluent icon app." It is the app that carries *the active organisation's* design language into surfaces the token app cannot reach — Utrecht's icons for Utrecht, Fluent's for Fluent. That is a far better fit with the roadmap's "translation product" framing than a Microsoft-shaped one-off.

**And the cost is symmetrical.** Extending reach extends exposure by exactly the same step: every surface reached this way is one nobody promised to keep, and — per §14.2c — the deepest reach (inlining third-party SVG) is also the one that removes a browser sandbox. Reach and risk are the same axis. That is the argument for keeping it a separate, declinable app, not a reason to avoid building it.

### 9.2 Does "separate app" mean a separate repo?

**Yes, a sibling repo — but do not create it yet.**

Separate *app* and separate *repo* are not the same requirement, so it is worth being explicit about why they coincide here. The scope decision only demands a distinct app ID, so that an administrator can adopt the stable token half without inheriting the fragile shell half. A monorepo could in principle satisfy that.

**The existing release machinery decides it.** `.github/workflows/release-stable.yaml:29` sets:

```yaml
APP_NAME=${GITHUB_REPOSITORY##*/}
```

The app name *is* the repository name. The same pipeline reads `appinfo/info.xml` from the repo root (`:34`, `:42`), tars `package/${{ github.event.repository.name }}` (`:102`), and pushes to the app store under that name (`:131-136`). **One repo = one app is baked in.** A monorepo means rewriting `release-stable`, `release-beta`, `release-unstable`, `release-workflow`, `beta-release`, and `unstable-release` — roughly **1,000 of the 1,604 lines of workflow YAML** in this repository, on the release path, where mistakes are expensive and rarely caught before publishing.

Two further reasons the split is right independent of tooling:

- **The app store models one listing per app ID**, with its own version and its own `<nextcloud min-version max-version>` range. A shell or icon app riding unversioned Vue DOM should declare a *narrower* range than `nldesign`'s 28–33. That divergence is the entire point of splitting, and it cannot be expressed from one `info.xml`.
- **Runtime coupling is lower than it looks.** The token layer consumes `@fluentui/tokens`; an icon app consumes `@fluentui/svg-icons` and `@mdi/js`. Different upstream packages, different generated artifacts, no shared runtime code. The monorepo argument — "they share build tooling" — turns out to be nearly the only one, and it is weak against a thousand lines of release YAML.

**The honest cost of a sibling:** duplicating ~1,604 lines of workflow, ~22 KB of tooling config (`phpcs.xml`, `phpmd.xml`, `psalm.xml`, `phpstan.neon`, `composer.json`), and the custom sniffs in `phpcs-custom-sniffs/`. Mostly copy-once boilerplate, but it is real, and it will drift. Worth noting the asymmetry: an icon app is `info.xml`, one `Application.php` calling `addStyle`, a generator script, and generated CSS plus vendored SVGs — **the repository scaffolding would exceed the source code.**

**Which is why the timing matters more than the structure.** Before creating anything, note what the sibling would actually contain:

| Candidate content | Status |
|---|---|
| Navigation rail | **Probably nothing** — `side_menu` already does it (§2). Only in play if Gate C shows it fights |
| Icons | The fingerprint CSS, generator, and Fluent assets (§14) — **entirely contingent on Gate B** |
| Components | Nothing. Not viable as CSS at all (§1) |

So the sibling is, realistically, **an icon app** — and whether it should exist is exactly what Gate B answers. **Run the gates first.** If Gate B says icons are not worth it and Gate C says `side_menu` composes, the second repo has nothing to hold and should not be created.

Name it once its contents are known: `nldesign-icons` if that is all it does, something broader only if Gate C forces rail work back in scope.

### 9.3 The product-identity question — resolved by §12

A `fluent` **profile** would not be an organisation's house style; it would be **another vendor's product identity**. `roadmap.md` frames this app as a translation product carrying "house-style decisions" into Nextcloud, with invariant 10: *"A missing profile value or failed adapter never falls back to another organisation's identity."* So "should the NL Design System theming app ship a Microsoft lookalike?" was a real question the scope boundary did not answer.

**§12 dissolves it.** Ship Fluent as a *design-language setting* — shape, density, motion, no brand — and Microsoft's purple never enters the repository. The organisation's own identity still supplies every colour. Nothing falls back to another organisation's identity, because the layer has no identity to fall back to.

## 10. Naming and legal

Ship it as **`fluent`** (or `fluent-2`), described as *"Fluent-inspired"* or *"Fluent 2 design tokens."* Not `teams`, not "Microsoft Teams theme."

The token values come from `@fluentui/tokens`, **MIT licensed** — reusing them is unambiguously fine, and the licence text should be vendored alongside the token file the way the repo already handles Fira Sans (OFL) and the Amsterdam icons.

What is **not** MIT: the *Microsoft Teams* name, the Teams logo and icon set, and Segoe UI. Avoid all three in the profile id, display name, description, screenshots, and any bundled assets. This matters more than usual here — the deployment context is Dutch public-sector procurement, where "our Nextcloud looks like Microsoft Teams" is a sentence that ends up in a tender document.

**Under §12 this gets easier still.** A setting labelled **"Fluent Design Tokens"** is nominative use of a design system's own name, describing MIT-licensed values that genuinely are Fluent's. No brand colour ships, so there is no purple to argue about, no Teams name anywhere, and nothing that could read as passing off. The naming problem largely disappears along with the profile.

One paragraph, then move on. It is a naming constraint, not a blocker.

## 11. Recommendation

Given the §9 boundary, the work splits across two apps — and **the second app mostly already exists.**

### In `nldesign`

1. **Ship `css/shape/fluent.css` as a design-language setting, not a token set (§12).** Shape, density, motion — **no brand colour, no font family**. New config key `nldesign:design_language`, default `nldesign`, so existing instances are untouched.

   **Ship it in two steps, because only half the plumbing exists** (verified, §12.2): **v1 is radii alone** — `theme.css:46-51` already forwards all six radius variables, so this is ~6 declarations plus a settings control, it works today, and sharp-vs-rounded is the most legible Fluent signal anyway. **v2 adds type scale, density, stroke width, and motion**, each of which needs a new `--nldesign-*` token and a new forwarding line first; that is shared-plumbing work touching all 40 token sets and wants its own regression pass. **Drop spacing and shadows entirely** — Nextcloud exposes no contract for either, so they would need element-level CSS, which §9 puts out of scope.

   Gates either way: **exclude Fluent's neutral surface scale** — §12.3 shows it drops Amsterdam, Leiden, and Rotterdam below 4.5:1 — and **keep Fluent dark mode off** until token sets declare lightened brand ramps.

2. **Branding assets via the existing Theming bridge.** Logo, favicon, background. Already built, in scope, hours.

3. **Let MDI icons inherit the palette.** They use `fill="currentColor"`, so tier 1 recolours them for free. Recolouring is not replacing — it stays inside the boundary.

### In a separate app

4. **The navigation rail: the cheapest way to satisfy the boundary is to adopt rather than build.** [`side_menu`](https://apps.nextcloud.com/apps/side_menu) already exists, it supports NC 18–34, and it was updated last month by a maintainer whose job is tracking that DOM. Building a second one is the most expensive way to arrive where you can already be today. **The one experiment worth running is composition:** install `side_menu` + `AppOrder`, select the `fluent` profile, see whether they cooperate or fight — `side_menu` renders its own sidebar while this app's `#header` overrides assume Nextcloud's. A day's work, and if they compose the deliverable is a README recipe with zero structural CSS.

5. **Icons: run the §13 spike before deciding anything.** Two corrections to earlier drafts, in opposite directions. The *mapping* is far cheaper than I first said — §7.5 measures **99% coverage of a realistic 85-icon set from a ~40-line alias table**, CSS generated not hand-written. But the *ceiling* is lower than I said: `NcIconSvgWrapper` gives every icon it draws the same `.icon-vue` class, so **~half of server icon call sites can never be targeted individually.** Any icon project therefore ships a partially-swapped instance by construction.

   Whether that reads as deliberate or broken is a pixels question, so: **six specimens, one to two days, scratch CSS, no app** (§13). It answers "will icons block?" before anyone scopes an inventory or creates a second app — and the decision criteria are written down in advance so the result gets read honestly.

6. **Components (tier 3): not viable in any app.** Message bubbles and compose boxes live in Talk's own markup. A CSS-only app cannot reach them; changing them means patching Talk.

### Migration, when the boundary is enforced

7. **Move `show-menu-labels.css` out, delete `data-v-1f87d811`.** §9.1. The first is navigation-pattern work sitting in the wrong app; the second is a Vue scoped-style hash that will fail silently on some future release and should go regardless of any of this.

### The honest summary

Tier 1 in `nldesign` plus `side_menu` gets you most of the way, in about a week, on maintained foundations. Everything beyond that is weeks of work against unversioned DOM for diminishing resemblance. **The left rail is the "Teams" signal and someone else already maintains it — that, not the purple, is the finding worth acting on.**

### Test these before committing to anything

- **`--color-main-background`.** Can the profile get the `#ffffff`-on-`#f5f5f5` surface/ground split at all, given §5? If not, tier 1's resemblance is much weaker than this document implies and the estimate is wrong.
- **`side_menu` + this app's `#header` overrides.** Do they compose or fight?
- **Status colours across NC 28–33.** The `--color-error` semantics caveat in §5 needs a per-version answer before any status mapping is written.
- **Do MDI icons actually look wrong once the shape layer lands?** §7.7 suspects not — both families are 24×24 rounded-geometric line icons and inherit `currentColor`. Cheap to falsify: ship tier 1, look at the nav rail, decide. This is the gate that tells you whether the icon project is worth its one-to-two weeks.
- **Will icons block? Run the §13 spike — six specimens, one to two days, no commitment.** Current answer: **probably not.** Every icon population has a lever that works from inside an app (§14), including the class-less `NcIconSvgWrapper` ones. Two things could still kill it, both cheap to test: whether DOMPurify passes `d` through unaltered, and whether `:has()` with dozens of long attribute selectors is fast enough on a real DOM. **Highest decision-value experiment in the document.**

### Which Teams — resolved, but make it a variant

Classic Teams had **purple chrome**: a saturated purple rail and title bar. Teams v2 / Fluent 2 uses **neutral chrome with purple as accent only**. The token package answers this: `colorBrandBackground` (`#5b5fc7`) and `colorNeutralBackground1` (`#ffffff`) are separate axes, with a full neutral scale (`Background1`–`Background6`) independent of brand. That structure *is* neutral-chrome-plus-brand-accent, read off the package Microsoft ships for Teams itself.

**Under §12, this question stops applying — and that is the point.** The design language carries no brand colour, so chrome takes the *organisation's* primary: Utrecht chrome is Utrecht red, Rijkshuisstijl chrome is `#154273`. Fluent contributes the neutral-chrome-plus-accent *structure*, never the hue.

Which means **Teams-purple chrome is deliberately out of scope.** An organisation gets its own colour or it gets Nextcloud's default; `#5b5fc7` is not on offer, because supplying it would require exactly the Microsoft-identity token set §12 exists to avoid. If someone genuinely needs Teams purple, that is a separate token set they author themselves under their own name — not something this app ships.

The measured chrome pairs are in §12.3: white-on-brand passes for all eight brands tested (worst case Amsterdam at 4.60:1), so brand-coloured chrome with white text is safe across the board.

## 12. Better shape: Fluent as a design-language setting, not a brand profile

Everything above assumed the deliverable is a `fluent` **token set** sitting alongside Utrecht and Rijkshuisstijl — one more entry in the same list, carrying Microsoft's purple. That framing is wrong, and it is what made §9.3 uncomfortable.

**The proposal: don't bake in the purple. Make Fluent a separate axis.**

Today the app has one dimension: *which organisation are you?* Add a second, orthogonal one: *which design language shapes it?*

```
token set (who you are)   ×   design language (how it is shaped)
─────────────────────────     ────────────────────────────────────
Gemeente Utrecht              NL Design  (default — sharp, flat)
Rijkshuisstijl                Fluent     (rounded, soft, elevated)
SENERAWA
…39 more
```

`Gemeente Utrecht × Fluent` = Utrecht's legally-required red, in Teams' ergonomics. That is what an organisation migrating off Teams actually wants: their own identity, in a shell their staff already know. It is not available at all under the profile framing, because a `fluent` profile would drag Microsoft's purple in with it.

### 12.1 Why this is strictly better

**It resolves §9.3 outright.** The app never ships a Microsoft-lookalike *identity* — `#5b5fc7` need not appear in the repository at all. It ships a shape-and-density option, applied to whichever Dutch government identity the admin already selected. "Should an NL Design System theming app carry a Microsoft lookalike?" stops being the question.

**It kills the trademark surface.** A setting labelled **"Fluent Design Tokens"** is descriptive and accurate — nominative use of Microsoft's design-system name, sourced from an MIT package. No Teams name, no Teams logo, no Teams purple.

**It multiplies rather than adds.** One profile serves one organisation. One design language serves all 40, and every one added later.

**It matches the architecture already being built.** `architecture.md` separates "profile CSS, profile recommendations, instance identity, administrator policy, user preference, and derived core state" into distinct data classes, and invariant 4 is "ownership remains visible." Brand colour is organisational identity; corner radius is administrator policy. Merging them into one file is precisely the ownership blur the architecture is trying to undo.

### 12.2 What goes in the layer — and what must not

Narrow and enumerated. The layer carries **shape, density, and motion. Never brand.**

| In the Fluent layer | Values | Contrast-safe? | Plumbing today |
|---|---|---|---|
| Border radii | 2 / 4 / 6 / 8px, pill 10000px→100px | Yes | **Works.** `theme.css:46-51` already forwards six radius variables, plus `--body-container-radius` at `overrides.css:44` |
| Type **scale** — sizes, line-heights, weights | 14/20px base, 400/500/600/700 | Yes | **Missing.** `--default-font-size`, `--font-size-small`, `--default-line-height`, `--font-weight-*` are never defined |
| Density | Fluent 4px grid | Yes | **Missing.** `--default-clickable-area`, `--default-grid-baseline` never defined |
| Stroke widths | 1 / 2 / 3 / 4px | Yes | **Missing.** `--border-width-input` never defined |
| Motion | 100/200ms, Fluent easings | Yes | **Missing.** `--animation-quick`, `--animation-slow` never defined |
| Shadows | `shadow2`–`shadow16` | Yes | **Missing**, and Nextcloud exposes only `--color-box-shadow`, not a shadow scale — needs element-level rules, not a variable |
| Spacing scale | 4 / 8 / 12 / 16 / 20px | Yes | **No Nextcloud equivalent to forward.** Nextcloud has no spacing-token contract; this reaches nothing without element CSS |

**Verified, not assumed** — `grep -E '^\s*--(border-radius|default-font-size|animation|default-clickable-area)…:' css/*.css`. This materially changes the estimate; see §12.5.

| **Excluded** | Why |
|---|---|
| `--nldesign-color-primary` and every brand colour | Comes from the token set. This is the whole point. |
| **Font family** | Brand identity — SENERAWA's IBM Plex, Rijkshuisstijl's Fira Sans. Fluent contributes the *scale*, the organisation keeps the *face*. |
| Neutral surface scale (`Bg1`–`Bg6`) | **Not contrast-neutral.** See §12.3 — this is the one that bites. |

The first six are pure geometry and timing. They cannot break a contrast ratio, because they do not touch colour. That is what makes this setting safe to offer across all 40 token sets without a combinatorial audit.

### 12.3 The neutral surface scale is the trap — measured

Teams' *look* depends on white panes floating on a grey ground (`#ffffff` on `#f5f5f5`). Tempting to include. But NL Design token sets were measured against **flat white**, and Fluent's tinted surfaces move the floor under every one of them.

Measured, brand-as-text/link on Fluent's light surfaces (floor 4.5:1):

| Brand | `Bg1` #ffffff | `Bg2` #fafafa | `Bg3` #f5f5f5 | `Bg4` #f0f0f0 |
|---|---|---|---|---|
| Amsterdam `#ec0000` | 4.60 ⚠ | **4.41** | **4.22** | **4.04** |
| Leiden `#d62410` | 5.10 | 4.88 | 4.68 | **4.47** |
| Rotterdam `#00811f` | 5.05 | 4.84 | 4.63 | **4.43** |
| Den Haag `#1a7a3e` | 5.39 | 5.16 | 4.94 | 4.73 |
| Utrecht `#cc0000` | 5.89 | 5.64 | 5.40 | 5.17 |
| Rijkshuisstijl `#154273` | 10.20 | 9.77 | 9.36 | 8.95 |
| VNG `#003865` | 11.98 | 11.47 | 10.98 | 10.51 |
| *(Fluent's own `#5b5fc7`)* | 5.38 | 5.15 | 4.93 | 4.72 |

**Bold = below 4.5:1. ⚠ = passes with no headroom** — by this repo's own standard ("a ratio of exactly 3.00 fails as soon as antialiasing or a display profile shifts it"), Amsterdam's 4.60:1 on plain white is already on the line before Fluent touches anything. It then fails on every tinted surface. Leiden and Rotterdam fail on `Bg4`. Nothing about the *shape* tokens caused this — only the surfaces.

Two things that do **not** break, checked across all eight brands: white-on-brand for buttons passes everywhere (4.60:1 Amsterdam, worst case), and brand-as-border passes 3:1 everywhere. So brand *usage* is fine; only brand-on-tinted-surface is at risk.

**Dark mode is a harder gate.** Every light-mode brand fails on Fluent's dark surface `#292929`:

```
FAIL 3.16:1 Amsterdam   FAIL 2.47:1 Utrecht        FAIL 1.43:1 Rijkshuisstijl
FAIL 2.85:1 Leiden      FAIL 2.88:1 Rotterdam      FAIL 1.21:1 VNG
FAIL 2.70:1 Den Haag    FAIL 2.71:1 Fluent's own #5b5fc7
```

Fluent's own purple fails too — which is exactly why Fluent ships a **separate dark brand ramp** (`colorBrandForeground1` lightens to `#7f85f5` in dark). Most token sets here have no dark-mode brand variant. **Fluent dark mode therefore requires each token set to declare a lightened brand value, or it must be refused for that set.** Not optional, and it is the single largest piece of work this proposal creates.

### 12.4 Cascade position

```
fonts → defaults → tokens/{org} → shape/{language} → utrecht-bridge → theme → overrides → element-overrides
                                  ^^^^^^^^^^^^^^^^ new
```

**After** the org token set, deliberately. SENERAWA declares `--nldesign-border-radius: 1px`; Fluent must be able to override that, or the setting does nothing for any set that declares shape. The admin is explicitly overriding the organisation's shape decision — that is the feature.

`shape/nldesign.css` (empty, the default) and `shape/fluent.css`. New config key `nldesign:design_language`, defaulting to `nldesign`, so **every existing instance is unaffected**.

### 12.5 What it costs, and the honest objection

**Corrected after checking the forwarding, which I initially assumed.** The estimate splits in two:

**Radii alone: genuinely days.** `theme.css:46-51` already forwards `--border-radius`, `-small`, `-large`, `-element`, `-rounded`, `-pill` from `--nldesign-*`, and `overrides.css:44` forwards `--body-container-radius`. A radius-only Fluent layer is ~6 declarations plus a config key and a settings control, and it works through plumbing that exists. Radius is also the single most legible Fluent signal — sharp-vs-rounded is what the eye reads first.

**Everything else needs new forwarding first.** Type scale, density, stroke width, and motion are **not defined anywhere in `css/`** — `--default-font-size`, `--font-size-small`, `--default-line-height`, `--font-weight-*`, `--default-clickable-area`, `--default-grid-baseline`, `--border-width-input`, `--animation-quick`, `--animation-slow` are all absent. Each needs a new `--nldesign-*` token in `defaults.css` and a new forwarding line in `theme.css`. That is shared-plumbing work touching every token set, so it needs its own regression check, not a bolt-on to this feature.

**Two do not map at all.** Nextcloud exposes no spacing-token contract and no shadow scale (only `--color-box-shadow`). Fluent's `spacing*` and `shadow2`–`shadow16` cannot be forwarded as variables — they would require element-level CSS, which is the structural-override territory §9 pushes out. **Recommend dropping both from scope** rather than reaching for selectors.

So: **v1 = radii, days. v2 = type/density/motion, a week or so of shared-plumbing work plus regression testing across 40 token sets. Spacing and shadows: out.**

The gates, in order of cost: **(1)** exclude Fluent's neutral surface scale from v1, or gate it per token set on the §12.3 measurements; **(2)** Fluent dark mode stays off until token sets declare lightened brand ramps; **(3)** the v2 plumbing lands as its own change, reviewed against all 40 sets, before any type-scale values are promised.

One fidelity note for v1: `theme.css:49` maps Nextcloud's `--border-radius-element` to `--nldesign-border-radius` rather than a distinct token, so Fluent's 4px/8px element distinction collapses to a single value. Cosmetic, but it is why `--nldesign-border-radius-element` may be worth adding alongside the v2 work.

**The objection worth stating:** NL Design house style is deliberately sharp and flat; Fluent is rounded, soft, and elevated. `Utrecht × Fluent` is neither NL Design compliant nor Teams. Where NL Design System compliance is contractual — plausible in Dutch public-sector procurement — this setting **breaks it by design**. That is defensible for a deliberate admin choice, but the setting must say so in the admin UI, not bury it. Label it as leaving NL Design System conformance, and default it off.

## 13. MVP spike — will icons block? Six specimens, one to two days

The question is not "how long would an icon overhaul take" but **"is there a wall, and where."** §7.5 already found the ceiling analytically: about half of server icon call sites use `NcIconSvgWrapper`, which gives every icon the same `.icon-vue` class and cannot be targeted individually. What analysis cannot tell you is whether the resulting **half-swapped instance looks deliberate or broken.** Only pixels answer that.

So: six specimens, chosen because **each one fails differently.** Do them in order. The goal is a decision, not coverage — **stop as soon as you have one.**

### The ladder

| # | Specimen | Mechanism under test | Expected | What a failure means |
|---|---|---|---|---|
| 1 | **Favicon / logo** | Theming app `ImageManager`, supported API | **Works** | The branding path itself is broken — unrelated to icons, but fix before anything else |
| 2 | **Folder icon**, Files list | `vue-material-design-icons`, per-icon class, `mask` swap | **Works** | The whole CSS-masking technique is dead. **Stop here — this is the wall.** |
| 3a | **App menu icon** via `content: url()` | `<img>` content-replacement from inside an app | **Likely works** | Falls back to 3b; only costs you the app-distributable route |
| 3b | **App menu icon** via `themes/MyTheme/apps/files/img/app.svg` | Source-level shadowing — works everywhere the icon appears | **Likely works** | If *both* 3a and 3b fail, the nav rail is unreachable — the worst outcome in this table, since that is where a Teams user looks first |
| 4 | **Action icon inside `NcActions`** | Popover content teleported to `<body>`, outside `#content` | **Unknown** | Selectors scoped to page containers miss all menu/dialog icons. Fixable by rescoping, but it changes every rule |
| 5 | **Any `NcIconSvgWrapper` icon** (e.g. unified search) via `:has(path[d="…"])` (§14) | Path-data fingerprint + DOMPurify passthrough + `:has()` cost | **Should work** | If `d` is rewritten by sanitisation, or `:has()` is too slow on a real DOM, §14.2 dies and idea 2 (JS) becomes the route |
| 6 | **Non-folder file type icon** | `FileIcon` vs preview-image path | **Partial** | Files with previews never show an icon anyway; tells you the real denominator |

Specimens 2, 3a and 5 are load-bearing. **3b is now a control, not a candidate** — `themes/` shadowing needs server-root access, which is ruled out (§14), so run it only to confirm that a failure in 3a is a CSS limitation rather than something wrong with the icon file itself. Specimen 5 carries the most weight, because §14.2 rests on it.

### Runnable starting point

Vendor two or three Fluent SVGs from `@fluentui/svg-icons` (MIT) and try:

```css
/* #2 — the canonical test. MDI gives a per-icon class. */
.material-design-icon.folder-icon .material-design-icon__svg { display: none !important; }
.material-design-icon.folder-icon {
	width: 24px; height: 24px;
	background-color: currentColor;
	mask: url('../img/fluent/folder_24_regular.svg') center / contain no-repeat;
}

/* #3a — an <img>, so replace its content outright. Verified in Firefox 153 + Chromium 151. */
#header .app-menu-entry__icon img,
.app-menu__current-app-icon { content: url('../img/fluent/folder_24_regular.svg'); }

/* #3b — no CSS at all: drop the file in the server root and let imagePath() find it.
   themes/MyTheme/apps/files/img/app.svg
   Then enable the theme. Swaps at source, so it also fixes places CSS never reaches. */

/* #5 — expected to fail usefully: this hits EVERY icon-vue at once. */
/* Run it once, screenshot the damage, then delete it. It is the ceiling, made visible. */
.icon-vue > svg { outline: 2px solid magenta; }
```

Specimen 5's rule is diagnostic, not a candidate fix — outlining every `.icon-vue` in magenta for one screenshot shows you **exactly which icons can never be swapped**, across the whole UI, in one pass. That single screenshot is the most decision-relevant artifact in this spike; take it early.

### Decision criteria, written before you look

Commit to these now, so the result is read honestly rather than rationalised:

- **#2 fails** → icons are blocked. Ship the design-language setting (§12), close the question, no separate icon app.
- **#3a works** → the nav rail is reachable from inside an app. **#3a fails but #3b works** → CSS content-replacement is the limitation, so fall back to idea 2's JS, which can rewrite `<img src>` directly (§14.3). **Both fail** → the icon file itself is wrong; retest before concluding anything.
- **#5 works** → §14.2 is confirmed and there is no ceiling. **#5 fails** → find out which half: DOMPurify altering `d`, or `:has()` not matching. The first kills fingerprinting outright; the second is a syntax problem worth 20 more minutes.
- **#2 and #3 work, #5's magenta screenshot looks tolerable** → proceed to the inventory pass (§7.7) with real confidence.
- **#2 and #3 work but the magenta screenshot is everywhere** → the honest answer is that a half-Fluent instance looks worse than a coherent MDI one. Recolour via `currentColor`, stop there.

### What this costs and what it settles

One to two days, entirely in a scratch CSS file — **no app, no build step, no commitment**. Paste into the Theming app's Custom CSS field, or `addStyle` it behind a temporary toggle, and throw it away afterwards.

It answers the actual question — *will icons block?* — before anyone scopes an inventory, writes an alias table, or creates a second app. And it front-loads the two failure modes that analysis genuinely cannot predict: whether `<img>`-based nav icons are reachable, and whether half-coverage reads as broken.

**Do this before §12 v2, and before any icon work.** It is the cheapest decision-per-hour in this document.

## 14. Solving it inside an app — the `themes/` folder is not available

**Constraint:** everything must ship inside an installable Nextcloud app. No server-root access, no separate container, no deployment recipe. That removes `themes/` shadowing (§7.3, lever 1) and with it the cleanest answer for app icons.

Good news: **the ceiling I described in §7.5 is not real.** It rested on "`NcIconSvgWrapper` gives every icon the same `.icon-vue` class, so no selector can distinguish them." True about *classes* — and irrelevant, because the icons carry a better identifier.

### 14.1 The path data is the identifier — measured

Every one of these icons renders its geometry into the DOM as `<path d="…">`. Extracted from `@mdi/js` v7.4.47:

| | |
|---|---|
| MDI path constants | **7,447** |
| Distinct `d` strings | **7,437** |
| Collisions | **9**, all genuine aliases (`mdiAlphaO`/`mdiNumeric0`) |

**The `d` attribute is a 99.9% unique fingerprint** — and CSS attribute selectors match on it. So `.icon-vue` sharing a class stops mattering:

```css
.icon-vue:has(> svg > path[d="M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z"])
```

That selects **the folder icon and nothing else**, with no class, no JS, and no server access. It works identically for `NcIconSvgWrapper` (`v-html`-injected) and `vue-material-design-icons` (inlined), because both put the same path data in the DOM.

Use **full-string exact match**. Prefix matching degrades badly — only 69% unique at 20 chars, 78% at 64 — so `[d="…"]`, not `[d^="…"]`.

### 14.2 Idea 1 — generated fingerprint CSS *(recommended)*

Split identification from painting, so the generated part is only variable assignments and the paint rules are written once:

```css
/* generated: fingerprint → icon choice */
.icon-vue:has(> svg > path[d="M10,4H4C2.89,…Z"]),
.material-design-icon:has(> svg > path[d="M10,4H4C2.89,…Z"]) {
	--nld-icon: url("../img/fluent/folder_24_regular.svg");
}

/* hand-written once: paint whatever was identified */
.icon-vue:has(> svg > path[d]) > svg,
.material-design-icon:has(> svg > path[d]) > svg { visibility: hidden; }
.icon-vue:has(> svg > path[d])::after,
.material-design-icon:has(> svg > path[d])::after {
	content: ""; position: absolute; inset: 0;
	background-color: currentColor;
	mask: var(--nld-icon) center / contain no-repeat;
}
```

**Built and measured**, not sketched: a 26-icon generator run produced **26/26 rules with zero misses** — every MDI path resolved and every mapped Fluent file existed — at **13.7 KB**, extrapolating to **~45 KB for 85 icons**. Comparable to the existing `overrides.css` (28.8 KB). Generated from `@mdi/js` plus the ~40-line alias table from §7.5, in the same build-script pattern as `scripts/generate-tokens.mjs`.

**Risks, honestly:**
- **`:has()` performance** with dozens of long attribute selectors. Baseline-supported since 2023 so availability is fine in 2026, but *cost* on a large DOM is unmeasured. **Measure before committing** — this is the one thing that could kill the idea.
- **MDI version churn.** Path data changes when MDI redraws an icon; pin the version and regenerate. A miss degrades to the stock icon, which is a safe failure.
- **Sanitisation.** `NcIconSvgWrapper` runs DOMPurify over injected SVG. `d` on `path` is permitted and should pass through unaltered — **verify in the §13 spike**, because the whole idea rests on it.
- **`::after` needs a positioned parent**; `.icon-vue` is `display:flex`, so add `position:relative`.

### 14.2a What CSS alone reaches — 86% of icons, no JS

Before reaching for JavaScript, note how much of the UI is *already* inline SVG. Measured on the live Files page:

| Population | Count | Share | CSS reaches internals? |
|---|---|---|---|
| `.material-design-icon` (MDI Vue) | 65 | 54% | **Yes** |
| `.icon-vue` (`NcIconSvgWrapper`) | 39 | 32% | **Yes** |
| `img[src$=".svg"]` | 17 | 14% | **No** — whole element only |

**86% of icons are real SVG in the DOM and fully controllable from a stylesheet.** Verified by applying rules and reading computed style back:

```
stroke:     rgb(196, 73, 0)                       ← per-shape stroke
transition: fill                                  ← hover/state transitions
filter:     drop-shadow(rgba(0,0,0,0.35) …)       ← shading
animation:  nldpulse                              ← CSS keyframes
transform:  matrix(0.997564, -0.0697565, …)       ← rotate(-4deg)
fill:       rgb(16, 124, 16)                      ← per-shape fill (.icon-vue)
```

So **shading, blurring, animation, hover states and per-shape colour need no JavaScript at all** for the large majority of icons. MDI components render `fill="currentColor"`, so even plain `color` inheritance recolours them — which is why the Fluent design-language layer recolours icons for free the moment it lands.

This is not theoretical on this instance: SENERAWA's own stylesheet already does it, with `#header … svg { fill: #faf7f0 !important }`. In the test above that rule *out-specified* the experiment on `fill` — the capability was never in question, only which rule won.

**The 14% that resists is bounded and known.** An `<img>` has **zero child nodes** (measured), so there is nothing to select. CSS can still do a great deal to it — swap the whole graphic with `content: url()`, recolour it with `mask`, and apply `filter`, `opacity` and `transform` to the element — but it cannot touch one shape inside the mark, or animate part of it.

**Practical consequence:** do the CSS-only work first. It covers 86% of icons, ships in the existing cascade, and needs no runtime. Only reach for §14.2b if the remaining 17 header/nav icons specifically need internal shading or motion.

### 14.2a-ii Can CSS change icon *radii*? Mostly no — and that is the case for substitution

Since CSS reaches SVG internals (§14.2a), the natural next question is whether corner radius — the most legible Fluent signal — can be restyled rather than swapped. Tested on the live instance; the answer splits by shape primitive.

**Shape census on the Files page: 108 `<path>`, 17 `<rect>`.**

**`<rect>`: yes, exactly.** `rx`/`ry` are SVG2 geometry properties, settable from CSS, and they override the presentation attribute. Verified: `CSS.supports('rx','4px')` is true, and a rect carrying `rx="11"` computed to `rx: 4px` under a stylesheet rule.

```css
.material-design-icon svg rect, .icon-vue svg rect { rx: 4px; ry: 4px; }
```

That is a genuine radius knob — for **17 of 125 shapes**.

**`<path>`: no.** The geometry is baked into the `d` string. There is no `border-radius` for path corners, and 108 of 125 shapes are paths.

Two workarounds were tested and neither is usable as a radius control:

- **`filter: blur() contrast()`** — the CSS-only "gooey" trick. It does soften corners, but `contrast()` operates on colour channels, so a mid-tone icon **saturates to black**, and edges stay visibly fuzzy. Confirmed by rendering: a teal folder came out black with soft edges.
- **An alpha-only SVG filter** (`feGaussianBlur` + `feColorMatrix` on the alpha channel) — **colour survives**, and usefully, **a data-URI filter reference works from CSS with no DOM injection**. Verified by rendering an identical heavy blur from `filter: url("data:image/svg+xml;utf8,…#id")` and from a real `<filter>` element. But it is a morphology hack, not a radius parameter: across several strengths it hardened corners rather than rounding them by a controllable amount, and it erodes fine detail at icon sizes.

**Worth keeping from that:** CSS-only SVG filters via data URI *do* render, which makes shading, glow and tint effects available with no markup injection and no security cost. Just not radius.

**The conclusion this forces.** Icon corner radius is not a styling property — it is the drawing. Changing it means changing the glyph, which is exactly §9.1b's boundary: `nldesign` projects everything expressible as a value, and geometry is not. Fluent's icons already carry Fluent's corner language; you get it by drawing Fluent's folder, not by rounding Material's.

*(Rendering checks were Chromium via the `browse` daemon. The data-URI filter reference was subsequently confirmed in Firefox 153 as well — see §14.2a-iii.)*

### 14.2a-iii The better move: compile the icons to be CSS-receptive

§14.2a-ii concluded that geometry is not stylable. That is true *of the assets as Fluent ships them* — a single merged `<path d="…">` with everything baked in, and separate files for `regular` and `filled`.

But we control the build. Instead of copying Fluent's SVGs, **compile them into a form designed for CSS control**. Four transforms, all tested and all working:

| # | Transform | Result | Verified |
|---|---|---|---|
| 1 | `fill="var(--nldesign-icon-fill, currentColor)"` in the presentation attribute | icon colour driven by a **token**, no per-icon rule | computed `rgb(91,95,199)` |
| 2 | Emit both variants; switch with the CSS `d` property | **geometry switched from a stylesheet** — outline → filled | `CSS.supports('d', …)` true; rendered filled |
| 3 | Emit true `<rect>` where a shape really is one; `rx: var(--nldesign-icon-radius)` | **a real radius knob** | rendered at 2px and 10px |
| 4 | Stroke-authored geometry + `stroke-width` / `stroke-linejoin` | weight and corner rounding as tokens | see caveat below |

Transform 2 is the striking one. **`d` is a CSS property**, so a compiled icon set can carry Fluent's `regular` and `filled` geometry and let a stylesheet choose:

```css
:root { --nldesign-icon-weight: path("<regular d>"); }
[data-icon-weight="filled"] { --nldesign-icon-weight: path("<filled d>"); }
.nld-icon path { d: var(--nldesign-icon-weight); }
```

Icon *weight* becomes a design token, exactly like radius or type scale — which is precisely §9.1b's "extends the reach of the token app," achieved properly rather than by per-icon rules.

**Honest caveat on transform 4.** The test applied `fill:none; stroke:…` to Fluent's filled path, and the render shows why that is a cheat: Fluent's "regular" icons are *filled outlines*, so stroking them draws the outline **of the outline** — a doubled line, not a clean weight control. The `stroke-linejoin: round` versus `miter` difference is real and visibly rounds corners, but it is rounding an artifact. **A genuine stroke-based weight knob needs icons authored as strokes**, which Fluent does not ship; that is real geometry work or a different icon source, not a build flag.

**Cross-browser: confirmed, no caveats left.** Run in **Firefox 153** and **Chromium 151** via Playwright, comparing computed style and rendered output. Every mechanism behaves identically and the screenshots are indistinguishable:

| Mechanism | Firefox 153 | Chromium 151 |
|---|---|---|
| `var()` in a presentation attribute | `rgb(91,95,199)` | same |
| **CSS `d` property** (`CSS.supports('d', …)`) | **true**, renders filled | same |
| `rx` as a CSS property | 2px / 10px | same |
| `stroke-linejoin` | `round` | same |
| `:has(> svg > path[d="…"])` | matches **1 of 1** | same |
| `content: url()` replacing an `<img>` | **replaces** — Fluent glyph over a red control | same |
| data-URI SVG `filter` reference | **renders** the blur | same |

This retires the three open cross-browser questions in one pass: the `d` property, `content: url()` on `<img>` (§14.2 lever 2), and data-URI filter references (§14.2a-ii). Keeping a static `d` attribute as a fallback is still good practice — an unsupported `d` property leaves the attribute in place, so it degrades safely — but it is belt-and-braces, not a required mitigation.

**Why this changes the architecture.** The second app stops being "a folder of replacement SVGs plus a big generated stylesheet" and becomes **a small compiled icon set whose appearance is token-driven** — colour, weight, and radius-where-applicable exposed as `--nldesign-icon-*`. That is the same shape as `theme.css`: semantic tokens in, a narrow projection out. It also shrinks the generated CSS dramatically, because per-icon rules are only needed for *identification* (§14.2), not for styling.

### 14.2a-iv Vendoring several sets — and why stroke-authored ones are better

If icon geometry is going to be compiled anyway (§14.2a-iii), there is no reason to vendor only Fluent. Icon *set* becomes a third axis alongside token set and design language.

**All the obvious candidates are permissively licensed**, and all but one are already on a 24-unit grid:

| Set | Licence | Icons | viewBox | Authoring | Size |
|---|---|---|---|---|---|
| Fluent (regular 24) | MIT | 2,457 | 24 | filled path | 1.5 MB |
| **Lucide** | **ISC** | 2,022 | 24 | **stroke** | 0.9 MB |
| **Tabler outline** | **MIT** | 5,130 | 24 | **stroke** | 2.7 MB |
| Tabler filled | MIT | 1,054 | 24 | filled path | 0.7 MB |
| Phosphor regular | MIT | 1,512 | **256** | filled path | 0.7 MB |

**The important column is authoring, not count.** Stroke-authored sets ship as:

```html
<svg fill="none" stroke="currentColor" stroke-width="2"
     stroke-linecap="round" stroke-linejoin="round">
```

Every one of those is a **live CSS property**, so a stroke set exposes knobs a filled set cannot:

| Control | Stroke sets (Lucide, Tabler outline) | Filled sets (Fluent, Phosphor) |
|---|---|---|
| Colour | `stroke` | `fill` |
| **Weight** | **`stroke-width` — continuous** | only by swapping `d` between shipped variants |
| **Corner radius** | **`stroke-linejoin` — the knob §14.2a-ii could not find** | none |
| End caps | `stroke-linecap` | none |

Verified in **Firefox 153 and Chromium 151**, computed style and render: `stroke-width` at 1 / 2 / 3 produces visibly lighter-to-heavier glyphs; `stroke-linejoin: miter` versus `round` visibly sharpens or rounds the corners; `stroke: var(--nld-icon-fill)` tints. Identical in both engines.

**So the radius problem was an asset-choice problem.** Fluent cannot express corner radius as a token because it ships merged filled paths. Lucide and Tabler can, natively, with no build tricks at all — which makes a stroke set the better default for a *token-driven* icon system, whatever the visual target.

### How to build them

A generator, in the pattern of the existing `scripts/build-icons.js` and `generate-tokens.mjs`:

1. **Vendor from npm**, pinned. Keep the licence file beside the output, as the repo already does for Fira Sans and the Amsterdam set.
2. **Normalise the grid.** Everything to a 24 viewBox — Phosphor is 256 and needs scaling; the rest are already 24. Strip fixed `width`/`height` so CSS sizes them.
3. **Strip set-specific noise.** Tabler ships a spacer `<path stroke="none" d="M0 0h24v24H0z" fill="none"/>` in every icon, and vendor classes (`icon-tabler-folder`, `lucide-folder`) that would collide.
4. **Replace hardcoded presentation attributes with token hooks**, so appearance is token-driven rather than per-icon CSS:
   ```html
   <svg fill="none" stroke="var(--nldesign-icon-color, currentColor)"
        stroke-width="var(--nldesign-icon-weight, 2)"
        stroke-linejoin="var(--nldesign-icon-corner, round)">
   ```
5. **Emit a manifest**, not a pile of files: `semantic name → { set → inner markup }`. The alias table from §7.5 supplies the semantic names, and one table then serves every set.
6. **Generate identification CSS separately** — the path fingerprints of §14.2, which are about *finding* Nextcloud's icons, not styling ours. Keeping the two apart is what stops the generated stylesheet growing with the icon count.

**Cost check.** Vendoring all five sets is ~6.5 MB of source, but only the selected set's icons ship in the compiled output, and only for the inventoried surface — an 85-icon set is tens of kilobytes. The manifest is the deliverable; the vendored sets are build inputs.

**One caveat worth stating.** Mixing sets within one instance is a design mistake, not a feature. The manifest should make a set an all-or-nothing choice, with a documented fallback for names a set lacks, rather than silently drawing a Lucide folder next to a Fluent document.

### 14.2b Idea 1b — inline the `<img>` icons, tested live

CSS can *recolour* an `<img>` and swap what it displays, but an `<img>` is an opaque replaced element: **no per-shape fill, no internal animation, no gradient or blur on part of the mark.** Only whole-element filters. If the goal is Fluent's behaviour — shading, hover transitions, motion — that is the wrong container.

**Replacing them with real inline `<svg>` works, and was verified on cloud.opus95.com (NC 33.0.5):**

```js
const res = await fetch(img.getAttribute('src'));       // same-origin
const svg = new DOMParser()
  .parseFromString(await res.text(), 'image/svg+xml').documentElement;
svg.setAttribute('role', 'img');
if (img.alt) svg.setAttribute('aria-label', img.alt);
img.replaceWith(svg);
```

Measured on the Files page: **14 of 14 SVG `<img>` converted, 0 failures, 0 remaining.** Served same-origin as `image/svg+xml` at ~530 bytes each, so no CORS and no meaningful payload.

What that unlocks, confirmed by computed style on a converted icon:

```
filter:    blur(1.2px) drop-shadow(rgba(0,0,0,0.4) 0px 1px 2px)
fill:      rgb(91, 95, 199)        ← #5b5fc7, per shape
animation: nldspin                 ← CSS keyframes
transition: fill                   ← hover states
```

All four are impossible on the `<img>` it replaced.

**Two things the census settles.** Not every `<img>` should be inlined — of 29 on the Files page, **22 were SVG icons**, and the rest were a user avatar and five file previews, which are genuine raster content. Target `img[src$=".svg"]`, never `img`.

**Durability — corrected, and better than first reported.** An earlier draft claimed "Nextcloud re-renders them, so a `MutationObserver` is not optional." **That was wrong.** The evidence behind it was a `goto`, which is a full page load — that resets any DOM change trivially and proves nothing about Vue.

Tested properly, all within one document (`sameDocument: true` throughout):

| Event | Inlined SVGs surviving |
|---|---|
| SPA navigation (Files → Recent) | **17 of 17** |
| Opening a folder — rebuilds the file list | **17 of 17** |
| Opening the Settings menu | **17 of 17** |

**Vue does not clobber them.** The `<img>` icons live in the header and left nav — 9 in `#header`, 8 elsewhere, **0 in the file list**, because file rows use the MDI Vue components instead. Those regions simply do not re-render on in-page navigation.

The real gap is **late arrival, not re-rendering**, and it is small. Counting `img[src$=".svg"]` after a fresh load:

```
t+0ms  17      t+500ms  18      t+2s  18      t+5s  18
```

**One icon of eighteen** appears after initial DOM, inside 500 ms (a lazily-registered app icon), and nothing arrives after that.

So the implementation is simpler than "an observer is mandatory": **a pass on page load, plus a short re-scan or a briefly-lived observer that disconnects once the DOM settles.** A full-page-load reset needs no special handling at all, because a Nextcloud app's script runs on every page load by construction.

This is still **idea 2's mechanism** (§14.3) rather than a CSS technique — CSS can change what an icon *looks like*; only inlining changes how it *behaves*. But the runtime cost is a startup pass, not a permanently-attached observer watching every mutation.

### 14.2c Security — inlining trades a browser sandbox for a policy, and the CSS is public

Two findings, both verified on cloud.opus95.com, that were missing from the analysis above. Together they narrow §14.2b considerably.

#### The theming stylesheet is a public document

`GET /apps/theming_customcss/styles` returns **HTTP 200 with no authentication** — 16,760 bytes, no cookies sent. This is not a misconfiguration: the login page references it, so it *must* be readable before sign-in. It is inherent to how Nextcloud theming works.

**Consequence: everything in that stylesheet is published**, including comments. The SENERAWA stylesheet currently publishes its internal engineering notes to anyone who asks — which Nextcloud version broke what, dated incident notes, and candid admissions of what is not understood:

```
 * The precise mechanism is NOT established, so do not trust a tidy story
 * about it. What is verified is how Nextcloud 33 core consumes them:
```

Nothing here is a credential, and the design tokens themselves are meant to be public. But **write that file as a public document**: keep the reasoning in the tracked source and the repository, and keep the deployed copy to what has to ship. It is also a reconnaissance surface — it names versions, apps and known-broken behaviour to an unauthenticated reader.

#### `<img>` is a sandbox. Inlining removes it.

This is the part that cuts against §14.2b. An SVG loaded through `<img src="…">` is **strictly sandboxed by the browser**: it cannot execute script, cannot load external resources, cannot reach the parent document. Inlining it as live DOM removes that boundary entirely.

**The instance's CSP does most of the work of putting it back:**

```
default-src 'none'; script-src 'nonce-…' blob: 'wasm-unsafe-eval';
script-src-elem 'strict-dynamic' 'nonce-…'; style-src 'self' 'unsafe-inline';
img-src 'self' data: blob: …; connect-src 'self' blob: …
```

Nonce-based `script-src` with `strict-dynamic` blocks both injected `<script>` and inline `onload=` handlers, and `connect-src 'self'` blocks exfiltration. So this is not remote code execution. **But `style-src 'unsafe-inline'` is permitted**, so an injected `<style>` inside an SVG *would* apply to the page — enough for overlay and UI-redress mischief within the origin. And a hard browser sandbox has been swapped for continued CSP correctness.

**The sharper problem is provenance.** The icons §14.2b proposes fetching are not ours:

```
/custom_apps/calendar/img/calendar.svg     /custom_apps/mail/img/mail.svg
/custom_apps/contacts/img/app.svg          /custom_apps/tables/img/app.svg
```

They belong to **third-party apps**. Fetch-and-inline turns "any app that ships an icon" into "any app that injects markup into every page of every session, including an administrator's." That is a privilege path the `<img>` sandbox currently closes, and the app store's review is not a substitute for it.

Worse, the §14.2b snippet uses `DOMParser` + `replaceWith` with **no sanitisation at all** — strictly less careful than Nextcloud's own `NcIconSvgWrapper`, which runs the injected markup through DOMPurify first.

#### The fix is cheap and makes the design better

**Only inline SVGs the app itself ships.** Vendored Fluent icons are MIT, reviewed once at build time, and never change at runtime. Never fetch-and-inline from `/apps/*` or `/custom_apps/*`.

That single rule moves the ingestion surface from *every installed app, at runtime* to *our build, reviewed once* — and it costs nothing, because the whole point was to replace those icons anyway. There is no reason to inline a third-party icon you are about to substitute.

For anything left in third-party hands, stay on the CSS route: **`mask` and `content: url()` never introduce foreign DOM**, so they keep the sandbox intact. Combined with §14.2a — 86% of icons are already inline SVG and need no injection at all — the residual case for runtime inlining is small enough to question whether it is worth any added surface.

### 14.3 Idea 2 — JS patching *(fallback, and strictly more capable)*

If `:has()` is too slow, do the same lookup in JavaScript. A `MutationObserver` watches for added nodes, reads `path.getAttribute('d')`, looks it up in a `Map`, and replaces the SVG. Same fingerprint table, O(1) per icon.

More capable than CSS: it can rewrite `<img src>` for app icons, handle icons rendered into portals, and adjust `viewBox` when Fluent and MDI disagree. Costs a script on every page and needs care to avoid fighting Vue re-renders — observe and re-apply rather than assuming one pass.

**Ship idea 1 if it performs; keep idea 2 as the escape hatch.** Both consume the same generated mapping table, so the table is the real asset and the delivery mechanism is swappable.

### 14.4 Idea 3 — app icons via `content: url()`

For the nav rail, `<img :src>` still yields to CSS content replacement from inside an app (§7.3, lever 2):

**Corrected against NC 33.0.5 — the class-based selector does not exist.** `#header .app-menu-entry__icon img` matches **zero** elements. The header has 15 `<img>` and they carry **no class at all**. They do carry distinctive `src` paths, so the fingerprint principle simply moves from class to `src`:

```css
#header img[src$="/files/img/app.svg"],
#header img[src$="/calendar/img/calendar.svg"] {
	content: url("data:image/svg+xml;base64,…") !important;
}
```

Tested live: matched, and `content` applied — and **confirmed in Firefox 153 and Chromium 151** (§14.2a-iii). This is what makes the nav rail reachable without `themes/`.

### 14.5 Idea 4 — restyle instead of replace *(cheapest, zero fragility)*

Worth pricing before building any of the above. MDI and Fluent are both 24×24 geometric line families; much of the perceived difference is *container and weight*, not glyph geometry. Rounded icon containers, Fluent sizing, `currentColor` inheritance from the design-language layer, and consistent optical sizing get a substantial share of the effect with **no fingerprints, no generation, no upgrade exposure.**

This is the control condition for the §13 spike: apply tier 1, look, and see how much is left to fix. It may be most of the way.

### 14.6 Idea 5 — fix it upstream *(the only permanent answer)*

None of the above is a supported contract. The real fix is one line in `NcIconSvgWrapper`: emit a per-icon class or `data-icon` attribute alongside `icon-vue`. That would give every theming author a stable hook and make idea 1 unnecessary.

Long lead time, so it does not unblock anything now — but it costs an issue and a small PR, Nextcloud accepts theming and accessibility contributions, and the sovereign-migration context (§2) is a credible motivation. **File it regardless of what you build**, and note the irony: this app already carries `.content[data-v-1f87d811]` precisely because no such hook exists.

### 14.7 Dead ends — checked, so nobody re-checks them

| Approach | Verdict |
|---|---|
| `themes/` folder shadowing | **Out by constraint** — needs server-root write access |
| `LoadAdditionalEntriesEvent` (NC 31+) | **No.** Bare marker class; apps *add* their own navigation entries, they cannot modify another app's entry or its icon |
| Theming app `IconBuilder` | **No.** Recolours app icons, never substitutes them |
| `core/img/filetypes/*` replacement | **No.** Even when it works, the Files list no longer renders these (§7.2) |
| Service worker rewriting icon responses | Only reaches URL-fetched icons, not inlined SVG; conflicts with Nextcloud's own SW. Not worth it given ideas 1–3 |

### 14.8 Recommended combination

1. **Idea 4 first** — ship the design-language layer, look at the result. It may be enough, and it costs nothing extra.
2. **Idea 1 for what remains**, gated on a `:has()` performance measurement and DOMPurify passthrough (both in the §13 spike).
3. **Idea 3 for the nav rail** — the highest-visibility surface, reachable without `themes/`.
4. **Idea 2 held in reserve**, sharing idea 1's mapping table.
5. **Idea 5 filed upstream** on day one, so the permanent fix is in flight while you ship the workaround.

The mapping table is the durable asset. Everything above is a delivery mechanism for it, and they are interchangeable — which is exactly the property §9 says this codebase should want.
