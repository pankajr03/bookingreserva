<?php

namespace BookneticApp\Backend\Staff\Repository;

use BookneticApp\Models\ServiceStaff;

class ServiceStaffRepository
{
    public function deleteByStaffId(int $staffId): void
    {
        ServiceStaff::query()->where('staff_id', $staffId)->delete();
    }

    public function insert(int $staffId, int $serviceId, float $price = -1, float $deposit = -1, string $depositType = 'percent'): int
    {
        ServiceStaff::query()->insert([
            'staff_id' => $staffId,
            'service_id' => $serviceId,
            'price' => $price,
            'deposit' => $deposit,
            'deposit_type' => $depositType,
        ]);

        return ServiceStaff::lastId();
    }

    /**
     * @param int $staffId
     * @return int[]
     */
    public function getIdsByStaffId(int $staffId): array
    {
        return array_map(
            static fn ($s) => (int)$s->service_id,
            ServiceStaff::query()->select(['service_id'])->where('staff_id', $staffId)->fetchAll()
        );
    }
}
