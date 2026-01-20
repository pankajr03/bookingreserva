<?php

namespace BookneticApp\Backend\Services\Repositories;

use BookneticApp\Models\ServiceExtra;

class ServiceExtraRepository
{
    /**
     * @return ServiceExtra[]
     */
    public function getAll($serviceId = null): array
    {
        $query = ServiceExtra::query();
        if (empty($serviceId)) {
            return $query->fetchAll();
        }

        return $query->where('service_id', $serviceId)->fetchAll();
    }

    public function getByServiceIdAndExtraId(int $serviceId, int $extraId): ?ServiceExtra
    {
        $query = ServiceExtra::query()->where('service_id', $serviceId)->where('id', $extraId);

        return $query->fetch();
    }
}
