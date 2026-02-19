<?php

namespace BookneticApp\Backend\Services\Repositories;

use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceStaff;
use BookneticApp\Models\Staff;

class ServiceRepository
{
    /**
     * @param string $search
     * @param int $category
     * @return array
     */
    public function getServices(string $search, int $category = 0): array
    {
        $allowedStaffIDs = array_column(Staff::query()->fetchAll(), 'id');

        $services = Service::query()->where('is_active', 1);

        if (! empty($category)) {
            $services = $services->where('category_id', $category);
        }

        if (! empty($search)) {
            $services = $services->like('name', $search);
        }

        $data = [];

        foreach ($services->fetchAll() as $service) {
            $isAllowedServiceForStaff = ServiceStaff::query()->where('staff_id', $allowedStaffIDs)->where('service_id', $service->id)->count();

            if ($isAllowedServiceForStaff == 0) {
                continue;
            }

            $data[] = [
                'id'				=>	(int)$service['id'],
                'text'				=>	htmlspecialchars($service['name']),
                'repeatable'		=>	(int)$service['is_recurring'],
                'repeat_type'		=>	htmlspecialchars((string)$service['repeat_type']),
                'repeat_frequency'	=>	htmlspecialchars((string)$service['repeat_frequency']),
                'full_period_type'	=>	htmlspecialchars((string)$service['full_period_type']),
                'full_period_value'	=>	(int)$service['full_period_value'],
                'max_capacity'		=>	(int)$service['max_capacity'],
                'date_based'		=>	$service['duration'] >= 1440
            ];
        }

        return $data;
    }

    public function getAllByIds(array $ids): array
    {
        return Service::query()->where('id', 'in', $ids)->fetchAll();
    }
}
