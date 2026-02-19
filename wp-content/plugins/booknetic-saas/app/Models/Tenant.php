<?php

namespace BookneticSaaS\Models;

use BookneticApp\Models\Appearance;
use BookneticApp\Models\Timesheet;
use BookneticApp\Providers\Core\Permission;
use BookneticApp\Providers\DB\Model;
use BookneticApp\Providers\Helpers\Date;
use BookneticSaaS\Integrations\PaymentGateways\Paypal;
use BookneticSaaS\Integrations\PaymentGateways\Stripe;
use BookneticSaaS\Providers\Helpers\Helper;

/**
 * @property-read int $id
 * @property int $user_id
 * @property string $email
 * @property string $full_name
 * @property string $domain
 * @property string $picture
 * @property int $plan_id
 * @property string $expires_in
 * @property string $inserted_at
 * @property string $invoice_id
 * @property string $remember_token
 * @property string $remember_token_sent_at
 * @property string $verified_at
 * @property string $active_subscription
 * @property string $logs
 * @property float $money_balance
 */
class Tenant extends Model
{
    public static $hasTriggers = false;

    public static $relations = [
        'billing'   => [ TenantBilling::class ],
        'plan'      => [ Plan::class, 'id', 'plan_id' ]
    ];

    public static function billingStatusUpdate($billing_id, $subscription, $expireDate = null, string $invoiceId = null)
    {
        $paymentInf = TenantBilling::noTenant()->get($billing_id);

        if ($paymentInf->status === 'paid') {
            return;
        }

        if ($paymentInf->payment_method === 'credit_card') {
            if ($paymentInf->invoice_id === $invoiceId) {
                return;
            }
        } else {
            if ($paymentInf && abs(Date::epoch() - Date::epoch($paymentInf->created_at)) < 3 * 24 * 60 * 60) {
                return;
            }
        }

        TenantBilling::noTenant()->where('id', $billing_id)->update([
            'status' => 'paid',
            'agreement_id' => $subscription,
            'event_type' => 'payment_received',
            'invoice_id' => $invoiceId
        ]);

        $paymentInf->status = 'paid';
        $paymentInf->agreement_id = $subscription;

        $tenantInf = Tenant::get($paymentInf->tenant_id);
        if (!empty($tenantInf->active_subscription)) {
            $activeBillingInfo = TenantBilling::noTenant()->where('agreement_id', $tenantInf->active_subscription)->fetch();
            if ($activeBillingInfo) {
                if ($activeBillingInfo->payment_method === 'paypal') {
                    $payment = new Paypal();
                    $payment->cancelSubscription($activeBillingInfo->agreement_id);
                } elseif ($activeBillingInfo->payment_method === 'credit_card') {
                    $payment = new Stripe();
                    $payment->cancelSubscription($activeBillingInfo->agreement_id);
                }
            }
        }

        if ($paymentInf->payment_method === 'credit_card') {
            $newExpireDate = Date::dateSQL($expireDate);
        } else {
            if (! empty($tenantInf->plan_id) && Date::epoch($tenantInf->expires_in) > Date::epoch()) {
                $newExpireDate = Date::dateSQL($tenantInf->expires_in, $paymentInf->payment_cycle === 'monthly' ? '+1 month' : '+1 year');
            } else {
                $newExpireDate = Date::dateSQL('now', $paymentInf->payment_cycle === 'monthly' ? '+1 month' : '+1 year');
            }
        }

        Tenant::where('id', $paymentInf->tenant_id)->update([
            'expires_in'            =>  $newExpireDate,
            'plan_id'               =>  $paymentInf->plan_id,
            'active_subscription'   =>  $paymentInf->agreement_id
        ]);

        do_action('bkntcsaas_tenant_subscribed', $paymentInf->tenant_id);
    }

