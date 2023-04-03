<?php

namespace FlyntComponentsOverview;

use Flynt\ComponentManager;
use FlyntComponentsOverview\AdminMenu;

defined('ABSPATH') || exit;

class Plugin
{
    public const TRANSIENT_KEY_COMPONENTS = 'flynt_components_overview_components';
    public const TRANSIENT_KEY_COMPONENTS_POST_TYPES = 'flynt_components_overview_components_post_types';

    public static function init(): void
    {
        if (class_exists('Flynt\\ComponentManager')) {
            AdminMenu::init();
            add_action('save_post', [self::class, 'deleteTransients']);
        } else {
            add_action('admin_notices', [self::class, 'showAdminNoticeThemeNotFound']);
        }
    }

    public static function showAdminNoticeThemeNotFound(): void
    {
        $title = esc_html__('404 – Theme not found', 'flynt-components-overview');
        $message = sprintf(
            // translators: 1: <a> element 2: </a> element
            __('The “Flynt Components Overview” plugin requires a %1$sFlynt%2$s based theme to work.', 'flynt-components-overview'),
            "<a href='https://flyntwp.com/' target='_blank' rel='noopener noreferrer'>",
            "</a>"
        );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $message is escaped
        echo '<div class="notice notice-warning"><p><strong>' . $title . '</strong></p><p>' . $message . '</p></div>';
    }

    public static function deleteTransients()
    {
        delete_transient(self::TRANSIENT_KEY_COMPONENTS);
        delete_transient(self::TRANSIENT_KEY_COMPONENTS_POST_TYPES);
    }

    public static function getPluginRootDir(): string
    {
        return plugin_dir_path(FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE);
    }
}
