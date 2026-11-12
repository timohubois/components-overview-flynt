<?php

namespace FlyntComponentsOverview;

use WP_Query;

defined('ABSPATH') || exit;

final class PostsWithLayout
{
    public function get(
        string $layoutName,
        array $fieldGroups,
        string $postType = 'any',
        int $postsPerPage = 20,
        int $page = 1,
        string $orderby = 'post_date',
        string $order = 'desc',
        string $search = ''
    ): WP_Query {

        $offset = ($page - 1) * $postsPerPage;

        // Build OR meta_query for all field groups
        $metaQuery = ['relation' => 'OR'];
        foreach ($fieldGroups as $fieldGroup => $layouts) {
            $metaQuery[] = [
                'key' => $fieldGroup,
                'value' => serialize(strval($layoutName)),
                'compare' => 'LIKE'
            ];
        }

        $args = [
            'post_type' => $this->getPostTypes($postType),
            'posts_per_page' => $postsPerPage,
            'offset' => $offset,
            'paged' => $page,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required serialized ACF layout lookup.
            'meta_query' => $metaQuery,
            'orderby' => in_array($orderby, ['post_date', 'post_title', 'post_type'], true)
                ? [$orderby => $order, 'ID' => $order]
                : $orderby,
            'order' => $order,
            's' => $search
        ];

        // WordPress ignores an empty meta_query; do not turn it into an unfiltered posts query.
        if ($fieldGroups === []) {
            $args['post__in'] = [0];
            $args['no_found_rows'] = true;
        }

        return new WP_Query($args);
    }

    public function getCount(
        string $layoutName,
        array $fieldGroups,
        string $postType,
        ?string $search
    ): int {
        if ($fieldGroups === []) {
            return 0;
        }

        // Build OR meta_query for all field groups
        $metaQuery = ['relation' => 'OR'];
        foreach ($fieldGroups as $fieldGroup => $layouts) {
            $metaQuery[] = [
                'key' => $fieldGroup,
                'value' => serialize(strval($layoutName)),
                'compare' => 'LIKE'
            ];
        }

        $args = [
            'post_type' => $this->getPostTypes($postType),
            'posts_per_page' => 1,
            'fields' => 'ids',
            'orderby' => 'none',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required serialized ACF layout count.
            'meta_query' => $metaQuery,
            's' => $search
        ];

        $query = new WP_Query($args);
        return $query->found_posts;
    }

    public function getQueryContext(string $postType): string
    {
        global $wpdb;

        $query = new WP_Query();
        // Let WordPress apply its query filters, but do not execute or cache this context-only query.
        $skipQuery = static function ($posts, WP_Query $currentQuery) use ($query) {
            if ($currentQuery !== $query) {
                return $posts;
            }
            $currentQuery->set('cache_results', false);
            return [];
        };
        add_filter('posts_pre_query', $skipQuery, PHP_INT_MAX, 2);
        try {
            $query->query([
                'post_type' => $this->getPostTypes($postType),
                'posts_per_page' => 1,
                'fields' => 'ids',
                'orderby' => 'none',
                'no_found_rows' => true,
                'cache_results' => false,
            ]);
        } finally {
            remove_filter('posts_pre_query', $skipQuery, PHP_INT_MAX);
        }

        return $wpdb->remove_placeholder_escape($query->request);
    }

    private function getPostTypes(string $postType): array
    {
        if ($postType === 'any') {
            $flexibleContentLayouts = FlexibleContentLayouts::getInstance();
            $postTypesWithLayouts = $flexibleContentLayouts->getPostTypesWithLayouts();
            return array_values($postTypesWithLayouts);
        }

        return [$postType];
    }
}
