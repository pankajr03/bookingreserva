<?php

namespace BookneticApp\Providers\Core;

use BookneticApp\Backend\Appointments\Helpers\ReminderService;
use BookneticApp\Providers\Core\Tasks\Abstracts\TaskInterface;
use BookneticApp\Providers\Helpers\BackgrouondProcess;
use BookneticApp\Providers\Helpers\Helper;
use BookneticApp\Providers\IoC\Container;

class CronJob
{
    private static BackgrouondProcess $backgroundProcess;

    public static function init()
    {
        self::$backgroundProcess = new BackgrouondProcess();

        if (! Helper::processRuntimeController('cron_job', 60)) {
            return;
        }

        if (defined('DOING_CRON')) {
            self::runTasks();
        } elseif (!self::isThisProcessBackgroundTask()) {
            self::$backgroundProcess->dispatch();
        }
    }

    public static function isThisProcessBackgroundTask(): bool
    {
        $action = Helper::_get('action', '', 'string');

        return $action === self::$backgroundProcess->getAction();
    }

    public static function runTasks(): void
    {
        do_action('bkntc_cronjob');
        $reminderService = new ReminderService();

        $reminderService->run();

        $tasks = Container::getAll(TaskInterface::class);

        foreach ($tasks as $task) {
            if (! $task->canExecute()) {
                continue;
            }

            $task->execute();
        }
    }
}
