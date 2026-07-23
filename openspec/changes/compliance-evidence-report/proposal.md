---
kind: code
---

## Why

Dutch government organizations are legally required (Wet digitale overheid / Besluit digitale
toegankelijkheid, implementing EN 301 549 → WCAG 2.2 AA) to publish a toegankelijkheidsverklaring
per site/app — and the obligation explicitly covers **intranets and extranets**, which puts a
themed Nextcloud workplace squarely in scope. The state of compliance is dire: the national
Monitor 2025 shows only **~4% of government declarations at status A**, with 32% stale.
Procurement and audit flows demand *evidence*: a WCAG-EM evaluation by an expert, fed by concrete
measurable inputs. Automated tooling cannot replace the expert report, but it can (and in
practice does — auditors like Siteimprove sell exactly this) supply the measurable color-contrast
substrate.

nldesign already owns the two building blocks:

- `ContrastService` (`lib/Service/ContrastService.php`) — WCAG relative-luminance math, literal
  color parsing, the `unevaluated`-never-passes discipline.
- `ShippedTokenSetAuditService` (`lib/Service/ShippedTokenSetAuditService.php`) — layered
  resolution (`css/systems/nldesign/defaults.css` under `css/tokens/{id}.css`), per-set verdicts,
  a deterministic Markdown report (`docs/reference/contrast-report.md`).

But both audit **shipped sets in isolation** over just **two fixed pairs**. Neither can answer
the question an auditor actually asks: *"what does THIS instance's ACTIVE configuration look
like?"* — the active token set **plus the admin's custom overrides** (`css/custom-overrides.css`,
which loads last and wins the cascade per `css-architecture` REQ-CSS-009), evaluated over a real
pair matrix (status colors, borders, text-on-background), with metadata that makes the evidence
attributable and reproducible (which instance, which app version, which set, which overrides,
when).

This change closes that gap with an exportable compliance evidence report over the **effective**
runtime token values. Claim-accuracy discipline (see `openspec/specs/claim-accuracy/spec.md`,
born from the fix-readiness-claims change) applies with full force here: the report is
color-contrast evidence for theme tokens **only** — it is NOT a WCAG-EM audit and NOT a full
WCAG evaluation, and the report must say so in its own output. Overstating it would turn a
compliance aid into a compliance liability.

## What Changes

- New `ComplianceReportService` (`lib/Service/ComplianceReportService.php`) that:
  - resolves the **effective** token values exactly as the runtime cascade layers them
    (design-system defaults → active token set CSS → `theming.background_color` fallback →
    custom overrides last), reusing `CssParserService`, `CustomOverridesService::read()` and the
    resolution approach of `ShippedTokenSetAuditService::resolveDeclarations()`;
  - computes WCAG 2.2 contrast ratios via the existing `ContrastService` math for a **defined
    pair matrix** derived from the `TokenRegistry` groups (login/brand pairs, status pairs,
    typography/text pairs, border pairs — enumerated normatively in the new
    `compliance-evidence` spec);
  - classifies each pair pass/fail at WCAG 2.2 AA thresholds — 4.5:1 normal text (SC 1.4.3),
    3:1 large text and non-text UI components (SC 1.4.3 / SC 1.4.11) — with `unevaluated` never
    counting as a pass;
  - renders the result as (a) machine-readable JSON and (b) human-readable Markdown, both
    carrying report metadata: instance id + base URL, nldesign app version, Nextcloud version,
    active token set id/name/version, generation timestamp (ISO 8601 UTC), and a SHA-256 hash of
    the canonicalized custom overrides;
  - embeds a mandatory **honest-scope statement** in both formats.
- New admin-auth export endpoint `GET /settings/compliance-report?format=json|markdown` on
  `SettingsController` (same `@AuthorizedAdminSetting` posture as every other `/settings/*`
  route), served as a download (`Content-Disposition: attachment`).
- New occ command `nldesign:compliance-report` (`lib/Command/ComplianceReport.php`, first
  command in the app — registered via `appinfo/info.xml` `<commands>`) for headless/OTAP and
  audit-pipeline use.
- **New canonical spec** `compliance-evidence` (ADDED requirements).
- **MODIFIED** `token-set-contrast-audit` — the Reproducible Contrast Report requirement now
  states the shared-contract relationship: shipped-set audit and active-config compliance report
  use the same `ContrastService` math and the same `unevaluated`-never-passes rule, and
  cross-references `compliance-evidence` for the active-configuration report.
- No DB tables, no Vue: service + vanilla endpoint + occ command, consistent with the app's
  IConfig/files architecture.

## Impact

- `lib/Service/ComplianceReportService.php` — new.
- `lib/Command/ComplianceReport.php` — new (first `lib/Command/`).
- `lib/Controller/SettingsController.php` — new `complianceReport()` method.
- `appinfo/routes.php` — new `settings#complianceReport` route.
- `appinfo/info.xml` — `<commands>` block registering the occ command.
- `tests/unit/Service/ComplianceReportServiceTest.php`,
  `tests/unit/Command/ComplianceReportTest.php` — new fixtures-based tests.
- `openspec/specs/compliance-evidence/spec.md` — new canonical spec (via archive).
- `openspec/specs/token-set-contrast-audit/spec.md` — modified requirement (via archive).
- Cross-references (no file changes there): `custom-token-sets` (custom-set metadata versions,
  see change `dtcg-ingestion-hardening`), `claim-accuracy` (scope-statement discipline).
