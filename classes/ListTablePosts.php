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

class ListTablePosts extends WP_List_Table
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
        $currentView = isset($_GET['postType']) ? sanitize_text_field(wp_unslash($_GET['postType'])) : 'all';
        $componentName = isset($_GET['componentName']) ? sanitize_text_field(wp_unslash($_GET['componentName'])) : false;
        $views = [];

        $componentCount = PostsWithComponents::getCount($componentName);
        if (!$componentCount) {
            return $views;
        }

        $views['all'] = sprintf(
            '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
            esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&componentName=' . $componentName)),
            $currentView === 'all' ? ' class="current"' : '',
            __('All', 'components-overview-flynt'),
            $componentCount
        );

        $postTypes = PostsWithComponents::getPostTypes($componentName);
        foreach ($postTypes as $postType) {
            $postTypeCount = PostsWithComponents::getCount($componentName, $postType->slug);
            $url = esc_url(sprintf(
                admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&postType=%s' . '&componentName=' . $componentName),
                $postType->slug
            ));
            $views[$postType->label] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                $url,
                $currentView === $postType->slug ? ' class="current"' : '',
                $postType->label,
                $postTypeCount
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
                    'edit' => sprintf('<a href="%s">%s</a>', get_edit_post_link($item->post->ID), __('Edit')),
                    'view' => sprintf('<a href="%s">%s</a>', get_permalink($item->post->ID), __('View')),
                ];
                $title = $item->post->post_title;

                echo '<strong>';
                printf(
                    '<a class="row-title" href="%s" aria-label="%s">%s</a>',
                    esc_url(get_edit_post_link($item->post->ID)),
                    /* translators: %s: Post title. */
                    esc_attr(sprintf(__('&#8220;%s&#8221; (Edit)'), $title)),
                    esc_attr($title)
                );
                _post_states($item->post);
                echo "</strong>\n";

                echo wp_kses_post($this->row_actions($actions));
                return;
            case 'post_type':
                $url = esc_url(sprintf(
                    admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&postType=%s&componentName=%s'),
                    $item->post_type->name,
                    $item->componentName
                ));
                return sprintf(
                    '<a href="%s">%s</a>',
                    $url,
                    $item->post_type->label,
                );
            case 'post_date':
                return $wpPostsListTable->column_date($item->post);
            default:
                return print_r($item, true);
        }
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function get_columns()
    {
        return [
            'post_title'     => __('Title', 'components-overview-flynt'),
            'post_type'  => __('Post Type', 'components-overview-flynt'),
            'post_date' => __('Date', 'components-overview-flynt')
        ];
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function get_sortable_columns()
    {
        return [
            'post_title'     => ['post_title', false],
            'post_type'  => ['post_type', false],
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
        $offset = ($pageNumber - 1) * $perPage;
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        $componentName = !empty($_GET['componentName']) ? sanitize_text_field(wp_unslash($_GET['componentName'])) : false;
        $postType = !empty($_GET['postType']) ? sanitize_text_field(wp_unslash($_GET['postType'])) : false;
        $orderby = !empty($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'post_date';
        $order = !empty($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'desc';
        $search = !empty($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

        $data = PostsWithComponents::get($componentName, $postType, $perPage, $offset, $orderby, $order, $search);

        $this->items = isset($data->items) ? $data->items : [];

        $totalItems = isset($data->totalItems) ? $data->totalItems : 0;
        $this->set_pagination_args([
            'total_items' => $totalItems,
            'per_page'    => $perPage,
            'total_pages' => ceil($totalItems / $perPage)
        ]);
    }
}
