## 1. Derivation service

- [x] 1.1 Create `lib/Service/DarkPaletteService.php` implementing the algorithm in design.md §3:
      HSL conversion helpers, lightness inversion with background/text anchor compression,
      background-only desaturation, brand-primary exception, `-rgb` companion regeneration.
      Constructor deps: `ContrastService`, `CssParserService`, `TokenRegistry`, `IAppManager`,
      `LoggerInterface`. SPDX `@license`/`@copyright` docblocks, `@spec` tags on every public
      method (hydra gates).
      **Deviation**: `TokenRegistry` is NOT injected/used — it covers the `--color-*`
      custom-overrides editor (a distinct concern mapped onto core NC variable names), not the
      `--nldesign-*` token-set primitives this service derives (verified: TokenRegistry's tokens
      are all `--color-*`, never `--nldesign-*`). Classification uses a dedicated local
      background/text split on the `--nldesign-*` naming convention instead (see the class
      docblock). Constructor is `(ContrastService, CssParserService, IAppManager,
      LoggerInterface)`.
- [x] 1.2 Implement the WCAG verification loop: `verifyAndRepair(array $darkDeclarations, array
      $protectedTokens = []): array` using `ContrastService::check()`; binary-search fg lightness
      (max 8 iterations), snap fallback, warning collection. Hand-authored declarations are
      warn-only, never rewritten (via the `$protectedTokens` param).
- [x] 1.3 Implement hand-authored override extraction: extend `CssParserService` with
      `parseDarkBlock(string $css): array` returning declarations inside a top-level
      `@media (prefers-color-scheme: dark)` block (empty array when absent). Added `@spec` tag.
- [x] 1.4 Implement `renderDarkCss(string $setId, array $declarations, string $sourceHash): string`
      emitting the exact dual-scope file shape from design.md §2 (generator-version + source-hash
      header comment, media block with the `:not(...)` explicit-choice exclusion,
      `body[data-theme-dark], body[data-themes*=dark]` block).
- [x] 1.5 Implement `generateForSet(string $setId): ?array{css, warnings}` (null for skipped sets:
      `design_system` of `none` or `high-contrast`), including `logo_dark` emission from the
      set's `theming` metadata.

## 2. Generation command + repair step

- [x] 2.1 Create `lib/Command/GenerateDarkVariants.php` (`occ nldesign:generate-dark-variants`),
      options `--set=<id>` (single set) and `--force` (ignore hash freshness); writes
      `css/tokens/dark/<set>.css`; prints per-set result + contrast warnings; non-zero exit only
      on write failure, not on skips. Registered in `appinfo/info.xml` `<commands>`.
- [x] 2.2 Create `lib/Migration/GenerateDarkVariantsRepairStep.php` implementing `IRepairStep`:
      regenerate missing/stale files, log-and-skip when `css/tokens/dark/` is not writable.
      Registered in `appinfo/info.xml` `<repair-steps><post-migration>`.
- [x] 2.3 Hook custom-set uploads: `CustomTokenSetService::store()` best-effort generates the
      dark variant right after persisting `custom-<id>.css` (never fails the upload itself);
      `delete()` removes the dark file via `DarkPaletteService::deleteDarkVariant()`.
