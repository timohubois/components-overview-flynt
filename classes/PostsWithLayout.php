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
            'meta_query' => $metaQuery,
            'orderby' => $orderby,
            'order' => $order,
            's' => $search
        ];

        return new WP_Query($args);
    }

    public function getCount(
        string $layoutName,
        array $fieldGroups,
        string $postType,
        ?string $search
    ): int {
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
            'meta_query' => $metaQuery,
            's' => $search
        ];

        $query = new WP_Query($args);
        return $query->found_posts;
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
