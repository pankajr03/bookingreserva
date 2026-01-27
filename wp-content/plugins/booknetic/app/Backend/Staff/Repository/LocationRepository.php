<?php

namespace BookneticApp\Backend\Staff\Repository;

use BookneticApp\Models\Location;
use BookneticApp\Providers\DB\Collection;

class LocationRepository
{
    /**
     * @return array<Location|Collection>
     */
    public function getAllForCurrentUser(): array
    {
        return Location::my()->select([
            'id', 'name'
        ])->fetchAll();
    }
}
