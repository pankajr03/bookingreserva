<?php

namespace BookneticApp\Backend\Appearance\Repositories;

use BookneticApp\Models\Appearance;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\DB;

class AppearanceRepository
{
    public function getAllAppearances(): array
    {
        return Appearance::fetchAll();
    }

    public function getById(int $id): ?Collection
    {
        return Appearance::get($id);
    }

    public function delete(int $id): bool
    {
        Appearance::where('id', $id)->delete();

        return true;
    }

    public function insert(array $data): int
    {
        Appearance::insert($data);

        return DB::lastInsertedId();
    }

    public function update(int $id, array $data): int
    {
        Appearance::where('id', $id)->update($data);

        return $id;
    }

    public function selectDefaultAppearance(int $id): bool
    {
        Appearance::where('is_default', 1)->update([ 'is_default' => 0 ]);
        Appearance::whereId($id)->update([ 'is_default' => 1 ]);

        return true;
    }
}
