## 1. Dependency swap

- [ ] 1.1 In `package.json`: remove `@amsterdam/design-system-assets` and
      `@amsterdam/design-system-react-icons` from `dependencies`; add
      `@conduction/nextcloud-vue` to `devDependencies` (build-time only — nothing under `js/` or
      `lib/` may import it). Run `npm install` to refresh `package-lock.json`.
      Before removing `@amsterdam/design-system-react-icons`, grep the repo to confirm its only
      references are `package.json`/`package-lock.json`/`README.md` (expected: yes).
- [ ] 1.2 Update `README.md` dependency table (line ~321): drop both `@amsterdam/*` rows
      (including the incorrect "EUPL-compatible" claim for the react-icons package) and add
      `@conduction/nextcloud-vue` with the per-set licences (RVO CC0-1.0, OpenGemeenten CC0-1.0,
      Den Haag EUPL-1.2, per `nextcloud-vue/src/icons/ATTRIBUTION.md`).

## 2. Alias map

- [ ] 2.1 Create `scripts/icon-aliases.json`: a JSON object mapping legacy Amsterdam PascalCase
      names (no `.svg`) to replacement pack paths (`"<set>/<key>"`), curated by eyeballing the
      344 legacy names in `img/ICONS.md` against the pack labels in
      `@conduction/nextcloud-vue/src/icons/{rvo,openGemeenten,denHaag}.js`. Cover at minimum the
      generic-UI names most plausible in stored consumer data (Search, Star, Home, Calendar,
      Mail/Email, Phone, Person/User, Settings/Gear, Document, Download, Upload, Alert/Bell,
      arrows, chevrons, Close, Menu, Map/Location, Print, Trash, Edit/Pencil, Info, Question,
      Checkmark, plus their `Fill` variants where an equivalent exists). Names without a
      reasonable equivalent are simply absent from the map.
- [ ] 2.2 Add a header note in the JSON (top-level `"_comment"` key) stating the alias set is a
      one-release deprecation shim, scheduled for deletion in the next minor release.

## 3. Rewrite the icon build

- [ ] 3.1 Rewrite `scripts/build-icons.js`:
      - delete `img/icons/` content and recreate it (idempotent regenerate);
      - load `rvo.js`, `openGemeenten.js`, `denHaag.js` from
        `node_modules/@conduction/nextcloud-vue/src/icons/`;
      - for each `{ key, url }` entry, strip the `data:image/svg+xml,` prefix, `decodeURIComponent`
        the remainder, and write the SVG to `img/icons/rvo/<key>.svg`,
        `img/icons/open-gemeenten/<key>.svg`, `img/icons/den-haag/<key>.svg` respectively;
      - for each `scripts/icon-aliases.json` entry, copy the mapped replacement SVG to the legacy
        top-level path `img/icons/<Name>.svg`;
      - REMOVE all copying from `@amsterdam/design-system-assets` (icons AND `logo/`); the script
        MUST NOT touch `img/logos/`;
      - regenerate `img/ICONS.md` (see 3.2);
      - print per-set counts + alias count and exit non-zero if any set yields zero icons.
- [ ] 3.2 New `img/ICONS.md` template inside the build script, containing:
      - per-set inventories (set, upstream project, licence, count) attributing
        `@conduction/nextcloud-vue` `src/icons/ATTRIBUTION.md` as the canonical licence record —
        the MPL-2.0/Amsterdam attribution is gone;
      - the legacy-alias table (legacy name → replacement path) with the explicit removal
        deadline "removed in the next minor release";
      - updated PHP usage snippet using `imagePath('nldesign', 'icons/rvo/zoek.svg')`;
      - the existing availability/fallback paragraph and naming-stability (public API) section;
      - a Logos section (23 org logos, static checked-in huisstijl assets, not build output).
