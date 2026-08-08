---
sidebar_position: 4
---

# Token audit status

The February 2026 “all implementations are correct” report has been retired. It covered a small earlier profile set, treated selected hex values as proof of rendered accessibility, and did not record reproducible upstream versions or a Nextcloud surface matrix.

## Automated checks available now

- unique, safe profile ids;
- one manifest entry per packaged stylesheet, explicit ready/source-only status,
  a required null package default, derived ready count, and no orphans;
- required names and descriptions;
- allowlisted manual-Theming fields;
- six-digit colour syntax, contained regular local assets, no remote/data URLs,
  no unresolved source placeholders, and no high-risk CSS constructs;
- exactly the four consumed semantic properties for each ready projection,
  at most ten declarations including explicit-dark and system-dark/default
  overrides, a 32 KiB runtime cap, and 4.5:1 primary/text and
  primary-hover/text contrast in each supplied mode;
- no URLs, escapes, arbitrary selectors, or unsupported at-rules in ready
  projection CSS; and
- deterministic initial state, random transition revisions, exclusive locking,
  rollback, authorization attributes, and stylesheet order in unit tests.

## Still required

Source semantics, provenance, non-primary component behavior, identity rights,
high-contrast precedence, and live Nextcloud compatibility remain profile
evidence tasks. Passing the measured primary-pair gate is not whole-profile or
whole-installation conformance. See the [roadmap](../roadmap.md) for the
intended fixtures and surface matrix.
