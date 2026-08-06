#!/usr/bin/env bash
#
# DO-NOT-MERGE — DIAGNOSTIC CONTROL ONLY.
#
# Destroys the app's shipped assets before the e2e suite runs, so the suite's
# pass count can be read as an answer to: "how many of these tests would still
# pass if the product did not exist?" Any browser-facing test that survives is
# not evidence about nldesign.
#
# Deliberately NOT `set -e`. This script's exit status is its own verdict about
# whether the truncation actually happened, and `set -e` would let an unrelated
# non-zero command decide it instead.
#
# It also never chains onto the real seed with `&&`: a seed that exits non-zero
# would then skip the truncation entirely and the run would tally a perfectly
# credible-looking control that measured the untouched app.
#
set -uo pipefail

TARGET="${TRUNCATE_TARGET:-all}"
BASE="${NEXTCLOUD_URL:-http://localhost:8080}"
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"

echo "=== truncation control: target=${TARGET} app_dir=${APP_DIR} base=${BASE} ==="

files=()
case "$TARGET" in
	js)  mapfile -t files < <(find "$APP_DIR/js" -type f -name '*.js') ;;
	css) mapfile -t files < <(find "$APP_DIR/css" -type f -name '*.css') ;;
	all) mapfile -t files < <(find "$APP_DIR/js" -type f -name '*.js'; find "$APP_DIR/css" -type f -name '*.css') ;;
	*)   echo "::error::unknown TRUNCATE_TARGET '${TARGET}'"; exit 1 ;;
esac

# A control that matched nothing is the most dangerous outcome of all: the suite
# runs against an intact app and the pass count reads as "survived truncation".
if [ "${#files[@]}" -eq 0 ]; then
	echo "::error::truncation control matched ZERO files for target '${TARGET}' under ${APP_DIR}. This run would have proven nothing."
	exit 1
fi

# Sizes are read with stat calls that run in THIS shell, before and after the
# truncate — never via a $(…) embedded in a string an outer shell expands, which
# would print the same number twice by construction and look like a clean run.
before_total=0
for f in "${files[@]}"; do
	before_total=$(( before_total + $(stat -c%s "$f") ))
done
echo "BEFORE: ${#files[@]} file(s), ${before_total} bytes total"

for f in "${files[@]}"; do
	truncate -s 0 "$f"
done

after_total=0
survivors=0
for f in "${files[@]}"; do
	s=$(stat -c%s "$f")
	after_total=$(( after_total + s ))
	if [ "$s" -ne 0 ]; then
		survivors=$(( survivors + 1 ))
		echo "SURVIVED: ${s} bytes  ${f}"
	fi
done
echo "AFTER:  ${#files[@]} file(s), ${after_total} bytes total"

if [ "$before_total" -eq "$after_total" ]; then
	echo "::error::BEFORE and AFTER totals are identical (${before_total}). Nothing was truncated; this run is not a control."
	exit 1
fi
if [ "$survivors" -ne 0 ]; then
	echo "::error::${survivors} file(s) survived truncation."
	exit 1
fi

# ── The served response is the only thing the browser ever sees ──────────────
# On-disk zero is necessary but not sufficient: a cache, a build step, or a
# route that regenerates the asset would leave the browser with intact bytes.
probe_urls=()
case "$TARGET" in
	js)  probe_urls=("/apps/nldesign/js/admin.js") ;;
	css) probe_urls=("/apps/nldesign/css/systems/lasuite/element-overrides.css") ;;
	all) probe_urls=("/apps/nldesign/js/admin.js" "/apps/nldesign/css/systems/lasuite/element-overrides.css") ;;
esac

served_ok=0
for p in "${probe_urls[@]}"; do
	for url in "${BASE}${p}" "${BASE}/index.php${p}"; do
		read -r code bytes < <(curl -s -o /tmp/served.$$ -w '%{http_code} %{size_download}' "$url")
		echo "SERVED: HTTP ${code}  ${bytes} bytes  ${url}"
		if [ "$code" = "200" ]; then
			served_ok=$(( served_ok + 1 ))
			if [ "$bytes" -ne 0 ]; then
				echo "::error::${url} still serves ${bytes} bytes after truncation — the browser would receive intact assets and this run is not a control."
				rm -f /tmp/served.$$
				exit 1
			fi
		fi
		rm -f /tmp/served.$$
	done
done

if [ "$served_ok" -eq 0 ]; then
	echo "::error::no probe URL returned HTTP 200, so the truncation could not be confirmed on the wire. Refusing to run a control that cannot be verified."
	exit 1
fi

echo "=== truncation control ARMED: ${before_total} -> ${after_total} bytes on disk, ${served_ok} URL(s) confirmed empty on the wire ==="
