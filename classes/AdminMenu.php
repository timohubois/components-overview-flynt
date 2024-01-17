<?php

namespace FlyntComponentsOverview;

defined('ABSPATH') || exit;

final class AdminMenu
{
    const MENU_SLUG = 'flyntComponentsOverview';

    public static function init(): void
    {
        $isEmptyComponentName = isset($_GET['componentName']) && empty($_GET['componentName']);

        if ($isEmptyComponentName && isset($_GET['postType'])) {
            $url = sprintf(
                admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&postType=%s'),
                sanitize_text_field(wp_unslash($_GET['postType']))
            );
            wp_redirect($url);
            exit;
        } elseif ($isEmptyComponentName) {
            $url = esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG));
            wp_redirect($url);
            exit;
        }


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

        $isCronjobAsapPlanned = (bool) get_option(CronJob::OPTION_NAME_CRONJOB_RUN_ASAP_PLANNED);
        $isCronjobRunning = (bool) get_option(CronJob::OPTION_NAME_CRONJOB_RUNNING);
        if ($isCronjobAsapPlanned || $isCronjobRunning) {
            RenderAdminPage::nextUpdateNotification();
        }

        $isComponentName = isset($_GET['componentName']);

        if ($isComponentName) {
            $componentName = sanitize_text_field(wp_unslash($_GET['componentName']));
            RenderAdminPage::postsWithComponent($componentName);
        } else {
            RenderAdminPage::componentsOverview();
        }

        set_screen_options();
    }
}
