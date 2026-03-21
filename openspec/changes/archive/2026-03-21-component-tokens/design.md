# Design: component-tokens

## Context
Introduces component-level NL Design System tokens using `--nldesign-component-*` prefix, with a temporary bridge file mapping `--utrecht-*` component tokens to the nldesign namespace.

## Decisions
1. Component tokens use `--nldesign-component-*` prefix
2. Utrecht bridge is a temporary file clearly marked as such
3. Bridge falls back to defaults when utrecht tokens are not defined
