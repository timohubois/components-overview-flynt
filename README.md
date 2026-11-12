# Components Overview for Flynt

Components Overview for Flynt shows where components of the [Flynt WordPress theme](https://github.com/flyntwp/flynt) are used in ACF flexible content fields. Open a component layout to see the posts that use it.

## Requirements

- WordPress 6.5 or later.
- PHP 8.0 or later.
- An active Flynt-based theme with ACF flexible content fields and the ACF functionality required by that theme.
- An account with the `manage_options` capability to access the overview.

## Installation

1. Clone this repository into `wp-content/plugins/components-overview-flynt/`.
2. Activate **Components Overview for Flynt** under **Plugins**.
3. Open **Components Overview for Flynt** in the WordPress admin menu.

Composer is not required to run the plugin.

## Usage

Use the post-type filters and search to find a layout, then open it to see matching posts. After changing layouts or content, use **Refresh cached Layouts** if the overview is out of date. The overview reads registered ACF flexible content layouts and saved post data; layout lists are cached for up to one week.

Both admin screens follow normal WordPress query filters, including language filters provided by other plugins. Cached layout lists are kept separate for the resulting query context.

Listing URLs use WordPress security nonces and can expire. If an old or saved link is rejected, reopen the plugin from the WordPress admin menu and select the component again.

## Development

Use PHP 8.3 for development and CI. The released plugin supports PHP 8.0; keep plugin code compatible with that version. Composer development dependencies are not included in the release.

After installation, ensure `php --version` reports PHP 8.3 and Composer uses that PHP installation, then run:

```sh
composer install
composer check-platform-reqs
composer php:lint
```

## License

GPLv3 or later. See [LICENSE](LICENSE).
