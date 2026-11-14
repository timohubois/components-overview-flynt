=== Components Overview for Flynt ===
Contributors: timohubois
Tags: flynt, components
Requires at least: 6.7
Tested up to: 7.1
Stable tag: 2.3.1
Requires PHP: 8.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Find the pages and posts that use your Flynt components, with an admin overview, post-type filters and search.

== Description ==

Components Overview for Flynt shows which pages and posts use each component in your Flynt theme.

Open **Components Overview for Flynt** in the WordPress admin to browse the ACF Flexible Content layouts found in your content. Select a component to see matching posts, then follow an edit or view link to inspect them.

**Features**

* Browse used components by their layout label and internal name.
* Filter the overview and matching content by post type.
* With WPML, use the admin language selector to filter component usage.
* Search components by label or name, and search within matching posts.
* Open matching posts for editing or viewing, with post type and date shown in the list.
* Adjust the number of items per page through Screen Options.
* Refresh the cached layout list after content or field changes.

**Requirements and scope**

The plugin needs an active [Flynt-based theme](https://github.com/flyntwp/flynt) and **Advanced Custom Fields PRO** for Flexible Content fields. Neither is included. ACF PRO is a separate paid dependency; the free ACF plugin alone does not provide Flexible Content.

The overview follows Flynt's field-group and stored-layout conventions. It is not a general component index for arbitrary ACF setups, the block editor or template code.

== Installation ==

1. Make sure WordPress 6.7 or later and PHP 8.3 or later are available, your Flynt-based theme is active, and ACF PRO is installed and active.
2. Under **Plugins > Add New**, search for **Components Overview for Flynt**, install it and activate it. Alternatively, upload the components-overview-flynt folder to /wp-content/plugins/ and activate it under **Plugins**.
3. Sign in with an account that has the `manage_options` capability (administrators by default) and open **Components Overview for Flynt** in the admin menu. Normal WordPress editing permissions apply to linked posts.
4. Select a component to find matching content. Use the post-type links and search to narrow the results.

If the admin page is missing, check for a notice about the active theme or ACF dependency.

== Frequently Asked Questions ==

= Does this work with any WordPress theme or ACF field setup? =

No. The active theme must expose Flynt's `ComponentManager` class. The plugin reads top-level ACF Flexible Content fields in named field groups and looks up saved layout names using Flynt's field-group storage conventions.

It derives post-type filters from public post types and the `post_type` conditions in ACF field-group location rules; other location conditions are not fully evaluated.

Custom storage, nested Flexible Content fields, options pages and non-post locations are outside its coverage. It does not scan Twig/PHP templates, shortcodes or block-editor content for component references.

= Why is a component missing, or why does the overview look out of date? =

Only layouts with matching saved content are listed. Component lists are cached for up to one week and are not automatically refreshed after content edits.

After changing component usage or field definitions, click **Refresh components** below the overview heading to refresh the lists for all post types and languages.

Matching posts are queried when you open a component, rather than stored in the component-list cache.

If a layout still does not appear, check its field-group registration, storage conventions and post-type location rules.

= What if the component list does not load? =

Loading requires JavaScript and access to the WordPress REST API. If loading fails, reload the page. If the view has expired, reopen **Components Overview for Flynt** from the admin menu.

= Does the overview find every use of a component? =

No. It finds saved layouts in the Flynt fields described above, not references in templates, shortcodes or block-editor content. Results also depend on the selected post type and language.

A component's absence from the list is not proof that its code can be deleted. Check other references and refresh the cached list before removing code.

The plugin does not delete components or modify post content.

== Screenshots ==

1. Browse used components by their label and internal name, filter by post type, search, and select View where used.
2. Find the pages using a selected component, with post type, date, and edit or view links.

== Want to contribute? ==

Report issues or contribute on [GitHub](https://github.com/timohubois/components-overview-flynt/). The repository README covers development setup.

== Changelog ==

= 2.3.1 =
* Add a subtle pulse to component-list and filter loading placeholders, respecting reduced-motion preferences.

= 2.3.0 =
* Show cached component lists immediately and load uncached lists and post-type filters after the page opens.
* Keep filters, search and pagination available through normal WordPress navigation.
* Improve component labels, loading feedback, mobile table behavior and right-to-left alignment.
* Restrict component usage queries to field groups that register the component.
* Require WordPress 6.7 or later and PHP 8.3 or later.

= 2.2.5 =
* Strengthen admin access and validation of listing parameters and registered layout names.
* Protect listing links and search/filter forms with WordPress security nonces.
* Preserve selected filters, including language, when moving between lists or refreshing components.
* Keep cached component lists separate for different filtered views, and refresh them together.
* Fix pagination after filtering and keep matching-post results in a consistent order.
* Avoid showing unrelated posts when a component's field groups are no longer registered.

See [earlier releases](https://github.com/timohubois/components-overview-flynt/releases) for older changes.

== Upgrade Notice ==

= 2.3.0 =
Requires WordPress 6.7 or later and PHP 8.3 or later. JavaScript and WordPress REST API access are required to load uncached component lists and post-type filters.

= 2.2.5 =
Security and reliability fixes. If a saved listing link is rejected or has expired, reopen the plugin from the WordPress admin menu.
