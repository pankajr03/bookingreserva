<?php

namespace BookneticApp\Backend\Customers\Repositories;

use BookneticApp\Models\Customer;
use BookneticApp\Models\CustomerCategory;
use BookneticApp\Providers\DB\Collection;

class CustomerCategoryRepository
{
    /**
     * @param int $id
     * @return Collection|CustomerCategory|null
     */
    public function get(int $id): ?Collection
    {
        return CustomerCategory::query()->get($id);
    }

    /**
     * @param array $data
     * @param bool $applyToUncategorizedCustomers
     * @return int
     */
    public function create(array $data, bool $applyToUncategorizedCustomers = false): int
    {
        CustomerCategory::query()->insert($data);

        $id = CustomerCategory::lastId();

        if ($data['is_default']) {
            $this->replaceDefault($id, $applyToUncategorizedCustomers);
        }

        return $id;
    }

    /**
     * @param int $id
     * @param array $data
     * @param bool $applyToUncategorizedCustomers
     * @return void
     */
    public function update(int $id, array $data, bool $applyToUncategorizedCustomers): void
    {
        CustomerCategory::query()
            ->where('id', $id)
            ->update($data);

        if ($data['is_default']) {
            $this->replaceDefault($id, $applyToUncategorizedCustomers);
        }
    }

    public function delete(array $ids): void
    {
        $defaultCategory = CustomerCategory::query()
            ->select(['id'])
            ->where('is_default', 1)
            ->fetch();

        $categoryIdToAssign = null;

        if ($defaultCategory !== null && !in_array($defaultCategory->id, $ids)) {
            $categoryIdToAssign = $defaultCategory->id;
        }

        Customer::query()
            ->where('category_id', 'in', $ids)
            ->update([
                'category_id' => $categoryIdToAssign
            ]);

        CustomerCategory::query()
            ->where('id', 'in', $ids)
            ->delete();
    }

    public function replaceDefault(int $id, bool $applyToUncategorizedCustomers): void
    {
        CustomerCategory::query()
            ->where('id', '<>', $id)
            ->update([
                'is_default' => 0
            ]);

        if ($applyToUncategorizedCustomers) {
            Customer::query()
                ->where('category_id', 'is', null)
                ->update([
                    'category_id' => $id
                ]);
        }
    }

    public function getUncategorizedCustomerCount(): int
    {
        return Customer::query()
            ->where('category_id', 'is', null)
            ->count();
    }

    /**
     * @return CustomerCategory|Collection|null
     */
    public function getDefaultCategory(): ?Collection
    {
        return CustomerCategory::query()->where('is_default', 1)->fetch();
    }
}
