---
sidebar_position: 1
---

# Profile inventory and projection

NL Design packages upstream-derived CSS snapshots and only exposes profiles
that also carry a statically gated Nextcloud projection:

    upstream source vocabulary
        -> packaged profile snapshot
        -> minimal nldesign projection
        -> bounded Nextcloud core variables

This is semantic translation. Similar names do not establish that an NL
Design System component and a Nextcloud component have the same role or
states.

## Statuses

`token-sets.json` is a versioned catalogue envelope. It declares its schema,
a required null activation sentinel, and a profile list. The
`default_profile` field may not name an organisation: every fresh installation
stays on native Nextcloud until an administrator acts. Every
record has an `id`, `name`, `description`, and one of these statuses:

- `source-only`: inventory retained for provenance and future mapping work;
  never administrator-selectable and may not declare Theming hints.
- `ready`: selectable only with `projection: nextcloud-core-v1` and all four
  required app-owned properties for primary, primary text, primary hover, and
  font family.

The current package contains 40 records and matching CSS files: 8 ready and 32
source-only. Directory discovery cannot promote a file into the runtime
catalogue.

## Namespace and ownership

- `--nldesign-*` is the app's runtime projection namespace.
- Organisation-prefixed and NL Design System source variables remain source
  context unless the profile explicitly derives the four required values.
- `--color-*`, `--font-face`, user themes, core surfaces, and layout remain
  owned by Nextcloud.

There is no defaults layer. A ready profile must be internally complete for
the values consumed by `theme.css`; it cannot inherit another organisation's
identity by accident.

## Manual Theming hints

Ready records may contain evidenced recommendations for `primary_color`,
`background_color`, `logo`, and `background`. Colours must be six-digit hex.
Assets must be contained local files in the allowlisted image directories.
The current app displays these as manual recommendations and never mutates
Nextcloud Theming automatically.

## Adding or promoting a profile

1. Add a lowercase kebab-case CSS file and matching `source-only` manifest
   record.
2. Record source version, transformation, rights status, and known gaps.
3. Derive and test the four required semantic projection properties.
4. Reduce the runtime file to the four consumed properties, change the record
   to `ready`, add `nextcloud-core-v1`, and add only evidenced Theming hints.
5. Run the manifest, stylesheet, PHP, packaged browser, and accessibility
   checks.

The validator derives catalogue size from the manifest: every record needs one
matching stylesheet and every stylesheet needs one record. `default_profile`
is required and must be null; activation is instance state, never package
policy. The validator deliberately does not hard-code today's 40/8 inventory,
so adding or promoting a profile changes instance data rather than app logic.
