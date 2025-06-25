<?php

namespace FlyntComponentsOverview;

use WP_List_Table;

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

final class ListTableLayouts extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => __('Component Overview for Flynt', 'components-overview-flynt'),
            'plural' => __('Components Overview for Flynt', 'components-overview-flynt'),
            'ajax' => false
        ]);
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function get_views()
    {
        $flexibleContentLayouts = FlexibleContentLayouts::getInstance();
        $currentPostType = isset($_GET['postType']) ? sanitize_text_field(wp_unslash($_GET['postType'])) : 'any';
        $isSearch = isset($_GET['s']) && !empty($_GET['s']);

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

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function column_default($item, $column_name)
    {
        $href = sprintf(
            admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&layoutName=%s'),
            $item['name']
        );

        $currentPostType = isset($_GET['postType']) ? sanitize_text_field(wp_unslash($_GET['postType'])) : 'any';
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
                    esc_attr(sprintf(__('&#8220;%s&#8221; (Edit)'), $title)),
                    wp_kses_post($title)
                );
                echo "</strong>\n";
                echo wp_kses_post($this->row_actions($actions));
                return;
            case 'name':
                return $item['name'];
            default:
                return print_r($item, true);
        }
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function get_columns()
    {
        $columns = [
            'layout' => __('Layout', 'components-overview-flynt'),
            'name' => __('Name', 'components-overview-flynt'),
        ];
        return $columns;
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function get_sortable_columns()
    {
        return [
            'layout' => ['layout', false],
        ];
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function prepare_items(): void
    {
        $perPage = $this->get_items_per_page('components_overview_posts_per_page');
        $pageNumber = $this->get_pagenum();
        $currentPostType = isset($_GET['postType']) ? sanitize_text_field(wp_unslash($_GET['postType'])) : 'any';

        $flexibleContentLayouts = FlexibleContentLayouts::getInstance();

        // Get all layouts first (without pagination)
        $allLayouts = $flexibleContentLayouts->getLayouts($currentPostType);

        // Search filtering - do this BEFORE pagination
        $search = empty($_GET['s']) ? '' : sanitize_text_field(wp_unslash($_GET['s']));
        if (!empty($search)) {
            $allLayouts = array_filter($allLayouts, static function (array $item) use ($search): bool {
                // Check both 'label' and 'name' fields for search, with fallback
                $label = $item['label'] ?? $item['name'] ?? '';
                $name = $item['name'] ?? '';
                $searchLower = strtolower($search);

                return str_contains(strtolower($label), $searchLower) ||
                       str_contains(strtolower($name), $searchLower);
            });
        }

        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];

        // Sorting
        $orderby = empty($_GET['orderby']) ? '' : sanitize_text_field(wp_unslash($_GET['orderby']));
        $order = empty($_GET['order']) ? '' : sanitize_text_field(wp_unslash($_GET['order']));
        if ($orderby && $order) {
            usort($allLayouts, function ($a, $b) use ($orderby, $order) {
                if ($orderby === 'layout') {
                    $orderby = 'label';
                }

                // Ensure the field exists before comparison
                $aValue = $a[$orderby] ?? '';
                $bValue = $b[$orderby] ?? '';

                if ($order === 'asc') {
                    return $aValue > $bValue ? 1 : ($aValue < $bValue ? -1 : 0);
                } else {
                    return $aValue < $bValue ? 1 : ($aValue > $bValue ? -1 : 0);
                }
            });
        }

        // Calculate total items after search filtering
        $totalItems = count($allLayouts);
        $totalPages = ceil($totalItems / $perPage);

        // Apply pagination AFTER search and sorting
        $offset = ($pageNumber - 1) * $perPage;
        $layouts = array_slice($allLayouts, $offset, $perPage);

        $this->set_pagination_args([
            'total_items' => $totalItems,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ]);

        $this->items = $layouts;
    }
}
