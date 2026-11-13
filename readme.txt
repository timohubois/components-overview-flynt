=== Components Overview for Flynt ===
Contributors: timohubois
Tags: flynt, components
Requires at least: 6.7
Tested up to: 7.1
Stable tag: 2.2.5
Requires PHP: 8.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Find the pages and posts that use your Flynt components, with an admin overview, post-type filters and search.

== Description ==

Need to update a Flynt component and find the content that uses it? Components Overview for Flynt gives administrators a starting point without opening pages one by one.

Open **Components Overview for Flynt** in the WordPress admin to browse the ACF Flexible Content layouts found in your content. Select a component to see matching posts, then follow an edit or view link to inspect them.

**Features**

* Browse used components by their layout label and internal name.
* Filter the overview and matching content by post type.
* Search components by label or name, and search within matching posts.
* Open matching posts for editing or viewing, with post type and date shown in the list.
* Adjust the number of items per page through Screen Options.
* Refresh the cached layout list after content or field changes.

**Requirements and scope**

This plugin needs an active [Flynt-based theme](https://github.com/flyntwp/flynt) and **Advanced Custom Fields PRO** for Flexible Content fields. Neither is included. ACF PRO is a separate paid dependency; the free ACF plugin alone does not provide Flexible Content.

The overview follows Flynt's field-group and stored-layout conventions. It is not a general component index for arbitrary ACF setups, the block editor or template code. See the FAQs for coverage and caching limits.

== Installation ==

1. Make sure WordPress 6.7 or later and PHP 8.3 or later are available, your Flynt-based theme is active, and ACF PRO is installed and active.
2. Under **Plugins > Add New**, search for **Components Overview for Flynt**, install it and activate it. Alternatively, upload the components-overview-flynt folder to /wp-content/plugins/ and activate it under **Plugins**.
3. Sign in with an account that has the manage_options capability (administrators by default) and open **Components Overview for Flynt** in the admin menu.
4. Select a component to find matching content. Use the post-type links and search to narrow the results.

No Composer installation or separate service is required to use the released plugin. If the admin page is missing, check for a notice about the active theme or ACF dependency.

== Frequently Asked Questions ==

= Does this work with any WordPress theme or ACF field setup? =

No. The active theme must expose Flynt's ComponentManager class. The plugin reads top-level ACF Flexible Content fields in named field groups and looks up saved layout names using Flynt's field-group storage conventions.

It derives post-type filters from public post types and the post_type conditions in ACF field-group location rules; other location conditions are not fully evaluated. Custom storage, nested Flexible Content fields, options pages and non-post locations are outside its coverage. It does not scan Twig/PHP templates, shortcodes or block-editor content for component references.

= Why is a component missing, or why does the overview look out of date? =

Only layouts with matching saved content are listed. The layout list for each post-type filter is cached in WordPress transients for up to one week and is not automatically refreshed on each content edit.

After adding or removing component usage or changing field definitions, use **Refresh cached Layouts** at the top of the overview. The matching-post list is queried when opened rather than stored in that layout cache. If a layout still does not appear, check its field-group registration, storage conventions and post-type location rules.

= Has language filtering been tested? =

Language filtering has been tested with WPML, including individual languages and All languages, across both admin screens, navigation, search and cache refreshes.

WPML is not required; the plugin was also tested without a multilingual plugin. Compatibility with other multilingual plugins has not been verified.

= Can I use this to prove a component is unused and delete it? =

No. This is a navigation aid, not a complete dependency audit. Results depend on the supported field structure, cached layout list and WordPress query visibility. A component can also be referenced in templates or other places the plugin does not inspect.

Review those references and keep a backup before removing component code. The plugin does not delete components or modify post content.

= Who can see the overview, and where does the data go? =

The admin page requires the manage_options capability, normally held by administrators. Normal WordPress editing permissions still apply to linked posts. The overview is not a public front-end page.

The plugin queries your site's WordPress database and stores temporary layout lists as transients. It does not send component or post data to an external service.

== Screenshots ==

1. Browse used components by their label and internal name, filter by post type, search, and select View where used.
2. Find the pages using a selected component, with post type, date, and edit or view links.

== Want to contribute? ==

Visit the [GitHub repository](https://github.com/timohubois/components-overview-flynt/) to report issues or contribute. The GitHub README covers the development setup.

== Changelog ==
= Unreleased =
* Require WordPress 6.7 or later and PHP 8.3 or later.

= 2.2.5 =
* Strengthen admin access and validation of listing parameters and registered layout names.
* Protect listing links and search/filter forms with WordPress security nonces.
* Preserve the current view context, including language selections, across both admin screens and cache refreshes.
* Separate layout caches by filtered query context and refresh all contexts together.
* Recover out-of-range filtered pages and make matching-post ordering deterministic.
* Prevent unrelated results when no flexible-content field groups remain and avoid unnecessary sorting in count queries.
* Clarify plugin requirements, coverage, caching and admin-link behavior.

= 2.2.4 =
* Secure the layout cache refresh action with nonce validation.
* Improve admin input handling, escaping, and translation compatibility.

= 2.2.3 =
* Raise the minimum supported WordPress version to 6.5.

= 2.2.2 =
* Update the WordPress compatibility declaration to 7.1.

= 2.2.1 =
* Fix pagination buttons remaining enabled on last page by ensuring proper integer type casting

= 2.2.0 =
* Optimize database query performance by consolidating multiple queries into single OR meta_query
* Improve page load times when displaying posts with layouts

= 2.1.1 =
* Fix escaping in RenderAdminPage.php to use esc_attr for HTML attributes
* Streamline search query handling for improved readability

= 2.1.0 =
* Add search results subtitle to enhance user experience
* Improve search and pagination logic for layouts, preserving postType filter

= 2.0.5 =
* Add transient deletion for 'any' post type

= 2.0.4 =
* Enhance post type filtering logic for field groups

= 2.0.3 =
* Remove Requires Plugins

= 2.0.2 =
* Add Requires Plugins
* Remove unused code
* Tested up to: 6.7

= 2.0.1 =
* Prevent a php warning

= 2.0.0 =
* Drop Cronjob
* Tested with WordPress 6.5

= 1.0.1 =
* Fixed an issue in combination with screen_options

= 1.0 =
* Initial Release

== Upgrade Notice ==
= 2.2.5 =
Security and reliability fixes. If a saved listing link is rejected or has expired, reopen the plugin from the WordPress admin menu.
