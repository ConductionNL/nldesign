## 1. Derivation service

- [ ] 1.1 Create `lib/Service/DarkPaletteService.php` implementing the algorithm in design.md §3:
      HSL conversion helpers, lightness inversion with background/text anchor compression,
      background-only desaturation, brand-primary exception, `-rgb` companion regeneration.
      Constructor deps: `ContrastService`, `CssParserService`, `TokenRegistry`, `IAppManager`,
      `LoggerInterface`. SPDX `@license`/`@copyright` docblocks, `@spec` tags on every public
      method (hydra gates).
- [ ] 1.2 Implement the WCAG verification loop: `verifyAndRepair(array $darkDeclarations): array`
      using `ContrastService::check()`; binary-search fg lightness (max 8 iterations), snap
      fallback, warning collection. Hand-authored declarations are warn-only, never rewritten.
- [ ] 1.3 Implement hand-authored override extraction: extend `CssParserService` with
      `parseDarkBlock(string $css): array` returning declarations inside a top-level
      `@media (prefers-color-scheme: dark)` block (empty array when absent). Add `@spec` tag.
- [ ] 1.4 Implement `renderDarkCss(string $setId, array $declarations): string` emitting the exact
      dual-scope file shape from design.md §2 (generator-version + source-hash header comment,
      media block with the `:not(...)` explicit-choice exclusion, `body[data-theme-dark],
      body[data-themes*=dark]` block).
- [ ] 1.5 Implement `generateForSet(string $setId): ?string` (null for skipped sets:
      `design_system` of `none` or `high-contrast`), including `logo_dark` emission from the
      set's `theming` metadata.

## 2. Generation command + repair step

- [ ] 2.1 Create `lib/Command/GenerateDarkVariants.php` (`occ nldesign:generate-dark-variants`),
      options `--set=<id>` (single set) and `--force` (ignore hash freshness); writes
      `css/tokens/dark/<set>.css`; prints per-set result + contrast warnings; non-zero exit only
      on write failure, not on skips. Register in `appinfo/info.xml` `<commands>`.
- [ ] 2.2 Create `lib/Migration/GenerateDarkVariantsRepairStep.php` implementing `IRepairStep`:
      regenerate missing/stale files, log-and-skip when `css/tokens/dark/` is not writable.
      Register in `appinfo/info.xml` `<repair-steps><post-migration>`.
- [ ] 2.3 Hook custom-set uploads: after `CustomTokenSetService` persists `custom-<id>.css`,
      generate its dark variant; delete the dark file when the custom set is deleted.
- [ ] 2.4 Run the command for all shipped sets and commit the generated `css/tokens/dark/*.css`
      files. Extend the nightly token-sync GitHub Action so regenerated upstream sets also
      regenerate their dark variants in the same PR.

## 3. Injection + toggle

- [ ] 3.1 In `lib/AppInfo/Application.php` `injectThemeCSS()`: when `designSystemId !== 'none'`,
      `dark_variants` config is `'1'`, and `css/tokens/dark/<set>.css` exists, add
      `\OCP\Util::addStyle(application: self::APP_ID, file: 'tokens/dark/'.$tokenSet)` directly
      after the `tokens/<set>` line and before `custom-overrides`.
- [ ] 3.2 Add `SettingsController::getDarkVariants()` / `setDarkVariants()` (admin-only,
      `@AuthorizedAdminSetting(settings=OCA\NLDesign\Settings\Admin)`), routes
      `GET/POST /settings/dark-variants` in `appinfo/routes.php`. Value stored as
      `IConfig::setAppValue('nldesign', 'dark_variants', '0'|'1')`, default `'1'`.
- [ ] 3.3 Admin panel: checkbox "Dark mode variants" in `templates/settings/admin.php` +
      wiring in `js/admin.js` (fetch current value, POST on change, i18n keys in ENGLISH).
- [ ] 3.4 Guarantee: grep-verify the diff introduces no read/write of `enforce_theme` and no call
      into `ThemesService` — user/OS theme choice is never forced.

## 4. logo_dark manifest + theming-sync dialog

