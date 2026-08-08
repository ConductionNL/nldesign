# Nextcloud Theming Bridge Tasks

## Current manual slice

- [x] Normalize and allowlist profile Theming hints in TokenSetService.
- [x] Build a pure, non-executing manual plan.
- [x] Expose the plan through an authorized read endpoint.
- [x] State in API, UI, and documentation that profile activation performs no
  core-Theming write.
- [x] Remove automatic apply routes and load-bearing private dependencies.
- [x] Isolate the private API experiment below the compatibility boundary.
- [x] Enforce the boundary with a static architecture check.
- [x] Replace the incomplete dormant private mutator with a read-only method-
  presence probe.

## Deferred automatic bridge

- [ ] Define neutral branding plan, result, snapshot, and driver interfaces.
- [ ] Add public OCP\Theming reader and mutator drivers as APIs become available.
- [ ] Build exact-version private drivers only for tested compatibility cells.
- [ ] Add capability fingerprints, circuit breaking, and a public-only kill
  switch.
- [ ] Add snapshot, apply, read-back, rollback, audit, and operation-revision
  services.
- [ ] Run lifecycle integration tests across the declared Nextcloud matrix.
- [ ] Add an explicit review/apply UI only after the recovery contract passes.
