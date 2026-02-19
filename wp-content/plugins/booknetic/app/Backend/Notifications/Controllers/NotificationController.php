<?php

namespace BookneticApp\Backend\Notifications\Controllers;

use BookneticApp\Backend\Notifications\Services\NotificationService;
use BookneticApp\Providers\Core\RestRequest;
use BookneticApp\Providers\IoC\Container;

class NotificationController
{
    private NotificationService $service;

    public function __construct()
    {
        $this->service = Container::get(NotificationService::class);
    }

    public function getAll(RestRequest $request): array
    {
        $page = $request->param('page', 1, RestRequest::TYPE_INTEGER);
        $rowsPerPage = $request->param('rows_count', 10, RestRequest::TYPE_INTEGER);

        $notificationData = $this->service->getNotificationList(get_current_user_id(), $page, $rowsPerPage);

        return [
            'notifications' => $notificationData['data'],
            'count' => isset($notificationData['count']) ? (int)$notificationData['count'] : 0,
        ];
    }

    /**
     * @param RestRequest $request
     * @return array
     */
    public function markAsRead(RestRequest $request): array
    {
        $notificationId = $request->param('notification_id', 0, RestRequest::TYPE_INTEGER);

        $this->service->markAsRead($notificationId, get_current_user_id());

        return [];
    }

    /**
     * @return array
     */
    public function markAllAsRead(): array
    {
        $this->service->markAllAsRead(get_current_user_id());

        return [];
    }

    /**
     * @return array
     */
    public function clear(): array
    {
        $this->service->clear(get_current_user_id());

        return [];
    }
}
