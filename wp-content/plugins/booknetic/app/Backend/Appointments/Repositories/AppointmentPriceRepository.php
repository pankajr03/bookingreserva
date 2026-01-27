<?php

namespace BookneticApp\Backend\Appointments\Repositories;

use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Providers\DB\Collection;
use BookneticApp\Providers\Helpers\Math;

class AppointmentPriceRepository
{
    public function create(int $appointmentId, $data): void
    {
        AppointmentPrice::query()->insert([
            'appointment_id'       =>  $appointmentId,
            'unique_key'           =>  $data['unique_key'],
            'price'                =>  Math::abs($data['price'] ?? 0),
            'negative_or_positive' =>  $data['negative_or_positive'] ?? 0,
        ]);
    }
    public function deleteByAppointmentId($appointmentId): void
    {
        AppointmentPrice::query()->where('appointment_id', $appointmentId)->delete();
    }

    /**
     * @param int $appointmentId
     * @return Collection|AppointmentPrice
     */
    public function getTotalAmount(int $appointmentId): ?Collection
    {
        return AppointmentPrice::query()->where('appointment_id', $appointmentId)
            ->select('sum(price * negative_or_positive) as total_amount', true)
            ->fetch();
    }
}
