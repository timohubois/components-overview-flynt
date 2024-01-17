<?php

namespace FlyntComponentsOverview;

defined('ABSPATH') || exit;

final class PostsWithComponents
{
    public static function get(
        string|bool $componentName = false,
        string|array|bool $postType = false,
        int $limit = -1,
        int $offset = 0,
        string $orderby = 'post_date',
        string $order = 'desc',
        string $search = ''
    ): object {
        if (!$componentName) {
            return (object) [];
        }

        if (is_multisite()) {
            $currentBlogId = get_current_blog_id();
            switch_to_blog($currentBlogId);
        }

        global $wpdb;

        $postTypeClause = '';
        if ($postType) {
            $postTypeClause = $wpdb->prepare(
                sprintf('AND %s.post_type = %%s ', $wpdb->posts),
                $postType
            );
        }

        $searchClause = '';
        if ($search !== '' && $search !== '0') {
            $searchClause = $wpdb->prepare(
                sprintf('AND (%s.post_title LIKE %%s OR %s.post_content LIKE %%s)', $wpdb->posts, $wpdb->posts),
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            );
        }

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $wpdb->prepare() is used above
        $sql = $wpdb->prepare(
            "SELECT {$wpdb->posts}.ID, {$wpdb->posts}.post_type
                FROM {$wpdb->posts}
                LEFT JOIN {$wpdb->postmeta} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                WHERE {$wpdb->posts}.post_status = 'publish'
                AND {$wpdb->postmeta}.meta_value LIKE %s
                AND {$wpdb->postmeta}.meta_value REGEXP '^[a]:.*[;}]\$' -- Check if meta_value is serialized
                {$postTypeClause}
                {$searchClause}
                GROUP BY {$wpdb->posts}.ID
                ORDER BY {$orderby} {$order}
                LIMIT %d, %d",
            '%' . $wpdb->esc_like($componentName) . '%',
            $offset,
            $limit
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- It’s prepared above
        $results = $wpdb->get_results($sql, OBJECT);

        $results = array_map(static function ($value) use ($componentName) {
            $value->componentName = $componentName;
            $value->post_type = get_post_type_object($value->post_type);
            $value->post = get_post($value->ID);
            return $value;
        }, $results);

        if (is_multisite()) {
            restore_current_blog();
        }

        return (object) [
            'items' => $results,
            'totalItems' => self::getCount($componentName, $postType)
        ];
    }

    public static function getCount(string|bool $componentName = false, string|array|bool $postType = false): int
    {
        if (!$componentName) {
            return 0;
        }

        if (is_multisite()) {
            $currentBlogId = get_current_blog_id();
            switch_to_blog($currentBlogId);
        }

        global $wpdb;

        $postTypeClause = '';
        if ($postType) {
            $postTypeClause = $wpdb->prepare(
                sprintf('AND %s.post_type = %%s ', $wpdb->posts),
                $postType
            );
        }

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $wpdb->prepare() is used above
        $sql = $wpdb->prepare(
            "SELECT COUNT(DISTINCT {$wpdb->posts}.ID)
                FROM {$wpdb->posts}
                LEFT JOIN {$wpdb->postmeta} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                WHERE {$wpdb->posts}.post_status = 'publish'
                AND {$wpdb->postmeta}.meta_value LIKE %s
                AND {$wpdb->postmeta}.meta_value REGEXP '^[a]:.*[;}]\$' -- Check if meta_value is serialized
                {$postTypeClause}",
            '%' . $wpdb->esc_like($componentName) . '%'
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $wpdb->prepare()

        if (is_multisite()) {
            restore_current_blog();
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- It’s prepared above
        return $wpdb->get_var($sql) ?? 0;
    }

    public static function getPostTypes(string|bool $componentName = false): object
    {
        if (!$componentName) {
            return (object) [];
        }

        if (is_multisite()) {
            $currentBlogId = get_current_blog_id();
            switch_to_blog($currentBlogId);
        }

        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT DISTINCT {$wpdb->posts}.post_type
                FROM {$wpdb->posts}
                LEFT JOIN {$wpdb->postmeta} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                WHERE {$wpdb->posts}.post_status = 'publish'
                AND {$wpdb->postmeta}.meta_value LIKE %s
                AND {$wpdb->postmeta}.meta_value REGEXP '^[a]:.*[;}]\$' -- Check if meta_value is serialized
                ",
            '%' . $wpdb->esc_like($componentName) . '%',
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- It’s prepared above
        $results = $wpdb->get_results($sql, OBJECT);

        $postTypes = array_map(static function ($value) use ($componentName) {
            return (object) [
                'label' => get_post_type_object($value->post_type)->label,
                'slug' => $value->post_type,
                'totalItems' => self::getCount($componentName, $value->post_type),
            ];
        }, $results);

        if (is_multisite()) {
            restore_current_blog();
        }

        return (object) $postTypes;
    }
}
