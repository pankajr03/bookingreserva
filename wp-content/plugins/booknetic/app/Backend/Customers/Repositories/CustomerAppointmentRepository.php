<?php

namespace BookneticApp\Backend\Customers\Repositories;

use BookneticApp\Models\Appointment;

class CustomerAppointmentRepository
{
    public function getAppointmentCount($ids): int
    {
        return Appointment::where('customer_id', 'IN', $ids)->count();
    }
}
