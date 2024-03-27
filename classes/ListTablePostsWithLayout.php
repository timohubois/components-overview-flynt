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
    public function __construct()
    {
        parent::__construct([
            'singular' => __('Component Overview for Flynt', 'components-overview-flynt'),
            'plural'    => __('Components Overview for Flynt', 'components-overview-flynt'),
            'ajax'      => false
        ]);
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function get_views()
    {
        $layoutName = isset($_GET['layoutName']) ? sanitize_text_field(wp_unslash($_GET['layoutName'])) : false;
        $currentPostType = isset($_GET['postType']) ? sanitize_text_field(wp_unslash($_GET['postType'])) : 'any';
        $isSearch = isset($_GET['s']) && !empty($_GET['s']);
        $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : null;

        $flexibleContentLayouts = FlexibleContentLayouts::getInstance();
        $postsWithLayout = new PostsWithLayout();

        $count = $postsWithLayout->getCount($layoutName, null, 'any', $search);
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
            $count = $postsWithLayout->getCount($layoutName, null, $postType, $search);
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

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function column_default($item, $column_name)
    {
        $wpPostsListTable = new WP_Posts_List_Table();

        switch ($column_name) {
            case 'post_title':
                $actions = [
                    'edit' => sprintf('<a href="%s">%s</a>', get_edit_post_link($item), __('Edit')),
                    'view' => sprintf('<a href="%s">%s</a>', get_permalink($item), __('View')),
                ];

                $title = $item->post_title;
                echo '<strong>';
                printf(
                    '<a class="row-title" href="%s" aria-label="%s">%s</a>',
                    esc_url(get_edit_post_link($item)),
                    /* translators: %s: Post title. */
                    esc_attr(sprintf(__('&#8220;%s&#8221; (Edit)'), $title)),
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
                return print_r($item, true);
        }
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function get_columns()
    {
        return [
            'post_title' => __('Title', 'components-overview-flynt'),
            'post_type' => __('Post Type', 'components-overview-flynt'),
            'post_date' => __('Date', 'components-overview-flynt')
        ];
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function get_sortable_columns()
    {
        return [
            'post_title' => ['post_title', false],
            'post_type' => ['post_type', false],
            'post_date' => ['post_date', false]
        ];
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function prepare_items(): void
    {
        $perPage = get_user_meta(get_current_user_id(), 'components_overview_posts_per_page', true)
            ? (int) get_user_meta(get_current_user_id(), 'components_overview_posts_per_page', true)
            : 20;
        $pageNumber = $this->get_pagenum();
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        $layoutName = empty($_GET['layoutName']) ? false : sanitize_text_field(wp_unslash($_GET['layoutName']));
        $postType = empty($_GET['postType']) ? 'any' : sanitize_text_field(wp_unslash($_GET['postType']));
        $orderby = empty($_GET['orderby']) ? 'post_date' : sanitize_text_field(wp_unslash($_GET['orderby']));
        $order = empty($_GET['order']) ? 'DESC' : sanitize_text_field(wp_unslash($_GET['order']));
        $search = empty($_GET['s']) ? '' : sanitize_text_field(wp_unslash($_GET['s']));

        $flexibleContentLayouts = FlexibleContentLayouts::getInstance();
        $fieldGroups = $flexibleContentLayouts->getFieldGroupLayouts();
        $data = [];
        $totalItems = 0;
        foreach ($fieldGroups as $fieldGroup => $layouts) {
            $postsWithLayout = new PostsWithLayout();
            $wpQuery = $postsWithLayout->get(
                $layoutName,
                $fieldGroup,
                $postType,
                $perPage,
                $pageNumber,
                $orderby,
                $order,
                $search
            );

            $posts = $wpQuery->posts;

            $data = [
                ...$data,
                ...$posts
            ];

            $totalItems += $wpQuery->found_posts;
        }

        usort($data, function ($a, $b) use ($orderby, $order) {
            if ($a->$orderby == $b->$orderby) {
                return 0;
            }

            if ($order === 'asc') {
                return ($a->$orderby < $b->$orderby) ? -1 : 1;
            } else {
                return ($a->$orderby < $b->$orderby) ? 1 : -1;
            }
        });

        $this->items = $data ?? [];

        $this->set_pagination_args([
            'total_items' => $totalItems,
            'per_page'    => $perPage,
            'total_pages' => ceil($totalItems / $perPage)
        ]);
    }
}
