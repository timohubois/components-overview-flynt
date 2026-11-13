<?php

namespace FlyntComponentsOverview;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WP_Screen;

defined('ABSPATH') || exit;

final class RestApi
{
    public const NAMESPACE = 'components-overview-flynt/v1';

    public static function init(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
        add_action('init', [self::class, 'useAdminLocale'], 0);
    }

    public static function useAdminLocale(): void
    {
        // Multilingual plugins can change the fallback locale of a user set to Site Default.
        // Match the originating admin UI before post-type and ACF labels are registered.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- REST authenticates the request separately.
        $route = isset($_GET['rest_route']) && is_string($_GET['rest_route'])
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Exact route comparison below.
            ? wp_unslash($_GET['rest_route']) : '';
        $path = isset($_SERVER['REQUEST_URI'])
            ? wp_parse_url(sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])), PHP_URL_PATH) : '';
        if (
            $route !== '/' . self::NAMESPACE . '/table'
            && !str_ends_with(
                rtrim((string) $path, '/'),
                '/' . rest_get_url_prefix() . '/' . self::NAMESPACE . '/table'
            )
        ) {
            return;
        }
        if (!current_user_can('manage_options') || !isset($_GET['ui_locale']) || !is_string($_GET['ui_locale'])) {
            return;
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Installed-locale allowlist below.
        $locale = wp_unslash($_GET['ui_locale']);
        if (in_array($locale, array_merge(['en_US'], get_available_languages()), true)) {
            switch_to_locale($locale);
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
    }

    public static function registerRoutes(): void
    {
        register_rest_route(self::NAMESPACE, '/table', [
            'methods' => WP_REST_Server::READABLE,
            'permission_callback' => [self::class, 'checkPermission'],
            'callback' => [self::class, 'renderTable'],
            'args' => [
                'ui_locale' => [
                    'type' => 'string',
                    'validate_callback' => static fn($locale) => in_array(
                        $locale,
                        array_merge(['en_US'], get_available_languages()),
                        true
                    ),
                ],
                'part' => [
                    'type' => 'string',
                    'enum' => ['table', 'views'],
                    'default' => 'table',
                ],
                // URL-safe base64 keeps the admin query separate and survives language-plugin redirects.
                'view' => [
                    'type' => 'string',
                    'required' => true,
                    'validate_callback' => [self::class, 'validateView'],
                ],
            ],
        ]);
    }

    public static function checkPermission(): bool|WP_Error
    {
        if (!current_user_can('manage_options')) {
            return new WP_Error(
                'components_overview_forbidden',
                __('You are not allowed to access this page.', 'components-overview-flynt'),
                ['status' => rest_authorization_required_code()]
            );
        }

        return true;
    }

    public static function validateView(mixed $value): bool
    {
        $query = self::decodeView($value);
        if ($query === null) {
            return false;
        }

        wp_parse_str($query, $view);
        if (($view['page'] ?? '') !== AdminMenu::MENU_SLUG) {
            return false;
        }
        foreach ($view as $name => $item) {
            if (!is_string($item) || !preg_match('/^[a-z0-9_-]+$/iD', (string) $name)) {
                return false;
            }
        }
        // An async table request must never perform an admin action.
        if (isset($view['action']) || isset($view['action2'])) {
            return false;
        }
        if (
            isset($view['postType'])
            && $view['postType'] !== ''
            && $view['postType'] !== 'any'
            && !post_type_exists($view['postType'])
        ) {
            return false;
        }
        if (isset($view['paged']) && (!ctype_digit($view['paged']) || (int) $view['paged'] < 1)) {
            return false;
        }
        if (
            isset($view['orderby'])
            && !in_array($view['orderby'], ['', 'layout', 'post_title', 'post_type', 'post_date'], true)
        ) {
            return false;
        }
        if (isset($view['order']) && !in_array(strtolower($view['order']), ['asc', 'desc'], true)) {
            return false;
        }

        return true;
    }

    private static function decodeView(mixed $value): ?string
    {
        if (!is_string($value) || !preg_match('/^[a-z0-9_-]+$/iD', $value)) {
            return null;
        }
        $query = base64_decode(strtr($value, '-_', '+/'), true);
        return $query === false ? null : $query;
    }

    public static function renderTable(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        wp_parse_str(self::decodeView($request->get_param('view')) ?? '', $view);
        if (!wp_verify_nonce($view[AdminMenu::VIEW_NONCE_NAME] ?? '', AdminMenu::VIEW_NONCE_ACTION)) {
            return new WP_Error(
                'components_overview_expired_view',
                __('This view has expired.', 'components-overview-flynt'),
                ['status' => 403]
            );
        }

        if ($request->get_param('part') !== 'views' && isset($view['layoutName'])) {
            return new WP_Error(
                'components_overview_native_table',
                __('Matching posts are rendered on the admin page.', 'components-overview-flynt'),
                ['status' => 400]
            );
        }

        $previousLanguage = apply_filters('wpml_current_language', null);
        $language = $view['lang'] ?? $previousLanguage;
        if ($previousLanguage !== null && $language !== 'all') {
            $languages = apply_filters('wpml_active_languages', null, ['skip_missing' => 0]);
            if (!is_array($languages) || !isset($languages[$language])) {
                return new WP_Error(
                    'components_overview_invalid_language',
                    __('The requested language is not available.', 'components-overview-flynt'),
                    ['status' => 400]
                );
            }
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
        require_once ABSPATH . 'wp-admin/includes/screen.php';
        require_once ABSPATH . 'wp-admin/includes/template.php';

        // WP_List_Table builds navigation from globals. Scope the original admin view to rendering only.
        $previousGet = $_GET;
        $previousRequest = $_REQUEST;
        // Preserve the raw server value for restoration, not for use as input or output.
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Scoped server-state snapshot only.
        $previousUri = $_SERVER['REQUEST_URI'] ?? '';
        $previousScreen = $GLOBALS['current_screen'] ?? null;
        $async = static fn(): bool => true;
        $bufferLevel = ob_get_level();
        try {
            $_GET = wp_slash($view);
            $_REQUEST = $_GET;
            $_SERVER['REQUEST_URI'] = wp_parse_url(admin_url('admin.php'), PHP_URL_PATH)
                . '?' . http_build_query($view, '', '&', PHP_QUERY_RFC3986);
            // Retain admin post-status visibility and native screen options in a REST request.
            $GLOBALS['current_screen'] = WP_Screen::get('toplevel_page_' . AdminMenu::MENU_SLUG);
            // Prevent core pagination from redirecting/exiting an asynchronous response for a stale page.
            add_filter('wp_doing_ajax', $async);
            if ($previousLanguage !== null) {
                do_action('wpml_switch_language', $language);
            }

            ob_start();
            if ($request->get_param('part') === 'views') {
                RenderAdminPage::views();
            } else {
                RenderAdminPage::table();
            }
            $html = ob_get_clean();
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
            remove_filter('wp_doing_ajax', $async);
            if ($previousLanguage !== null) {
                do_action('wpml_switch_language', $previousLanguage);
            }
            $_GET = $previousGet;
            $_REQUEST = $previousRequest;
            $_SERVER['REQUEST_URI'] = $previousUri;
            $GLOBALS['current_screen'] = $previousScreen;
        }

        $response = new WP_REST_Response(['html' => $html]);
        $response->header('Cache-Control', 'no-store');
        return $response;
    }
}
