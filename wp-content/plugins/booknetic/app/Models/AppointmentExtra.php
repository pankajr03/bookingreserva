<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;

/**
 * @property-read int $id
 * @property-read int $appointment_id
 */
class AppointmentExtra extends Model
{
    public static $relations = [
        'customer'  =>  [ Customer::class ],
        'extra'     =>  [ ServiceExtra::class, 'id', 'extra_id' ]
    ];
}
