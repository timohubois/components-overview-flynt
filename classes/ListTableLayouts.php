<?php

namespace FlyntComponentsOverview;

use WP_List_Table;

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

final class ListTableLayouts extends WP_List_Table
{
    private string $postTypeFilter;
    private string $search;
    private string $orderby;
    private string $order;

    public function __construct()
    {
        parent::__construct([
            'singular' => __('Component Overview for Flynt', 'components-overview-flynt'),
            'plural' => __('Components Overview for Flynt', 'components-overview-flynt'),
            'ajax' => false
        ]);

        $postType = isset($_GET['postType']) && is_string($_GET['postType'])
            ? sanitize_text_field(wp_unslash($_GET['postType']))
            : '';
        $this->postTypeFilter = $postType === 'any' || post_type_exists($postType) ? $postType : '';

        $this->search = isset($_GET['s']) && is_string($_GET['s'])
            ? sanitize_text_field(wp_unslash($_GET['s']))
            : '';

        $orderby = isset($_GET['orderby']) && is_string($_GET['orderby'])
            ? sanitize_text_field(wp_unslash($_GET['orderby']))
            : '';
        $this->orderby = array_key_exists($orderby, $this->get_sortable_columns()) ? $orderby : '';

        $order = isset($_GET['order']) && is_string($_GET['order'])
            ? strtolower(sanitize_text_field(wp_unslash($_GET['order'])))
            : 'asc';
        $this->order = $order === 'desc' ? 'desc' : 'asc';
    }

    public function getPostTypeFilter(): string
    {
        return $this->postTypeFilter;
    }

    public function getSearchQuery(): string
    {
        return $this->search;
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required WP_List_Table override.
    public function get_views()
    {
        $flexibleContentLayouts = FlexibleContentLayouts::getInstance();
        $currentPostType = $this->postTypeFilter ?: 'any';
        $isSearch = $this->search !== '';

        $views['any'] = sprintf(
            '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
            esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG)),
            $currentPostType === 'any' && !$isSearch ? ' class="current"' : '',
            __('All', 'components-overview-flynt'),
            $flexibleContentLayouts->getLayoutsCount()
        );

        $postTypesWithLayouts = $flexibleContentLayouts->getPostTypesWithLayouts();
        foreach ($postTypesWithLayouts as $postType) {
            $postTypeObject = get_post_type_object($postType);
            $count = $flexibleContentLayouts->getLayoutsCount($postType);

            if ($count === 0) {
                continue;
            }

            $views[$postType] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&postType=' . $postType)),
                $currentPostType === $postType ? ' class="current"' : '',
                $postTypeObject->labels->name ?? $postType,
                $count
            );
        }

        return $views;
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required WP_List_Table override.
    public function column_default($item, $column_name)
    {
        $href = sprintf(
            admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&layoutName=%s'),
            $item['name']
        );

        $currentPostType = $this->postTypeFilter ?: 'any';
        if ($currentPostType !== 'any') {
            $href = sprintf(
                admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&layoutName=%s&postType=%s'),
                $item['name'],
                $currentPostType
            );
        }

        switch ($column_name) {
            case 'layout':
                $actions = [
                    'view' => sprintf(
                        '<a href="%s">%s</a>',
                        esc_url($href),
                        __('View where used', 'components-overview-flynt')
                    ),
                ];

                $title = $item['label'] ?? $item['name'];
                echo '<strong>';
                printf(
                    '<a class="row-title" href="%s" aria-label="%s">%s</a>',
                    esc_url($href),
                    /* translators: %s: Post title. */
                    esc_attr(sprintf(__('&#8220;%s&#8221; (Edit)', 'components-overview-flynt'), $title)),
                    wp_kses_post($title)
                );
                echo "</strong>\n";
                echo wp_kses_post($this->row_actions($actions));
                return;
            case 'name':
                return $item['name'];
            default:
                return '';
        }
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required WP_List_Table override.
    public function get_columns()
    {
        $columns = [
            'layout' => __('Layout', 'components-overview-flynt'),
            'name' => __('Name', 'components-overview-flynt'),
        ];
        return $columns;
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required WP_List_Table override.
    public function get_sortable_columns()
    {
        return [
            'layout' => ['layout', false],
        ];
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required WP_List_Table override.
    public function prepare_items(): void
    {
        $perPage = $this->get_items_per_page('components_overview_posts_per_page');
        $pageNumber = $this->get_pagenum();
        $currentPostType = $this->postTypeFilter ?: 'any';

        $flexibleContentLayouts = FlexibleContentLayouts::getInstance();

        // Get all layouts first (without pagination)
        $allLayouts = $flexibleContentLayouts->getLayouts($currentPostType);

        // Search filtering - do this BEFORE pagination
        $search = strtolower($this->search);
        if ($search !== '') {
            $allLayouts = array_filter($allLayouts, static function (array $item) use ($search): bool {
                // Check both 'label' and 'name' fields for search, with fallback
                $label = $item['label'] ?? $item['name'] ?? '';
                $name = $item['name'] ?? '';

                return str_contains(strtolower($label), $search) ||
                       str_contains(strtolower($name), $search);
            });
        }

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];

        if ($this->orderby === 'layout') {
            $order = $this->order;
            usort($allLayouts, static function (array $a, array $b) use ($order): int {
                $comparison = ($a['label'] ?? '') <=> ($b['label'] ?? '');
                return $order === 'asc' ? $comparison : -$comparison;
            });
        }

        // Calculate total items after search filtering
        $totalItems = count($allLayouts);

        // Apply pagination AFTER search and sorting
        $offset = ($pageNumber - 1) * $perPage;
        $layouts = array_slice($allLayouts, $offset, $perPage);

        $this->set_pagination_args([
            'total_items' => (int) $totalItems,
            'per_page' => (int) $perPage,
            'total_pages' => (int) ceil($totalItems / $perPage),
        ]);

        $this->items = $layouts;
    }
}
