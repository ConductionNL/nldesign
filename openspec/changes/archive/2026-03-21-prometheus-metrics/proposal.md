# Prometheus Metrics Endpoint

## Problem
Expose application metrics in Prometheus text exposition format at `GET /api/metrics` for monitoring, alerting, and operational dashboards. The nldesign app is a CSS-only theming layer with no database tables, so metrics focus on configuration state (active token set, custom overrides count, theming sync operations) and standard application health signals.

## Proposed Solution
Implement Prometheus Metrics Endpoint following the detailed specification. Key requirements include:
- See full spec for detailed requirements

## Scope
This change covers all requirements defined in the prometheus-metrics specification.

## Success Criteria
- Metrics endpoint returns correct content type
- Metrics endpoint is publicly accessible without CSRF
- Metrics endpoint returns all metric families
- Route registration
- Info gauge with version labels
