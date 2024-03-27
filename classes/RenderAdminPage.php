<?php

namespace FlyntComponentsOverview;

defined('ABSPATH') || exit;

final class RenderAdminPage
{
    public static function layoutsOverview(): void
    {

        $listTableLayouts = new ListTableLayouts();
        $listTableLayouts->prepare_items();

        $inputPageValue = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Components Overview for Flynt', 'components-overview-flynt') ?></h1>
            <?php printf(
                '<a href="%s" class="page-title-action">%s</a>',
                esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&action=refreshLayoutCache')),
                esc_html__('Refresh cached Layouts', 'components-overview-flynt'),
            ); ?>
            <hr class="wp-header-end">
            <h2 class="screen-reader-text"><?php esc_html_e('Filter components list', 'components-overview-flynt') ?></h2>
            <form id="components-overview-flynt" method="get">
                <input type="hidden" name="page" value="<?php echo esc_html($inputPageValue) ?>" />
                <?php $listTableLayouts->views() ?>
                <?php $listTableLayouts->search_box(esc_attr__('Search', 'components-overview-flynt'), 'search_id'); ?>
                <?php $listTableLayouts->display() ?>
            </form>
        </div>
        <?php
    }

    public static function postsWithLayout(string $layoutName): void
    {
        $listTablePostsWithLayout = new ListTablePostsWithLayout();
        $listTablePostsWithLayout->prepare_items();

        $inputPageValue = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $inputPostTypeValue = isset($_GET['postType']) ? sanitize_text_field(wp_unslash($_GET['postType'])) : '';
        $inputLayoutNameValue = isset($_GET['layoutName']) ? sanitize_text_field(wp_unslash($_GET['layoutName'])) : '';

        $flexibleContentLayouts = FlexibleContentLayouts::getInstance();
        $layouts = $flexibleContentLayouts->getLayouts();
        $currentLayout = $layouts[$layoutName];
        $heading = $currentLayout["label"] ?? $layoutName;
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo wp_kses_post($heading); ?></h1>
            <?php printf(
                '<a href="%s" class="page-title-action">%s</a>',
                esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG)),
                esc_html__('Back to Overview', 'components-overview-flynt'),
            ); ?>
            <hr class="wp-header-end">
            <h2 class="screen-reader-text"><?php esc_html_e('Filter posts list', 'components-overview-flynt') ?></h2>
            <form id="component-overview-flynt-posts" method="get">
                <input type="hidden" name="page" value="<?php echo esc_html($inputPageValue) ?>" />
                <input type="hidden" name="postType" value="<?php esc_html($inputPostTypeValue) ?>" />
                <input type="hidden" name="layoutName" value="<?php echo esc_html($inputLayoutNameValue) ?>" />
                <?php $listTablePostsWithLayout->views() ?>
                <?php $listTablePostsWithLayout->search_box(esc_attr__('Search', 'components-overview-flynt'), 'search_posts'); ?>
                <?php $listTablePostsWithLayout->display() ?>
            </form>
        </div>
        <?php
    }
}
