<?php

namespace BookneticApp\Backend\Notifications\Repositories;

use BookneticApp\Models\Notification;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\Helpers\Date;

class NotificationRepository
{
    /**
     * @param int $userId
     * @param int $page
     * @param int $rowsPerPage
     * @return array
     */
    public function getAllByUserId(int $userId, int $page = 0, int $rowsPerPage = 0): array
    {
        $query = Notification::query()
            ->where('user_id', $userId)
            ->orderBy('id DESC');

        $count = $query->count();

        if (!empty($page)) {
            $query->offset(($page - 1) * $rowsPerPage);
        }

        if (!empty($rowsPerPage)) {
            $query->limit($rowsPerPage);
        }

        $notifications = $query->fetchAll();

        return [
            'data' => $notifications,
            'count' => $count
        ];
    }

    /**
     * @param int $userId
     * @param int $notificationId
     * @return Collection|Notification|null
     */
    public function getByIdAndUserId(int $userId, int $notificationId): ?Collection
    {
        return Notification::query()
            ->where('user_id', $userId)
            ->where('id', $notificationId)
            ->whereIsNull('read_at')
            ->fetch();
    }

    /**
     * @param int $notificationId
     * @return void
     */
    public function markAsRead(int $notificationId): void
    {
        Notification::query()
            ->where('id', $notificationId)
            ->whereIsNull('read_at')
            ->update(['read_at' => Date::format('Y-m-d H:i:s')]);
    }

    /**
     * @param int $userId
     * @return void
     */
    public function updateByUserId(int $userId): void
    {
        Notification::query()
            ->where('user_id', $userId)
            ->whereIsNull('read_at')
            ->update(['read_at' => Date::format('Y-m-d H:i:s')]);
    }

    /**
     * @param int $userId
     * @return void
     */
    public function deleteByUserId(int $userId): void
    {
        Notification::query()->where('user_id', $userId)->delete();
    }

    /**
     * @param array $data
     * @return void
     */
    public function create(array $data): void
    {
        Notification::query()->insert($data);
    }
}
