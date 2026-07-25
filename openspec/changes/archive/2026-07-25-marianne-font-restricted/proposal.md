---
kind: code
---

## Why

The `lasuite` design system reproduces the chrome of La Suite numérique (Cunningham design
system). La Suite's own configured font stack is `Marianne, Inter, Roboto Flex Variable,
sans-serif`: **Marianne is the official typeface of the French State.** Today the app renders
Inter for everyone — `css/systems/lasuite/fonts.css` self-hosts Inter and references Marianne
**only** via `local()`, so a French-administration device with Marianne installed at OS level
gets it and nobody else receives a single Marianne byte. That is a correct default, but it
means a French government running this app never gets true Marianne rendering from the server,
even though it is lawfully entitled to it.

Conduction serves French governments. Marianne can be lawfully self-hosted **for those
customers** — but it is a legally restricted asset, not a permissive open font, so it must
never be silently shipped to arbitrary instances:

1. **The restriction is real and explicit.** Marianne is *"réservée aux administrations de
   l'État"* — reserved for French State administrations. `suitenumerique/meet#426` ("Marianne
   font compatible with MIT license?") records that it "cannot be proposed as is for
   self-hosters." This is why the current `lasuite-stack` spec forbids bundling it at all
   (`### Requirement: La Suite Fonts Layer With Open Fallback`, `### Requirement: La Suite Asset
   License Compliance` — the compliance test asserts *no* app path matches `/marianne/i`).

2. **A lawful, redistributable source exists.** `@gouvfr/dsfr@1.15.1` (the official French
   government Design System, DSFR) ships **16 Marianne `woff2`** files at
   `dist/fonts/Marianne-*.woff2` under the **Etalab Open Licence 2.0** (`etalab-2.0`). The
   Etalab licence permits redistribution; the *Marianne-as-French-State-identity* restriction
   on **who may use it** sits on top of that and is a usage condition, not a redistribution
   bar. So we may bundle the files, provided we (a) carry the licence and the restriction
   verbatim, and (b) gate their activation on the operator affirming they are entitled to use
   them.

3. **Silent bundling is the failure mode to prevent.** The safe design is: bundle the files,
   keep them **inert by default** (no `@font-face` `url()` source is emitted, so the browser
   never fetches a Marianne byte and Inter renders), and only emit the real self-hosted
   `@font-face Marianne` after an admin has ticked an *"our organisation is a French State
   agency"* acknowledgement. Default OFF, Inter used, until acknowledged.

This change bundles Marianne from DSFR, self-hosted and CSP-clean, behind an admin
acknowledgement gate, with the licence, a user agreement, and unmissable point-of-selection
notices — and corrects the two `lasuite-stack` requirements and the `claim-accuracy` spec that
currently assert Marianne is never bundled.

## What Changes

- **NEW capability `marianne-font`** (canonical spec slug `marianne-font`):
  - Bundle the DSFR Marianne `woff2` files into `css/systems/lasuite/fonts/marianne/`
    (weights DSFR ships: Light, Regular, Medium, Bold, plus their italics where available),
    self-hosted, no external request. Sourced from `@gouvfr/dsfr@1.15.1` via a devDependency
    build step (mirroring how `scripts/build-icons.js` materialises the nc-vue icon packs), or
    committed with a documented provenance — either way the licence text travels with them.
  - Real self-hosted `@font-face Marianne` declarations placed in a **separate** stylesheet
    `css/systems/lasuite/marianne.css`, emitted by `CssInjectionService` **only** when the
    active design system is `lasuite` **and** the acknowledgement config flag is set. When the
    flag is off, no `url()` source for Marianne exists at runtime, so Inter renders (the family
    stack in `fonts.css` stays `Marianne, Inter, sans-serif`).
  - **Admin acknowledgement gate** — app config flag `nldesign` / `marianne_enabled`
    (default `'0'`). A settings control lets an admin tick *"our organisation is a French State
    agency (administration de l'État)"*; only then is Marianne activated. Untick reverts to
    Inter. This is the mechanism the whole change hinges on.
  - **Legal artifacts** (repository files): `MARIANNE-LICENCE.md` carrying the Etalab Open
    Licence 2.0 terms and the Marianne restriction **verbatim** with the source URL; and
    `AGREEMENT-MARIANNE.md`, the operator user agreement affirming that enabling Marianne is
    permitted only for a French State agency and that Conduction bundles it solely for that
    lawful use.
  - **Unmissable restriction notice** at the point of selection — in the admin settings UI
    beside the Marianne acknowledgement control, and in `README.md` / `docs/`. Translatable:
    English source key, with Dutch and French translations present in `l10n/`.
  - **SPDX / attribution**: the `woff2` files travel with their `etalab-2.0` licence notice;
    `.license-overrides.json` maps them to `Etalab-2.0`, and a `LICENSES/Etalab-2.0.txt` (or
    `MARIANNE-LICENCE.md`) carries the text, mirroring how `OFL.txt` travels with the Inter
    files under `css/systems/lasuite/fonts/`.

- **MODIFIED `lasuite-stack`** (BREAKING relative to the current spec text):
  - `### Requirement: La Suite Fonts Layer With Open Fallback` — no longer forbids bundling
    Marianne; instead requires the gated, inert-by-default self-hosted Marianne described here,
    with Inter as the shipped fallback that renders whenever the gate is off or a glyph is
    missing.
  - `### Requirement: La Suite Asset License Compliance` — the blanket "MUST NOT bundle
    Marianne" and the `/marianne/i` "no such path" assertion are replaced by: Marianne MAY be
    bundled **iff** it ships with its `etalab-2.0` licence, the restriction notice, the user
    agreement, and the default-off acknowledgement gate.

- **MODIFIED `claim-accuracy`** (ADDS one requirement): the app's documentation and the
  compliance material MUST state the Marianne licensing situation honestly — bundled under
  Etalab-2.0, self-hosted, restricted to French State agencies, off by default until an admin
  acknowledges eligibility — and MUST NOT claim Marianne is unconditionally free/open, nor
  (as before this change) that "no Marianne file exists anywhere in the app."

## Impact

- **New spec:** `openspec/specs/marianne-font/spec.md` (created on archive).
- **Modified specs:** `openspec/specs/lasuite-stack/spec.md`,
  `openspec/specs/claim-accuracy/spec.md`.
- **Code / assets (implementation, out of scope for this doc but named for the builder):**
  - `css/systems/lasuite/fonts/marianne/Marianne-*.woff2` (bundled from DSFR)
  - `css/systems/lasuite/marianne.css` (new, gated `@font-face Marianne` layer)
  - `lib/Service/CssInjectionService.php` (emit `systems/lasuite/marianne` behind the flag)
  - `lib/Controller/SettingsController.php` + `lib/Settings/Admin.php` +
    `templates/settings/admin.php` (acknowledgement control + notice)
  - `MARIANNE-LICENCE.md`, `AGREEMENT-MARIANNE.md`, `LICENSES/Etalab-2.0.txt`,
    `.license-overrides.json` (legal / attribution)
  - `l10n/en.json`, `l10n/nl.json`, `l10n/fr.json` (notice strings)
  - `README.md`, `docs/` (restriction documentation)
  - `scripts/build-fonts-marianne.js` (optional devDependency build, if not committed directly)
- **Cross-references:** `lasuite-stack` (the Cunningham/Inter fonts layer this extends);
  `custom-fonts` (the existing self-hosted-webfont delivery pattern); `icon-assets`
  (the DSFR-and-build-script materialisation and REUSE/SPDX attribution pattern).
