<?php

namespace BookneticSaaS\Backend\Notifications\NotificationWorkflowEvents;

use BookneticSaaS\Backend\Notifications\Registerer\NotificationWorkflowEvent;

class TenantDeleteNotificationWorkflowEvent implements NotificationWorkflowEvent
{
    private string  $actionType = 'noClick';
    private string $entityName = '';
    private string $url = '';

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function getActionUrl(): string
    {
        return $this->url;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }
}
