<?php

namespace BookneticApp\Backend\Notifications\Registerer;

interface NotificationWorkflowEvent
{
    public function getActionType(): string;
    public function getActionUrl(): string;
    public function getEntityName(): string;
}
