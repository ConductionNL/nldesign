# Proposal: full-nextcloud-token-support-from-nldesign

## Summary

Create a complete, documented mapping between all Nextcloud CSS variables and NL Design System tokens, expand the nldesign app to support all available NL Design System token sets (currently 48 organizations), introduce component-level token support, and automate token updates via a nightly GitHub Actions workflow.

## Motivation

The nldesign app currently maps only a subset of Nextcloud's ~97 CSS variables to ~35 custom `--nldesign-*` tokens, and supports only 5 token sets (Rijkshuisstijl, Amsterdam, Utrecht, Den Haag, Rotterdam). This creates three problems:

1. **Incomplete Nextcloud coverage** — Many Nextcloud CSS variables (animations, spacing, gradients, placeholders, etc.) are not mapped, causing visual inconsistencies where NL Design styling doesn't reach.
2. **Limited organization support** — The NL Design System ecosystem now has 48+ token sets for Dutch municipalities and organizations, but only 5 are available in the app.
3. **No component-level tokens** — The NL Design System architecture supports component-level tokens (e.g., `--utrecht-button-*`, `--utrecht-heading-*`) that enable fine-grained styling, but these are not yet supported.

## Affected Projects

- [x] Project: `nldesign` — Primary target: overrides, token generation, GitHub workflow, documentation

## Scope

### In Scope

1. **Complete Nextcloud variable audit** — Investigate all Nextcloud CSS files (core, theming app, dist) to produce a definitive list of every CSS custom property Nextcloud defines (~97 variables from `DefaultTheme.php` plus any additional ones in SCSS/CSS files).

2. **Comprehensive overrides.css** — Create a new `overrides.css` that contains ALL Nextcloud CSS variables, each mapped to the appropriate `--nldesign-*` variable. Variables without an appropriate NL Design mapping are commented out with a comment explaining why (e.g., `/* --animation-quick: no NL Design equivalent — Nextcloud animation timing */`).

3. **Utrecht-to-NLDesign bridge file** — Create a separate `utrecht-bridge.css` file that maps `--utrecht-*` component tokens to `--nldesign-*` tokens. This is a **temporary** file: the NL Design System currently uses `--utrecht-*` prefixed component tokens as its standard, but this is expected to change to a vendor-neutral prefix in the future. When that happens, this bridge file can simply be removed. The file should include a header comment documenting this intent.

4. **Default token values** — Define sensible defaults for ALL `--nldesign-*` tokens in a base/defaults CSS file. This way incomplete token sets simply don't override all available tokens — no fallback logic needed. Token sets only need to define the values they want to customize.

5. **mappings.md documentation** — Create a `mappings.md` file with a table of every Nextcloud variable, its NL Design mapping (or "unmapped" with reason), and the category it belongs to.

