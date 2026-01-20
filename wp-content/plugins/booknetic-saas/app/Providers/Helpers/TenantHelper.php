<?php

namespace BookneticSaaS\Providers\Helpers;

use BookneticApp\Models\Location;
use BookneticApp\Models\Service;
use BookneticApp\Models\ServiceExtra;
use BookneticApp\Models\Staff;
use BookneticApp\Providers\Core\Permission as PermissionRegular;
use BookneticApp\Providers\DB\DB;
use BookneticApp\Providers\Helpers\Date;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\Tenant;

class TenantHelper
{
    private static array $tables = [
        'data',
        'tenant_custom_data',
        'appearance',
        'appointments',
        'customers',
        'holidays',
        'locations',
        'service_categories',
        'services',
        'special_days',
        'staff',
        'timesheet',
        'tenant_billing',
        'workflows',
        'workflow_logs',
    ];

    /**
     * @var Tenant $tenant
    */
    public static function delete($tenant)
    {
        do_action('bkntcsaas_tenant_deleted', $tenant->id);

        foreach (self::$tables as $table) {
            DB::DB()->delete(DB::table($table), [ 'tenant_id' => $tenant->id ]);
        }

        if ($tenant->user_id > 0) {
            $userData = get_userdata($tenant->user_id);
            if ($userData && $userData->roles == [ 'booknetic_saas_tenant' ]) {
                require_once ABSPATH . 'wp-admin/includes/user.php';
                wp_delete_user($tenant->user_id);
            }
        }

        Tenant::whereId($tenant->id)->delete();
    }

    public static function tables(): array
    {
        return self::$tables;
    }

    public static function restrictLimits($tenantId, $permissions)
    {
        $models = [
            'services'       => Service::where('is_active', 1),
            'staff'          => Staff::where('is_active', 1),
            'locations'      => Location::where('is_active', 1),
            'service_extras' => ServiceExtra::where('is_active', 1),
        ];

        foreach ($models as $key => $model) {
            /**
             * @var $model Service|Staff|Location|ServiceExtra
             */
            $model = $model->noTenant()->where('tenant_id', $tenantId);

            $entities = $model->select('id')->fetchAll();
            $limits = $permissions['limits'][$key . '_allowed_max_number'] ?? -1;

            if (count($entities) <= $limits || $limits == -1) {
                continue;
            }

            $toHideRaw = array_slice($entities, $limits);

            $toHide = array_map(function ($node) {
                return $node->id;
            }, $toHideRaw);

            $model->where('id', $toHide)->update([
                'is_active' => 0
            ]);
        }
    }

    public static function revertRestrictedLimits($tenantData)
    {
        if (! $tenantData) {
            return;
        }

        if (Tenant::$hasTriggers) {
            return;
        }

        Tenant::$hasTriggers = true;

        $tenantInf = $tenantData->fetch();

        $tenant = Tenant::where('user_id', $tenantInf->user_id)->fetch();
        $plan   = Plan::get($tenantInf->plan_id);

        $permissions = json_decode($plan->permissions, true);

        $oldExpiry = $tenant->expires_in;
        $newExpiry = $tenantInf->expires_in;
        $now       = Date::dateSQL();

        if (
            $plan->expire_plan != 1 &&
            Date::epoch($now) > Date::epoch($oldExpiry) &&
            Date::epoch($now) <= Date::epoch($newExpiry)
        ) {
            PermissionRegular::setTenantId($tenant->id);

            $models = [
                'services'  => Service::where('is_active', 0),
                'staff'     => Staff::where('is_active', 0),
                'locations' => Location::where('is_active', 0),
            ];

            foreach ($models as $key => $model) {
                $limits = $permissions['limits'][ $key . '_allowed_max_number' ];

                if ($limits != - 1) {
                    $model->limit($limits)->update([ 'is_active' => 1 ]);
                } else {
                    $model->update([ 'is_active' => 1 ]);
                }
            }

            PermissionRegular::setTenantId(- 1);
        }
    }
}
