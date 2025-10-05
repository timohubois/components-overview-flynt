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
        $searchQuery = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Components Overview for Flynt', 'components-overview-flynt') ?></h1>
            <?php printf(
                '<a href="%s" class="page-title-action">%s</a>',
                esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&action=refreshLayoutCache')),
                esc_html__('Refresh cached Layouts', 'components-overview-flynt'),
            ); ?>
            <?php
            // Show search results subtitle (matches native WordPress behavior)
            if (!empty($searchQuery)) {
                echo '<span class="subtitle">';
                printf(
                    /* translators: %s: Search query. */
                    esc_html__('Search results for: %s', 'components-overview-flynt'),
                    '<strong>' . esc_html($searchQuery) . '</strong>'
                );
                echo '</span>';
            }
            ?>
            <hr class="wp-header-end">
            <h2 class="screen-reader-text"><?php esc_html_e('Filter components list', 'components-overview-flynt') ?></h2>
            <form id="components-overview-flynt" method="get">
                <input type="hidden" name="page" value="<?php echo esc_html($inputPageValue) ?>" />
                <?php
                // Preserve postType filter when searching (matches native WordPress behavior)
                $currentPostType = isset($_GET['postType']) ? sanitize_text_field(wp_unslash($_GET['postType'])) : '';
                if (!empty($currentPostType)) {
                    echo '<input type="hidden" name="postType" value="' . esc_attr($currentPostType) . '" />';
                }
                ?>
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
        $searchQuery = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

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
            <?php
            // Show search results subtitle (matches native WordPress behavior)
            if (!empty($searchQuery)) {
                echo '<span class="subtitle">';
                printf(
                    /* translators: %s: Search query. */
                    esc_html__('Search results for: %s', 'components-overview-flynt'),
                    '<strong>' . esc_html($searchQuery) . '</strong>'
                );
                echo '</span>';
            }
            ?>
            <hr class="wp-header-end">
            <h2 class="screen-reader-text"><?php esc_html_e('Filter posts list', 'components-overview-flynt') ?></h2>
            <form id="component-overview-flynt-posts" method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($inputPageValue) ?>" />
                <input type="hidden" name="postType" value="<?php echo esc_attr($inputPostTypeValue) ?>" />
                <input type="hidden" name="layoutName" value="<?php echo esc_attr($inputLayoutNameValue) ?>" />
                <?php $listTablePostsWithLayout->views() ?>
                <?php $listTablePostsWithLayout->search_box(esc_attr__('Search', 'components-overview-flynt'), 'search_posts'); ?>
                <?php $listTablePostsWithLayout->display() ?>
            </form>
        </div>
        <?php
    }
}
