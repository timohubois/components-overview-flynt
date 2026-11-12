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
            'manage_options',
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
            'label' => __('Number of items per page', 'components-overview-flynt'),
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
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__('You are not allowed to access this page.', 'components-overview-flynt'),
                '',
                ['response' => 403]
            );
            return;
        }

        if (isset($_GET['layoutName'])) {
            $layoutName = is_string($_GET['layoutName'])
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Exact catalog key.
                ? wp_unslash($_GET['layoutName'])
                : '';
            // Validate the exact name against the layout catalog in the renderer.
            RenderAdminPage::postsWithLayout($layoutName);
        } else {
            RenderAdminPage::layoutsOverview();
        }

        set_screen_options();
    }

    public static function maybeRedirect(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__('You are not allowed to access this page.', 'components-overview-flynt'),
                '',
                ['response' => 403]
            );
            return;
        }

        $action = isset($_GET['action']) && is_string($_GET['action'])
            ? sanitize_key(wp_unslash($_GET['action']))
            : '';
        if ($action === 'refreshlayoutcache') {
            check_admin_referer('components_overview_refresh_layout_cache');

            $flexibleContentLayouts = FlexibleContentLayouts::getInstance();
            $flexibleContentLayouts->deleteTransients();

            wp_safe_redirect(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG));
            exit;
        }

        $isEmptyLayoutName = isset($_GET['layoutName']) && $_GET['layoutName'] === '';
        if (!$isEmptyLayoutName) {
            return;
        }

        $url = admin_url('admin.php?page=' . self::MENU_SLUG);
        if (isset($_GET['postType']) && is_string($_GET['postType'])) {
            $postType = sanitize_text_field(wp_unslash($_GET['postType']));
            if ($postType === 'any' || post_type_exists($postType)) {
                $url = add_query_arg('postType', $postType, $url);
            }
        }

        wp_safe_redirect($url);
        exit;
    }
}
