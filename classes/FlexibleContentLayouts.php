<?php

namespace FlyntComponentsOverview;

use WP_Query;

use const Crontrol\TRANSIENT;

defined('ABSPATH') || exit;

final class FlexibleContentLayouts
{
    private array $postTypesWithLayouts = [];
    private array $fieldGroups = [];
    private array $layouts = [];
    private static ?FlexibleContentLayouts $instance = null;

    const TRANSIENT_BASE_NAME = 'flynt_components_overview_flexibleContentLayouts_';
    const TRANSIENT_EXPIRATION = WEEK_IN_SECONDS;

    public static function init(): void
    {
        self::getInstance();
    }

    public static function getInstance(): FlexibleContentLayouts
    {
        if (!self::$instance instanceof FlexibleContentLayouts) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getPostTypesWithLayouts(): array
    {
        if ([] === $this->postTypesWithLayouts) {
            $this->registerPostTypesFromACFFieldGroups();
        }

        return $this->postTypesWithLayouts;
    }

    public function getLayoutsFromPostType(string $postType = 'any'): array
    {
        $transientKey = self::TRANSIENT_BASE_NAME . $postType;

        if (false !== ($postTypeLayouts = get_transient($transientKey))) {
            return $postTypeLayouts;
        }

        $fieldGroupsLayouts = $this->getFieldGroupLayouts();

        $postTypeLayouts = [];
        foreach ($fieldGroupsLayouts as $fieldGroup => $layouts) {
            foreach ($layouts as $layout) {
                $postsWithLayout = new PostsWithLayout();
                $count = $postsWithLayout->getCount($layout['name'], $fieldGroup, $postType, null);

                if ($count > 0) {
                    $postTypeLayouts[$layout['name']] = $layout;
                }
            }
        }

        set_transient($transientKey, $postTypeLayouts, self::TRANSIENT_EXPIRATION);

        return $postTypeLayouts;
    }

    public function getFieldGroupLayouts(): array
    {
        if ([] === $this->fieldGroups) {
            $this->registerFieldGroups();
        }

        return $this->fieldGroups;
    }

    public function getLayouts(?string $postType = 'any', ?int $perPage = 0, ?int $pageNumber = 0): array
    {
        $this->layouts = $this->getLayoutsFromPostType($postType ?? 'any');

        if ($perPage > 0 && $pageNumber > 0) {
            $offset = ($pageNumber - 1) * $perPage;
            return array_slice($this->layouts, $offset, $perPage);
        }

        return $this->layouts;
    }

    public function getLayoutsCount(?string $postType = 'any'): int
    {
        return count($this->getLayouts($postType));
    }

    private function registerPostTypesFromAcfFieldGroups(): void
    {
        $postTypes = [];
        $acfFieldGroups = acf_get_field_groups();

        foreach ($acfFieldGroups as $fieldGroup) {
            foreach ($fieldGroup['location'] as $locationGroup) {
                foreach ($locationGroup as $rule) {
                    if ($rule['param'] === 'post_type' && $rule['operator'] === '==') {
                        $postTypes[] = $rule['value'];
                    }
                }
            }
        }

        $this->postTypesWithLayouts = array_unique($postTypes) ?: [];
    }

    private function registerFieldGroups(): void
    {
        $fieldGroups = acf_get_field_groups();

        foreach ($fieldGroups as $fieldGroup) {
            if (!isset($fieldGroup['name'])) {
                continue;
            }

            $fields = acf_get_fields($fieldGroup['key']);
            foreach ($fields as $field) {
                if ($field['type'] === 'flexible_content') {
                    foreach ($field['layouts'] as $layout) {
                        $this->fieldGroups[$fieldGroup['name']][] = $layout;
                    }
                }
            }
        }
    }

    public function deleteTransients(): void
    {
        $postTypes = $this->getPostTypesWithLayouts();

        foreach ($postTypes as $postType) {
            delete_transient(self::TRANSIENT_BASE_NAME . $postType);
        }
    }
}
