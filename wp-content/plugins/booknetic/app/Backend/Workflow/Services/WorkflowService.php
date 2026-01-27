<?php

namespace BookneticApp\Backend\Workflow\Services;

use BookneticApp\Models\CustomerCategory;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Helpers\Helper;

class WorkflowService
{
    public function getLocations(string $query): array
    {
        $locations = Location::query()
            ->select([
                'id',
                'name'
            ])
            ->like('name', $query)
            ->fetchAll();

        $data = [];

        foreach ($locations as $location) {
            $data[] = [
                'id' => (int)$location['id'],
                'text' => htmlspecialchars($location['name'])
            ];
        }

        return $data;
    }

    public function getServices(string $query): array
    {
        $services = Service::query()
            ->select([
                'id',
                'name'
            ])
            ->like('name', $query)
            ->fetchAll();

        $data = [];

        foreach ($services as $service) {
            $data[] = [
                'id' => (int)$service['id'],
                'text' => htmlspecialchars($service['name'])
            ];
        }

        return $data;
    }

    public function getStaffs(string $query): array
    {
        $staffs = Staff::query()
            ->select([
                'id',
                'name'
            ])
            ->like('name', $query)
            ->fetchAll();

        $data = [];

        foreach ($staffs as $staff) {
            $data[] = [
                'id' => (int)$staff['id'],
                'text' => htmlspecialchars($staff['name'])
            ];
        }

        return $data;
    }

    public function getStatuses(): array
    {
        $data = [];

        foreach (Helper::getAppointmentStatuses() as $statusKey => $value) {
            $data[] = [
                'id' => $statusKey,
                'text' => htmlspecialchars($value['title'])
            ];
        }

        return $data;
    }

    public function getCategories(string $query): array
    {
        $categories = CustomerCategory::query()
            ->select([
                'id',
                'name'
            ])
            ->like('name', $query)
            ->fetchAll();

        $data = [];

        foreach ($categories as $category) {
            $data[] = [
                'id' => (int)$category['id'],
                'text' => htmlspecialchars($category['name'])
            ];
        }

        return $data;
    }
}
