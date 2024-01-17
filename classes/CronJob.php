<?php

namespace FlyntComponentsOverview;

use FlyntComponentsOverview\Components;

defined('ABSPATH') || exit;

class CronJob
{
    public const OPTION_NAME_CRONJOB_RUN_ASAP = 'flynt_components_overview_cronjob_asap';
    public const OPTION_NAME_CRONJOB_RUN_ASAP_PLANNED = 'flynt_components_overview_cronjob_asap_planned';
    public const OPTION_NAME_CRONJOB_RUNNING = 'flynt_components_overview_cronjob_running';

    public $hook = 'flynt_components_update_transients';
    protected static $instance;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function init(): void
    {
        $cronjob = self::getInstance();
        $runCronjobAsap = (bool) get_option(CronJob::OPTION_NAME_CRONJOB_RUN_ASAP);

        if (false === $runCronjobAsap) {
            $cronjob->register();
            return;
        }

        $cronjob->registerAsap();
        add_option(CronJob::OPTION_NAME_CRONJOB_RUN_ASAP_PLANNED, true);
    }

    public function __construct()
    {
        add_action($this->hook, [$this, 'run']);
    }

    public function run(): void
    {
        add_option(CronJob::OPTION_NAME_CRONJOB_RUNNING, true);

        delete_transient(Components::TRANSIENT_KEY_COMPONENTS);
        Components::getInstance()->getAll(true);

        delete_option(CronJob::OPTION_NAME_CRONJOB_RUN_ASAP);
        delete_option(CronJob::OPTION_NAME_CRONJOB_RUN_ASAP_PLANNED);
        delete_option(CronJob::OPTION_NAME_CRONJOB_RUNNING);
    }

    public function register(): void
    {
        if (!wp_next_scheduled($this->hook)) {
            wp_schedule_event(time(), 'weekly', $this->hook);
        }
    }

    public function registerAsap(): void
    {
        if (wp_next_scheduled($this->hook)) {
            $this->unregister();
        }

        if (!wp_next_scheduled($this->hook)) {
            $now = time();
            $timestamp = mktime(
                gmdate('H', $now),
                gmdate('i', $now),
                gmdate('s', $now) + 30,
                gmdate('n', $now),
                gmdate('j', $now),
                gmdate('Y', $now)
            );
            wp_schedule_single_event($timestamp, $this->hook);
        }
    }

    public function unregister(): void
    {
        wp_clear_scheduled_hook($this->hook);

        delete_option(CronJob::OPTION_NAME_CRONJOB_RUN_ASAP);
        delete_option(CronJob::OPTION_NAME_CRONJOB_RUN_ASAP_PLANNED);
        delete_option(CronJob::OPTION_NAME_CRONJOB_RUNNING);
    }
}
