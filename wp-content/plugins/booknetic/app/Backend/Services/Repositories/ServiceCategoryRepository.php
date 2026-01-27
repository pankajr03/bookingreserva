<?php

namespace BookneticApp\Backend\Services\Repositories;

use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceCategory;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\QueryBuilder;

class ServiceCategoryRepository
{
    /**
     * @param $id
     * @return ServiceCategory|Collection|null
     */
    public function get($id): ?Collection
    {
        return ServiceCategory::query()->get($id);
    }

    /**
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        ServiceCategory::query()->insert($data);

        return ServiceCategory::lastId();
    }

    /**
     * @param int $id
     * @param array $data
     * @return void
     */
    public function update(int $id, array $data): void
    {
        ServiceCategory::query()
            ->where('id', $id)
            ->update($data);

        ServiceCategory::handleTranslation($id);
    }

    /**
     * @param string $name
     * @param int $parent
     * @param int|null $id
     * @return Collection|null
     */
    public function checkIfNameExist(string $name, int $parent, int $id = null): ?Collection
    {
        $query = ServiceCategory::query()
            ->withoutGlobalScope('my_service_categories')
            ->where('name', $name)
            ->where('parent_id', $parent);

        if ($id !== null) {
            $query->where('id', '!=', $id);
        }

        return $query->fetch();
    }

    /**
     * @return QueryBuilder
     */
    public function getTenantQuery(): QueryBuilder
    {
        return  ServiceCategory::query()
            ->leftJoinSelf(
                'parent_category',
                ['name'],
                ServiceCategory::getField('parent_id'),
                'parent_category.id'
            )
            ->select([
                ServiceCategory::getField('id'),
                ServiceCategory::getField('name'),
                ServiceCategory::getField('parent_id AS parent'),
                'parent_category.name AS parent_name'
            ]);
    }

    /**
     * @return array
     */
    public function getAllWithParent(): array
    {
        return $this->getTenantQuery()->fetchAll();
    }

    /**
     * @param array $ids
     * @return int
     */
    public function getSubCategoryCount(array $ids): int
    {
        return ServiceCategory::query()
            ->where('parent_id', 'IN', $ids)
            ->count();
    }

    /**
     * @param array $ids
     * @return int
     */
    public function getServiceByCategory(array $ids): int
    {
        return Service::query()
            ->where('category_id', 'IN', $ids)
            ->count();
    }

    /**
     * @param array $ids
     * @return void
     */
    public function delete(array $ids): void
    {
        ServiceCategory::query()
            ->where('id', 'IN', $ids)
            ->delete();
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return ServiceCategory::query()
            ->select(['id', 'name'])
            ->fetchAll();
    }
}