- [ ] 4.1 Extend `token-sets.json` schema handling: `TokenSetService` passes through an optional
      `theming.logo_dark` key (no code change needed for passthrough — verify and add a test).
      Add `logo_dark` entries for sets that ship a dark logo asset (start with `rijkshuisstijl`
      if a white-on-transparent variant exists in `img/logos/`; otherwise ship the mechanism with
      zero entries and file a content follow-up issue).
- [ ] 4.2 `lib/Service/ThemingService.php`: include `logo_dark` in `validateImagePaths()` with
      identical rules to `logo` (traversal, `img/logos/` prefix, existence).
- [ ] 4.3 `js/admin.js` theming-sync dialog: when the selected set's `theming` has `logo_dark`,
      render a dark-logo swatch row (dark checkerboard background) with an explanatory note that
      the dark logo is applied by nldesign's dark stylesheet (NC core has no dark logo slot —
      server#47357). No new POST field to NC core theming.
- [ ] 4.4 Update `version` in `appinfo/info.xml` (cache-bust — NC `?v=` hash gotcha).

## 5. Unit tests

- [ ] 5.1 `tests/unit/Service/DarkPaletteServiceTest.php` with known color fixtures:
      - `#154273` (Rijkshuisstijl blue, H≈212°) derives to a same-hue color; assert hue delta < 2°.
      - Light background `#F5F6F7` (L≈0.96) derives to L in [0.08, 0.16]; text `#333333`
        derives to L in [0.62, 0.92].
      - Every `ContrastService` pair in the derived output of the `rijkshuisstijl` and
        `amsterdam` fixtures passes ≥ 4.5:1 (the verification-loop contract).
      - A pathological fixture (primary `#FFFF00`, primary-text `#FFFFFF`) is repaired by the
        loop to a passing pair.
      - Hand-authored dark block declaration (`--nldesign-color-primary: #4844AD` inside a
        `@media (prefers-color-scheme: dark)` block) survives derivation unchanged even when it
        fails a pair, and produces a warning.
      - `-rgb` companions match their base token.
- [ ] 5.2 `parseDarkBlock()` tests: present, absent, nested-braces, malformed CSS → graceful empty.
- [ ] 5.3 `renderDarkCss()` snapshot test: exact selector shape from design.md §2, including the
      four `:not()` exclusions and the `body[data-theme-dark], body[data-themes*=dark]` scope.
- [ ] 5.4 Command test: `--set=rijkshuisstijl` writes the file; skips `nextcloud` (design_system
      none) and the high-contrast set; `--force` rewrites despite fresh hash.
- [ ] 5.5 Settings toggle tests: default `'1'`, POST persists, non-admin rejected. Application
      injection test: dark stylesheet added only when file exists AND toggle on.
- [ ] 5.6 vitest: theming-sync dialog renders the dark-logo row iff `logo_dark` present.

## 6. Verify

- [ ] 6.1 `composer check:strict` and full PHPUnit in the `nextcloud:34` container
      (`docker run --rm -v $PWD:/app -w /app <nc-image> php vendor/bin/phpunit`); `npm test`
      (vitest) green.
- [ ] 6.2 Live on the 8080 dev instance (admin:admin): run
      `occ nldesign:generate-dark-variants`, activate the `amsterdam` token set, then verify the
      three-state matrix with a Playwright browser session:
      (a) user theme "System default" + emulated `prefers-color-scheme: dark` → screenshot shows
      dark nldesign surfaces (dark header/nav, AA text) — no half-dark hybrid;
      (b) explicit "Dark theme" selected (body carries `data-theme-dark`) with a LIGHT OS
      emulation → still fully dark;
      (c) explicit "Light theme" with a DARK OS emulation → fully light (media block correctly
      excluded). Screenshot each state.
- [ ] 6.3 Live curl: `curl -s http://localhost:8080/index.php/apps/nldesign/css/tokens/dark/amsterdam.css`
      returns the generated file; unauthenticated POST to `/settings/dark-variants` is rejected;
      admin POST `dark_variants=0` then reload → dark stylesheet no longer in page head.
- [ ] 6.4 With a set carrying `logo_dark`: open the theming-sync dialog, confirm the dark-logo
      swatch renders; switch NC to dark and confirm the header logo swaps via
      `--nldesign-logo-url`. Screenshot.
- [ ] 6.5 Confirm `git grep enforce_theme` over the app returns nothing.