- [x] 2.4 Ran the command for all 41 eligible shipped sets (of 43 — `nextcloud`=none,
      `hoog-contrast`=high-contrast skip as designed) and committed the generated
      `css/tokens/dark/*.css` files. Verified all 41 generated files are AA-clean by construction
      (zero non-`unevaluated` `ContrastService::check()` warnings). Extended
      `.github/workflows/sync-tokens.yml` with a PHP setup + `scripts/generate-dark-variants.php
      --force` step (a standalone script — no NC bootstrap needed, mirrors
      `tests/bootstrap.php`'s OCP autoload registration) so regenerated upstream sets also
      regenerate their dark variants in the same PR. **Deferred**: the workflow step itself
      cannot be exercised from this worktree (GitHub Actions runner) — verified the underlying
      script directly instead (ran it standalone against the real app tree, confirmed idempotent
      skip-when-fresh and `--force` rewrite).

## 3. Injection + toggle

- [x] 3.1 In `lib/AppInfo/Application.php` `injectThemeCSS()`: when `designSystemId !== 'none'`,
      `dark_variants` config is `'1'`, and `css/tokens/dark/<set>.css` exists, add
      `\OCP\Util::addStyle(application: self::APP_ID, file: 'tokens/dark/'.$tokenSet)` directly
      after the `tokens/<set>` line and before `custom-overrides`. Extracted into
      `injectDarkVariantStyle()` (own private method) to keep `injectThemeCSS()`'s cyclomatic
      complexity under the phpmd threshold; the file-existence check reuses
      `DesignSystemService` (already a dependency of this class) via a new
      `hasGeneratedDarkVariant()` method, rather than adding a fresh `IAppManager` dependency
      that would have tripped the `CouplingBetweenObjects` gate.
- [x] 3.2 Added `SettingsController::getDarkVariants()` / `setDarkVariants()` (admin-only,
      `#[AuthorizedAdminSetting(Admin::class)]`), routes `GET/POST /settings/dark-variants` in
      `appinfo/routes.php`. Value stored as `IConfig::setAppValue('nldesign', 'dark_variants',
      '0'|'1')`, default `'1'`. `dark_variants` added to `Settings\Admin::getAuthorizedAppConfig()`.
- [x] 3.3 Admin panel: checkbox "Enable dark mode variants for the active token set" in
      `templates/settings/admin.php` + wiring in `js/admin.js` (fetch current value via
      `darkVariantsEnabled` template var, POST on change, i18n keys in ENGLISH, Dutch translated).
- [x] 3.4 Guarantee verified: `git grep -n enforce_theme` and `git grep -n setEnabledThemes` over
      the app (excluding this tasks.md/design.md/proposal.md documentation) return zero hits.

## 4. logo_dark manifest + theming-sync dialog

- [x] 4.1 Verified `TokenSetService` passes through the full `theming` object generically
      (`$tokenSet['theming'] = $meta['theming']`) — no code change needed for `logo_dark`
      passthrough; covered by `DarkPaletteServiceTest::testLogoDarkEmitsRelativeLogoUrlOverride()`
      and `tests/vitest/admin-dark-mode.spec.js`. **No `logo_dark` entries added to
      `token-sets.json`**: `img/logos/` contains no dark-surface/white-on-transparent logo
      variant for any shipped set today (verified — no `*-dark.svg` files exist). Shipped the
      mechanism with zero entries per the task's own fallback instruction; filed the content
      follow-up issue (see PR body).
- [x] 4.2 `lib/Service/ThemingService.php`: `logo_dark` added to `validateImagePaths()` with
      identical rules to `logo` (traversal, `img/logos/` prefix, existence) — still excluded from
      `applyImages()` so it is never synced to NC core theming.
- [x] 4.3 `js/admin.js` theming-sync dialog: when the selected set's `theming` has `logo_dark`,
      renders a `.nldesign-dialog-dark-logo-row` dark-checkerboard swatch (new CSS in
      `css/admin.css`) with an explanatory ENGLISH i18n note (Dutch translated) that the dark
      logo is applied by nldesign's dark stylesheet (server#47357). Confirmed the dialog never
      adds `logo_dark` to the `POST /settings/theming` payload.
- [x] 4.4 `appinfo/info.xml` `<version>` bumped `0.1.3-unstable.14` -> `0.1.3-unstable.15`.

## 5. Unit tests

- [x] 5.1 `tests/Unit/Service/DarkPaletteServiceTest.php` (22 tests) with known color fixtures:
      - Hue preservation for a background-class token (delta < 2°) and the brand-primary
        exception keeping the rijkshuisstijl blue byte-identical when its light pair already passes.
      - Light background (L≈0.96) derives to L in [0.08, 0.16]; text (L≈0.2) derives to L in
        [0.62, 0.92].
      - **Real shipped-set integration** (not just synthetic fixtures): `rijkshuisstijl` and
        `amsterdam`'s generated dark declarations, run against the actual worktree files, have
        zero non-`unevaluated` `ContrastService::check()` warnings (data-provider test).
      - Pathological fixture (primary `#FFFF00`, primary-text `#FFFFFF`) repaired by the loop to
        a passing pair (verified via `ContrastService::check()` on the result).
      - Hand-authored declaration survives `verifyAndRepair()` unchanged even when it fails a
        pair (identical fg/bg, ratio exactly 1:1), and produces a warning.
      - `-rgb` companions match their derived base token; non-color tokens and unparseable
        values (gradient/`var()`) are skipped with no exception.
      - `generateForSet()`/`generateAndWrite()`: hand-authored override wins, ineligible design
        systems return null/skip, idempotence without `--force`, `--force` rewrites,
        `logo_dark` emits the correct `../../../../img/logos/...` relative path,
        `discoverAllSetIds()`, `deleteDarkVariant()`.
      - `renderDarkCss()` selector-shape assertions (folded into this file rather than a
        separate 5.3 file — see below).
- [x] 5.2 `tests/Unit/Service/CssParserServiceTest.php` (5 tests): `parseDarkBlock()` present,
      absent, unrelated braces elsewhere in the file, malformed/unclosed block, empty input — all
      degrade to `[]`, never an exception.
- [x] 5.3 `renderDarkCss()` shape assertions folded into
      `DarkPaletteServiceTest::testRenderDarkCssSelectorShape()` /
      `testGeneratedCssNeverUsesImportant()`: exact selector shape from design.md §2, the four
      `:not()` exclusions, the `body[data-theme-dark], body[data-themes*=dark]` scope, and no
      `!important` anywhere in generated output.
- [x] 5.4 `tests/Unit/Command/GenerateDarkVariantsTest.php` (4 tests): `--set=amsterdam` writes
      only that file; a full run skips `nextcloud` (none) and `hoog-contrast` (high-contrast)
      with exit code 0; a second run without `--force` skips the fresh file; `--force` rewrites.
- [x] 5.5 `tests/Unit/Controller/SettingsControllerDarkVariantsTest.php` (4 tests): default `'1'`,
      POST persists (both directions), both routes carry `#[AuthorizedAdminSetting]` (the
      established reflection-based idiom this codebase already uses for "non-admin rejected" —
      see `SettingsControllerUpstreamFreshnessTest`). **Deferred**: a PHPUnit-level
      `Application::injectThemeCSS()` test — the method is private, boot-context-coupled, and
      calls the real `\OCP\Util::addStyle()` static (which needs a live NC container to resolve,
      the same class of harness limitation as the documented `OC\Mail\EMailTemplate` one); the
      injection logic itself is unit-tested indirectly via `DesignSystemService::
      hasGeneratedDarkVariant()` plus the toggle tests above, and the full path is covered by the
      live-verification matrix in §6.2 (deferred to post-merge, per the builder brief).
- [x] 5.6 `tests/vitest/admin-dark-mode.spec.js` (4 tests): theming-sync dialog renders the
      dark-logo row iff `logo_dark` present, and confirming never adds `logo_dark` to the
      `POST /settings/theming` payload; dark-variants checkbox POSTs `{enabled}` to
      `/settings/dark-variants`.

## 6. Verify

- [x] 6.1 `composer check:strict` and full PHPUnit in the `nextcloud:34` container
      (`docker run --rm -v $PWD:/app -w /app <nc-image> php vendor/bin/phpunit`); `npm test`
      (vitest) green.
- [ ] 6.2 (deferred to post-merge live verification — requires the shared 8080 dev instance) Live
      on the 8080 dev instance (admin:admin): run `occ nldesign:generate-dark-variants` (files are
      already committed, so this only exercises freshness-skip live), activate the `amsterdam`
      token set, then verify the three-state matrix with a Playwright browser session:
      (a) user theme "System default" + emulated `prefers-color-scheme: dark` → screenshot shows
      dark nldesign surfaces (dark header/nav, AA text) — no half-dark hybrid;
      (b) explicit "Dark theme" selected (body carries `data-theme-dark`) with a LIGHT OS
      emulation → still fully dark;
      (c) explicit "Light theme" with a DARK OS emulation → fully light (media block correctly
      excluded). Screenshot each state.
- [ ] 6.3 (deferred to post-merge live verification) Live curl:
      `curl -s http://localhost:8080/index.php/apps/nldesign/css/tokens/dark/amsterdam.css`
      returns the generated file; unauthenticated POST to `/settings/dark-variants` is rejected;
      admin POST `dark_variants=0` then reload → dark stylesheet no longer in page head.
- [ ] 6.4 (deferred to post-merge live verification) With a set carrying `logo_dark`: open the
      theming-sync dialog, confirm the dark-logo swatch renders; switch NC to dark and confirm
      the header logo swaps via `--nldesign-logo-url`. Screenshot. **Note**: no shipped set
      currently carries `logo_dark` (see 4.1) — this step needs a temporary test fixture entry or
      a real dark-logo asset landing first.
- [x] 6.5 Confirmed `git grep -n enforce_theme` and `git grep -n setEnabledThemes` over the app
      (excluding this change's own openspec docs) return zero hits.
