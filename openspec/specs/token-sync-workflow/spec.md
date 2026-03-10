# Token Sync Workflow Specification

## Purpose
Automates the synchronization of NL Design System token sets from the upstream `nl-design-system/themes` repository via a nightly GitHub Actions workflow that generates CSS token files and opens PRs when changes are detected.

## ADDED Requirements

### Requirement: Nightly Schedule
The sync workflow MUST run automatically every night to check for upstream token changes.

#### Scenario: Scheduled execution
- GIVEN the GitHub Actions workflow `sync-tokens.yml` is configured
- WHEN the cron schedule triggers (daily at 3 AM UTC)
- THEN the workflow MUST clone the `nl-design-system/themes` repository
- AND it MUST run the token generation script

#### Scenario: Manual trigger
- GIVEN a maintainer wants to sync tokens immediately
- WHEN they trigger the workflow manually via `workflow_dispatch`
- THEN the workflow MUST execute the same steps as the nightly run

### Requirement: Change Detection
The workflow MUST detect whether upstream token changes result in different CSS output before creating a PR.

#### Scenario: No upstream changes
- GIVEN the upstream token files have not changed since the last sync
- WHEN the generation script runs and produces identical CSS output
- THEN the workflow MUST NOT create a PR
- AND it MUST exit successfully

#### Scenario: Upstream changes detected
- GIVEN the upstream token files have changed
- WHEN the generation script produces different CSS output
- THEN the workflow MUST create a PR with the updated token files

#### Scenario: New organization added upstream
- GIVEN a new organization directory appears in the themes repository
- WHEN the generation script runs
- THEN a new CSS token file MUST be generated
- AND `token-sets.json` MUST be updated with the new organization
- AND the PR MUST include both the new CSS file and the updated manifest

### Requirement: PR-Based Updates
Token updates MUST be delivered as pull requests, not direct commits, to allow review before merging.

#### Scenario: PR creation
- GIVEN the generation script produced changed CSS output
- WHEN the workflow creates a PR
- THEN the PR title MUST be `chore: sync NL Design System tokens`
- AND the PR body MUST describe which token sets were added or changed
- AND the PR MUST be created on a branch named `chore/sync-nldesign-tokens`

#### Scenario: Existing open PR
- GIVEN a sync PR from a previous run is still open
- WHEN a new sync run detects additional changes
- THEN the workflow MUST update the existing branch and PR rather than creating a duplicate

### Requirement: Token Generation Script
The system MUST include a generation script that converts upstream JSON token files to CSS custom property files.

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

#### Scenario: Script updates manifest
- GIVEN the script processes all upstream organizations
- WHEN it finishes generating CSS files
- THEN it MUST update `token-sets.json` with the complete list of processed organizations
- AND it MUST preserve any manually added metadata (descriptions, display names)

### Requirement: README Sources Section
The README MUST document the token sync architecture and link to upstream sources.

#### Scenario: Developer reads README
- GIVEN a developer opens the nldesign README
- WHEN they look for information about token sources
- THEN they MUST find links to the NL Design System themes repository
- AND they MUST find links to the NL Design System design tokens handbook
- AND they MUST find an explanation of how the nightly sync workflow operates
- AND they MUST find instructions for adding a new token set manually
