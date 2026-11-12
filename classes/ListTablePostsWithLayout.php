<?php

namespace FlyntComponentsOverview;

use WP_List_Table;
use WP_Posts_List_Table;

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

if (!class_exists('WP_Posts_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-posts-list-table.php');
}

final class ListTablePostsWithLayout extends WP_List_Table
{
    private string $layoutName;
    private string $postTypeFilter;
    private ?string $search;
    private string $orderby;
    private string $order;

    public function __construct(?string $layoutName = null)
    {
        parent::__construct([
            'singular' => __('Component Overview for Flynt', 'components-overview-flynt'),
            'plural'    => __('Components Overview for Flynt', 'components-overview-flynt'),
            'ajax'      => false
        ]);

        $this->layoutName = $layoutName ?? (
            isset($_GET['layoutName']) && is_string($_GET['layoutName'])
                ? sanitize_text_field(wp_unslash($_GET['layoutName']))
                : ''
        );

        $postType = isset($_GET['postType']) && is_string($_GET['postType'])
            ? sanitize_text_field(wp_unslash($_GET['postType']))
            : '';
        $this->postTypeFilter = $postType === 'any' || post_type_exists($postType) ? $postType : '';

        $this->search = isset($_GET['s']) && is_string($_GET['s'])
            ? sanitize_text_field(wp_unslash($_GET['s']))
            : null;

        $orderby = isset($_GET['orderby']) && is_string($_GET['orderby'])
            ? sanitize_text_field(wp_unslash($_GET['orderby']))
            : 'post_date';
        $this->orderby = array_key_exists($orderby, $this->get_sortable_columns()) ? $orderby : 'post_date';

        $order = isset($_GET['order']) && is_string($_GET['order'])
            ? strtoupper(sanitize_text_field(wp_unslash($_GET['order'])))
            : 'DESC';
        $this->order = $order === 'ASC' ? 'ASC' : 'DESC';
    }

    public function getPostTypeFilter(): string
    {
        return $this->postTypeFilter;
    }

    public function getSearchQuery(): string
    {
        return $this->search ?? '';
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required WP_List_Table override.
    public function get_views()
    {
        $layoutName = $this->layoutName;
        if ($layoutName === '') {
            return [];
        }

        $currentPostType = $this->postTypeFilter ?: 'any';
        $isSearch = $this->getSearchQuery() !== '';
        $search = $this->search;

        $flexibleContentLayouts = FlexibleContentLayouts::getInstance();
        $fieldGroups = $flexibleContentLayouts->getFieldGroupLayouts();
        $postsWithLayout = new PostsWithLayout();

        $count = $postsWithLayout->getCount($layoutName, $fieldGroups, 'any', $search);
        $href = sprintf(
            admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&layoutName=%s'),
            $layoutName
        );

        $views['any'] = sprintf(
            '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
            esc_url($href),
            $currentPostType === 'any' && !$isSearch ? ' class="current"' : '',
            __('All', 'components-overview-flynt'),
            $count
        );

        $postTypesWithLayouts = $flexibleContentLayouts->getPostTypesWithLayouts();
        foreach ($postTypesWithLayouts as $postType) {
            $postTypeObject = get_post_type_object($postType);
            $count = $postsWithLayout->getCount($layoutName, $fieldGroups, $postType, $search);
            if ($count === 0) {
                continue;
            }

            $href = sprintf(
                admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&layoutName=%s&postType=%s'),
                $layoutName,
                $postType
            );

            $views[$postType] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                esc_url($href),
                $currentPostType === $postType ? ' class="current"' : '',
                $postTypeObject->labels->name,
                $count
            );
        }

        return $views;
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required WP_List_Table override.
    public function column_default($item, $column_name)
    {
        $wpPostsListTable = new WP_Posts_List_Table();

        switch ($column_name) {
            case 'post_title':
                $actions = [
                    'edit' => sprintf(
                        '<a href="%s">%s</a>',
                        get_edit_post_link($item),
                        __('Edit', 'components-overview-flynt')
                    ),
                    'view' => sprintf(
                        '<a href="%s">%s</a>',
                        get_permalink($item),
                        __('View', 'components-overview-flynt')
                    ),
                ];

                $title = $item->post_title;
                echo '<strong>';
                printf(
                    '<a class="row-title" href="%s" aria-label="%s">%s</a>',
                    esc_url(get_edit_post_link($item)),
                    /* translators: %s: Post title. */
                    esc_attr(sprintf(__('&#8220;%s&#8221; (Edit)', 'components-overview-flynt'), $title)),
                    esc_attr($title)
                );
                _post_states($item);
                echo "</strong>\n";

                echo wp_kses_post($this->row_actions($actions));
                return;
            case 'post_type':
                $postTypeObject = get_post_type_object($item->post_type);
                return esc_html($postTypeObject->labels->name);
            case 'post_date':
                return $wpPostsListTable->column_date($item);
            default:
                return '';
        }
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required WP_List_Table override.
    public function get_columns()
    {
        return [
            'post_title' => __('Title', 'components-overview-flynt'),
            'post_type' => __('Post Type', 'components-overview-flynt'),
            'post_date' => __('Date', 'components-overview-flynt')
        ];
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required WP_List_Table override.
    public function get_sortable_columns()
    {
        return [
            'post_title' => ['post_title', false],
            'post_type' => ['post_type', false],
            'post_date' => ['post_date', false]
        ];
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- Required WP_List_Table override.
    public function prepare_items(): void
    {
        $perPage = $this->get_items_per_page('components_overview_posts_per_page');
        $pageNumber = $this->get_pagenum();
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        if ($this->layoutName === '') {
            $this->items = [];
            $this->set_pagination_args([
                'total_items' => 0,
                'per_page' => $perPage,
                'total_pages' => 0,
            ]);
            return;
        }

        $flexibleContentLayouts = FlexibleContentLayouts::getInstance();
        $fieldGroups = $flexibleContentLayouts->getFieldGroupLayouts();

        // Use single query with OR meta_query instead of multiple queries
        $postsWithLayout = new PostsWithLayout();
        $wpQuery = $postsWithLayout->get(
            $this->layoutName,
            $fieldGroups,
            $this->postTypeFilter ?: 'any',
            $perPage,
            $pageNumber,
            $this->orderby,
            $this->order,
            $this->getSearchQuery()
        );

        $this->items = $wpQuery->posts ?? [];

        $this->set_pagination_args([
            'total_items' => (int) $wpQuery->found_posts,
            'per_page'    => (int) $perPage,
            'total_pages' => (int) ceil($wpQuery->found_posts / $perPage),
        ]);
    }
}
