<?php

/**
 * Plugin Name:       Components Overview for Flynt
 * Plugin URI:        https://github.com/timohubois/components-overview-flynt/
 * Description:       Get an overview of where components of the Flynt theme are currently used in flexible content acf fields.
 * Version:           2.0.1
 * Requires at least: 5.0
 * Requires PHP:      8.0
 * Author:            Timo Hubois
 * Author URI:        https://pixelsaft.wtf
 * Text Domain:       components-overview-flynt
 * Domain Path:       /languages
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace FlyntComponentsOverview;

defined('ABSPATH') || exit;

if (!defined('FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE')) {
    define('FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE', __FILE__);
}

// Autoloader via Composer if available.
if (file_exists(plugin_dir_path(FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE) . 'vendor/autoload.php')) {
    require plugin_dir_path(FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE) . 'vendor/autoload.php';
}

// Custom autoloader if Composer is not available.
if (!file_exists(plugin_dir_path(FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE) . 'vendor/autoload.php')) {
    spl_autoload_register(static function ($className): void {
        $prefix = 'FlyntComponentsOverview\\';
        $baseDir = plugin_dir_path(FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE) . 'classes/';
        $length = strlen($prefix);
        if (strncmp($prefix, $className, $length) !== 0) {
            return;
        }

        $relativeClass = substr($className, $length);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

add_action('after_setup_theme', static function (): void {
    if (is_admin()) {
        Plugin::init();
    }
});
