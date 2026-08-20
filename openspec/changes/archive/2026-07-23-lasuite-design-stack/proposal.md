---
kind: code
---

## Why

**The EDIC / MijnBureau play (USER-DIRECTED).** The Dutch government is building a sovereign
workplace: MinBZK/mijn-bureau (EUPL-1.2, OpenBSW programme, Rijksoverheid + Gemeente Amsterdam +
VNG) describes itself as the Dutch implementation of a *European* collaboration suite maintained
"samen met Frankrijk en Duitsland" — its showcased components are literally La Suite numérique's
apps (Chat, Docs, Meet, AI-chat screenshots in the repo's `index.md`/`assets/`), while the
German/openDesk side of the bundle contributes Nextcloud for files. Research files
04-nlds-ecosystem and 05-gap-analysis record the same: MijnBureau/DAWO pilots (Amsterdam, Ede,
Den Bosch, Zaanstad + VNG + BZK) combine La Suite + openDesk-incl.-Nextcloud, and "huisstijl
across components = open problem". Today a MijnBureau user clicks from Docs (Cunningham design
system: Inter-class type, 4px radii, La Suite brand `#4844AD`, its grey scale) into Nextcloud
(NC blue `#0082c9`, NC type, pill radii) and the visual seam screams "two products". nldesign is
the only component in the bundle positioned to close that seam — a bundled La Suite design stack
makes a Nextcloud core visually match the surrounding La Suite chrome, which is a concrete,
demonstrable contribution to the EDIC/sovereign-workplace channel (the 2026 distribution window
flagged as a strategic risk in 05-gap-analysis).

**Verified research on La Suite's design system** (WebFetch/WebSearch, 2026-07-23):

- La Suite's design system is **Cunningham** — `github.com/suitenumerique/cunningham`, **MIT
  licensed**, npm `@gouvfr-lasuite/cunningham-react` (v4.x). Its token pipeline generates a
  `cunningham-tokens.css` of CSS custom properties.
- **Token shape**: hierarchical custom properties prefixed `--c--`, e.g.
  `--c--globals--colors--brand-650`, categories `globals`/`contextuals` ×
  `colors|fonts|spacings|transitions|breakpoints`. Verified live in La Suite Docs' shipped
  `src/frontend/apps/impress/src/cunningham/cunningham-tokens.css`, whose first tokens are
  `--c--globals--colors--logo-1-light: #4844ad` / `--c--globals--colors--logo-1-dark: #bec5f0`.
- **Palette** (from `suitenumerique/ui-kit` `cunningham.ts`): brand scale `brand-050 #EEF1FA` →
  `brand-650 #4844AD` (the La Suite blue-violet) → `brand-950 #11131F`; greyscale `gray-000
  #FFFFFF` → `gray-1000 #000000`; semantic `success-500 #1E884A`, `warning-500 #CB5000`,
  `error-500 #E82322`, `info-500 #0077DE`; component `border-radius: 4px`.
- **Fonts**: the configured stack is `"Marianne, Inter, Roboto Flex Variable, sans-serif"`.
  **Marianne is French-state-restricted** — suitenumerique/meet#426 ("Marianne font compatible
  with MIT license?") records that it "cannot be proposed as is for self-hosters"; the sanctioned
  approach is an open fallback. **Inter (SIL OFL 1.1) is the first open font in La Suite's own
  stack** and is what non-French deployments actually render — so our bundle self-hosts Inter
  and keeps `Marianne` first in the `font-family` list as a *local()-only* name (French-state
  machines with Marianne installed get it; nobody receives Marianne files from us).

**License compliance**: everything bundled is MIT (Cunningham token values) or SIL OFL 1.1
(Inter woff2, same license as the already-bundled Fira Sans) — both EUPL-compatible. NO Marianne
font files, NO La Suite/Gouvernement logos or trademarks (the token set's logo slot stays empty).

The goal wording is deliberate: a **pixel-adjacent match to La Suite chrome** — same type ramp,
palette, radii, and surface tones so the seam disappears at a glance — not a pixel-perfect clone
of a React component library inside Nextcloud's DOM.

## What Changes

- **New canonical spec `lasuite-stack`**: a fourth shipped design system `lasuite` following the
  existing pattern (`design-systems.json` entry + `css/systems/lasuite/` ordered bundle):
  1. `systems/lasuite/fonts` — self-hosted Inter woff2 @font-face (SIL OFL 1.1, `font-display:
     swap`), family stack `Marianne, Inter, sans-serif` with Marianne resolvable only via
     `local()`.
  2. `systems/lasuite/defaults` — Cunningham-derived token values transcribed as `--lasuite-*`
     custom properties on `:root` (curated subset: brand scale, greyscale, semantic colors,
     radii, spacing basics — not the full 14k-line token dump).
  3. `systems/lasuite/bridge` — maps `--lasuite-*` onto the `--nldesign-*` namespace and the
     Nextcloud `--color-*` variables (respecting the css-architecture invariants: no
     `--color-main-background` override, dark-compat variables untouched, ADR-CSS-002
     `!important` discipline).
  4. `systems/lasuite/element-overrides` — NC chrome adjustments (header, navigation, buttons,
     4px radii) for the pixel-adjacent match.
- **MODIFIED `css-architecture` spec**: design-system requirements updated — `lasuite` joins the
  shipped systems (directory structure + resolution scenarios).
- **MODIFIED `token-sets` spec**: a shipped `lasuite` token set entry (`css/tokens/lasuite.css` +
  manifest entry with `design_system: "lasuite"`, theming metadata `primary_color: "#4844AD"`,
  `background_color: "#FFFFFF"`, **no logo** — the slot stays empty for trademark reasons).
- **Visual-comparison verification** is a first-class task: side-by-side screenshot of a real La
  Suite app page vs the themed NC on the 8080 dev instance, checked for type/palette/radius
  parity.
- No Vue, no DB tables, no new PHP services — the existing `DesignSystemService` +
  `TokenSetService` machinery picks the new system up from the two JSON manifests.

## Impact

- `design-systems.json` — new `lasuite` entry with the 4-stylesheet bundle
- `token-sets.json` — new `lasuite` set entry (`design_system: "lasuite"`, theming metadata,
  empty logo slot)
- `css/systems/lasuite/fonts.css`, `defaults.css`, `bridge.css`, `element-overrides.css` (new)
- `css/systems/lasuite/fonts/` — Inter woff2 files (Latin subset, weights 400/500/600/700 +
  italics 400/700) + OFL.txt license text
- `css/tokens/lasuite.css` (new Layer-3 token file)
- `openspec/specs/lasuite-stack/` (new), `openspec/specs/css-architecture/`,
  `openspec/specs/token-sets/`
- `appinfo/info.xml` — version bump (cache-bust)
- Tests: PHPUnit manifest/audit coverage picks the new set up automatically
  (`ShippedTokenSetAuditService`); add explicit fixtures where noted in tasks
- Cross-reference: the `dark-mode-token-variants` change will auto-derive a dark variant for
  `lasuite` once both land (Cunningham even ships `logo-1-dark` tokens — good hand-authored
  override source); no coupling, each change is independently buildable
