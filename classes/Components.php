<?php

namespace FlyntComponentsOverview;

use Flynt\ComponentManager;

defined('ABSPATH') || exit;

final class Components
{
    public const TRANSIENT_KEY_COMPONENTS = 'flynt_components_overview_components';

    private $components = false;

    private static ?\FlyntComponentsOverview\Components $instance = null;

    public static function getInstance(): Components
    {
        if (!self::$instance instanceof \FlyntComponentsOverview\Components) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getAll(bool $force = false): object
    {
        if (false === $this->components) {
            $this->addComponents($force);
        }

        return (object) $this->components;
    }

    private function addComponents(bool $force): void
    {
        $isCronjobRunning = (bool) get_option(CronJob::OPTION_NAME_CRONJOB_RUNNING);
        if ($isCronjobRunning && false === $force) {
            $this->components = (object) [];
            return;
        }

        $this->components = get_transient(self::TRANSIENT_KEY_COMPONENTS);

        if (false === $this->components || [] === get_object_vars($this->components)) {
            $this->components = (object) [];
            $componentManager = ComponentManager::getInstance();
            $componentManagerAllComponents = $componentManager->getAll();

            foreach ($componentManagerAllComponents as $key => $component) {
                $componentObject = PostsWithComponents::get($key, false, 1);
                if ($componentObject->totalItems > 0) {
                    $this->components->{$key} = [
                        'name' => $key,
                        'postTypes' => PostsWithComponents::getPostTypes($key),
                        'totalItems' => $componentObject->totalItems
                    ];
                }
            }

            set_transient(self::TRANSIENT_KEY_COMPONENTS, $this->components, WEEK_IN_SECONDS);
        }
    }
}
