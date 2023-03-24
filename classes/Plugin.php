<?php

namespace FlyntComponentsOverview;

use FlyntComponentsOverview\AdminMenu;

defined('ABSPATH') || exit;

class Plugin
{
    public static function init(): void
    {
        if (class_exists('Flynt\\ComponentManager')) {
            AdminMenu::init();
        } else {
            add_action('admin_notices', [__CLASS__, 'showAdminNoticeThemeNotFound']);
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

    public static function getPluginRootDir(): string
    {
        return plugin_dir_path(FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE);
    }
}
