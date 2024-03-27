<?php

namespace FlyntComponentsOverview;

use WP_Query;

defined('ABSPATH') || exit;

final class PostsWithLayout
{
    public function get(
        string $layoutName,
        string $fieldGroup,
        string $postType = 'any',
        int $postsPerPage = 20,
        int $page = 1,
        string $orderby = 'post_date',
        string $order = 'desc',
        string $search = ''
    ): WP_Query {

        $offset = ($page - 1) * $postsPerPage;

        $args = [
            'post_type' => $this->getPostTypes($postType),
            'posts_per_page' => $postsPerPage,
            'offset' => $offset,
            'paged' => $page,
            'meta_query' => [
                [
                    'key' => $fieldGroup,
                    'value' => serialize(strval($layoutName)),
                    'compare' => 'LIKE'
                ]
            ],
            'orderby' => $orderby,
            'order' => $order,
            's' => $search
        ];

        return new WP_Query($args);
    }

    public function getCount(
        string $layoutName,
        ?string $fieldGroup,
        string $postType,
        ?string $search,
    ): int {
        $count = 0;

        if ($fieldGroup === null) {
            $flexibleContentLayouts = FlexibleContentLayouts::getInstance();
            $fieldGroups = $flexibleContentLayouts->getFieldGroupLayouts();

            foreach ($fieldGroups as $fieldGroup => $layouts) {
                $count += $this->getCountForFieldGroup($layoutName, $fieldGroup, $postType, $search);
            }
        } else {
            $count = $this->getCountForFieldGroup($layoutName, $fieldGroup, $postType, $search);
        }

        return $count;
    }

    private function getCountForFieldGroup(
        string $layoutName,
        string $fieldGroup,
        string $postType,
        ?string $search,
    ): int {
        $args = [
            'post_type' => $this->getPostTypes($postType),
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_query' => [
                [
                    'key' => $fieldGroup,
                    'value' => serialize(strval($layoutName)),
                    'compare' => 'LIKE'
                ]
            ],
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
