---
status: implemented
reviewed_date: 2026-02-28
enriched_date: 2026-03-20
---

# Prometheus Metrics Endpoint

## Purpose
Expose application metrics in Prometheus text exposition format at `GET /api/metrics` for monitoring, alerting, and operational dashboards.

@e2e exclude API/backend metrics spec — all scenarios describe HTTP response format, metric values, and controller dependency injection; no admin UI surface. The nldesign app is a CSS-only theming layer with no database tables, so metrics focus on configuration state (active token set, custom overrides count, theming sync operations) and standard application health signals.

## Requirements

### REQ-PROM-001: Metrics Endpoint
The app MUST expose a Prometheus-compatible metrics endpoint that returns all app metrics in the standard text exposition format.

#### Scenario: Metrics endpoint returns correct content type
- GIVEN the admin or monitoring system calls `GET /index.php/apps/nldesign/api/metrics`
- WHEN the `MetricsController::index()` method handles the request
- THEN the response MUST have content type `text/plain; version=0.0.4; charset=utf-8`
- AND the response body MUST contain valid Prometheus text exposition format

#### Scenario: Metrics endpoint is publicly accessible without CSRF
- GIVEN a monitoring system (e.g. Prometheus scraper) calls the metrics endpoint
- WHEN the request is made
- THEN the `@NoCSRFRequired` annotation MUST allow access without a CSRF token
- AND this allows automated scraping from external monitoring tools

#### Scenario: Metrics endpoint returns all metric families
- GIVEN the metrics endpoint is called
- WHEN the response is generated
- THEN it MUST contain HELP and TYPE lines for each metric family
- AND each metric family MUST have at least one sample line
- AND the response MUST end with a newline character

#### Scenario: Route registration
- GIVEN the app's routes configuration
- WHEN routes are loaded from `appinfo/routes.php`
- THEN a GET route for `/api/metrics` MUST be mapped to `metrics#index`

### REQ-PROM-002: Application Info Metric
The app MUST expose an info gauge with version labels for identification.

#### Scenario: Info gauge with version labels
- GIVEN the metrics endpoint is called
- WHEN the info metric is generated
- THEN `nldesign_info` MUST be a gauge with value `1`
- AND it MUST have labels: `version` (app version from IConfig `installed_version`), `php_version` (PHP_VERSION), `nextcloud_version` (from system config `version`)

#### Scenario: Info gauge format
- GIVEN the app version is "1.2.3", PHP is "8.2.0", Nextcloud is "29.0.0"
- WHEN the info metric is output
- THEN the line MUST be: `nldesign_info{version="1.2.3",php_version="8.2.0",nextcloud_version="29.0.0"} 1`
- AND it MUST be preceded by `# HELP nldesign_info Application information`
- AND `# TYPE nldesign_info gauge`

#### Scenario: Versions read from correct sources
- GIVEN the metrics controller is initialized
- WHEN version values are collected
- THEN the app version MUST come from `IConfig::getAppValue('nldesign', 'installed_version', '0.0.0')`
- AND the PHP version MUST come from the `PHP_VERSION` constant
- AND the Nextcloud version MUST come from `IConfig::getSystemValueString('version', '0.0.0')`

### REQ-PROM-003: Application Up Gauge
The app MUST expose an up gauge indicating overall application health.

#### Scenario: App is healthy
- GIVEN the metrics endpoint responds successfully
- WHEN the up metric is generated
- THEN `nldesign_up` MUST be a gauge with value `1`
- AND it MUST be preceded by HELP and TYPE lines

#### Scenario: Up gauge always 1 when endpoint responds
- GIVEN the metrics controller is operational
- WHEN the endpoint is called
- THEN `nldesign_up 1` MUST always be present
- AND if the endpoint itself fails to respond, Prometheus will treat the target as down

#### Scenario: Up gauge format
- GIVEN the metrics are generated
- THEN the output MUST include:
  - `# HELP nldesign_up Whether the application is up`
  - `# TYPE nldesign_up gauge`
  - `nldesign_up 1`

### REQ-PROM-004: Token Sets Total Metric
The app MUST expose the total number of available token sets as a gauge.

#### Scenario: Token sets counted from filesystem
- GIVEN there are 39 CSS files in `css/tokens/`
- WHEN the token set metric is collected via `TokenSetService::getAvailableTokenSets()`
- THEN `nldesign_token_sets_total` MUST be a gauge with value `39`

#### Scenario: Token set metric with HELP and TYPE
- GIVEN the metrics are generated
- THEN the output MUST include:
  - `# HELP nldesign_token_sets_total Total number of available token sets`
  - `# TYPE nldesign_token_sets_total gauge`
  - `nldesign_token_sets_total 39`

#### Scenario: Token set count error handled gracefully
- GIVEN `TokenSetService::getAvailableTokenSets()` throws an exception
- WHEN the metrics are collected
- THEN `nldesign_token_sets_total` MUST be reported as `0`
- AND a warning MUST be logged via the logger with the exception message
- AND the metrics endpoint MUST NOT fail entirely

