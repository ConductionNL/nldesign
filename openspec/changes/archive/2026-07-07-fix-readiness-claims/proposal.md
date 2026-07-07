# Proposal: fix-readiness-claims

## Why

A readiness audit at HEAD (`c124804`) found that several public, machine- and human-readable claim surfaces contradict the shipped code. These are not missing features — the features exist — they are *dishonest metadata* that misleads procuring municipalities reading the app store listing and `docs/GOVERNMENT-FEATURES.md` as a Programma van Eisen checklist. Each item below was verified against the tree.

- **Licence mismatch (HIGH).** `appinfo/info.xml` declares `<licence>agpl</licence>`, but the bundled `LICENSE` file is the *European Union Public Licence v1.2*, every `lib/**/*.php` SPDX header reads `EUPL-1.2`, `README.md` states EUPL-1.2, and the Specter intelligence record for the app (id 8) is `EUPL-1.2`. The Nextcloud App Store publishes `<licence>` verbatim, so the store currently tells adopters the wrong licence. The fleet's correct value is `eupl` (as used by `portaliq/appinfo/info.xml`). `docs/GOVERNMENT-FEATURES.md` compounds this: line "Licentie: AGPL" and row T-02 "Open source | AGPL, GitHub" — both the licence *and* the host are wrong (the canonical repo is `codeberg.org/Conduction/nldesign`, not GitHub). nldesign's `info.xml` `<description>` is also missing the "Free and open source under the EUPL-1.2 license" line the rest of the fleet carries.

- **Font delivery documented as CDN, actually bundled (MED).** `README.md` claims "No Build Required: Fonts loaded via CDN", "Delivery: Loaded via CDN from jsdelivr.net", and shows an `@font-face` example with a `https://cdn.jsdelivr.net/...` `src`. The shipped `css/fonts.css` loads Fira Sans **only** from app-relative paths (`url('fonts/fira-sans-latin-400-normal.woff2')`) and the `.woff2/.woff` files are committed under `css/fonts/`. A CDN font load would be blocked by Nextcloud's default Content-Security-Policy and would fail on the air-gapped instances typical of Dutch government — so the documented behaviour is both false and, if followed, harmful. `docs/reference/compliance.md` has the opposite stale error: it says typography files are "not loaded / fallback to system fonts", which is also false now that Fira Sans is bundled and injected.

- **Token-set count drift (MED).** The app ships **41** token sets (`token-sets.json` has 41 entries; `css/tokens/` has 41 CSS files). But `info.xml`'s `<description>` enumerates only 5, `README.md` says "5 token sets", `project.md` says "39 token sets", `openspec/config.yaml` context says "39", and the `docs-content` spec bakes in "39". No single number is right.

- **Token audit over-claim (MED).** `docs/reference/token-audit.md` (dated 2026-02-03, "Audited by: AI Assistant") asserts "All 5 organization token sets have been thoroughly reviewed", "Final Score: 100/100", and "APPROVED FOR PRODUCTION". Since then 36 additional community-derived sets shipped and were never audited. The document reads as if all shipped sets are production-verified when 36 of 41 have had no contrast or correctness review. Paired with `GOVERNMENT-FEATURES.md` A-01..A-05/F-09 ("WCAG 2.1 AA gegarandeerd / afgedwongen"), this is a WCAG conformity over-statement — a real procurement risk, because 93 Dutch tenders in the intelligence corpus require the solution to "voldoen aan de minimale eisen van WCAG 2.1/2.2 niveau AA (EN 301 549, Digitoegankelijk)".

This change corrects the claims so the manifest and documentation match the code. It authors **honesty invariants** as a new capability so the drift cannot silently return; it does not add or remove any runtime feature. Making the WCAG claim *true by construction* (an automated contrast gate over all sets) is a separate change, `shipped-token-set-contrast-audit`, which supersedes the manual audit doc.

## What Changes

- **NEW** — `claim-accuracy` capability: testable invariants asserting that the licence declaration, font-delivery description, token-set counts, and audit-scope statements agree with the shipped tree.
- **FIX** — `appinfo/info.xml`: `<licence>agpl</licence>` → `<licence>eupl</licence>`; add the "Free and open source under the EUPL-1.2 license" prose to `<description>` (fleet convention).
- **FIX** — `docs/GOVERNMENT-FEATURES.md`: "Licentie: AGPL" → "EUPL-1.2"; T-02 "AGPL, GitHub" → "EUPL-1.2, Codeberg"; other GitHub references pointed at the Codeberg canonical repo.
- **FIX** — `README.md`: replace the CDN / jsdelivr font-delivery statements and the `@font-face` CDN example with the real bundled, self-hosted delivery (and note the CSP / air-gap rationale).
- **FIX** — `docs/reference/compliance.md`: typography rows corrected — bundled Fira Sans is loaded; proprietary RijksoverheidSansWebText remains intentionally absent.
- **FIX** — token-set count wording in `info.xml` `<description>`, `README.md`, and `project.md` reconciled to the `token-sets.json` inventory (currently 41).
- **FIX** — `docs/reference/token-audit.md`: scope corrected to name the 5 manually-audited sets and mark the remaining community-derived sets as unaudited; the "100/100 / APPROVED FOR PRODUCTION" blanket verdict removed for sets that were never reviewed.

## Capabilities

### New Capabilities
- `claim-accuracy` — The app's public claim surfaces (manifest metadata, README, government feature checklist, compliance and audit docs) MUST agree with the shipped code: licence, font delivery, token-set counts, and audit scope are each pinned to a filesystem source of truth.

## Decisions

1. **`<licence>eupl</licence>`, matching `portaliq`.** The canonical licence is EUPL-1.2 (the `LICENSE` file and every SPDX header). `eupl` is the value the Nextcloud store schema accepts that the fleet already uses; `agpl` is simply wrong for this app.
2. **Honesty invariants, not a documentation rewrite.** The capability pins each claim to a filesystem source of truth (LICENSE, `css/fonts.css`, `token-sets.json`) so a future edit that re-introduces "AGPL", "CDN", or a wrong count fails a check, rather than adding prose that drifts again.
3. **Count is derived, not hard-coded.** Requirements assert "matches `token-sets.json` length", not "== 41", so adding a token set does not immediately make the docs a lie.
4. **Audit scope downgraded here; audit made true elsewhere.** This change only stops the over-claim in `token-audit.md`. The companion `shipped-token-set-contrast-audit` change replaces the hand-written doc with a generated, reproducible report — the two are sequenced so the honest-scope wording lands first.
5. **No feature status is upgraded or invented.** Custom-token-set upload, per-app theming, and icon assets were flagged "claimed but nonexistent" in the 2026-06-11 audit; they are now fully implemented and tested at HEAD, so no status change is needed for them.

## Impact

- **nldesign app only** — `appinfo/info.xml`, `README.md`, `project.md`, `docs/GOVERNMENT-FEATURES.md`, `docs/reference/compliance.md`, `docs/reference/token-audit.md`; new `tests/Unit/ClaimAccuracyTest.php` (inventory-style, mirrors `IconAssetsTest`).
- **App Store listing** — adopters see the correct EUPL-1.2 licence after the next release (bump `info.xml` version so the immutable cache busts).
- **No runtime behaviour change** — no CSS, controllers, services, or config touched; purely metadata + docs + a guard test.
- **No database migration.**

## Rollback Strategy

- Revert the metadata/doc edits; the `claim-accuracy` test is additive and can be removed independently.
- No user-facing or theming behaviour changes, so rollback is inert for running instances.
