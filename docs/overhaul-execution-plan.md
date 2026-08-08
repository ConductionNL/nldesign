# NL Design Nextcloud — Overhaul Execution Plan

**Objective:** convert the architecture/rules docs into a releaseable implementation sequence with explicit dependencies, gates, and handoff criteria.

**Current assumption:** this runs on the architecture-v1 baseline described in `architecture.md` and `roadmap.md`.

## 0) Start state lock

1. Freeze current scope:
   - No implementation changes to `development` branch code paths.
   - Keep existing SENERAWA/profile-discovery work available but fenced.
2. Confirm baseline:
   - Branch/commit to ship from.
   - Supported Nextcloud major matrix (at least 32–34 unless re-baselined).
   - Reproducible package build command.
3. Create a feature ledger artifact with columns:
   - Feature name
   - Present in code
   - Reachable at runtime
   - Evidence-tested
   - Documentation status
   - Released
4. Add architecture boundary checks:
   - Run `./scripts/check-architecture-boundary.sh` before compatibility slices.
   - Keep any violations unresolved before moving to Slice 1.

**Exit test:** one named baseline commit + clean package + one failing command removed before adding new features.

## 1) Make-phase 1: safe load-bearing core (Gate 0 + Slice 1)

Owner: core team

### Work

- Replace code-derived catalogue with manifest-verified catalogue.
- Add typed active profile state and revision persistence (app config).
- Move CSS injection to template-render events and fail-open on profile load errors.
- Add profile snapshot/rollback mechanics and deterministic neutral fallback.
- Prove package-directory immutability for runtime writes.

### Acceptance

- Profile can be previewed/published/rolled back without touching private Theming APIs.
- App still works when Theming is unavailable.
- No writes under installed app path.

## 2) Make-phase 2: contract boundary (Slice 2)

Owner: platform + backend

### Work

- Introduce domain model for managed branding fields.
- Add `leave|set|reset` patch model and immutable plan records.
- Add capability broker with `public/private/manual/unavailable` access levels.
- Implement manual driver with per-field `occ theming:config` instructions.
- Add operation persistence in app data with before-snapshot + result states.

### Acceptance

- API surface has only versioned plan/apply/rollback endpoints.
- No generic setting key route exists.
- Capability changes do not alter profile schema or manifest format.

## 3) Make-phase 3: temporary compatibility (Slice 3)

Owner: compatibility team

### Work

- Implement `OcaThemingDriverV32ToV34` behind factory only.
- Add strict probes: runtime major, class/method existence, behavior probe, policy checks.
- Implement scalar and image lifecycles end-to-end with MIME + cache + restore.
- Add kill switch `public-only`.
- Add stale-capability and no-fallthrough tests.

### Acceptance

- Matrix pass per field (set/reset/readback/restore) per supported major.
- Failed private mutation does not auto-fall back to raw writes.
- Manual fallback remains available.

## 4) Make-phase 4: surface and matrix hardening (Milestone 4/5 precondition)

Owner: frontend + QA

### Work

- Define surface support status: Supported / Compatible / Best effort / Unsupported.
- Add selector budgets + variable-only contract exceptions list.
- Add matrix tests for mode/accessibility combinations in supported majors.
- Fix neutral fallback policy to prevent cross-brand leakage.

### Acceptance

- Accessibility mode precedence validated.
- At least one major with full profile and CLI rollback path proven.
- No unsupported surface claimed as supported.

## 5) Make-phase 5: release hardening

Owner: maintainer

### Work

- Publish support matrix and limitations generated from evidence.
- Add install/upgrade/downgrade/uninstall recovery path.
- Add offline/runtime no-network checks.
- Add security/accessibility smoke checks and CSP/CSRF/authorization checks.

### Acceptance

- One release candidate with reproducible package, package tests, and rollback path.
- Recovery path documented and tested without admin UI.
- No stale or contradictory claims in docs.

## 6) Upstream-safe migration lane (parallel)

Owner: lead

### Work

- Track `OCP\Theming\IDefaults` direction and open `NCU/OCP` mutation-manager proposal.
- Never change profile API for private/public switch.
- Remove private compatibility driver field by field once public API supports equivalent lifecycle.

### Acceptance

- Removal contract test proves compatibility module deletion does not break profile workflow.
- Public migration only where behavior parity is proven.

## Work sequencing and dependencies

1. Gate 0 and Slice 1 can run in parallel with docs evidence cleanup.
2. Slice 2 depends on Slice 1 contracts being stable.
3. Slice 3 depends on Slice 2 and is blocked by surface matrix decisions.
4. Release hardening depends on at least one complete slice 3 profile path + one full rollback proof.

## Immediate execution board (first 10 commits)

1. Add feature ledger + baseline decision doc.
2. Add compatibility/ import ban checks and private-import static rule.
3. Add profile manifest validator fixture and hash check.
4. Wire template listener only path for profile CSS.
5. Add revisioned active-profile persistence.
6. Add CLI status/recovery command stubs.
7. Add plan/operation persistence and stale-plan detection.
8. Add manual driver + generated instructions output.
9. Add compatibility driver interface and factory probe.
10. Add removal-contract CI test.

## Stop criteria before Milestone 5

- Any new private surface area requires evidence in docs and tests.
- Any failing slice blocks adding pilot profiles.
- No feature enters release scope without required test evidence.
- No changes to UI copy that claim ownership not in profile contract.
