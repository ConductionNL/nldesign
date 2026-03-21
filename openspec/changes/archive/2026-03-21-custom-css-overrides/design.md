# Design: custom-css-overrides

## Context
CSS file persistence layer for user-defined token customizations. custom-overrides.css is the single write target loaded last in the CSS stack.

## Decisions
1. Single :root {} block format, one declaration per line
2. Atomic write via temp file + rename
3. TokenRegistry validation — only editable tokens accepted
4. No database storage — filesystem only
