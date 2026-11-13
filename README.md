# Components Overview for Flynt

Components Overview for Flynt shows where components of the [Flynt WordPress theme](https://github.com/flyntwp/flynt) are used in ACF flexible content fields. Open a component layout to see the posts that use it.

## Requirements

- WordPress 6.7 or later.
- PHP 8.3 or later.
- An active Flynt-based theme with ACF flexible content fields and the ACF functionality required by that theme.
- An account with the `manage_options` capability to access the overview.

## Installation

1. Clone this repository into `wp-content/plugins/components-overview-flynt/`.
2. Activate **Components Overview for Flynt** under **Plugins**.
3. Open **Components Overview for Flynt** in the WordPress admin menu.

Composer is not required to run the plugin.

## Usage

Use the post-type filters and search to find a layout, then open it to see matching posts. After changing layouts or content, use **Refresh components** if the overview is out of date. The overview reads registered ACF flexible content layouts and saved post data; layout lists are cached for up to one week.

Language filtering has been tested with WPML, including individual languages and All languages. The plugin was also tested without a multilingual plugin; compatibility with other multilingual plugins has not been verified.

## Development

Use PHP 8.3 for development and CI, matching the plugin's minimum supported version. Composer development dependencies are not included in the release.

After installation, ensure `php --version` reports PHP 8.3 and Composer uses that PHP installation, then run:

```sh
composer install
composer check-platform-reqs
composer php:lint
```

## License

GPLv3 or later. See [LICENSE](LICENSE).
