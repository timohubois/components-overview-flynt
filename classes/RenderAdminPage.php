<?php

namespace FlyntComponentsOverview;

defined('ABSPATH') || exit;

final class RenderAdminPage
{
    public static function layoutsOverview(): void
    {

        $listTableLayouts = new ListTableLayouts();
        $listTableLayouts->prepare_items();

        $inputPageValue = AdminMenu::MENU_SLUG;
        $searchQuery = $listTableLayouts->getSearchQuery();
        $currentPostType = $listTableLayouts->getPostTypeFilter();
        $refreshLayoutCacheUrl = wp_nonce_url(
            admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&action=refreshLayoutCache'),
            'components_overview_refresh_layout_cache'
        );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php
                esc_html_e('Components Overview for Flynt', 'components-overview-flynt')
            ?></h1>
            <?php printf(
                '<a href="%s" class="page-title-action">%s</a>',
                esc_url($refreshLayoutCacheUrl),
                esc_html__('Refresh cached Layouts', 'components-overview-flynt'),
            ); ?>
            <?php
            // Show search results subtitle (matches native WordPress behavior)
            if ($searchQuery !== '') {
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
            <h2 class="screen-reader-text"><?php
                esc_html_e('Filter components list', 'components-overview-flynt')
            ?></h2>
            <form id="components-overview-flynt" method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr($inputPageValue) ?>" />
                <?php
                // Preserve postType filter when searching (matches native WordPress behavior)
                if ($currentPostType !== '') {
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
        $layouts = [];
        if ($layoutName !== '') {
            $layouts = FlexibleContentLayouts::getInstance()->getLayouts();
        }

        if (!isset($layouts[$layoutName]) || !is_array($layouts[$layoutName])) {
            self::layoutNotFound();
            return;
        }

        $currentLayout = $layouts[$layoutName];
        $heading = isset($currentLayout['label']) && is_string($currentLayout['label'])
            ? $currentLayout['label']
            : $layoutName;

        $listTablePostsWithLayout = new ListTablePostsWithLayout($layoutName);
        $listTablePostsWithLayout->prepare_items();

        $inputPageValue = AdminMenu::MENU_SLUG;
        $inputPostTypeValue = $listTablePostsWithLayout->getPostTypeFilter();
        $inputLayoutNameValue = $layoutName;
        $searchQuery = $listTablePostsWithLayout->getSearchQuery();
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
            if ($searchQuery !== '') {
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
                <?php $listTablePostsWithLayout->search_box(
                    esc_attr__('Search', 'components-overview-flynt'),
                    'search_posts'
                ); ?>
                <?php $listTablePostsWithLayout->display() ?>
            </form>
        </div>
        <?php
    }

    private static function layoutNotFound(): void
    {
        printf(
            '<div class="wrap"><h1>%s</h1><p>%s</p><p><a href="%s">%s</a></p></div>',
            esc_html__('Layout not found', 'components-overview-flynt'),
            esc_html__(
                'The requested layout is not available. Please select a layout from the overview.',
                'components-overview-flynt'
            ),
            esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG)),
            esc_html__('Back to Overview', 'components-overview-flynt')
        );
    }
}
