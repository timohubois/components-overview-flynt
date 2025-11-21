=== Components Overview for Flynt ===
Contributors: timohubois
Tags: flynt, components
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 2.2.1
Requires PHP: 8.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Get an overview of where components of the Flynt theme currently used.

== Description ==

Components Overview for Flynt creates an admin menu page with a list table to get an overview of where components inside acf flexible content are currently used.

**Please note:** The active theme of the website must use the [Flynt Theme](https://flyntwp.com).

== Want to contribute? ==
Check out the Plugin [GitHub Repository](https://github.com/timohubois/components-overview-flynt).

== Installation ==

= INSTALL WITHIN WORDPRESS =
(recommended)

1. Open **Plugins > Add new**
2. Search for **Components Overview for Flynt**
3. Click **install and activate** the plugin

= INSTALL MANUALLY THROUGH FTP =

1. Download the plugin on the WordPress plugin page
2. Upload the ‘components-overview-flynt’ folder to the /wp-content/plugins/ directory
3. Activate the plugin through the ‘Plugins’ menu in WordPress

== Changelog ==
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
