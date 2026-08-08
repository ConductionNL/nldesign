---
sidebar_position: 1
---

# Profile inventory

The package contains 40 manifest records and 40 matching CSS snapshots. Eight
are status `ready`; the other 32 are `source-only`. `npm run check:manifest`
enforces that inventory and only ready entries appear in the administrator
selector.

`token-sets.json` is authoritative. A directory scan does not make a profile
selectable.

Every record requires a safe unique id, bounded name and description, status,
and matching `css/tokens/{id}.css`. A ready record additionally requires
`projection: nextcloud-core-v1` and complete semantic properties for font,
primary, primary text, and primary hover. Only ready records may contain
strictly validated manual-Theming hints.

Ready runtime files may contain only those four properties, plus overrides of
the same colour properties for a supplied dark mode. The gate caps them at ten
declarations and 32 KiB and measures primary/text and hover/text at 4.5:1.
If a profile supplies `[data-theme-dark]`, it must repeat the same three dark
colours for `[data-theme-default]` inside the one permitted
`prefers-color-scheme: dark` media query. This covers accounts that retain
Nextcloud's system-following default theme. Ready projection CSS cannot contain
URLs; any future reviewed profile asset belongs in a separate manifest hint.
The catalogue's required `default_profile` field must be `null`, so no package
can silently choose an organisation for a fresh installation.

## What status means

`source-only` means the package retains upstream-derived material for
provenance and mapping work. It is not a runtime profile.

`ready` means the bounded Nextcloud projection contract is structurally
complete. It does not by itself establish official status, endorsement,
current provenance, identity permission, WCAG or NL Design System conformance,
or rendering compatibility across Nextcloud versions and apps.

## Adding or promoting a profile

1. Add a source-only stylesheet and manifest record.
2. Record source repository/version, transformation, gaps, and identity rights.
3. Derive the semantic projection and test its colour pairs.
4. Promote to ready only with the declared projection id.
5. Run manifest, CSS, PHP, package, browser, and accessibility checks.

See the [token reference](../reference/tokens.md) and
[architecture](../architecture.md) for the build/runtime split.
