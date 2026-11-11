# Components Overview for Flynt

Components Overview for Flynt is a plugin to get an overview of where components of the [Flynt WordPress theme](https://github.com/flyntwp/flynt) are currently used in acf flexible content fields.

## Requirements

- PHP >= 8.0

## Installation

1. Clone the repository and place it in `wp-content/plugins/` folder.
2. Make sure you have the correct [requirements](#requirements).

## Development

Use PHP 8.3 for development and CI. The released plugin still supports PHP 8.0;
Composer development dependencies are not included in the release.

1. Perform [Installation](#installation).
2. Ensure `php --version` reports PHP 8.3 and Composer uses that PHP installation.
3. Run `composer install` to install development dependencies.
4. Run `composer check-platform-reqs` and `composer php:lint`.

Keep plugin code compatible with PHP 8.0 when using newer development tools.

## WordPress.org listing artwork

Listing assets live in `.wordpress-org/` and are excluded from plugin ZIPs. The selected Blueprint Finder banners and icon use lossless PNG, alongside two genuine interface screenshots. File sizes and dimensions follow the [WordPress.org asset requirements](https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/).

## License

GPLv3