### REQ-PROM-005: Active Token Set Metric
The app MUST expose which token set is currently active as a labeled gauge.

#### Scenario: Active token set reported
- GIVEN the active token set is "amsterdam"
- WHEN the active set metric is collected
- THEN `nldesign_active_token_set{name="amsterdam"}` MUST be a gauge with value `1`

#### Scenario: Active token set with HELP and TYPE
- GIVEN the metrics are generated
- THEN the output MUST include:
  - `# HELP nldesign_active_token_set Currently active token set`
  - `# TYPE nldesign_active_token_set gauge`

#### Scenario: Default token set reported when not configured
- GIVEN no token set has been explicitly configured
- WHEN the metric is collected from `IConfig::getAppValue('nldesign', 'token_set', 'rijkshuisstijl')`
- THEN `nldesign_active_token_set{name="rijkshuisstijl"}` MUST have value `1`

#### Scenario: Active token set in error recovery
- GIVEN the token set metrics collection fails
- WHEN the error is caught
- THEN the active token set metric MUST be omitted (it is inside the try block)
- AND only `nldesign_token_sets_total 0` MUST be reported as fallback

### REQ-PROM-006: Custom Overrides Total Metric
The app MUST expose the number of admin-defined custom CSS overrides as a gauge.

#### Scenario: Custom overrides counted
- GIVEN the admin has defined 5 custom CSS overrides in `custom-overrides.css`
- WHEN the override metric is collected via `CustomOverridesService::read()`
- THEN `nldesign_custom_overrides_total` MUST be a gauge with value `5`

#### Scenario: No custom overrides
- GIVEN no custom overrides have been defined
- WHEN the metric is collected
- THEN `nldesign_custom_overrides_total` MUST be `0`

#### Scenario: Custom overrides with HELP and TYPE
- GIVEN the metrics are generated
- THEN the output MUST include:
  - `# HELP nldesign_custom_overrides_total Total custom CSS overrides`
  - `# TYPE nldesign_custom_overrides_total gauge`

#### Scenario: Override count error handled gracefully
- GIVEN `CustomOverridesService::read()` throws an exception
- WHEN the metrics are collected
- THEN `nldesign_custom_overrides_total` MUST be reported as `0`
- AND a warning MUST be logged
- AND the metrics endpoint MUST NOT fail entirely

### REQ-PROM-007: Theming Syncs Counter
The app MUST expose the total number of theming sync operations as a counter.

#### Scenario: Theming syncs counter reported
- GIVEN the admin has performed 3 theming sync operations
- AND `IConfig::getAppValue('nldesign', 'theming_syncs_total', '0')` returns `'3'`
- WHEN the sync metric is collected
- THEN `nldesign_theming_syncs_total` MUST be a counter with value `3`

#### Scenario: No theming syncs performed
- GIVEN no theming sync has been performed
- WHEN the metric is collected
- THEN `nldesign_theming_syncs_total` MUST be `0`

#### Scenario: Theming syncs with HELP and TYPE
- GIVEN the metrics are generated
- THEN the output MUST include:
  - `# HELP nldesign_theming_syncs_total Total theming sync operations`
  - `# TYPE nldesign_theming_syncs_total counter`

#### Scenario: Syncs counter is read from IConfig
- GIVEN the syncs counter is stored in IConfig
- WHEN the value is read
- THEN it MUST be cast to integer via `(int)` to handle string storage
- AND if the value is not set, the default MUST be `'0'`

### REQ-PROM-008: Error Resilience
The metrics endpoint MUST be resilient to individual metric collection failures without failing the entire response.

#### Scenario: Token set metrics fail, other metrics succeed
- GIVEN the token set service throws an exception
- WHEN the metrics are collected
- THEN info, up, custom overrides, and theming syncs metrics MUST still be present
- AND token set metrics MUST fall back to 0
- AND a warning MUST be logged

#### Scenario: Custom overrides fail, other metrics succeed
- GIVEN the custom overrides service throws an exception
- WHEN the metrics are collected
- THEN info, up, token sets, and theming syncs metrics MUST still be present
- AND custom overrides MUST fall back to 0
- AND a warning MUST be logged

#### Scenario: Multiple failures handled independently
- GIVEN both token set and custom overrides services throw exceptions
- WHEN the metrics are collected
- THEN info, up, and theming syncs MUST still be present
- AND both failing metrics MUST fall back to 0
- AND both warnings MUST be logged independently

### REQ-PROM-009: Health Check Endpoint
The app MUST expose a health check endpoint that returns JSON status for monitoring and load balancers.

