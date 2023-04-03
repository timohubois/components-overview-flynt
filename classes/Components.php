<?php

namespace FlyntComponentsOverview;

use Flynt\ComponentManager;

defined('ABSPATH') || exit;

class Components
{
    protected $components;
    protected static $instance = null;

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getAll(): object
    {
        if (null === $this->components) {
            $this->addComponents();
        }
        return (object) $this->components;
    }

    private function addComponents(): void
    {
        $this->components = get_transient(PLUGIN::TRANSIENT_KEY_COMPONENTS);

        if (false === $this->components || count(get_object_vars($this->components)) === 0) {
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
            set_transient(PLUGIN::TRANSIENT_KEY_COMPONENTS, $this->components, YEAR_IN_SECONDS);
        }
    }
}
