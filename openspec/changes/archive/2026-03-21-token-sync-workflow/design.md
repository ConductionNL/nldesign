# Design: token-sync-workflow

## Context
GitHub Actions workflow for nightly sync of NL Design System token sets from upstream nl-design-system/themes repository.

## Decisions
1. Nightly schedule with manual trigger
2. Change detection via hash comparison
3. PR-based updates with auto-generated CSS
4. README sources section for attribution
