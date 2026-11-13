<?php

namespace FlyntComponentsOverview;

defined('ABSPATH') || exit;

final class AdminMenu
{
    public const MENU_SLUG = 'flyntComponentsOverview';
    public const VIEW_NONCE_ACTION = 'components_overview_view';
    public const VIEW_NONCE_NAME = '_components_overview_nonce';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'addAdminMenu']);
        add_filter('set-screen-option', [self::class, 'setScreenOption'], 10, 3);
    }

    public static function getViewUrl(array $args = []): string
    {
        $args = ['page' => self::MENU_SLUG] + $args + self::getViewContext();
        $args[self::VIEW_NONCE_NAME] = wp_create_nonce(self::VIEW_NONCE_ACTION);

        return add_query_arg(
            map_deep($args, static fn($value) => is_string($value) ? urlencode($value) : $value),
            admin_url('admin.php')
        );
    }

    public static function getViewContext(): array
    {
        $context = [];
        wp_parse_str((string) wp_parse_url(add_query_arg([]), PHP_URL_QUERY), $context);
        if (($context['page'] ?? '') !== self::MENU_SLUG) {
            return [];
        }

        $remove = array_merge(wp_removable_query_args(), [
            'page', 'layoutName', 'postType', 's', 'paged', 'orderby', 'order',
            'action', 'action2', '_wpnonce', '_wp_http_referer', '_wp_original_http_referer',
            'redirect_to', self::VIEW_NONCE_NAME,
        ]);
        $context = array_diff_key($context, array_flip($remove));
        foreach ($context as $name => $value) {
            if (
                !is_string($value)
                || !preg_match('/^[a-z0-9_-]+$/iD', (string) $name)
                || preg_match('/(?:^|_)nonce$/i', (string) $name)
            ) {
                unset($context[$name]);
            }
        }

        return $context;
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

    public static function checkViewNonce(): void
    {
        $nonce = '';
        if (
            isset($_REQUEST[self::VIEW_NONCE_NAME])
            && is_string($_REQUEST[self::VIEW_NONCE_NAME])
        ) {
            $nonce = sanitize_text_field(wp_unslash($_REQUEST[self::VIEW_NONCE_NAME]));
        }
        if (!wp_verify_nonce($nonce, self::VIEW_NONCE_ACTION)) {
            wp_nonce_ays(self::VIEW_NONCE_ACTION);
            exit;
        }
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

        $nonce = '';
        if (
            isset($_REQUEST[self::VIEW_NONCE_NAME])
            && is_string($_REQUEST[self::VIEW_NONCE_NAME])
        ) {
            $nonce = sanitize_text_field(wp_unslash($_REQUEST[self::VIEW_NONCE_NAME]));
        }
        if (!wp_verify_nonce($nonce, self::VIEW_NONCE_ACTION)) {
            wp_nonce_ays(self::VIEW_NONCE_ACTION);
            exit;
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

        // Keep the native menu link as an entry point, without accepting unsigned view parameters.
        if ($_GET === ['page' => self::MENU_SLUG]) {
            wp_safe_redirect(self::getViewUrl());
            exit;
        }

        $action = isset($_GET['action']) && is_string($_GET['action'])
            ? sanitize_key(wp_unslash($_GET['action']))
            : '';
        if ($action === 'refreshlayoutcache') {
            check_admin_referer('components_overview_refresh_layout_cache');

            $flexibleContentLayouts = FlexibleContentLayouts::getInstance();
            $flexibleContentLayouts->deleteTransients();

            wp_safe_redirect(self::getViewUrl());
            exit;
        }

        self::checkViewNonce();

        $isEmptyLayoutName = isset($_GET['layoutName']) && $_GET['layoutName'] === '';
        if (!$isEmptyLayoutName) {
            return;
        }

        $url = self::getViewUrl();
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
