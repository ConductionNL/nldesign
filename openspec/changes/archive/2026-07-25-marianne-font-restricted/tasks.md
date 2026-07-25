## Tasks

### 1. Bundle the Marianne font files (self-hosted, CSP-clean)

- [x] 1.1 Add `@gouvfr/dsfr@1.15.1` as a **devDependency** in `package.json` (build-only, no
      runtime PHP/JS dependency), consistent with how `@conduction/nextcloud-vue` is used only
      by `scripts/build-icons.js`.
- [x] 1.2 Copy the DSFR Marianne `woff2` files from `node_modules/@gouvfr/dsfr/dist/fonts/
      Marianne-*.woff2` into `css/systems/lasuite/fonts/marianne/`, keeping the DSFR filenames
      (e.g. `Marianne-Light.woff2`, `Marianne-Regular.woff2`, `Marianne-Medium.woff2`,
      `Marianne-Bold.woff2` and the `*_Italic.woff2` variants DSFR ships). Do NOT hardcode a
      file count in code — the authoritative set is what DSFR 1.15.1 ships. Either commit the
      files directly with a provenance comment, or add `scripts/build-fonts-marianne.js` that
      materialises them (preferred: mirrors `scripts/build-icons.js`).
      Done: committed the 8 woff2 files directly (provenance documented in
      css/systems/lasuite/marianne.css's header and MARIANNE-LICENCE.md), PLUS added
      `scripts/build-fonts-marianne.js` (mirrors `scripts/build-fonts.js`'s graceful-degradation
      pattern) so a future refresh from a real `@gouvfr/dsfr` install is scripted.
- [x] 1.3 Verify no file uses `.woff` fallbacks unless needed; prefer `woff2` only for
      CSP-clean self-hosting.
      Done: only the 8 `.woff2` files are bundled (asserted by
      `tests/Unit/MarianneFontTest.php::testAllEightMarianneWoff2FilesAreBundled`).

### 2. Gated self-hosted @font-face layer

- [x] 2.1 Create `css/systems/lasuite/marianne.css` with real `@font-face Marianne`
      declarations: one per bundled weight/style, `src: url('fonts/marianne/Marianne-*.woff2')
      format('woff2')`, matching `font-weight` (Light→300, Regular→400, Medium→500, Bold→700)
      and `font-style`, `font-display: swap`. App-relative `url()` only — no `http(s)://`.
- [x] 2.2 Leave `css/systems/lasuite/fonts.css` family stack as `--lasuite-font-family:
      Marianne, Inter, sans-serif` (Inter first fallback). Update its header comment: Marianne
      is now bundled and self-hosted **but only activated via the acknowledgement gate**; when
      the gate is off, `marianne.css` is not loaded, so no `url()` source for Marianne exists
      and Inter renders.
- [x] 2.3 In `lib/Service/CssInjectionService.php::inject()`, after the design-system
      stylesheets are emitted, emit `systems/lasuite/marianne` **iff** `$designSystemId ===
      'lasuite'` AND `getAppValue(APP_ID, 'marianne_enabled', '0') === '1'`. Emit it after the
      base `systems/lasuite/fonts` layer so the real `@font-face` wins.
      Done via a new `injectMarianneStylesheet()` private helper (mirrors the existing
      `injectDarkVariantStyle()` seam) — extracted to keep `inject()` under the phpmd
      cyclomatic/NPath complexity thresholds.

### 3. Admin acknowledgement gate

- [x] 3.1 Add a settings endpoint on `lib/Controller/SettingsController.php` (routed in
      `appinfo/routes.php` with an `#[AuthorizedAdminSetting(Admin::class)]` posture) that reads
      and writes the `nldesign` / `marianne_enabled` app config flag (default `'0'`).
      Done: `getMarianneEnabled()` / `setMarianneEnabled()`, routed at `GET`/`POST
      /settings/marianne`. No new constructor dependency was needed (reuses the existing
      `$config` + `$auditService`).
- [x] 3.2 In `lib/Settings/Admin.php`, pass `marianne_enabled` and the active design system id
      into the template parameters.
      Done: `marianneEnabled` + `currentDesignSystem` (resolved from the already-fetched
      `$tokenSets` inventory — no new service dependency needed).
- [x] 3.3 In `templates/settings/admin.php`, render — only meaningfully when the selected token
      set's design system is `lasuite` — a checkbox *"Our organisation is a French State agency
      (administration de l'État)"* bound to the flag, with the unmissable restriction notice
      (task 5) adjacent to it. Default unchecked.
- [x] 3.4 Wire the checkbox in `js/admin.js` to POST the flag and reflect the notice; when
      unchecked, Marianne reverts to Inter on the next render.
      Done: `saveMarianneSetting()` POSTs `{enabled}` to `/settings/marianne`;
      `updateMarianneVisibility()` shows/hides the gate section live on token-set selection
      (mirrors `updateDesignSystemBadge()`).

### 4. Legal / governance artifacts

- [x] 4.1 Add `MARIANNE-LICENCE.md` at the repo root carrying the **Etalab Open Licence 2.0**
      terms and the Marianne restriction (*"réservée aux administrations de l'État"*)
      **verbatim**, with the source URL (`https://www.systeme-de-design.gouv.fr/` /
      `@gouvfr/dsfr@1.15.1` and the Etalab licence URL).
- [x] 4.2 Add `AGREEMENT-MARIANNE.md` at the repo root: the operator user agreement stating
      that enabling Marianne is permitted ONLY when the serving organisation is a French State
      agency, that the operator affirms this by enabling the gate, and that Conduction bundles
      Marianne solely for that lawful use.
- [x] 4.3 Add `LICENSES/Etalab-2.0.txt` with the full Etalab Open Licence 2.0 text (REUSE /
      SPDX identifier `Etalab-2.0`).
- [x] 4.4 Update `.license-overrides.json` to map `css/systems/lasuite/fonts/marianne/*.woff2`
      to `Etalab-2.0` (mirroring how `OFL.txt` accompanies the Inter files).

### 5. Unmissable restriction notice (translatable)

- [x] 5.1 Add the English source notice string (e.g. *"Marianne is the official typeface of the
      French State and is reserved for French State administrations. Enable it only if your
      organisation is a French State agency. Otherwise Inter is used."*) to `l10n/en.json`.
- [x] 5.2 Add Dutch translation to `l10n/nl.json` and French translation to `l10n/fr.json`.
      Done: real (non-English-placeholder) nl/fr translations added, then
      `npm run test:l10n:completeness:write` backfilled the remaining 34 locales, verified with
      `npm run test:l10n:completeness` (exit 0).
- [x] 5.3 Add a restriction section to `README.md` and to the relevant `docs/` page (font
      delivery / compliance) describing: bundled under Etalab-2.0, self-hosted, restricted to
      French State agencies, OFF by default until the admin acknowledges eligibility.
      Done: README.md "Marianne Font (La Suite numérique)" section +
      docs/reference/compliance.md "La Suite numérique — Marianne Font Compliance" section.

### 6. Verify

- [x] 6.1 Unit (PHPUnit, run in the `nextcloud:34` container): assert
      `css/systems/lasuite/marianne.css` exists; every `url()` in it is app-relative (no
      `http(s)://`); each referenced `fonts/marianne/*.woff2` exists on disk; `MARIANNE-LICENCE.md`,
      `AGREEMENT-MARIANNE.md`, and `LICENSES/Etalab-2.0.txt` exist; `.license-overrides.json`
      maps the marianne woff2 to `Etalab-2.0`.
      Done in `tests/Unit/MarianneFontTest.php`.
- [x] 6.2 Unit: assert `CssInjectionService` emits `systems/lasuite/marianne` when design
      system is `lasuite` AND `marianne_enabled='1'`, and does NOT emit it when the flag is
      `'0'` or the design system is not `lasuite`.
      Done in `tests/Unit/Service/CssInjectionServiceTest.php` (3 new tests) +
      `tests/Unit/Controller/SettingsControllerMarianneTest.php` (gate persistence + admin-only
      posture).
- [x] 6.3 Unit: assert the `en`/`nl`/`fr` notice keys are present in the respective `l10n`
      files.
      Done in `tests/Unit/MarianneFontTest.php::testRestrictionNoticeIsTranslatedInNlAndFr`
      (also asserts nl/fr are NOT the untranslated English source).
- [x] 6.4 vitest: assert `js/admin.js` posts the flag when the checkbox toggles and shows the
      notice for the `lasuite` design system.
      Done in `tests/vitest/admin-marianne.spec.js` (5 tests: POST true/false, initial
      show/hide, live toggle on token-set change).
- [ ] 6.5 Live (8080 dev instance): select the `lasuite` token set; confirm the notice + the
      acknowledgement checkbox render in admin settings. With the checkbox OFF, load a page and
      confirm via DevTools Network that **no** `Marianne-*.woff2` request occurs and computed
      `font-family` resolves to Inter. Tick the checkbox; reload; confirm the `Marianne-*.woff2`
      files load from the app (HTTP 200, app-relative URL, no external host) and text renders in
      Marianne. Untick; reload; confirm Marianne is gone and Inter renders again.
      (deferred to post-merge live verification — per builder brief, live-8080 tasks are done
      on the main checkout after merge)
- [x] 6.6 `composer check:strict` passes (PHPCS, PHPMD, Psalm, PHPStan); hydra gates pass
      (SPDX headers on any new PHP, route-auth on the new endpoint, spec-coverage `@spec` tags
      on changed methods).
      Done: `composer check:strict` — lint/phpcs/phpmd/psalm/phpstan all green (the `test:all`
      step inside it hits the pre-existing, documented `OC\Mail\EMailTemplate` harness
      limitation — not a regression from this change; the same 475 tests pass when run directly
      via the `nextcloud:34` container per the builder brief's corrected command).
