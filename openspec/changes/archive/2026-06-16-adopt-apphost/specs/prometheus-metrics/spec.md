# Prometheus Metrics Endpoint (delta)

## MODIFIED Requirements

### Requirement: Health Check Endpoint
The app MUST expose a public health check endpoint at `GET /api/health` for monitoring and load balancers. The endpoint MUST be served by the OpenRegister AppHost observability engine's `GenericHealthController` (ADR-040), aliased lazily from the leaf `OCA\NLDesign\Controller\HealthController` service name so that the route name (`health#index`) and URL are unchanged. The checks MUST be declared in `src/manifest.json` using only the OpenRegister-independent primitives (`database`, `filesystem`, `appEnabled`) — never `orAvailable` — because nldesign has no OpenRegister dependency.

#### Scenario: Health check returns the canonical envelope
- GIVEN the app configuration is accessible and the database and filesystem are healthy
- WHEN `GET /index.php/apps/nldesign/api/health` is called
- THEN the response MUST be JSON with the ADR-006 envelope `{"status", "app", "version", "checks"}`
- AND `status` MUST be `"ok"` with `checks.database`, `checks.filesystem`, and `checks.nldesign` all `"ok"`

#### Scenario: Critical check failure yields 503 under adr006 policy
- GIVEN a `severity: "critical"` check (database or appEnabled) fails
- WHEN the health endpoint is called
- THEN the response MUST be HTTP 503 with `status: "error"` and the failing check value starting with `failed`

#### Scenario: Degraded filesystem check does not error the overall status
- GIVEN the `filesystem` check (`severity: "degraded"`) fails while critical checks pass
- WHEN the health endpoint is called
- THEN the response MUST be HTTP 200 with `status: "degraded"` and `checks.filesystem` starting with `failed`

#### Scenario: Health endpoint is publicly accessible without CSRF
- GIVEN a monitoring system calls the health endpoint
- WHEN the request is made
- THEN the engine's `#[PublicPage]` + `#[NoCSRFRequired]` posture MUST allow access without a session or CSRF token

#### Scenario: Nextcloud boots when OpenRegister is absent
- GIVEN OpenRegister is disabled or not installed
- WHEN Nextcloud boots and `Application::register()` runs
- THEN no OpenRegister class MUST be loaded (the thin `HealthController` subclass autoloads its OpenRegister parent only on route dispatch, never at bootstrap), so nldesign still loads and themes
- AND only a request to `/api/health` would surface a degraded 5xx

#### Scenario: Route registration
- GIVEN the app's routes configuration
- WHEN routes are loaded from `appinfo/routes.php`
- THEN a GET route for `/api/health` MUST be mapped to `health#index`

### Requirement: Controller Dependencies
The `MetricsController` MUST receive its dependencies via constructor injection. The health endpoint is served by the engine `GenericHealthController` and is not constructed by nldesign.

#### Scenario: Metrics controller dependencies
- GIVEN the `MetricsController` is constructed
- THEN it MUST receive: `IConfig`, `TokenSetService`, `CustomOverridesService`, `LoggerInterface`
- AND all dependencies MUST be declared as `private readonly` promoted constructor parameters

#### Scenario: No direct service instantiation
- GIVEN the `MetricsController` processes a request
- WHEN metrics are collected
- THEN it MUST use the injected services and MUST NOT use `new TokenSetService()` or similar direct instantiation

#### Scenario: Health controller is engine-owned
- GIVEN the health endpoint is dispatched
- THEN it MUST be served by `OCA\OpenRegister\AppHost\Controller\GenericHealthController` via the lazy alias, NOT by a bespoke nldesign `HealthController`
