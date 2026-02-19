<?php

namespace BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvents;

use BookneticApp\Backend\Notifications\Registerer\NotificationWorkflowEvent;

class CustomerBirthdayNotificationWorkflowEvent implements NotificationWorkflowEvent
{
    private string $actionType = 'detail';
    private string $actionUrl = 'customers.info';
    private string $entityName = 'customer_id';
    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function getActionUrl(): string
    {
        return $this->actionUrl;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }
}
