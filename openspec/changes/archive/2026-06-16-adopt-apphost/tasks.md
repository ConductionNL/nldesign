# Tasks — Adopt AppHost observability engine for health

## 1. Declarative manifest
- [x] 1.1 Add `src/manifest.json` with an `observability.health` block using only `database`, `filesystem`, `appEnabled` checks (no `orAvailable`, no OR-object metrics).

## 2. Engine wiring (soft/optional dependency)
- [x] 2.1 No bootstrap-time registration: `Application::register()` stays empty for health. OpenRegister is a soft dependency wired only through the thin subclass, which autoloads on route dispatch — never at bootstrap — so Nextcloud boots when OpenRegister is absent.

## 3. Replace boilerplate with a thin subclass
- [x] 3.1 Replace the ~92-LOC bespoke `lib/Controller/HealthController.php` with a thin subclass of the AppHost `GenericHealthController` whose `index()` delegates to `parent::index()` and re-declares `#[PublicPage]` + `#[NoCSRFRequired]`.
- [x] 3.2 Keep the `health#index` route and `/api/health` URL unchanged.

## 4. Preserve domain
- [x] 4.1 Leave the bespoke `MetricsController` and `/api/metrics` untouched (theme metrics are domain, not boilerplate; engine metrics are admin-only).

## 5. Spec + parity
- [x] 5.1 Update the `prometheus-metrics` spec health requirement to reflect engine adoption.
- [x] 5.2 Confirm `/api/health` parity: same URL, same public posture, improved `{status, app, version, checks}` envelope.
