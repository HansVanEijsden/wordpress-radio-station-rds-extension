# Radio Station – RDS Extension

[![PHP Code Sniffer](https://github.com/HansVanEijsden/radio-station-rds-extension/actions/workflows/phpcs.yml/badge.svg)](https://github.com/HansVanEijsden/radio-station-rds-extension/actions/workflows/phpcs.yml)
[![PHPStan](https://github.com/HansVanEijsden/radio-station-rds-extension/actions/workflows/phpstan.yml/badge.svg)](https://github.com/HansVanEijsden/radio-station-rds-extension/actions/workflows/phpstan.yml)
[![CodeQL](https://github.com/HansVanEijsden/radio-station-rds-extension/actions/workflows/codeql.yml/badge.svg)](https://github.com/HansVanEijsden/radio-station-rds-extension/actions/workflows/codeql.yml)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress](https://img.shields.io/badge/WordPress-6.5+-blue)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.1+-blue)](https://php.net/)

Extends the [Radio Station WordPress plugin](https://wordpress.org/plugins/radio-station/) with FM-RDS-PTY and FM-RDS-PTYN taxonomies and adds them to the metadata middleware output.

## Features

- Adds FM-RDS-PTY and FM-RDS-PTYN taxonomies to shows and overrides
- Integrates with Radio Station's REST API metadata endpoint
- Supports temporary overrides
- Configurable PTY code mapping via term meta
- Fully translatable
- WordPress coding standards compliant

## Requirements

- WordPress 6.5 or higher
- PHP 8.1 or higher
- Radio Station WordPress plugin

## Installation

1. Upload the plugin files to `/wp-content/plugins/radio-station-rds-extension`, or install through WordPress admin.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. The plugin will automatically create the necessary taxonomies.

## Usage

### Adding PTY/PTYN to Shows

1. Edit a show or override
2. Look for the "FM-RDS-PTY" and "FM-RDS-PTYN" meta boxes
3. Select the appropriate term from the dropdown

### Configuring PTY Codes

The PTY code mapping can be configured per term:

1. Go to Posts > FM-RDS-PTY
2. Edit any term
3. Add the PTY code in the "RSRDS PTY Code" field

### REST API

The plugin extends the Radio Station's broadcast endpoint with additional fields:

- `fm_rds_pty`: The numeric PTY code
- `fm_rds_ptyn`: The PTYN text

Example endpoint: `/wp-json/metadata/v1/current`

## Development

### Local Development

```bash
# Clone repository
git clone https://github.com/HansVanEijsden/radio-station-rds-extension.git

# Install development dependencies
composer install

# Run PHPCS
vendor/bin/phpcs --standard=WordPress .

# Run PHPStan
vendor/bin/phpstan analyse
