# Summer Breeze — soft, airy theme

Summer Breeze is a calm, light-blue design system for Nextcloud. It is gentle on
the eyes and relaxed in nature: the Inter typeface, large rounded surfaces,
low-contrast cool-tinted shadows, pastel status colours, and a pale-blue gradient
navigation. It generalises the **Acato** design language (created for the
`docudesk` app) into a theme that any Nextcloud instance can switch on, so the
look is no longer trapped inside one app's bespoke components.

## Activate it

Admin → Theming → NL Design, pick **Summer Breeze** as the token set. Or via CLI:

```bash
occ config:app:set thematiq token_set --value summer-breeze
```

Switch back to stock with `--value nextcloud`, or any municipality token set.

## How it is built (Tier 1 — shipped)

Summer Breeze is a self-contained **design system** in the `thematiq` app. It does
not reuse the government `nldesign` stylesheets (those deliberately strip shadows
and sharpen corners — the opposite of this aesthetic). Everything lives under a
`--summer-*` token namespace so it never collides with `--nldesign-*`.

| File | Role |
| --- | --- |
| `css/tokens/summer-breeze.css` | `--summer-*` token values (palette, radii, shadows, gradient, Inter stack). The file the token-set scanner discovers. |
| `css/systems/summer-breeze/fonts.css` + `fonts/Inter-*.woff2` | Inter variable font (SIL OFL). |
| `css/systems/summer-breeze/theme.css` | Maps `--summer-*` onto Nextcloud core variables (`--color-primary`, `--color-*-light`, `--border-radius-*`, `--color-box-shadow`, …) so the look cascades through core **and** the `Cn*` component library. |
| `css/systems/summer-breeze/element-overrides.css` | The chrome the variable mapping can't express: pale-blue gradient navigation as a floating rounded card, solid-blue active pill, soft card shadows, pill inputs, background-image strip, dashboard greeting legibility. |
| `design-systems.json` | Registers the `summer-breeze` stylesheet bundle. |
| `token-sets.json` | Registers the `summer-breeze` token set (`design_system: summer-breeze`, theming colours `#2874D1` / `#EAF2FB`). |

Because the `Cn*` library and Nextcloud core both consume CSS variables, ~80% of
the Acato look is delivered purely by remapping those variables — no component
source changes. Verified live on stock **Files** and **Dashboard** (apps with no
styling of their own).

## What Tier 1 cannot do exactly (Tier 2 — nc-vue work)

A handful of Acato signatures are baked into `docudesk`'s bespoke `Dd*` components
and into hardcoded values inside the shared `nextcloud-vue` library, so a theme
cannot reach them. To let **any** app match Acato pixel-for-pixel without forking
components, the shared library needs these changes:

1. **Themeable card shadows.** `CnCard` / `CnObjectCard` hardcode
   `box-shadow: 0 2px 8px …` ([`src/css/card.css:18`], `CnCard.vue:312`). Introduce
   `--cn-card-shadow` / `--cn-card-shadow-hover` vars defaulting to the current
   values; Summer Breeze then sets them to `--summer-shadow-panel`. Today the theme
   overrides the whole rule with `!important`, which works but is brittle.

2. **Sliding-thumb view toggle.** `CnIndexPage` renders the Tiles/List toggle as a
   grouped `NcCheckboxRadioSwitch`, not Acato's pill with an animated sliding thumb
   (`docudesk/src/components/DdViewToggle.vue`). Port that as a `CnViewToggle`
   variant so the segmented control matches.

3. **First-class pastel badges.** `CnStatusBadge` uses `--color-*-light` with a
   hardcoded `rgba()` fallback ([`src/css/badge.css`]). Drop the fallback / promote
   the pastel variants so badge fills are fully theme-driven (Summer Breeze already
   defines `--color-*-light`).

4. **Optional: gradient surface hook.** No component exposes a gradient background;
   the gradient navigation is reachable only by overriding the core
   `#app-navigation` selector (as Summer Breeze does). If a component-level gradient
   slot is wanted, add an opt-in prop on the app-shell wrapper.

Delivery: one OpenSpec change against `nextcloud-vue`, then a new beta. After that
the Summer Breeze design system drives these via variables instead of `!important`
element overrides, and the element-overrides sheet shrinks to just the navigation
gradient and background-image strip.
