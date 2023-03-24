<?php

/**
 * Plugin Name:       Flynt Components Overview
 * Plugin URI:        https://github.com/flynt-components-overview/
 * Description:       Get an overview of where components of the Flynt theme are currently used in flexible content acf fields.
 * Version:           1.0.0
 * Requires at least: 5.0
 * Requires PHP:      7.4
 * Author:            Timo Hubois
 * Author URI:        https://pixelsaft.de
 * Text Domain:       flynt-components-overview
 * Domain Path:       /languages
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

 namespace FlyntComponentsOverview;

 defined('ABSPATH') || exit;

if (! defined('FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE')) {
    define('FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE', __FILE__);
}

if (file_exists(plugin_dir_path(FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE) . 'vendor/autoload.php')) {
    require plugin_dir_path(FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE) . 'vendor/autoload.php';
}

add_action('after_setup_theme', function (): void {
    Plugin::init();
});
