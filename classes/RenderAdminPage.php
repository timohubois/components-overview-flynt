<?php

namespace FlyntComponentsOverview;

defined('ABSPATH') || exit;

class RenderAdminPage
{
    public static function nextUpdateNotification(): void
    {
        $cronjob = CronJob::getInstance();
        $nextScheduledTimestamp = wp_next_scheduled($cronjob->hook);
        $timeLeft = $nextScheduledTimestamp - time();

        $isCronjobRunning = (bool) get_option(CronJob::OPTION_NAME_CRONJOB_RUNNING);
        if ($isCronjobRunning || $timeLeft < 1) {
            $message = __('Component Overview is currently updating. Please reload this page in a few seconds.', 'components-overview-flynt');
        } else {
            $message = sprintf(
                __('Full update scheduled in %s %s.', 'components-overview-flynt'),
                esc_html($timeLeft),
                ($timeLeft > 1 ? esc_html__('seconds', 'components-overview-flynt') : esc_html__('second', 'components-overview-flynt'))
            );
        }
        ?>
        <div class="notice notice-info">
            <p>
                <?php esc_attr_e($message) ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG)) ?>">
                    <?php esc_html_e('Reload this page', 'components-overview-flynt') ?>
                </a>
            </p>
        </div>
        <?php
    }

    public static function componentsOverview(): void
    {
        $table = new ListTableComponents();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Components Overview for Flynt', 'components-overview-flynt') ?></h1>
            <hr class="wp-header-end">
            <h2 class="screen-reader-text"><?php esc_html_e('Filter components list', 'components-overview-flynt') ?></h2>
            <form id="components-overview-flynt" method="get">
                <input type="hidden" name="page" value="<?php isset($_GET['page']) ? esc_html_e(sanitize_text_field(wp_unslash($_GET['page']))) : '' ?>" />
                <?php $table->views() ?>
                <?php $table->search_box(esc_attr__('Search', 'components-overview-flynt'), 'search_id'); ?>
                <?php $table->display() ?>
            </form>
        </div>
        <?php
    }

    public static function postsWithComponent(string $componentName): void
    {
        $table = new ListTablePosts();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e($componentName); ?></h1>
            <?php printf(
                '<a href="%s" class="page-title-action">%s</a>',
                esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG)),
                esc_html__('Back to Overview', 'components-overview-flynt'),
            ); ?>
            <hr class="wp-header-end">
            <h2 class="screen-reader-text"><?php esc_html_e('Filter posts list', 'components-overview-flynt') ?></h2>
            <form id="component-overview-flynt-posts" method="get">
                <input type="hidden" name="page" value="<?php isset($_GET['page']) ? esc_html_e(sanitize_text_field(wp_unslash($_GET['page']))) : '' ?>" />
                <input type="hidden" name="postType" value="<?php isset($_GET['postType']) ? esc_html_e(sanitize_text_field(wp_unslash($_GET['postType']))) : '' ?>" />
                <input type="hidden" name="componentName" value="<?php isset($_GET['componentName']) ? esc_html_e(sanitize_text_field(wp_unslash($_GET['componentName']))) : '' ?>" />
                <?php $table->views() ?>
                <?php $table->search_box(esc_attr__('Search', 'components-overview-flynt'), 'search_posts'); ?>
                <?php $table->display() ?>
            </form>
        </div>
        <?php
    }
}
