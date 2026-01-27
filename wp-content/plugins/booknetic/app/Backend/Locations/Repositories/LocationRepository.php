<?php

namespace BookneticApp\Backend\Locations\Repositories;

use BookneticApp\Models\Location;
use BookneticApp\Providers\DB\Collection;

class LocationRepository
{
    /**
     * @param int $id
     *
     * @return Location|Collection|null
     * */
    public function get(int $id): ?Collection
    {
        return Location::get($id);
    }

    public function count(): int
    {
        return Location::count();
    }

    public function deleteAll(array $ids): void
    {
        Location::where('id', $ids)->delete();
    }

    public function updateAll(array $ids, array $data)
    {
        Location::where('id', 'in', $ids)
                ->update($data);
    }

    public function update(int $id, $data): void
    {
        Location::where('id', $id)
                ->update($data);

        Location::handleTranslation($id);
    }

    public function create(array $data): int
    {
        Location::insert($data);

        $id = Location::lastId();

        Location::handleTranslation($id);

        return $id;
    }

    public function getAll(array $ids)
    {
        return Location::where('id', 'in', $ids)
                       ->fetchAll();
    }

    public function getAllLocations()
    {
        return Location::query()->fetchAll();
    }

    /**
     * @param string $search
     * @return Location[]
     */
    public function getMyAllEnabledLocations(string $search): array
    {
        return Location::my()->where('is_active', 1)
        ->where('name', 'LIKE', '%' . $search . '%')
        ->fetchAll();
    }
}
