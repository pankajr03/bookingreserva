<?php

namespace BookneticApp\Backend\Notifications\Services;

use BookneticApp\Backend\Notifications\DTOs\Request\NotificationRequest;
use BookneticApp\Backend\Notifications\Mappers\NotificationMapper;
use BookneticApp\Backend\Notifications\Repositories\NotificationRepository;

class NotificationService
{
    private NotificationRepository $repository;

    public function __construct(NotificationRepository $repository)
    {
        $this->repository = $repository;
    }
    public function getNotificationList(int $userId, int $page, int $rowsPerPage): array
    {
        $notificationData = $this->repository->getAllByUserId($userId, $page, $rowsPerPage);

        $count = $notificationData['count'];
        $notifications = $notificationData['data'];

        $notifications = (new NotificationMapper())->toListResponse($notifications);

        array_map(function ($notification) {
            $user = get_userdata($notification->getUserId());
            $notification->setUserLogin($user->user_login ?? null);
        }, $notifications);

        return [
            'count' => $count,
            'data' => $notifications
        ];
    }

    /**
     * @param int $notificationId
     * @param int $userId
     * @return void
     */
    public function markAsRead(int $notificationId, int $userId): void
    {
        if ($notificationId <= 0) {
            throw new \RuntimeException(bkntc__('Invalid notification ID'));
        }

        $notification = $this->repository->getByIdAndUserId($userId, $notificationId);

        if (!$notification) {
            throw new \RuntimeException(bkntc__('Notification not found'));
        }

        $this->repository->markAsRead($notificationId);
    }

    /**
     * @param int $userId
     * @return void
     */
    public function markAllAsRead(int $userId): void
    {
        $this->repository->updateByUserId($userId);
    }

    /**
     * @param int $userId
     * @return void
     */
    public function clear(int $userId): void
    {
        $this->repository->deleteByUserId($userId);
    }

    public function create(NotificationRequest $request): void
    {
        $data = [
            'user_id' => $request->getUserId(),
            'type' => $request->getType(),
            'title' => $request->getTitle(),
            'message' => $request->getMessage(),
            'action_type' => $request->getActionType(),
            'action_data' => $request->getActionData(),
        ];

        $this->repository->create($data);
    }
}
