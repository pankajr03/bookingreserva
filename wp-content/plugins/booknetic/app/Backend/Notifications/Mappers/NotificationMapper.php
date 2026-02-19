<?php

namespace BookneticApp\Backend\Notifications\Mappers;

use BookneticApp\Backend\Notifications\DTOs\Response\NotificationResponse;
use BookneticApp\Models\Notification;
use BookneticApp\Providers\DB\Collection;

class NotificationMapper
{
    /**
     * @param Collection|Notification $notification
     * @return NotificationResponse
     */
    public function toResponse(Collection $notification): NotificationResponse
    {
        $notificationResponse = new NotificationResponse();

        $notificationResponse->setId($notification->id);
        $notificationResponse->setUserId($notification->user_id);
        $notificationResponse->setType($notification->type);
        $notificationResponse->setTitle($notification->title);
        $notificationResponse->setMessage($notification->message);
        $notificationResponse->setActionType($notification->action_type);
        $notificationResponse->setActionData($notification->action_data);
        $notificationResponse->setReadAt($notification->read_at);
        $notificationResponse->setCreatedAt($notification->created_at);

        return $notificationResponse;
    }

    /**
     * @param array $data
     * @return array<NotificationResponse>
     */
    public function toListResponse(array $data): array
    {
        return array_map([$this, 'toResponse'], $data);
    }
}
