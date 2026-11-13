<?php

namespace FlyntComponentsOverview;

defined('ABSPATH') || exit;

final class FlexibleContentLayouts
{
    private array $postTypesWithLayouts = [];
    private array $fieldGroups = [];
    private array $postTypeLayouts = [];
    private ?string $cacheGeneration = null;
    private static ?FlexibleContentLayouts $instance = null;

    public const TRANSIENT_BASE_NAME = 'flynt_components_overview_flexibleContentLayouts_';
    public const TRANSIENT_EXPIRATION = WEEK_IN_SECONDS;

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

    public function getCachedLayoutsFromPostType(string $postType = 'any'): ?array
    {
        return $this->loadLayouts($postType, false);
    }

    public function getLayoutsFromPostType(string $postType = 'any'): array
    {
        return $this->loadLayouts($postType, true) ?? [];
    }

    private function loadLayouts(string $postType, bool $rebuild): ?array
    {
        $postsWithLayout = new PostsWithLayout();
        if ($this->cacheGeneration === null) {
            $generationKey = self::TRANSIENT_BASE_NAME . '.generation';
            $generation = get_transient($generationKey);
            if (!is_string($generation) || $generation === '') {
                if (!$rebuild) {
                    return null;
                }
                $generation = wp_generate_uuid4();
                set_transient($generationKey, $generation, self::TRANSIENT_EXPIRATION);
            }
            $this->cacheGeneration = $generation;
        }

        // Use WordPress's filtered query and UI locale, without a language-plugin dependency.
        $context = md5(serialize([
            $postsWithLayout->getQueryContext($postType),
            determine_locale(),
            $this->cacheGeneration,
        ]));
        $transientKey = self::TRANSIENT_BASE_NAME . $postType . '.' . $context;

        if (array_key_exists($transientKey, $this->postTypeLayouts)) {
            return $this->postTypeLayouts[$transientKey];
        }
        $postTypeLayouts = get_transient($transientKey);
        if (is_array($postTypeLayouts)) {
            $this->postTypeLayouts[$transientKey] = $postTypeLayouts;
            return $postTypeLayouts;
        }

        if (!$rebuild) {
            return null;
        }

        $fieldGroupsLayouts = $this->getFieldGroupLayouts();
        $postTypeLayouts = [];
        foreach ($fieldGroupsLayouts as $fieldGroup => $layouts) {
            foreach ($layouts as $layout) {
                // Pass single field group as array
                $count = $postsWithLayout->getCount($layout['name'], [$fieldGroup => $layouts], $postType, null);

                if ($count > 0) {
                    $postTypeLayouts[$layout['name']] = $layout;
                }
            }
        }

        set_transient($transientKey, $postTypeLayouts, self::TRANSIENT_EXPIRATION);
        $this->postTypeLayouts[$transientKey] = $postTypeLayouts;

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
        $layouts = $this->getLayoutsFromPostType($postType ?? 'any');

        if ($perPage > 0 && $pageNumber > 0) {
            $offset = ($pageNumber - 1) * $perPage;
            return array_slice($layouts, $offset, $perPage);
        }

        return $layouts;
    }

    public function getLayoutsCount(?string $postType = 'any'): int
    {
        return count($this->getLayouts($postType));
    }

    private function registerPostTypesFromAcfFieldGroups(): void
    {
        $postTypes = [];
        $acfFieldGroups = acf_get_field_groups();
        $allPostTypes = get_post_types(['public' => true], 'names');

        foreach ($acfFieldGroups as $fieldGroup) {
            if (!isset($fieldGroup['location'])) {
                continue;
            }
            foreach ($allPostTypes as $postType) {
                if ($this->fieldGroupAppliesToPostType($fieldGroup['location'], $postType)) {
                    $postTypes[] = $postType;
                }
            }
        }
        $this->postTypesWithLayouts = array_unique($postTypes) ?: [];
    }

    private function fieldGroupAppliesToPostType(array $location, string $postType): bool
    {
        foreach ($location as $group) { // OR between groups
            $groupMatch = true;
            foreach ($group as $rule) { // AND within group
                if ($rule['param'] === 'post_type') {
                    if ($rule['operator'] === '==') {
                        if ($postType !== $rule['value']) {
                            $groupMatch = false;
                            break;
                        }
                    } elseif ($rule['operator'] === '!=') {
                        if ($postType === $rule['value']) {
                            $groupMatch = false;
                            break;
                        }
                    }
                }
            }
            if ($groupMatch) {
                return true;
            }
        }
        return false;
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
        // Expire every query context together; old entries retain their normal transient expiry.
        delete_transient(self::TRANSIENT_BASE_NAME . '.generation');
        $this->cacheGeneration = null;
        $this->postTypeLayouts = [];

        // Also remove the legacy, context-free entries.
        $postTypes = $this->getPostTypesWithLayouts();

        foreach ($postTypes as $postType) {
            delete_transient(self::TRANSIENT_BASE_NAME . $postType);
        }

        delete_transient(self::TRANSIENT_BASE_NAME . 'any');
    }
}