- [ ] 3.3 Run `npm run build:icons`; commit the regenerated `img/icons/` tree (Amsterdam SVGs
      deleted, `rvo/` + `open-gemeenten/` + `den-haag/` + alias files present) and `img/ICONS.md`.

## 4. Tests

- [ ] 4.1 Update `tests/Unit/IconAssetsTest.php`:
      - inventory test derives expected per-set file lists/counts from the pack modules (or from
        `img/ICONS.md`'s generated counts) instead of the hardcoded 344;
      - every alias in `scripts/icon-aliases.json` resolves: legacy file exists at
        `img/icons/<Name>.svg` and is byte-identical to its mapped `img/icons/<set>/<key>.svg`;
      - drop the `Fill`-suffix pairing assertion for pack icons (Amsterdam naming convention);
        keep SVG well-formedness + no-`<script>`/no-event-handler assertions for every file in
        `img/icons/` and `img/logos/`;
      - licence-notice assertion now checks `img/ICONS.md` names CC0-1.0 and EUPL-1.2 and does
        NOT mention MPL-2.0 or `@amsterdam/design-system-assets` as a current source;
      - assert NO file in the shipped tree comes from the Amsterdam package: the string
        `@amsterdam` must not appear in `package.json` `dependencies`, and no top-level
        `img/icons/*.svg` exists that is not listed in `scripts/icon-aliases.json`.
- [ ] 4.2 Run the PHP suite in the nextcloud:34 container
      (`docker run --rm -v $PWD:/app -w /app <nc-image> php vendor/bin/phpunit -c phpunit-unit.xml`)
      and `npm run test:unit`; both green.

## 5. Docs + changelog

- [ ] 5.1 Update `docs/reference/icons.md`: retitle away from "Amsterdam Design System Icons
      Integration"; new counts (1488 across three sets + N aliases), new paths, per-set licences,
      alias/deprecation table, unchanged fallback guidance.
- [ ] 5.2 Update `README.md` icon sections (lines ~27, ~31-33, ~350): new counts and sources;
      remove every claim that Amsterdam icons ship.
- [ ] 5.3 `CHANGELOG.md`: BREAKING entry listing (a) removal of all 344 Amsterdam icon filenames
      with the licence rationale, (b) the alias table (name → replacement, or "no equivalent"),
      (c) the alias-removal deadline (next minor release), (d) the new inventory. Bump
      `appinfo/info.xml` `<version>` so the NC `?v=` cache-buster refreshes.
- [ ] 5.4 File a follow-up issue on launchpad to migrate
      `src/components/__tests__/TileEditor.spec.js` fixture URLs (and any stored-tile migration
      guidance) from `/apps/nldesign/img/icons/Star.svg` to the new paths before the alias
      removal release.

## 6. Verify

- [ ] 6.1 `composer check:strict` and full unit suites (PHP in nextcloud:34 container + vitest)
      pass.
- [ ] 6.2 Grep gates: `grep -ri "@amsterdam" package.json lib/ js/ scripts/build-icons.js`
      returns nothing; `grep -rn "MPL-2.0" img/ docs/ README.md` returns nothing;
      `ls img/logos | wc -l` still prints 23.
- [ ] 6.3 Live curl on the 8080 dev instance (nldesign enabled, app redeployed):
      - `curl -sI http://localhost:8080/apps/nldesign/img/icons/rvo/home.svg` → 200 with an SVG
        content type (pick any key that exists in the generated tree);
      - one aliased legacy URL, e.g. `curl -sI http://localhost:8080/apps/nldesign/img/icons/Star.svg`
        → 200 (adjust to a name present in `scripts/icon-aliases.json`);
      - one removed unaliased legacy name → 404;
      - `curl -sI http://localhost:8080/apps/nldesign/img/logos/amsterdam.svg` → 200 (logos
        untouched).
- [ ] 6.4 Browser check on 8080 (browser-1): open a launchpad tile (or any page) whose icon URL
      is an aliased legacy path and confirm the replacement artwork renders (no broken image).
