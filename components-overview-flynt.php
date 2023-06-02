<?php

/**
 * Plugin Name:       Components Overview for Flynt
 * Plugin URI:        https://github.com/timohubois/components-overview-flynt/
 * Description:       Get an overview of where components of the Flynt theme are currently used in flexible content acf fields.
 * Version:           1.2.0
 * Requires at least: 5.0
 * Requires PHP:      8.0
 * Author:            Timo Hubois
 * Author URI:        https://pixelsaft.de
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

// Basic Auth Fallback for WP Cron.
if (!empty($_SERVER['PHP_AUTH_USER']) && !empty($_SERVER['PHP_AUTH_PW'])) {
    if (!defined('WP_CRON_CUSTOM_HTTP_BASIC_USERNAME')) {
        $username = sanitize_text_field(wp_unslash($_SERVER['PHP_AUTH_USER'])) ?? '';
        define('WP_CRON_CUSTOM_HTTP_BASIC_USERNAME', $username);
    }
    if (!defined('WP_CRON_CUSTOM_HTTP_BASIC_PASSWORD')) {
        $password = sanitize_text_field(wp_unslash($_SERVER['PHP_AUTH_PW'])) ?? '';
        define('WP_CRON_CUSTOM_HTTP_BASIC_PASSWORD', $password);
    }

    add_filter('cron_request', function (array $cronRequest) {
        $headers = [
            'Authorization' => sprintf(
                'Basic %s',
                base64_encode(WP_CRON_CUSTOM_HTTP_BASIC_USERNAME . ':' . WP_CRON_CUSTOM_HTTP_BASIC_PASSWORD)
            )
        ];
        $cronRequest['args']['headers'] = isset($cronRequest['args']['headers'])
            ? array_merge($cronRequest['args']['headers'], $headers)
            : $headers;
        return $cronRequest;
    }, PHP_INT_MAX);
}