6. **Auto-generate token sets from official JSON** — Build a script/tool that reads the official JSON token files from [nl-design-system/themes](https://github.com/nl-design-system/themes) and generates CSS token files for the nldesign app. This replaces manual curation.

7. **Nightly GitHub Actions workflow** — Create a GitHub Actions workflow (`sync-tokens.yml`) that runs every night to:
   - Check the `nl-design-system/themes` repository for changes
   - Re-generate token CSS files if upstream tokens have changed
   - Open a PR with the updated token files (or auto-commit to a branch)
   - This ensures the nldesign app stays in sync with the ecosystem automatically

8. **Expand token set support** — Support ALL available NL Design System token sets from the themes repository. Current ecosystem has 48 organizations:
   - **Currently supported (5):** Rijkshuisstijl, Amsterdam, Utrecht, Den Haag, Rotterdam
   - **To add (~43):** Groningen, Haarlem, Haarlemmermeer, Tilburg, Nijmegen, Leiden, Zwolle, Zaanstad, Purmerend, Bodegraven-Reeuwijk, Borne, Buren, Demodam, Dinkelland, Drechterland, Duiven, DUO, Enkhuizen, Epe, Hoeksche Waard, Hoorn, Horst aan de Maas, Leidschendam-Voorburg, Noaberkracht, Noordoostpolder, Noordwijk, NORA, Provincie Zuid-Holland, Riddeliemers, Ridderkerk, Stedebroec, Tubbergen, Venray, Voorne aan Zee, Vught, Westervoort, XXLLnc, Zevenaar
   - Incomplete token sets are supported — they simply won't override all default `--nldesign-*` values.

9. **Component-level token support** — Introduce component-level NL Design tokens using the `--nldesign-*` prefix. The NL Design System currently defines component tokens under `--utrecht-*` (button, textbox, heading, link, table, badge, form, etc.). These are mapped to `--nldesign-*` via the bridge file (see item 3).

10. **Expand `--nldesign-*` token vocabulary** — Add new nldesign tokens to cover currently unmapped Nextcloud variables where sensible (e.g., `--nldesign-color-placeholder`, `--nldesign-color-scrollbar`, `--nldesign-color-favorite`, `--nldesign-animation-quick`, `--nldesign-spacing-*`).

11. **Update README with sources** — Add a sources section to the nldesign README documenting:
    - NL Design System themes repository link
    - NL Design System design tokens handbook
    - Individual organization design system links
    - How the nightly sync workflow works
    - How to add a new token set

### Out of Scope

- Changing the visual design of the existing 5 token sets (colors stay the same)
- Modifying other apps to consume component tokens
- Dark mode / high contrast theme variants (future work)
- Custom/proprietary fonts per organization (licensing constraints)

## Approach

### CSS Architecture (load order)

```
1. fonts.css              — Font declarations (Fira Sans)
2. defaults.css           — All --nldesign-* tokens with sensible defaults
3. tokens/{org}.css       — Organization-specific token overrides (auto-generated)
4. utrecht-bridge.css     — Maps --utrecht-* → --nldesign-* (temporary)
5. theme.css              — Maps --nldesign-* → Nextcloud --color-* variables
6. overrides.css          — ALL Nextcloud variables, mapped or commented
7. Optional: hide-slogan.css, show-menu-labels.css
```

### Token generation pipeline

1. **Script** (`scripts/generate-tokens.js` or Python): Reads JSON token files from `nl-design-system/themes`, flattens token hierarchy, converts to CSS custom properties with `--nldesign-*` prefix, writes one CSS file per organization.
2. **GitHub Action** (`sync-tokens.yml`): Runs nightly, clones themes repo, runs generation script, compares output with current files, opens PR if changes detected.

### Implementation steps

1. Audit Nextcloud variables — extract definitive list from PHP + SCSS sources
2. Create `defaults.css` — all `--nldesign-*` tokens with sensible default values
3. Build token generation script — JSON → CSS conversion
4. Run generator for all 48 token sets
5. Create `utrecht-bridge.css` — `--utrecht-*` → `--nldesign-*` mapping
6. Rewrite `overrides.css` — every Nextcloud variable, mapped or commented
7. Write `mappings.md` — complete documentation table
8. Create `sync-tokens.yml` GitHub Actions workflow
9. Update admin settings UI — dynamic token set list from available CSS files
10. Update README — sources, architecture, contribution guide

## Cross-Project Dependencies

- Depends on the [nl-design-system/themes](https://github.com/nl-design-system/themes) repository for official token values.
- The `openspec/specs/nl-design/spec.md` shared spec may need updating to reflect expanded token support.
- Other apps (opencatalogi, mydash, softwarecatalog) will benefit from component tokens but don't need changes for this work.

## Rollback Strategy

- The current `overrides.css`, `theme.css`, and token files can be restored from git history.
- New token set CSS files are additive — removing them only removes support for those organizations.
- The admin settings default remains `rijkshuisstijl`, so existing deployments are unaffected.
- The nightly workflow creates PRs rather than auto-merging, so token updates are reviewable.

## Capabilities

### New Capabilities
- `nextcloud-variable-mapping` — Complete audit and mapping of all Nextcloud CSS variables to NL Design tokens, including mappings.md documentation
- `extended-token-sets` — Support for all 48+ NL Design System organization token sets, auto-generated from official JSON
- `component-tokens` — Component-level NL Design token support (button, form, heading, etc.) with temporary utrecht-bridge.css
- `token-sync-workflow` — Nightly GitHub Actions workflow to sync tokens from upstream nl-design-system/themes

### Modified Capabilities
- `nl-design` — Existing spec needs updating to reflect expanded token coverage and component tokens

## Decisions (from open questions)

1. **Auto-generate tokens** — Token CSS files will be auto-generated from the official JSON files in `nl-design-system/themes` via a generation script, not manually curated.
2. **Use `--nldesign-*` prefix** — All tokens use the `--nldesign-*` prefix for consistency. A separate `utrecht-bridge.css` maps `--utrecht-*` → `--nldesign-*`. This bridge is temporary and can be removed when the NL Design System moves to a vendor-neutral prefix.
3. **Support incomplete token sets** — All available token sets are included regardless of completeness. A `defaults.css` file provides sensible defaults for all `--nldesign-*` tokens, so incomplete sets simply don't override everything.

## Sources

- [NL Design System Themes Repository](https://github.com/nl-design-system/themes) — Official token definitions for all organizations
- [NL Design System Design Tokens Handbook](https://nldesignsystem.nl/handboek/design-tokens/) — Architecture and guidelines
- [NL Design System Participation Guide](https://nldesignsystem.nl/meedoen/design-tokens/) — How to contribute tokens
- [Rijkshuisstijl Community](https://github.com/nl-design-system/rijkshuisstijl-community) — Dutch national government design tokens
- [Utrecht Design System](https://github.com/nl-design-system/utrecht) — Component tokens reference implementation
