<?php

declare(strict_types=1);

namespace BookneticApp\Backend\Workflow\Services\WorkflowEventServices\Abstracts;

use BookneticApp\Backend\Workflow\Repositories\WorkflowRepository;
use BookneticApp\Models\CustomerCategory;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\Staff;

abstract class BaseEventService
{
    protected WorkflowRepository $repository;

    public function __construct()
    {
        $this->repository = new WorkflowRepository();
    }

    abstract public function getEventParams(int $id): array;

    protected function getCommonSelectionData(array $idsByType): array
    {
        $data = [];

        if (!empty($idsByType['locations']) && is_array($idsByType['locations'])) {
            $locations = Location::query()
                ->select(['id', 'name'])
                ->where('id', 'IN', $idsByType['locations'])
                ->fetchAllAsArray();

            if (!empty($locations)) {
                $data['locations'] = $locations;
            }
        }

        if (!empty($idsByType['services']) && is_array($idsByType['services'])) {
            $services = Service::query()
                ->select(['id', 'name'])
                ->where('id', 'IN', $idsByType['services'])
                ->fetchAllAsArray();

            if (!empty($services)) {
                $data['services'] = $services;
            }
        }

        if (!empty($idsByType['staffs']) && is_array($idsByType['staffs'])) {
            $staffs = Staff::query()
                ->select(['id', 'name'])
                ->where('id', 'IN', $idsByType['staffs'])
                ->fetchAllAsArray();

            if (!empty($staffs)) {
                $data['staffs'] = $staffs;
            }
        }

        if (!empty($idsByType['categories']) && is_array($idsByType['categories'])) {
            $categories = CustomerCategory::query()
                ->select(['id', 'name'])
                ->where('id', 'IN', $idsByType['categories'])
                ->fetchAllAsArray();

            if (!empty($categories)) {
                $data['categories'] = $categories;
            }
        }

        return $data;
    }

    protected function getCommonParams(array $data): array
    {
        return [
            'locale' => $data['locale'] ?? get_locale(),
            'locales' => $this->getLocales(),
            'called_from' => $data['called_from'] ?? '',
            'call_from' => [
                'both' => bkntc__('Both'),
                'backend' => bkntc__('Backend'),
                'frontend' => bkntc__('Frontend'),
            ],
        ];
    }

    protected function getLocales(): array
    {
        require_once ABSPATH . 'wp-admin/includes/translation-install.php';

        $availableLocales = wp_get_available_translations();

        array_unshift($availableLocales, [
            'language' => '',
            'iso' => [''],
            'native_name' => bkntc__('Any locale')
        ], [
            'language' => 'en_US',
            'iso' => ['en'],
            'native_name' => 'English (United States)'
        ]);

        return $availableLocales ?? [];
    }
}
