<?php

namespace FlyntComponentsOverview;

use FlyntComponentsOverview\AdminMenu;

defined('ABSPATH') || exit;

final class Plugin
{
    public static function init(): void
    {
        if (!class_exists('Flynt\\ComponentManager')) {
            add_action('admin_notices', [self::class, 'adminNoticeThemeNotFound']);
            return;
        }

        if (!class_exists('acf')) {
            add_action('admin_notices', [self::class, 'adminNoticeAcfNotFound']);
            return;
        }

        AdminMenu::init();
    }

    public static function getPluginRootDir(): string
    {
        return plugin_dir_path(FLYNT_COMPONENTS_OVERVIEW_PLUGIN_FILE);
    }

    public static function adminNoticeThemeNotFound(): void
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

    public static function adminNoticeAcfNotFound(): void
    {
        $title = esc_html(__('Components Overview for Flynt is missing a required plugin', 'components-overview-flynt'));

        $pluginName = sprintf(
            // translators: %1$s, %2$s: link wrapper.
            __('%1$sAdvanced Custom Fields PRO%2$s', 'components-overview-flynt'),
            '<a href="' . esc_url(admin_url('plugins.php')) . '" target="_blank">',
            '</a>'
        );

        $pluginsUrl = sprintf(
            // translators: %1$s, %2$s: link wrapper.
            __('%1$splugin page%2$s', 'components-overview-flynt'),
            '<a href="' . esc_url(admin_url('plugins.php')) . '" target="_blank">',
            '</a>'
        );

        $message = sprintf(
            // translators: %1$s: Plugin Name, %2$s: plugin page.
            __('%1$s plugin not activated. Make sure you activate the plugin on the %2$s.', 'components-overview-flynt'),
            $pluginName,
            $pluginsUrl
        );

        echo '<div class="notice notice-warning"><p><strong>' . esc_html($title) . '</strong></p><p>' . wp_kses_post($message) . '</p></div>';
    }
}
