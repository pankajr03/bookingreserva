<?php

namespace BookneticApp\Backend\Workflow\Actions;

use BookneticApp\Config;
use BookneticApp\Models\Customer;
use BookneticApp\Models\CustomerCategory;
use BookneticApp\Providers\Common\WorkflowDriver;

class SetCustomerCategory extends WorkflowDriver
{
    protected $driver = 'set_customer_category';

    public function __construct()
    {
        $this->setName(bkntc__('Set customer category'));
        $this->setEditAction('workflow_actions', 'set_customer_category_view');
    }

    public function handle($eventData, $actionSettings, $shortCodeService)
    {
        if (empty($eventData['customer_id'])) {
            return;
        }

        $actionData = json_decode($actionSettings['data'], true);

        if (empty($actionData)) {
            return;
        }

        $customerCategory = CustomerCategory::query()->where('id', $actionData['category_id'])->fetch();

        if ($customerCategory === null) {
            return;
        }

        $customer = Customer::query()->where('id', $eventData['customer_id'])->fetch();

        if ($customer === null) {
            return;
        }

        Customer::query()->where('id', $eventData['customer_id'])->update(['category_id' => $actionData['category_id']]);

        Config::getWorkflowEventsManager()->setEnabled(Config::getWorkflowEventsManager()->isEnabled());
    }
}
