<?php

namespace FlyntComponentsOverview;

use WP_List_Table;

defined('ABSPATH') || exit;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

final class ListTableComponents extends WP_List_Table
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
        $views = [];

        $components = Components::getInstance()->getAll();

        $componentsCount = count(get_object_vars($components));
        $views['all'] = sprintf(
            '<a href="%s"%s>%s <span class="count">(%d)</span></a>',
            esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG)),
            $currentView === 'all' ? ' class="current"' : '',
            __('All', 'components-overview-flynt'),
            $componentsCount
        );

        $postTypes = [];
        foreach ($components as $component) {
            if ($component["postTypes"]) {
                foreach ($component["postTypes"] as $postType) {
                    if (!isset($postTypes[$postType->slug])) {
                        $postTypes[$postType->slug] = $postType;
                        $postTypes[$postType->slug]->count = 1;
                    } else {
                        ++$postTypes[$postType->slug]->count;
                    }
                }
            }
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
                    '<a href="%s"><strong>%s</strong></a> <span class="count">(%d)</span>',
                    $url,
                    __('All: ', 'components-overview-flynt'),
                    $item['totalItems']
                );

                $postTypesLinks = [];
                if (get_object_vars($item['postTypes']) !== []) {
                    $item['postTypes'] = (array) $item['postTypes'];

                    $postTypesLinks = array_map(static function ($postType) use ($item) : string {
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
                }

                array_unshift($postTypesLinks, $totalCount);
                return implode(' | ', $postTypesLinks);
            default:
                return print_r($item, true);
        }
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function get_columns()
    {
        return [
            'name'     => __('Component', 'components-overview-flynt'),
            'results'  => __('Results', 'components-overview-flynt')
        ];
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function get_sortable_columns()
    {
        return [
            'name'     => ['name', false]
        ];
    }

    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps -- WordPress method name
    public function prepare_items(): void
    {
        $perPage = $this->get_items_per_page('components_overview_posts_per_page', 20);
        $pageNumber = $this->get_pagenum();
        $offset = ($pageNumber - 1) * $perPage;
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $data = get_object_vars(Components::getInstance()->getAll());

        $postType = empty($_GET['postType']) ? false : sanitize_text_field(wp_unslash($_GET['postType']));
        $orderby = empty($_GET['orderby']) ? 'name' : sanitize_text_field(wp_unslash($_GET['orderby']));
        $order = empty($_GET['order']) ? 'asc' : sanitize_text_field(wp_unslash($_GET['order']));
        $search = empty($_GET['s']) ? '' : sanitize_text_field(wp_unslash($_GET['s']));

        if (!empty($search)) {
            $data = array_filter($data, static function (array $item) use ($search) : bool {
                return str_contains(strtolower($item['name']), strtolower($search));
            });
        }

        if ($postType) {
            $data = array_filter($data, static function (array $component) use ($postType) : bool {
                if ($component["postTypes"]) {
                    foreach ($component["postTypes"] as $postTypeItem) {
                        if ($postTypeItem->slug === $postType) {
                            return true;
                        }
                    }
                }
                
                return false;
            });
        }

        if ($orderby) {
            usort($data, static function (array $a, array $b) use ($orderby, $order) : int {
                $result = strcmp($a[$orderby], $b[$orderby]);
                return ($order === 'asc') ? $result : -$result;
            });
        }

        $totalItems = count($data);
        $totalPages = ceil($totalItems / $perPage);

        $data = array_slice($data, $offset, $perPage);

        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = $data;

        $this->set_pagination_args([
            'total_items' => $totalItems,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ]);
    }
}
