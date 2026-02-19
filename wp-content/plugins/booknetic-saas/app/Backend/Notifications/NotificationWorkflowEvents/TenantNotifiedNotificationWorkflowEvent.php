<?php

namespace BookneticSaaS\Backend\Notifications\NotificationWorkflowEvents;

use BookneticSaaS\Backend\Notifications\Registerer\NotificationWorkflowEvent;

class TenantNotifiedNotificationWorkflowEvent implements NotificationWorkflowEvent
{
    private string  $actionType = 'detail';
    private string $entityName = 'tenant_id';
    private string $url = 'tenants.add_new';

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
