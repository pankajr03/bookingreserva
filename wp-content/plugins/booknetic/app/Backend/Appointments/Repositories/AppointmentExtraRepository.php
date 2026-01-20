<?php

namespace BookneticApp\Backend\Appointments\Repositories;

use BookneticApp\Models\AppointmentExtra;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Helper;

class AppointmentExtraRepository
{
    public function create($appointmentId, array $data): int
    {
        AppointmentExtra::query()->insert([
            'appointment_id' => $appointmentId,
            'extra_id'		 =>	$data[ 'id' ],
            'quantity'		 =>	$data[ 'quantity' ],
            'price'			 =>	$data[ 'price' ],
            'duration'		 =>	( int ) $data[ 'duration' ]
        ]);

        return DB::lastInsertedId();
    }

    public function deleteByAppointmentId($appointmentId): void
    {
        AppointmentExtra::query()->where('appointment_id', $appointmentId)->delete();
    }

    /**
     * @return AppointmentExtra[]
     */
    public function getAllExtras(): array
    {
        $allExtras = AppointmentExtra::query()->leftJoin('extra', ['name']);

        if (Helper::isSaaSVersion()) {
            $allExtras = $allExtras->where(ServiceExtra::getField('tenant_id'), Permission::tenantId());
        }

        return $allExtras->fetchAll();
    }

    public function getAllExtrasByAppointmentId(int $appointmentId): array
    {
        return AppointmentExtra::query()->where('appointment_id', $appointmentId)->fetchAll();
    }
}