#### Scenario: Health check returns OK
- GIVEN the app configuration is accessible
- AND `IConfig::getAppValue('nldesign', 'token_set', 'rijkshuisstijl')` returns a non-empty string
- WHEN `GET /index.php/apps/nldesign/api/health` is called
- THEN the response MUST be JSON with `{"status": "ok", "checks": {"configuration": "ok"}}`

#### Scenario: Health check returns degraded
- GIVEN the token set configuration returns an empty string
- WHEN the health check runs
- THEN `status` MUST be `"ok"` (overall)
- AND `checks.configuration` MUST be `"degraded"`

#### Scenario: Health check returns error on failure
- GIVEN the IConfig service throws an exception
- WHEN the health check runs
- THEN `status` MUST be `"error"`
- AND `checks.configuration` MUST be `"error"`
- AND the error MUST be logged via the logger

#### Scenario: Health endpoint is publicly accessible without CSRF
- GIVEN a monitoring system calls the health endpoint
- WHEN the request is made
- THEN the `@NoCSRFRequired` annotation MUST allow access without a CSRF token

#### Scenario: Route registration
- GIVEN the app's routes configuration
- WHEN routes are loaded from `appinfo/routes.php`
- THEN a GET route for `/api/health` MUST be mapped to `health#index`

### REQ-PROM-010: Prometheus Format Compliance
All metrics MUST strictly comply with the Prometheus text exposition format specification.

#### Scenario: HELP line format
- GIVEN any metric family
- WHEN the HELP line is output
- THEN it MUST follow the format: `# HELP <metric_name> <docstring>`
- AND each metric MUST have exactly one HELP line

#### Scenario: TYPE line format
- GIVEN any metric family
- WHEN the TYPE line is output
- THEN it MUST follow the format: `# TYPE <metric_name> <type>`
- AND type MUST be one of: `counter`, `gauge`, `histogram`, `summary`, `untyped`
- AND each metric MUST have exactly one TYPE line

#### Scenario: Label values properly escaped
- GIVEN the active token set name contains special characters (e.g., quotes)
- WHEN the label value is output
- THEN double quotes in label values MUST be escaped
- AND backslashes MUST be escaped
- AND newlines MUST be escaped

### REQ-PROM-011: Controller Dependencies
The MetricsController MUST receive all required dependencies via constructor injection.

#### Scenario: Dependencies injected
- GIVEN the MetricsController is constructed
- THEN it MUST receive: `IConfig` (for reading config values), `TokenSetService` (for counting token sets), `CustomOverridesService` (for counting overrides), `LoggerInterface` (for error logging)
- AND all dependencies MUST be declared as `private readonly` promoted constructor parameters

#### Scenario: No direct service instantiation
- GIVEN the MetricsController processes a request
- WHEN metrics are collected
- THEN it MUST use the injected services
- AND it MUST NOT use `new TokenSetService()` or similar direct instantiation

#### Scenario: Health controller dependencies
- GIVEN the HealthController is constructed
- THEN it MUST receive: `LoggerInterface` (for error logging)
- AND it MAY use `\OCP\Server::get(\OCP\IConfig::class)` for configuration access (stateless pattern)

## Current Implementation Status

**Fully implemented:**
- MetricsController at `lib/Controller/MetricsController.php` with `@NoCSRFRequired` annotation
- Info gauge: `nldesign_info` with version, php_version, nextcloud_version labels
- Up gauge: `nldesign_up` always 1
- Token sets total: `nldesign_token_sets_total` via `TokenSetService::getAvailableTokenSets()` with try/catch fallback to 0
- Active token set: `nldesign_active_token_set{name="..."}` from IConfig with default "rijkshuisstijl"
- Custom overrides total: `nldesign_custom_overrides_total` via `CustomOverridesService::read()` with try/catch fallback to 0
- Theming syncs counter: `nldesign_theming_syncs_total` from IConfig with cast to int
- Content-Type header: `text/plain; version=0.0.4; charset=utf-8`
- Error resilience: independent try/catch blocks for token set and override metrics
- Warning logging on metric collection failures
- HealthController at `lib/Controller/HealthController.php` with `@NoCSRFRequired`
- Health check: configuration check with ok/degraded/error status
- Routes: `/api/metrics` -> `metrics#index`, `/api/health` -> `health#index`
- Constructor injection of IConfig, TokenSetService, CustomOverridesService, LoggerInterface (promoted parameters with `private readonly`)

**Not yet implemented:**
- All requirements in this spec are fully implemented.
- Note: `nldesign_requests_total` and `nldesign_request_duration_seconds` (mentioned in original spec) are NOT implemented -- these require request-level instrumentation middleware which is not present. The implemented metrics focus on configuration state which is appropriate for a CSS-only theming app.

## Standards & References
- Prometheus text exposition format: https://prometheus.io/docs/instrumenting/exposition_formats/
- OpenMetrics specification: https://openmetrics.io/
- Nextcloud server monitoring patterns
- OpenRegister MetricsService and HeartbeatController as reference implementation
