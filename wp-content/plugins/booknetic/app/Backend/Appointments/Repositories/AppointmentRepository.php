<?php

namespace BookneticApp\Backend\Appointments\Repositories;

use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentExtra;
use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Models\Customer;
use BookneticApp\Models\Data;
use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;

class AppointmentRepository
{
    public function update(int $appointmentId, array $data): int
    {
        Appointment::query()->where('id', $appointmentId)->update($data);

        return $appointmentId;
    }

    public function delete(int $appointmentId): bool
    {
        Appointment::query()->where('id', $appointmentId)->delete();
        Data::query()->where('row_id', $appointmentId)->where('table_name', Appointment::getTableName())->delete();

        return true;
    }

    /**
     * @param int $appointmentId
     * @return Appointment|null
     */
    public function get(int $appointmentId): ?Collection
    {
        return Appointment::query()->get($appointmentId);
    }

    /**
     * @param int $appointmentId
     * @return Appointment|null
     */
    public function getDetails(int $appointmentId): ?Collection
    {
        return Appointment::query()->
        leftJoin('customer', ['first_name', 'last_name', 'phone_number', 'email', 'profile_image'])
            ->leftJoin('location', ['name'])
            ->leftJoin('service', ['name'])
            ->leftJoin('staff', ['name', 'profile_image', 'email', 'phone_number'])
            ->where(Appointment::getField('id'), $appointmentId)->fetch();
    }

    public function getSearchByColumns(): array
    {
        return [
            Appointment::getField('id'),
            Location::getField('name'),
            Service::getField('name'),
            Staff::getField('name'),
            'CONCAT(' . Customer::getField('first_name') . ", ' ', " . Customer::getField('last_name') . ')',
            Customer::getField('email'),
            Customer::getField('phone_number'),
        ];
    }

    private function addFiltersQuery($query, array $filters): void
    {
        if (!empty($filters['starts_at'])) {
            $query->where('starts_at', '<=', Date::epoch($filters['starts_at'], '+1 day'));
        }
        if (!empty($filters['ends_at'])) {
            $query->where('ends_at', '<=', Date::epoch($filters['ends_at']));
        }
        if (!empty($filters['service_id']) && is_numeric($filters['service_id'])) {
            $query->where('service_id', $filters['service_id']);
        }
        if (!empty($filters['customer_id']) && is_numeric($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }
        if (!empty($filters['staff_id']) && is_numeric($filters['staff_id'])) {
            $query->where('staff_id', $filters['staff_id']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['is_finished'])) {
            if ($filters['is_finished'] == 1) {
                $query->where(Appointment::getField('ends_at'), '<', Date::epoch());
            } else {
                $query->where(Appointment::getField('starts_at'), '>', Date::epoch());
            }
        }
    }

    private function orderByField($query, array $orderBy): void
    {
        $orderByFields = [
            'starts_at'            => 'starts_at',
            'customer_name'        => 'customer_first_name',
            'staff_name'           => 'staff_name',
            'service_name'         => 'service_name',
            'duration'             => '( ends_at - starts_at )',
            'created_at'           => 'created_at',
        ];

        if (!array_key_exists($orderBy['field'], $orderByFields)) {
            return;
        }

        $type = 'ASC';

        if (
            isset($orderBy['type']) &&
            in_array(strtoupper($orderBy['type']), ['ASC', 'DESC'])
        ) {
            $type = strtoupper($orderBy['type']);
        }

        $query->orderBy("{$orderByFields[$orderBy['field']]} $type");
    }

    public function getAppointmentsQuery()
    {
        $totalPrice = AppointmentPrice::query()->where('appointment_id', DB::field(Appointment::getField('id')))->select(DB::raw('sum(price * negative_or_positive)'));

        return Appointment::query()->leftJoin('customer', [ 'first_name', 'last_name', 'email', 'profile_image', 'phone_number' ])
            ->leftJoin('staff', [ 'name', 'profile_image' ])
            ->leftJoin('location', [ 'name' ])
            ->leftJoin('service', [ 'name' ])
            ->select([ Appointment::getField('*') ])
            ->selectSubQuery($totalPrice, 'total_price');
    }

    /**
     * @param array $filters
     * @param array $orderBy
     * @param string $search
     * @param int $skip
     * @param int $limit
     * @return array{
     *   data: Collection[]|Appointment[],
     *   total: int
     * }
     */
    public function getAppointments(array $filters, array $orderBy, string $search, int $skip, int $limit = 12): array
    {
        $appointments = $this->getAppointmentsQuery();

        if (!empty($filters)) {
            $this->addFiltersQuery($appointments, $filters);
        }

        if (!empty($search)) {
            $search = esc_sql($search);
            $searchByColumns = $this->getSearchByColumns();
            if (!empty($searchByColumns)) {
                $appointments->where(function ($query) use ($searchByColumns, $search) {
                    foreach ($searchByColumns as $column) {
                        $query->orWhere($column, 'like', '%' . $search . '%');
                    }
                });
            }
        }

        if (!empty($orderBy['field'])) {
            $this->orderByField($appointments, $orderBy);
        }

        $total = $appointments->count();

        if (! empty($skip)) {
            $appointments->offset($skip);
        }

        if (! empty($limit)) {
            $appointments->limit($limit);
        }

        $appointmentsData = $appointments->orderBy('id DESC')->fetchAll();

        return [
            'total' => $total,
            'data' => $appointmentsData
        ];
    }

    /**
     * @param int $appointmentId
     * @return Collection[]
     */
    public function getExtras(int $appointmentId): array
    {
        return AppointmentExtra::query()->
            where('appointment_id', $appointmentId)
                ->leftJoin(ServiceExtra::class, ['name', 'image'], ServiceExtra::getField('id'), AppointmentExtra::getField('extra_id'))
                ->fetchAll();
    }
}
