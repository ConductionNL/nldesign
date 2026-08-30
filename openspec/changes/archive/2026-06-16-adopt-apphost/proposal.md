# Adopt the AppHost observability engine for the health endpoint

## Problem
nldesign ships a ~92-LOC bespoke `HealthController` (`lib/Controller/HealthController.php`) that is pure boilerplate: it hand-rolls the `{status, checks}` envelope and only verifies that the `token_set` app-config is non-empty. Every Conduction app re-implements this same controller slightly differently, so the health contract drifts per app. OpenRegister now ships the AppHost observability engine (ADR-040), a single engine-owned `GenericHealthController` that renders the canonical ADR-006 `{status, app, version, checks}` shape from a declarative `observability.health` block in `src/manifest.json`.

## Constraint — nldesign is a theme app with NO OpenRegister dependency
nldesign is a pure NL Design token-injection / per-app CSS theming layer. It has no OpenRegister objects, no register, and no hard dependency on OpenRegister (`info.xml` declares no `<openregister>` dependency). Therefore:

- The adopted health block uses ONLY the OR-independent check primitives: `database`, `filesystem`, `appEnabled`. It does NOT use `orAvailable` and does NOT declare any OR-object metrics.
- The engine lives in OpenRegister, so it becomes a SOFT/optional dependency for the health endpoint. It is wired through a thin subclass that `extends` the engine controller; that subclass is autoloaded only when `/api/health` is dispatched, never at Nextcloud bootstrap. So when OpenRegister is disabled/absent Nextcloud still boots and nldesign still themes — only the health endpoint would degrade.

## Proposed Solution
- Add an `observability.health` block to `src/manifest.json` with `database` (critical) + `filesystem` (degraded) + `appEnabled: nldesign` (critical) checks under the `adr006` status-code policy.
- Replace the bespoke `lib/Controller/HealthController.php` with a thin subclass of `OCA\OpenRegister\AppHost\Controller\GenericHealthController` whose `index()` delegates to `parent::index()` and re-declares the public auth posture so it stays statically visible at nldesign's route. The route name (`health#index`) and URL (`/api/health`) are unchanged; the response contract is owned by the engine.
- KEEP nldesign's domain untouched: the bespoke `MetricsController` (`/api/metrics`) is NOT adopted because it exposes nldesign's own theme metrics (token sets, custom overrides, theming syncs) — domain value, not boilerplate — and the engine's metrics endpoint is admin-only, which would change the public auth posture.

## Scope
Health endpoint only. Metrics, theming, token injection, and per-app CSS exclusion remain nldesign's own.

## Success Criteria
- `GET /api/health` is unchanged in URL and remains public (`#[PublicPage]`).
- Response now carries the canonical `{status, app, version, checks}` envelope (a documented improvement over the bespoke `{status, checks}`).
- Nextcloud boots with OpenRegister disabled (no OR symbol referenced at bootstrap).
- Bespoke `HealthController` removed.
