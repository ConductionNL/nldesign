---
status: retired
reviewed_date: 2026-08-08
retired_date: 2026-08-08
---

# Hide Slogan Retirement Specification

## REQ-SLOGAN-001: No reachable compatibility option

The app MUST NOT expose a slogan-toggle route or settings control and MUST NOT
load a footer-hiding stylesheet. A legacy `hide_slogan` app-config value MUST
have no runtime effect.

## REQ-SLOGAN-002: Ownership and reintroduction gate

The app MUST NOT represent hiding the whole guest footer as changing only the
slogan. Instance slogan mutation remains owned by Nextcloud Theming. Any future
adapter MUST define the exact retained and removed content and MUST pass
packaged login-page tests on each claimed Nextcloud major.
