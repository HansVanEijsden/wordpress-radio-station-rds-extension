# Radio Station – RDS Extension

[![PHP Lint](https://github.com/HansVanEijsden/wordpress-radio-station-rds-extension/actions/workflows/php-lint.yml/badge.svg)](https://github.com/HansVanEijsden/wordpress-radio-station-rds-extension/actions/workflows/php-lint.yml)
[![License: GPL v2 or later](https://img.shields.io/badge/License-GPL--2.0--or--later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.5+-blue)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue)](https://php.net/)
[![Requires](https://img.shields.io/badge/Requires-Radio%20Station%20plugin-orange)](https://wordpress.org/plugins/radio-station/)

Extends the [Radio Station](https://wordpress.org/plugins/radio-station/) WordPress plugin with **FM-RDS-PTY** and **FM-RDS-PTYN** taxonomies and exposes them through a dedicated REST endpoint (`/wp-json/metadata/v1/current`) built for RDS encoders and broadcast metadata middleware.

> **Use case:** feed your FM transmitter's RDS encoder with a machine-readable, always-current PTY code and PTYN name for the show that is on-air right now — including temporary overrides.

## Features

- **FM-RDS-PTY taxonomy** — assign a single Programme Type to every show and override. The REST endpoint outputs the **numeric PTY code** (e.g. `10` for *Pop*).
- **FM-RDS-PTYN taxonomy** — assign a single Programme Type Name to every show and override. The REST endpoint outputs the **free-text name** (e.g. `Hot AC`).
- **Dedicated REST endpoint** — `GET /wp-json/metadata/v1/current` merges Radio Station's broadcast payload with PTY/PTYN data in one machine-readable response.
- **Temporary overrides** — honours Radio Station's `temporary_override` post meta and applies the override's PTY/PTYN values while the override is active.
- **RFC 3339 timestamps** — `start`, `end` and `expiry` are converted to ISO 8601 / RFC 3339 in your site's timezone.
- **Smart caching** — successful responses are cached for 30 seconds and invalidated on save, so RDS encoders can poll cheaply.
- **Fully translatable** — every UI string ships with the `radio-station-rds-extension` text domain.
- **WordPress coding standards** — prefixed functions/constants, sanitized input, escaped output, nonce + capability checks.

## Requirements

- WordPress **6.5 or higher**
- PHP **8.1 or higher**
- The [Radio Station](https://wordpress.org/plugins/radio-station/) plugin (**active**)

## Installation

1. Upload the plugin folder to `/wp-content/plugins/radio-station-rds-extension`, or install it from the WordPress admin (**Plugins → Add New → Upload Plugin**).
2. Activate **Radio Station – RDS Extension** from the *Plugins* screen.
3. On activation, the plugin automatically creates the default FM-RDS-PTY and FM-RDS-PTYN terms (see [Configuration](#configuration)).

## Configuration

### Assign PTY / PTYN to a show or override

1. Open a **Show** or **Override** in the editor.
2. Use the **FM-RDS-PTY** and **FM-RDS-PTYN** single-select dropdowns in the sidebar.
3. Publish / Update — the selection is saved and immediately reflected in the REST endpoint.

### PTY code mapping (single source of truth)

PTY codes are the numeric Programme Type codes used by RDS encoders. The term name → code mapping is defined **once** in `rsrds_get_pty_terms()` and used everywhere (meta boxes, validation, REST output):

| Term             | PTY code |
| ---------------- | :------: |
| Nieuws           | 1        |
| Actualiteit      | 2        |
| Sport            | 4        |
| Cultuur          | 7        |
| Pop              | 10       |
| Rock             | 11       |
| Ontspanning      | 12       |
| Nationale muziek | 26       |
| Volksmuziek      | 28       |
| Gouwe Ouwe       | 27       |
| Overige muziek   | 15       |

The available **PTYN** names are: `Hot AC`, `Politiek`, `Voetbal`, `Blues`, `Ballads`, `NL-Talig`, `Old Hits`, `SportMix`, `Dance`, `Variatie`, `Human`.

> To add, remove or re-map a term, edit `rsrds_get_pty_terms()` / `rsrds_get_ptyn_terms()` — every other part of the plugin derives from these two functions.

## REST API

The plugin registers a single endpoint:

```
GET /wp-json/metadata/v1/current
```

It calls Radio Station's `/wp-json/radio/broadcast` endpoint and returns an enriched payload:

| Field                        | Type     | Description                                            |
| ---------------------------- | -------- | ------------------------------------------------------ |
| `status`                     | `string` | `on-air`, `off-air` or `error`                         |
| `broadcast.current_show`     | `object` | Currently on-air show, enriched with RDS metadata      |
| `broadcast.next_show`        | `object` | Next scheduled show, enriched with RDS metadata        |
| `fm_rds_pty`                 | `string` | Numeric PTY code (e.g. `"10"`); empty string when unset |
| `fm_rds_ptyn`                | `string` | PTYN name (e.g. `"Hot AC"`); empty string when unset   |
| `start` / `end` / `expiry`   | `string` | RFC 3339 timestamps in the site's timezone             |

### Example response

```json
{
  "status": "on-air",
  "broadcast": {
    "current_show": {
      "show": { "id": 42 },
      "show_name": "The Morning Show",
      "hosts": "Jane Doe",
      "start": "2026-08-01T07:00:00+02:00",
      "end": "2026-08-01T10:00:00+02:00",
      "expiry": "2026-08-01T10:00:00+02:00",
      "fm_rds_pty": "10",
      "fm_rds_ptyn": "Hot AC"
    },
    "next_show": {
      "show": { "id": 43 },
      "show_name": "The Drive Home",
      "hosts": "John Smith",
      "start": "2026-08-01T10:00:00+02:00",
      "end": "2026-08-01T13:00:00+02:00",
      "fm_rds_pty": "11",
      "fm_rds_ptyn": "Rock"
    }
  }
}
```

> **Behaviour notes:** when no show is scheduled, the endpoint returns `{ "status": "off-air" }`. When Radio Station's broadcast endpoint cannot be reached, it returns `{ "status": "error", "message": "…" }`. Responses are cached for 30 seconds and the cache is cleared automatically whenever a show or override is saved.

## Development

The plugin is intentionally a single file: `radio-station-rds-extension.php`.

```bash
# PHP syntax check (matches the CI workflow in .github/workflows/php-lint.yml)
find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l
```

Conventions:

- All functions are prefixed `rsrds_`, all constants `RSRDS_`.
- PTY/PTYN term names and codes are defined **only** in `rsrds_get_pty_terms()` and `rsrds_get_ptyn_terms()` — never hardcode them elsewhere.
- Every user-facing string is translatable via the `radio-station-rds-extension` text domain.
- See [AGENTS.md](./AGENTS.md) for the full architecture and coding guidelines.

## Changelog

### 1.0.1
- Centralised PTY/PTYN term definitions (single source of truth).
- Enforced single-term selection in the save handler, with allowlist validation.
- Improved REST response caching and timezone-safe RFC 3339 output.

### 1.0.0
- Initial release.

## Contributing

Contributions are welcome! Please:

1. Fork the repository and create a feature branch.
2. Keep the plugin a single file and follow the [conventions](#development) above.
3. Run the PHP lint command before opening a pull request.

Bug reports and feature requests are tracked in [GitHub Issues](https://github.com/HansVanEijsden/wordpress-radio-station-rds-extension/issues).

## License

This plugin is licensed under the **GNU General Public License v2.0 or later** — see [LICENSE](./LICENSE) for the full license text.

---

*Developed by [Hans van Eijsden](https://www.hansvaneijsden.com). Not affiliated with the Radio Station plugin or its authors.*
