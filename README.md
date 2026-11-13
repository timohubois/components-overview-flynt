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

When the component list is not cached, it loads after the page opens. Post-type filters may also take a moment to appear.

Loading requires JavaScript and access to the WordPress REST API. If it fails, reload the page. If the view has expired, reopen the plugin from the admin menu.

Language filtering has been tested with WPML, including individual languages and All languages. The plugin was also tested without a multilingual plugin; compatibility with other multilingual plugins has not been verified.

## Development

Use PHP 8.3 for development and CI, matching the plugin's minimum supported version. Composer development dependencies are not included in the release.

Admin JavaScript is an unbundled native ES module loaded through WordPress's Script Modules API. It imports `@wordpress/a11y` and reads configuration from WordPress's module-data JSON, without a global configuration object. JavaScript and CSS are enqueued only on the plugin's admin screens, with file modification times for cache busting. No JavaScript build step is required.

After installation, ensure `php --version` reports PHP 8.3 and Composer uses that PHP installation, then run:

```sh
composer install
composer check-platform-reqs
composer php:lint
```

### REST integration checks

With the plugin, Flynt and ACF active in a **disposable local** WordPress installation, run from this repository:

```sh
wp --path=/path/to/disposable-wordpress eval-file tests/rest-api.php
```

The checks create and remove temporary pages and a subscriber, and invalidate the layout cache. They cover cold and cached overview rendering without initial usage queries, server-rendered matching posts, separately loaded filter links and counts, hidden zero-count post types, duplicate layout labels, registered field-group ownership, admin post-status visibility, pagination, permissions, nonces and request-context cleanup.

## License

GPLv3 or later. See [LICENSE](LICENSE).
