<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;
use BookneticApp\Providers\DB\QueryBuilder;

/**
 * @property-read int $id
 * @property int staff_id
 * @property int date
 * @property int start_time
 * @property int duration
 * @property string notes
 * @property string event_id
 * @property string module
 * @property int $tenant_id
 */
class CoreStaffBusySlot extends Model
{
    use MultiTenant {
        booted as private tenantBoot;
    }
    public static $relations = [
        'staff'                 => [ Staff::class, 'id', 'staff_id' ]
    ];

    public static function booted()
    {
        self::tenantBoot();

        self::addGlobalScope('staff_id', function (QueryBuilder $builder, $queryType) {
            if (! Permission::isBackEnd() || Permission::isAdministrator()) {
                return;
            }

            $builder->where('staff_id', Permission::myStaffId());
        });
    }
}
