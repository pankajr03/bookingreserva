<?php

namespace BookneticApp\Backend\Payments\Repositories;

use BookneticApp\Models\Appointment;
use BookneticApp\Models\AppointmentPrice;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\DB\QueryBuilder;

class PaymentRepository
{
    public function getPaymentsForDataTableQuery(): QueryBuilder
    {
        $totalPriceSubQuery = AppointmentPrice::where('appointment_id', DB::field(Appointment::getField('id')))
                                            ->select(DB::raw('SUM(price * negative_or_positive)'));

        return Appointment::leftJoin('customer', ['first_name', 'last_name', 'email', 'profile_image', 'phone_number'])
            ->leftJoin('staff', ['name', 'profile_image'])
            ->leftJoin('location', ['name'])
            ->leftJoin('service', ['name'])
            ->select([ "if(payment_status = 'paid', paid_amount, 0) as real_paid_amount" ])
            ->selectSubQuery($totalPriceSubQuery, 'total_amount');
    }
}
