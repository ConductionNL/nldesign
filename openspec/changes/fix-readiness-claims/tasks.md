# Tasks: fix-readiness-claims

## 1. Licence declaration
- [ ] 1.1 `appinfo/info.xml`: change `<licence>agpl</licence>` to `<licence>eupl</licence>`
- [ ] 1.2 `appinfo/info.xml`: add "Free and open source under the EUPL-1.2 license." (+ Dutch line) to `<description>`, matching the fleet convention (see `decidesk`, `openbuild`)
- [ ] 1.3 `appinfo/info.xml`: bump `<version>` so the immutable CSS/asset cache busts on upgrade
- [ ] 1.4 `docs/GOVERNMENT-FEATURES.md`: "Licentie: AGPL" → "EUPL-1.2"; row T-02 "AGPL, GitHub" → "EUPL-1.2, Codeberg"; repoint remaining GitHub links to `codeberg.org/Conduction/nldesign`

## 2. Font-delivery documentation
- [ ] 2.1 `README.md`: remove the "Fonts loaded via CDN" / "jsdelivr" statements and the `@font-face` CDN `src` example; describe the shipped self-hosted delivery (`css/fonts/*.woff2` loaded via app-relative `url()` in `css/fonts.css`)
- [ ] 2.2 `README.md`: add a one-line rationale — self-hosting keeps fonts inside Nextcloud's CSP and works on air-gapped government instances
- [ ] 2.3 `docs/reference/compliance.md`: correct the Typography rows — bundled Fira Sans IS loaded; only the proprietary RijksoverheidSansWebText remains intentionally absent

## 3. Token-set count reconciliation
- [ ] 3.1 Reconcile every stated count to the `token-sets.json` inventory (currently 41): `info.xml` `<description>` (enumerate accurately or state the true total), `README.md` "5 token sets", `project.md` "39 token sets"
- [ ] 3.2 `openspec/config.yaml` context line "39 token sets" updated (or reworded to "derived from token-sets.json")

## 4. Token-audit scope honesty
- [ ] 4.1 `docs/reference/token-audit.md`: rewrite the Executive Summary to name the 5 manually-reviewed sets (Rijkshuisstijl, Utrecht, Amsterdam, Den Haag, Rotterdam) and explicitly mark the remaining community-derived sets as **not individually audited**
- [ ] 4.2 Remove the blanket "Final Score: 100/100" and "APPROVED FOR PRODUCTION" verdict as covering all sets; scope it to the audited five
- [ ] 4.3 Add a forward pointer to the generated contrast report produced by `shipped-token-set-contrast-audit`

## 5. Guard test (ADR-009)
- [ ] 5.1 `tests/Unit/ClaimAccuracyTest.php` (mirrors `tests/Unit/IconAssetsTest.php`): assert `info.xml` `<licence>` == `eupl` AND `LICENSE` first line contains "EUROPEAN UNION PUBLIC LICENCE"
- [ ] 5.2 Assert every `lib/**/*.php` SPDX-License-Identifier is `EUPL-1.2` (agrees with the manifest)
- [ ] 5.3 Assert `css/fonts.css` contains no `http(s)://` `url()` and that each referenced `css/fonts/*.woff2` exists on disk
- [ ] 5.4 Assert the token-set count stated in `README.md` equals `count(token-sets.json)`
- [ ] 5.5 Assert `docs/reference/token-audit.md` contains no "APPROVED FOR PRODUCTION" line asserting more sets than the named audited subset

## 6. Documentation and i18n (ADR-005, ADR-010)
- [ ] 6.1 Any new user-visible string via `$l->t()` with an English source key; Dutch in `l10n/nl.json` (+ de/fr/es/it parity) — none expected (metadata/docs only)
- [ ] 6.2 CHANGELOG entry recording the licence correction (adopters may key compliance on the declared licence)
