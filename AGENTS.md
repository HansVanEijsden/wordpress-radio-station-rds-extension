# AGENTS.md

Guidance for AI coding agents working in this repository.

## Project Overview

WordPress plugin that extends the [Radio Station plugin](https://wordpress.org/plugins/radio-station/) with FM-RDS-PTY and FM-RDS-PTYN taxonomies and merges them into the `metadata/v1/current` REST endpoint output. Single-file plugin: all code lives in `radio-station-rds-extension.php`.

Requirements: WordPress 6.5+, PHP 8.1+, Radio Station plugin active. See [README.md](./README.md) for user-facing usage docs.

## Commands

```bash
# PHP syntax lint (matches the GitHub Actions workflow in .github/workflows/php-lint.yml)
find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l
```

There is no test suite, no `composer.json`, and no composer install step. Validate changes with `php -l`.

## Architecture (single file, in order)

1. **Plugin header + constants** — `RSRDS_VERSION`, `RSRDS_PLUGIN_DIR`, `RSRDS_PLUGIN_URL`, `RSRDS_PLUGIN_BASENAME`, `RSRDS_METADATA_CACHE_KEY`
2. **Single source of truth for terms** — `rsrds_get_pty_terms()` (term name → PTY code) and `rsrds_get_ptyn_terms()` (plain name list). **Everything else derives from these two functions**; never hardcode term names/codes elsewhere.
3. **Taxonomy registration** — `fm_rds_pty` and `fm_rds_ptyn`, applied to post types `show` and `override`, each with a custom dropdown meta box (`rsrds_pty_meta_box`, `rsrds_ptyn_meta_box`)
4. **Save handling** — `rsrds_save_single_term` enforces single-term selection (nonce + capability + allowlist checks) and clears the term when an empty value is explicitly submitted
5. **REST endpoint** — an anonymous `rest_api_init` callback registers `GET /wp-json/metadata/v1/current` (callback `rsrds_rest_current`, schema `rsrds_rest_current_schema`), calls Radio Station's `/wp-json/radio/broadcast`, merges PTY/PTYN, converts times to RFC3339, applies temporary overrides, and caches the merged payload
6. **Activation** — `rsrds_activate` seeds the default terms via `register_activation_hook`

## ⚠️ Single source of truth for terms

PTY/PTYN term names and their numeric PTY codes are defined **only** in `rsrds_get_pty_terms()` and `rsrds_get_ptyn_terms()`. The meta box dropdowns (`array_keys()` / plain list), save handler allowlists, activation seeds, and the REST PTY output all derive from these functions. To add/remove/rename a term, edit only these two functions.

Note the meta boxes use the term **name** (e.g. `'Pop'`) but the REST endpoint outputs the mapped numeric code (e.g. `'10'`), taken from `rsrds_get_pty_terms()`.

## Conventions

- **Prefixes** — All functions start with `rsrds_`, all constants with `RSRDS_`.
- **Coding style** — WordPress Coding Standards: snake_case, Yoda-style optional, hooks via `add_action` / `add_filter` / `register_activation_hook`, `ABSPATH` guard at top of file.
- **i18n** — Every user-facing string is translated with the text domain `radio-station-rds-extension` (`__()`, `_x()`, `esc_html_e()`). Never add untranslated UI strings.
- **Security pattern** — Meta box writes must follow the existing pattern: autosave/revision checks → nonce verify (`rsrds_taxonomy_nonce` / `rsrds_taxonomy_meta_box`) → `current_user_can` → `sanitize_text_field` → allowlist check.
- **Escaping** — Output must be escaped (`esc_html`, `esc_attr`, `esc_url`).
- **Post types** — Only `show` and `override` are relevant; taxonomies are `fm_rds_pty` (single value, numeric code output) and `fm_rds_ptyn` (single value, text output).

## Gotchas

- **VS Code "Undefined function" errors for WP core functions are false positives.** This project has no WordPress stubs and no `.vscode/` config, so the PHP language server flags every WordPress core function (`add_action`, `__()`, `esc_html()`, `wp_get_post_terms()`, `register_rest_route`, ...) as undefined. These are runtime-defined by WordPress and are NOT bugs — `php -l` is the source of truth. Do NOT "fix" them by defining local stubs in the plugin, wrapping calls in `function_exists()`, or adding `@function` PHPDoc.
- `rsrds_rest_current` depends on the Radio Station plugin's `/wp-json/radio/broadcast` endpoint being available; it returns `status: off-air` / `status: error` gracefully when not.
- The merged payload is cached in a transient (`RSRDS_METADATA_CACHE_KEY`, 30s TTL); only successful payloads are cached, and the cache is cleared on `save_post` for `show`/`override` posts (`rsrds_clear_metadata_cache`). When testing, remember the cache may be stale for up to 30s.
- Date handling uses `wp_timezone()` and `DateTime::RFC3339` for `start`, `end`, and `expiry` fields. Temporary overrides are read from `temporary_override` post meta; the override block is guarded so missing `start`/`end` don't cause PHP errors.
- Term values are retrieved with `wp_get_post_terms(..., ['fields' => 'names'])` — the first element is used since selection is single-term.
- The REST route has a declared `schema` (`rsrds_rest_current_schema`) — keep `status` enum values (`on-air`/`off-air`/`error`) in sync if you change the response shapes.
- In `rsrds_save_single_term`, an explicitly submitted empty value clears the term; a missing key (e.g. posts saved outside the editor) is left untouched. Save logic guards `is_string()` on the submitted field to avoid array-to-string warnings.
