<?php

namespace BookneticApp\Models;

use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\DB\MultiTenant;
use BookneticApp\Providers\DB\QueryBuilder;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $color
 * @property-read string $icon
 * @property-read bool $is_default
 * @property-read string $note
 * @property-read int $created_by
 * @property-read int $created_at
 * @property-read int $tenant_id
 */
class CustomerCategory extends Model
{
    use MultiTenant {
        booted as private tenantBoot;
    }
    protected static bool $timeStamps = true;
    protected static bool $enableOwnershipFields = true;

    protected static $tableName = 'customer_categories';
    public static function booted()
    {
        self::tenantBoot();

        self::addGlobalScope('my_customers', function (QueryBuilder $builder, $queryType) {
            if (! Permission::isBackEnd() || Permission::isAdministrator()) {
                return;
            }

            if (apply_filters('bkntc_query_builder_global_scope', false, 'customers')) {
                return;
            }
        });
    }
}
