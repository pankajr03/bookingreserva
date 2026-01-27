<?php

namespace BookneticApp\Backend\Base\Repository;

use BookneticApp\Models\Holiday;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;

class HolidayRepository
{
    public function getByStaffId(int $staffId): array
    {
        $holidays = Holiday::query()->where('staff_id', $staffId)->fetchAll();
        $result = [];

        foreach ($holidays as $holiday) {
            $result[Date::dateSQL($holiday['date'])] = $holiday['id'];
        }

        return $result;
    }

    public function deleteByStaffId(int $staffId): void
    {
        Holiday::query()->where('staff_id', $staffId)->delete();
    }

    public function insert(int $staffId, string $date): int
    {
        Holiday::query()->insert([
            'staff_id' => $staffId,
            'date'     => Date::dateSQL($date),
        ]);

        return Holiday::lastId();
    }
    public function getLastInsertedId(): int
    {
        return DB::lastInsertedId();
    }

    public function deleteMissing(int $staffId, array $keepIds): void
    {
        $query = Holiday::query()->where('staff_id', $staffId);
        if (!empty($keepIds)) {
            $query = $query->where('id', 'not in', $keepIds);
        }
        $query->delete();
    }
}
