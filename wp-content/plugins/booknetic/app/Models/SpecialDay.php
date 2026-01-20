<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;

/**
 * @property-read int $id
 * @property-read int $service_id
 * @property-read int $staff_id
 * @property-read string $date
 * @property-read string $timesheet
 * @property-read int $tenant_id
 */
class SpecialDay extends Model
{
    use MultiTenant;

    public static $relations = [
        'service'       => [ Service::class, 'id', 'service_id' ],
        'staff'         => [ Staff::class, 'id', 'staff_id' ]
    ];
}
