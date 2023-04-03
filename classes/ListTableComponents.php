<?php

namespace FlyntComponentsOverview;

use Flynt\ComponentManager;
use WP_List_Table;

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class ListTableComponents extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => __('Flynt Component Overview', 'flynt-components-overview'),
            'plural'    => __('Flynt Components Overview', 'flynt-components-overview'),
            'ajax'      => false
        ]);
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function get_views()
    {
        $currentView = isset($_GET['postType']) ? sanitize_text_field(wp_unslash($_GET['postType'])) : 'all';
        $views = [];

        $components = Components::getInstance()->getAll();

        $componentsCount = count(get_object_vars($components));
        $views['all'] = sprintf(
            '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
            esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG)),
            $currentView === 'all' ? ' class="current"' : '',
            __('All', 'flynt-components-overview'),
            $componentsCount
        );

        $postTypes = get_transient(PLUGIN::TRANSIENT_KEY_COMPONENTS_POST_TYPES);
        if (false === $postTypes || count($postTypes) === 0) {
            $postTypes = [];
            foreach ($components as $component) {
                if ($component["postTypes"]) {
                    foreach ($component["postTypes"] as $postType) {
                        if (!isset($postTypes[$postType->slug])) {
                            $postTypes[$postType->slug] = $postType;
                            $postTypes[$postType->slug]->count = 1;
                        } else {
                            $postTypes[$postType->slug]->count++;
                        }
                    }
                }
            }
            set_transient(PLUGIN::TRANSIENT_KEY_COMPONENTS_POST_TYPES, $postTypes, YEAR_IN_SECONDS);
        }

        foreach ($postTypes as $postType) {
            $url = esc_url(sprintf(
                admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&postType=%s'),
                $postType->slug
            ));
            $views[$postType->label] = sprintf(
                '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
                $url,
                $currentView === $postType->slug ? ' class="current"' : '',
                $postType->label,
                $postType->count
            );
        }

        return $views;
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'name':
                $postType = isset($_GET['postType']) ? sanitize_text_field(wp_unslash($_GET['postType'])) : 'all';

                if ($postType !== 'all') {
                    $url = esc_url(sprintf(
                        admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&postType=%s&componentName=%s'),
                        $postType,
                        $item['name']
                    ));
                } else {
                    $url = esc_url(sprintf(
                        admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&componentName=%s'),
                        $item['name']
                    ));
                }
                return sprintf(
                    '<a href="%s"><strong>%s</strong></a>',
                    $url,
                    $item['name']
                );
            case 'results':
                $url = esc_url(sprintf(
                    admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&componentName=%s'),
                    $item['name']
                ));

                $totalCount = sprintf(
                    '<a href="%s"><strong>%s</strong></a> <span class="count">(%d)</span> | ',
                    $url,
                    __('All: ', 'flynt-components-overview'),
                    $item['totalItems']
                );

                if (!is_array($item['postTypes'])) {
                    $item['postTypes'] = (array) $item['postTypes'];
                }
                $postTypesLinks = array_map(function ($postType) use ($item) {
                    $url = esc_url(sprintf(
                        admin_url('admin.php?page=' . AdminMenu::MENU_SLUG . '&postType=%s&componentName=%s'),
                        $postType->slug,
                        $item['name']
                    ));
                    return sprintf(
                        '<a href="%s">%s</a> <span class="count">(%d)</span>',
                        $url,
                        $postType->label,
                        $postType->totalItems
                    );
                }, $item['postTypes']);
                return $totalCount . implode(' | ', $postTypesLinks);
            default:
                return print_r($item, true);
        }
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function get_columns()
    {
        $columns = [
            'name'     => __('Component', 'flynt-components-overview'),
            'results'  => __('Results', 'flynt-components-overview')
        ];
        return $columns;
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function get_sortable_columns()
    {
        $sortable_columns = [
            'name'     => ['name', false]
        ];
        return $sortable_columns;
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function prepare_items()
    {
        $perPage = get_user_meta(get_current_user_id(), 'components_overview_posts_per_page', true)
            ? (int) get_user_meta(get_current_user_id(), 'components_overview_posts_per_page', true)
            : 20;
        $pageNumber = $this->get_pagenum();
        $offset = ($pageNumber - 1) * $perPage;
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $data = get_object_vars(Components::getInstance()->getAll());
        $totalItems = count($data);

        $postType = !empty($_GET['postType']) ? sanitize_text_field(wp_unslash($_GET['postType'])) : false;
        $orderby = !empty($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : 'name';
        $order = !empty($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'asc';
        $search = !empty($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

        if (!empty($search)) {
            $data = array_filter($data, function ($item) use ($search) {
                return strpos(strtolower($item['name']), strtolower($search)) !== false;
            });
        }

        if ($postType) {
            $data = array_filter($data, function ($component) use ($postType) {
                if ($component["postTypes"]) {
                    foreach ($component["postTypes"] as $postTypeItem) {
                        if ($postTypeItem->slug === $postType) {
                            return true;
                        }
                    }
                }
                return false;
            });
            $totalItems = count($data);
        }

        if ($orderby) {
            usort($data, function ($a, $b) use ($orderby, $order) {
                $result = strcmp($a[$orderby], $b[$orderby]);
                return ($order === 'asc') ? $result : -$result;
            });
        }

        $data = array_slice($data, $offset, $perPage);

        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = $data;

        $this->set_pagination_args([
            'total_items' => $totalItems,
            'per_page' => $perPage,
            'total_pages' => ceil($totalItems / $perPage),
        ]);
    }
}
