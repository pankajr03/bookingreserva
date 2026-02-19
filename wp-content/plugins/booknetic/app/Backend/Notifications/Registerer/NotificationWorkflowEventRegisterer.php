<?php

namespace BookneticApp\Backend\Notifications\Registerer;

use RuntimeException;

class NotificationWorkflowEventRegisterer
{
    private static array $instances = [];

    /**
     * @param string $eventName
     * @param class-string $instance
     * @return void
     */
    public static function registerEvents(string $eventName, string $instance): void
    {
        $interfaces = class_implements($instance);

        if (!in_array(NotificationWorkflowEvent::class, $interfaces, true)) {
            throw new RuntimeException(bkntc__('Class %s does not implement NotificationWorkflowEvent', $instance));
        }

        self::$instances[$eventName] = $instance;
    }

    public static function getEventInstance(string $eventName): ?NotificationWorkflowEvent
    {
        return isset(self::$instances[$eventName]) ? new self::$instances[$eventName]() : null;
    }
}
