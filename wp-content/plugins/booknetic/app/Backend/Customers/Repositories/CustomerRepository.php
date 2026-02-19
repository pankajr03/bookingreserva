<?php

namespace BookneticApp\Backend\Customers\Repositories;

use BookneticApp\Backend\Customers\DTOs\Request\CustomerFilterRequest;
use BookneticApp\Models\Appointment;
use BookneticApp\Models\Customer;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\DB;

class CustomerRepository
{
    /**
     * @param int $id
     * @return Customer|Collection|null
     */
    public function get(int $id): ?Collection
    {
        return Customer::query()->leftJoin('category', ['name'])->get($id);
    }

    /**
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        Customer::query()->insert($data);

        return Customer::lastId();
    }

    /**
     * @param int $id
     * @param array $data
     * @return void
     */
    public function update(int $id, array $data): void
    {
        Customer::query()->where('id', $id)->update($data);
    }

    /**
     * @param string $email
     * @return int
     */
    public function getCustomerCountByEmail(string $email): int
    {
        return  Customer::noTenant()->where('email', $email)->count();
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id): void
    {
        Customer::query()->where('id', $id)->delete();
    }

    /**
     * @param int $id
     * @return int
     */
    public function getCustomerCountByWpUserId(int $id): int
    {
        return Customer::noTenant()->where('user_id', $id)->count();
    }

    /**
     * @return array{
     *     data: Customer[],
     *     total: int
     * }
     */
    public function getAll(CustomerFilterRequest $request): array
    {
        $query = Customer::query()->select([
            'id',
            'first_name',
            'last_name',
            "email",
            "phone_number",
            "birthdate",
            'gender',
            'user_id',
            'notes',
            'profile_image as profile_image_url',
        ])
            ->selectSubQuery(
                Appointment::query()->withoutGlobalScope('mobile_app')
                    ->where('customer_id', '=', DB::field('id', 'customers'))
                    ->select('created_at', true)
                    ->orderBy('created_at desc')
                    ->limit(1),
                'last_appointment_date'
            );

        if (!empty($request->getOrderBy())) {
            $query->orderBy(sprintf(
                '%s %s',
                $request->getOrderBy(),
                $request->getOrderDirection()
            ));
        } else {
            $query->orderBy('id desc');
        }

        if (!empty($request->getSearch())) {
            $query->where(function ($query) use ($request) {
                $query->where("CONCAT(first_name, ' ', last_name)", 'LIKE', '%' . $request->getSearch() . '%')
                    ->orWhere('email', 'LIKE', '%' . $request->getSearch() . '%')
                    ->orWhere('phone_number', 'LIKE', '%' . $request->getSearch() . '%');
            });
        }

        $total = $query->count();

        if (!empty($request->getSkip())) {
            $query->offset($request->getSkip());
        }

        if (!empty($request->getLimit())) {
            $query->limit($request->getLimit());
        }

        $customersData = $query->fetchAll();

        return [
            'data' => $customersData,
            'total' => $total,
        ];
    }
}
