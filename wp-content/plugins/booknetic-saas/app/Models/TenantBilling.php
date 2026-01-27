<?php

namespace BookneticSaaS\Models;

use BookneticApp\Providers\DB\MultiTenant;
use BookneticApp\Providers\DB\Model;

class TenantBilling extends Model
{
    use MultiTenant;

    protected static $tableName = 'tenant_billing';

    public static $relations = [
        'tenant'    => [ Tenant::class, 'id', 'tenant_id' ],
        'plan'      => [ Plan::class, 'id', 'plan_id' ]
    ];
}
