<?php

namespace FlyntComponentsOverview;

defined('ABSPATH') || exit;

final class AdminMenu
{
    public const MENU_SLUG = 'flyntComponentsOverview';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'addAdminMenu']);
        add_filter('set-screen-option', [self::class, 'setScreenOption'], 10, 3);
    }

    public static function addAdminMenu(): void
    {
        $menuPage = add_menu_page(
            __('Components Overview for Flynt', 'components-overview-flynt'),
            __('Components Overview for Flynt', 'components-overview-flynt'),
            'administrator',
            self::MENU_SLUG,
            [self::class, 'renderAdminPage'],
            'dashicons-info-outline',
            85
        );

        add_action('load-' . $menuPage, [self::class, 'addScreenOptions']);
        add_action('load-' . $menuPage, [self::class, 'maybeRedirect']);
    }

    public static function addScreenOptions(): void
    {
        $option = 'per_page';
        $args = [
            'label' => __('Number of items per page'),
            'default' => 20,
            'option' => 'components_overview_posts_per_page'
        ];
        add_screen_option($option, $args);
    }

    public static function setScreenOption($status, $option, $value): mixed
    {
        return $value;
    }

    public static function renderAdminPage(): void
    {
        $isLayoutName = isset($_GET['layoutName']);

        if ($isLayoutName) {
            $layoutName = sanitize_text_field(wp_unslash($_GET['layoutName']));
            RenderAdminPage::postsWithLayout($layoutName);
        } else {
            RenderAdminPage::layoutsOverview();
        }

        set_screen_options();
    }

    public static function maybeRedirect(): void
    {
        $shouldRefreshLayoutCache = isset($_GET['action']) && $_GET['action'] === 'refreshLayoutCache';
        if ($shouldRefreshLayoutCache) {
            $flexibleContentLayouts = FlexibleContentLayouts::getInstance();
            $flexibleContentLayouts->deleteTransients();

            $url = esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG));
            wp_redirect(esc_url($url));
            exit;
        }

        $isEmptyLayoutName = isset($_GET['layoutName']) && empty($_GET['layoutName']);
        if ($isEmptyLayoutName && isset($_GET['postType'])) {
            $url = sprintf(
                admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&postType=%s'),
                sanitize_text_field(wp_unslash($_GET['postType']))
            );
            wp_redirect($url);
            exit;
        }

        if ($isEmptyLayoutName) {
            $url = esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG));
            wp_redirect(esc_url($url));
            exit;
        }
    }
}
