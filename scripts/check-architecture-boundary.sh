#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
TARGET_DIR="${ROOT_DIR}/lib"

# Prefer ripgrep locally, but GitHub's Node runner image does not guarantee it.
# The fallback remains fail-closed: a missing tool or a search error aborts the
# gate instead of being mistaken for "no matches".
if command -v rg >/dev/null 2>&1; then
  search_tool="rg"
elif command -v grep >/dev/null 2>&1; then
  search_tool="grep"
else
  echo "[boundary] ripgrep (rg) or grep is required but neither was found" >&2
  exit 1
fi
readonly search_tool

if [[ ! -d "${TARGET_DIR}" ]]; then
  echo "[boundary] no lib directory found at ${TARGET_DIR}" >&2
  exit 0
fi

violations_log=$(mktemp)
search_output=$(mktemp)
readonly violations_log search_output
trap 'rm -f "${violations_log}" "${search_output}"' EXIT

check_pattern() {
  local pattern="$1"
  local message="$2"
  local compatibility_allowed="${3:-false}"
  local file
  local status=0

  # Both search tools exit 1 for "no matches" and 2+ for a real error. Only the
  # first is benign.
  if [[ "$search_tool" == "rg" ]]; then
    rg -n --no-heading --hidden --glob '*.php' -e "$pattern" "${TARGET_DIR}" \
      > "${search_output}" || status=$?
  else
    grep -R -n -E --include='*.php' -e "$pattern" "${TARGET_DIR}" \
      > "${search_output}" || status=$?
  fi

  if [[ "$status" -gt 1 ]]; then
    echo "[boundary] search failed (${search_tool} exit ${status}) for pattern: ${pattern}" >&2
    exit "$status"
  fi

  while IFS= read -r line; do
    [[ -z "$line" ]] && continue
    file=$(echo "$line" | cut -d: -f1)
    if [[ "$compatibility_allowed" != "true" || "$file" != *"/lib/Infrastructure/Nextcloud/Compatibility/"* ]]; then
      printf '%s: %s\n' "$line" "$message" >> "${violations_log}"
    fi
  done < "${search_output}"
}

check_pattern "\\bOCA\\\\Theming\\\\" "private OCA\\Theming namespace used outside compatibility" true
check_pattern "\\bThemingDefaults\\b" "private ThemingDefaults used outside compatibility" true
check_pattern "\\bImageManager\\b" "private ImageManager used outside compatibility" true
check_pattern "\\btheming\.config\." "direct theming config key access outside compatibility" true
check_pattern "\\b(file_put_contents|fwrite|ftruncate|rename|unlink|copy|mkdir|rmdir|touch|chmod|chown|symlink|link)[[:space:]]*\\(" "direct filesystem mutation used in runtime code"

if [[ -s "${violations_log}" ]]; then
  echo "Architecture boundary violations detected:"
  sort -u "${violations_log}" | while IFS= read -r violation; do
    echo "  - $violation"
  done
  printf '\nRunbook: move private integration behind lib/Infrastructure/Nextcloud/Compatibility/*.\n' >&2
  exit 1
fi

echo "Architecture boundary check passed."
