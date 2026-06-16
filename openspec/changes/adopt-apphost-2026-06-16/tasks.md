# Tasks — Adopt AppHost observability engine for health

## 1. Declarative manifest
- [x] 1.1 Add `src/manifest.json` with an `observability.health` block using only `database`, `filesystem`, `appEnabled` checks (no `orAvailable`, no OR-object metrics).

## 2. Lazy engine wiring
- [x] 2.1 In `Application::register()`, register a lazy service alias from `OCA\NLDesign\Controller\HealthController` to the AppHost `GenericHealthController`, referencing engine class names only as strings inside the closure so Nextcloud boots when OpenRegister is absent.

## 3. Remove boilerplate
- [x] 3.1 Delete the bespoke `lib/Controller/HealthController.php`.
- [x] 3.2 Keep the `health#index` route and `/api/health` URL unchanged.

## 4. Preserve domain
- [x] 4.1 Leave the bespoke `MetricsController` and `/api/metrics` untouched (theme metrics are domain, not boilerplate; engine metrics are admin-only).

## 5. Spec + parity
- [x] 5.1 Update the `prometheus-metrics` spec health requirement to reflect engine adoption.
- [x] 5.2 Confirm `/api/health` parity: same URL, same public posture, improved `{status, app, version, checks}` envelope.
