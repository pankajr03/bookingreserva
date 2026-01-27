<?php

namespace BookneticApp\Backend\Base\Repository;

use BookneticApp\Models\SpecialDay;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\DB;

class SpecialDayRepository
{
    /**
     * @param int $staffId
     * @return array<SpecialDay|Collection>
     */
    public function getByStaffId(int $staffId): array
    {
        return SpecialDay::query()->where('staff_id', $staffId)->fetchAll();
    }

    public function insert(array $data): int
    {
        SpecialDay::query()->insert([
            'staff_id'  => $data['staff_id'],
            'date'      => $data['date'],
            'timesheet' => $data['timesheet'],
        ]);

        return SpecialDay::lastId();
    }

    public function updateByIdAndStaffId(int $id, int $staffId, array $data): void
    {
        SpecialDay::query()->where('id', $id)
            ->where('staff_id', $staffId)
            ->update([
                'date'      => $data['date'],
                'timesheet' => $data['timesheet'],
            ]);
    }
    public function deleteMissing(int $staffId, array $keepIds): void
    {
        $query = SpecialDay::query()->where('staff_id', $staffId);

        if (!empty($keepIds)) {
            $query = $query->where('id', 'not in', $keepIds);
        }

        $query->delete();
    }

    public function getLastInsertedId(): int
    {
        return DB::lastInsertedId();
    }
}
