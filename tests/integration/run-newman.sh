#!/usr/bin/env bash
#
# NL Design System API-contract test runner (Newman / Postman).
#
# Runs tests/integration/nldesign.postman_collection.json against a live
# Nextcloud instance serving the nldesign (NL Design System Theme) app. The
# collection is self-contained and idempotent: it captures the live token_set
# and custom overrides, asserts the read/write/validation/authz contract, and
# restores the captured state in teardown so a run leaves no theming drift.
#
# Usage:
#   ./run-newman.sh                                  # defaults to localhost:8080, admin:admin
#   BASE_URL=http://localhost:8080 ./run-newman.sh
#   ADMIN_USER=admin ADMIN_PASS=admin ./run-newman.sh
#
# Uses a globally-installed `newman` if present, otherwise falls back to
# `npx newman`. Runs are serialised via flock (when available) so concurrent
# CI agents do not trip the Nextcloud brute-force protection.
#
# SPDX-License-Identifier: EUPL-1.2
# SPDX-FileCopyrightText: 2026 Conduction B.V. <info@conduction.nl>

set -euo pipefail

# Re-exec under an exclusive flock so parallel agents serialise.
LOCK_FILE="/tmp/uiaudit-nldesign.lock"
if [ "${NLDESIGN_NEWMAN_LOCKED:-}" != "1" ] && command -v flock >/dev/null 2>&1; then
  export NLDESIGN_NEWMAN_LOCKED=1
  exec flock "${LOCK_FILE}" "$0" "$@"
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
COLLECTION="${SCRIPT_DIR}/nldesign.postman_collection.json"
# Multipart formdata file `src` paths in the collection (fixtures/*.css) are
# resolved by newman relative to --working-dir, which DEFAULTS TO THE PROCESS
# CWD. The shared CI workflow (ConductionNL/.github .github/workflows/quality.yml,
# job `newman`) does `cd server/apps/<app>/<newman-collection-path>` and then
# invokes newman with no --working-dir, so in CI the working dir is THIS
# directory. The `src` paths are therefore relative to the collection file, and
# this runner must pin --working-dir to the same place — otherwise every file
# part silently resolves to nothing, newman sends the request WITHOUT the file,
# and the app answers 400 "No file uploaded." for a payload the test believes it
# uploaded. That is exactly how 14 assertions failed on `development`: the paths
# were repo-root-relative, which only ever worked because this script passed the
# repo root, and CI never did.
FIXTURE_ROOT="${SCRIPT_DIR}"

BASE_URL="${BASE_URL:-http://localhost:8080}"
ADMIN_USER="${ADMIN_USER:-admin}"
ADMIN_PASS="${ADMIN_PASS:-admin}"

# Authenticated requests use baseUrl; the authorization (no-auth) tests use a
# DIFFERENT host alias so the session cookie that authenticated requests
# establish (host-scoped) is never sent to them — keeping them genuinely
# unauthenticated. Defaults to the 127.0.0.1 form of baseUrl.
if [ -n "${NO_AUTH_BASE:-}" ]; then
  NOAUTH_BASE="${NO_AUTH_BASE}"
elif [[ "${BASE_URL}" == *"localhost"* ]]; then
  NOAUTH_BASE="${BASE_URL/localhost/127.0.0.1}"
else
  NOAUTH_BASE="${BASE_URL/127.0.0.1/localhost}"
fi

if command -v newman >/dev/null 2>&1; then
  NEWMAN=(newman)
else
  NEWMAN=(npx --yes newman)
fi

# --ignore-redirects: assert NC's 401 on unauthenticated requests directly
# instead of following it to a 200 HTML login page (so authz tests are honest).
"${NEWMAN[@]}" run "${COLLECTION}" \
  --env-var "baseUrl=${BASE_URL}" \
  --env-var "noAuthBase=${NOAUTH_BASE}" \
  --env-var "adminUser=${ADMIN_USER}" \
  --env-var "adminPass=${ADMIN_PASS}" \
  --ignore-redirects \
  --working-dir "${FIXTURE_ROOT}" \
  --reporters cli \
  --color on \
  "$@"
