<?php

namespace FlyntComponentsOverview;

use FlyntComponentsOverview\AdminMenu;

defined('ABSPATH') || exit;

class Plugin
{
    public static function init(): void
    {
        if (!class_exists('Flynt\\ComponentManager')) {
            add_action('admin_notices', [self::class, 'showAdminNoticeThemeNotFound']);
            return;
        }

        AdminMenu::init();
    }

    public static function showAdminNoticeThemeNotFound(): void
    {
        $title = __('404 – Theme not found', 'components-overview-flynt');
        $message = sprintf(
            // translators: 1: <a> element 2: </a> element
            __('The “Components Overview for Flynt” plugin requires a active %1$sFlynt%2$s based theme to work.', 'components-overview-flynt'),
            "<a href='https://flyntwp.com/' target='_blank' rel='noopener noreferrer'>",
            "</a>"
        );

        echo '<div class="notice notice-warning"><p><strong>' . esc_html($title) . '</strong></p><p>' . wp_kses_post($message) . '</p></div>';
    }

    public static function savePost($postId): void
    {
        if (wp_is_post_revision($postId)) {
            return;
        }

        add_option(CronJob::OPTION_NAME_CRONJOB_RUN_ASAP, true);
    }

    public static function getPluginRootDir(): string
    {
        return plugin_dir_path(FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE);
    }
}
