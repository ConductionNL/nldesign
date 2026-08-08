# Gate 0: Architecture baseline and exit record

**Date:** 2026-08-08

**Starting branch anchor:** `senerawa-token-set` at `720e7a5`

**Upstream anchors at audit time:** `origin/main` at `66e7f42` and
`origin/development` at `7b0fade`

## Baseline decision

The branch is the v1 productization baseline. The large divergent development
line is a prototype mine, not a merge target. Features enter this line only
when they satisfy the architecture boundary, evidence rules, and current
product scope.

The implementation may advance locally while Gate 0 is open, but nothing is a
release until code, documentation, package contents, and exact-version runtime
evidence agree.

## Locally completed evidence

- The feature ledger distinguishes implemented, reachable, verified, and
  released state.
- The package catalogue distinguishes 8 ready projections from 32 source-only
  inventory records.
- Profile state is revisioned, exclusively locked, rollback-capable, bounded,
  and stored outside the package directory.
- Template-event injection has a three-layer core plan limited to root and
  body theme-state guards.
- Private Theming references are fenced below the compatibility infrastructure
  boundary and are not registered.
- Automatic release, branch-sync, and unpinned token-generation workflows are
  absent from the trusted path.
- Local quality, dependency, manifest, architecture, and package checks are
  defined and blocking.

## Exit evidence still required

1. Build the minimal release candidate reproducibly from a clean checkout.
2. Install that exact artifact read-only on clean instances for every claimed
   Nextcloud major.
3. Exercise admin publish, stale write, concurrent write, rollback, login,
   core surfaces, accessibility preferences, and optional selector toggles in
   supported browsers.
4. Record the supported major/app/browser matrix in versioned evidence.
5. Resolve or explicitly scope each ready profile's provenance, identity
   permission, and measured colour-pair evidence.
6. Keep all bridge capabilities manual until a public or exact-build private
   adapter passes lifecycle and restoration tests.

Run `./scripts/check-architecture-boundary.sh` with every compatibility change.
Gate 0 closes only when the release artifact—not merely this working tree—has
the evidence above.
