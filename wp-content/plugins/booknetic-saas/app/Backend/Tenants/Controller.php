<?php

namespace BookneticSaaS\Backend\Tenants;

use BookneticApp\Providers\UI\Abstracts\AbstractDataTableUI;
use BookneticSaaS\Models\Tenant;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticSaaS\Providers\Helpers\TenantHelper;
use BookneticSaaS\Providers\UI\DataTableUI;

class Controller extends \BookneticApp\Providers\Core\Controller
{
    public function index()
    {
        $dataTable = new DataTableUI(
            Tenant::leftJoin('plan', 'name')
        );

        $dataTable->setIdFieldForQuery(Tenant::getField('id'));

        $dataTable->addAction('billing_history', bkntcsaas__('Payment history'));
        $dataTable->addAction('edit', bkntcsaas__('Edit'));
        $dataTable->addAction('delete', bkntcsaas__('Delete'), [static::class , '_delete'], AbstractDataTableUI::ACTION_FLAG_BULK_SINGLE);

        $dataTable->setTitle(bkntcsaas__('Tenants'));

        $dataTable->addNewBtn(bkntcsaas__('ADD TENANT'));

        $dataTable->searchBy(["full_name", 'email', 'domain']);

        $dataTable->addColumns(bkntcsaas__('ID'), 'id');
        $dataTable->addColumns(bkntcsaas__('DOMAIN'), 'domain');
        $dataTable->addColumns(bkntcsaas__('FULL NAME'), 'full_name');
        $dataTable->addColumns(bkntcsaas__('EMAIL'), 'email');
        $dataTable->addColumns(bkntcsaas__('PLAN'), 'plan_name');
        $dataTable->addColumns(bkntcsaas__('EXPIRES IN'), 'expires_in', ['type' => 'date']);
        $dataTable->addColumns(bkntcsaas__('STATUS'), function ($tenant) {
            if (! empty($tenant['active_subscription']) && Date::epoch(Date::dateSQL()) <= Date::epoch($tenant->expires_in)) {
                $statusBtn = '<button type="button" class="btn btn-xs btn-light-success text-nowrap">'.bkntcsaas__('Subscribed').'</button>';
            } elseif (! empty($tenant['active_subscription'])) {
                $statusBtn = '<button type="button" class="btn btn-xs btn-light-warning text-nowrap">'.bkntcsaas__('Expired').'</button>';
            } elseif (empty($tenant['verified_at'])) {
                $statusBtn = '<button type="button" class="btn btn-xs btn-light-warning text-nowrap">'.bkntcsaas__('Not activated').'</button><br/><button type="button" class="btn btn-xs btn-default mt-1 resend_activation_email_btn text-nowrap">'.bkntcsaas__('Resend activation email').'</button>';
            } else {
                $statusBtn = '<button type="button" class="btn btn-xs btn-light-danger text-nowrap">'.bkntcsaas__('Not subscribed').'</button>';
            }

            return $statusBtn;
        }, ['is_html' => true, 'order_by_field' => 'active_subscription']);

        $table = $dataTable->renderHTML();

        add_filter('bkntc_localization', function ($lang) {
            return array_merge([
                'are_you_sure_resend'       =>  bkntcsaas__('Are you sure you want to resend the activation email to the tenant?'),
                'resend'                    =>  bkntcsaas__('Resend'),
                'activation_sent_success'   =>  bkntcsaas__('The activation code was sent successfully!'),
            ], $lang);
        });

        $this->view('index', [
            'table' => $table
        ]);
    }

    public static function _delete($ids)
    {
        foreach ($ids as $id) {
            $tenant = Tenant::get($id);

            TenantHelper::delete($tenant);
        }
    }
}
