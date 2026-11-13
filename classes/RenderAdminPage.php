<?php

namespace FlyntComponentsOverview;

defined('ABSPATH') || exit;

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Both entry points verify the signed view first.

final class RenderAdminPage
{
    public static function page(): void
    {
        ?>
        <div id="components-overview-async">
            <?php
            if (isset($_GET['layoutName'])) {
                $layoutName = is_string($_GET['layoutName'])
                    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Exact catalog key.
                    ? wp_unslash($_GET['layoutName'])
                    : '';
                self::postsWithLayout($layoutName);
            } else {
                self::layoutsOverview();
            }
            ?>
        </div>
        <?php
    }

    public static function layoutsOverview(bool $cachedOnly = false): void
    {
        $table = new ListTableLayouts();
        $table->prepare_items($cachedOnly);
        self::render(
            $table,
            null,
            __('Components Overview for Flynt', 'components-overview-flynt'),
            $table->isLoading()
        );
    }

    public static function table(): void
    {
        $table = new ListTableLayouts();
        $table->prepare_items();
        $table->display();
    }

    public static function postsWithLayout(string $layoutName): void
    {
        // Resolve the heading from field definitions, without rebuilding the entire used-layout index.
        $layout = self::registeredLayout($layoutName);
        if ($layout === null) {
            self::layoutNotFound();
            return;
        }

        $table = new ListTablePostsWithLayout($layoutName);
        $table->prepare_items();
        // Preserve the unavailable-layout check when search or post-type filtering returns no rows.
        if (!$table->has_items()) {
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
        self::render($table, $layoutName, $heading, false);
    }

    public static function views(): void
    {
        if (isset($_GET['layoutName']) && is_string($_GET['layoutName'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Exact catalog key.
            $layoutName = wp_unslash($_GET['layoutName']);
            if (self::registeredLayout($layoutName) === null) {
                return;
            }
            $table = new ListTablePostsWithLayout($layoutName);
        } else {
            $table = new ListTableLayouts();
        }
        $table->views();
    }

    private static function render(
        ListTableLayouts|ListTablePostsWithLayout $table,
        ?string $layoutName,
        string $heading,
        bool $loading
    ): void {
        $isOverview = $layoutName === null;
        $actionUrl = $isOverview
            ? wp_nonce_url(
                AdminMenu::getViewUrl(['action' => 'refreshLayoutCache']),
                'components_overview_refresh_layout_cache'
            )
            : AdminMenu::getViewUrl();
        $search = $table->getSearchQuery();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo wp_kses_post($heading); ?></h1>
            <?php
            if ($search !== '') {
                echo '<span class="subtitle">';
                printf(
                    /* translators: %s: Search query. */
                    esc_html__('Search results for: %s', 'components-overview-flynt'),
                    '<strong>' . esc_html($search) . '</strong>'
                );
                echo '</span>';
            }
            ?>
            <hr class="wp-header-end">
            <p class="description">
                <?php
                echo $isOverview
                    ? esc_html__('Find a component to see where it is used.', 'components-overview-flynt')
                    : esc_html__('Find content that uses this component.', 'components-overview-flynt');
                ?>
                <a href="<?php echo esc_url($actionUrl); ?>"><?php
                    echo $isOverview
                        ? esc_html__('Refresh components', 'components-overview-flynt')
                        : esc_html__('Back to Overview', 'components-overview-flynt');
                ?></a>
            </p>
            <h2 class="screen-reader-text"><?php
                echo $isOverview
                    ? esc_html__('Filter components list', 'components-overview-flynt')
                    : esc_html__('Filter posts list', 'components-overview-flynt');
            ?></h2>
            <form id="<?php echo $isOverview ? 'components-overview-flynt' : 'component-overview-flynt-posts'; ?>"
                method="get">
                <input type="hidden" name="page" value="<?php echo esc_attr(AdminMenu::MENU_SLUG); ?>" />
                <?php
                wp_nonce_field(AdminMenu::VIEW_NONCE_ACTION, AdminMenu::VIEW_NONCE_NAME, false);
                self::viewContextFields();
                if ($table->getPostTypeFilter() !== '') {
                    printf(
                        '<input type="hidden" name="postType" value="%s" />',
                        esc_attr($table->getPostTypeFilter())
                    );
                }
                if ($layoutName !== null) {
                    printf('<input type="hidden" name="layoutName" value="%s" />', esc_attr($layoutName));
                }
                $loadingFilters = self::filters($table);
                self::searchBox($search);
                ?>
                <?php
                printf(
                    '<div data-results data-state="%s"%s>',
                    $loading ? 'loading' : 'loaded',
                    $loading ? ' data-async="table" aria-busy="true"' : ''
                );
                ?>
                    <?php if ($loading) : ?>
                        <div data-error class="notice notice-error inline" hidden>
                            <p data-error-message role="alert"></p>
                        </div>
                    <?php endif; ?>
                    <div data-body><?php $table->display(); ?></div>
                </div>
                <?php if ($loading || $loadingFilters) : ?>
                <noscript>
                    <p>
                        <?php
                        if ($loading) {
                            esc_html_e(
                                'JavaScript is required to load the list and filters.',
                                'components-overview-flynt'
                            );
                        } else {
                            esc_html_e(
                                'JavaScript is required to load the post-type filters.',
                                'components-overview-flynt'
                            );
                        }
                        ?>
                    </p>
                </noscript>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    private static function filters(ListTableLayouts|ListTablePostsWithLayout $table): bool
    {
        $table->views();
        return false;
    }

    private static function searchBox(string $search): void
    {
        // Unlike WP_List_Table::search_box(), keep search available before any items have loaded.
        foreach (['orderby', 'order'] as $name) {
            if (isset($_GET[$name]) && is_string($_GET[$name])) {
                printf(
                    '<input type="hidden" name="%s" value="%s" />',
                    esc_attr($name),
                    esc_attr(sanitize_text_field(wp_unslash($_GET[$name])))
                );
            }
        }
        ?>
        <p class="search-box">
            <label class="screen-reader-text" for="components-overview-search"><?php
                esc_html_e('Search:', 'components-overview-flynt');
            ?></label>
            <input type="search" id="components-overview-search" name="s" value="<?php echo esc_attr($search); ?>" />
            <?php submit_button(__('Search', 'components-overview-flynt'), '', '', false, ['id' => 'search-submit']); ?>
        </p>
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
