# Token Sync Workflow — Provenance Recording Delta

**Spec refs**: `token-sync-workflow` (Token Generation Script requirement), `token-sets`
(manifest schema — modified in this change), `upstream-freshness` (consumer)
**Standards**: Semantic Versioning 2.0.0, git commit SHA provenance

## MODIFIED Requirements

### Requirement: Token Generation Script

The system MUST include a generation script that converts upstream JSON token files to CSS
custom property files. For every upstream-generated entry, the script MUST additionally
record provenance into `token-sets.json`: `upstreamVersion` (the version of the upstream
theme package the tokens were generated from, when the upstream package declares one) and
`upstreamRef` (the commit SHA of the `nl-design-system/themes` checkout being processed,
provided to the script by the sync workflow). Provenance MUST only be written for entries
the script actually (re)generates; hand-authored entries MUST remain untouched. These
fields are the comparison baseline for the deployed instances' upstream-freshness check
(`upstream-freshness` spec).

#### Scenario: Script reads upstream tokens

- GIVEN the themes repository is cloned to a local path
- WHEN `node scripts/generate-tokens.mjs /path/to/themes` is executed
- THEN the script MUST process all directories under `proprietary/` that contain token files
- AND the script MUST output CSS files to `css/tokens/`

#### Scenario: Script handles malformed input

- GIVEN an upstream token JSON file contains invalid JSON
- WHEN the generation script encounters it
- THEN the script MUST log a warning for that organization
- AND it MUST continue processing other organizations
- AND it MUST NOT overwrite the existing CSS file for that organization
- AND it MUST NOT update that organization's provenance fields (stale tokens keep their
  stale provenance so the freshness check still reports them as outdated)

#### Scenario: Script updates manifest

- GIVEN the script processes all upstream organizations
- WHEN it finishes generating CSS files
- THEN it MUST update `token-sets.json` with the complete list of processed organizations
- AND it MUST preserve any manually added metadata (descriptions, display names)
- AND each regenerated entry MUST carry `upstreamRef` set to the processed themes-repo
  commit SHA
- AND each regenerated entry MUST carry `upstreamVersion` when the upstream theme package
  declares a version, and omit the field otherwise
- AND entries not generated from upstream (e.g. `nextcloud`, `summer-breeze`) MUST NOT
  receive provenance fields

#### Scenario: Workflow provides the upstream commit SHA

- GIVEN the sync workflow has cloned `nl-design-system/themes`
- WHEN it invokes the generation script
- THEN it MUST pass the checkout's commit SHA to the script (argument or environment)
- AND the recorded `upstreamRef` MUST equal that SHA
