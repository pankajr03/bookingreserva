<?php

namespace BookneticApp\Backend\Staff\Repository;

use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\DB;

class StaffRepository
{
    /**
     * Insert a new staff record.
     *
     * @param array $data
     * @return int inserted ID
     */
    public function insert(array $data): int
    {
        $data['is_active'] = $data['is_active'] ?? 1;

        Staff::query()
            ->insert($data);

        return DB::lastInsertedId();
    }

    /**
     * Update existing staff.
     *
     * @param int $id
     * @param array $data
     * @return void
     */
    public function update(int $id, array $data): void
    {
        Staff::query()
            ->whereId($id)->update($data);
    }

    /**
     * Delete staff by ID.
     *
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        Staff::query()
            ->whereId($id)->delete();
    }

    /**
     * @param int $id
     * @return Staff|Collection|null
     */
    public function get(int $id): ?Collection
    {
        return Staff::query()->whereId($id)->fetch();
    }

    /**
     * Delegates translation handling to the model trait.
     *
     * @param int   $staffId
     * @param array|string $translations
     */
    public function handleTranslation(int $staffId, $translations): void
    {
        if (is_array($translations)) {
            $translations = json_encode($translations);
        }

        Staff::handleTranslation($staffId, $translations);
    }

    /**
     * @param string $search
     * @param int $location
     * @param int $service
     * @return Staff[]
     */
    public function getAll(string $search = '', int $location = 0, int $service = 0): array
    {
        $staff = Staff::query()->where('is_active', 1)
            ->where('name', 'like', "%$search%");

        if (!empty($location)) {
            $staff->whereFindInSet('locations', $location);
        }

        if (!empty($service)) {
            $serviceStaffSubQuery = ServiceStaff::query()->where('service_id', $service)->select('staff_id');
            $staff->where('id', 'IN', $serviceStaffSubQuery);
        }

        return $staff->fetchAll();
    }
}
