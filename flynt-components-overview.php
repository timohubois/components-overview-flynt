<?php

/**
 * Plugin Name:       Flynt Components Overview
 * Plugin URI:        https://github.com/timohubois/flynt-components-overview/
 * Description:       Get an overview of where components of the Flynt theme are currently used in flexible content acf fields.
 * Version:           1.1.0
 * Requires at least: 5.0
 * Requires PHP:      8.0
 * Author:            Timo Hubois
 * Author URI:        https://pixelsaft.de
 * Text Domain:       flynt-components-overview
 * Domain Path:       /languages
 * License:           GPLv3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

namespace FlyntComponentsOverview;

defined('ABSPATH') || exit;

if (!defined('FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE')) {
    define('FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE', __FILE__);
}

if (file_exists(plugin_dir_path(FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE) . 'vendor/autoload.php')) {
    require plugin_dir_path(FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE) . 'vendor/autoload.php';
}

add_action('after_setup_theme', function (): void {
    if (is_admin()) {
        Plugin::init();
    }

    CronJob::init();
});

register_activation_hook(__FILE__, function (): void {
    if (is_multisite()) {
        foreach (get_sites(['fields' => 'ids']) as $blogId) {
            switch_to_blog($blogId);
            add_option(CronJob::OPTION_NAME_CRONJOB_RUN_ASAP, true);
            restore_current_blog();
        }
    } else {
        add_option(CronJob::OPTION_NAME_CRONJOB_RUN_ASAP, true);
    }
});

register_deactivation_hook(__FILE__, function (): void {
    if (is_multisite()) {
        foreach (get_sites(['fields' => 'ids']) as $blogId) {
            switch_to_blog($blogId);
            Cronjob::getInstance()->unregister();
            restore_current_blog();
        }
    } else {
        Cronjob::getInstance()->unregister();
    }
});
