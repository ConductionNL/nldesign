# Design: prometheus-metrics

## Context
Prometheus metrics and health endpoints for nldesign. CSS-only app with no database, so metrics focus on configuration state.

## Decisions
1. Metrics: info, up, token_sets_total, active_token_set, custom_overrides_total, theming_syncs_total
2. Health: configuration check (IConfig accessibility)
3. No CSRF required, public access
