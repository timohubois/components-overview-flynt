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
            AdminMenu::getViewUrl(['action' => 'refreshLayoutCache']),
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
                wp_nonce_field(AdminMenu::VIEW_NONCE_ACTION, AdminMenu::VIEW_NONCE_NAME, false);
                self::viewContextFields();
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
        // Resolve the heading from field definitions, without rebuilding the entire used-layout index.
        $layout = self::registeredLayout($layoutName);
        if ($layout === null) {
            self::layoutNotFound();
            return;
        }

        $listTablePostsWithLayout = new ListTablePostsWithLayout($layoutName);
        $listTablePostsWithLayout->prepare_items();
        // Preserve the unavailable-layout check when search or post-type filtering returns no rows.
        if (!$listTablePostsWithLayout->has_items()) {
            $count = (new PostsWithLayout())->getCount(
                $layoutName,
                FlexibleContentLayouts::getInstance()->getFieldGroupLayouts(),
                'any',
                null
            );
            if ($count === 0) {
                self::layoutNotFound();
                return;
            }
        }

        $heading = isset($layout['label']) && is_string($layout['label']) ? $layout['label'] : $layoutName;

        $inputPageValue = AdminMenu::MENU_SLUG;
        $inputPostTypeValue = $listTablePostsWithLayout->getPostTypeFilter();
        $inputLayoutNameValue = $layoutName;
        $searchQuery = $listTablePostsWithLayout->getSearchQuery();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo wp_kses_post($heading); ?></h1>
            <?php printf(
                '<a href="%s" class="page-title-action">%s</a>',
                esc_url(AdminMenu::getViewUrl()),
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
                <?php
                wp_nonce_field(AdminMenu::VIEW_NONCE_ACTION, AdminMenu::VIEW_NONCE_NAME, false);
                self::viewContextFields();
                ?>
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

    private static function registeredLayout(string $name): ?array
    {
        $matches = [];
        foreach (FlexibleContentLayouts::getInstance()->getFieldGroupLayouts() as $group => $layouts) {
            foreach ($layouts as $layout) {
                if ($layout['name'] === $name) {
                    $matches[$group] = $layout;
                }
            }
        }
        if ($matches === []) {
            return null;
        }
        // Most Flynt field groups share the same component label; no usage lookup is needed then.
        $labels = array_map(
            static fn(array $layout) => isset($layout['label']) && is_string($layout['label'])
                ? $layout['label'] : $name,
            $matches
        );
        if (count(array_unique($labels)) === 1) {
            return end($matches);
        }
        // With conflicting labels, mirror the overview's last used definition, not the last registration.
        $match = null;
        foreach ($matches as $group => $layout) {
            if ((new PostsWithLayout())->getCount($name, [$group => [$layout]], 'any', null) > 0) {
                $match = $layout;
            }
        }
        return $match;
    }

    private static function viewContextFields(): void
    {
        foreach (AdminMenu::getViewContext() as $name => $value) {
            printf('<input type="hidden" name="%s" value="%s" />', esc_attr($name), esc_attr($value));
        }
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
            esc_url(AdminMenu::getViewUrl()),
            esc_html__('Back to Overview', 'components-overview-flynt')
        );
    }
}
