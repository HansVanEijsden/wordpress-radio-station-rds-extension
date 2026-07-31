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

There is no test suite and no composer install step (dependabot tracks a hypothetical composer ecosystem). Validate changes with `php -l`.

## Architecture (single file, in order)

1. **Plugin header + constants** — `RSRDS_VERSION`, `RSRDS_PLUGIN_DIR`, `RSRDS_PLUGIN_URL`, `RSRDS_PLUGIN_BASENAME`
2. **Taxonomy registration** — `fm_rds_pty` and `fm_rds_ptyn`, applied to post types `show` and `override`, each with a custom dropdown meta box (`rsrds_pty_meta_box`, `rsrds_ptyn_meta_box`)
3. **Save handling** — `rsrds_save_single_term` enforces single-term selection (nonce + capability + allowlist checks)
4. **REST endpoint** — `rsrds_rest_current` registers `GET /wp-json/metadata/v1/current`, calls Radio Station's `/wp-json/radio/broadcast`, merges PTY/PTYN, converts times to RFC3339, and applies temporary overrides
5. **Activation** — `rsrds_activate` seeds the default terms via `register_activation_hook`

## ⚠️ Critical: Keep the 4 term lists in sync

PTY/PTYN term names and their PTY numeric codes are **duplicated in 4 places** in `radio-station-rds-extension.php`. Any change to terms/codes **must** update all of them together, or the plugin breaks silently:

1. **Meta box hardcoded dropdowns** — `$terms` arrays in `rsrds_pty_meta_box` / `rsrds_ptyn_meta_box`
2. **Save handler allowlists** — `$allowed_pty` / `$allowed_ptyn` in `rsrds_save_single_term`
3. **Activation seed terms** — `$default_pty_terms` / `$default_ptyn_terms` in `rsrds_activate`
4. **PTY code map** — `$pty_map` in `rsrds_rest_current` (term name → numeric PTY code)

Adding/removing/renaming a term means touching all 4 spots. Note the meta boxes use the term **name** (e.g. `'Pop'`) but the REST endpoint outputs the mapped numeric code (e.g. `'10'`).

## Conventions

- **Prefixes** — All functions start with `rsrds_`, all constants with `RSRDS_`.
- **Coding style** — WordPress Coding Standards: snake_case, Yoda-style optional, hooks via `add_action` / `add_filter` / `register_activation_hook`, `ABSPATH` guard at top of file.
- **i18n** — Every user-facing string is translated with the text domain `radio-station-rds-extension` (`__()`, `_x()`, `esc_html_e()`). Never add untranslated UI strings.
- **Security pattern** — Meta box writes must follow the existing pattern: autosave/revision checks → nonce verify (`rsrds_taxonomy_nonce` / `rsrds_taxonomy_meta_box`) → `current_user_can` → `sanitize_text_field` → allowlist check.
- **Escaping** — Output must be escaped (`esc_html`, `esc_attr`, `esc_url`).
- **Post types** — Only `show` and `override` are relevant; taxonomies are `fm_rds_pty` (single value, numeric code output) and `fm_rds_ptyn` (single value, text output).

## Gotchas

- `rsrds_rest_current` depends on the Radio Station plugin's `/wp-json/radio/broadcast` endpoint being available; it returns `status: off-air` / `status: error` gracefully when not.
- Date handling uses `wp_timezone()` and `DateTime::RFC3339` for `start`, `end`, and `expiry` fields. Temporary overrides are read from `temporary_override` post meta.
- Term values are retrieved with `wp_get_post_terms(..., ['fields' => 'names'])` — the first element is used since selection is single-term.
