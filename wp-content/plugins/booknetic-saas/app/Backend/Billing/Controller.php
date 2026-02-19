<?php

namespace BookneticSaaS\Backend\Billing;

use BookneticApp\Providers\DB\DB;
use BookneticSaaS\Models\Plan;
use BookneticSaaS\Models\TenantBilling;
use BookneticSaaS\Providers\Helpers\Helper;
use BookneticSaaS\Providers\UI\DataTableUI;
use BookneticSaaS\Providers\Helpers\Date;
use BookneticApp\Providers\Core\Permission;

class Controller extends \BookneticApp\Providers\Core\Controller
{
    public function index()
    {
        $dataTable = new DataTableUI(
            TenantBilling::leftJoin('plan', 'name')
        );

        $dataTable->setTitle(bkntcsaas__('Billing'));

        $dataTable->searchBy(["created_at", 'status', 'payment_method', Plan::getField('name'), 'payment_cycle']);

        $dataTable->addColumns(bkntcsaas__('DATE'), 'created_at', ['type' => 'datetime']);
        $dataTable->addColumns(bkntcsaas__('EVENT'), function ($appointment) {
            if ($appointment['event_type'] === 'deposit_added') {
                return bkntcsaas__('Deposit added');
            }

            if ($appointment['event_type'] === 'payment_received') {
                return bkntcsaas__('Payment received');
            }

            if ($appointment['event_type'] === 'subscribed') {
                return bkntcsaas__('Subscribed');
            }

            return htmlspecialchars($appointment['event_type']);
        }, [ 'order_by_field' => 'event_type' ]);
        $dataTable->addColumns(bkntcsaas__('PLAN'), 'plan_name');
        $dataTable->addColumns(bkntcsaas__('AMOUNT'), 'amount', ['type' => 'price']);

        $dataTable->addColumns(bkntcsaas__('PAYMENT METHOD'), function ($payment) {
            return \BookneticSaaS\Providers\Helpers\Helper::paymentMethod($payment['payment_method']);
        }, ['order_by_field' => 'payment_method']);

        $dataTable->addColumns(bkntcsaas__('STATUS'), function ($appointment) {
            if ($appointment['status'] === 'pending') {
                $statusBtn = '<button type="button" class="btn btn-xs btn-light-warning">'.bkntcsaas__('Pending').'</button>';
            } elseif ($appointment['status'] === 'paid') {
                $statusBtn = '<button type="button" class="btn btn-xs btn-light-success">'.bkntcsaas__('OK').'</button>';
            } else {
                $statusBtn = '<button type="button" class="btn btn-xs btn-light-danger">'.bkntcsaas__('NOT OK').'</button>';
            }

            return $statusBtn;
        }, ['is_html' => true, 'order_by_field' => 'status']);

        $table = $dataTable->renderHTML();

        $tenantInf   = Permission::tenantInf();
        $currentPlan = Plan::get($tenantInf->plan_id);
        $expiresIn   = $tenantInf->expires_in;
        $hasExpired  = empty($expiresIn) || Date::epoch($expiresIn) < Date::epoch(Date::dateSQL());

        $subQuery = TenantBilling::where('status', 'paid')
            ->where('plan_id', DB::field(Plan::getField('id')))
            ->select('plan_id')
            ->limit(1);
        $subQuery1 = $subQuery->where('payment_cycle', 'monthly');
        $subQuery2 = $subQuery->where('payment_cycle', 'annually');

        $plans = Plan::orderBy('order_by')
            ->select('*')
            ->selectSubQuery($subQuery1, 'actual_monthly_discount')
            ->selectSubQuery($subQuery2, 'actual_annually_discount')
            ->where('is_active', 1)
            ->fetchAll();

        foreach ($plans as $plan) {
            $discountMultiplier = $plan->annually_price_discount > 0
            && empty($plan->actual_annually_discount)
                ? (100 - $plan->annually_price_discount) / 100
                : 1;

            $annualPrice = $plan->annually_price * $discountMultiplier;

            $plan->annual_monthly_breakdown = round($annualPrice / 12, 2);
        }

        $showMonthlyBreakdownOnAnnual =
            Helper::getOption(
                'show_monthly_breakdown_on_annual',
                'off'
            ) === 'on';

        $isAnnualPlanBadgeEnabled = Helper::getOption('is_annual_plan_badge_enabled', 0) == 1;
        $annualPlanBadgeText = Helper::getOption('annual_plan_badge_text', '');
        $annualPlanBadgeColor = Helper::getOption('annual_plan_badge_color', '');

        $this->view('index', [
            'table'                     =>  $table,
            'plans'                     =>  $plans,
            'payment_gateways_order'    =>  explode(',', \BookneticSaaS\Providers\Helpers\Helper::getOption('payment_gateways_order', 'stripe,paypal,woocommerce')),
            'current_plan'              =>  $currentPlan,
            'has_expired'               =>  $hasExpired,
            'expires_in'                =>  $expiresIn,
            'active_subscription'       =>  $tenantInf->active_subscription,
            'money_balance'             =>  $tenantInf->money_balance,
            'show_monthly_breakdown_on_annual' => $showMonthlyBreakdownOnAnnual,
            'is_annual_plan_badge_enabled' => $isAnnualPlanBadgeEnabled,
            'annual_plan_badge_text' => $annualPlanBadgeText,
            'annual_plan_badge_color' => $annualPlanBadgeColor
        ]);
    }
}