    public static function paymentSucceded($subscriptionId, $billingId = null, $expireDate = null, $invoiceId = null)
    {
        $paymentInf = TenantBilling::noTenant()->where('agreement_id', $subscriptionId);

        if (! is_null($billingId)) {
            $paymentInf = $paymentInf->orWhere('id', $billingId);
        }

        $paymentInf = $paymentInf->fetch();

        if (!$paymentInf) {
            return false;
        }

        if (empty($paymentInf->agreement_id)) {
            self::billingStatusUpdate($paymentInf->id, $subscriptionId, $expireDate, $invoiceId);
        }

        // avoid dublicate insert
        $lastBillingInvoice = TenantBilling::noTenant()
            ->where('event_type', 'payment_received')
            ->where('agreement_id', $subscriptionId)
            ->orderBy('`created_at` DESC')
            ->limit(1)
            ->fetch();

        if ($paymentInf->payment_method === 'credit_card') {
            if (empty($paymentInf->agreement_id) && abs(Date::epoch() - Date::epoch($paymentInf->created_at)) < 3 * 24 * 60 * 60) {
                return true;
            }
        } else {
            if (isset($lastBillingInvoice) && abs(Date::epoch() - Date::epoch($lastBillingInvoice->created_at)) < 3 * 24 * 60 * 60) {
                return true;
            }
        }

        if ($lastBillingInvoice && $lastBillingInvoice->invoice_id === $invoiceId) {
            return true;
        }

        if (empty($paymentInf->agreement_id)) {
            self::billingStatusUpdate($paymentInf->id, $subscriptionId);
        }

        TenantBilling::noTenant()->insert([
            'event_type'            =>  'payment_received',
            'tenant_id'             =>  $paymentInf->tenant_id,
            'amount'                =>  $paymentInf->amount_per_cycle,
            'amount_per_cycle'      =>  $paymentInf->amount_per_cycle,
            'status'                =>  'paid',
            'created_at'            =>  Date::dateTimeSQL(),
            'plan_id'               =>  $paymentInf->plan_id,
            'invoice_id'            =>  $invoiceId,
            'payment_method'        =>  $paymentInf->payment_method,
            'payment_cycle'         =>  $paymentInf->payment_cycle,
            'error'                 =>  '',
            'agreement_id'          =>  $subscriptionId
        ]);

        $tenant = Tenant::query()->get($paymentInf->tenant_id);

        if ($tenant === null) {
            return false;
        }

        if ($paymentInf->payment_method === 'credit_card') {
            $newExpireDate = Date::dateTimeSQL($expireDate);
        } else {
            $newExpireDate = Date::dateSQL(
                $paymentInf->payment_cycle === 'monthly'
                    ? $tenant->expires_in . ' +1 month'
                    : $tenant->expires_in . ' +1 year'
            );
        }

        Tenant::query()
            ->where('id', $tenant->id)
            ->update([
                'expires_in'    =>  $newExpireDate,
                'plan_id'       =>  $paymentInf->plan_id
            ]);

        do_action('bkntcsaas_tenant_paid', $paymentInf->tenant_id);

        return true;
    }

    public static function unsubscribed($agreementId): bool
    {
        $tenant = Tenant::query()
            ->where('active_subscription', $agreementId)
            ->fetch();

        if ($tenant === null) {
            return false;
        }

        Tenant::query()
            ->where('id', $tenant->id)
            ->update([
                'active_subscription' => null
            ]);

        do_action('bkntcsaas_tenant_unsubscribed', $tenant->id);

        return true;
    }

    public static function createInitialData(int $tenantId): void
    {
        $appearances = Appearance::noTenant()->where('tenant_id', 'is', null)->fetchAll();
        foreach ($appearances as $appearance) {
            $appearance = $appearance->toArray();
            $appearance[ 'tenant_id' ] = $tenantId;
            unset($appearance['id']);

            Appearance::noTenant()->insert($appearance);
        }

        $defaultCurrency = Helper::getOption('tenant_default_currency', Helper::getOption('currency', 'USD'));
        $defaultCurrencySymbol = Helper::getOption('tenant_default_currency_symbol', Helper::getOption('currency_symbol', '$'));
        $defaultCurrencyFormat = Helper::getOption('tenant_default_currency_format', Helper::getOption('currency_format', '1'));

        Helper::setOption('currency', $defaultCurrency, $tenantId);
        Helper::setOption('currency_symbol', $defaultCurrencySymbol, $tenantId);
        Helper::setOption('currency_format', $defaultCurrencyFormat, $tenantId);

        Timesheet::noTenant()
            ->insert([
                'timesheet' => '[{"day_off":0,"start":"09:00","end":"18:00","breaks":[]},{"day_off":0,"start":"09:00","end":"18:00","breaks":[]},{"day_off":0,"start":"09:00","end":"18:00","breaks":[]},{"day_off":0,"start":"09:00","end":"18:00","breaks":[]},{"day_off":0,"start":"09:00","end":"18:00","breaks":[]},{"day_off":0,"start":"09:00","end":"18:00","breaks":[]},{"day_off":0,"start":"09:00","end":"18:00","breaks":[]}]',
                'tenant_id' => $tenantId
            ])
        ;

        do_action('bkntcsaas_tenant_created', $tenantId);
    }

    public static function haveEnoughBalanceToPay()
    {
        $tenantInf = Permission::tenantInf();
        $activeSub = $tenantInf->active_subscription ?? '';

        if (strpos($activeSub, 'balance_') !== 0) {
            return false;
        }

        $billingInf = TenantBilling::noTenant()->where('agreement_id', $tenantInf->active_subscription)->fetch();
        $amount = Helper::floor($billingInf->amount_per_cycle);
        $currentBalance = Helper::floor($tenantInf->money_balance);

        if ($amount > $currentBalance) {
            return false;
        }

        Tenant::where('id', Permission::tenantId())->update([
            'money_balance' => Helper::floor($currentBalance - $amount)
        ]);

        self::paymentSucceded($tenantInf->active_subscription);

        return true;
    }
}
