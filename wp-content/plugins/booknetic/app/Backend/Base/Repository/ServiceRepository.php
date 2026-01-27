<?php

namespace BookneticApp\Backend\Base\Repository;

use BookneticApp\Models\Service;

class ServiceRepository
{
    public function getAll(): array
    {
        return Service::query()->select([
            'id', 'name'
        ])->fetchAll();
    }
}
